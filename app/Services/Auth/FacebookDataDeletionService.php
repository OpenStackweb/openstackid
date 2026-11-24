<?php namespace App\Services\Auth;
/**
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use App\Models\Utils\BaseEntity;
use App\Services\AbstractService;
use Auth\Repositories\IUserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Facades\URL;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\RandomGenerator;
use Utils\Db\ITransactionService;

/**
 * Class FacebookDataDeletionService
 * @package App\Services\Auth
 */
final class FacebookDataDeletionService extends AbstractService implements IFacebookDataDeletionService
{
    const Table = 'facebook_deletion_requests';

    const StatusCompleted = 'completed';

    const StatusNotFound = 'not_found';

    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * FacebookDataDeletionService constructor.
     * @param IUserRepository $user_repository
     * @param ITransactionService $tx_service
     */
    public function __construct(IUserRepository $user_repository, ITransactionService $tx_service)
    {
        parent::__construct($tx_service);
        $this->user_repository = $user_repository;
    }

    /**
     * Resolved fresh on every call (never cached on the instance), matching
     * DoctrineRepository::getEntityManager() - DoctrineTransactionService::transaction()
     * can swap in a new EntityManager mid-request on a retryable connection error
     * (Registry::resetManager()), and a cached reference would then point at a
     * closed connection.
     * @return EntityManagerInterface
     */
    private function getEntityManager(): EntityManagerInterface
    {
        return Registry::getManager(BaseEntity::EntityManager);
    }

    /**
     * @inheritDoc
     */
    public function processDeletionRequest(string $external_id, string $provider = 'facebook'): array
    {
        return $this->tx_service->transaction(function () use ($external_id, $provider) {

            $existing = $this->findRequest($provider, $external_id);
            if (!is_null($existing)) {
                return $this->toResult($existing);
            }

            $user = $this->user_repository->getByExternalId($provider, $external_id);

            $status = self::StatusNotFound;
            $user_id = null;

            if (!is_null($user)) {
                $user->setExternalId(null);
                $user->setExternalProvider(null);
                $user->setExternalPic(null);
                $user_id = $user->getId();
                $status = self::StatusCompleted;
            }

            $confirmation_code = (new RandomGenerator())->randomToken('sha256');

            // Inserted through the same Doctrine DBAL connection the user-entity
            // flush below uses (via $this->tx_service->transaction()), so both
            // writes commit or roll back together - using Laravel's separately
            // autocommitting `DB` facade connection here would let this row
            // persist even if the flush that actually nulls the user's fields
            // fails afterward.
            try {
                $this->getEntityManager()->getConnection()->insert(self::Table, [
                    'provider' => $provider,
                    'external_id' => $external_id,
                    'confirmation_code' => $confirmation_code,
                    'status' => $status,
                    'user_id' => $user_id,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ]);
            } catch (UniqueConstraintViolationException $ex) {
                // a concurrent request already inserted the row for this
                // (provider, external_id) pair before this one committed -
                // two independent statements racing the same unique index.
                // Treat this as the idempotent path instead of failing the request.
                $existing = $this->findRequest($provider, $external_id);
                if (!is_null($existing)) {
                    return $this->toResult($existing);
                }
                throw $ex;
            }

            return $this->toResult((object)[
                'confirmation_code' => $confirmation_code,
                'status' => $status,
            ]);
        });
    }

    /**
     * @inheritDoc
     */
    public function getStatus(string $confirmation_code): ?array
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT * FROM ' . self::Table . ' WHERE confirmation_code = ?',
            [$confirmation_code]
        );
        return $row === false ? null : $row;
    }

    /**
     * @param string $provider
     * @param string $external_id
     * @return object|null
     */
    private function findRequest(string $provider, string $external_id)
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT * FROM ' . self::Table . ' WHERE provider = ? AND external_id = ?',
            [$provider, $external_id]
        );
        return $row === false ? null : (object)$row;
    }

    /**
     * @param object $row
     * @return array
     */
    private function toResult(object $row): array
    {
        return [
            'confirmation_code' => $row->confirmation_code,
            'status' => $row->status,
            'url' => URL::route('facebook_data_deletion_status', ['confirmation_code' => $row->confirmation_code]),
        ];
    }
}

<?php namespace App\Http\Controllers\Api;
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
use App\Http\Controllers\Controller;
use App\libs\Auth\FacebookSignedRequestParser;
use App\Services\Auth\IFacebookDataDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class FacebookDataDeletionController
 * Implements Facebook's Data Deletion Request Callback.
 * @see https://developers.facebook.com/documentation/development/create-an-app/app-dashboard/data-deletion-callback
 * @package App\Http\Controllers\Api
 */
final class FacebookDataDeletionController extends Controller
{
    /**
     * @var IFacebookDataDeletionService
     */
    private $service;

    /**
     * FacebookDataDeletionController constructor.
     * @param IFacebookDataDeletionService $service
     */
    public function __construct(IFacebookDataDeletionService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $signed_request = $request->input('signed_request');

        if (empty($signed_request)) {
            Log::warning("FacebookDataDeletionController::handle missing signed_request");
            return response()->json([
                'error' => ['code' => 'invalid_request', 'message' => 'signed_request is required.']
            ], 400);
        }

        $secret = Config::get('services.facebook.client_secret');
        $data = FacebookSignedRequestParser::parse($signed_request, $secret);

        if (is_null($data)) {
            Log::warning("FacebookDataDeletionController::handle invalid signed_request");
            return response()->json([
                'error' => ['code' => 'invalid_signature', 'message' => 'Invalid signed_request.']
            ], 400);
        }

        $result = $this->service->processDeletionRequest((string)$data['user_id']);

        return response()->json([
            'url' => $result['url'],
            'confirmation_code' => $result['confirmation_code'],
        ], 200);
    }

    /**
     * @param Request $request
     * @param string $confirmation_code
     * @return \Illuminate\Contracts\View\View
     */
    public function status(Request $request, string $confirmation_code)
    {
        $result = $this->service->getStatus($confirmation_code);

        if (is_null($result)) {
            abort(404);
        }

        return view('auth.facebook_data_deletion_status', ['result' => $result]);
    }
}

<?php namespace App\ModelSerializers\Auth;
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

use App\libs\Auth\Models\UserTrustedDevice;
use App\ModelSerializers\BaseSerializer;

/**
 * Class UserTrustedDeviceSerializer
 *
 * Never exposes device_identifier (the SHA-256 of the device-trust cookie
 * token) nor the raw user agent.
 *
 * @package App\ModelSerializers\Auth
 */
final class UserTrustedDeviceSerializer extends BaseSerializer
{
    /**
     * Pass the hashed device identifier of the current request's device-trust
     * cookie under this key to get the "is_current" flag computed.
     */
    public const ParamCurrentDeviceIdentifier = 'current_device_identifier';

    protected static $array_mappings = [
        'DeviceName' => 'device_name:json_string',
        'IpAddress'  => 'ip_address:json_string',
        'TrustedAt'  => 'trusted_at:datetime_epoch',
        'ExpiresAt'  => 'expires_at:datetime_epoch',
        'LastSeenAt' => 'last_seen_at:datetime_epoch',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $device = $this->object;
        if (!$device instanceof UserTrustedDevice) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        $current = $params[self::ParamCurrentDeviceIdentifier] ?? null;
        $values['is_current'] = is_string($current) && hash_equals($device->getDeviceIdentifier(), $current);
        return $values;
    }
}

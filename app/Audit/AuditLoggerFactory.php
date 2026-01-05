<?php

namespace App\Audit;

/**
 * Copyright 2023 OpenStack Foundation
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

use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Throwable;

/**
 * Class ChildEntityFormatterFactory
 * @package App\Audit\ConcreteFormatters\ChildEntityFormatter
 */
class AuditLoggerFactory
{

    public static function build($entity): ?ILogger
    {
        try {
            $short = class_basename(is_string($entity) ? $entity : get_class($entity));
            $class_name = "App\\Audit\\Loggers\\{$short}AuditLogger";
            return class_exists($class_name) ? new $class_name() : null;
        } catch (Throwable $e) {
            Log::error($e);
            return null;
        }
    }
}
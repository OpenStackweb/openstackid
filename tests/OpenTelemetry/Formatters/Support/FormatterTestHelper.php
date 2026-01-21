<?php

namespace Tests\OpenTelemetry\Formatters\Support;

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
use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Audit\IAuditLogFormatter;
use Exception;
use ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * Helper class for testing audit log formatters.
 *
 * Provides assertion methods to validate formatter implementations,
 * including instantiation, method presence, and graceful error handling.
 */
class FormatterTestHelper
{
    /**
     * Asserts that a formatter class can be instantiated.
     *
     * Attempts to create an instance of the specified formatter class,
     * first trying with an event type parameter, then without parameters.
     *
     * @param string $formatterClass The fully qualified class name of the formatter to instantiate
     * @param string $eventType The event type to pass to the formatter constructor
     *
     * @return IAuditLogFormatter The instantiated formatter
     *
     * @throws Exception If the class does not exist, cannot be instantiated,
     *                   or does not implement IAuditLogFormatter
     */
    public static function assertFormatterCanBeInstantiated(string $formatterClass, string $eventType): IAuditLogFormatter
    {
        try {
            if (!class_exists($formatterClass)) {
                throw new Exception("Formatter class does not exist: {$formatterClass}");
            }

            $reflection = new ReflectionClass($formatterClass);

            try {
                $formatter = $reflection->newInstance($eventType);
            } catch (Throwable $e) {
                $formatter = $reflection->newInstance();
            }

            if (!$formatter instanceof IAuditLogFormatter) {
                throw new Exception("Formatter must implement IAuditLogFormatter");
            }

            return $formatter;
        } catch (ReflectionException $e) {
            throw new Exception("Failed to instantiate {$formatterClass}: " . $e->getMessage());
        }
    }

    /**
     * Asserts that a formatter has a setContext method.
     *
     * Validates that the formatter implementation includes the setContext method
     * which is required for setting contextual information during formatting.
     *
     * @param IAuditLogFormatter $formatter The formatter instance to validate
     *
     * @return void
     *
     * @throws Exception If the formatter does not have a setContext method
     */
    public static function assertFormatterHasSetContextMethod(IAuditLogFormatter $formatter): void
    {
        $reflection = new ReflectionClass($formatter);

        if (!$reflection->hasMethod('setContext')) {
            throw new Exception(
                get_class($formatter) . " must have a setContext method"
            );
        }
    }

    /**
     * Asserts that a formatter has a valid constructor.
     *
     * Validates that the formatter can be instantiated either with an event type
     * parameter, without parameters, or that all constructor parameters have
     * default values or are optionally injectable.
     *
     * @param string $formatterClass The fully qualified class name of the formatter to validate
     *
     * @return void
     *
     * @throws Exception If the formatter is abstract, has required constructor
     *                   parameters without defaults, or cannot be instantiated
     */
    public static function assertFormatterHasValidConstructor(string $formatterClass): void
    {
        try {
            $reflection = new ReflectionClass($formatterClass);

            if ($reflection->isAbstract()) {
                throw new Exception("Cannot test abstract formatter: {$formatterClass}");
            }

            $constructor = $reflection->getConstructor();
            if ($constructor === null) {
                return;
            }

            try {
                $reflection->newInstance(IAuditStrategy::EVENT_ENTITY_CREATION);
                return;
            } catch (Throwable $e) {
                try {
                    $reflection->newInstance();
                    return;
                } catch (Throwable $e) {
                    $requiredParams = [];
                    foreach ($constructor->getParameters() as $param) {
                        if (!$param->isOptional() && !$param->allowsNull()) {
                            $requiredParams[] = $param->getName();
                        }
                    }

                    if (!empty($requiredParams)) {
                        throw new Exception(
                            "{$formatterClass} has required constructor parameters: " .
                            implode(', ', $requiredParams) .
                            ". These parameters must either have default values or be optionally injectable."
                        );
                    }
                    throw $e;
                }
            }
        } catch (ReflectionException $e) {
            throw new Exception("Failed to validate constructor for {$formatterClass}: " . $e->getMessage());
        }
    }

    /**
     * Asserts that a formatter handles invalid subjects gracefully.
     *
     * Tests that the formatter does not throw unhandled exceptions when
     * provided with an invalid or unexpected subject type.
     *
     * @param IAuditLogFormatter $formatter The formatter instance to test
     * @param mixed $invalidSubject An invalid subject to pass to the formatter
     *
     * @return void
     *
     * @throws Exception If the formatter throws an exception when handling
     *                   the invalid subject
     */
    public static function assertFormatterHandlesInvalidSubjectGracefully(
        IAuditLogFormatter $formatter,
        mixed $invalidSubject
    ): void {
        try {
            $formatter->format($invalidSubject, []);
        } catch (Throwable $e) {
            throw new Exception(
                get_class($formatter) . " must handle invalid subjects gracefully: " . $e->getMessage()
            );
        }
    }

    /**
     * Asserts that a formatter handles empty changesets gracefully.
     *
     * Tests that the formatter does not throw unhandled exceptions when
     * provided with an empty changeset array.
     *
     * @param IAuditLogFormatter $formatter The formatter instance to test
     *
     * @return void
     *
     * @throws Exception If the formatter throws an exception when handling
     *                   an empty changeset
     */
    public static function assertFormatterHandlesEmptyChangesetGracefully(
        IAuditLogFormatter $formatter
    ): void {
        try {
            $formatter->format(new \stdClass(), []);
        } catch (Throwable $e) {
            throw new Exception(
                get_class($formatter) . " must handle empty changesets gracefully: " . $e->getMessage()
            );
        }
    }

    /**
     * Asserts that a formatter extends AbstractAuditLogFormatter.
     *
     * Validates that the formatter class properly extends the abstract base
     * class to ensure consistent behavior across all formatters.
     *
     * @param string $formatterClass The fully qualified class name of the formatter to validate
     *
     * @return void
     *
     * @throws Exception If the formatter does not extend AbstractAuditLogFormatter
     */
    public static function assertFormatterExtendsAbstractFormatter(string $formatterClass): void
    {
        try {
            $reflection = new ReflectionClass($formatterClass);

            if (!$reflection->isSubclassOf(AbstractAuditLogFormatter::class)) {
                throw new Exception(
                    "{$formatterClass} must extend AbstractAuditLogFormatter"
                );
            }
        } catch (ReflectionException $e) {
            throw new Exception("Failed to validate {$formatterClass}: " . $e->getMessage());
        }
    }

    /**
     * Asserts that a formatter has a valid format method.
     *
     * Validates that the formatter class has a concrete (non-abstract) format()
     * method that accepts at least one parameter (the subject).
     *
     * @param string $formatterClass The fully qualified class name of the formatter to validate
     *
     * @return void
     *
     * @throws Exception If the formatter does not have a format() method,
     *                   the method is abstract, or accepts fewer than 1 parameter
     */
    public static function assertFormatterHasValidFormatMethod(string $formatterClass): void
    {
        try {
            $reflection = new ReflectionClass($formatterClass);

            if (!$reflection->hasMethod('format')) {
                throw new Exception(
                    "{$formatterClass} must have a format() method"
                );
            }

            $method = $reflection->getMethod('format');

            if ($method->isAbstract()) {
                throw new Exception(
                    "{$formatterClass}::format() must not be abstract"
                );
            }

            $params = $method->getParameters();
            if (count($params) < 1) {
                throw new Exception(
                    "{$formatterClass}::format() must accept at least 1 parameter (subject)"
                );
            }
        } catch (ReflectionException $e) {
            throw new Exception("Failed to validate format method for {$formatterClass}: " . $e->getMessage());
        }
    }
}
<?php namespace App\Providers;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\libs\Utils\TextUtils;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;
use Sokil\IsoCodes\IsoCodesFactory;
use Validators\CustomValidator;
use App\Http\Utils\Log\LaravelMailerHandler;
use Utils\Services\ICacheService;
use Illuminate\Support\Facades\URL;
/**
 * Class AppServiceProvider
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!App::isLocal())
            URL::forceScheme('https');

        $logger = Log::getLogger();

        foreach($logger->getHandlers() as $handler) {
            $handler->setLevel(Config::get('log.level', 'error'));
        }

        //set email log
        $to   = Config::get('log.to_email');
        $from = Config::get('log.from_email');

        if (!empty($to) && !empty($from)) {
            $subject = Config::get('log.email_subject', 'openstackid-resource-server error');
            $cacheService = App::make(ICacheService::class);
            $handler = new LaravelMailerHandler($cacheService, $to, $subject, $from);
            $handler->setLevel(Config::get('log.email_level', 'error'));
            $logger->pushHandler($handler);
        }

        Validator::resolver(function($translator, $data, $rules, $messages)
        {
            return new CustomValidator($translator, $data, $rules, $messages);
        });

        Validator::extend('country_iso_alpha2_code', function($attribute, $value, $parameters, $validator)
        {

            $validator->addReplacer('country_iso_alpha2_code', function($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid country iso code", $attribute);
            });
            if(!is_string($value)) return false;
            $value = trim($value);

            $isoCodes  = new IsoCodesFactory();
            $countries = $isoCodes->getCountries();
            $country   = $countries->getByAlpha2($value);

            return !is_null($country);
        });

        Validator::extend('openid.identifier', function($attribute, $value, $parameters, $validator)
        {
            $validator->addReplacer('openid.identifier', function($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid openid identifier", $attribute);
            });

            return preg_match('/^(\w|\.)+$/', $value);
        });

        Validator::extend('int_array', function($attribute, $value, $parameters, $validator)
        {
            $validator->addReplacer('int_array', function($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be an array of integers", $attribute);
            });
            if(!is_array($value)) return false;
            foreach($value as $element)
            {
                if(!is_integer($element)) return false;
            }
            return true;
        });

        Validator::extend("password_policy", function($attribute, $value, $parameters, $validator){
            $min_length = Config::get("auth.password_min_length");
            $max_length = Config::get("auth.password_max_length");
            $warning = Config::get("auth.password_shape_warning");
            $pattern = Config::get("auth.password_shape_pattern");

            $validator->addReplacer('password_policy', function($message, $attribute, $rule, $parameters) use ($validator, $min_length, $max_length, $warning) {
                return sprintf("The %s must be %s–%s characters, and %s", $attribute, $min_length, $max_length, $warning);
            });

            $password = TextUtils::trim($value);

            if (strlen($password) < $min_length) {
                return false;
            }

            if (strlen($password) > $max_length) {
                return false;
            }

            if (!preg_match("/$pattern/", $password)) {
                return false;
            }

            return true;
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

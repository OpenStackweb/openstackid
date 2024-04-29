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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
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
        if(App::isProduction())
            URL::forceScheme('https');

        $logger = Log::getLogger();

        foreach($logger->getHandlers() as $handler) {
            $handler->setLevel(Config::get('log.level', 'error'));
        }

        Log::debug(sprintf("AppServiceProvider::boot - app is local %s", config('app.env')));
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
            $min = 8;
            $validator->addReplacer('password_policy', function($message, $attribute, $rule, $parameters) use ($validator, $min) {
                return sprintf("The %s must be %s–30 characters, and must include a special character", $attribute, $min);
            });

            return preg_match("/^((?=.*?[#?!@()$%^&*=_{}[\]:;\"'|<>,.\/~`±§+-])).{8,30}$/", $value);
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

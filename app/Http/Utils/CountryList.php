<?php namespace App\Http\Utils;
/**
 * Copyright 2020 OpenStack Foundation
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
use Sokil\IsoCodes\IsoCodesFactory;
/**
 * Class CountryList
 * @package App\Http\Utils
 */
final class CountryList
{
    private static function countrySort($a,$b) {
        $al = strtolower($a['name']);
        $bl = strtolower($b['name']);
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }

    private static function countryMap2Dic($country) {
        return [
            'name' => $country->getAlpha2() == 'TW'? 'Taiwan' : $country->getName(),
            'alpha2' => $country->getAlpha2(),
        ];
    }

    public static function getCountries(){
        // init database
        $isoCodes = new IsoCodesFactory();
        $countries  = $isoCodes->getCountries()->toArray();
        $countries = array_map( array('App\Http\Utils\CountryList','countryMap2Dic'), $countries);
        usort($countries, array('App\Http\Utils\CountryList','countrySort'));
        return $countries;
    }
}
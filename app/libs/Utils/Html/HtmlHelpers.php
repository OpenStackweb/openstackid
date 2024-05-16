<?php
/**
 * Copyright 2024 OpenStack Foundation
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

if (!function_exists('style_to')) {
    function style_to($path, $attributes = [], $secure = null)
    {
        return app('html_utils')->style(asset($path), $attributes, $secure);
    }
}

if (!function_exists('script_to')) {
    function script_to($path, $attributes = [], $secure = null)
    {
        return app('html_utils')->script(asset($path), $attributes, $secure);
    }
}

if (!function_exists('button_to')) {
    function button_to($text, $attributes = [], $type = null)
    {
        $attributesString = '';
        $typeString = '';

        foreach ($attributes as $key => $value) {
            $attributesString .= " {$key}=\"{$value}\"";
        }

        if ($type != null) {
            $typeString = "type=\"{$type}\"";
        }
        return app('html_utils')->toHtmlString("<button {$typeString}{$attributesString}>{$text}</button>");
    }
}

if (!function_exists('submit_button')) {
    function submit_button($text, $attributes = [])
    {
        return button_to($text, $attributes, "submit");
    }
}

if (!function_exists('link_to')) {
    function link_to($url, $title = null, $attributes = [], $secure = null)
    {
        return app('html_utils')->link($url, $title, $attributes, $secure);
    }
}

if (!function_exists('radio_to')) {
    function radio_to($name, $value, $checked = false, $attributes = [])
    {
        $attributesString = '';

        foreach ($attributes as $key => $valueAttribute) {
            $attributesString .= " {$key}=\"{$valueAttribute}\"";
        }

        $isChecked = $checked ? 'checked' : '';
        return app('html_utils')
            ->toHtmlString("<input type=\"radio\" name=\"{$name}\" value=\"{$value}\" {$isChecked}{$attributesString}>");
    }
}

if (!function_exists('select_to')) {
    function select_to($name, $options = [], $selected = null, $attributes = [])
    {
        $attributesString = '';

        foreach ($attributes as $key => $value) {
            $attributesString .= " {$key}=\"{$value}\"";
        }

        $html = "<select name=\"{$name}\"{$attributesString}>";

        foreach ($options as $value => $label) {
            $isSelected = $value == $selected ? 'selected' : '';
            $html .= "<option value=\"{$value}\" {$isSelected}>{$label}</option>";
        }

        $html .= "</select>";

        return app('html_utils')->toHtmlString($html);
    }
}

if (!function_exists('basic_form_open')) {
    function basic_form_open($url, $method = 'POST', $attributes = [])
    {
        static $postVerb = 'POST';
        $defaults = ['accept-charset' => 'UTF-8'];

        $attributes = array_merge($defaults, $attributes);

        $attributesString = '';

        foreach ($attributes as $key => $value) {
            $attributesString .= " {$key}=\"{$value}\"";
        }

        $csrfField = '';
        $methodField = '';
        $method = strtoupper($method);

        if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            // If the method is PUT, PATCH or DELETE we will need to add a spoofer hidden
            // field that will instruct the Symfony request to pretend the method is a
            // different method than it actually is, for convenience from the forms.
            $methodField = method_field($method);
            $method = $postVerb;
        }

        if ($method === $postVerb) {
            $csrfField = csrf_field();
        }

        $actionUrl = url($url);

        return app('html_utils')
            ->toHtmlString("<form action=\"{$actionUrl}\" method=\"{$method}\"{$attributesString}>{$csrfField}{$methodField}");
    }
}

if (!function_exists('form_close')) {
    function form_close()
    {
        return app('html_utils')->toHtmlString('</form>');
    }
}
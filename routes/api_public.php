<?php
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

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| Public API endpoints that do not require authentication.
|
*/

Route::get('/version', function () {
    $versionFile = base_path('version.json');

    // Check if file exists
    if (!file_exists($versionFile)) {
        return response()->json(['error' => 'Version information not found'], 404);
    }

    // Read file contents
    $contents = file_get_contents($versionFile);

    // Decode JSON
    $versionData = json_decode($contents, true);

    // Check if JSON is valid or empty
    if ($versionData === null && json_last_error() !== JSON_ERROR_NONE) {
        return response()->json(['error' => 'Version information malformed'], 412);
    }

    if (empty($versionData)) {
        return response()->json(['error' => 'Version information malformed'], 412);
    }

    return response()->json($versionData, 200);
});

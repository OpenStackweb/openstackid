<?php
/*
 * Copyright 2025 OpenStack Foundation
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
return [
    'monitored_security_groups_set' => explode(',', env('MONITORED_SECURITY_GROUPS', 'administrators,summit-front-end-administrators,super-admins')),
    'monitored_security_groups_set_activity_watchers' => explode(',', env('‘‘MONITORED_SECURITY_GROUPS_SET_ACTIVITY_WATCHERS', 'super-admins,administrators')),
];
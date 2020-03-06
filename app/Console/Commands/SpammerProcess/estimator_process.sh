#!/bin/bash
# Copyright (c) 2017 OpenStack Foundation
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
# implied.
# See the License for the specific language governing permissions and
# limitations under the License.

WORK_DIR=$1
DB_HOST=$2
DB_USER=$3
DB_PASSWORD=$4
DB_NAME=$5

export PYTHONPATH="$PYTHONPATH:$WORK_DIR";

cd $WORK_DIR;

source env/bin/activate;

python estimator_process.py $DB_HOST $DB_USER $DB_PASSWORD $DB_NAME;

deactivate;
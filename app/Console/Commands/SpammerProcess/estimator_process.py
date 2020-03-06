# -*- coding: utf-8 -*-
#!/usr/bin/env python
#
# Copyright (c) 2020 OpenStack Foundation
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

import sys
from openstack_member_spammer_estimator import EstimatorClassifier
import os

# params
db_host = sys.argv[1]
db_user = sys.argv[2]
db_user_password = sys.argv[3]
db_name = sys.argv[4]
filename = 'user_classifier.pickle'

classifier = EstimatorClassifier(db_host=db_host, db_user=db_user, db_user_password=db_user_password, db_name=db_name)
script_dir = os.path.dirname(__file__)
pickle_file = os.path.join(script_dir, filename)
if not os.path.exists(pickle_file):
    raise Exception('File %s does not exists!' % pickle_file)

res = classifier.classify(pickle_file)

# output CSV file
print("ID,Type")
for row in res:
    print("%s,%s" % row)

#!/usr/bin/env bash

export XDEBUG_CONFIG="remote_enable=1 remote_mode=req remote_port=9000 remote_host=127.0.0.1 remote_connect_back=1"

rm ref-out/*.log
rm ./*.!!!
rm ./*.err

bash _stud_tests.sh && php test.php


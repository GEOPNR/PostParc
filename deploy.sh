#!/bin/sh
set -e

php composer.phar install
yarn encore prod
cd app/config/clients && git pull 
cd ../../../scripts && ./cache_clear.sh && ./update_db.sh 


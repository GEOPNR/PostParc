#! /bin/bash

dir="../app/config/clients"
notEnvUpdate=(dev prod test)

for subdomain in "$dir"/config_*.yml; do
    sname=$(echo $subdomain | sed 's/.*config_\(.*\).yml/\1/')
    if [[ ! ${notEnvUpdate[*]} =~ "$sname" ]]; then
        echo "cache clear for env $sname"
        php7.3 ../bin/console cache:clear -e $sname --no-debug;
        php7.3 ../bin/console doctrine:cache:clear-metadata -e $sname;
    fi
done

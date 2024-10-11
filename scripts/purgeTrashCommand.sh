#! /bin/bash

dir="../app/config/clients"
notEnvUpdate=(dev prod test)

for subdomain in "$dir"/config_*.yml; do
    sname=$(echo $subdomain | sed 's/.*config_\(.*\).yml/\1/')
    if [[ ! ${notEnvUpdate[*]} =~ "$sname" ]]; then
        php7.3 ../bin/console postparc:purgeTrashCommand -e $sname;
    fi
done

#! /bin/bash

dir="../app/config/clients"
notEnvUpdate=(dev prod test)

for subdomain in "$dir"/config_*.yml; do
    sname=$(echo $subdomain | sed 's/.*config_\(.*\).yml/\1/')
    if [[ ! ${notEnvUpdate[*]} =~ "$sname" ]]; then
         echo "update bd for env $sname"
	php7.3 ../bin/console doctrine:schema:update --force -e $sname ;
    fi
done

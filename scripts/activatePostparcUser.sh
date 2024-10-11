#! /bin/bash

dir="../app/config/clients"
notEnvUpdate=(dev prod test demo sandbox pnr)

for subdomain in "$dir"/config_*.yml; do
    sname=$(echo $subdomain | sed 's/.*config_\(.*\).yml/\1/')
    if [[ ! ${notEnvUpdate[*]} =~ "$sname" ]]; then
	php7.3 ../bin/console fos:user:activate postparc -e $sname ;
    fi
done

#! /bin/bash

dir="../app/config/clients"
notEnvUpdate=(dev prod test)

for subdomain in "$dir"/config_*.yml; do
    sname=$(echo $subdomain | sed 's/.*config_\(.*\).yml/\1/')
    if [[ ! ${notEnvUpdate[*]} =~ "$sname" ]]; then
        php7.3 ../bin/console postparc:updateAddressCoordinates -e $sname;
	# wait 5 min to execute command for an other instance
	sleep 5m;
    fi
done

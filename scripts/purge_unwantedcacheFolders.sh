#! /bin/bash

dir="../app/config/clients"
notEnvUpdate=(dev prod test)

goodCacheFolders=(dev prod test)
for subdomain in "$dir"/config_*.yml; do
    sname=$(echo $subdomain | sed 's/.*config_\(.*\).yml/\1/')
    if [[ ! ${notEnvUpdate[*]} =~ "$sname" ]]; then
        goodCacheFolders+=("$sname")
    fi
done

#echo "${goodCacheFolders[@]}"

echo suppression des dossiers cache non souhaités
find ../var/cache/* -maxdepth 0 -type d $(printf "! -name %s " ${goodCacheFolders[*]}) -delete



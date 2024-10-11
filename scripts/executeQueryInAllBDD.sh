#! /bin/bash

dir="../app/config/clients"
notEnvUpdate=(dev prod test)
#query="UPDATE civility set isFeminine=1 WHERE slug IN ('madame','mesdames')"
#query="UPDATE fos_user set wishes_to_be_informed_of_changes = '0' WHERE email='yann.picot@probesys.com'"; 
#query="INSERT INTO help(id, slug, name, description, created_by_id, updated_by_id, created, updated) VALUES
#(34, 'typde-de-mandat','Aide type de mandat', '<p>Aide à compléter</p>', 1, 1, '2019-08-27 16:11:41','2019-08-27 16:11:41'),
#(35, 'aide-profession','Aide profession', '<p>Aide à compléter</p>', 1, 1, '2019-08-27 16:11:41','2019-08-27 16:11:41')";
#query="ALTER TABLE fos_user CHANGE wishes_to_be_informed_of_changes wishes_to_be_informed_of_changes TINYINT(1) DEFAULT '0' NOT NULL;",
#query="INSERT INTO ext_translations_help (locale, object_class, field, foreign_key, content)
#SELECT 'fr', 'PostparcBundle\\\Entity\\\Help','name', id, name FROM help;"
#query="INSERT INTO ext_translations_help (locale, object_class, field, foreign_key, content)
#SELECT 'fr', 'PostparcBundle\\\Entity\\\Help','description', id, description FROM help;"
#query="TRUNCATE TABLE ext_translations_help;";

for subdomain in "$dir"/config_*.yml; do
    sname=$(echo $subdomain | sed 's/.*config_\(.*\).yml/\1/')
    if [[ ! ${notEnvUpdate[*]} =~ "$sname" ]]; then
        echo 'execute query for env '$sname;
        php7.3 ../bin/console doctrine:query:sql "$query" -e $sname;
    fi
done

## generation clé privé DKIM
utiliser opendkim pour créer votre clé privé liée à votre domaine
nommer cette clé mail.private et placée la dans ce dossier
le selecteur, le domaine et le chemin vers le fichier sont modifiable dans le fichier config.yml 
si le paramètre dkim.use_dkim est vrai alors une entête DKIM sera ajoutée.
Vous devez configurer correctement votre DNS pour que le DKIM soit pris en compte

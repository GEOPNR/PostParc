<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use PostparcBundle\Entity\SearchList;
use PostparcBundle\Entity\Service;
use PostparcBundle\Entity\PersonFunction;
use PostparcBundle\Entity\Civility;
use PostparcBundle\Entity\City;
use PostparcBundle\Entity\Territory;
use PostparcBundle\Entity\Group;
use PostparcBundle\Entity\Help;
use PostparcBundle\Entity\Person;
use PostparcBundle\Entity\AdditionalFunction;
use PostparcBundle\Entity\Organization;

class migrateOldPostparcCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:migrateOldPostparc')
            ->setDescription('Migrate v1 postparc to v2 postparc')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // recupération environnement courant
        $subDomain = $this->getContainer()->get('kernel')->getEnvironment();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $admin = $em->getRepository('PostparcBundle:User')->find(1);
        $prefixOldDataBase = $this->getContainer()->getParameter('prefixOldDataBase');
        $prefixNewDatabase = $this->getContainer()->getParameter('prefixNewDatabase');

        // insertion des users
        $this->insertUsers($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // gestion des password
        $this->upgradeUserPassword($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // mise a jour des permissions
        $this->upgradeUserRole($output, $subDomain, $em, $prefixOldDataBase);
        // insertion service
        $this->insertServices($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion person_function
        $this->insertPersonFunctions($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion Civility
        $this->insertCivilities($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion Cities
        $this->insertCities($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion Territory
        $this->insertTerritories($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion Groups
        $this->insertGroups($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion helps
        $this->insertHelps($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion emails
        $this->insertEmails($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // insertion Coordinates
        $this->insertCoordinates($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // insertion Persons
        $this->insertPersons($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion territories_cities
        $this->insertTerritoriesCities($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // insertion Printformats
        $this->insertPrintformats($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // insertion AdditionnalFunctions
        $this->insertAdditionnalFunctions($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion Organizations
        $this->insertOrganizations($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // insertion Pfos
        $this->insertPfos($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // insertion pfo_prefered_emails
        $this->insertPfoPreferedEmails($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // insertion person_prefered_emails
        $this->insertPersonPreferedEmails($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // insertion pfo_person_group
        $this->insertPfoPersonGroup($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
        // insertion searchLists
        $this->insertSearchLists($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase);
        // mis a jour champs search_params table search_list avec les anciennes données
        $this->insertSearchParams($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase);
    }

    private function insertUsers($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.fos_user</info>');
        $sql = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.fos_user (id, username, username_canonical, email, email_canonical, enabled, salt, password, last_login, locked, expired, expires_at, confirmation_token, password_requested_at, credentials_expire_at, wishes_to_be_informed_of_changes, results_per_page, roles, first_name, last_name)
        SELECT sf.id, username, username, email_address, email_address, is_active, salt, password, last_login,0,0,NULL, NULL, NULL, NULL, wishes_to_be_informed_of_changes, 25, \'a:0:{}\', first_name, last_name
        FROM ' . $prefixOldDataBase . $subDomain . '.sf_guard_user sf
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.user_profile up ON up.sf_guard_user_id = sf.id';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $output->writeln('');
    }

    private function upgradeUserPassword($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Mise a jour password des users suivant schema password = @login!</info>');
        $um = $this->getContainer()->get('fos_user.user_manager');

        $command = $this->getApplication()->find('fos:user:change-password');
        $usersFOS = $um->findUsers();

        foreach ($usersFOS as $user) {
            $login = $user->getUserName();
            $password = '@' . $user->getUserName() . '!';
            $arguments = [
                                'command' => $command->getName(),
                                'username' => $login,
                                'password' => $password,
                            ];
            $input = new ArrayInput($arguments);
            $returnCode = $command->run($input, $output);
            $output->writeln('<info>Mise a jour user ' . $user->getUserName() . ' :</info>');
            $output->writeln('<comment>Login : ' . $login . '</comment>');
            $output->writeln('<comment>Password : ' . $password . '</comment>');
        }
        $em->flush();
    }

    private function upgradeUserRole($output, $subDomain, $em, $prefixOldDataBase)
    {
        $output->writeln('<info>Mise a jour des permissions des utilisateurs</info>');
        $command = $this->getApplication()->find('fos:user:promote');
        $sql = '
        SELECT username, group_id, is_super_admin
        FROM  ' . $prefixOldDataBase . $subDomain . '.sf_guard_user sf
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.sf_guard_user_group sgug ON sf.id = sgug.user_id
        ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll();
        foreach ($users as $user) {
            switch ($user['group_id']) {
                case 1: // lecteur
                    $role = 'ROLE_USER';
                    break;
                case 2: // Contributeur
                    $role = 'ROLE_CONTRIBUTOR';
                    break;
                case 3:
                    $role = 'ROLE_CONTRIBUTOR_PLUS';
                    break;
                case 4:
                    $role = 'ROLE_ADMIN';
                    break;
                default:
                    $role = 'ROLE_USER';
                    break;
            }
            if ($user['is_super_admin']) {
                $role = 'ROLE_SUPER_ADMIN';
            }
            $arguments = [
                                'command' => $command->getName(),
                                'username' => $user['username'],
                                'role' => $role,
                            ];
            $input = new ArrayInput($arguments);
            $returnCode = $command->run($input, $output);
        }
    }

    private function insertServices($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.Service</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\Service');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // recupération anciennes données
        $sql = '
        SELECT id, intitule, updated_by, updated_by FROM ' . $prefixOldDataBase . $subDomain . '.service';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldServices = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldServices));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $progress->setMessage('Task in progress...');
        $i = 0;
        foreach ($oldServices as $oldService) {
            $service = new Service();
            $service->setId($oldService['id']);
            $service->setName($oldService['intitule']);
            $service->setCreatedBy($admin);
            if ($oldService['updated_by']) {
                $user = $em->getRepository('PostparcBundle:User')->find($oldService['updated_by']);
                $service->setUpdatedBy($user);
            }
            $em->persist($service);
            $progress->advance();
            ++$i;
            if (0 == $i % 100) {
                $em->flush();
            }
        }

        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');
    }

    private function insertPersonFunctions($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.PersonFunction</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\PersonFunction');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // recupération anciennes données
        $sql = '
        SELECT id, intitule, intitule_feminin, particule_masculin, particule_feminin, updated_by FROM ' . $prefixOldDataBase . $subDomain . '.fonction';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldFonctions = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldFonctions));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldFonctions as $oldFonction) {
            $personFunction = new PersonFunction();
            $personFunction->setId($oldFonction['id']);
            $personFunction->setName($oldFonction['intitule']);
            $personFunction->setWomenName($oldFonction['intitule_feminin']);
            $personFunction->setMenParticle($oldFonction['particule_masculin']);
            $personFunction->setWomenParticle($oldFonction['particule_feminin']);
            $personFunction->setCreatedBy($admin);
            if ($oldFonction['updated_by']) {
                $user = $em->getRepository('PostparcBundle:User')->find($oldFonction['updated_by']);
                $personFunction->setUpdatedBy($user);
            }
            $em->persist($personFunction);
            $progress->advance();
            ++$i;
            if (0 == $i % 100) {
                $em->flush();
            }
        }

        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');
    }

    public function insertCivilities($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.Civility</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\Civility');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // recupération anciennes données
        $sql = '
        SELECT id, intitule FROM ' . $prefixOldDataBase . $subDomain . '.civilite';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldCivilities = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldCivilities));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldCivilities as $oldCivility) {
            $civility = new Civility();
            $civility->setId($oldCivility['id']);
            $civility->setName($oldCivility['intitule']);
            $civility->setCreatedBy($admin);
            $em->persist($civility);
            $progress->advance();
            ++$i;
            if (0 == $i % 100) {
                $em->flush();
            }
        }

        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');
    }

    private function insertCities($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.City</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\City');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // recupération anciennes données
        $sql = '
        SELECT id, intitule, code_postal, departement, pays, est_active FROM ' . $prefixOldDataBase . $subDomain . '.commune';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldCities = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldCities));
        $progress->setFormat('verbose');
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldCities as $oldCity) {
            $city = new City();
            $city->setId($oldCity['id']);
            $city->setName($oldCity['intitule']);
            $city->setZipCode($oldCity['code_postal']);
            $city->setDepartment($oldCity['departement']);
            $city->setCountry($oldCity['pays']);
            $city->setIsActive($oldCity['est_active']);
            $city->setCreatedBy($admin);
            $city->setInsee($oldCity['id']);
            $em->persist($city);
            $progress->advance();
            ++$i;
            if (0 == $i % 1000) {
                $em->flush();
            }
        }

        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');
    }

    private function insertTerritories($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.Territory</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\Territory');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // recupération anciennes données
        $sql = '
        SELECT id, updated_by, intitule, root_id, level, lft, rgt FROM ' . $prefixOldDataBase . $subDomain . '.territoire';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldTerritories = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldTerritories));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldTerritories as $oldTerritory) {
            $territory = new Territory();
            $territory->setId($oldTerritory['id']);
            $territory->setName($oldTerritory['intitule']);
            $territory->setRoot($oldTerritory['root_id']);
            $territory->setLevel($oldTerritory['level']);
            $territory->setLft($oldTerritory['lft']);
            $territory->setRgt($oldTerritory['rgt']);
            $territory->setCreatedBy($admin);

            if ($oldTerritory['updated_by']) {
                $user = $em->getRepository('PostparcBundle:User')->find($oldTerritory['updated_by']);
                $territory->setUpdatedBy($user);
            }
            $em->persist($territory);
            $progress->advance();
            ++$i;
            if (0 == $i % 100) {
                $em->flush();
            }
        }
        $em->flush();
        $progress->finish();
        $progress->setMessage('Insertion territory terminée');

        // requete mise a jour champ level
        $output->writeln('');
        $output->writeln('Mise a jour colonne level');
        $sql = '
        UPDATE ' . $prefixNewDatabase . $subDomain . '.territory t
        INNER JOIN
        ( SELECT id,level FROM ' . $prefixOldDataBase . $subDomain . '.territoire) sub ON  sub.id=t.id
        SET  t.level = sub.level';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();

        // mise a jour colonne parent_id
        $output->writeln('');
        $output->writeln('<info>Mise a jour colonne parent_id</info>');
        $sql = '
        UPDATE ' . $prefixNewDatabase . $subDomain . '.territory SET parent_id=root';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
    }

    private function insertGroups($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.Group</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\Group');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // recupération anciennes données
        $sql = '
        SELECT id, updated_by, intitule, root_id, level, lft, rgt FROM ' . $prefixOldDataBase . $subDomain . '.groupe';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldGroups = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldGroups));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldGroups as $oldGroup) {
            $group = new Group();
            $group->setId($oldGroup['id']);
            $group->setName($oldGroup['intitule']);
            $group->setRoot($oldGroup['root_id']);
            $group->setLevel($oldGroup['level']);
            $group->setLft($oldGroup['lft']);
            $group->setRgt($oldGroup['rgt']);
            $group->setCreatedBy($admin);

            if ($oldGroup['updated_by']) {
                $user = $em->getRepository('PostparcBundle:User')->find($oldGroup['updated_by']);
                $group->setUpdatedBy($user);
            }
            $em->persist($group);
            $progress->advance();
            ++$i;
            if (0 == $i % 100) {
                $em->flush();
            }
        }
        $em->flush();

        $progress->finish();
        $progress->setMessage('Fin insertion Groupe');

        // synchronisation post insertion entre ancienne table et nouvelle table
        $output->writeln('');
        $output->writeln('Synchronisation post insertion entre ancienne table et nouvelle table');
        $sql = '
        UPDATE ' . $prefixNewDatabase . $subDomain . '.groups g
        INNER JOIN (
            SELECT id, root_id, lft, rgt, level FROM ' . $prefixOldDataBase . $subDomain . '.groupe
            ) as sub ON g.id= sub.id
            SET g.root= sub.root_id, g.lft= sub.lft, g.rgt= sub.rgt, g.parent_id = sub.root_id, g.level=sub.level
        ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
    }

    private function insertHelps($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion dans table ' . $prefixNewDatabase . $subDomain . '.Help</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\Help');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        // recupération anciennes données
        $sql = '
        SELECT id, title, description FROM ' . $prefixOldDataBase . $subDomain . '.aide';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldHelps = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldHelps));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldHelps as $oldHelp) {
            $help = new Help();
            $help->setId($oldHelp['id']);
            $help->setName($oldHelp['title']);
            $help->setDescription($oldHelp['description']);
            $help->setCreatedBy($admin);
            $em->persist($help);
            $progress->advance();
            ++$i;
            if (0 == $i % 100) {
                $em->flush();
            }
        }
        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');
    }

    private function insertEmails($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.Email</info>');
        $sql = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.email (id, email)
        SELECT id, mail FROM ' . $prefixOldDataBase . $subDomain . '.email';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $output->writeln('');
    }

    private function insertCoordinates($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.Coordinate</info>');

        $sql = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.coordinates(id, created_by_id, updated_by_id, address_line_1, address_line_2, cedex, phone, mobile_phone, fax, web_site, created, updated, city_id, email_id)
SELECT id, updated_by, updated_by, adresse1, adresse2, cedex, telephone_fixe, telephone_portable, fax, site_internet, created_at, updated_at, commune_id, email_id  FROM ' . $prefixOldDataBase . $subDomain . '.coordonnees';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();

        $output->writeln('');
    }

    private function insertPersons($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données table ' . $prefixNewDatabase . $subDomain . '.Person requete1</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\Person');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        // recupération anciennes données
        $sql1 = '
        SELECT DISTINCT p.id, civilite_id, p.updated_by, p.nom, prenom, c.id as coordinate_id FROM ' . $prefixOldDataBase . $subDomain . '.personne p
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.personne_fonction_organisme pfo ON pfo.personne_id = p.id
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.organisme o ON pfo.organisme_id = o.id
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.coordonnees c ON c.organisme_id = o.id
        WHERE o.est_perso=1 AND o.intitule=\'Personnelle\'
        GROUP BY p.id
        ';
        $stmt = $em->getConnection()->prepare($sql1);
        $stmt->execute();
        $oldPersons = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldPersons));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldPersons as $oldPerson) {
            $person = new Person();
            $person->setId($oldPerson['id']);
            $person->setName($oldPerson['nom']);
            $person->setFirstName($oldPerson['prenom']);
            $person->setCreatedBy($admin);
            if ($oldPerson['updated_by']) {
                $user = $em->getRepository('PostparcBundle:User')->find($oldPerson['updated_by']);
                $person->setUpdatedBy($user);
            }
            if ($oldPerson['civilite_id']) {
                $civility = $em->getRepository('PostparcBundle:Civility')->find($oldPerson['civilite_id']);
                $person->setCivility($civility);
            }
            if ($oldPerson['coordinate_id']) {
                $coordinate = $em->getRepository('PostparcBundle:Coordinate')->find($oldPerson['coordinate_id']);
                $person->setCoordinate($coordinate);
            }
            $em->persist($person);
            $progress->advance();
            ++$i;
            if (0 == $i % 200) {
                $em->flush();
            }
        }
        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');

        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.Person requete2</info>');
        $sql2 = '
        SELECT DISTINCT p.id, civilite_id, p.updated_by, p.nom, prenom FROM ' . $prefixOldDataBase . $subDomain . '.personne p
        WHERE p.id NOT IN (SELECT id FROM ' . $prefixNewDatabase . $subDomain . '.person)
        ';
        $stmt = $em->getConnection()->prepare($sql2);
        $stmt->execute();
        $oldPersons = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldPersons));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldPersons as $oldPerson) {
            $person = new Person();
            $person->setId($oldPerson['id']);
            $person->setName($oldPerson['nom']);
            $person->setFirstName($oldPerson['prenom']);
            $person->setCreatedBy($admin);
            if ($oldPerson['updated_by']) {
                $user = $em->getRepository('PostparcBundle:User')->find($oldPerson['updated_by']);
                $person->setUpdatedBy($user);
            }
            if ($oldPerson['civilite_id']) {
                $civility = $em->getRepository('PostparcBundle:Civility')->find($oldPerson['civilite_id']);
                $person->setCivility($civility);
            }
            $em->persist($person);
            $progress->advance();
            ++$i;
            if (0 == $i % 200) {
                $em->flush();
            }
        }
        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');
    }

    private function insertTerritoriesCities($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.territories_cities</info>');

        $sql = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.territories_cities(territory_id, city_id)
        SELECT DISTINCT territoire_id, commune_id FROM ' . $prefixOldDataBase . $subDomain . '.territoire_commune'
        ;
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();

        $output->writeln('');
    }

    private function insertPrintformats($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.print_format</info>');

        $sql = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.print_format(id, slug, name, description, format, orientation, margin_top, margin_bottom, margin_left, margin_right, number_per_row, sticker_height, sticker_width,padding_horizontal_inter_sticker, padding_vertical_inter_sticker, margin_horizontal_inter_sticker, margin_vertical_inter_sticker, created, updated)
        SELECT id, slug, intitule, description, format, orientation, marge_haute, marge_basse, marge_gauche, marge_droite, nb_etiquette_par_ligne, hauteur_etiquette, largeur_etiquette, retrait_horizontal_inter_etiquette,  retrait_vertical_inter_etiquette, marge_horizontale_inter_etiquette, marge_vertical_inter_etiquette, created_at, updated_at FROM ' . $prefixOldDataBase . $subDomain . '.format_impression
        '
        ;
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();

        $output->writeln('');
    }

    private function insertAdditionnalFunctions($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.AdditionnalFunction</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\AdditionalFunction');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        // recupération anciennes données
        $sql = '
        SELECT id, intitule, updated_by FROM ' . $prefixOldDataBase . $subDomain . '.complement_fonction';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldAdditionnalFunctions = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldAdditionnalFunctions));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldAdditionnalFunctions as $oldAdditionnalFunction) {
            $additionalFunction = new AdditionalFunction();
            $additionalFunction->setId($oldAdditionnalFunction['id']);
            $additionalFunction->setName($oldAdditionnalFunction['intitule']);
            $additionalFunction->setCreatedBy($admin);
            if ($oldAdditionnalFunction['updated_by']) {
                $user = $em->getRepository('PostparcBundle:User')->find($oldAdditionnalFunction['updated_by']);
                $additionalFunction->setUpdatedBy($user);
            }
            $em->persist($additionalFunction);
            $progress->advance();
            ++$i;
            if (0 == $i % 100) {
                $em->flush();
            }
        }
        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');
    }

    private function insertOrganizations($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.Organization</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\Organization');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        // recupération anciennes données
        $sql = '
        SELECT o.id, o.updated_by,  c.id as coordinate_id, o.intitule, o.abreviation
        FROM ' . $prefixOldDataBase . $subDomain . '.organisme o
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.coordonnees c ON c.organisme_id = o.id
        WHERE o.est_perso!=1
        GROUP BY o.id';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldOrganizations = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldOrganizations));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldOrganizations as $oldOrganization) {
            $organization = new Organization();
            $organization->setId($oldOrganization['id']);
            $organization->setName($oldOrganization['intitule']);
            $organization->setAbbreviation($oldOrganization['abreviation']);
            $organization->setCreatedBy($admin);
            if ($oldOrganization['updated_by']) {
                $user = $em->getRepository('PostparcBundle:User')->find($oldOrganization['updated_by']);
                $organization->setUpdatedBy($user);
            }
            if ($oldOrganization['coordinate_id']) {
                $coordinate = $em->getRepository('PostparcBundle:Coordinate')->find($oldOrganization['coordinate_id']);
                $organization->setCoordinate($coordinate);
            }
            $em->persist($organization);
            $progress->advance();
            ++$i;
            if (0 == $i % 300) {
                $em->flush();
            }
        }
        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');
    }

    private function insertPfos($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.pfo</info>');
        $sql = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.pfo(id, created_by_id, updated_by_id, person_id, person_function_id, additional_function_id, service_id, organization_id, connecting_city_id, prefered_coordinate_address_id, email_id, created, updated, phone, mobile_phone, fax, observation)
        SELECT pfo.id, pfo.updated_by, pfo.updated_by, pfo.personne_id, pfo.fonction_id, pfo.complement_fonction_id, pfo.service_id, pfo.organisme_id, pfo.commune_rattachement_id, coord.id as coordId, pfo.email_id, pfo.created_at, pfo.updated_at, pfo.telephone_fixe_perso, pfo.telephone_portable_perso, pfo.fax_perso, pfo.observation
        FROM ' . $prefixOldDataBase . $subDomain . '.personne_fonction_organisme pfo
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.organisme o ON pfo.organisme_id = o.id
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.organisme o2 ON pfo.adresse_envoie_organisme_id = o2.id
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.coordonnees coord ON coord.organisme_id = o2.id

        WHERE o.est_perso!=1 AND o.intitule!=\'Personnelle\' GROUP BY pfo.id
        ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $output->writeln('');
    }

    private function insertPfoPreferedEmails($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.pfo_prefered_emails</info>');
        $sql = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.pfo_prefered_emails (pfo_id, email_id)
        SELECT DISTINCT pe.personne_fonction_organisme_id, pe.email_id
        FROM ' . $prefixOldDataBase . $subDomain . '.prefered_email pe
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.personne_fonction_organisme pfo ON  pe.personne_fonction_organisme_id = pfo.id
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.organisme o ON pfo.organisme_id = o.id
        WHERE o.est_perso!=1 AND o.intitule!=\'Personnelle\'
        ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();

        $output->writeln('');
    }

    private function insertPersonPreferedEmails($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données dans table ' . $prefixNewDatabase . $subDomain . '.person_prefered_emails</info>');
        $sql = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.person_prefered_emails (person_id, email_id)
        SELECT DISTINCT pfo.personne_id, pe.email_id FROM ' . $prefixOldDataBase . $subDomain . '.prefered_email pe
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.personne_fonction_organisme pfo ON  pe.personne_fonction_organisme_id = pfo.id
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.organisme o ON pfo.organisme_id = o.id
        WHERE o.est_perso=1 AND o.intitule=\'Personnelle\'
        ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();

        $output->writeln('');
    }

    private function insertPfoPersonGroup($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données table ' . $prefixNewDatabase . $subDomain . '.pfo_person_group</info>');
        $sql1 = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.pfo_person_group (pfo_id,person_id, group_id)
        SELECT pfog.personne_fonction_organisme_id, NULL, pfog.groupe_id
        FROM ' . $prefixOldDataBase . $subDomain . '.personne_fonction_organisme_groupe pfog
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.personne_fonction_organisme pfo ON pfog.personne_fonction_organisme_id = pfo.id
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.organisme o ON pfo.organisme_id = o.id
        WHERE o.est_perso!=1 AND o.intitule!=\'Personnelle\'
        ';
        $stmt = $em->getConnection()->prepare($sql1);
        $stmt->execute();
        $sql2 = '
        INSERT INTO ' . $prefixNewDatabase . $subDomain . '.pfo_person_group (pfo_id, person_id, group_id)
        SELECT NULL, pfo.personne_id, pfog.groupe_id
        FROM ' . $prefixOldDataBase . $subDomain . '.personne_fonction_organisme_groupe pfog
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.personne_fonction_organisme pfo ON pfog.personne_fonction_organisme_id = pfo.id
        LEFT JOIN ' . $prefixOldDataBase . $subDomain . '.organisme o ON pfo.organisme_id = o.id
        WHERE o.est_perso=1 AND o.intitule=\'Personnelle\';
        ';
        $stmt = $em->getConnection()->prepare($sql2);
        $stmt->execute();
        $output->writeln('');
    }

    private function insertSearchLists($output, $subDomain, $em, $admin, $prefixOldDataBase, $prefixNewDatabase)
    {
        $output->writeln('<info>Insertion données table ' . $prefixNewDatabase . $subDomain . '.SearchList</info>');
        $metadata = $em->getClassMetaData('PostparcBundle\Entity\SearchList');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        // recupération anciennes données
        $sql = '
        SELECT id, updated_by, intitule, champ_libre FROM ' . $prefixOldDataBase . $subDomain . '.liste';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $oldSearchLists = $stmt->fetchAll();
        $progress = new ProgressBar($output, count($oldSearchLists));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $i = 0;
        foreach ($oldSearchLists as $oldSearchList) {
            $searchList = new SearchList();
            $searchList->setId($oldSearchList['id']);
            $searchList->setName($oldSearchList['intitule']);
            $searchList->setDescription($oldSearchList['champ_libre']);
            $searchList->setCreatedBy($admin);
            if ($oldSearchList['updated_by']) {
                $user = $em->getRepository('PostparcBundle:User')->find($oldSearchList['updated_by']);
                $searchList->setUpdatedBy($user);
            }
            $em->persist($searchList);
            $progress->advance();
            ++$i;
            if (0 == $i % 100) {
                $em->flush();
            }
        }
        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('');
    }

    private function insertSearchParams($output, $subDomain, $em, $prefixOldDataBase, $prefixNewDatabase)
    {
        // on part du principe que la table a été peuplée et qu'il reste a peupler la colonne search_params
        $searchLists = $em->getRepository('PostparcBundle:SearchList')->findAll();
        $haveToBeFlush = false;
        $output->writeln('<info>Démarrage command mise a jour colonne search_params table Search_list à partir des anciennes données</info>');
        foreach ($searchLists as $searchList) {
            // recherche de l'ancienne version de l'objet

            $sql = '
             SELECT liste_fonction, liste_territoire, liste_commune, liste_groupe, liste_organisme, observation, exclusion_fonction, exclusion_territoire, exclusion_commune, exclusion_groupe, exclusion_organisme
             FROM ' . $prefixOldDataBase . $subDomain . '.liste
             WHERE id=' . $searchList->getId();
            $row = $em->getConnection()->fetchArray($sql);

            $output->writeln('Mise a jour searList id ' . $searchList->getId());
            // construction du tableau
            $liste_fonction = unserialize($row[0]) ? unserialize($row[0]) : null;
            $liste_territoire = unserialize($row[1]) ? unserialize($row[1]) : null;
            $liste_commune = unserialize($row[2]) ? unserialize($row[2]) : null;
            $liste_groupe = unserialize($row[3]) ? unserialize($row[3]) : null;
            $liste_organisme = unserialize($row[4]) ? unserialize($row[4]) : null;
            $observation = $row[5];
            $exclusion_fonction = $row[6] ? 'on' : null;
            $exclusion_territoire = $row[7] ? 'on' : null;
            $exclusion_commune = $row[8] ? 'on' : null;
            $exclusion_groupe = $row[9] ? 'on' : null;
            $exclusion_organisme = $row[10] ? 'on' : null;

            $searchParmsArray = [];
            $searchParmsArray['filterAdvancedSearch'] = '1';
            $searchParmsArray['functionIds'] = $liste_fonction;
            $searchParmsArray['function_exclusion'] = $exclusion_fonction;
            $searchParmsArray['serviceIds'] = null;
            $searchParmsArray['service_exclusion'] = null;
            $searchParmsArray['organizationTypeIds'] = null;
            $searchParmsArray['organizationType_exclusion'] = null;
            $searchParmsArray['organizationIds'] = $liste_organisme;
            $searchParmsArray['organization_exclusion'] = $exclusion_organisme;
            $searchParmsArray['observation'] = $observation;
            $searchParmsArray['territoryIds'] = $liste_territoire;
            $searchParmsArray['territory_exclusion'] = $exclusion_territoire;
            $searchParmsArray['territory_sub'] = null;
            $searchParmsArray['cityIds'] = $liste_commune;
            $searchParmsArray['city_exclusion'] = $exclusion_commune;
            $searchParmsArray['groupIds'] = $liste_groupe;
            $searchParmsArray['group_exclusion'] = $exclusion_groupe;
            $searchParmsArray['group_sub'] = null;
            $output->writeln('valeur injectée dans colonne search_list: <info>' . json_encode($searchParmsArray) . '</info>');
            $searchList->setSearchParams($searchParmsArray);
            $em->persist($searchList);
            $haveToBeFlush = true;
        }
        if ($haveToBeFlush) {
            $output->writeln('<info>Fin command mise a jour colonne search_params table Search_list a partir des anciennes données.' . count($searchLists) . ' objets mis à jour</info>');
            $em->flush();
        }
        $output->writeln('<info>Fin command mise a jour colonne search_params table Search_list à partir des anciennes données</info>');
    }
}

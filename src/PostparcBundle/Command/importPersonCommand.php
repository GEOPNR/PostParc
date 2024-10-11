<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\NullOutput;
use Cocur\Slugify\Slugify;
use PostparcBundle\Entity\Person;
use PostparcBundle\Entity\Coordinate;
use PostparcBundle\Entity\Email;
use PostparcBundle\Entity\Profession;

class importPersonCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:importPerson')
            ->setDescription('import new person form import_person table in postparc')
            ->addArgument('entityID', InputArgument::OPTIONAL, 'id de l\'entité à laquelle associer les insertions')
            ->addArgument('searchCityByCP', InputArgument::OPTIONAL, 'recherche la ville par le cp puis par insee si non trouvée')    
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slugify = new Slugify();
        $now = new \DateTime();
        $observationMessage = ' -- import postparc du ' . $now->format('d-m-Y') . ' --';
        $nbNewEntries = 0;
        $nbUpdatedEntries = 0;
        $returnMessage = [];
        $env = $this->getContainer()->getParameter('kernel.environment');

        if (!$input->getArgument('entityID')) {
            $helper = $this->getHelper('question');
            $entityIdQuestion = new Question('id de l\'entité à laquelle associer les insertions: ', 0);
            $entityId = $helper->ask($input, $output, $entityIdQuestion);
        } else {
            $entityId = $input->getArgument('entityID');
        }
        
        $searchCityByCP = false;
        if ($input->getArgument('searchCityByCP')) {
            $searchCityByCP = true;
        }

        $output->writeln('<info>Démarrage insertion Person</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();

        // récupération du contenu de la table importPerson
        $sql = 'SELECT * FROM import_person';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $progress = new ProgressBar($output, count($results));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $progress->setMessage('Task in progress...');
        $i = 0;
        $civilityMr = $em->getRepository('PostparcBundle:Civility')->find(1);
        $civilityMme = $em->getRepository('PostparcBundle:Civility')->find(2);
        $importUser = $em->getRepository('PostparcBundle:User')->find(2);
        $entity = $em->getRepository('PostparcBundle:Entity')->find($entityId);

        foreach ($results as $result) {
            $civility = $this->cleanString($result['civility']);
            $name = $this->cleanString($result['name']);
            $first_name = $this->cleanString($result['first_name']);
            $insee = $this->cleanString($result['insee']);
            $address_line_1 = $this->cleanString($result['address_line_1']);
            $address_line_2 = $this->cleanString($result['address_line_2']);
            $address_line_3 = $this->cleanString($result['address_line_3']);
            $cedex = $this->cleanString($result['cedex']);
            $phone = $this->cleanString($result['phone']);
            $mobile_phone = $this->cleanString($result['mobile_phone']);
            $fax = $this->cleanString($result['fax']);
            $web_site = $this->cleanString($result['web_site']);
            $email = $this->cleanString($result['email']);
            $birthInsee = $this->cleanString($result['birthInsee']);
            $profession = $this->cleanString($result['profession']);
            $facebookAccount = $this->cleanString($result['facebookAccount']);
            $twitterAccount = $this->cleanString($result['twitterAccount']);
            $observation = $this->cleanString($result['observation']);
            
            // cas particulier recherche sur CP si pas insee
            if ($searchCityByCP && $insee) {
                $city = $em->getRepository('PostparcBundle:City')->findOneBy(['zipCode'=> $insee]);
                if($city) {
                    $insee = $city->getInsee();
                }
            }

            // recherche si personne existe déjà dans la base
            $personSlug = $slugify->slugify(str_replace(['\'', '’'], '', $name . ' ' . $first_name), '-');
            $existingPerson = $em->getRepository('PostparcBundle:Person')->searchPersonForImport($personSlug, $insee, $entityId);
            $is_new = true;
            if ($existingPerson) {
                $person = $existingPerson;
                $is_new = false;
                $nbUpdatedEntries++;
                $returnMessage[] = '<info>' . $person . ' already exist in database => <comment>update</comment></info>';
            } else { // add new Person
                $person = new Person();
                $person->setEnv($env);
                $nbNewEntries++;
            }
            if ($is_new) {
                $person->setCreatedBy($importUser);
                $person->setEntity($entity);
            } else {
                $person->setObservation($existingPerson->getObservation() .' '. $observation);
            }
            if ($civility) {
                $civilitySlug = $slugify->slugify($civility, '-');
                $civilityObject = $em->getRepository('PostparcBundle:Civility')->findOneBySlug($civilitySlug);
                if (!$civilityObject) {
                    $civilityObject = in_array($civilitySlug, ['mme', 'mlle', 'mademoiselle']) ? $civilityMme : $civilityMr;
                }
                $person->setCivility($civilityObject);
            }
            $person->setName($name);
            $person->setFirstName($first_name);
            if ($profession) {
                $professionSlug = $slugify->slugify($profession, '-');
                $professionObject = $em->getRepository('PostparcBundle:Profession')->findOneBy(['slug'=>$professionSlug]);
                if (!$professionObject) {
                    $professionObject = new Profession();
                    $professionObject->setName($profession);
                    $em->persist($professionObject);
                    $em->flush();
                }
                $person->setProfession($professionObject);
            }
            if (array_key_exists('nbMinorChildreen', $result)) {
                $nbMinorChildreen = $this->cleanString($result['nbMinorChildreen']);
                $person->setNbMinorChildreen($nbMinorChildreen);
            }
            if (array_key_exists('nbMinorChildreen', $result)) {
                $nbMajorChildreen = $this->cleanString($result['nbMajorChildreen']);
                $person->setNbMajorChildreen($nbMajorChildreen);
            }
            $person->setObservation($observation . $observationMessage);

            // coordonnées
            $newCoordinate = false;
            $newEmail = false;
            if ($is_new || (!$is_new && !$person->getCoordinate())) {
                $coordinate = new Coordinate();
                $newCoordinate = true;
            } else {
                $coordinate = $person->getCoordinate();
            }

            $coordinate->setAddressLine1($address_line_1);
            $coordinate->setAddressLine2($address_line_2);
            $coordinate->setAddressLine3($address_line_3);
            $coordinate->setCedex($cedex);
            $coordinate->setPhone($phone);
            $coordinate->setMobilePhone($mobile_phone);
            $coordinate->setFax($fax);
            $coordinate->setWebSite($web_site);
            $coordinate->setFacebookAccount($facebookAccount);
            $coordinate->setTwitterAccount($twitterAccount);
            if ($newCoordinate) {
                $coordinate->setCreatedBy($importUser);
            }
            if ($email) {
                if ($is_new || (!$is_new && (!$person->getCoordinate() || ($person->getCoordinate() && !$person->getCoordinate()->getEmail())))) {
                    $newEmail = true;
                    $emailObject = new Email();
                } else {
                    $emailObject = $person->getCoordinate()->getEmail();
                }

                $emailObject->setEmail($email);
                $em->persist($emailObject);
                $em->flush();
                if ($newEmail) {
                    $coordinate->setEmail($emailObject);
                }
            }
            if ($insee) {
                $city = $em->getRepository('PostparcBundle:City')->findOneBy(['insee'=>$insee]);
                if ($city) {
                    $city->setIsActive(1);
                    $em->persist($city);
                    $coordinate->setCity($city);
                }
            }
            if ($birthInsee) {
                $city = $em->getRepository('PostparcBundle:City')->findOneBy(['insee'=>$birthInsee]);
                if ($city) {
                    $person->setBirthLocation($city);
                }
            }

            $em->persist($coordinate);
            if ($newCoordinate) {
                $person->setCoordinate($coordinate);
            }
            $person->setIsShared(false);

            $em->persist($person);

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
        $output->writeln('<info><comment>' . $nbNewEntries . '</comment> persons added</info>');
        $output->writeln('<info><comment>' . $nbUpdatedEntries . '</comment> persons updated</info>');
        foreach($returnMessage as $message){
            $output->writeln($message);
        }

        // lancement commande updateAddressCoordinates
        $this->updateAddressCoordinates($output);
    }

    private function updateAddressCoordinates($output)
    {
        //$output->writeln('<info>Start execution of postparc:updateAddressCoordinates command</info>');
        $command = $this->getApplication()->find('postparc:updateAddressCoordinates');
        $arguments = ['command' => $command->getName()];
        $input = new ArrayInput($arguments);
        $output = new NullOutput();
        $command->run($input, $output);
    }

    private function cleanString($string)
    {
        return strip_tags(trim($string));
    }
}

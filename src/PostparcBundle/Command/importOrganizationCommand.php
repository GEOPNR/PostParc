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
use PostparcBundle\Entity\Organization;
use PostparcBundle\Entity\OrganizationType;
use PostparcBundle\Entity\Coordinate;
use PostparcBundle\Entity\Email;

class importOrganizationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:importOrganization')
            ->setDescription('import new organization form import_organization table in postparc')
            ->addArgument('entityID', InputArgument::OPTIONAL, 'id de l\'entité à laquelle associer les insertions')
            ->addArgument('searchCityByCP', InputArgument::OPTIONAL, 'recherche la ville par le cp puis par insee si non trouvée')  
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slugify = new Slugify();
        $now = new \DateTime();
        $observationMessage = ' -- import postparc du ' . $now->format('d-m-Y') . ' --';

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

        $output->writeln('<info>Démarrage insertion Organization</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $env = $this->getContainer()->getParameter('kernel.environment');

        // récupération du contenu de la table importOrganization
        $sql = 'SELECT * FROM import_organization';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $progress = new ProgressBar($output, count($results));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $progress->setMessage('Task in progress...');
        $i = 0;

        $importUser = $em->getRepository('PostparcBundle:User')->find(2);
        $entity = $em->getRepository('PostparcBundle:Entity')->find($entityId);

        $nbNewEntries = 0;
        $nbUpdatedEntries = 0;
        $returnMessage = [];

        foreach ($results as $result) {
            $name = $this->cleanString($result['name']);
            $abbreviation = $this->cleanString($result['abbreviation']);
            $insee = $this->cleanString($result['insee']);
            $organizationType = $this->cleanString($result['organizationType']);
            $address_line_1 = $this->cleanString($result['address_line_1']);
            $address_line_2 = $this->cleanString($result['address_line_2']);
            $address_line_3 = $this->cleanString($result['address_line_3']);
            $cedex = $this->cleanString($result['cedex']);
            $phone = $this->cleanString($result['phone']);
            $mobile_phone = $this->cleanString($result['mobile_phone']);
            $fax = $this->cleanString($result['fax']);
            $web_site = $this->cleanString($result['web_site']);
            $email = $this->cleanString($result['email']);
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

            // recherche si un organisme existe déjà dans la base
            $organizationSlug = $slugify->slugify(str_replace(['\'', '’'], '', $name), '-');
            $existingOrganization = $em->getRepository('PostparcBundle:Organization')->searchOrganizationForImport($organizationSlug, $insee, $entityId);
            $is_new = true;
            if ($existingOrganization) {
                $organization = $existingOrganization;
                $is_new = false;
                $nbUpdatedEntries++;
                $returnMessage[] = '<info>' . $organization . ' already exist in database => <comment>update</comment></info>';
            } else { // add new Person
                $organization = new Organization();
                $nbNewEntries++;
            }

            if ($is_new) {
                $organization->setCreatedBy($importUser);
                $organization->setEntity($entity);
                $organization->setEnv($env);
                $organization->setName($name);
                $organization->setObservation($observation . $observationMessage);
                $organization->setIsShared(false);
            } else {
                $organization->setObservation($existingOrganization->getObservation() .' '. $observation);
            }
            $organization->setAbbreviation($abbreviation);

            // gestion OrganizationType
            if ($organizationType) {
                $organizationTypeSlug = $slugify->slugify(str_replace(['\'', '’'], '', $organizationType), '-');
            $organizationTypeObject = $em->getRepository('PostparcBundle:OrganizationType')->findOneBy(['slug' => $organizationTypeSlug]);
                if (!$organizationTypeObject) {
                    $organizationTypeObject = new OrganizationType();
                    $organizationTypeObject->setName($organizationType);
                    $organizationTypeObject->setCreatedBy($importUser);
                    $em->persist($organizationTypeObject);
                    $em->flush();
                }
                $organization->setOrganizationType($organizationTypeObject);
            }
            // coordonnées
            $newCoordinate = false;
            $newEmail = false;
            if ($is_new || (!$is_new && !$organization->getCoordinate())) {
                $coordinate = new Coordinate();
                $newCoordinate = true;
            } else {
                $coordinate = $organization->getCoordinate();
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
                if ($is_new || (!$is_new && (!$organization->getCoordinate() || ($organization->getCoordinate() && !$organization->getCoordinate()->getEmail())))) {
                    $emailObject = new Email();
                    $newEmail = true;
                } else {
                    $emailObject = $organization->getCoordinate()->getEmail();
                }
                $emailObject->setEmail($email);
                $em->persist($emailObject);
                $em->flush();
                if ($newEmail) {
                    $coordinate->setEmail($emailObject);
                }
            }
            if ($insee) {
                $city = $em->getRepository('PostparcBundle:City')->findOneBy(['insee'=> $insee]);
                if ($city) {
                    $city->setIsActive(1);
                    $em->persist($city);
                    $coordinate->setCity($city);
                }
            }
            $em->persist($coordinate);
            if ($newCoordinate) {
                $organization->setCoordinate($coordinate);
            }

            $em->persist($organization);

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
        $output->writeln('<info><comment>' . $nbNewEntries . '</comment> organizations added</info>');
        $output->writeln('<info><comment>' . $nbUpdatedEntries . '</comment> organizations updated</info>');
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
        $output = new NullOutput();
        $input = new ArrayInput($arguments);
        $command->run($input, $output);
    }

    private function cleanString($string)
    {
        return strip_tags(trim($string));
    }
}

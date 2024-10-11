<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;
use Cocur\Slugify\Slugify;
use PostparcBundle\Entity\Representation;
use PostparcBundle\Entity\Person;
use PostparcBundle\Entity\Organization;
use PostparcBundle\Entity\MandateType;
use PostparcBundle\Entity\NatureOfRepresentation;
use PostparcBundle\Entity\Coordinate;

class importRepresentationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:importRepresentation')
            ->setDescription('import new representation form import_representation table in postparc')
            ->addArgument('entityID', InputArgument::OPTIONAL, 'id de l\'entité à laquelle associer les insertions')
            ->addArgument('searchCityByCP', InputArgument::OPTIONAL, 'recherche la ville par le cp puis par insee si non trouvée')    
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slugify = new Slugify();
        $now = new \DateTime();
        $observationMessage = ' -- import postparc du ' . $now->format('d-m-Y') . ' --';
        $addObjectIfNotexist =  true;
        $env = $this->getContainer()->getParameter('kernel.environment');
        $returnMessage = [];

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

        $output->writeln('<info>Démarrage insertion representations</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $civilityMr = $em->getRepository('PostparcBundle:Civility')->find(1);

        // récupération du contenu de la table importOrganization
        $sql = 'SELECT * FROM import_representation';
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

        foreach ($results as $result) {
            $organization = $this->cleanString($result['organisme']);
            $theme = $this->cleanString($result['theme']);
            $nature = $this->cleanString($result['nature']);
            $dateDesignation = $this->cleanString($result['date_designation']);
            $duree_mandat = $this->cleanString($result['duree_mandat']);
            $nb_reunions_an = $this->cleanString($result['nb_reunions_an']);
            $mode_designation = $this->cleanString($result['mode_designation']);
            $observation = $this->cleanString($result['observation']);
            $mandateType = $this->cleanString($result['type_de_mandat']);
            $lastname = $this->cleanString($result['last_name']);
            $firstname = $this->cleanString($result['first_name']);
            $insee = $this->cleanString($result['insee_personne']);
            
            // cas particulier recherche sur CP si pas insee
            if ($searchCityByCP && $insee) {
                $city = $em->getRepository('PostparcBundle:City')->findOneBy(['zipCode'=> $insee]);
                if($city) {
                    $insee = $city->getInsee();
                }
            }

            $representation = new Representation();
            $representation->setCreatedBy($importUser);
            $representation->setEntity($entity);
            $representation->setIsShared(0);

            // organization
            if ($organization) {
                $organizationSlug = $slugify->slugify(str_replace(['\'', '’'], '', $organization), '-');
                $organizationObject = $em->getRepository('PostparcBundle:Organization')->findOneBy(['slug'=>$organizationSlug]);
                if (!$organizationObject) {
                    $organizationObject = new Organization();
                    $organizationObject->setName($organization);
                    $organizationObject->setEnv($env);
                    $organizationObject->setObservation($observationMessage);
                    $organizationObject->setCreatedBy($importUser);
                    $organizationObject->setEntity($entity);
                    $organizationObject->setIsShared(0);
                    $organizationObject->setEnv($env);
                    $em->persist($organizationObject);
                    $em->flush();
                }
                $representation->setOrganization($organizationObject);
            }

            //theme / tag
            if ($theme) {
                $themeSlug = $slugify->slugify(str_replace(['\'', '’'], '', $theme), '-');
                $themeObject = $em->getRepository('PostparcBundle:Tag')->findOneBy(['slug'=>$themeSlug]);
                if ($themeObject) {
                    $representation->addTag($themeObject);
                }
            }

            // nature / atureOfRepresentation
            if ($nature) {
                $natureSlug = $slugify->slugify(str_replace(['\'', '’'], '', $nature), '-');
                $natureObject = $em->getRepository('PostparcBundle:NatureOfRepresentation')->findOneBy(['slug'=>$natureSlug]);
                if ($addObjectIfNotexist && !$natureObject) {
                    $natureObject = new NatureOfRepresentation();
                    $natureObject->setName($nature);
                    $em->persist($natureObject);
                    $em->flush();
                }
                if ($natureObject) {
                    $representation->setNatureOfRepresentation($natureObject);
                }
            }

            // date designation
            if ($dateDesignation) {
                $date = \DateTime::createFromFormat('d/m/Y', $dateDesignation);
                if ($date) {
                    $representation->setBeginDate($date) ;
                }
            }

            // duree_mandat
            if ($duree_mandat) {
                $representation->setMandatDuration($duree_mandat);
            }

            // $nb_reunions_an
            if ($nb_reunions_an) {
                $representation->setPeriodicity($nb_reunions_an);
            }

            // $mode_designation
            $representation->setElected(0);
            if ($mode_designation) {
                $modeDesignationSlug = $slugify->slugify(str_replace(['\'', '’'], '', $mode_designation), '-');
                if ($modeDesignationSlug == 'elu') {
                    $representation->setElected(1);
                }
            }

            // personne
            $personSlug = $slugify->slugify(str_replace(['\'', '’'], '', $lastname . ' ' . $firstname), '-');
            if (strlen($personSlug) !== 0) {
                $person = $em->getRepository('PostparcBundle:Person')->searchPersonForImport($personSlug, $insee);
                if (!$person) {
                    $person = new Person();
                    $person->setFirstName($firstname);
                    $person->setLastName($lastname);
                    $person->setCivility($civilityMr);
                    $person->setObservation($observationMessage);
                    $person->setCreatedBy($importUser);
                    $person->setEntity($entity);
                    $person->setIsShared(0);
                    $person->setEnv($env);
                    if ($insee) {
                        $city = $em->getRepository('PostparcBundle:City')->findOneBy(['insee'=>$insee]);
                        if ($city) {
                            $coordinate = new Coordinate();
                            $coordinate->setCity($city);
                            $em->persist($coordinate);
                            $person->setCoordinate($coordinate);
                        }
                    }
                    $em->persist($person);
                    $em->flush();
                }
                $representation->setPerson($person);
            }

            // $observation
            if ($observation) {
                $observationMessage .= $observation;
            }
            $representation->setObservation($observationMessage);

            // $mandatType
            if ($mandateType) {
                $mandateTypeSlug = $slugify->slugify(str_replace(['\'', '’'], '', $mandateType), '-');
                $mandateTypeObject = $em->getRepository('PostparcBundle:MandateType')->findOneBy(['slug'=>$mandateTypeSlug]);
                if ($addObjectIfNotexist &&  !$mandateTypeObject) {
                    $mandateTypeObject = new MandateType();
                    $mandateTypeObject->setName($mandateType);
                    $mandateTypeObject->setCreatedBy($importUser);
                    $em->persist($mandateTypeObject);
                    $em->flush();
                }
                if ($mandateTypeObject) {
                    $representation->setMandateType($mandateTypeObject);
                }
            }

            $representationName = $representation->getMandateType() . " " . $representation->getOrganization() . " " . $representation->getPerson();
            $representation->setName($representationName);

            $em->persist($representation);

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
        $output->writeln('<info>Fin insertion Representation <comment>' . $i . ' representations insérées</comment></info>');
        foreach($returnMessage as $message){
            $output->writeln($message);
        }
    }

    private function cleanString($string)
    {
        return strip_tags(trim($string));
    }
}

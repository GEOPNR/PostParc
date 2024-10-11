<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;
use Cocur\Slugify\Slugify;
use PostparcBundle\Entity\Pfo;
use PostparcBundle\Entity\Service;
use PostparcBundle\Entity\PersonFunction;
use PostparcBundle\Entity\AdditionalFunction;
use PostparcBundle\Entity\Email;

class importPfoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:importPfo')
            ->setDescription('import new pfos form import_pfo table in postparc')
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

        $output->writeln('<info>Démarrage insertion Pfo</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();

        // récupération du contenu de la table importOrganization
        $sql = 'SELECT * FROM import_pfo GROUP BY CONCAT(name,first_name,organization,service,function,phone,mobile_phone,fax)';
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
            $name = $this->cleanString($result['name']);
            $first_name = $this->cleanString($result['first_name']);
            $insee = $this->cleanString($result['insee']);
            $organization = $this->cleanString($result['organization']);
            $service = $this->cleanString($result['service']);
            $function = $this->cleanString($result['function']);
            $additionalFunction = $this->cleanString($result['additionalFunction']);
            $phone = $this->cleanString($result['phone']);
            $mobile_phone = $this->cleanString($result['mobile_phone']);
            $fax = $this->cleanString($result['fax']);
            $email = $this->cleanString($result['email']);
            $observation = $this->cleanString($result['observation']);
            
            // cas particulier recherche sur CP si pas insee
            if ($searchCityByCP && $insee) {
                $city = $em->getRepository('PostparcBundle:City')->findOneBy(['zipCode'=> $insee]);
                if($city) {
                    $insee = $city->getInsee();
                }
            }

            $pfo = new Pfo();
            $pfo->setCreatedBy($importUser);
            $pfo->setEntity($entity);
            $pfo->setIsShared(0);
            // recherche de la personne
            if (strlen($name . $first_name) > 0) {
                $personSlug = $slugify->slugify(str_replace(['\'', '’'], '', $name . ' ' . $first_name), '-');
                //$output->writeln('<info>Recherche personne avec slug '.$personSlug.'</info>');
                $person = $em->getRepository('PostparcBundle:Person')->searchPersonForImport($personSlug, $insee);
                if ($person) {
                    $pfo->setPerson($person);
                }
            }
            // champ commune de rattachement
            if ($insee) {
                $city = $em->getRepository('PostparcBundle:City')->findOneBy(['insee'=> $insee]);
                if ($city) {
                    $pfo->setConnectingCity($city);
                }
            }
            // recherche organization
            $organizationSlug = $slugify->slugify(str_replace(['\'', '’'], '', $organization), '-');
            $organizationObject = $em->getRepository('PostparcBundle:Organization')->findOneBySlug($organizationSlug);
            //$output->writeln('<info>Recherche organisme avec slug '.$organizationSlug.'</info>');
            if ($organizationObject) {
                $pfo->setOrganization($organizationObject);
            }
            // recherche service
            if ($service) {
                $serviceSlug = $slugify->slugify(str_replace(['\'', '’'], '', $service), '-');
                $serviceObject = $em->getRepository('PostparcBundle:Service')->findOneBySlug($serviceSlug);
                if (!$serviceObject) {
                    $serviceObject = new Service();
                    $serviceObject->setName($service);
                    $serviceObject->setCreatedBy($importUser);
                    $em->persist($serviceObject);
                    $em->flush();
                }
                $pfo->setService($serviceObject);
            }
            // recherche function
            if ($function) {
                $functionSlug = $slugify->slugify(str_replace(['\'', '’'], '', $function), '-');
                $functionObject = $em->getRepository('PostparcBundle:PersonFunction')->findOneBySlug($functionSlug);
                if (!$functionObject) {
                    $functionObject = new PersonFunction();
                    $functionObject->setName($function);
                    $functionObject->setCreatedBy($importUser);
                    $em->persist($functionObject);
                    $em->flush();
                }
                $pfo->setPersonFunction($functionObject);
            }

            // recherche additionalFunction
            if ($additionalFunction) {
                $additionalFunctionSlug = $slugify->slugify(str_replace(['\'', '’'], '', $additionalFunction), '-');
                $additionalFunctionObject = $em->getRepository('PostparcBundle:AdditionalFunction')->findOneBySlug($additionalFunctionSlug);
                if (!$additionalFunctionObject) {
                    $additionalFunctionObject = new AdditionalFunction();
                    $additionalFunctionObject->setName($additionalFunction);
                    $additionalFunctionObject->setCreatedBy($importUser);
                    $em->persist($additionalFunctionObject);
                    $em->flush();
                }
                $pfo->setAdditionalFunction($additionalFunctionObject);
            }
            $pfo->setPhone($phone);
            $pfo->setMobilePhone($mobile_phone);
            $pfo->setFax($fax);
            $pfo->setObservation($observation . $observationMessage);

            if ($email) {
                $emailObject = new Email();
                $emailObject->setEmail($email);
                $em->persist($emailObject);
                $em->flush();
                $pfo->setEmail($emailObject);
            }

            $em->persist($pfo);

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
        $output->writeln('<info>Fin insertion Pfo <comment>' . $i . ' pfo insérés</comment></info>');
    }

    private function cleanString($string)
    {
        return strip_tags(trim($string));
    }
}

<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Dumper;

class deployInstanceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
                ->setName('postparc:deployInstanceCommand')
                ->setDescription('deploy new postparc instance')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        // question sous domaine
        $subDomainQuestion = new Question('Sous domaine de l\'instance: ', null);
        $subDomain = $helper->ask($input, $output, $subDomainQuestion);
        $subDomain = str_replace('.', '_', $subDomain);
        // question nom de l'instance
        $instanceNameQuestion = new Question('Intutulé de l\'instance: ', null);
        $instanceName = $helper->ask($input, $output, $instanceNameQuestion);
        // question si migration ou Non
        //$isMigrationQuestion = new ConfirmationQuestion('S\'agit il d\'une migration depuis une version V1? (y/n): ', false);
        //$isMigration = $helper->ask($input, $output, $isMigrationQuestion) ? 1 : 0;

        $isMigration = false;

        $department = null;
        if (!$isMigration) {
            // question departement
            //$departmentQuestion = new Question('Numéro de département à activer: ', null);
            //$department = $helper->ask($input, $output, $departmentQuestion);
        }

        $output->writeln('<info>Démarrage deploiement instance ' . $subDomain . '</info>');

        // lancement de la création de la bdd
        $bddInfos = $this->deployBdd($subDomain, $isMigration, $output);

        if ($bddInfos && 200 == $bddInfos->result_code) {
            $output->writeln('<info>base de donnée ' . $this->getContainer()->getParameter('prefixNewDatabase') . $subDomain . ' deployée</info>');
            // generation fichier de conf
            $this->deployConfigFile($subDomain, $instanceName, $bddInfos, $output);
            // mise a jour du schema de base de données
            $this->updateSchema($subDomain, $input, $output);
            // migration
            if ($isMigration) {
                $this->executeMigration($subDomain, $input, $output);
            } else {
                // activation des communes du département
                //$this->activateCities($subDomain, $department, $output);
                // mise en place assets
                $this->deployAssets($subDomain, $output);
            }
            // mise en place dossier stockage images
            $this->createDocumentImagesFolder($subDomain, $output);
            $this->createOrganizationImagesFolder($subDomain, $output);
            // envoi email à support pour https
            $this->sendMessageToSupport($subDomain, $output);
            $output->writeln('<comment>Deploiement terminé, url de la nouvelle instance : <info>http://' . $subDomain . '.postparc.fr</info></comment>');
        } elseif ($bddInfos) {
            $output->writeln('<comment>Erreur lors du lancement du script de création de base de données: <info>' . $bddInfos->result_code . ': ' . $bddInfos->result_text . '</info></comment>');
        } else {
            $output->writeln('<comment>Erreur lors du lancement du script de création de base de données: <info>probleme deploiement bdd</info></comment>');
        }
    }

    /**
     * lancement commande mise a jour bdd.
     * */
    private function updateSchema($subDomain, $input, $output)
    {
        // mise a jour schema bdd
        $output->writeln('<info>Lancement commande mise à jour schema bdd</info>');
        $phpCliCommand = $this->getContainer()->hasParameter('phpCliCommand') ? $this->getContainer()->getParameter('phpCliCommand') : 'php';
        $command = $phpCliCommand . ' ' . $this->getContainer()->getParameter('postparcAbsolutePath') . 'bin/console doctrine:schema:update --force --env=' . $subDomain;
        $process = new Process($command);
        try {
            $process->mustRun();
            $output->writeln('<info>Mise a jour bdd executé:</info> <comment> ' . $process->getOutput() . '</comment>');
        } catch (ProcessFailedException $e) {
            $output->writeln('<info>Erreur update bdd :</info> <comment>' . $e->getMessage() . '</comment>');
        }
    }

    /**
     * lancement commande peuplement bdd v2 à partir bdd v1.
     * */
    private function executeMigration($subDomain, $input, $output)
    {
        $output->writeln('<info>Lancement script de migration de données</info>');
        $phpCliCommand = $this->getContainer()->hasParameter('phpCliCommand') ? $this->getContainer()->getParameter('phpCliCommand') : 'php';
        $command = $phpCliCommand . ' ' . $this->getContainer()->getParameter('postparcAbsolutePath') . 'bin/console postparc:migrateOldPostparc --env=' . $subDomain;
        $process = new Process($command);
        $process->setTimeout(7200);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo $buffer;
            } else {
                echo $buffer;
            }
        });
    }

    /**
     * lancement de l'appel au script de création d'une nouvelle bdd.
     *
     * @param type $subDomain
     * @param bool $isMigration
     *
     * @return type
     */
    private function deployBdd($subDomain, $isMigration, $output)
    {
        // lancement de la création de la bdd
        $params = [
            'key' => $this->getContainer()->getParameter('api_key'),
            'name' => $subDomain,
            'isMigration' => $isMigration,
        ];
        $url = $this->getContainer()->getParameter('api_protocol') . '://' . $this->getContainer()->getParameter('database_host') . ':' . $this->getContainer()->getParameter('api_port') . '/pp2/create';

        $restClient = $this->getContainer()->get('circle.restclient');

        $output->writeln('<info>Lancement deploiement sur serveur base de données</info>');
        $output->writeln('<comment>url appelée : ' . $url . '</comment>');
        $output->writeln('<comment>params : ' . print_r($params, 1) . '</comment>');
        try {
            $response = $restClient->post($url, json_encode($params));
        } catch (Ci\RestClientBundle\Exceptions\CurlException $exception) {
            $output->writeln('<comment>Erreur deploiement bdd :' . $exception . '</comment>');

            return null;
        }

        return json_decode($response->getContent());
    }

    /**
     * lancement copie du config.yml et edition de ce fichier avec les paramètres du site.
     *
     * @param type $subDomain
     * @param type $bddInfos
     */
    private function deployConfigFile($subDomain, $instanceName, $bddInfos, $output)
    {
        // generation fichier de conf
        $newConfigFileName = 'config_' . $subDomain . '.yml';
        $absoluteBaseFolder = $this->getContainer()->getParameter('postparcAbsolutePath') . 'app/config/';
        $database_host = $this->getContainer()->getParameter('database_host');

        $dumper = new Dumper();
        $ymlDump = [
            'imports' => [['resource' => 'config_prod.yml']],
            'parameters' => [
                'instanceName' => $instanceName,
                'isMultiInstance' => false,
            ],
            'twig' => [
                'globals' => [
                    'isMultiInstance' => '%isMultiInstance%',
                    'locales' => ['fr'],
                ],
            ],
            'doctrine' => [
                'dbal' => [
                    'dbname' => $bddInfos->dbname,
                    'user' => $bddInfos->dbuser,
                    'password' => $bddInfos->secret,
                    'host' => $database_host,
                ],
            ],
        ];
        $yaml = $dumper->dump($ymlDump, 5);
        $path = $absoluteBaseFolder . '/' . $newConfigFileName;
        file_put_contents($path, $yaml);
        $output->writeln('<info>Fichier de configuration config_' . $subDomain . ' deployée</info>');
    }

    /**
     * activation des communes du département
     * uniquement executé dans le cadre du deploiement d'une instance vierge.
     * */
    private function activateCities($subDomain, $department, $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $prefixNewDatabase = $this->getContainer()->getParameter('prefixNewDatabase');
        $output->writeln('<info>Activation des communes du département ' . $department . ' dans table ' . $prefixNewDatabase . $subDomain . '.city</info>');

        $sql = '
        UPDATE ' . $prefixNewDatabase . $subDomain . '.city SET is_active=1 WHERE zip_code LIKE \'' . $department . '%\'';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $output->writeln('');
    }

    /**
     * copie du dossier des assets par defaut
     * uniquement executé dans le cadre du deploiement d'une instance vierge.
     * */
    private function deployAssets($subDomain, $output)
    {
        // mise a jour schema bdd
        $output->writeln('<info>Lancement commande deploiement specifics assets</info>');

        $command = 'cp -r ' . $this->getContainer()->getParameter('postparcAbsolutePath') . 'src/PostparcBundle/Resources/public/specifics/default ' . $this->getContainer()->getParameter('postparcAbsolutePath') . 'src/PostparcBundle/Resources/public/specifics/' . $subDomain;
        $process = new Process($command);
        try {
            $process->mustRun();
            $output->writeln('<info>Deploiement specifics assets:</info><comment> ' . $process->getOutput() . '</comment>');
        } catch (ProcessFailedException $e) {
            $output->writeln('<info>Erreur deploiement specifics assets :</info><comment>' . $e->getMessage() . '</comment>');
        }
    }

    /**
     * création dossier de stockage images pour les documents.
     *
     * @param type $subDomain
     * @param type $output
     */
    private function createDocumentImagesFolder($subDomain, $output)
    {
        $output->writeln('<info>Lancement création dossier de stockage images pour les documents</info>');
        $command = 'mkdir -p ' . $this->getContainer()->getParameter('postparcAbsolutePath') . 'web/uploads/documentTemplateImages/' . $subDomain . '/images';
        $process = new Process($command);
        try {
            $process->mustRun();
            $output->writeln('<info>Génération du dossier de stockage images pour les documents terminée :</info><comment> ' . $process->getOutput() . '</comment>');
        } catch (ProcessFailedException $e) {
            $output->writeln('<info>Génération du dossier de stockage images pour les documents :</info><comment>' . $e->getMessage() . '</comment>');
        }
    }

    /**
     * création dossier de stockage images pour les organizations.
     *
     * @param type $subDomain
     * @param type $output
     */
    private function createOrganizationImagesFolder($subDomain, $output)
    {
        $output->writeln('<info>Lancement création dossier de stockage images pour les organizations</info>');
        $command = 'mkdir -p ' . $this->getContainer()->getParameter('postparcAbsolutePath') . 'web/uploads/organizationImages/' . $subDomain . '/images';
        $process = new Process($command);
        try {
            $process->mustRun();
            $output->writeln('<info>Génération du dossier de stockage images pour les organizations terminée :</info><comment> ' . $process->getOutput() . '</comment>');
        } catch (ProcessFailedException $e) {
            $output->writeln('<info>Génération du dossier de stockage images pour les organizations :</info><comment>' . $e->getMessage() . '</comment>');
        }
    }

    private function sendMessageToSupport($subDomain, $output)
    {
        $container = $this->getContainer();

        $mailer = $container->get('mailer');
        $message = \Swift_Message::newInstance()
                ->setSubject("nouveau deploiement instance postparc '" . $subDomain . "'")
                ->setFrom('no-reply@postparc.fr')
                ->setTo('support@probesys.com')
                ->setCc('contact@postparc.fr')
                ->setContentType('text/html')
                ->setBody("Bonjour,<br/>Nouvelle instance postparc deployée : $subDomain.postparc.fr</br>Mettre en place certificat pour https.<br/>Merci")
        ;
        $mailer->send($message);
        $output->writeln('<info>Email envoyé à support@probesys.com pour mise en place https</info>');
    }
}

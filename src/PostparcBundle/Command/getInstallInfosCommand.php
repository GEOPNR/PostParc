<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputOption;

class getInstallInfosCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
                ->setName('postparc:getInstallInfosCommand')
                ->setDescription('get principals infos for each env')
                ->addOption('annualReport',null,  InputOption::VALUE_OPTIONAL, 'report for all the previous annual data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $optionValue = $input->getOption('annualReport');
        if(($optionValue == 1)){
            $now = new \DateTime('- 1 year');
            $title = 'RAPPORT ANNUEL UTILISATION POSTPARC ' . $now->format('Y');
        } else {
            $now = new \DateTime('- 1 month');
            $title = 'RAPPORT MENSUEL UTILISATION POSTPARC ' . $now->format('Y-m');
        }

        $datePreviousMonthFormated = $now->format('Y-m');
        
        $phpExecutagbleVersion = 'php7.3';

        $output->writeln('<info>#### ' . $title . ' ####</info>');
        $output->writeln('');

        //$notEnvUpdate = ['prod', 'test'];
        $notEnvUpdate = ['dev', 'prod', 'test', 'demo', 'sandbox', 'troizaire', 'dev_example'];
        $queryAccount = 'SELECT count(u) as nb_account FROM PostparcBundle:User u WHERE u.enabled=1';
        $queryNewAccount = $queryAccount.' AND u.created >= \''.$now->format('Y-m').'-01\'';
        $queryEntities = 'SELECT count(e) as nb_entity FROM PostparcBundle:Entity e';
        $queryPersons = 'SELECT count(p) as nb FROM PostparcBundle:Person p WHERE p.deletedAt IS NULL';
        $queryNewPersons = $queryPersons.' AND p.created >= \''.$now->format('Y-m').'-01\'';
        $queryOrganizations = 'SELECT count(o) as nb FROM PostparcBundle:Organization o WHERE o.deletedAt IS NULL';
        $queryNewOrganizations = $queryOrganizations.' AND o.created >= \''.$now->format('Y-m').'-01\'';
        $queryPfos = 'SELECT count(pfo) as nb FROM PostparcBundle:Pfo pfo WHERE pfo.deletedAt IS NULL';
        $queryNewPfos = $queryPfos.' AND pfo.created >= \''.$now->format('Y-m').'-01\'';
        $queryRepresentations = 'SELECT count(rep) as nb FROM PostparcBundle:Representation rep WHERE rep.deletedAt IS NULL';
        $queryNewRepresentations = $queryRepresentations.' AND rep.created >= \''.$now->format('Y-m').'-01\'';
        $queryMailSentDuringPreviousMonth = "SELECT SUM(ms.nbEmail) as nb FROM PostparcBundle:MailStats ms WHERE ms.date LIKE '" . $datePreviousMonthFormated . "%'";

        $nbtotalAccount = 0;
        $nbTotalEntities = 0;
        $nbInstall = 0;

        $subdomains = glob($this->getContainer()->get('kernel')->getRootDir() . '/config/clients/config_*.yml');
        $report = [];
        $total = [
                'nbActiveAccount' => 0,
                'nbNewActiveAccount' => 0,
                'nbEntities' => 0,
                'nbPersons' => 0,
                'nbNewPersons' => 0,
                'nbOrganizations' => 0,
                'nbNewOrganizations' => 0,
                'nbPfos' => 0,
                'nbNewPfos' => 0,
                'nbRepresentations' => 0,
                'nbNewRepresentations' => 0,
                'nbMailSentDuringPreviousMonth' => 0
        ];

        foreach ($subdomains as $subdomain) {
            $sname = str_replace(['config_', '.yml'], '', basename($subdomain));
            if (!in_array($sname, $notEnvUpdate)) {
                // active accounts
                $resultAccount = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryAccount . '" --hydrate=single_scalar -e ' . $sname);
                $nbAccount = 0;
                if (substr_count($resultAccount, 'NULL') === 0) {
                    $nbAccount = explode('\'', str_replace('"', '\'', $resultAccount))[1];
                }
                $report[$sname]['nbActiveAccount'] = $nbAccount;
                $total['nbActiveAccount'] += $nbAccount;
                
                // new nactive accounts
                $resultNewAccount = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryNewAccount . '" --hydrate=single_scalar -e ' . $sname);
                $nbNewAccount = 0;
                if (substr_count($resultNewAccount, 'NULL') === 0) {
                    $nbNewAccount = explode('\'', str_replace('"', '\'', $resultNewAccount))[1];
                }
                $report[$sname]['nbNewActiveAccount'] = $nbNewAccount;
                $total['nbNewActiveAccount'] += $nbNewAccount;

                // entities
                $resultEntities = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryEntities . '" --hydrate=single_scalar -e ' . $sname);
                $nbEntities = explode('\'', str_replace('"', '\'', $resultEntities))[1];
                if ($nbEntities > 1) {
                    // multiinstance
                    $nbEntities--;
                }
                $report[$sname]['nbEntities'] = $nbEntities;
                $total['nbEntities'] += $nbEntities;
                $nbInstall++;

                // nbPersons
                $resultPersons = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryPersons . '" --hydrate=single_scalar -e ' . $sname);
                $nbPersons = explode('\'', str_replace('"', '\'', $resultPersons))[1];
                $report[$sname]['nbPersons'] = $nbPersons;
                $total['nbPersons'] += $nbPersons;
                
                // nbNewPersons
                $resultNewPersons = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryNewPersons . '" --hydrate=single_scalar -e ' . $sname);
                $nbNewPersons = explode('\'', str_replace('"', '\'', $resultNewPersons))[1];
                $report[$sname]['nbNewPersons'] = $nbNewPersons;
                $total['nbNewPersons'] += $nbNewPersons;

                // Organization
                $resultOrganizations = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryOrganizations . '" --hydrate=single_scalar -e ' . $sname);
                $nbOrganizations = explode('\'', str_replace('"', '\'', $resultOrganizations))[1];
                $report[$sname]['nbOrganizations'] = $nbOrganizations;
                $total['nbOrganizations'] += $nbOrganizations;
                
                // New Organization
                $resultNewOrganizations = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryNewOrganizations . '" --hydrate=single_scalar -e ' . $sname);
                $nbNewOrganizations = explode('\'', str_replace('"', '\'', $resultNewOrganizations))[1];
                $report[$sname]['nbNewOrganizations'] = $nbNewOrganizations;
                $total['nbNewOrganizations'] += $nbNewOrganizations;

                // Pfo
                $resultPfos = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryPfos . '" --hydrate=single_scalar -e ' . $sname);
                $nbPfos = explode('\'', str_replace('"', '\'', $resultPfos))[1];
                $report[$sname]['nbPfos'] = $nbPfos;
                $total['nbPfos'] += $nbPfos;
                
                // New Pfo
                $resultNewPfos = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryNewPfos . '" --hydrate=single_scalar -e ' . $sname);
                $nbNewPfos = explode('\'', str_replace('"', '\'', $resultNewPfos))[1];
                $report[$sname]['nbNewPfos'] = $nbNewPfos;
                $total['nbNewPfos'] += $nbNewPfos;

                // Representation
                $resultRepresentations = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryRepresentations . '" --hydrate=single_scalar -e ' . $sname);
                $nbRepresentations = explode('\'', str_replace('"', '\'', $resultRepresentations))[1];
                $report[$sname]['nbRepresentations'] = $nbRepresentations;
                $total['nbRepresentations'] += $nbRepresentations;
                
                // New Representation
                $resultNewRepresentations = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryNewRepresentations . '" --hydrate=single_scalar -e ' . $sname);
                $nbNewRepresentations = explode('\'', str_replace('"', '\'', $resultNewRepresentations))[1];
                $report[$sname]['nbNewRepresentations'] = $nbNewRepresentations;
                $total['nbNewRepresentations'] += $nbNewRepresentations;

                // Mails sent during previous month
                $resultMailSentDuringPreviousMonth = shell_exec($phpExecutagbleVersion.' bin/console doctrine:query:dql "' . $queryMailSentDuringPreviousMonth . '" --hydrate=single_scalar -e ' . $sname);
                $nbMailSentDuringPreviousMonth = 0;
                if (substr_count($resultMailSentDuringPreviousMonth, 'NULL') === 0) {
                    $nbMailSentDuringPreviousMonth = explode('\'', str_replace('"', '\'', $resultMailSentDuringPreviousMonth))[1];
                }
                $report[$sname]['nbMailSentDuringPreviousMonth'] = $nbMailSentDuringPreviousMonth;
                $total['nbMailSentDuringPreviousMonth'] += $nbMailSentDuringPreviousMonth;
            }
            
        }

        $table = new Table($output);
        $table->setHeaders(['INSTANCE', 'nbActiveAccount', 'nbNewActiveAccount', 'nbEntities', 'nbPersons','nbNewPersons', 'nbOrganizations', 'nbNewOrganizations', 'nbPfos', 'nbNewPfos', 'nbRepresentations','nbNewRepresentations', 'nbMailSentDuringPreviousMonth']);
        foreach ($report as $key => $data) {
            $table->addRow(array_merge([$key], $data));
            $table->addRow(new TableSeparator());
        }

        $table->addRow(array_merge(['TOTAL'], $total));
        $table->render();

        $output->writeln('');
        $output->writeln('<info>#### FIN RAPPORT UTILISATION POSTPARC ' . $now->format('Y-m') . ' pour ' . $nbInstall . ' installation(s) ####</info>');
    }
}

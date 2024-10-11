<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;

class purgeTrashCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
                ->setName('postparc:purgeTrashCommand')
                ->setDescription('purge all elements in trash older than one year')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $this->getContainer()->get('kernel')->getEnvironment();
        $output->writeln('<info>Start command,  purge all elements in trash older than one year for env ' . $env . '</info>');
        $trashableTables = ['print_format','groups','pfo','person','organization','search_list','document_template','territory','representation','entity'];
        sort($trashableTables);

        $dateLimit = new \DateTime();
        $dateLimit->sub(new \DateInterval('P1Y'));

        $em = $this->getContainer()->get('doctrine')->getManager();
        $processDetailsResult = [];

        foreach ($trashableTables as $tableName) {
            $sql = "DELETE FROM " . $tableName . " WHERE deletedAt IS NOT NULL AND deletedAt < '" . $dateLimit->format('Y-m-d') . "'";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            // get nb deleted elements
            $processDetailsResult[$tableName] = $stmt->rowCount();
        }
        $output->writeln('<info>Results :</info>');

        $table = new Table($output);
        $table->setHeaders(['TABLE NAME', 'Nb elements deleted']);
        foreach ($processDetailsResult as $key => $value) {
            $table->addRow([$key, $value]);
        }
        $table->render();

        $output->writeln('<info>End process for env ' . $env . '</info>');
    }
}

<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Cocur\Slugify\Slugify;

class updateRepresentationNameCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:updateRepresentationName')
            ->setDescription('massive update of representations entities')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slugify = new Slugify();
        $now = new \DateTime();

        $output->writeln('<info> -- démmarage update massif representations name ' . $now->format('d-m-Y') . ' --</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $representations = $em->getRepository('PostparcBundle:Representation')->findAll();

        $progress = new ProgressBar($output, count($representations));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $progress->setMessage('Task in progress...');
        $i = 0;

        foreach ($representations as $representation) {
            $updator = $representation->getUpdatedBy();
            $representation->updateName();
            $representation->setUpdatedBy($updator);
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
        $output->writeln('<info>Fin update massif representations slug <comment>' . $i . ' representation maj</comment></info>');
    }
}

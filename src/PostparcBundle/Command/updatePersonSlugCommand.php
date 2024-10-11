<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Cocur\Slugify\Slugify;

class updatePersonSlugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:updatePersonSlug')
            ->setDescription('massive update of persons entities')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slugify = new Slugify();
        $now = new \DateTime();

        $output->writeln('<info> -- démmarage update massif persons slug ' . $now->format('d-m-Y') . ' --</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $persons = $em->getRepository('PostparcBundle:Person')->findAll();

        $progress = new ProgressBar($output, count($persons));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $progress->setMessage('Task in progress...');
        $i = 0;

        foreach ($persons as $person) {
            $updator = $person->getUpdatedBy();
            $personSlug = $slugify->slugify($person->getName() . ' ' . $person->getFirstName(), '-');
            $person->setSlug($personSlug);
            $person->setUpdatedBy($updator);
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
        $output->writeln('<info>Fin update massif persons slug <comment>' . $i . ' person maj</comment></info>');
    }
}

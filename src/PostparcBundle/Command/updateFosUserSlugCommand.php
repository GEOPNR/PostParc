<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Cocur\Slugify\Slugify;

class updateFosUserSlugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:updateFosUserSlug')
            ->setDescription('massive update of fos_user entities')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slugify = new Slugify();
        $now = new \DateTime();

        $output->writeln('<info> -- démmarage update massif fos_user slug ' . $now->format('d-m-Y') . ' --</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $users = $em->getRepository('PostparcBundle:User')->findAll();

        $progress = new ProgressBar($output, count($users));
        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setMessage('Task starts');
        $progress->start();
        $progress->setMessage('Task in progress...');
        $i = 0;

        foreach ($users as $user) {
            $slug = $slugify->slugify($user->getLastName() . ' ' . $user->getFirstName(), '-');
            $user->setSlug($slug);
            $em->persist($user);

            $progress->advance();
            ++$i;
            if (0 == $i % 100) {
                $em->flush();
            }
        }
        $em->flush();
        $progress->finish();
        $progress->setMessage('Task is finished');
        $output->writeln('<info>Fin update massif fos_user slug <comment>' . $i . ' person maj</comment></info>');
    }
}

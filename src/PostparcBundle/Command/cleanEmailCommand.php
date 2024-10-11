<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PostparcBundle\Entity\Email;

class cleanEmailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:cleanEmailCommand')
            ->setDescription('clean identical refs Email between pfo and coordinates')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Lancement commande nettoyage email communs entre pfo et coordonnées de l\'organisme associé</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $sql = 'SELECT pfo.id as pfoId, o.id as OrganizationId, em1.id as emailId, em1.email as email FROM pfo
            LEFT JOIN email em1 ON  pfo.email_id = em1.id
            LEFT JOIN organization o ON pfo.organization_id = o.id
            LEFT JOIN coordinates coord ON o.coordinate_id = coord.id
            LEFT JOIN email em2 ON coord.email_id = em2.id
            WHERE em1.id=em2.id';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        foreach ($results as $result) {
            $pfo = $em->getRepository('PostparcBundle:Pfo')->find($result['pfoId']);
            $email = new Email();
            $email->setEmail($result['email']);
            $em->persist($email);
            $pfo->setEmail($email);
            $em->persist($pfo);
        }
        $em->flush();
        $output->writeln('<info>' . count($results) . ' nouveaux emails ont été crées et associés aux pfos</info>');
    }
}

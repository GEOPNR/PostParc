<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PostparcBundle\Entity\Email;

class cleanDoublonEmailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
                ->setName('postparc:cleanDoublonEmailCommand')
                ->setDescription('clean identical refs Email in coordinates table')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $this->getContainer()->get('kernel')->getEnvironment();
        $output->writeln('<info>Lancement commande nettoyage email en double ou plus table  coordonnées pour env ' . $env . '</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $sql = 'select email_id, email, count(*) as nb
            FROM coordinates c
            LEFT JOIN email e ON c.email_id = e.id
            WHERE email_id is not null
            GROUP BY email_id
            HAVING nb > 1 ORDER BY nb';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $nbEmailCreated = 0;
        foreach ($results as $result) {
            $emailString = $result['email'];
            $nb = $result['nb'];
            $emailId = $result['email_id'];
            $limit = $nb - 1;

            $coordinates = $em->getRepository('PostparcBundle:Coordinate')->findBy(['email' => $emailId], [], $limit, 0);
            foreach ($coordinates as $coordinate) {
                $email = new Email();
                $email->setEmail($emailString);
                $em->persist($email);
                $coordinate->setEmail($email);
                $em->persist($coordinate);
                ++$nbEmailCreated;
                if (0 == $nbEmailCreated % 100) {
                    $em->flush();
                }
            }
        }
        $em->flush();
        $output->writeln('<info>Fin commande nettoyage email en double ou plus table coordonnées ' . $nbEmailCreated . ' email ajoutés</info>');
    }
}

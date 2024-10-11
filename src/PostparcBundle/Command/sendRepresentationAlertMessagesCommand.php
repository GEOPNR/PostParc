<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class sendRepresentationAlertMessagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('postparc:sendRepresentationAlertMessages')
            ->setDescription('send representation alerts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $container = $this->getContainer();
        $mailer = $container->get('mailer');
        $env = $container->get('kernel')->getEnvironment();
        $host = $env . '.postparc.fr';
        $from = "no-reply@" . $host;
        $noreplyEmails = $this->getContainer()->getParameter('noreplyEmails');
        if($noreplyEmails && count($noreplyEmails)) {
            $firstNoreplyEmails = $noreplyEmails[0];
            if ('no-reply@postparc.fr' != $firstNoreplyEmails) {
                $from = $firstNoreplyEmails;
            }
        }
        $noreplyEmails =  $this->getContainer()->getParameter('noreplyEmails');

        $output->writeln('<info> -- start of sending representation alert command ' . $now->format('d-m-Y H:i:s') . ' for env ' . $env . ' --</info>');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $representations = $em->getRepository('PostparcBundle:Representation')->getSendableRepresentationAlerts($now);

        foreach ($representations as $representation) {
            $to = $representation->getAlerter()->getEmail();
            $body = 'Bonjour,<br/>'
                    . "La representation '" . $representation . "' arrive à échéance le : " . $representation->getEndDate()->format('d/m/Y') . '<br/><br/>'
                    . "Ce mail est un mail automatique envoyé par l'application postparc, ne pas y répondre."
                    ;
            // send message
            $subject = 'Alerte automatique fin de representation ' . $representation;
            $message = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom($from)
                    ->setTo($to)
                    ->setBody($body, 'text/html')
                    ;
            $mailer->send($message);
            $output->writeln('<info>Mail d\'alerte envoyé pour la représentation <comment>' . $representation . '</comment> à l\'adresse email <comment>' . $to . '</comment></info>');
        }

        $output->writeln('<info> -- end of sending representation alert command  for env ' . $env . ' --</info>');
    }
}

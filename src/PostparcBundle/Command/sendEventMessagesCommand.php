<?php

namespace PostparcBundle\Command;

use PostparcBundle\Entity\Event;
use PostparcBundle\Entity\EventAlert;
use PostparcBundle\Entity\MailStats;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class sendEventMessagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
              ->setName('postparc:sendEventMessagesCommand')
              ->setDescription('send eventAlerts to receipients')
              ->addArgument('eventAlertID', InputArgument::OPTIONAL, 'the id of the eventAlert to force send execution')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();

        $env = $container->get('kernel')->getEnvironment();

        $output->writeln('<info> -- start of sending eventAlerts emails command ' . $now->format('d-m-Y H:i:s') . ' for env ' . $env . ' --</info>');

        $eventAlertID = $input->getArgument('eventAlertID');
        if ($eventAlertID) {
            $eventAlert = $em->getRepository('PostparcBundle:EventAlert')->find($eventAlertID);
            $eventAlerts = [$eventAlert];
            $eventAlert->setIsSendedManualy(true);
        } else {
            $eventAlerts = $em->getRepository('PostparcBundle:EventAlert')->getSendableEventAlerts($now);
        }
        $emailInfos = [];
        $output->writeln('<comment><info>' . count($eventAlerts) . '</info> alertes à envoyer</comment>');
        
        $from = 'no-reply@postparc.fr';
        $noreplyEmails =  $this->getContainer()->getParameter('noreplyEmails');
        if($noreplyEmails && is_array($noreplyEmails)) {
            $from = $noreplyEmails[0];
        }

        foreach ($eventAlerts as $eventAlert) {
            if ($eventAlert->getIsManualAlert()) {
                $eventAlert->setEffectiveDate($now);
            }
            $event = $eventAlert->getEvent();
            $output->writeln('<comment>Traitement alert pour event <info>' . $event . '</info></comment>');            

            $mailFooterString = '';
            if ($eventAlert->getMailFooter()) {
                $mailFooterString = '<br/><br/>' . $eventAlert->getMailFooter()->getFooter();
            }

            $body = $eventAlert->getMessage() . $mailFooterString;
            $subject = $eventAlert->getName();

            $emailInfos = $this->getEventAlertEmailsInfos($eventAlert, $from, $body, $subject);

            // send messages
            $output->writeln('<comment>Expediteur de l\'alerte: <info>' . $from . '</info></comment>');
            $output->writeln('<comment>Envoi des emails</comment>');
            list($goodEmails, $badEmails) = $this->sendEmails($eventAlert, $emailInfos, $from);
            $output->writeln('<info>' . count($goodEmails) . '  emails sent and ' . count($badEmails) . ' emails rejected</info>');
            if (count($goodEmails) > 0) {
                $output->writeln('<info>List of good emails :</info>');
                $output->writeln('<comment>' . implode(', ', $goodEmails) . '</comment>');
            }
            if (count($badEmails) > 0) {
                $output->writeln('<info>List of bad emails :</info>');
                $output->writeln('<comment>' . implode(', ', $badEmails) . '</comment>');
            }
            // change infos of eventAlert
            $eventAlert->setRecipientEmails($goodEmails);
            $eventAlert->setrejectedEmails($badEmails);
            $eventAlert->setIsSended(true);
            $eventAlert->setSendedDate($now);
            $em->persist($eventAlert);
            $em->flush();
            if ('no-reply@postparc.fr' != $from) {
                $this->sendSummuaryEmail($from, $goodEmails, $badEmails, $eventAlert);
            }
            
            // ecriture dans la table email_stat
            if (($goodEmails !== []) > 0) {
                $mailStat = new MailStats();
                $now = new \DateTime();
                $mailStat->setDate($now);
                $mailStat->setSubject($subject);
                $mailStat->setBody($body);
                $mailStat->setNbEmail(count($goodEmails));
                $mailStat->setSender($eventAlert->getSenderEmail());
                $mailStat->setCreatedBy($eventAlert->getCreatedBy());
                $mailStat->setRecipientEmails($goodEmails);
                $mailStat->setRejectedEmails($badEmails);
                $mailStat->setToken($eventAlert->getToken());
                $em->persist($mailStat);
                $em->flush();
            }
        }

        $output->writeln('<info>end of sending eventAlerts emails command for env ' . $env . ' --</info>');
    }

    private function sendEmails(EventAlert $eventAlert, $emailInfos, $from)
    {
        $badEmails = [];
        $goodEmails = [];
        $maxAttachmentsSize = 10; // 10Mo

        // Matomo Tracker
        $env = $this->getContainer()->get('kernel')->getEnvironment();
        $piwikParams = $this->getContainer()->getParameter('piwik');
        $host = $env . '.postparc.fr';
        $token = 'eventAlert_' . $env . '-' . uniqid();
        $tracker = '<!-- Matomo Image Tracker-->'
            . '<img referrerpolicy="no-referrer-when-downgrade" src="https://stats.probesys.com/matomo.php?idsite='.$piwikParams['piwikStatsMailId'].'&rec=1&bots=1&url=https%3A%2F%2F' . $host . '%2Femail-opened%2F' . $token . '&action_name=email-opened-'.$token.'&_rcn=' . $token . '&_rck=' . $token . '&mtm_keyword=' . $token . '" style="border:0;” alt="" />'
            . '<!-- End Matomo -->';

        $em = $this->getContainer()->get('doctrine')->getManager();
        $eventAlert->setToken($token);
        $em->persist($eventAlert);
        $em->flush();

        $dkimParams = $this->getContainer()->getParameter('dkim');
        $useDkim = $dkimParams['use_dkim'];
        if ($useDkim) {
            $domain = $dkimParams['domain'];
            $selector = $dkimParams['selector'];
            $privateKey = file_get_contents($this->getContainer()->get('kernel')->getRootDir() . '/../' . $dkimParams['private_key_path']);
            $signer = new \Swift_Signers_DKIMSigner($privateKey, $domain, $selector);
        }
        
        if($eventAlert->getSenderName() && $eventAlert->getSenderEmail()) {
           $senderFromEmail = $eventAlert->getSenderEmail();
           $from = $eventAlert->getSenderName();
        } else {
            $senderFromEmail = "no-reply@" . $host;
            $noreplyEmails = $this->getContainer()->getParameter('noreplyEmails');
            if($noreplyEmails && count($noreplyEmails)) {
                $firstNoreplyEmails = $noreplyEmails[0];
                if ('no-reply@postparc.fr' != $firstNoreplyEmails) {
                    $senderFromEmail = $firstNoreplyEmails;
                }
            }
        }
        $senderFrom = [$senderFromEmail => $from];
        
        // ajout infos expéditeur
        $tracker .= '<br/><br/><i>' . str_replace(['%sender%','%replyMail%'], [$senderFromEmail, '<a href="mailto:' . $from . '">' . $from . '</a>'], $this->getContainer()->get('translator')->trans('sendMailMassifModule.messages.senderReplyInfo')) . '</i>';

        foreach ($emailInfos as $emailInfo) {
            $body = $emailInfo['body'];
            // Matomo Image Tracker
            $body .= $tracker;

            $subject = $emailInfo['subject'];
            // remove empty or false values from $recipients array
            $recipients = array_filter(explode(';', $emailInfo['email']));

            foreach ($recipients as $recipient) {
                if (!in_array($recipient, $goodEmails) && !in_array($recipient, $badEmails)) {
                    if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                        $message = $useDkim ? \Swift_SignedMessage::newInstance() : \Swift_Message::newInstance();
                        $message
                            ->setSubject($subject)
                            ->setFrom($senderFrom)
                            //->setReplyTo($from)
                            ->setTo(trim($recipient))
                            ->setBody($body, 'text/html');
                        // ajout envoi version text
                        $html = new \Html2Text\Html2Text($body);
                        $textVersion = $this->getContainer()->get('translator')->trans('error.configureEmailerForHtml') . $html->getText();
                        $message->addPart($textVersion, 'text/plain');

                        if ($useDkim) {
                            $message->attachSigner($signer);
                        }

                        $attachmentsSize = 0;
                        if ($eventAlert->getAttachments()) {
                            foreach ($eventAlert->getAttachments() as $attachment) {
                                $attachmentsSize += $attachment->getAttachmentSize();                                
                                //dump($attachment->getAttachmentFile()->getFileName().' ('.($attachment->getAttachmentSize()/1000000).' Mo) : taille totale attachement '. ($attachmentsSize / 1000000) .' Mo');
                                if (($attachmentsSize / 1000000) < $maxAttachmentsSize) {
                                    $swiftAttachment = \Swift_Attachment::fromPath($attachment->getAttachmentFile()->getPathName(), $attachment->getAttachmentFile()->getMimeType())->setFilename($attachment->getAttachmentFile()->getFileName());
                                    $message->attach($swiftAttachment);
                                }
                            }
                        }

                        // test si mail correctement envoyé
                        if ($this->getContainer()->get('mailer')->send($message)) {
                            $goodEmails[] = $recipient;
                        } else {
                            $badEmails[] = $recipient;
                        }
                    } else {
                        $badEmails[] = $recipient;
                    }
                }
            }
        }

        return [$goodEmails, $badEmails];
    }

    private function getEventAlertEmailsInfos(EventAlert $eventAlert, $from, $body, $subject)
    {
        $emailInfos = [];
        $event = $eventAlert->getEvent();
        switch ($eventAlert->getRecipients()) {
            case '1': // only organizer
                if ('no-reply@postparc.fr' != $from) {
                    foreach ($event->getOrganizators() as $organizer) {
                        if (method_exists($organizer, 'getScalarInfos')) {
                            $emailInfos[] = $this->injectValueInMailInfos($event, $subject, $body, $organizer->getScalarInfos(), $organizer->getEmail());
                        } else {
                            $emailInfos[] = $this->injectValueInMailInfos($event, $subject, $body, ['object' => $organizer], $organizer->getEmail());
                        }
                    }
                }
                break;
            case '2': // only participants
                $emailInfos[] = $this->getEventParticipantsEmailsInfos($event, $subject, $body, $eventAlert);

                break;
            case '3': // all
                $emailInfos[] = $this->getEventParticipantsEmailsInfos($event, $subject, $body, $eventAlert);

                if ('no-reply@postparc.fr' != $from) {
                    foreach ($event->getOrganizators() as $organizer) {
                        if (method_exists($organizer, 'getScalarInfos')) {
                            $emailInfos[0][] = $this->injectValueInMailInfos($event, $subject, $body, $organizer->getScalarInfos(), $organizer->getEmail());
                        } else {
                            $emailInfos[0][] = $this->injectValueInMailInfos($event, $subject, $body, ['object' => $organizer], $organizer->getEmail());
                        }
                    }
                }
                break;
        }

        if (count($emailInfos) && !array_key_exists('subject', $emailInfos[0])) {
            return $emailInfos[0];
        } else {
            return $emailInfos;
        }
    }

    private function getEventParticipantsEmailsInfos($event, $subject, $body, $eventAlert)
    {
        $emailInfos = [];
        $rgpdMessage = '';
        if ($eventAlert->getAddRGPDMessageForPerson()) {
            $rgpdMessage = $this->getContainer()->get('translator')->trans('sendMailMassifModule.messages.rgpdMessage');
            $routerParams = $this->getContainer()->getParameter('router');
            $host = $routerParams['request_context']['host'];
            $scheme = $routerParams['request_context']['scheme'];
            $base_url = $routerParams['request_context']['base_url'];
            $subdomain = $routerParams['request_context']['subdomain'];
            if (empty($subdomain)) {
                $subdomain = $this->getContainer()->get('kernel')->getEnvironment();
            }
            // special case for mairiechambery
            if('mairiechambery' === $subdomain) {
                $subdomain = 'mairie-chambery';
            }
        }
        $em = $this->getContainer()->get('doctrine')->getManager();
        if($eventAlert->getLimitToRecipiantsList()) {
            $persons = $eventAlert->getEventAlertPersons();
            $pfos = $eventAlert->getEventAlertPfos();
            $representations = $eventAlert->getEventAlertRepresentations();
        } else {
            $persons = $em->getRepository('PostparcBundle:Person')->getEventPersons($event, $eventAlert);
            $pfos = $em->getRepository('PostparcBundle:Pfo')->getEventPfos($event, $eventAlert);
            $representations = $em->getRepository('PostparcBundle:Representation')->getEventRepresentations($event, $eventAlert);
        }
        foreach ($persons as $person) {
            if (!$person->getDontWantToBeContacted()) {
                $bodyForperson = $body;
                if ($eventAlert->getAddRGPDMessageForPerson()) {
                    // create rgpd Link to unsuscribe
                    $router = $this->getContainer()->get('router');
                    $url = $router->generate('rgpd-unsuscribe', ['hash' => $this->encrypt($person->getId())]);
                    $url = $scheme . '://' . $subdomain . '.' . $host . $base_url . $url;
                    $link = '<a href="' . $url . '">' . $url . '</a>';
                    $personRgpdMessage = str_replace('%link%', $link, $rgpdMessage);
                    $bodyForperson .= '<br/><br/><i>' . $personRgpdMessage . '</i><br/><br/>';
                }
                if (count($person->getPreferedEmails()) > 0) {
                    foreach ($person->getPreferedEmails() as $email) {
                        $emailInfos[] = $this->injectValueInMailInfos($event, $subject, $bodyForperson, $person->getScalarInfos(), $email);
                    }
                } elseif ($person->getCoordinate() && $person->getCoordinate()->getEmail()) {
                    $emailInfos[] = $this->injectValueInMailInfos($event, $subject, $bodyForperson, $person->getScalarInfos(), $person->getCoordinate()->getEmail());
                }
            }
        }
        
        foreach ($pfos as $pfo) {
            if (count($pfo->getPreferedEmails()) > 0) {
                foreach ($pfo->getPreferedEmails() as $email) {
                    $emailInfos[] = $this->injectValueInMailInfos($event, $subject, $body, $pfo->getScalarInfos(), $email);
                }
            } elseif ($pfo->getEmail()) {
                $emailInfos[] = $this->injectValueInMailInfos($event, $subject, $body, $pfo->getScalarInfos(), $pfo->getEmail());
            }
        }
        
        foreach ($representations as $rep) {
            if ($rep->getPreferedEmail()) {
                $emailInfos[] = $this->injectValueInMailInfos($event, $subject, $body, $rep->getScalarInfos(), $rep->getPreferedEmail());
            } elseif ($rep->getCoordinate() && $rep->getCoordinate()->getEmail()) {
                $emailInfos[] = $this->injectValueInMailInfos($event, $subject, $body, $rep->getScalarInfos(), $rep->getCoordinate()->getEmail());
            }
        }

        return $emailInfos;
    }

    private function encrypt($string)
    {
        $encrypt_method = 'AES-256-CBC';
        $secret_key = 'D2056E42A433C16EAF88C5612823A0A8';
        $secret_iv = '4CF76A4CE128A8999B198968C214D1F0';
        $output = false;
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);

        return base64_encode($output);
    }

    /**
     * envoi du mail récapitulatif à l'expediteur.
     *
     * @param type $sender
     * @param type $goodEmails
     * @param type $badEmails
     * @param type $eventAlert
     */
    private function sendSummuaryEmail($sender, $goodEmails, $badEmails, $eventAlert)
    {
        // envoi du mail récapitulatif à l'expediteur
        $translator = $this->getContainer()->get('translator');
        $summuarySubject = $translator->trans('EventAlert.messages.summuary.subject');
        $summuaryBody = $translator->trans('hello') . ',<br/>';
        $summuaryBody .= $translator->trans('EventAlert.messages.summuary.bodyStart') . ':<br/>';
        $summuaryBody .= $translator->trans('EventAlert.messages.summuary.eventAssociate') . ': ' . $eventAlert->getEvent() . '<br/>';
        $summuaryBody .= $translator->trans('EventAlert.messages.summuary.nbMailSent') . ': ' . count($goodEmails) . '<br/>';
        $summuaryBody .= $translator->trans('EventAlert.messages.summuary.nbBadEmails') . ': ' . count($badEmails) . '<br/>';
        if (count($goodEmails) > 0) {
            $summuaryBody .= '<br/>' . $translator->trans('EventAlert.messages.summuary.listMailSent') . ': <br/>';
            foreach ($goodEmails as $email) {
                $summuaryBody .= $email . '<br/>';
            }
        }
        if (count($badEmails) > 0) {
            $summuaryBody .= '<br/>' . $translator->trans('EventAlert.messages.summuary.listBadEmails') . ': <br/>';
            foreach ($badEmails as $email) {
                $summuaryBody .= $email . '<br/>';
            }
        }
        $summuaryBody .= '<br/><br/>' . $translator->trans('EventAlert.messages.summuary.no-reply');
        // envoi du message
        $message = \Swift_Message::newInstance()
            ->setSubject($summuarySubject)
            ->setFrom('no-reply@postparc.fr')
            ->setTo($sender)
            ->setBody(
                $summuaryBody,
                'text/html'
            );
        $this->getContainer()->get('mailer')->send($message);
    }

    /**
     * @param type $event
     * @param type $subject
     * @param type $body
     * @param type $scalar
     * @param type $email
     * @param type $representation
     *
     * @return array
     */
    private function injectValueInMailInfos($event, $subject, $body, $scalar, $email, $representation = null)
    {
        $twig = $this->getContainer()->get('twig');
        $globals = $twig->getGlobals();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $availableVariables = $globals['documentTemplate_availableFields'];

        // traitement particulier dans le cas d'une representation
        if ($representation !== null) {
            if ($representation->getOrganization()) {
                $subject = str_replace('[[o_name]]', $representation->getOrganization(), $subject);
                $body = str_replace('[[o_name]]', $representation->getOrganization(), $body);
            }
            if ($representation->getPersonFunction()) {
                $personFunction = $representation->getPersonFunction();
                if ('female' == $representation->getSexe()) {
                    $personFunctionString = $personFunction->getWomenParticle() . ' ' . $personFunction->getWomenName();
                } else {
                    $personFunctionString = $personFunction->getMenParticle() . ' ' . $personFunction->getName();
                }
                $subject = str_replace('[[rep_function]]', $personFunctionString, $subject);
                $body = str_replace('[[rep_function]]', $personFunctionString, $body);
            }
            if ($representation->getMandateType()) {
                $subject = str_replace('[[mt_name]]', $representation->getMandateType(), $subject);
                $body = str_replace('[[mt_name]]', $representation->getMandateType(), $body);
            }
        }

        foreach ($availableVariables as $documentVariable) {
            if (isset($scalar[$documentVariable])) {
                $val = $scalar[$documentVariable];
                $subject = str_replace('[[' . $documentVariable . ']]', $val, $subject);
                $body = str_replace('[[' . $documentVariable . ']]', $val, $body);
            }
        }

        // gestion [[coord_bloc]]
        if ($event->getCoordinate()) {
            $blocCoordinate = $event->getCoordinate()->getFormatedAddress();
            $subject = str_replace('[[coord_bloc]]', $blocCoordinate, $subject);
            $body = str_replace('[[coord_bloc]]', $blocCoordinate, $body);
        }

        // replace [[confirmation_url]]
        $object = $scalar['object'];
        $eventInvitation = null;
        $url = '';

        switch ($object->getClassName()) {
            case 'Person':
                $eventInvitation = $em->getRepository('PostparcBundle:EventPersons')->findOneBy(['person' => $object->getId(), 'event' => $event->getId()]);
                break;
            case 'Pfo':
                $eventInvitation = $em->getRepository('PostparcBundle:EventPfos')->findOneBy(['pfo' => $object->getId(), 'event' => $event->getId()]);
                break;
            case 'Representation':
                $eventInvitation = $em->getRepository('PostparcBundle:EventRepresentations')->findOneBy(['representation' => $object->getId(), 'event' => $event->getId()]);
                break;
            case 'User':
                $email = $object; // special case for entité user
        }
        if ($eventInvitation && !empty($eventInvitation->getConfirmationToken())) {
            $confirmationToken = $eventInvitation->getConfirmationToken();
            $routerParams = $this->getContainer()->getParameter('router');
            $host = $routerParams['request_context']['host'];
            $scheme = $routerParams['request_context']['scheme'];
            $base_url = $routerParams['request_context']['base_url'];
            $subdomain = $routerParams['request_context']['subdomain'];
            if (empty($subdomain)) {
                $subdomain = $this->getContainer()->get('kernel')->getEnvironment();
            }
            // special case for mairiechambery
            if('mairiechambery' === $subdomain) {
                $subdomain = 'mairie-chambery';
            }

            $router = $this->getContainer()->get('router');
            $url = $router->generate('confirmEventPresence', ['confirmationToken' => $object->getClassName() . '_' . $confirmationToken]);
            $url = $scheme . '://' . $subdomain . '.' . $host . $base_url . $url;
            $url = '<a href="' . $url . '">' . $url . '</a>';
        }
        $body = str_replace('[[confirmation_url]]', $url, $body);        
        
        // nettoyage des variables non remplacées
        foreach ($availableVariables as $documentVariable) {
            $subject = str_replace('[[' . $documentVariable . ']]', '', $subject);
            $body = str_replace('[[' . $documentVariable . ']]', '', $body);
        }

        return [
        'subject' => $subject,
        'body' => $body,
        'email' => $email->getEmail(),
        ];
    }
}

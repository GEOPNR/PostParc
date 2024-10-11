<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\MailStats;

/**
 * MailMassiveModuleController controller.
 *
 * @Route("/mailMassiveModule")
 */
class MailMassiveModuleController extends Controller
{
    private $encrypt_method;
    private $secret_key;
    private $secret_iv;
    private $maxAttachmentFileSize;

    public function __construct()
    {
        $this->encrypt_method = 'AES-256-CBC';
        $this->secret_key = 'D2056E42A433C16EAF88C5612823A0A8';
        $this->secret_iv = '4CF76A4CE128A8999B198968C214D1F0';
        $this->maxAttachmentFileSize = 8; // MB
    }
    /**
     * MailMassiveModule index.
     *
     * @param Request $request
     * @Route("/", name="mailMassiveModule_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $emails = [];
        $itemsWhitoutEmail = [];
        $nbItemsWhitoutEmail = 0;
        // nombre de mail a générer
        $nbMailtoBeGenerate = 0;
        $selectionDataEmailsDetails = [];

        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            $selectionDataEmailsDetails = [];
            // récupération des emails
            $emails = $em->getRepository('PostparcBundle:Email')->getSelectionEmails($selectionData);
            // récupération des persons sans emails
            if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
                $nbMailtoBeGenerate += count($selectionData['personIds']);
                $personWithoutEmails = $em->getRepository('PostparcBundle:Person')->getPersonWithoutEmails($selectionData['personIds']);
                if ($personWithoutEmails) {
                    $itemsWhitoutEmail['persons'] = $personWithoutEmails;
                    $nbItemsWhitoutEmail += count($personWithoutEmails);
                    $nbMailtoBeGenerate -= count($personWithoutEmails);
                }
                foreach ($selectionData['personIds'] as $personId) {
                    $person = $em->getRepository('PostparcBundle:Person')->find($personId);
                    if ($person && count($person->getEmailsArray())) {
                        $selectionDataEmailsDetails['persons'][$personId] = [
                                'label' => $person->__toString(),
                                'emails'=>$person->getEmailsArray()
                        ];
                    }
                }
            }
            // récupération des organizations sans emails
            if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
                $nbMailtoBeGenerate += count($selectionData['organizationIds']);
                $organizationWithoutEmails = $em->getRepository('PostparcBundle:Organization')->getOrganizationWithoutEmails($selectionData['organizationIds']);
                if ($organizationWithoutEmails) {
                    $itemsWhitoutEmail['organizations'] = $organizationWithoutEmails;
                    $nbItemsWhitoutEmail += count($organizationWithoutEmails);
                    $nbMailtoBeGenerate -= count($organizationWithoutEmails);
                }
                foreach ($selectionData['organizationIds'] as $organizationId) {
                    $organization = $em->getRepository('PostparcBundle:Organization')->find($organizationId);
                    if ($organization && $organization->getCoordinate() && $organization->getCoordinate()->getEmail()) {
                        $selectionDataEmailsDetails['organizations'][$organizationId] = [
                            'label' => $organization->__toString(),
                            'emails'=>[$organization->getCoordinate()->getEmail()->__toString()]
                        ];
                    }
                }
            }
            // récupération des pfos sans emails
            if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
                $nbMailtoBeGenerate += count($selectionData['pfoIds']);
                $pfoWithoutEmails = $em->getRepository('PostparcBundle:Pfo')->getPfoWithoutEmails($selectionData['pfoIds']);
                if ($pfoWithoutEmails) {
                    $itemsWhitoutEmail['pfos'] = $pfoWithoutEmails;
                    $nbItemsWhitoutEmail += count($pfoWithoutEmails);
                    $nbMailtoBeGenerate -= count($pfoWithoutEmails);
                }
                foreach ($selectionData['pfoIds'] as $pfoId) {
                    $pfo = $em->getRepository('PostparcBundle:Pfo')->find($pfoId);
                    if ($pfo && count($pfo->getEmailsArray())) {
                        $selectionDataEmailsDetails['pfos'][$pfoId] = [
                            'label' => $pfo->__toString(),
                            'emails'=>$pfo->getEmailsArray()
                        ];
                    }
                }
            }
            // representations
            if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
                $nbMailtoBeGenerate += count($selectionData['representationIds']);
                foreach ($selectionData['representationIds'] as $representationId) {
                    $representation = $em->getRepository('PostparcBundle:Representation')->find($representationId);
                    if ($representation->getPerson() && count($representation->getPerson()->getEmailsArray())) {
                        $selectionDataEmailsDetails['representations'][$representationId] = [
                            'label' => $representation->__toString(),
                            'emails'=>$representation->getPerson()->getEmailsArray()
                        ];
                    }
                    if ($representation->getPfo() && count($representation->getPfo()->getEmailsArray())) {
                        $selectionDataEmailsDetails['representations'][$representationId] = [
                            'label' => $representation->__toString(),
                            'emails'=>$representation->getPfo()->getEmailsArray()
                        ];
                    }
                    // aucun mail trouvé on recupère le mail de l'organisme associé à la représentation
                    if ($representation->getOrganization() && !array_key_exists('representations', $selectionDataEmailsDetails) && count($representation->getOrganization()->getEmailsArray())) {
                        $selectionDataEmailsDetails['representations'][$representationId] = [
                            'label' => $representation->__toString(),
                            'emails'=>$representation->getOrganization()->getEmailsArray()
                        ];
                    }
                }
            }
        }
        $emails = array_unique($emails);
        $nbEmails = count($emails);
        // recupération du quota par mois:
        $quotaExceeded = false;
        $consumptionInfos = $this->getComsuptionInfo($request);
        if ($consumptionInfos['quota'] < ($consumptionInfos['nbEmail'] + $nbEmails)) {
            $request->getSession()
              ->getFlashBag()
              ->add('error', 'flash.massiveSendMailQuotaExceeded');
            $quotaExceeded = true;
        }

        // récupération des modèles de document
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $documentTemplates = $em->getRepository('PostparcBundle:DocumentTemplate')->getActiveDocumentTemplates($entityId, $show_SharedContents, true, $this->getUser());

        // récupération des signatures de mail liées à l'utilisateur courant
        $mailFooters = $em->getRepository('PostparcBundle:MailFooter')->findBy(['user' => $user], ['name' => 'desc']);

        // mise en place expediteur
        $noreplyEmails =  $this->getParameter('noreplyEmails');
        $senderEmail = $noreplyEmails[0];
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $userEmailArray = explode('@', $user->getEmail());
            $senderEmail = $userEmailArray[0];
        }

        // gestion champ replyToContent
        $replyToContent = '';      
        if(!array_key_exists('emptySpecificMessageField',$currentEntityConfig) || !$currentEntityConfig['emptySpecificMessageField']) {
            $senderFromEmail = $noreplyEmails[0];
            $replyToContent = '<br/><br/><i>' . str_replace(['%sender%','%replyMail%'], [$senderFromEmail, '<a href="mailto:' . $this->getUser()->getEmail() . '">' . $this->getUser()->getEmail() . '</a>'], $this->get('translator')->trans('sendMailMassifModule.messages.senderReplyInfo')) . '</i>';
        } 

        $form = $this->createForm('PostparcBundle\Form\MailMassifModuleType');

        return $this->render('mailMassiveModule/index.html.twig', [
              'emails' => $emails,
              'itemsWhitoutEmail' => $itemsWhitoutEmail,
              'nbItemsWhitoutEmail' => $nbItemsWhitoutEmail,
              'documentTemplates' => $documentTemplates,
              'mailFooters' => $mailFooters,
              'quotaExceeded' => $quotaExceeded,
              'consumptionInfos' => $consumptionInfos,
              'nbMailtoBeGenerate' => $nbMailtoBeGenerate,
              'senderEmail' => $senderEmail,
              'noreplyEmails' => $noreplyEmails,
              'form' => $form->createView(),
              'maxAttachmentFileSize' => $this->maxAttachmentFileSize,
              'selectionDataEmailsDetails' => $selectionDataEmailsDetails,
              'replyToContent' => $replyToContent,
        ]);
    }

    /**
     * MailMassiveModule process.
     *
     * @param Request $request
     * @Route("/process", name="mailMassiveModule_process", methods="GET|POST")
     *
     * @return Response
     */
    public function processAction(Request $request)
    {

        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        $twig = $this->container->get('twig');
        $globals = $twig->getGlobals();
        $availableVariables = $globals['documentTemplate_availableFields'];
        $emailInfos = [];
        $usedEmail = [];
        
        // contrôle sur les pièces jointes
        $attachments = [];
        $attachmentsFileSize = 0;
        $globalAttachments = $request->files->get('mail_massif_module');
        if(is_array($globalAttachments) && array_key_exists('attachments', $globalAttachments)){
            foreach ($globalAttachments['attachments'] as $attachmentInfos){
                $attachment = $attachmentInfos['attachmentFile']['file'];
                if($attachment) {
                    $attachments[] = $attachment;
                    $attachmentsFileSize += $attachment->getClientSize();
                }
            }
            if ($attachmentsFileSize / (1024 * 1024) > $this->maxAttachmentFileSize) {
                $request->getSession()
                  ->getFlashBag()
                  ->add('error', 'flash.maxAttachmentFileSize');
                return $this->redirect($this->generateUrl('mailMassiveModule_index', []));
            }
        }


        //$sender = $request->request->get('sender'); // adresse email indiquée dans champ Email expéditeur avant le @
        $senderName = $request->request->get('senderName'); // nom de l'expéditeur
        $mailDomain = $request->request->get('mailDomain'); // domaine du mail format @XXX.XXX
        $subject = $request->request->get('mail_massif_module')['subject'];
        $body = $request->request->get('mail_massif_module')['body'];
        //$body = preg_replace('/[^(\x20-\x7F)]*/','',$body);
        //$body = mb_convert_encoding($body,'UTF-8', 'UTF-8'); 
        $emails = $request->request->get('emails');
        $replyToContent = $request->request->has('replyToContent')?$request->request->get('replyToContent'):'';
        $noreplyEmail = $request->request->get('noreplyEmail');

        // ajout baseUrl pour les nettoyage des urls des images
        $baseUrl = $request->getScheme() . '://' . $request->getHost() . $request->getBaseUrl();
        $body = str_replace('src="/uploads/documentTemplateImages', 'src="' . $baseUrl . '/uploads/documentTemplateImages', $body);
        $originBody = $body;

        $requestingAReadReceipt = isset($request->request->get('mail_massif_module')['requestingAReadReceipt']) ? $request->request->get('mail_massif_module')['requestingAReadReceipt'] : null;
        $mailFooterId = $request->request->has('mailFooterId') ? $request->request->get('mailFooterId') : null;
        $sendMeCopyOfMail = $request->request->has('sendMeCopyOfMail') ? $request->request->get('sendMeCopyOfMail') : null;
        $deleteDuplicateEmails = $request->request->has('deleteDuplicateEmails') ? $request->request->get('deleteDuplicateEmails') : false;
        $selectionData = $session->get('selection');
        
        
        $domain = $request->getScheme() . '://' . $request->getHttpHost();
        $addRGPDMessageForPerson = $request->request->get('addRGPDMessageForPerson');

        // modification body and subject
        // persons
        if (isset($emails['persons']) && count($emails['persons'])) {
            if ($addRGPDMessageForPerson) {
                $rgpdMessage = $this->get('translator')->trans('sendMailMassifModule.messages.rgpdMessage');
            }
            $persons = $em->getRepository('PostparcBundle:Person')->getListForMassiveDocumentGeneration(array_keys($emails['persons']));

            foreach ($persons as $person) {
                // recherche email
                $personObject = $em->getRepository('PostparcBundle:Person')->find($person['p_id']);
                $bodyForperson = $body;
                if ($addRGPDMessageForPerson) {
                    // create rgpd Link to unsuscribe
                    $url = $domain . $this->generateUrl('rgpd-unsuscribe', ['hash' => $this->encrypt($person['p_id'])]);
                    $link = '<a href="' . $url . '">' . $url . '</a>';
                    $personRgpdMessage = str_replace('%link%', $link, $rgpdMessage);
                    $bodyForperson .= '<br/><br/><i>' . $personRgpdMessage . '</i><br/><br/>';
                }
                if (array_key_exists($person['p_id'], $emails['persons'])) {
                    foreach (explode(';', $emails['persons'][$person['p_id']])  as $email) {
                        $emailInfos[] = $this->injectValueInDocument($subject, $bodyForperson, $person, $availableVariables, $email);
                        $usedEmail[] = $email;
                    }
                }
            }
        }
        // organizations
        if (isset($emails['organizations']) && count($emails['organizations'])) {
            $organizations = $em->getRepository('PostparcBundle:Organization')->getListForMassiveDocumentGeneration(array_keys($emails['organizations']));
            foreach ($organizations as $organization) {
                $organizationObject = $em->getRepository('PostparcBundle:Organization')->find($organization['o_id']);
                if (array_key_exists($organization['o_id'], $emails['organizations'])) {
                    foreach (explode(';', $emails['organizations'][$organization['o_id']])  as $email) {
                        $emailInfos[] = $this->injectValueInDocument($subject, $body, $organization, $availableVariables, $email);
                        $usedEmail[] = $email;
                    }
                }
            }
        }
        // representations
        if (isset($emails['representations']) && count($emails['representations'])) {
            $representations = $em->getRepository('PostparcBundle:Representation')->getListForSelection(array_keys($emails['representations']))->getResult();
            foreach ($representations as $representation) {
                if ($representation->getPerson()) {
                    $persons = $em->getRepository('PostparcBundle:Person')->getListForMassiveDocumentGeneration([$representation->getPerson()->getId()]);
                    if (array_key_exists($representation->getId(), $emails['representations'])) {
                        foreach (explode(';', $emails['representations'][$representation->getId()])  as $email) {
                            $emailInfos[] = $this->injectValueInDocument($subject, $body, $person, $availableVariables, $email);
                            $usedEmail[] = $email;
                        }
                    }
                } elseif ($representation->getPfo()) {
                    $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForMassiveDocumentGeneration($representation->getPfo()->getId());
                    foreach ($pfos as $pfo) {
                        $pfoObject = $em->getRepository('PostparcBundle:Pfo')->find($pfo['pfo_id']);
                        if (array_key_exists($representation->getId(), $emails['representations'])) {
                            foreach (explode(';', $emails['representations'][$representation->getId()])  as $email) {
                                $emailInfos[] = $this->injectValueInDocument($subject, $body, $pfo, $availableVariables, $email);
                                $usedEmail[] = $email;
                            }
                        }
                    }
                }
            }
        }
        // pfos
        if (isset($emails['pfos']) && count($emails['pfos'])) {
            $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForMassiveDocumentGeneration(array_keys($emails['pfos']));
            foreach ($pfos as $pfo) {
                $pfoObject = $em->getRepository('PostparcBundle:Pfo')->find($pfo['pfo_id']);
                if (array_key_exists($pfoObject->getId(), $emails['pfos'])) {
                    foreach (explode(';', $emails['pfos'][$pfo['pfo_id']])  as $email) {
                        $emailInfos[] = $this->injectValueInDocument($subject, $body, $pfo, $availableVariables, $email);
                        $usedEmail[] = $email;
                    }
                }
            }
        }

        $usedEmail = array_unique($usedEmail);

        // recupération du quota par mois:
        $consumptionInfos = $this->getComsuptionInfo($request);
        if ($consumptionInfos['quota'] < ($consumptionInfos['nbEmail'] + count($emailInfos))) {
            $request->getSession()
              ->getFlashBag()
              ->add('error', 'flash.massiveSendMailQuotaExceeded');

            return $this->redirect($this->generateUrl('send_email_massif', []));
        }

        // init default values
        $host = $request->server->get('HTTP_HOST');
        $badEmails = [];
        $goodEmails = [];
        $sender = $noreplyEmail;
//        $senderFromEmail = "no-reply@" . $host;
        $senderFrom = $noreplyEmail;
        $senderFromEmail = $noreplyEmail;
        $senderReplyTo = $noreplyEmail;
        if ($senderName) {
            $senderReplyTo = [$noreplyEmail => $senderName];
            $senderFrom = [$noreplyEmail => $senderName];
        }
        // mail footer
        $mailFooterString = '';
        if ($mailFooterId) {
            $mailFooter = $em->getRepository('PostparcBundle:MailFooter')->find($mailFooterId);
            $mailFooterString = '<br/><br/>' . $mailFooter->getFooter();
            $mailFooterString = str_replace('src="/uploads/documentTemplateImages', 'src="' . $baseUrl . '/uploads/documentTemplateImages', $mailFooterString);
        }
        // ajout infos expéditeur depuis texte présent dans champ replyToContent
        if (strlen(strip_tags($replyToContent)) !== 0) {
            $mailFooterString .= $replyToContent;
        }

        // Matomo Tracker
        $piwikParams = $this->getParameter('piwik');
        $env = $this->container->get('kernel')->getEnvironment();
        $token = 'newsletter_' . $env . '-' . uniqid();
        $tracker = '<!-- Matomo Image Tracker-->'
            . '<img referrerpolicy="no-referrer-when-downgrade" src="https://stats.probesys.com/matomo.php?idsite='.$piwikParams['piwikStatsMailId'].'&rec=1&bots=1&url=https%3A%2F%2F' . $host . '%2Femail-opened%2F' . $token . '&action_name=email-opened-'.$token.'&_rcn=' . $token . '&_rck=' . $token . '&mtm_keyword=' . $token . '" style="border:0;” alt="" />'
            . '<!-- End Matomo -->';

        // AJOUT DKIM Signer
        $dkimParams = $this->getParameter('dkim');
        $useDkim = $dkimParams['use_dkim'];
        if ($useDkim) {
            $domain = $dkimParams['domain'];
            $selector = $dkimParams['selector'];
            $privateKey = file_get_contents($this->get('kernel')->getRootDir() . '/../' . $dkimParams['private_key_path']);
            $signer = new \Swift_Signers_DKIMSigner($privateKey, $domain, $selector);
        }

        // process sending emails
        foreach ($emailInfos as $emailInfo) {
            // construct mail body
            $body = $emailInfo['body'] . $mailFooterString;
            $body = str_replace('../uploads', $domain . '/uploads', $body); // traitement special pour les images dont le chemin est en ../../uploads
            $body .= $tracker; // Matomo Image Tracker

            $subject = $emailInfo['subject'];
            // remove empty or false values from $recipients array
            $recipients = array_filter(explode(';', str_replace([' ',','], ['',';'], $emailInfo['email'])));

            foreach ($recipients as $recipient) {
                if (!$deleteDuplicateEmails || ($deleteDuplicateEmails && !in_array($recipient, $goodEmails) && !in_array($recipient, $badEmails))) {
                    if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                        $message = $useDkim ? \Swift_SignedMessage::newInstance() : \Swift_Message::newInstance();
                        $message
                            ->setSubject($subject)
                            ->setFrom($senderFrom)
                            //->setReplyTo($senderReplyTo)
                            ->setTo(trim($recipient))
                            ->setBody($body, 'text/html')
                                ;
                        if ($useDkim) {
                            $message->attachSigner($signer);
                        }

                        // ajout envoi version text
                        $html = new \Html2Text\Html2Text($body);
                        $textVersion = $html->getText().$this->get('translator')->trans('error.configureEmailerForHtml');
                        $message->addPart($textVersion, 'text/plain');

                        foreach ($attachments as $attachment) {
                            $swiftAttachment = \Swift_Attachment::fromPath($attachment->getRealPath(), $attachment->getClientMimeType())->setFilename($attachment->getClientOriginalName());
                            $message->attach($swiftAttachment);
                        }
                        if ($requestingAReadReceipt) {
                            $message->setReadReceiptTo($sender);
                            $message->setReturnPath($senderFromEmail);
                        }
                        // test si mail correctement envoyé
                        if ($this->get('mailer')->send($message)) {
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
        if (($goodEmails !== []) > 0) {
            $request->getSession()
              ->getFlashBag()
              ->add('success', count($goodEmails) . ' ' . $this->get('translator')->trans('flash.massiveSendMailSuccess'));
        } else {
            $request->getSession()
              ->getFlashBag()
              ->add('error', 'flash.massiveSendMailFailureEmpty');
        }
        if (($badEmails !== []) > 0) {
            $request->getSession()
              ->getFlashBag()
              ->add('warning', '<strong>' . $this->get('translator')->trans('flash.massiveSendMailFailureBadEmails') . ' : </strong><br/>' . implode(' ', $badEmails));
        }

        // gestion envoi copie mail et récapitulatif envoi
        $cc = $this->getUser()->getEmail();
        // send copy email to sender
        if ($sendMeCopyOfMail && $message) {
            $messageCopy = clone $message;
            $messageCopy->setTo($cc);
            $this->get('mailer')->send($messageCopy);
        }
        // envoi du mail récapitulatif à l'expediteur
        $this->sendSummuaryEmail($senderFrom, $sender, $cc, $goodEmails, $badEmails, $subject, $originBody);
        
        // ecriture dans la table email_stat
        if (($goodEmails !== []) > 0) {
            //dump($originBody); die;
            $mailStat = new MailStats();
            $now = new \DateTime();
            $mailStat->setDate($now);
            $mailStat->setSubject($subject);
            $mailStat->setBody($originBody);
            $mailStat->setNbEmail(count($goodEmails));
            $mailStat->setSender($sender);
            $mailStat->setCreatedBy($this->getUser());
            $mailStat->setRecipientEmails($goodEmails);
            $mailStat->setRejectedEmails($badEmails);
            $mailStat->setToken($token);
            $em->persist($mailStat);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('send_email_massif', []));
    }

    /**
     * envoi du mail récapitulatif à l'expediteur.
     *
     * @param type $sender
     * @param type $goodEmails
     * @param type $badEmails
     */
    private function sendSummuaryEmail($sender,$to,  $cc, $goodEmails, $badEmails, $subject, $body)
    {
        // envoi du mail récapitulatif à l'expediteur
        $sendMailMassifSummuarySubject = $this->get('translator')->trans('sendMailMassifModule.messages.summuary.subject');
        $sendMailMassifSummuaryBody = $this->get('translator')->trans('hello') . ',<br/>';
        $sendMailMassifSummuaryBody .= $this->get('translator')->trans('sendMailMassifModule.messages.summuary.bodyStart') . ':<br/>';
        $sendMailMassifSummuaryBody .= $this->get('translator')->trans('sendMailMassifModule.fields.subject') . ': ' . $subject . '<br/>';
        $sendMailMassifSummuaryBody .= $this->get('translator')->trans('sendMailMassifModule.fields.body') . ': ' . $body . '<br/><br/>';
        $sendMailMassifSummuaryBody .= $this->get('translator')->trans('sendMailMassifModule.messages.summuary.nbMailSent') . ': ' . count($goodEmails) . '<br/>';
        $sendMailMassifSummuaryBody .= $this->get('translator')->trans('sendMailMassifModule.messages.summuary.nbBadEmails') . ': ' . count($badEmails) . '<br/>';
        if (count($goodEmails) > 0) {
            $sendMailMassifSummuaryBody .= '<br/>' . $this->get('translator')->trans('sendMailMassifModule.messages.summuary.listMailSent') . ': <br/>';
            foreach ($goodEmails as $email) {
                $sendMailMassifSummuaryBody .= $email . '<br/>';
            }
        }
        if (count($badEmails) > 0) {
            $sendMailMassifSummuaryBody .= '<br/>' . $this->get('translator')->trans('sendMailMassifModule.messages.summuary.listBadEmails') . ': <br/>';
            foreach ($badEmails as $email) {
                $sendMailMassifSummuaryBody .= $email . '<br/>';
            }
        }
        $sendMailMassifSummuaryBody .= '<br/><br/>' . $this->get('translator')->trans('sendMailMassifModule.messages.summuary.no-reply');
        // envoi du message
        $message = \Swift_Message::newInstance()
            ->setSubject($sendMailMassifSummuarySubject)
            ->setFrom($sender)
            ->setTo($to)
            ->setBody(
                $sendMailMassifSummuaryBody,
                'text/html'
            );
        // si expediteur != email user connecté, envoi du rapport également à l'utilisateur connecté
        if($sender!=$cc){
            $message->setCc($cc);
        }
        $this->get('mailer')->send($message);
    }

    /**
     * @param type $subject
     * @param type $body
     * @param type $scalar
     * @param type $availableVariables
     * @param type $email
     *
     * @return array
     */
    private function injectValueInDocument($subject, $body, $scalar, $availableVariables, $email, $representation = null)
    {
        // traitement particulier dans le cas d'une representation
        if ($representation) {
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
        
        // nettoyage des variables non remplacées
        foreach ($availableVariables as $documentVariable) {
            $subject = str_replace('[[' . $documentVariable . ']]', '', $subject);
            $body = str_replace('[[' . $documentVariable . ']]', '', $body);
        }

        return [
        'subject' => $subject,
        'body' => $body,
        'email' => $email,
        ];
    }

    /**
     * get mail comsuption info.
     *
     * @param Request $request
     *
     * @return type
     */
    private function getComsuptionInfo(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        // recuperation du user courant
        $entityID = null;
        $user = $this->getUser();
        if (!$user->hasRole('ROLE_SUPER_ADMIN')) {
            $entityID = $user->getEntity()->getId();
        }

        $massiveMailInfos = $request->getSession()->get('currentEntityConfig');
        $quota = $massiveMailInfos['max_email_per_month'];
        $consumption = $em->getRepository('PostparcBundle:MailStats')->getComsuptionForCurrentMonth($entityID);
        $percentMail = round($consumption['nbEmail'] * 100 / $quota);

        return [
            'quota' => $quota,
            'nbEmail' => $consumption['nbEmail'],
            'attachmentsSize' => $consumption['attachmentsSize'],
            'percentMail' => $percentMail,
          ];
    }

    private function encrypt($string)
    {
        $output = false;
        $key = hash('sha256', $this->secret_key);
        $iv = substr(hash('sha256', $this->secret_iv), 0, 16);
        $output = openssl_encrypt($string, $this->encrypt_method, $key, 0, $iv);

        return base64_encode($output);
    }
}

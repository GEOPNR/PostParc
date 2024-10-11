<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Psr\Log\LoggerInterface;

/**
 * SendInBlue controller.
 *
 * @Route("/sendInBlue")
 */
class SendInBlueController extends Controller
{
    /**
     * generate select with sendinBlue lists
     *
     * @param Request $request
     * @Route("/sendSelectionToSendInBlue", name="sendinBlue_sendSelection_page", methods="GET|POST")
     *
     * @return Response
     */
    public function sendSelectionPageAction(Request $request)
    {
        $this->loadSendInBlueConfiguration($request);
        $lists = $this->getLists();
        $result = [];
        //$smtpTemplates = $this->getSmtpTemplates();
        if ($request->request->has('sendInBlueListsSelector') || $request->request->has('newlistName')) {
            $newlistName = $request->request->get('newlistName');
            $listId = $request->request->get('sendInBlueListsSelector');
            if ($newlistName) {
                // create new list
                $data = ['name' => $newlistName, 'folderId' => 1];
                $newListResult = $this->createList($data);
                $listId = $newListResult['id'];
            }
            if ($listId) {
                $result = $this->addEmailsSelectionToSendinBlueList($listId, $request);
            }
        }

        return $this->render('sendInblue/index.html.twig', [
                  'lists' => $lists,
                  'result' => $result
        ]);
    }

    /**
     * generate select with sendinBlue lists
     *
     * @param Request $request
     * @Route("/{listId}/addSelectionEmailsToSendInBlueList", name="sendinBlue_addEmailsSelectionToList", methods="GET")
     *
     * @return Response
     */
    public function addEmailsSelectionToSendinBlueList($listId, Request $request)
    {
        $this->loadSendInBlueConfiguration($request);
        $apiContactInstance = $this->get('sendinblue_client_ContactsApi');
        $apiListInstance = $this->get('sendinblue_client_ListsApi');
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('logger');
        $existingEmails = [];
        $newEmails = [];
        $nonSendedEmails = [];
        $log = true;

        // récupération des contacts dans sendinblue ( tous )
        $sendInBlueEmails = $this->getContactArraysEmails($apiContactInstance);

        // récupération des contacts déjà associés à la liste
        $alreadyassociateEmails = $this->getContactsFromList($listId, $apiListInstance);

        $session = $request->getSession();
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            $contactEmails = $em->getRepository('PostparcBundle:Email')->getSelectionEmails($selectionData);
            //$contactsInfos = $em->getRepository('PostparcBundle:Email')->getSelectionDetailledCoordinates($selectionData);
            // suppression des emails déjà associées
            $contactEmailsNotAlreadyAssociate = array_diff($contactEmails, $alreadyassociateEmails);
            // test si le contact existe dans sendinblue
            foreach ($contactEmailsNotAlreadyAssociate as $email) {
                $email = trim(strtolower($email));
                if (in_array($email, $sendInBlueEmails)) {
                    // contact already exist in sendinblue but not in your list
                    $existingEmails[] = $email;
                } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email)) {
                    // contact not already exist in sendinblue, we must add it before
                    if ($this->createContact($email, $apiContactInstance)) {
                        $newEmails[] = $email;
                    }
                } else {
                    $nonSendedEmails[] = $email;
                }
            }

            // merge the two tab
            $allContactEmailsToBeAssociate = array_merge($existingEmails, $newEmails);

            // affectation des emails deja existants
            if (($allContactEmailsToBeAssociate !== []) > 0) {
                $maxElementsByArray = 150;
                $nbWaitingSeconds = 2;

                if ($log) {
                    $logger->error('********************************************************************************************');
                    $logger->error('liste des emails déjà présents dans sendinblue devant être associés à la liste : ' . json_encode($existingEmails));
                    $logger->error('********************************************************************************************');
                    $logger->error('liste des emails non présents dans sendinblue devant être ajoutés au préalable : ' . json_encode($newEmails));
                    $logger->error('********************************************************************************************');
                }

                $contactEmailsChunked = array_chunk($allContactEmailsToBeAssociate, $maxElementsByArray);
                foreach ($contactEmailsChunked as $key => $emails) {
                    if ($key) {
                        if ($log) {
                            $logger->error('waiting ' . $nbWaitingSeconds . 's');
                        }
                        sleep($nbWaitingSeconds);
                    }
                    if ($log) {
                        $logger->error('****************************  addEmailsSelectionToSendinBlueList  *************************');
                        $logger->error('                            ');
                        $logger->error('call sendinblue api to add contact to list ' . $listId);
                        $logger->error('contactEmails: ' . json_encode($emails));
                        $logger->error('Nb Emails in array : ' . count($emails));
                        $logger->error('                            ');
                        $logger->error('********************************************************************************************');
                    }
                    $this->addContactToList($listId, $emails, $apiListInstance);
                }
                $request->getSession()
                        ->getFlashBag()
                        ->add('success', 'SendInBlue.flash.exportToSendInBlueSuccess');
            } else {
                $request->getSession()
                        ->getFlashBag()
                        ->add('error', 'SendInBlue.flash.exportToSendInBlueEmpty');
            }
        } else {
            $request->getSession()
                    ->getFlashBag()
                    ->add('error', 'SendInBlue.flash.exportToSendInBlueEmpty');
        }

        return [
          'contactEmails' => $contactEmails,
          'alreadyassociateEmails' => $alreadyassociateEmails,
          'existingEmails' => $existingEmails,
          'newEmails' => $newEmails,
          'nonSendedEmails' => $nonSendedEmails,
        ];
    }

    /*
     * si multi-instance, appel de l'api en direct sans passer par le bundle sendinblue api_bundle mais juste par api-v3-sdk
     * infos sur les méthodes disponibles : https://github.com/sendinblue/APIv3-php-library
     * Sinon utilisation du plugin sendinblue
     * infos sur les méthodes disponibles : https://github.com/sendinblue/APIv3-symfony-bundle
     */

    private function loadSendInBlueConfiguration(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->getUser()->getEntity();
        $entityID = null;
        if (false == $this->container->getParameter('isMultiInstance') && $request->query->has('entityID') && $this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
            $entityID = $request->query->get('entityID');
            $entity = $em->getRepository('PostparcBundle:Entity')->find($entityID);
        }
        $configs = $entity->getConfigs();
        $sendInBlueApiKey = trim($configs['sendInBlue_apiKey']);
//    $sendInBlueApiKey = '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';
//    $sendInBlueApiKey = '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';

        $sendinblueconfig = $this->get('sendinblueapi_configuration');
        $sendinblueconfig->getDefaultConfiguration()->setApiKey('api-key', $sendInBlueApiKey);
    }

    private function callApiMethod($class, $method, $data = [])
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $api_instance = $this->get('sendinblue_client_' . $class);
        try {
            $result = $propertyAccessor->getValue($api_instance, $method);
        } catch (Exception $e) {
            echo 'Exception when calling ' . $class . '->' . $method . ': ', $e->getMessage(), PHP_EOL;
        }
        return $result;
    }

    private function getLists()
    {
        return $this->callApiMethod('ListsApi', 'getLists');
    }

    private function getContacts()
    {
        return $this->callApiMethod('ContactsApi', 'getContacts');
    }

    private function createList($data)
    {
        $apiInstance = $this->get('sendinblue_client_ListsApi');
        try {
            $result = $apiInstance->createList($data);
        } catch (Exception $e) {
            echo 'Exception when calling ListsApi->createList : ', $e->getMessage(), PHP_EOL;
        }
        //$result = $this->callApiMethod('ListsApi', 'createList', $data);
        return $result;
    }

    private function addContactToList($listId, $contactEmails, $apiInstance = false)
    {
        if (!$apiInstance) {
            $apiInstance = $this->get('sendinblue_client_ListsApi');
        }

        $return = true;
        try {
            $result = $apiInstance->addContactToList($listId, ['emails' => $contactEmails]);
        } catch (Exception $e) {
            echo 'Exception when calling ListsApi->createList : ', $e->getMessage(), PHP_EOL;
            $return = false;
        } finally {
            return $return;
        }


        return $result;
    }

    private function getContactArraysEmails($apiInstance = false)
    {
        if (!$apiInstance) {
            $apiInstance = $this->get('sendinblue_client_ListsApi');
        }
        $contactsEmails = [];
        try {
            // first call to known the number of contacts
            $perPage = 250;
            $offset = 0;
            $result = $apiInstance->getContacts($perPage, $offset);
            $nbResult = count($result['contacts']);
            $nbTotal = $result['count'];
            foreach ($result['contacts'] as $contact) {
                $contactsEmails[] =  $contact['email'];
            }

            while ($nbResult < $nbTotal) {
                $offset += 1;
                $result = $apiInstance->getContacts($perPage, $offset);
                $nbResult += count($result['contacts']);
                foreach ($result['contacts'] as $contact) {
                    $contactsEmails[] =  $contact['email'];
                }
            }
            array_unique($contactsEmails);
        } catch (Exception $e) {
            echo 'Exception when calling ContactsApi->getContactInfo : ', $e->getMessage(), PHP_EOL;
        } finally {
            return $contactsEmails;
        }

        return $contactsEmails;
    }

    private function getContactInfo($email)
    {
        $apiInstance = $this->get('sendinblue_client_ContactsApi');
        try {
            $result = $apiInstance->getContactInfo($email);
        } catch (Exception $e) {
            echo 'Exception when calling ContactsApi->getContactInfo : ', $e->getMessage(), PHP_EOL;
        }

        return $result;
    }

    private function getContactsFromList($listId, $apiInstance = false)
    {
        if (!$apiInstance) {
            $apiInstance = $this->get('sendinblue_client_ContactsApi');
        }
        $contactsEmails = [];
        try {
            $perPage = 200;
            $modifiedSince = new \DateTime("2013-10-20T19:20:30+01:00");
            $offset = 0;
            $result = $apiInstance->getContactsFromList($listId, $modifiedSince, $perPage, $offset);
            $nbResult = count($result['contacts']);
            $nbTotal = $result['count'];
            foreach ($result['contacts'] as $contact) {
                $contactsEmails[] = $contact['email'];
            }
            while ($nbResult < $nbTotal) {
                $offset += 1;
                $result = $apiInstance->getContactsFromList($listId, $modifiedSince, $perPage, $offset);
                $nbResult += count($result['contacts']);
                foreach ($result['contacts'] as $contact) {
                    $contactsEmails[] = $contact['email'];
                }
            }
        } catch (Exception $e) {
            echo 'Exception when calling ContactsApi->getContactsFromList : ', $e->getMessage(), PHP_EOL;
        }

        return $contactsEmails;
    }

    private function createContact($email, $apiInstance = false)
    {
        if (!$apiInstance) {
            $apiInstance = $this->get('sendinblue_client_ContactsApi');
        }
        $return = true;
        try {
            $createcontact = new \SendinBlue\Client\Model\CreateContact();
            $createcontact->setEmail($email);
            $result = $apiInstance->createContact($createcontact);
        } catch (Exception $e) {
            echo 'Exception when calling ContactsApi->getContactInfo : ', $e->getMessage(), PHP_EOL;
            $return = false;
        } finally {
            return $return;
        }

        return $result;
    }

    private function importContactsToList($listId, $contactEmails, $apiInstance = false)
    {
        if (!$apiInstance) {
            $apiInstance = $this->get('sendinblue_client_ContactsApi');
        }

        try {
            $tabListId[] = $listId;
            $strEmail = implode(";", $contactEmails);
            $requestContactImport = new \SendinBlue\Client\Model\RequestContactImport();

            $requestContactImport->setFileBody($strEmail);
            if ($listId) {
                $requestContactImport->setListIds($tabListId);
            }
            $result = $apiInstance->importContacts($requestContactImport);
        } catch (Exception $e) {
            echo 'Exception when calling ListsApi->createList : ', $e->getMessage(), PHP_EOL;
        }

        return $result;
    }

    private function getSmtpTemplates()
    {
        $apiInstance = $this->get('sendinblue_client_SMTPApi');
        $templateStatus = 'true';
        $limit = 100;
        $offset = 0;
        try {
            $result = $apiInstance->getSmtpTemplates($templateStatus, $limit, $offset);
        } catch (Exception $e) {
            echo 'Exception when calling ListsApi->createList : ', $e->getMessage(), PHP_EOL;
        }

        return $result;
    }

    // ******************   UNUSED METHODS  ***********************************


    private function getEmailCampaigns()
    {
        return $this->callApiMethod('EmailCampaignsApi', 'getEmailCampaigns');
    }

    private function getAccount()
    {
        return $this->callApiMethod('AccountApi', 'getAccount');
    }

    private function getSenders()
    {
        return $this->callApiMethod('SendersApi', 'getSenders');
    }

    private function getSmsCampaings()
    {
        return $this->callApiMethod('SMSCampaignsApi', 'getSmsCampaigns');
    }

    private function getProcess()
    {
        return $this->callApiMethod('ProcessApi', 'getProcesses');
    }

    private function createContactsList($data)
    {
        $apiInstance = $this->get('sendinblue_client_ContactsApi');
        try {
            $result = $apiInstance->createList($data);
        } catch (Exception $e) {
            echo 'Exception when calling ListsApi->createList : ', $e->getMessage(), PHP_EOL;
        }

        return $result;
    }



    /**
     * test sendinBlue
     *
     * @param Request $request
     * @Route("/test", name="sendinBlue_test", methods="GET|POST")
     *
     * @return Response
     */
    public function testAction(Request $request)
    {
        $this->loadSendInBlueConfiguration($request);

        // account
        //dump($this->getAccount());
        // test creation list
        //$data = ['name'=>'liste test depuis postparc', 'folderId'=>1];
        //dump($this->createList($data));
        // list
        //dump($this->getLists());
        // email_campaigns
        //dump($this->getEmailCampaigns());
        // contacts
        //dump($this->getContacts());
        // senders
        //dump($this->getSenders());
        // sms_campaigns
        //dump($this->getSmsCampaings());
        // process
        //dump($this->getProcess());
        // smtp
        dump($this->getSmtpTemplates());
        // test creation list
        //$this->createList(['list_name'=>'liste test depuis postparc']);
        die;
    }
}

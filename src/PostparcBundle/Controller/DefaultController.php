<?php

namespace PostparcBundle\Controller;

use PostparcBundle\Entity\Help;
use PostparcBundle\Entity\Person;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tree\Fixture\User;
use Cocur\Slugify\Slugify;

class DefaultController extends Controller
{
    private $encrypt_method;
    private $secret_key;
    private $secret_iv;

    public function __construct()
    {
        $this->encrypt_method = 'AES-256-CBC';
        $this->secret_key = 'D2056E42A433C16EAF88C5612823A0A8';
        $this->secret_iv = '4CF76A4CE128A8999B198968C214D1F0';
    }
    /**
     * @Route("/", name="homepage"))
     *
     * @param Request $request
     *
     * @return Response
     */
    public function homepageAction(Request $request)
    {
        $currentUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        // récupération des listes de recherches non suuprimées
        $searchLists = $em->getRepository('PostparcBundle:SearchList')->getUserActiveSearchList($currentUser->getId());
        // récupération des évènements à venir
        $events = $em->getRepository('PostparcBundle:Event')->getFuturUserEvents($currentUser);
        // récupération des alertes à venir
        $eventAlerts = $em->getRepository('PostparcBundle:EventAlert')->getFuturUserEventAlerts($currentUser);

        // récupération du quota de mail
        $consumptionInfos = null;
        if (is_array($currentEntityConfig) && array_key_exists('use_massiveMail', $currentEntityConfig) && $currentEntityConfig['use_massiveMail']) {
            $consumptionInfos = $this->getComsuptionInfo($request);
        }

        // récupération des favories.
        $favoritesArray = [];
        $favories = $this->getUser()->getFavorites();
        foreach ($favories as $key => $favoriIds) {
            if (count($favoriIds) > 0) {
                $favoritesArray[$key] = $em->getRepository('PostparcBundle:' . $key)->findBy(['id' => $favoriIds]);
            }
        }

        return $this->render('default/index.html.twig', [
              'searchLists' => $searchLists,
              'events' => $events,
              'eventAlerts' => $eventAlerts,
              'consumptionInfos' => $consumptionInfos,
              'favorites' => $favoritesArray,
        ]);
    }
    
    /**
     * @Route("/email-opened/{token}", name="email-opened"))
     *
     * @param Request $token
     *
     * @return Response
     */
    public function emailOpenedAction($token)
    {
      return $this->render('default/empty.html.twig', [
            'token' => $token
        ]);
    }
    

    /**
     * @Route("summerUploads", name="summerUploads", options={"expose"=true})
     *
     * @return Response
     */
    public function summerUploadsAction(Request $request)
    {
        $file = $request->files->get('file');
        $filePathDir = $this->get('kernel')->getRootDir() . '/../web';
        $env = $this->container->get('kernel')->getEnvironment();
        $folder = '/uploads/documentTemplateImages/' . $env;
        
        $slugger = new Slugify();
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slugify($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        
        $file->move($filePathDir . $folder, $fileName);
        $output_file_name = $folder . '/' . $fileName;

        return new Response($output_file_name, 200);
    }

    /**
     * @Route("rgpd/{hash}/unsuscribe", name="rgpd-unsuscribe")
     * @param type $hash
     * @return type
     */
    public function rgpdUnsuscribeAction($hash, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $personId = $this->decrypt($hash);
        $confirmRgpd = $request->request->has('confirmRgpd');
        $deleteMyInfos =  false;
        $person = $em->getRepository('PostparcBundle:Person')->find($personId);
        if ($person && $confirmRgpd) {
            $person->setDontWantToBeContacted(true);
            $em->persist($person);
            $em->flush();
            if ($request->request->has('deleteMyInfos') && 1 == $request->request->get('deleteMyInfos')) {
                $this->cleanRgpdInfos($person);
                $deleteMyInfos =  true;
            }
        }

        return $this->render('default/rgpd-unsuscribe.html.twig', [
              'person' => $person,
              'confirmRgpd' => $confirmRgpd,
              'deleteMyInfos' => $deleteMyInfos
        ]);
    }
    /**
     * @Route("/{className}/{objectId}/lockMessage", name="lock-message")
     * @param string $className
     * @param id $objectId
     * @return type
     */
    public function lockMessageAction($className, $objectId)
    {
        $em = $this->getDoctrine()->getManager();
        $object = $em->getRepository('PostparcBundle:' . $className)->find($objectId);

        return $this->render('default/lockMessage.html.twig', [
              'object' => $object,
        ]);
    }

    /**
     * Clean all values in database associate to one person after rgpd unsuscrive
     * @param Person $person
     */
    private function cleanRgpdInfos(Person $person)
    {
        $em = $this->getDoctrine()->getManager();
        $coordinate = $person->getCoordinate();
        $person->setProfession(null);
        $person->setBirthDate(null);
        $person->setBirthDate(null);
        $person->setObservation(null);
        $person->setCoordinate(null);
        $em->persist($person);
        $em->flush();

        if ($coordinate) {
            $em->getRepository('PostparcBundle:Coordinate')->delete($coordinate->getId());
            $em->flush();
        }
    }

    private function decrypt($string)
    {
        $key = hash('sha256', $this->secret_key);
        $iv = substr(hash('sha256', $this->secret_iv), 0, 16);

        return openssl_decrypt(base64_decode($string), $this->encrypt_method, $key, 0, $iv);
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

    /**
     * Change the locale for the current user.
     *
     * @Route("/setlocale/{language}", name="setlocale")
     *
     * @param Request $request
     * @param string  $language
     *
     * @return Response
     */
    public function setLocaleAction(Request $request, $language = null)
    {
        if (null != $language) {
            $this->get('session')->set('_locale', $language);
        }

        $url = $request->headers->get('referer');
        if (empty($url)) {
            $url = $this->container->get('router')->generate('index');
        }

        return new RedirectResponse($url);
    }

    /**
     * @Route("/help_message/{id}", name="help_message"))
     *
     * @param int $id
     *
     * @return Response
     */
    public function helpMessageAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $help = $em->getRepository('PostparcBundle:Help')->find($id);

        if (!$help instanceof Help) {
            throw $this->createNotFoundException('Entity Help with id ' . $id . ' not found.');
        }

        return $this->render('default/help.html.twig', [
              'help' => $help,
        ]);
    }

    /**
     * @Route("/resultsPerPage", name="results_per_page"))
     *
     * @return Response
     */
    public function resultsPerPageAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userEntity = $em->getRepository('PostparcBundle:User')->find($user->getId());
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $resultsPerPage = $userEntity->getResultsPerPage() ? $userEntity->getResultsPerPage() : $default_items_per_page;

        if (!$userEntity instanceof User) {
            throw $this->createNotFoundException('Entity User with id ' . $id . ' not found.');
        }

        return $this->render('default/resultsPerPage.html.twig', [
              'resultsPerPage' => $resultsPerPage,
        ]);
    }

    /**
     * @Route("/updateResultsPerPage", name="update_results_per_page"))
     *
     * @return Response
     */
    public function updateResultsPerPageAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userEntity = $em->getRepository('PostparcBundle:User')->find($user->getId());
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $resultsPerPage = $userEntity->getResultPerPage() ? $userEntity->getResultPerPage() : $default_items_per_page;

        if (!$userEntity instanceof User) {
            throw $this->createNotFoundException('Entity User with id ' . $id . ' not found.');
        }

        return $this->render('default/resultsPerPage.html.twig', [
              'resultsPerPage' => $resultsPerPage,
        ]);
    }

    /**
     * checkListPerson.
     *
     * @Route("/checkListPerson", name="checkListPerson")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkListPerson(Request $request)
    {
        $search = $request->request->get('search');
        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        //$entityId = $currentEntityService->getCurrentEntityId();
        //$currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        //$persons = $em->getRepository('PostparcBundle:Person')->autoComplete($search, $entityId, $currentEntityConfig['show_SharedContents']);
        // on récupère l'ensemble des personnes, sans passer par les restrictions sur les entités
        $persons = $em->getRepository('PostparcBundle:Person')->autoComplete($search);

        return $this->render('default/checkListPerson.html.twig', [
              'persons' => $persons,
        ]);
    }

    /**
     * checkListOrganization.
     *
     * @Route("/checkListOrganization", name="checkListOrganization")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkListOrganization(Request $request)
    {
        $search = $request->request->get('search');
        $em = $this->getDoctrine()->getManager();
        //$currentEntityService = $this->container->get('postparc_current_entity_service');
        //$entityId = $currentEntityService->getCurrentEntityId();
        //$currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        //$organizations = $em->getRepository('PostparcBundle:Organization')->autoComplete($search, $entityId, $currentEntityConfig['show_SharedContents']);
        // on récupère l'ensemble des organismes, sans passer par les restrictions sur les entités
        $organizations = $em->getRepository('PostparcBundle:Organization')->autoComplete($search);

        return $this->render('default/checkListOrganization.html.twig', [
              'organizations' => $organizations,
        ]);
    }

    /**
     * checkListFunction.
     *
     * @Route("/checkListPersonFunction", name="ajax_checkListFunction")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkListPersonFunction(Request $request)
    {
        $search = $request->request->get('search');
        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        //$entityId = $currentEntityService->getCurrentEntityId();
        //$currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        //$persons = $em->getRepository('PostparcBundle:Person')->autoComplete($search, $entityId, $currentEntityConfig['show_SharedContents']);
        // on récupère l'ensemble des personnes, sans passer par les restrictions sur les entités
        $personFunctions = $em->getRepository('PostparcBundle:PersonFunction')->autoComplete($search);

        return $this->render('default/checkListPersonFunction.html.twig', [
              'personFunctions' => $personFunctions,
        ]);
    }

    /**
     * checkListAditionalFunction.
     *
     * @Route("/checkListadditionalFunction", name="ajax_checkListAdditionalFunction")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkListAditionalFunction(Request $request)
    {
        $search = $request->request->get('search');
        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        //$entityId = $currentEntityService->getCurrentEntityId();
        //$currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        //$persons = $em->getRepository('PostparcBundle:Person')->autoComplete($search, $entityId, $currentEntityConfig['show_SharedContents']);
        // on récupère l'ensemble des personnes, sans passer par les restrictions sur les entités
        $additionalFunctions = $em->getRepository('PostparcBundle:AdditionalFunction')->autoComplete($search);

        return $this->render('default/checkListAdditionalFunction.html.twig', [
              'additionalFunctions' => $additionalFunctions,
        ]);
    }

    /**
     * checkListTerritory.
     *
     * @Route("/checkListTerritory", name="ajax_checkListTerritory")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkListTerritoryFunction(Request $request)
    {
        $search = $request->request->get('search');
        $em = $this->getDoctrine()->getManager();
        //$currentEntityService = $this->container->get('postparc_current_entity_service');
        //$entityId = $currentEntityService->getCurrentEntityId();
        //$currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        //$persons = $em->getRepository('PostparcBundle:Person')->autoComplete($search, $entityId, $currentEntityConfig['show_SharedContents']);
        // on récupère l'ensemble des personnes, sans passer par les restrictions sur les entités
        $territories = $em->getRepository('PostparcBundle:Territory')->autoComplete($search);

        return $this->render('default/checkListTerritory.html.twig', [
              'territories' => $territories,
        ]);
    }
}

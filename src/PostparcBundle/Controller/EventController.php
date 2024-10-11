<?php

namespace PostparcBundle\Controller;

use PostparcBundle\Entity\Event;
use PostparcBundle\Entity\EventAlert;
use PostparcBundle\Entity\EventPersons;
use PostparcBundle\Entity\EventPfos;
use PostparcBundle\Entity\EventRepresentations;
use PostparcBundle\Entity\Person;
use PostparcBundle\Entity\Pfo;
use PostparcBundle\Form\EventType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Event controller.
 *
 * @Route("/event")
 */
class EventController extends Controller
{
    /**
     * Lists all Event entities.
     *
     * @param Request $request
     * @Route("/", name="event_index", methods="GET|POST")
     *
     * @return view
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\EventFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('eventFilter');

            return $this->redirect($this->generateUrl('event_index'));
        }

        // Filter action
        if ('filter' == $request->get('filter_action')) {
            // Bind values from the request
            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                // Save filter to session
                if ($filterForm->has('name')) {
                    $filterData['name'] = $filterForm->get('name')->getData();
                }
                if ($filterForm->has('eventType') && $filterForm->get('eventType')->getData()) {
                    $filterData['eventType'] = $filterForm->get('eventType')->getData()->getId();
                }
                if ($filterForm->has('tags') && null != $filterForm->get('tags')->getData()) {
                    $filterData['tags'] = $filterForm->get('tags')->getData();
                }
                if ($filterForm->has('createdBy') && null != $filterForm->get('createdBy')->getData()) {
                    $filterData['createdBy'] = $filterForm->get('createdBy')->getData();
                }

                $session->set('eventFilter', $filterData);
            }
        } elseif ($session->has('eventFilter')) {
            $filterData = $session->get('eventFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\EventFilterType', $filterData, ['data_class' => null]);
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
            if (array_key_exists('eventType', $filterData)) {
                $organizationType = $em->getRepository('PostparcBundle:EventType')->find($filterData['eventType']);
                $filterForm->get('eventType')->setData(eventType);
            }
            if (array_key_exists('tags', $filterData)) {
                $tagIds = [];
                foreach ($filterData['tags'] as $tag) {
                    $tagIds[] = $tag->getId();
                }
                if (($tagIds !== []) > 0) {
                    $tags = $em->getRepository('PostparcBundle:Tag')->findby(['id' => $tagIds]);
                    $filterForm->get('tags')->setData($tags);
                }
            }
            if (array_key_exists('createdBy', $filterData)) {
                $filterForm->get('createdBy')->setData($filterData['createdBy']);
            }
        }

        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $query = $em->getRepository('PostparcBundle:Event')->search($filterData, $entityId, $readerLimitations, $show_SharedContents);
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page)/* limit per page */
        );
        

        return $this->render('event/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
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
     * Creates a new PersonFunction entity.
     *
     * @param Request $request
     * @Route("/batch", name="event_batch", methods="POST")
     *
     * @return view
     */
    public function batchAction(Request $request)
    {
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();

        if ('batchDelete' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();
                $em->getRepository('PostparcBundle:Event')->batchDelete($ids, $entityId);
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('event_index');
    }

    /**
     * massive actions on selected event participants.
     *
     * @param Event   $event
     * @param Request $request
     * @Route("/{id}/batchDetails", name="eventDetails_batch", methods="POST")
     *
     * @return view
     */
    public function batchDetailsAction(Event $event, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $personIds = $request->request->get('personIds');
        $pfoIds = $request->request->get('pfoIds');
        $representationIds = $request->request->get('representationIds');

        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();

        if ($personIds || $pfoIds || $representationIds) {
            switch ($request->request->get('batch_action')) {
                case 'removeFromEvent':
                    if ($personIds) {
                        if ($entityId) {
                            $persons = $em->getRepository('PostparcBundle:Person')->findBy(['id' => $personIds, 'entity' => $entityId]);
                        } else {
                            $persons = $em->getRepository('PostparcBundle:Person')->findBy(['id' => $personIds]);
                        }
                        foreach ($persons as $person) {
                            $eventPersons = $em->getRepository('PostparcBundle:EventPersons')->findOneBy(['person' => $person->getId(), 'event' => $event->getId()]);
                            $em->remove($eventPersons);
                        }
                    }
                    if ($pfoIds) {
                        if ($entityId) {
                            $pfos = $em->getRepository('PostparcBundle:Pfo')->findBy(['id' => $pfoIds, 'entity' => $entityId]);
                        } else {
                            $pfos = $em->getRepository('PostparcBundle:Pfo')->findBy(['id' => $pfoIds]);
                        }
                        foreach ($pfos as $pfo) {
                            $eventPfos = $em->getRepository('PostparcBundle:EventPfos')->findOneBy(['pfo' => $pfo->getId(), 'event' => $event->getId()]);
                            $em->remove($eventPfos);
                        }
                    }
                    if ($representationIds) {
                        if ($entityId) {
                            $representations = $em->getRepository('PostparcBundle:Representation')->findBy(['id' => $representationIds, 'entity' => $entityId]);
                        } else {
                            $representations = $em->getRepository('PostparcBundle:Representation')->findBy(['id' => $representationIds]);
                        }
                        foreach ($representations as $representation) {
                            $eventRepresentations = $em->getRepository('PostparcBundle:EventRepresentations')->findOneBy(['representation' => $representation->getId(), 'event' => $event->getId()]);
                            $em->remove($eventRepresentations);
                        }
                    }
                    $em->persist($event);
                    $em->flush();
                    $this->addFlash('success', 'flash.deleteSuccess');
                    break;
                case 'batchAddBasket':
                    $session = $request->getSession();
                    $selectionData = [];
                    if ($session->has('selection')) {
                        $selectionData = is_array($session->get('selection')) ? $session->get('selection') : [];
                        if ($personIds) {
                            if (isset($selectionData['personIds'])) {
                                foreach ($personIds as $id) {
                                    if (!in_array($id, $selectionData['personIds'])) {
                                        $selectionData['personIds'][] = $id;
                                    }
                                }
                            } else {
                                $selectionData['personIds'] = $personIds;
                            }
                        }
                        if ($pfoIds) {
                            if (isset($selectionData['pfoIds'])) {
                                foreach ($pfoIds as $id) {
                                    if (!in_array($id, $selectionData['pfoIds'])) {
                                        $selectionData['pfoIds'][] = $id;
                                    }
                                }
                            } else {
                                $selectionData['pfoIds'] = $pfoIds;
                            }
                        }
                    }
                    $session->set('selection', $selectionData);
                    $this->addFlash('success', 'flash.addSuccess');
                    break;
                 case 'batchExport':
                     return $this->exportEventPersons($event, $personIds, $pfoIds, $representationIds);
                     break;
            }
        }

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    /**
     * Creates a new Event entity.
     *
     * @param Request $request
     * @Route("/new", name="event_new", methods="GET|POST")
     *
     * @return view
     */
    public function newAction(Request $request)
    {
        $event = new Event();
        $event->setEnv($this->container->get('kernel')->getEnvironment());
        $form = $this->createForm('PostparcBundle\Form\EventType', $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            $this->updateEndDateField($event);

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
            } else {
                $event = new Event();
                $form = $this->createForm('PostparcBundle\Form\EventType', $event);
            }
        }

        return $this->render('event/new.html.twig', [
              'event' => $event,
              'form' => $form->createView(),
        ]);
    }

    /**
     * calculate and update endDate field.
     *
     * @param Event $event
     */
    private function updateEndDateField(Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        if ($event->getDuration() !== '' && $event->getDuration() !== '0') {
            $endDate = $event->getDate();
            $endDate->add(new \DateInterval($event->getDuration()));
        } else {
            $endDate = $event->getDate();
        }
        $event->setEndDate($endDate);
        $em->persist($event);
        $em->flush();
    }

    /**
     * Create new event form an other event.
     *
     * @param Event $event
     * @Route("/{id}/copy", name="event_copy", methods="GET")
     *
     * @return view
     */
    public function copyAction(Event $event)
    {
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();
        $now = new \DateTime();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entity = $currentEntityService->getCurrentEntity();

        $newEvent = clone $event;
        $newEvent->setName($event->getName() . ' - ' . $translator->trans('copy'));
        $newEvent->setSlug($event->getSlug() . '_copy_' . uniqid());
        $newEvent->setCreated($now);
        $newEvent->setUpdated($now);
        $newEvent->setCreatedBy($this->getUser());
        $newEvent->setUpdatedBy(null);
        $newEvent->setEntity($entity);

        //coordinate
        $coordinate = $event->getCoordinate();
        $newCoordinate = clone $coordinate;
        $em->persist($newCoordinate);
        $newEvent->setCoordinate($newCoordinate);

        // récupération des invitations
        foreach ($event->getPersons() as $person) {
            $eventPersons = new EventPersons();
            $eventPersons->setEvent($newEvent);
            $eventPersons->setPerson($person);
            $em->persist($eventPersons);
        }
        foreach ($event->getPfos() as $pfo) {
            $eventPfos = new EventPfos();
            $eventPfos->setEvent($newEvent);
            $eventPfos->setPfo($pfo);
            $em->persist($eventPfos);
        }
        foreach ($event->getRepresentations() as $representation) {
            $eventRepresentations = new EventRepresentations();
            $eventRepresentations->setEvent($newEvent);
            $eventRepresentations->setRepresentation($representation);
            $em->persist($eventRepresentations);
        }

        $em->persist($newEvent);
        $em->flush();

        $this->addFlash('success', 'Event.alerts.copySuccess');

        return $this->redirectToRoute('event_show', ['id' => $newEvent->getId()]);
    }

    /**
     * Finds and displays a Event entity.
     *
     * @param Event   $event
     * @param Request $request
     * @Route("/{id}", name="event_show", methods="GET|POST", requirements={"id":"\d+"})
     *
     * @return view
     */
    public function showAction(Event $event, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();//session pour les filtres
        $currentEntityConfig = $session->get('currentEntityConfig');
        $paginator = $this->get('knp_paginator');

        $this->updateNbOpenedEmailFields($event);

        $checkAccessService = $this->container->get('postparc.check_access');

        if (!$checkAccessService->checkAccess($event)) {
            $referer = $request->headers->get('referer');
            $this->addFlash('info', 'flash.accessDenied');

            return $referer ? $this->redirect($referer) : $this->redirectToRoute('event_index');
        }

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\OneEventFilterType',null, ['method' => 'POST']);
        $filterAction = true;
        if ($request->request->has('filter_action')) {
            $action = $request->request->get('filter_action');
            $filterAction = ($action == 'reset') ? false : true;
        }
        
        if ($request->request->has('one_event_filter') && $filterAction) {
           
            $filterData = $request->request->get('one_event_filter');
            //pb avec la valeur 1, true attendu
            foreach ($request->request->get('one_event_filter') as $key => $value) {
                if ($value === "1") {
                    $filterData[$key] = true;
                }
            }
            $filterForm = $this->createForm('PostparcBundle\FormFilter\OneEventFilterType',  $filterData, ['data_class' => null]);
        }
        
        $deleteForm = $this->createDeleteForm($event);
        $nbElements = 0;
        // gestion tabs
        $activeTab = 'persons'; // default value

        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();

        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');

        // list persons, pfos and representations
        $queryPersons = $em->getRepository('PostparcBundle:Person')->getEventPersonsQuery($event->getId(),$filterData);
        $queryPfos = $em->getRepository('PostparcBundle:Pfo')->getEventPfosQuery($event->getId(), $entityId, $readerLimitations,true,$filterData);
        $queryRepresentations = $em->getRepository('PostparcBundle:Representation')->getEventRepresentationsQuery($event->getId(), $readerLimitations,$filterData);
        // lancement des requêtes paginées
        // persons
        $persons = $paginator->paginate(
            $queryPersons, /* query NOT result */
            $request->query->getInt('pagePerson', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
            'pageParameterName' => 'pagePerson',
            'sortFieldParameterName' => 'sortPerson',
            'sortDirectionParameterName' => 'directionPerson',
            'wrap-queries' => true,
                  ]
        );
        $persons->setParam('activeTab', 'persons');
        $nbElements += $persons->getTotalItemCount();

        // pfos
        $pfos = $paginator->paginate(
            $queryPfos, /* query NOT result */
            $request->query->getInt('pagePfo', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
            'pageParameterName' => 'pagePfo',
            'sortFieldParameterName' => 'sortPfo',
            'sortDirectionParameterName' => 'directionPfo',
            ]
        );
        $pfos->setParam('activeTab', 'pfos');
        $nbElements += $pfos->getTotalItemCount();

        // representations
        $representations = $paginator->paginate(
            $queryRepresentations, /* query NOT result */
            $request->query->getInt('pagePfo', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
            'pageParameterName' => 'pageRepresentation',
            'sortFieldParameterName' => 'sortRepresentation',
            'sortDirectionParameterName' => 'directionRepresentation',
            ]
        );
        $representations->setParam('activeTab', 'representations');
        $nbElements += $representations->getTotalItemCount();

        // NEw EventAlertForm
        $eventAlert = new EventAlert();
        $eventAlert->setRecipients(3);
        $eventAlert->setSenderName($this->getUser());
        
        // récupération des participants sans adresses emails
        $itemsWhitoutEmail = [];
        $nbItemsWhitoutEmail = 0;
        $personWithoutEmails = $em->getRepository('PostparcBundle:Person')->getEventPersonWithoutEmails($event);
        if ($personWithoutEmails) {
            $itemsWhitoutEmail['persons'] = $personWithoutEmails;
            $nbItemsWhitoutEmail += count($personWithoutEmails);
        }
        $pfoWithoutEmails = $em->getRepository('PostparcBundle:Pfo')->getEventPfoWithoutEmails($event);
        if ($pfoWithoutEmails) {
            $itemsWhitoutEmail['pfos'] = $pfoWithoutEmails;
            $nbItemsWhitoutEmail += count($pfoWithoutEmails);
        }
        $representationWithoutEmails = $em->getRepository('PostparcBundle:Representation')->getEventRepresentationWithoutEmails($event);
        if ($representationWithoutEmails) {
            $itemsWhitoutEmail['representations'] = $representationWithoutEmails;
            $nbItemsWhitoutEmail += count($representationWithoutEmails);
        }
        
        $formEventAlert = $this->createForm('PostparcBundle\Form\EventAlertType', $eventAlert, [
            'noreplyEmails' => $this->getParameter('noreplyEmails'),
            'event'=>$event
           ]);
        
        $consumptionInfos = $this->getComsuptionInfo($request);
        
        if ($request->isMethod('POST')) {
            $formEventAlert->handleRequest($request);

            if ($formEventAlert->isSubmitted() && $formEventAlert->isValid()) {
                
                if ($consumptionInfos['quota'] < $consumptionInfos['nbEmail']) {
                    $request->getSession()
                      ->getFlashBag()
                      ->add('error', 'flash.massiveSendMailQuotaExceeded');
                    return $this->redirect($this->generateUrl('event_show', ['id'=> $event->getId()]));
                }
                
                $em = $this->getDoctrine()->getManager();
                if (empty($eventAlert->getMessage())) {
                    $eventAlert->setMessage(' ');
                }
                $eventAlert->setEvent($event);
                $dateEffective = null;
                if (!$eventAlert->getIsManualAlert()) {
                    // calcul date et heure effective lancement
                    $dateEffective = clone $event->getDate();
                    // construct interval
                    $intervalString = 'P';
                    $intervalString .= 'H' == $eventAlert->getUnit() ? 'T' : '';
                    if ('W' == $eventAlert->getUnit()) { // week
                        $intervalString .= ($eventAlert->getGap() * 7) . 'D';
                    } else {
                        $intervalString .= $eventAlert->getGap() . $eventAlert->getUnit();
                    }
                    if ('add' == $eventAlert->getDirection()) {
                        $dateEffective->add(new \DateInterval($intervalString));
                    } else {
                        $dateEffective->sub(new \DateInterval($intervalString));
                    }
                }
                $eventAlert->setEffectiveDate($dateEffective);
                $em->persist($eventAlert);
                $em->flush();

                // clean form and entity to create new one
                unset($eventAlert);
                unset($formEventAlert);
                $eventAlert = new EventAlert();
                $formEventAlert = $this->createForm('PostparcBundle\Form\EventAlertType', $eventAlert, [
                    'noreplyEmails' => $this->getParameter('noreplyEmails'),
                    'event'=>$event
                   ]);

                $this->addFlash('success', 'flash.addSuccess');
                $activeTab = 'eventAlerts';
            }        
        }
        // EventAlerts
        $queryEventAlert = $em->getRepository('PostparcBundle:EventAlert')->getEventAlertsQuery($event->getId());
        $eventAlerts = $paginator->paginate(
            $queryEventAlert, /* query NOT result */
            $request->query->getInt('pageEventAlert', 1)/* page number */,
            $request->query->getInt('per_page', $this->container->getParameter('per_page_global')), /* limit per page */
            [
            'pageParameterName' => 'pageEventAlert',
            'sortFieldParameterName' => 'sortEventAlert',
            'sortDirectionParameterName' => 'directionEventAlert',
            ]
        );
        $eventAlerts->setParam('activeTab', 'eventAlerts');

        // récupération des modèles de document
        $documentTemplates = $em->getRepository('PostparcBundle:DocumentTemplate')->findBy(['isActive' => 1, 'mailable' => 1, 'deletedAt'=>null], ['name' => 'desc']);                

        return $this->render('event/show.html.twig', [
              'event' => $event,
              'delete_form' => $deleteForm->createView(),
              'nbElements' => $nbElements,
              'persons' => $persons,
              'pfos' => $pfos,
              'representations' => $representations,
              'activeTab' => $activeTab,
              'eventAlerts' => $eventAlerts,
              'documentTemplates' => $documentTemplates,
              'formEventAlert' => $formEventAlert->createView(),  
              'filter_form' => $filterForm->createView(),
              'itemsWhitoutEmail' => $itemsWhitoutEmail,
              'nbItemsWhitoutEmail' => $nbItemsWhitoutEmail,
              'consumptionInfos' => $consumptionInfos
        ]);
    }

    /**
     * Displays a form to edit an existing Event entity.
     *
     * @param Request $request
     * @param Event   $event
     * @Route("/{id}/edit", name="event_edit", methods="GET|POST")
     *
     * @return view
     */
    public function editAction(Request $request, Event $event)
    {
        $oldImage = $event->getImage();
        $deleteForm = $this->createDeleteForm($event);
        $editForm = $this->createForm('PostparcBundle\Form\EventType', $event);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if (!$request->files->has('image') && $oldImage && !$request->request->has('deleteImage')) {
                $event->setImage($oldImage);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            $this->updateEndDateField($event);

            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', [
              'event' => $event,
              'edit_form' => $editForm->createView(),
              'delete_form' => $deleteForm->createView(),
              'noreplyEmails' => $this->getParameter('noreplyEmails')
        ]);
    }

    /**
     * return confirmatiopnDate of one EventPersons.
     *
     * @param int $person
     * @param int $event
     * @Route("/{person}/{event]/getEventPersonsConfirmationDate", name="event_getEventPersonsConfirmationDate", methods="GET")
     *
     * @return view
     */
    public function getEventPersonsConfirmationDateAction($person, $event)
    {
        /*@var $eventPersons EventPersons */
        $em = $this->getDoctrine()->getManager();
        $eventPersons = $em->getRepository('PostparcBundle:EventPersons')->findOneBy(['person' => $person->getId(), 'event' => $event->getId()]);
        $confirmationDate = $eventPersons->getConfirmationDate();
        $unconfirmationDate = $eventPersons->getUnconfirmationDate();
        $representedBy = $eventPersons->getRepresentedBy();
        $representedByDate = $eventPersons->getRepresentedByDate();
        $token = $person->getClassName() . '_' . $eventPersons->getConfirmationToken();

        return $this->render(
                'event/_confirmationDateBloc.html.twig', 
                ['confirmationDate' => $confirmationDate, 
                 'unconfirmationDate' => $unconfirmationDate,
                 'representedBy' => $representedBy,
                 'confirmationToken' => $token,
                 'representedByDate' => $representedByDate
                ]);
    }

    /**
     * return confirmationDate of one EventPfos.
     *
     * @param int $pfo
     * @param int $event
     * @Route("/{pfo}/{event]/getEventPfosConfirmationDate", name="event_getEventPfosConfirmationDate", methods="GET")
     *
     * @return view
     */
    public function getEventPfosConfirmationDateAction($pfo, $event)
    {
        $em = $this->getDoctrine()->getManager();
        $eventPfos = $em->getRepository('PostparcBundle:EventPfos')->findOneBy(['pfo' => $pfo->getId(), 'event' => $event->getId()]);
        $confirmationDate = $eventPfos->getConfirmationDate();
        $unconfirmationDate = $eventPfos->getUnconfirmationDate();
        $token = $pfo->getClassName() . '_' . $eventPfos->getConfirmationToken();
        $representedBy = $eventPfos->getRepresentedBy();
        $representedByDate = $eventPfos->getRepresentedByDate();

        return $this->render(
                'event/_confirmationDateBloc.html.twig', 
                ['confirmationDate' => $confirmationDate, 
                 'unconfirmationDate' => $unconfirmationDate, 
                 'confirmationToken' => $token,
                 'representedBy' => $representedBy,
                 'representedByDate' => $representedByDate,
                ]);
    }

    /**
     * return confirmationDate of one EventPersons.
     *
     * @param int $representation
     * @param int $event
     * @Route("/{representation}/{event]/getEventRepresentationsConfirmationDate", name="event_getEventRepresentationsConfirmationDate", methods="GET")
     *
     * @return view
     */
    public function getEventRepresentationsConfirmationDateAction($representation, $event)
    {
        $em = $this->getDoctrine()->getManager();
        $eventRepresentations = $em->getRepository('PostparcBundle:EventRepresentations')->findOneBy(['representation' => $representation->getId(), 'event' => $event->getId()]);
        $confirmationDate = $eventRepresentations->getConfirmationDate();
        $unconfirmationDate = $eventRepresentations->getUnconfirmationDate();
        $representedBy = $eventRepresentations->getRepresentedBy();
        $token = $representation->getClassName() . '_' . $eventRepresentations->getConfirmationToken();

        return $this->render(
                'event/_confirmationDateBloc.html.twig', 
                [
                    'confirmationDate' => $confirmationDate, 
                    'unconfirmationDate' => $unconfirmationDate, 
                    'confirmationToken' => $token,
                    'representedBy' => $representedBy,
                ]);
    }

    /**
     * Deletes a Event entity.
     *
     * @param int $id
     * @Route("/{id}/delete", name="event_delete", methods="GET")
     *
     * @return view
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:Event')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('event_index');
    }

    /**
     * add persons ton one event.
     *
     * @param Request $request
     * @param Event   $event
     * @Route("/{id}/addPersons", name="event_addPersons", options={"expose"=true}, methods="POST")
     *
     * @return Response
     */
    public function addPersonsToEventAction(Request $request, Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = json_decode($request->request->get('ids'));
        $persons = $em->getRepository('PostparcBundle:Person')->findBy(['id' => $ids]);
        foreach ($persons as $person) {
            $eventPersons = new EventPersons();
            $eventPersons->setEvent($event);
            $eventPersons->setPerson($person);
            $em->persist($eventPersons);
        }
        $em->flush();
        $this->addFlash('success', 'flash.addSuccess');

        return new Response(json_encode($ids), 200);
    }

    /**
     * add pfos ton one event.
     *
     * @param Request $request
     * @param Event   $event
     * @Route("/{id}/addPfos", name="event_addPfos", options={"expose"=true}, methods="POST")
     *
     * @return Response
     */
    public function addPfosToEventAction(Request $request, Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = json_decode($request->request->get('ids'));
        $pfos = $em->getRepository('PostparcBundle:Pfo')->findBy(['id' => $ids]);
        foreach ($pfos as $pfo) {
            $eventPfos = new EventPfos();
            $eventPfos->setEvent($event);
            $eventPfos->setPfo($pfo);
            $em->persist($eventPfos);
        }

        $em->flush();
        $this->addFlash('success', 'flash.addSuccess');

        return new Response(json_encode($ids), 200);
    }

    /**
     * add representations ton one event.
     *
     * @param Request $request
     * @param Event   $event
     * @Route("/{id}/addRepresentations", name="event_addRepresentations", options={"expose"=true}, methods="POST")
     *
     * @return Response
     */
    public function addRepresentationsToEventAction(Request $request, Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = json_decode($request->request->get('ids'));
        $representations = $em->getRepository('PostparcBundle:Representation')->findBy(['id' => $ids]);
        foreach ($representations as $representation) {
            $eventRepresentations = new EventRepresentations();
            $eventRepresentations->setEvent($event);
            $eventRepresentations->setRepresentation($representation);
            $em->persist($eventRepresentations);
        }

        $em->flush();
        $this->addFlash('success', 'flash.addSuccess');

        return new Response(json_encode($ids), 200);
    }

    /**
     * remove person to one event.
     *
     * @param Event $event
     * @param int   $personId
     * @Route("/{id}/removePerson/{personId}", name="event_removePerson", methods="GET")
     *
     * @return Response
     */
    public function removePersonToEventAction(Request $request, Event $event, $personId)
    {
        $em = $this->getDoctrine()->getManager();

        $eventPersons = $em->getRepository('PostparcBundle:EventPersons')->findOneBy(['person' => $personId, 'event' => $event->getId()]);
        $em->remove($eventPersons);
        $em->flush();

        $this->addFlash('success', 'flash.deleteSuccess');

        if ($request->query->has('origin')) {
            return $this->redirect($request->query->get('origin'));
        } else {
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
    }

    /**
     * remove pfo to one event.
     *
     * @param Event $event
     * @param int   $pfoId
     * @Route("/{id}/removePfo/{pfoId}", name="event_removePfo", methods="GET")
     *
     * @return Response
     */
    public function removePfoToEventAction(Request $request, Event $event, $pfoId)
    {
        $em = $this->getDoctrine()->getManager();

        $eventPfos = $em->getRepository('PostparcBundle:EventPfos')->findOneBy(['pfo' => $pfoId, 'event' => $event->getId()]);
        $em->remove($eventPfos);
        $em->flush();

        $this->addFlash('success', 'flash.deleteSuccess');

        if ($request->query->has('origin')) {
            return $this->redirect($request->query->get('origin'));
        } else {
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
    }

    /**
     * confirm event presence of on person or pfo or representation.
     *
     * @param string $confirmationToken
     * @Route("/confirmEventPresence/{confirmationToken}", name="confirmEventPresence", methods="GET")
     *
     * @return Response
     */
    public function confirmEventPresence(Request $request, $confirmationToken)
    {
        $className = explode('_', $confirmationToken)[0];
        $isConfirmed = $request->query->has('isConfirmed');
        $alreadyConfirmed = false;
        $initialConfirmationToken = $confirmationToken;

        $confirmationToken = str_replace($className . '_', '', $confirmationToken);
        // search for inscription
        $em = $this->getDoctrine()->getManager();
        $eventInscription = $em->getRepository('PostparcBundle:Event' . $className . 's')->findOneBy(['confirmationToken' => $confirmationToken]);

        $participant = null;
        if ($eventInscription) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $participant = $propertyAccessor->getValue($eventInscription, strtolower($className));

            if (!$eventInscription->getConfirmationDate()) {
                if ($isConfirmed) {
                    $now = new \Datetime();
                    $eventInscription->setConfirmationDate($now);
                    $em->persist($eventInscription);
                    $em->flush();
                }
            } else {
                $alreadyConfirmed = true;
            }
        }

        return $this->render('event/confirmEventPresence.html.twig', [
            'confirmationToken' => $initialConfirmationToken,
            'eventInscription' => $eventInscription,
            'participant' => $participant,
            'isConfirmed' => $isConfirmed,
            'alreadyConfirmed' => $alreadyConfirmed,
         ]);
    }

    /**
     * unconfirm event presence of on person or pfo or representation.
     *
     * @param string $confirmationToken
     * @Route("/unconfirmEventPresence/{confirmationToken}", name="unconfirmEventPresence", methods="GET")
     *
     * @return Response
     */
    public function unconfirmEventPresence(Request $request, $confirmationToken)
    {
        $className = explode('_', $confirmationToken)[0];
        $isUnconfirmed = $request->query->has('isUnconfirmed');
        $alreadyUnconfirmed = false;
        $initialConfirmationToken = $confirmationToken;

        $confirmationToken = str_replace($className . '_', '', $confirmationToken);
        // search for inscription
        $em = $this->getDoctrine()->getManager();
        $eventInscription = $em->getRepository('PostparcBundle:Event' . $className . 's')->findOneBy(['confirmationToken' => $confirmationToken]);

        $participant = null;
        if ($eventInscription) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $participant = $propertyAccessor->getValue($eventInscription, strtolower($className));

            if (!$eventInscription->getUnconfirmationDate()) {
                if ($isUnconfirmed) {
                    $now = new \Datetime();
                    $eventInscription->setUnconfirmationDate($now);
                    $em->persist($eventInscription);
                    $em->flush();
                }
            } else {
                $alreadyUnconfirmed = true;
            }
        }

        return $this->render('event/confirmEventPresence.html.twig', [
            'confirmationToken' => $initialConfirmationToken,
            'eventInscription' => $eventInscription,
            'participant' => $participant,
            'isUnconfirmed' => $isUnconfirmed,
            'alreadyUnconfirmed' => $alreadyUnconfirmed,
         ]);
    }


    /**
     * unconfirm event presence of on person or pfo or representation.
     *
     * @param string $confirmationToken
     * @Route("/confirmEventRepresentedBy/{confirmationToken}", name="confirmEventRepresentedBy", methods="POST")
     *
     * @return Response
     */
    public function confirmEventRepresentedBy(Request $request, $confirmationToken)
    {
        $className = explode('_', $confirmationToken)[0];
        $isRepresentedBy = $request->query->has('isRepresentedBy');
        $alreadyRepresentatedBy = false;
        $initialConfirmationToken = $confirmationToken;

        $confirmationToken = str_replace($className . '_', '', $confirmationToken);
        // search for inscription
        $em = $this->getDoctrine()->getManager();
        $eventInscription = $em->getRepository('PostparcBundle:Event' . $className . 's')->findOneBy(['confirmationToken' => $confirmationToken]);

        $participant = null;
        if ($eventInscription) {
//            dump($eventInscription);die;
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $participant = $propertyAccessor->getValue($eventInscription, strtolower($className));

            if (!$eventInscription->getRepresentedBy()) {
                if ($isRepresentedBy) {
                    $now = new \Datetime();
                    $eventInscription->setRepresentedBy($request->request->get('representedBy'));
                    $eventInscription->setRepresentedByDate($now);
                    $em->persist($eventInscription);
                    $em->flush();
                }
            } else {
                $alreadyRepresentatedBy = true;
            }
        }

        return $this->render('event/confirmEventPresence.html.twig', [
            'confirmationToken' => $initialConfirmationToken,
            'eventInscription' => $eventInscription,
            'participant' => $participant,
            'isRepresentedBy' => $isRepresentedBy,
            'alreadyRepresentatedBy' => $alreadyRepresentatedBy,
         ]);
    }    

    /**
     * remove representation to one event.
     *
     * @param Event $event
     * @param int   $representationId
     * @Route("/{id}/removeRepresentation/{representationId}", name="event_removeRepresentation", methods="GET")
     *
     * @return Response
     */
    public function removeRepresentationToEventAction(Request $request, Event $event, $representationId)
    {
        $em = $this->getDoctrine()->getManager();

        $eventRepresentations = $em->getRepository('PostparcBundle:EventRepresentations')->findOneBy(['representation' => $representationId, 'event' => $event->getId()]);
        $em->remove($eventRepresentations);
        $em->flush();

        $this->addFlash('success', 'flash.deleteSuccess');
        if ($request->query->has('origin')) {
            return $this->redirect($request->query->get('origin'));
        } else {
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
    }
    
    /**
     * get template for tab dom.
     *
     * @param Request $request
     * @param string  $className
     * @param int     $objectId
     *
     *
     * @return Response
     */
    public function getTabDomAction($className, $objectId)
    {
        $em = $this->getDoctrine()->getManager();
        
        //récupération de l'object
        $object = $em->getRepository('PostparcBundle:' . $className)->find($objectId);

        $eventsInfos = $em->getRepository('PostparcBundle:Event')->getObjectEvents($className, $objectId);
        //dump($eventsInfos); die;

        return $this->render('event/tab.html.twig', [
          'eventsInfos' => $eventsInfos,
          'object' => $object,  
        ]);
    }
    
    /**
     * Lists all notes associate ton an object.
     *
     * @param Request $request
     * @param string  $className
     * @param int     $objectId
     * @Route("/{className}/{objectId}/events", name="object_events", methods="GET|POST")
     *
     * @return Response
     */
    public function getTabContentEventsAction(Request $request, $className, $objectId)
    {
        $em = $this->getDoctrine()->getManager();

        //récupération de l'object
        $object = $em->getRepository('PostparcBundle:' . $className)->find($objectId);

        $eventsInfos = $em->getRepository('PostparcBundle:Event')->getObjectEvents($className, $objectId);

        return $this->render('event/tabContent.html.twig', [
          'eventsInfos' => $eventsInfos,
          'object' => $object,
         ]);
    }

    /**
     * Creates a form to delete a Event entity.
     *
     * @param Event $event The Event entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Event $event)
    {
        return $this->createFormBuilder()
                    ->setAction($this->generateUrl('event_delete', ['id' => $event->getId()]))
                    ->setMethod('DELETE')
                    ->getForm()
        ;
    }

    private function updateNbOpenedEmailFields(Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        $havetoBeFlush = false;
        $piwikSatsService = $this->container->get('postparc.piwik_stats');

        foreach ($event->getEventAlerts() as $eventAlert) {
            if ($eventAlert->getToken()) {
                $piwikInfos = $piwikSatsService->getPiwikOpenedNewsletterInfos($eventAlert->getToken());
                if ($piwikInfos) {
                    $nbVisite = $piwikInfos['sum_daily_nb_uniq_visitors'];
                    if ($nbVisite && $eventAlert->getNbOpenedEmail() < $nbVisite) {
                        $eventAlert->setNbOpenedEmail($nbVisite);
                        $em->persist($eventAlert);
                        $havetoBeFlush = true;
                    }
                }
            }
        }
        if ($havetoBeFlush) {
            $em->flush();
        }
    }
    
    private function exportEventPersons($event, $personIds = [], $pfoIds = [], $representationIds = [])
    {
        $data=[];
        $em = $this->getDoctrine()->getManager();
        $personIds = $personIds ? $personIds : [];
        $pfoIds = $pfoIds ? $pfoIds : [];
        foreach ($personIds as $personId) {
            $person = $em->getRepository('PostparcBundle:Person')->find($personId);
            /*@var $eventPersons EventPersons */
            $eventPersons = $em->getRepository('PostparcBundle:EventPersons')->findOneBy(['person' => $person->getId(), 'event' => $event->getId()]);
            $pfos = $person->getPfos();
            $fonctions = $person->getFonctions();
            $oganizations = $person->getOrganizations();                
            $data[] = [
                $person->getCivility()->getName(),
                $person->getFirstName(),
                $person->getLastName(),
                implode(' | ', $fonctions),
                implode(' | ', $oganizations),
                $eventPersons->getConfirmationDate() ? 'X' : '',
                $eventPersons->getUnconfirmationDate() ? 'X' : '',
                $eventPersons->getRepresentedBy()
            ];
        }
        
        foreach ($pfoIds as $pfoId) 
        {
            $pfo = $em->getRepository('PostparcBundle:PFO')->find($pfoId);
            if (!in_array($pfo->getPerson()->getId(), $personIds)){
                $eventFpo = $em->getRepository('PostparcBundle:EventPfos')->findOneBy(['pfo' => $pfo->getId(), 'event' => $event->getId()]);
                $fonctions = $pfo->getPerson()->getFonctions();
                $oganizations = $pfo->getPerson()->getOrganizations();                   
                $data[] = [
                    $pfo->getPerson()->getCivility()->getName(),
                    $pfo->getPerson()->getFirstName(),
                    $pfo->getPerson()->getLastName(),
                    implode(' | ', $fonctions),
                    implode(' | ', $oganizations),
                    $eventFpo->getConfirmationDate() ? 'X' : '',
                    $eventFpo->getUnconfirmationDate() ? 'X' : '',
                    $eventFpo->getRepresentedBy()
                ];                                     
            }
        }           
        
        foreach ($representationIds as $representationId) 
        {
            /*@var $reprentation Representation */
            $reprentation = $em->getRepository('PostparcBundle:Representation')->find($representationId);
            if (!in_array($reprentation->getPerson()->getId(), $personIds)){
                $eventRepresentation = $em->getRepository('PostparcBundle:EventRepresentations')->findOneBy(['representation' => $reprentation->getId(), 'event' => $event->getId()]);
                $fonctions = $reprentation->getPerson()->getFonctions();
                $oganizations = $reprentation->getPerson()->getOrganizations();                   
                $data[] = [
                    $reprentation->getPerson()->getCivility()->getName(),
                    $reprentation->getPerson()->getFirstName(),
                    $reprentation->getPerson()->getLastName(),
                    implode(' | ', $fonctions),
                    implode(' | ', $oganizations),
                    $eventRepresentation->getConfirmationDate() ? 'X' : '',
                    $eventRepresentation->getUnconfirmationDate() ? 'X' : '',
                    $eventRepresentation->getRepresentedBy()
                ];                                     
            }
        } 

        

        $response = new StreamedResponse();
        $response->setCallback(function () use ($data) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, ['Civilité', 'Nom', 'Prénom', 'Fonction', 'Organisme', 'Présent', 'Absent', 'Représenté par'], ';');            
            foreach ($data as $row) {                
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        });

        /*@var $event Event*/
        $filename = 'Participant_' . $event->getSlug() . '.csv';
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);

        return $response;        

    }  

}
    

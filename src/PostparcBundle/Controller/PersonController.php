<?php

namespace PostparcBundle\Controller;

use PostparcBundle\Service\QrCodeService;
use Knp\Snappy\Pdf;
use PostparcBundle\Entity\Person;
use PostparcBundle\Entity\PfoPersonGroup;
use PostparcBundle\Entity\Representation;
use PostparcBundle\Form\PersonType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



/**
 * Person controller.
 *
 * @Route("/person")
 */
class PersonController extends Controller
{
    /**
     * Lists all Person entities.
     *
     * @param Request $request
     * @Route("/", name="person_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        if (!array_key_exists('personnalFieldsRestriction', $currentEntityConfig)) {
            $currentEntityConfig['personnalFieldsRestriction'] = [];
            $session->set('currentEntityConfig', $currentEntityConfig);
        }
        $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\PersonFilterType', null, ['personnalFieldsRestriction' => $personnalFieldsRestriction]);

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('personFilter');

            return $this->redirect($this->generateUrl('person_index'));
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
                if ($filterForm->has('city') && null != $filterForm->get('city')->getData()) {
                    $filterData['city'] = $filterForm->get('city')->getData()->getId();
                }
                if ($filterForm->has('profession') && null != $filterForm->get('profession')->getData()) {
                    $filterData['profession'] = $filterForm->get('profession')->getData()->getId();
                }
                if ($filterForm->has('tags') && null != $filterForm->get('tags')->getData()) {
                    $filterData['tags'] = $filterForm->get('tags')->getData();
                }
                if ($filterForm->has('updatedBy') && $filterForm->get('updatedBy')->getData()) {
                    $filterData['updatedBy'] = $filterForm->get('updatedBy')->getData()->getId();
                }

                $session->set('personFilter', $filterData);
            }
        } elseif ($session->has('personFilter')) {
            $filterData = $session->get('personFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\PersonFilterType', null, ['personnalFieldsRestriction' => $personnalFieldsRestriction]);
            if (array_key_exists('city', $filterData)) {
                $city = $em->getRepository('PostparcBundle:City')->find($filterData['city']);
                $filterForm->get('city')->setData($city);
            }
            if (array_key_exists('profession', $filterData)) {
                $city = $em->getRepository('PostparcBundle:Profession')->find($filterData['profession']);
                $filterForm->get('profession')->setData($city);
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
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
            if (array_key_exists('updatedBy', $filterData)) {
                $updatedBy = $em->getRepository('PostparcBundle:User')->find($filterData['updatedBy']);
                $filterForm->get('updatedBy')->setData($updatedBy);
            }
        }
        //echo $entityId;
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $query = $em->getRepository('PostparcBundle:Person')->search($filterData, $entityId, $show_SharedContents);
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
            'defaultSortFieldName' => 'p.slug',
            'defaultSortDirection' => 'asc',
                ]
        );

        return $this->render('person/index.html.twig', [
                    'pagination' => $pagination,
                    'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new Person entity.
     *
     * @param Request $request
     * @Route("/new", name="person_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $person = new Person();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];
        $person->setEnv($this->container->get('kernel')->getEnvironment());
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $form = $this->createForm('PostparcBundle\Form\PersonType', $person, ['personnalFieldsRestriction' => $personnalFieldsRestriction, 'user'=> $user]);
        $form->handleRequest($request);


        $entity = $user->getEntity();
        $person->setEntity($entity);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($person);
            $em->flush();

            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('person_show', ['id' => $person->getId()]);
            } else {
                $person = new Person();
                $form = $this->createForm(PersonType::class, $person, ['personnalFieldsRestriction' => $personnalFieldsRestriction, 'user'=> $user]);
            }
        }

        return $this->render('person/new.html.twig', [
                    'person' => $person,
                    'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Person entity.
     *
     * @param Person  $person
     * @param Request $request
     * @Route("/{id}", name="person_show", methods="GET", requirements={"id":"\d+"})
     *
     * @return Response
     */
    public function showAction(Person $person, Request $request)
    {
        
        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();

        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $pfos = $em->getRepository('PostparcBundle:Pfo')->getPersonPfos($person->getId(), $entityId, $readerLimitations, $show_SharedContents);
        

        $checkAccessService = $this->container->get('postparc.check_access');

        if (!$checkAccessService->checkAccess($person)) {
            $this->addFlash('info', 'flash.accessDenied');
            $referer = $request->headers->get('referer');
            return $referer ? $this->redirect($referer) : $this->redirectToRoute('person_index');
        }

        $representation = new Representation();
        $representationForm = $this->createForm('PostparcBundle\Form\RepresentationType', $representation);

        $representations = $em->getRepository('PostparcBundle:Representation')->getPersonRepresentations($person->getId(), $entityId, $show_SharedContents);

        $activeTab = 'persons'; // default value
        if(isset($currentEntityConfig['tabsOrder'])) {
            $tabsOrder = $currentEntityConfig['tabsOrder'];
            $activeTab = array_key_first($tabsOrder);
        }
        if ($request->query->has('activeTab')) {
            $activeTab = $request->query->get('activeTab');
        }
        $activePfo = $request->query->has('activePfo') ? $request->query->get('activePfo') : '';
        
        // Generate QRCode for Person
        $qrCodeService = $this->container->get('postparc_qrCodeService');
        $qrCodeInfos = $qrCodeService->generateVcardQrCode($person);
        // Generate QRCode for Pfos
        $pfoQrCodeInfos = [];
        foreach($pfos as $pfo){
            $pfoQrCodeInfos[$pfo->getId()] = $qrCodeService->generateVcardQrCode($pfo);
        }

        return $this->render('person/show.html.twig', [
                    'person' => $person,
                    'pfos' => $pfos,
                    'representations' => $representations,
                    'representation_form' => $representationForm->createView(),
                    'activePfo' => $activePfo,
                    'qrCodeUri' => $qrCodeInfos['uri'],
                    'pfoQrCodeInfos' => $pfoQrCodeInfos,
        ]);
    }



    /**
     * Displays a form to edit an existing Person entity.
     *
     * @param Request $request
     * @param Person  $person
     * @Route("/{id}/edit", name="person_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, Person $person)
    {
        $lockService = $this->container->get('postparc.lock_service');
        if ($lockService->isLock($person, $this->getParameter('maxLockDuration'))) {
            return $this->redirect($this->generateUrl('lock-message', ['className' => $person->getClassName(), 'objectId' => $person->getId()]));
        }
        $deleteForm = $this->createDeleteForm($person);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $editForm = $this->createForm(PersonType::class, $person, ['personnalFieldsRestriction' => $personnalFieldsRestriction, 'user'=> $user]);
        $editForm->handleRequest($request);
        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            // update name of associate representations
            if ($person->getRepresentations()) {
                foreach ($person->getRepresentations() as $representation) {
                    $representation->updateName();
                    $em->persist($representation);
                }
            }
            $em->persist($person);
            $em->flush();
            
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.editSuccess');
            
            $lockService->unlockObject($person);
            //return $this->redirectToRoute('person_edit', array('id' => $person->getId(), 'origin' => $origin));
            return $this->redirect($this->generateUrl('person_show', ['id' => $person->getId()]) . '#coordPerso');
        }

        return $this->render('person/edit.html.twig', [
                    'person' => $person,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
                    'origin' => $origin,
        ]);
    }

    /**
     * Displays a print format of a Person entity.
     *
     * @param Request $request
     * @param Person  $person
     * @Route("/{id}/print", name="person_print", methods="GET")
     *
     * @return Response
     */
    public function printAction(Request $request, Person $person)
    {
        $checkAccessService = $this->container->get('postparc.check_access');

        if (!$checkAccessService->checkAccess($person)) {
            $this->addFlash('info', 'flash.accessDenied');
            $referer = $request->headers->get('referer');
            return $referer ? $this->redirect($referer) : $this->redirectToRoute('person_index');
        }
        //$path = $this->getParameter('kernel.project_dir');
        $path = $request->getScheme() . '://' . $request->getHost();
        $path = 'http://' . $request->getHost();

        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $representations = $em->getRepository('PostparcBundle:Representation')->getPersonRepresentations($person->getId(), $entityId, $show_SharedContents);
        $pfos = $em->getRepository('PostparcBundle:Pfo')->getPersonPfos($person->getId(), $entityId, $readerLimitations, $show_SharedContents);
        $html = $this->renderView('person/print.html.twig', [
          'person' => $person,
          'path' => $path,
          'representations' => $representations,
          'pfos' => $pfos
          ]);

        $footerHtml = $this->renderView('default/pdfFooter.html.twig', [
          'path' => $path,
          'now' => new \DateTime()
          ]);

        $options = [
        'encoding' => 'UTF-8',
        'footer-html' => $footerHtml
        ];

        $vendorPath = $this->getParameter('kernel.project_dir'); 
        //$pdf = new Pdf($vendorPath . '/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64');
        $pdf = new Pdf($vendorPath . '/tools/wkhtmltopdf');
        $pdf->setOption('print-media-type', false);
        $pdf->setOption('header-spacing', 10);
        $pdf->setOption('footer-spacing', 10);
        $pdf->setOption('margin-bottom', 30);

        $filename = $person->getSlug();

        return new Response(
            $pdf->getOutputFromHtml($html, $options),
            200,
            [
               'Content-Type'          => 'application/pdf',
               'Content-Disposition'   => 'inline; filename="' . $filename . '.pdf"'
            ]
        );
    }

    /**
     * Deletes a Person entity.
     *
     * @param Request $request
     * @param Person  $person
     * @Route("/{id}/delete", name="person_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction(Request $request, Person $person)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$person->getId()];
        $coordinate = $person->getCoordinate();
        $isDelete = $person->getDeletedAt();
        $em->getRepository('PostparcBundle:Person')->batchDelete($ids, null, $this->getUser());
        if ($coordinate && $isDelete) {
            $email = $coordinate->getEmail();
            $em->getRepository('PostparcBundle:Coordinate')->delete($coordinate->getId());
            if ($email) {
                $em->getRepository('PostparcBundle:Email')->delete($email->getId());
            }
        }

        $request->getSession()
                ->getFlashBag()
                ->add('success', 'flash.deleteSuccess');

        if ($request->query->has('origin')) {
            switch ($request->query->get('origin')) {
                case 'search':
                    return $this->redirectToRoute('search');
                default:
                    return $this->redirectToRoute('person_index');
            }
        }

        return $this->redirectToRoute('person_index');
    }

    /**
     * add this personn to the basket.
     *
     * @param Person  $person
     * @param Request $request
     * @Route("/{id}/addBasket", name="person_addBasket", methods="GET")
     *
     * @return Response
     */
    public function addToBasketAction(Person $person, Request $request)
    {
        $session = $request->getSession();
        $selectionData = [];
        $alreadyExist = false;
        // Get filter from session
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            if (array_key_exists('personIds', $selectionData)) {
                if (!in_array($person->getId(), $selectionData['personIds'])) {
                    $selectionData['personIds'][] = $person->getId();
                } else {
                    $alreadyExist = true;
                }
            } else {
                $selectionData['personIds'] = [$person->getId()];
            }
        } else {
            $selectionData['personIds'] = [$person->getId()];
        }

        $session->set('selection', $selectionData);

        if ($alreadyExist) {
            $request->getSession()
                    ->getFlashBag()
                    ->add('error', 'Person.actions.addBasket.alreadyPresent');
        } else {
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'Person.actions.addBasket.success');
        }
        if ($request->query->has('origin')) {
            switch ($request->query->get('origin')) {
                case 'search':
                    return $this->redirectToRoute('search');
                case 'event':
                    return $this->redirectToRoute('event_show', ['id' => $request->query->get('eventId')]);
            }
        }

        return $this->redirectToRoute('person_index');
    }

    /**
     * add this personn to the basket.
     *
     * @param Request $request
     * @param Person  $person
     * @param string  $origin
     * @Route("/{id}/addGroup/{origin}", name="person_addGroup", methods="GET|POST")
     *
     * @return Response
     */
    public function addGroupAction(Request $request, Person $person, $origin)
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->request->has('groupIds')) {
            foreach ($request->request->get('groupIds') as $groupId) {
                $ppg = new PfoPersonGroup();
                $group = $em->getRepository('PostparcBundle:Group')->find($groupId);
                $ppg->setPerson($person);
                $ppg->setGroup($group);
                $em->persist($ppg);
            }
            $em->flush();
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'Person.actions.addGroup.success');
        }

        return $this->redirect($this->generateUrl('person_show', ['id' => $person->getId(),'activSubTab' => 'groupsInfosPerson']) . '#coordPerso');
    }

    /**
     * Batch actions.
     *
     * @param Request $request
     * @Route("/batch", name="person_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = $request->request->get('ids');
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();

        if ($ids) {
            switch ($request->request->get('batch_action')) {
                case 'batchDelete':
                    $em->getRepository('PostparcBundle:Person')->batchDelete($ids, $entityId, $this->getUser());
                    $this->addFlash('success', 'flash.deleteSuccess');

                    break;

                case 'batchAddBasket':
                    $session = $request->getSession();
                    $selectionData = [];
                    if ($session->has('selection')) {
                        $selectionData = $session->get('selection');

                        if (array_key_exists('personIds', $selectionData)) {
                            foreach ($ids as $id) {
                                if (!in_array($id, $selectionData['personIds'])) {
                                    $selectionData['personIds'][] = $id;
                                }
                            }
                        } else {
                            $selectionData['personIds'] = $ids;
                        }
                    } else {
                        $selectionData['personIds'] = $ids;
                    }

                    $session->set('selection', $selectionData);

                    $request->getSession()
                            ->getFlashBag()
                            ->add('success', 'Person.actions.addBasket.success');

                    break;
                case 'batchExportVcard':
                    $content = '';
                    $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
                    $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];
                    $persons = $em->getRepository('PostparcBundle:Person')->findBy(['id' => $ids]);
                    foreach ($persons as $person) {
                        $content .= $person->generateVcardContent($personnalFieldsRestriction);
                    }

                    if (strlen($content) !== 0) {
                        $response = new Response();
                        $response->setContent($content);
                        $response->setStatusCode(200);
                        $response->headers->set('Content-Type', 'text/x-vcard');
                        $response->headers->set('Content-Disposition', 'attachment; filename="massive_export_vcard.vcf"');
                        $response->headers->set('Content-Length', mb_strlen($content, 'utf-8'));

                        return $response;
                    }
                    break;
            }
        }

        return $this->redirectToRoute('person_index');
    }

    

    /**
     * Creates a form to delete a Person entity.
     *
     * @param Person $person The Person entity
     *
     * @return Form The form
     */
    private function createDeleteForm(Person $person)
    {
        return $this->createFormBuilder()
                        ->setAction($this->generateUrl('person_delete', ['id' => $person->getId()]))
                        ->setMethod('DELETE')
                        ->getForm()
        ;
    }
    
}

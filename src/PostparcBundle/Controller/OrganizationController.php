<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\Organization;
use PostparcBundle\Entity\OrganizationLink;
use PostparcBundle\Entity\Representation;
use PostparcBundle\Form\RepresentationType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Snappy\Pdf;

/**
 * Organization controller.
 *
 * @Route("/organization")
 */
class OrganizationController extends Controller
{
    /**
     * Lists all Organization entities.
     *
     * @param Request $request
     * @Route("/", name="organization_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\OrganizationFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('organizationFilter');

            return $this->redirect($this->generateUrl('organization_index'));
        }
        if ($request->request->has('organization_filter')) {
            $organizationFilter = $request->request->get('organization_filter');
            if (array_key_exists('city', $organizationFilter)) {
                $searchCityId = $organizationFilter['city'];
                unset($organizationFilter['city']);
                $request->request->set('organization_filter', $organizationFilter);
            }
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
                if ($filterForm->has('updatedBy') && $filterForm->get('updatedBy')->getData()) {
                    $filterData['updatedBy'] = $filterForm->get('updatedBy')->getData()->getId();
                }
                if ($filterForm->has('organizationType') && $filterForm->get('organizationType')->getData()) {
                    $filterData['organizationType'] = $filterForm->get('organizationType')->getData()->getId();
                    // recherche dans les sous organizationType
                    // recupération des sous organismeType
                    $filterData['organizationTypeIds'] = [];
                    $organizationType = $em->getRepository('PostparcBundle:OrganizationType')->find($filterData['organizationType']);
                    $subOrganizationTypes = $em->getRepository('PostparcBundle:OrganizationType')->getChildren($node = $organizationType, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                    foreach ($subOrganizationTypes as $subOrganizationType) {
                        $filterData['organizationTypeIds'][] = $subOrganizationType->getId();
                    }
                }
                if ($filterForm->has('abbreviation') && $filterForm->get('abbreviation')->getData()) {
                    $filterData['abbreviation'] = $filterForm->get('abbreviation')->getData();
                }
                if ($filterForm->has('siret') && $filterForm->get('siret')->getData()) {
                    $filterData['siret'] = $filterForm->get('siret')->getData();
                }
                if ($filterForm->has('tags') && null != $filterForm->get('tags')->getData()) {
                    $filterData['tags'] = $filterForm->get('tags')->getData();
                }
                if (isset($searchCityId) && $searchCityId) {
                    $filterData['city'] = $searchCityId;
                }
                $session->set('organizationFilter', $filterData);
            }
        } elseif ($session->has('organizationFilter')) {
            $filterData = $session->get('organizationFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\OrganizationFilterType');
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
            if (array_key_exists('updatedBy', $filterData)) {
                $updatedBy = $em->getRepository('PostparcBundle:User')->find($filterData['updatedBy']);
                $filterForm->get('updatedBy')->setData($updatedBy);
            }
            if (array_key_exists('organizationType', $filterData)) {
                $organizationType = $em->getRepository('PostparcBundle:OrganizationType')->find($filterData['organizationType']);
                $filterForm->get('organizationType')->setData($organizationType);
            }
            if (array_key_exists('abbreviation', $filterData)) {
                $filterForm->get('abbreviation')->setData($filterData['abbreviation']);
            }
            if (array_key_exists('siret', $filterData)) {
                $filterForm->get('siret')->setData($filterData['siret']);
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
            if (array_key_exists('city', $filterData)) {
                $city = $em->getRepository('PostparcBundle:City')->find($filterData['city']);
                $filterForm->get('city')->setData($city);
            }
        }

        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');

        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $query = $em->getRepository('PostparcBundle:Organization')->search($filterData, $entityId, $readerLimitations, $show_SharedContents);
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
            'defaultSortFieldName' => 'o.slug',
            'defaultSortDirection' => 'asc',
                ]
        );

        return $this->render('organization/index.html.twig', [
                    'pagination' => $pagination,
                    'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new Organization entity.
     *
     * @param Request $request
     * @Route("/new", name="organization_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $organization = new Organization();
        $organization->setEnv($this->container->get('kernel')->getEnvironment());
        $form = $this->createForm('PostparcBundle\Form\OrganizationType', $organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($organization);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('organization_show', ['id' => $organization->getId()]);
            } else {
                $organization = new Organization();
                $form = $this->createForm('PostparcBundle\Form\OrganizationType', $organization);
            }
        }

        return $this->render('organization/new.html.twig', [
                    'organization' => $organization,
                    'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @param Request $request
     * @Route("/batch", name="organization_batch", methods="POST")
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
                    $em = $this->getDoctrine()->getManager();
                    $em->getRepository('PostparcBundle:Organization')->batchDelete($ids, $entityId, $this->getUser());
                    $this->addFlash('success', 'flash.deleteMassiveSuccess');
                    break;

                case 'batchAddBasket':
                    $session = $request->getSession();
                    $selectionData = [];
                    if ($session->has('selection')) {
                        $selectionData = $session->get('selection');

                        if (array_key_exists('organizationIds', $selectionData)) {
                            foreach ($ids as $id) {
                                if (!in_array($id, $selectionData['organizationIds'])) {
                                    $selectionData['organizationIds'][] = $id;
                                }
                            }
                        } else {
                            $selectionData['organizationIds'] = $ids;
                        }
                    } else {
                        $selectionData['organizationIds'] = $ids;
                    }

                    $session->set('selection', $selectionData);

                    $request->getSession()
                            ->getFlashBag()
                            ->add('success', 'Organization.actions.addBasket.successMany');

                    break;
                case 'batchExportVcard':
                    $content = '';
                    $organisations = $em->getRepository('PostparcBundle:Organization')->findBy(['id' => $ids]);
                    foreach ($organisations as $organisation) {
                        $content .= $organisation->generateVcardContent();
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

        return $this->redirectToRoute('organization_index');
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @param Request $request
     * @param int     $organizationId
     * @Route("/{organizationId}/batchPfos", name="organizationPfos_batch", methods="POST")
     *
     * @return Response
     */
    public function pfosBatchAction(Request $request, $organizationId)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = $request->request->get('ids');
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();

        if ($ids) {
            switch ($request->request->get('batch_action')) {
                case 'batchDelete':
                    $em = $this->getDoctrine()->getManager();
                    $em->getRepository('PostparcBundle:Pfo')->batchDelete($ids, $entityId);
                    $this->addFlash('success', 'flash.deleteMassiveSuccess');
                    break;

                case 'batchAddBasket':
                    $session = $request->getSession();
                    $selectionData = [];
                    if ($session->has('selection')) {
                        $selectionData = $session->get('selection');

                        if (array_key_exists('pfoIds', $selectionData)) {
                            foreach ($ids as $id) {
                                if (!in_array($id, $selectionData['pfoIds'])) {
                                    $selectionData['pfoIds'][] = $id;
                                }
                            }
                        } else {
                            $selectionData['pfoIds'] = $ids;
                        }
                    } else {
                        $selectionData['pfoIds'] = $ids;
                    }

                    $session->set('selection', $selectionData);

                    $request->getSession()
                            ->getFlashBag()
                            ->add('success', 'Pfo.actions.addBasket.successMany');

                    break;

                case 'batchExportVcard':
                    $content = '';

                    $pfos = $em->getRepository('PostparcBundle:Pfo')->findBy(['id' => $ids]);
                    foreach ($pfos as $pfo) {
                        $content .= $pfo->generateVcardContent();
                    }

                    if (strlen($content) !== 0) {
                        $response = new Response();
                        $response->setContent($content);
                        $response->setStatusCode(200);
                        $response->headers->set('Content-Type', 'text/x-vcard');
                        $response->headers->set('Content-Disposition', 'attachment; filename="massive_export_Vcard.vcf"');
                        $response->headers->set('Content-Length', mb_strlen($content, 'utf-8'));

                        return $response;
                    }
                    break;
            }
        }

        return $this->redirectToRoute('organization_show', ['id' => $organizationId]);
    }

    /**
     * Finds and displays a Organization entity.
     *
     * @param Request      $request
     * @param Organization $organization
     * @Route("/{id}", name="organization_show", methods="GET", requirements={"id":"\d+"})
     *
     * @return Response
     */
    public function showAction(Request $request, Organization $organization)
    {
        $em = $this->getDoctrine()->getManager();

        $checkAccessService = $this->container->get('postparc.check_access');

        $activeTab = $request->query->has('activeTab') ? $request->query->get('activeTab') : 'personnalList';

        if (!$checkAccessService->checkAccess($organization)) {
            $referer = $request->headers->get('referer');
            $this->addFlash('info', 'flash.accessDenied');

            return $referer ? $this->redirect($referer) : $this->redirectToRoute('organization_index');
        }

        // récupération des organismes liés suivant type de relation service
        $subServiceOrganizations = $em->getRepository('PostparcBundle:Organization')->getSubServiceOrganizations($organization->getId());

        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $queryPerson = $em->getRepository('PostparcBundle:Pfo')->getOrganizationPfos($organization->getId(), $entityId, $readerLimitations, $show_SharedContents, $subServiceOrganizations);

        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $queryPerson, /* query NOT result */
            $request->query->getInt('page', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page)/* limit per page */
        );
        $organizationLink = new OrganizationLink();
        $organizationLink->setOrganizationOrigin($organization);
        $formOrganizationLink = $this->createForm('PostparcBundle\Form\OrganizationLinkType', $organizationLink, [
            'action' => $this->generateUrl('add_organizationLink', ['id' => $organization->getId()]),
            'method' => 'POST',
        ]);

        // récupération des organismes liés (horizontales->frères)
        $linkedOrganisations = [];
        $organizationHorizontalLinks = $em->getRepository('PostparcBundle:OrganizationLink')->getOrganisationHorizontalLink($organization->getId());
        foreach ($organizationHorizontalLinks as $organizationHorizontalLink) {
            if (!in_array($organizationHorizontalLink, $linkedOrganisations)) {
                $linkedOrganisations[] = $organizationHorizontalLink;
            }
        }
        // récupération des organismes liés (verticaux->parents)
        $organizationParentLinks = $em->getRepository('PostparcBundle:OrganizationLink')->getOrganisationParents($organization->getId());
        foreach ($organizationParentLinks as $organizationParentLink) {
            if (!in_array($organizationParentLink, $linkedOrganisations)) {
                $linkedOrganisations[] = $organizationParentLink;
            }
        }
        // récupération des organismes liés (enfants)
        $organizationChildreenLinks = $em->getRepository('PostparcBundle:OrganizationLink')->getOrganisationChildreens($organization->getId());
        foreach ($organizationChildreenLinks as $organizationChildreenLink) {
            if (!in_array($organizationChildreenLink, $linkedOrganisations)) {
                $linkedOrganisations[] = $organizationChildreenLink;
            }
        }

        // recuperations des representations visibles par l'utilisateur
        $representations = $em->getRepository('PostparcBundle:Representation')->getOrganizationRepresentation($organization->getId(), $entityId, $show_SharedContents, $subServiceOrganizations);

        // récupération de l'ensemble des résultats
        $allPersons = $queryPerson->execute();
        
        // Generate QRCode
        $qrCodeService = $this->container->get('postparc_qrCodeService');
        $qrCodeInfos = $qrCodeService->generateVcardQrCode($organization);

        return $this->render('organization/show.html.twig', [
                    'organization' => $organization,
                    'pfos' => $pagination,
                    'linkedOrganisations' => $linkedOrganisations,
                    'formOrganizationLink' => $formOrganizationLink->createView(),
                    'representations' => $representations,
                    'subServiceOrganizations' => $subServiceOrganizations,
                    'allPersons' => $allPersons,
                    'activeTab' => $activeTab,
                    'qrCodeUri' => $qrCodeInfos['uri']
        ]);
    }

    /**
     * Displays a print format of a Organization entity.
     *
     * @param Request $request
     * @param Organization  $organization
     * @Route("/{id}/print", name="organization_print", methods="GET")
     *
     * @return Response
     */
    public function printAction(Request $request, Organization $organization)
    {
        $em = $this->getDoctrine()->getManager();

        $checkAccessService = $this->container->get('postparc.check_access');
        if (!$checkAccessService->checkAccess($organization)) {
            $referer = $request->headers->get('referer');
            $this->addFlash('info', 'flash.accessDenied');

            return $referer ? $this->redirect($referer) : $this->redirectToRoute('organization_index');
        }
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();

        // récupération des organismes liés suivant type de relation service
        $subServiceOrganizations = $em->getRepository('PostparcBundle:Organization')->getSubServiceOrganizations($organization->getId());

        // récupération des organismes liés (horizontales->frères)
        $linkedOrganisations = [];
        $organizationHorizontalLinks = $em->getRepository('PostparcBundle:OrganizationLink')->getOrganisationHorizontalLink($organization->getId());
        foreach ($organizationHorizontalLinks as $organizationHorizontalLink) {
            if (!in_array($organizationHorizontalLink, $linkedOrganisations)) {
                $linkedOrganisations[] = $organizationHorizontalLink;
            }
        }
        // récupération des organismes liés (verticaux->parents)
        $organizationParentLinks = $em->getRepository('PostparcBundle:OrganizationLink')->getOrganisationParents($organization->getId());
        foreach ($organizationParentLinks as $organizationParentLink) {
            if (!in_array($organizationParentLink, $linkedOrganisations)) {
                $linkedOrganisations[] = $organizationParentLink;
            }
        }
        // récupération des organismes liés (enfants)
        $organizationChildreenLinks = $em->getRepository('PostparcBundle:OrganizationLink')->getOrganisationChildreens($organization->getId());
        foreach ($organizationChildreenLinks as $organizationChildreenLink) {
            if (!in_array($organizationChildreenLink, $linkedOrganisations)) {
                $linkedOrganisations[] = $organizationChildreenLink;
            }
        }

        // recuperations des representations visibles par l'utilisateur
        $representations = $em->getRepository('PostparcBundle:Representation')->getOrganizationRepresentation($organization->getId(), $entityId, $show_SharedContents, $subServiceOrganizations);

        // récupération de l'ensemble des résultats
        $queryPfos = $em->getRepository('PostparcBundle:Pfo')->getOrganizationPfos($organization->getId(), $entityId, $readerLimitations, $show_SharedContents, $subServiceOrganizations);
        $pfos = $queryPfos->execute();

        //$path = $this->getParameter('kernel.project_dir');
        $path = $request->getScheme() . '://' . $request->getHost();
        $html = $this->renderView('organization/print.html.twig', [
          'path' => $path,
          'organization' => $organization,
          'pfos' => $pfos,
          'linkedOrganisations' => $linkedOrganisations,
          'representations' => $representations,
          'subServiceOrganizations' => $subServiceOrganizations,
          ]);


        $vendorPath = $this->getParameter('kernel.project_dir'); 
        //$pdf = new Pdf($vendorPath . '/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64');
        $pdf = new Pdf($vendorPath . '/tools/wkhtmltopdf');
        $pdf->setOption('print-media-type', false);
        $pdf->setOption('header-spacing', 10);
        $pdf->setOption('footer-spacing', 10);
        $pdf->setOption('margin-bottom', 30);

        $filename = $organization->getSlug();

        $footerHtml = $this->renderView('default/pdfFooter.html.twig', [
          'path' => $path,
          'now' => new \DateTime()
          ]);

        $options = [
            'encoding' => 'UTF-8',
            'footer-html' => $footerHtml
        ];

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
     * Displays a form to edit an existing Organization entity.
     *
     * @param Request      $request
     * @param Organization $organization
     * @Route("/{id}/edit", name="organization_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, Organization $organization)
    {
        $lockService = $this->container->get('postparc.lock_service');
        if ($lockService->isLock($organization, $this->getParameter('maxLockDuration'))) {
            return $this->redirect($this->generateUrl('lock-message', ['className' => $organization->getClassName(), 'objectId' => $organization->getId()]));
        }
        $deleteForm = $this->createDeleteForm($organization);
        $oldImage = $organization->getImage();
        $editForm = $this->createForm('PostparcBundle\Form\OrganizationType', $organization);
        $editForm->handleRequest($request);
        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if (!$request->files->has('image') && $oldImage && !$request->request->has('deleteImage')) {
                $organization->setImage($oldImage);
            }
            $em = $this->getDoctrine()->getManager();
            // update name of associate representations
            if ($organization->getRepresentations()) {
                foreach ($organization->getRepresentations() as $representation) {
                    $representation->updateName();
                    $em->persist($representation);
                }
            }
            $em->persist($organization);
            $em->flush();

            $this->addFlash('success', 'flash.editSuccess');
            $lockService->unlockObject($organization);

            if ($origin) {
                return $this->redirect($origin);
            }

            return $this->redirectToRoute('organization_show', ['id' => $organization->getId(), 'origin' => $origin]);
        }

        return $this->render('organization/edit.html.twig', [
                    'organization' => $organization,
                    'edit_form' => $editForm->createView(),
                    'origin' => $origin,
                    'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Organization entity.
     *
     * @param Organization $organization
     * @param Request      $request
     * @Route("/{id}/delete", name="organization_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction(Organization $organization, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$organization->getId()];
        $isDelete = $organization->getDeletedAt();
        $coordinate = $organization->getCoordinate();
        $em->getRepository('PostparcBundle:Organization')->batchDelete($ids, null, $this->getUser());
        if ($coordinate && $isDelete) {
            $email = $coordinate->getEmail();
            $em->getRepository('PostparcBundle:Coordinate')->delete($coordinate->getId());
            if ($email) {
                $em->getRepository('PostparcBundle:Email')->delete($email->getId());
            }
            // suppression des representations liées
            $representations = $organization->getRepresentations();
            foreach($representations as $representation){
                $em->remove($representation);
            }
        }

        $this->addFlash('success', 'flash.deleteSuccess');

        if ($request->query->has('origin')) {
            switch ($request->query->get('origin')) {
                case 'search':
                    return $this->redirectToRoute('search');
                default:
                    return $this->redirectToRoute('organization_index');
            }
        }

        return $this->redirectToRoute('organization_index');
    }

    /**
     * add this personn to the basket.
     *
     * @param Organization $organization
     * @param Request      $request
     * @Route("/{id}/addBasket", name="organization_addBasket", methods="GET")
     *
     * @return Response
     */
    public function addToBasketAction(Organization $organization, Request $request)
    {
        $session = $request->getSession();
        $selectionData = [];
        $alreadyExist = false;
        // Get filter from session
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            if (array_key_exists('organizationIds', $selectionData)) {
                if (!in_array($organization->getId(), $selectionData['organizationIds'])) {
                    $selectionData['organizationIds'][] = $organization->getId();
                } else {
                    $alreadyExist = true;
                }
            } else {
                $selectionData['organizationIds'] = [$organization->getId()];
            }
        } else {
            $selectionData['organizationIds'] = [$organization->getId()];
        }

        $session->set('selection', $selectionData);

        if ($alreadyExist) {
            $request->getSession()
                    ->getFlashBag()
                    ->add('error', 'Organization.actions.addBasket.alreadyPresent');
        } else {
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'Organization.actions.addBasket.success');
        }

        if ($request->query->has('origin')) {
            switch ($request->query->get('origin')) {
                case 'fiche':
                    return $this->redirectToRoute('organization_show', ['id' => $organization->getId()]);
                case 'search':
                    return $this->redirectToRoute('search', ['activeTab' => 'organizations']);
            }
        }

        return $this->redirectToRoute('organization_index');
    }

    /**
     * add new representation to one Organization.
     *
     * @param Organization $organization
     * @param Request      $request
     * @Route("/{id}/addNewRepresentation", name="organization_addNewRepresentation", methods="GET|POST")
     *
     * @return Response
     */
    public function addNewRepresentationAction(Organization $organization, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $representation = new Representation();
        $representation->setOrganization($organization);
        $personOrPfoTobeAdded = null;
        $personId = null;
        $pfoId = null;
        if ($request->query->has('personId')) {
            $personOrPfoTobeAdded = $em->getRepository('PostparcBundle:Person')->find($request->query->get('personId'));
            $personId = $request->query->get('personId');
            $representation->setPerson($personOrPfoTobeAdded);
        }
        if ($request->query->has('pfoId')) {
            $personOrPfoTobeAdded = $em->getRepository('PostparcBundle:Pfo')->find($request->query->get('pfoId'));
            $pfoId = $request->query->get('pfoId');
            $representation->setPfo($personOrPfoTobeAdded);
        }

        $form = $this->createForm(RepresentationType::class, $representation, [
            'action' => $this->generateUrl('organization_addNewRepresentation', ['id' => $organization->getId()]) . '?personId=' . $personId . '&pfoId=' . $pfoId,
            'method' => 'POST',
        ]);

        $form->add('save', SubmitType::class, ['label' => 'actions.save', 'attr' => ['class' => 'btn btn-primary']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($representation);
                $em->flush();

                $this->addFlash('success', 'flash.addSuccess');

                return $this->redirectToRoute('organization_show', ['id' => $organization->getId(), 'activeTab' => 'representations']);
            }
        }

        return $this->render('organization/addNewRepresentation.html.twig', [
                    'representation' => $representation,
                    'personOrPfoTobeAdded' => $personOrPfoTobeAdded,
                    'organization' => $organization,
                    'form' => $form->createView(),
        ]);
    }

    

    /**
     * add this organization to the the.
     *
     * @Route("/{id}/addGroup", name="organization_addGroup", methods="GET|POST")
     *
     * @param Request      $request
     * @param Organization $organization
     * @param string       $origin
     *
     * @return Response
     */
    public function addGroupAction(Request $request, Organization $organization)
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->request->has('groupIds')) {
            foreach ($request->request->get('groupIds') as $groupId) {
                $group = $em->getRepository('PostparcBundle:Group')->find($groupId);
                $organization->addGroup($group);
                $em->persist($organization);
            }
            $em->flush();
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'Organization.actions.addToGroup.success');
        }
        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';
        if ($origin) {
            return $this->redirect($origin);
        }

        return $this->redirectToRoute('organization_show', ['id' => $organization->getId(), 'activeTab' => 'groups']);
    }

    /**
     * remove this organization to one group.
     *
     * @param Request      $request
     * @param Organization $organization
     * @Route("/{id}/removeToGroup/{groupId}", name="organization_removeFromGroup", methods="GET")
     *
     * @return Response
     */
    public function removeToGroupAction(Organization $organization, $groupId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('PostparcBundle:Group')->find($groupId);
        $organization->removeGroup($group);
        $em->persist($organization);
        $em->flush();

        $request->getSession()
                ->getFlashBag()
                ->add('success', 'Organization.actions.removeFromGroup.success');

        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';
        if ($origin) {
            return $this->redirect($origin);
        }

        return $this->redirectToRoute('organization_show', ['id' => $organization->getId(), 'activeTab' => 'groups']);
    }

    /**
     * Creates a form to delete a Organization entity.
     *
     * @param Organization $organization The Organization entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Organization $organization)
    {
        return $this->createFormBuilder()
                        ->setAction($this->generateUrl('organization_delete', ['id' => $organization->getId()]))
                        ->setMethod('DELETE')
                        ->getForm()
        ;
    }
}

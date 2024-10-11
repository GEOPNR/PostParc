<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\SearchList;

class SearchController extends Controller
{
    /**
     * @Route("/search", name="search-homepage"))
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $userConfigs = $this->getUser()->getConfigs();

        if (!substr_count($request->headers->get('referer'), 'searchList') && array_key_exists('empty_search_on_load', $userConfigs) && $userConfigs['empty_search_on_load'] == true) {
            $searchParams = [];
        } else {
            $searchParams = $session->has('searchParams') ? $session->get('searchParams') : null;
        }

        $searchParamsObjects = [];
        if (isset($searchParams['functionIds']) && count($searchParams['functionIds'])) {
            $searchParamsObjects['functions'] = $em->getRepository('PostparcBundle:PersonFunction')->findBy(['id' => $searchParams['functionIds']]);
        }
        if (isset($searchParams['organizationIds']) && count($searchParams['organizationIds'])) {
            $searchParamsObjects['organizations'] = $em->getRepository('PostparcBundle:Organization')->findBy(['id' => $searchParams['organizationIds']]);
        }
        if (isset($searchParams['organizationTypeIds']) && count($searchParams['organizationTypeIds'])) {
            $searchParamsObjects['organizationsTypes'] = $em->getRepository('PostparcBundle:OrganizationType')->findBy(['id' => $searchParams['organizationTypeIds']]);
        }
        if (isset($searchParams['cityIds']) && count($searchParams['cityIds'])) {
            $searchParamsObjects['cities'] = $em->getRepository('PostparcBundle:City')->findBy(['id' => $searchParams['cityIds']]);
        }
        if (isset($searchParams['departmentIds']) && count($searchParams['departmentIds'])) {
            $searchParamsObjects['departments'] = $em->getRepository('PostparcBundle:City')->autoCompleteDepartment(null, $searchParams['departmentIds']);
        }
        if (isset($searchParams['territoryIds']) && count($searchParams['territoryIds'])) {
            $searchParamsObjects['territories'] = $em->getRepository('PostparcBundle:Territory')->findBy(['id' => $searchParams['territoryIds']]);
        }
        if (isset($searchParams['groupIds']) && count($searchParams['groupIds'])) {
            $searchParamsObjects['groups'] = $em->getRepository('PostparcBundle:Group')->findBy(['id' => $searchParams['groupIds']]);
        }
        if (isset($searchParams['serviceIds']) && count($searchParams['serviceIds'])) {
            $searchParamsObjects['services'] = $em->getRepository('PostparcBundle:Service')->findBy(['id' => $searchParams['serviceIds']]);
        }
        if (isset($searchParams['tagIds']) && count($searchParams['tagIds'])) {
            $searchParamsObjects['tags'] = $em->getRepository('PostparcBundle:Tag')->findBy(['id' => $searchParams['tagIds']]);
        }
        if (isset($searchParams['professionIds']) && count($searchParams['professionIds'])) {
            $searchParamsObjects['professions'] = $em->getRepository('PostparcBundle:Profession')->findBy(['id' => $searchParams['professionIds']]);
        }
        if (isset($searchParams['mandateTypeIds']) && count($searchParams['mandateTypeIds'])) {
            $searchParamsObjects['mandateTypes'] = $em->getRepository('PostparcBundle:MandateType')->findBy(['id' => $searchParams['mandateTypeIds']]);
        }
        if (isset($searchParams['createdByIds']) && count($searchParams['createdByIds'])) {
            $searchParamsObjects['createdBys'] = $em->getRepository('PostparcBundle:User')->findBy(['id' => $searchParams['createdByIds']]);
        }
        if (isset($searchParams['createdByEntitiesIds']) && count($searchParams['createdByEntitiesIds'])) {
            $searchParamsObjects['createdByEntities'] = $em->getRepository('PostparcBundle:Entity')->findBy(['id' => $searchParams['createdByEntitiesIds']]);
        }

        return $this->render('search/index.html.twig', [
                    'searchParams' => $searchParams,
                    'searchParamsObjects' => $searchParamsObjects,
        ]);
    }

    /**
     * @Route("/search/results", name="search"), methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $translator = $this->get('translator');
        $postRequest = $request->request;
        $paginator = $this->get('knp_paginator');
        $session = $request->getSession();
        $nbElements = 0;
        $filterAdvancedSearch = 0;
        $representations = [];

        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;

        // gestion chargement page recherche sans params
        if (!$postRequest->has('filterAdvancedSearch') && $session->has('searchParams')) {
            $searchParams = $session->get('searchParams');
            $filterAdvancedSearch = $searchParams['filterAdvancedSearch'];
        } else {
            $filterAdvancedSearch = $postRequest->get('filterAdvancedSearch');
        }

        // gestion tabs
        $activeTab = 'persons'; // default value
        if(isset($currentEntityConfig['tabsOrder'])) {
            $tabsOrder = $currentEntityConfig['tabsOrder'];
            $activeTab = array_key_first($tabsOrder);
        }        
        if ($request->query->has('activeTab')) {
            $activeTab = $request->query->get('activeTab');
        }

        // gestion recherche depuis homepage
        if ($postRequest->has('searchFromHomepage')) {
            $filterAdvancedSearch = 0;
        }

        if (1 != $filterAdvancedSearch) { //simple search
            if (isset($searchParams) && !($postRequest->has('filterFullText'))) { // recuperation depuis la session
                $q = trim($searchParams['q']);
            } else {
                $q = trim($postRequest->get('filterFullText'));
            }

            $searchType = 'simple';
            $title = $translator->trans('Search.label');
            $searchParams = [
                'filterAdvancedSearch' => $filterAdvancedSearch,
                'q' => $q,
            ];
            // stockage des élements de recherche en session
            $session->set('searchParams', $searchParams);
        } else { // advanced search
            $title = $translator->trans('AdvancedSearch.result');
            $searchType = 'advanced';
            // recupération des champs de recherche avancé
            if (!isset($searchParams)) {
                $searchParams = [
                    'filterAdvancedSearch' => $postRequest->get('filterAdvancedSearch'),
                    'functionIds' => $postRequest->get('filterFunction'),
                    'function_exclusion' => $postRequest->get('filterFunction_exclusion'),
                    'serviceIds' => $postRequest->get('filterService'),
                    'service_exclusion' => $postRequest->get('filterService_exclusion'),
                    'organizationTypeIds' => $postRequest->get('filterOrganizationType'),
                    'organizationType_exclusion' => $postRequest->get('filterOrganizationType_exclusion'),
                    'organizationType_sub' => $postRequest->get('filterOrganizationType_sub'),
                    'organizationIds' => $postRequest->get('filterOrganization'),
                    'organization_exclusion' => $postRequest->get('filterOrganization_exclusion'),
                    'organization_includeOrganizationLinked' => $postRequest->get('filterOrganization_includeOrganizationLinked'),
                    'organization_includeSubServiceOrganizations' => $postRequest->get('filterOrganization_includeSubServiceOrganizations'),
                    'territoryIds' => $postRequest->get('filterTerritory'),
                    'territory_exclusion' => $postRequest->get('filterTerritory_exclusion'),
                    'territory_sub' => $postRequest->get('filterTerritory_sub'),
                    'cityIds' => $postRequest->get('filterCity'),
                    'city_exclusion' => $postRequest->get('filterCity_exclusion'),
                    'departmentIds' => $postRequest->get('filterDepartment'),
                    'department_exclusion' => $postRequest->get('filterDepartment_exclusion'),
                    'groupIds' => $postRequest->get('filterGroup'),
                    'group_exclusion' => $postRequest->get('filterGroup_exclusion'),
                    'group_sub' => $postRequest->get('filterGroup_sub'),
                    'tagIds' => $postRequest->get('filterTag'),
                    'tag_exclusion' => $postRequest->get('filterTag_exclusion'),
                    'tag_sub' => $postRequest->get('filterTag_sub'),
                    'professionIds' => $postRequest->get('filterProfession'),
                    'profession_exclusion' => $postRequest->get('filterProfession_exclusion'),
                    'mandateTypeIds' => $postRequest->get('filterMandateType'),
                    'mandateType_sub' => $postRequest->get('filterMandateType_sub'),
                    'mandateType_exclusion' => $postRequest->get('filterMandateType_exclusion'),
                    'maxUpdatedDate' => $postRequest->get('filterMaxUpdatedDate'),
                    'createdByIds' => $postRequest->get('filterCreatedBy'),
                    'createdByEntitiesIds' => $postRequest->get('filterCreatedByEntities'),
                    'observation' => $postRequest->get('filterObservation'),
                ];

                // stockage des élements de recherche en session
                $session->set('searchParams', $searchParams);
            }
        }

        // récupération des requêtes
        list($queryPersons, $queryPfos, $queryOrganizations, $queryRepresentations) = $this->getSearchQueries($searchParams, $request);

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
            'defaultSortFieldName' => 'p.name',
            'defaultSortDirection' => 'asc',
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
            'defaultSortFieldName' => 'p.name',
            'defaultSortDirection' => 'asc',
                ]
        );
        $pfos->setParam('activeTab', 'pfos');
        $nbElements += $pfos->getTotalItemCount();

        // organizations
        $organizations = $paginator->paginate(
            $queryOrganizations, /* query NOT result */
            $request->query->getInt('pageOrganization', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
            'pageParameterName' => 'pageOrganization',
            'sortFieldParameterName' => 'sortOrganization',
            'sortDirectionParameterName' => 'directionOrganization',
            'defaultSortFieldName' => 'o.name',
            'defaultSortDirection' => 'asc',
                ]
        );
        $organizations->setParam('activeTab', 'organizations');
        $nbElements += $organizations->getTotalItemCount();

        // representations
        if ($currentEntityConfig['use_representation_module']) {
            $representations = $paginator->paginate(
                $queryRepresentations, /* query NOT result */
                $request->query->getInt('pageRepresentation', 1)/* page number */,
                $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
                [
                'pageParameterName' => 'pageRepresentation',
                'sortFieldParameterName' => 'sortRepresentation',
                'sortDirectionParameterName' => 'directionRepresentation',
                'defaultSortFieldName' => 'r.slug',
                'defaultSortDirection' => 'asc',
                    ]
            );
            $representations->setParam('activeTab', 'representations');
            $nbElements += $representations->getTotalItemCount();
        }

        // chargement des objets liés à la recherche pour affichage du bloc criteria
        $searchParamsObjects = [];
        if (isset($searchParams['functionIds']) && count($searchParams['functionIds'])) {
            $searchParamsObjects['functions'] = $em->getRepository('PostparcBundle:PersonFunction')->findBy(['id' => $searchParams['functionIds']]);
        }
        if (isset($searchParams['serviceIds']) && count($searchParams['serviceIds'])) {
            $searchParamsObjects['services'] = $em->getRepository('PostparcBundle:Service')->findBy(['id' => $searchParams['serviceIds']]);
        }
        if (isset($searchParams['organizationTypeIds']) && count($searchParams['organizationTypeIds'])) {
            $searchParamsObjects['organizationTypes'] = $em->getRepository('PostparcBundle:OrganizationType')->findBy(['id' => $searchParams['organizationTypeIds']]);
        }
        if (isset($searchParams['organizationIds']) && count($searchParams['organizationIds'])) {
            $searchParamsObjects['organizations'] = $em->getRepository('PostparcBundle:Organization')->findBy(['id' => $searchParams['organizationIds']]);
        }
        if (isset($searchParams['territoryIds']) && count($searchParams['territoryIds'])) {
            $searchParamsObjects['territories'] = $em->getRepository('PostparcBundle:Territory')->findBy(['id' => $searchParams['territoryIds']]);
        }
        if (isset($searchParams['cityIds']) && count($searchParams['cityIds'])) {
            $searchParamsObjects['cities'] = $em->getRepository('PostparcBundle:City')->findBy(['id' => $searchParams['cityIds']]);
        }
        if (isset($searchParams['departmentIds']) && count($searchParams['departmentIds'])) {
            $searchParamsObjects['departments'] = $em->getRepository('PostparcBundle:City')->autoCompleteDepartment(null, $searchParams['departmentIds']);
        }
        if (isset($searchParams['groupIds']) && count($searchParams['groupIds'])) {
            $searchParamsObjects['groups'] = $em->getRepository('PostparcBundle:Group')->findBy(['id' => $searchParams['groupIds']]);
        }
        if (isset($searchParams['tagIds']) && count($searchParams['tagIds'])) {
            $searchParamsObjects['tags'] = $em->getRepository('PostparcBundle:Tag')->findBy(['id' => $searchParams['tagIds']]);
        }
        if (isset($searchParams['professionIds']) && count($searchParams['professionIds'])) {
            $searchParamsObjects['professions'] = $em->getRepository('PostparcBundle:Profession')->findBy(['id' => $searchParams['professionIds']]);
        }
        if (isset($searchParams['mandateTypeIds']) && count($searchParams['mandateTypeIds'])) {
            $searchParamsObjects['mandateTypes'] = $em->getRepository('PostparcBundle:MandateType')->findBy(['id' => $searchParams['mandateTypeIds']]);
        }
        if (isset($searchParams['createdByIds']) && count($searchParams['createdByIds'])) {
            $searchParamsObjects['createdBys'] = $em->getRepository('PostparcBundle:User')->findBy(['id' => $searchParams['createdByIds']]);
        }
        if (isset($searchParams['createdByEntitiesIds']) && count($searchParams['createdByEntitiesIds'])) {
            $searchParamsObjects['createdByEntities'] = $em->getRepository('PostparcBundle:Entity')->findBy(['id' => $searchParams['createdByEntitiesIds']]);
        }

        // récupération des searchLists
        $searchListCriteria = [];
        if (false === $this->isGranted('ROLE_CONTRIBUTOR')) {
            $searchListCriteria = ['createdBy' => $this->getUser()];
        }
        $searchListCriteria['currentUser'] = $this->getUser();
        $searchLists = $em->getRepository('PostparcBundle:SearchList')->search($searchListCriteria, $entityId, $show_SharedContents, 'sl.name')->getResult();

        // récupération de l'ensemble des résultats
        $allResults = [
            'persons' => $queryPersons->execute(),
            'pfos' => $queryPfos->execute(),
            'organizations' => $queryOrganizations->execute(),
        ];

        if ($currentEntityConfig['use_representation_module']) {
            $allResults['representations'] = $queryRepresentations->execute();
        }

        return $this->render('search/result.html.twig', [
                    'title' => $title,
                    'searchType' => $searchType,
                    'activeTab' => $activeTab,
                    'persons' => $persons,
                    'pfos' => $pfos,
                    'organizations' => $organizations,
                    'representations' => $representations,
                    'allResults' => $allResults,
                    'nbElements' => $nbElements,
                    'searchParams' => $searchParams,
                    'searchLists' => $searchLists,
                    'searchParamsObjects' => $searchParamsObjects,
        ]);
    }

    /**
     * @Route("/clearSearchParams", name="clearSearchParams"))
     *
     * @param Request $request
     *
     * @return Response
     */
    public function clearSearchParamsAction(Request $request)
    {
        $session = $request->getSession();
        $session->set('searchParams', []);
        $request->getSession()
                ->getFlashBag()
                ->add('success', 'flash.clear_criteria_success');

        return $this->redirectToRoute('search-homepage');
    }

    /**
     * @Route("/search/batch", name="search_batch", methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        switch ($request->request->get('batch_action')) {
            case 'batchAddBasket':
                $session = $request->getSession();
                $selectionData = [];
                if ($session->has('selection')) {
                    $selectionData = $session->get('selection');
                }
                // persons
                if ($request->request->has('personIds')) {
                    $personIds = $request->request->get('personIds');
                    if (array_key_exists('personIds', $selectionData)) {
                        foreach ($personIds as $id) {
                            if (!in_array($id, $selectionData['personIds'])) {
                                $selectionData['personIds'][] = $id;
                            }
                        }
                    } else {
                        $selectionData['personIds'] = $personIds;
                    }
                }
                // pfos
                if ($request->request->has('pfoIds')) {
                    $pfoIds = $request->request->get('pfoIds');
                    if (array_key_exists('pfoIds', $selectionData)) {
                        foreach ($pfoIds as $id) {
                            if (!in_array($id, $selectionData['pfoIds'])) {
                                $selectionData['pfoIds'][] = $id;
                            }
                        }
                    } else {
                        $selectionData['pfoIds'] = $pfoIds;
                    }
                }
                // organizationsorganizationIds
                if ($request->request->has('organizationIds')) {
                    $organizationIds = $request->request->get('organizationIds');
                    if (array_key_exists('organizationIds', $selectionData)) {
                        foreach ($organizationIds as $id) {
                            if (!in_array($id, $selectionData['organizationIds'])) {
                                $selectionData['organizationIds'][] = $id;
                            }
                        }
                    } else {
                        $selectionData['organizationIds'] = $organizationIds;
                    }
                }
                // representations
                if ($request->request->has('representationIds')) {
                    $representationIds = $request->request->get('representationIds');
                    if (array_key_exists('representationIds', $selectionData)) {
                        foreach ($representationIds as $id) {
                            if (!in_array($id, $selectionData['representationIds'])) {
                                $selectionData['representationIds'][] = $id;
                            }
                        }
                    } else {
                        $selectionData['representationIds'] = $representationIds;
                    }
                }

                $session->set('selection', $selectionData);
                $request->getSession()
                        ->getFlashBag()
                        ->add('success', 'flash.addSuccess');
                break;

            case 'batchExportVcard':
                $em = $this->getDoctrine()->getManager();
                $content = '';
                if ($request->request->has('personIds')) {
                    $persons = $em->getRepository('PostparcBundle:Person')->findBy(['id' => $request->request->get('personIds')]);
                    foreach ($persons as $person) {
                        $content .= $person->generateVcardContent();
                    }
                }
                if ($request->request->has('pfoIds')) {
                    $pfos = $em->getRepository('PostparcBundle:Pfo')->findBy(['id' => $request->request->get('pfoIds')]);
                    foreach ($pfos as $pfo) {
                        $content .= $pfo->generateVcardContent();
                    }
                }
                if ($request->request->has('organizationIds')) {
                    $organisations = $em->getRepository('PostparcBundle:Organization')->findBy(['id' => $request->request->get('organizationIds')]);
                    foreach ($organisations as $organisation) {
                        $content .= $organisation->generateVcardContent();
                    }
                }
                if ($request->request->has('representationIds')) {
                    $representations = $em->getRepository('PostparcBundle:Representation')->findBy(['id' => $request->request->get('representationIds')]);
                    foreach ($representations as $representation) {
                        $content .= $representation->generateVcardContent();
                    }
                }
                if (strlen($content) !== 0) {
                    $response = new Response();
                    $response->setContent($content);
                    $response->setStatusCode(200);
                    $response->headers->set('Content-Type', 'text/x-vcard');
                    $response->headers->set('Content-Disposition', 'attachment; filename="massive_export_Vcad.vcf"');
                    $response->headers->set('Content-Length', mb_strlen($content, 'utf-8'));

                    return $response;
                }
                break;
        }

        return $this->redirectToRoute('search');
    }

    /**
     * add searchList result to the selection.
     *
     * @Route("/searchList/{id}/addToSelection", name="searchList_addToSelection", methods="GET")
     *
     * @param Request    $request
     * @param SearchList $searchList
     *
     * @return Response
     */
    public function searchList_addToSelection(Request $request, SearchList $searchList)
    {
        $session = $request->getSession();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $searchParams = $searchList->getSearchParams();

        // récupération des requêtes
        list($queryPersons, $queryPfos, $queryOrganizations, $queryRepresentations) = $this->getSearchQueries($searchParams, $request);

        // lancement des requetes et stockage en sessions
        $selectionData = [];
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
        }
        if (!array_key_exists('personIds', $selectionData)) {
            $selectionData['personIds'] = [];
        }
        if (!array_key_exists('pfoIds', $selectionData)) {
            $selectionData['pfoIds'] = [];
        }
        if (!array_key_exists('organizationIds', $selectionData)) {
            $selectionData['organizationIds'] = [];
        }
        if ($currentEntityConfig['use_representation_module'] && !array_key_exists('representationIds', $selectionData)) {
            $selectionData['representationIds'] = [];
        }

        // persons
        if (!(isset($searchParams['functionIds']) && $searchParams['functionIds'] || isset($searchParams['serviceIds']) && $searchParams['serviceIds'] || isset($searchParams['organizationIds']) && $searchParams['organizationIds'] || isset($searchParams['organizationTypeIds']) && $searchParams['organizationTypeIds']) && count($queryPersons->getResult())) {
            foreach ($queryPersons->getResult() as $person) {
                if (!in_array($person->getId(), $selectionData['personIds'])) {
                    $selectionData['personIds'][] = $person->getId();
                } else {
                    $selectionData['personIds'] = [$person->getId()];
                }
            }
        }
        // pfo
        foreach ($queryPfos->getResult() as $pfo) {
            if (!in_array($pfo->getId(), $selectionData['pfoIds'])) {
                $selectionData['pfoIds'][] = $pfo->getId();
            } else {
                $selectionData['pfoIds'] = [$pfo->getId()];
            }
        }
        // organizations
        if (!(isset($searchParams['functionIds']) && $searchParams['functionIds']) && !(isset($searchParams['serviceIds']) && $searchParams['serviceIds']) && count($queryOrganizations->getResult())) {
            foreach ($queryOrganizations->getResult() as $organization) {
                if (!in_array($organization->getId(), $selectionData['organizationIds'])) {
                    $selectionData['organizationIds'][] = $organization->getId();
                } else {
                    $selectionData['organizationIds'] = [$organization->getId()];
                }
            }
        }
        // representations
        if ($currentEntityConfig['use_representation_module']) {
            foreach ($queryRepresentations->getResult() as $representation) {
                if (!in_array($representation->getId(), $selectionData['representationIds'])) {
                    $selectionData['representationIds'][] = $representation->getId();
                } else {
                    $selectionData['organizationIds'] = [$representation->getId()];
                }
            }
        }

        $session->set('selection', $selectionData);
        $this->addFlash('success', 'SearchList.actions.addBasket.success');

        return $this->redirectToRoute('searchList_index');
    }

    /**
     * add all of elements of one result tab to the selection.
     *
     * @Route("/{type}/addTabToSelection", name="search_addTabToSelection", options={"expose" = true}, methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addTabToSelectionAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $searchParams = $session->has('searchParams') ? $session->get('searchParams') : null;

        list($queryPersons, $queryPfos, $queryOrganizations, $queryRepresentations) = $this->getSearchQueries($searchParams, $request);
        $selectionData = [];
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
        }

        switch ($type) {
            case 'persons':
                if (!array_key_exists('personIds', $selectionData)) {
                    $selectionData['personIds'] = [];
                }
                foreach ($queryPersons->getResult() as $person) {
                    if (!in_array($person->getId(), $selectionData['personIds'])) {
                        $selectionData['personIds'][] = $person->getId();
                    } else {
                        $selectionData['personIds'] = [$person->getId()];
                    }
                }
                break;
            case 'pfos':
                if (!array_key_exists('pfoIds', $selectionData)) {
                    $selectionData['pfoIds'] = [];
                }
                foreach ($queryPfos->getResult() as $pfo) {
                    if (!in_array($pfo->getId(), $selectionData['pfoIds'])) {
                        $selectionData['pfoIds'][] = $pfo->getId();
                    } else {
                        $selectionData['pfoIds'] = [$pfo->getId()];
                    }
                }
                break;
            case 'organizations':
                if (!array_key_exists('organizationIds', $selectionData)) {
                    $selectionData['organizationIds'] = [];
                }
                foreach ($queryOrganizations->getResult() as $organization) {
                    if (!in_array($organization->getId(), $selectionData['organizationIds'])) {
                        $selectionData['organizationIds'][] = $organization->getId();
                    } else {
                        $selectionData['organizationIds'] = [$organization->getId()];
                    }
                }
                break;
            case 'representations':
                if (!array_key_exists('representationIds', $selectionData)) {
                    $selectionData['representationIds'] = [];
                }
                foreach ($queryRepresentations->getResult() as $representation) {
                    if (!in_array($representation->getId(), $selectionData['representationIds'])) {
                        $selectionData['representationIds'][] = $representation->getId();
                    } else {
                        $selectionData['organizationIds'] = [$representation->getId()];
                    }
                }
                break;
        }

        $session->set('selection', $selectionData);

        return new Response($this->getNbElementsInSelection($request), 200);
    }

    /**
     * Methode permettant de construire les requêtes de recherche, à utiliser dans les methodes searchList_addToSelection et search.
     *
     * @param type $searchParams
     * @param type $request
     *
     * @return type
     */
    private function getSearchQueries($searchParams, $request)
    {
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();
        $queryRepresentations = null;
        $em = $this->getDoctrine()->getManager();

        if (1 == $searchParams['filterAdvancedSearch']) {
            // surcharge pour le cas des sous groupes
            if (isset($searchParams['group_sub']) && 'on' == $searchParams['group_sub'] && isset($searchParams['groupIds']) && count($searchParams['groupIds']) > 0) {
                $groups = $em->getRepository('PostparcBundle:Group')->findBy(['id' => $searchParams['groupIds']]);
                foreach ($groups as $group) {
                    $subGroups = $em->getRepository('PostparcBundle:Group')->getChildren($node = $group, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                    foreach ($subGroups as $subGroup) {
                        $searchParams['groupIds'][] = $subGroup->getId();
                    }
                }
            }
            // surcharge pour le cas des sous territoires
            if (isset($searchParams['territory_sub']) && 'on' == $searchParams['territory_sub'] && isset($searchParams['territoryIds']) && count($searchParams['territoryIds']) > 0) {
                $territories = $em->getRepository('PostparcBundle:Territory')->findBy(['id' => $searchParams['territoryIds']]);
                foreach ($territories as $territory) {
                    $subTerritories = $em->getRepository('PostparcBundle:Territory')->getChildren($node = $territory, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                    foreach ($subTerritories as $subTerritory) {
                        $searchParams['territoryIds'][] = $subTerritory->getId();
                    }
                }
            }
            // surcharge pour le cas des sous organizationType
            if (isset($searchParams['organizationType_sub']) && 'on' == $searchParams['organizationType_sub'] && isset($searchParams['organizationTypeIds']) && count($searchParams['organizationTypeIds']) > 0) {
                $organizationTypes = $em->getRepository('PostparcBundle:OrganizationType')->findBy(['id' => $searchParams['organizationTypeIds']]);
                foreach ($organizationTypes as $organizationType) {
                    $subOrganizationTypes = $em->getRepository('PostparcBundle:OrganizationType')->getChildren($node = $organizationType, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                    foreach ($subOrganizationTypes as $subOrganizationType) {
                        $searchParams['organizationTypeIds'][] = $subOrganizationType->getId();
                    }
                }
            }
            // surcharge dans le cas inclusion organismes associés
            if (isset($searchParams['organization_includeOrganizationLinked']) && 'on' == $searchParams['organization_includeOrganizationLinked'] && count($searchParams['organizationIds']) > 0) {
                foreach ($searchParams['organizationIds'] as $organizationId) {
                    $organizationsLinkedIds = $em->getRepository('PostparcBundle:Organization')->getAllOrganizationLinkedIds($organizationId);
                    if (count($organizationsLinkedIds) > 0) {
                        $searchParams['organizationIds'] = array_merge($searchParams['organizationIds'], $organizationsLinkedIds);
                    }
                }
                array_unique($searchParams['organizationIds']);
            }
            // surcharge dans le cas inclusion organismes associés de type serice uniquement
            if (isset($searchParams['organization_includeSubServiceOrganizations']) && 'on' == $searchParams['organization_includeSubServiceOrganizations'] && isset($searchParams['organizationIds']) && count($searchParams['organizationIds']) > 0) {
                foreach ($searchParams['organizationIds'] as $organizationId) {
                    $subServiceOrganizations = $em->getRepository('PostparcBundle:Organization')->getSubServiceOrganizations($organizationId);
                    foreach ($subServiceOrganizations as $subServiceOrganization) {
                        $searchParams['organizationIds'][] = $subServiceOrganization->getId();
                    }
                }
                array_unique($searchParams['organizationIds']);
            }
            // surcharge pour le cas des sous mandateTypes
            if (isset($searchParams['mandateType_sub']) && 'on' == $searchParams['mandateType_sub'] && isset($searchParams['mandateTypeIds']) && count($searchParams['mandateTypeIds']) > 0) {
                $mandateTypes = $em->getRepository('PostparcBundle:MandateType')->findBy(['id' => $searchParams['mandateTypeIds']]);
                foreach ($mandateTypes as $mandateType) {
                    $subMandateTypes = $em->getRepository('PostparcBundle:MandateType')->getChildren($node = $mandateType, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                    foreach ($subMandateTypes as $subMandateType) {
                        $searchParams['mandateTypeIds'][] = $subMandateType->getId();
                    }
                }
            }
            // surcharge dans le cas de sous tags
            if (isset($searchParams['tag_sub']) && 'on' == $searchParams['tag_sub'] && isset($searchParams['tagIds']) && count($searchParams['tagIds']) > 0) {
                $tags = $em->getRepository('PostparcBundle:Tag')->findBy(['id' => $searchParams['tagIds']]);
                foreach ($tags as $tag) {
                    $subTags = $em->getRepository('PostparcBundle:Tag')->getChildren($node = $tag, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                    foreach ($subTags as $subTag) {
                        $searchParams['tagIds'][] = $subTag->getId();
                    }
                }
            }
            if (isset($searchParams['observation'])) {
                $searchParams['observation'] = trim($searchParams['observation']);
            }

            $queryPersons = $em->getRepository('PostparcBundle:Person')->advancedSearch($searchParams, $entityId, $readerLimitations, $show_SharedContents);
            $queryPfos = $em->getRepository('PostparcBundle:Pfo')->advancedSearch($searchParams, $entityId, $readerLimitations, $show_SharedContents);
            $queryOrganizations = $em->getRepository('PostparcBundle:Organization')->advancedSearch($searchParams, $entityId, $readerLimitations, $show_SharedContents);
            if (array_key_exists('use_representation_module', $currentEntityConfig) && $currentEntityConfig['use_representation_module']) {
                $queryRepresentations = $em->getRepository('PostparcBundle:Representation')->advancedSearch($searchParams, $entityId, $readerLimitations, $show_SharedContents);
            }
        } else {
            $q = trim($searchParams['q']);
            $queryPersons = $em->getRepository('PostparcBundle:Person')->simpleSearch($q, $entityId, $show_SharedContents);
            $queryPfos = $em->getRepository('PostparcBundle:Pfo')->simpleSearch($q, $entityId, $readerLimitations, $show_SharedContents);
            $queryOrganizations = $em->getRepository('PostparcBundle:Organization')->simpleSearch($q, $entityId, $readerLimitations, $show_SharedContents);
            if (array_key_exists('use_representation_module', $currentEntityConfig) && $currentEntityConfig['use_representation_module']) {
                $queryRepresentations = $em->getRepository('PostparcBundle:Representation')->simpleSearch($q, $entityId, $readerLimitations, $show_SharedContents);
            }
        }

        return [$queryPersons, $queryPfos, $queryOrganizations, $queryRepresentations];
    }

    /**
     * return total number element in selection.
     *
     * @param Request $request
     *
     * @return int
     * */
    private function getNbElementsInSelection(Request $request)
    {
        $nbElements = 0;
        $session = $request->getSession();
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');

            // Persons
            if (isset($selectionData['personIds'])) {
                $nbElements += count($selectionData['personIds']);
            }
            // Pfos
            if (isset($selectionData['pfoIds'])) {
                $nbElements += count($selectionData['pfoIds']);
            }
            // organizations
            if (isset($selectionData['organizationIds'])) {
                $nbElements += count($selectionData['organizationIds']);
            }
            // representations
            if (isset($selectionData['representationIds'])) {
                $nbElements += count($selectionData['representationIds']);
            }
        }

        return $nbElements;
    }
}

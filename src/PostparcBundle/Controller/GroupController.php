<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Cocur\Slugify\Slugify;
use PostparcBundle\Entity\Group;
use PostparcBundle\Entity\PfoPersonGroup;

/**
 * Group controller.
 *
 * @Route("/group")
 */
class GroupController extends Controller
{
    /**
     * Lists all Group entities.
     *
     * @Route("/", name="group_index", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\GroupFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('groupFilter');

            return $this->redirect($this->generateUrl('group_index'));
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
                if ($filterForm->has('onlyMyEntityGroups')) {
                    $filterData['onlyMyEntityGroups'] = $filterForm->get('onlyMyEntityGroups')->getData();
                }

                $session->set('groupFilter', $filterData);
            }
        } elseif ($session->has('groupFilter')) {
            $filterData = $session->get('groupFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\GroupFilterType');
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
            if (array_key_exists('onlyMyEntityGroups', $filterData)) {
                $filterForm->get('onlyMyEntityGroups')->setData($filterData['onlyMyEntityGroups']);
            }
        }
        $filterData['currentUser'] = $this->getUser();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = (is_array($currentEntityConfig) && array_key_exists('show_SharedContents', $currentEntityConfig)) ? $currentEntityConfig['show_SharedContents'] : false;
        $query = $em->getRepository('PostparcBundle:Group')->search($filterData, $entityId, $show_SharedContents);
        $repo = $em->getRepository('PostparcBundle:Group');

        $options = ['decorate' => false];

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        return $this->render('group/index.html.twig', [
                  'search_form' => $filterForm->createView(),
                  'htmlTree' => $htmlTree,
                  'nbResults' => count($query->getArrayResult()),
        ]);
    }

    /**
     * Creates a new Group entity.
     *
     * @Route("/new", name="group_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $group = new Group();
        $form = $this->createForm('PostparcBundle\Form\GroupType', $group, ['currentUser'=>$this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($group);
            $em->flush();

            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('group_edit', ['id' => $group->getId()]);
            } else {
                $group = new Group();
                $form = $this->createForm('PostparcBundle\Form\GroupType', $group, ['currentUser'=>$this->getUser()]);
            }
        }

        return $this->render('group/new.html.twig', [
                  'group' => $group,
                  'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a new Group entity.
     *
     * @Route("/new/{id}", name="group_new_subGroup", methods="GET|POST")
     *
     * @param Request $request
     * @param Group   $parent
     *
     * @return Response
     */
    public function newSubGroupAction(Request $request, Group $parent)
    {
        $group = new Group();
        $group->setParent($parent);
        $form = $this->createForm('PostparcBundle\Form\GroupType', $group, ['currentUser'=>$this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($group);
            $em->flush();

            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.addSuccess');

            return $this->redirectToRoute('group_edit', ['id' => $group->getId()]);
        }

        return $this->render('group/new.html.twig', [
                  'group' => $group,
                  'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Group entity.
     *
     * @Route("/{id}", name="group_show", methods="GET", requirements={"id":"\d+"})
     *
     * @param Group $group
     *
     * @return Response
     */
    public function showAction(Group $group)
    {
        $checkAccessService = $this->container->get('postparc.check_access');

        if (!$checkAccessService->checkAccess($group)) {
            $referer = $request->headers->get('referer');
            $this->addFlash('info', 'flash.accessDenied');

            return $referer ? $this->redirect($referer) : $this->redirectToRoute('group_index');
        }

        $deleteForm = $this->createDeleteForm($group);

        return $this->render('group/show.html.twig', [
                  'group' => $group,
                  'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Group entity.
     *
     * @Route("/{id}/edit", name="group_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param Group   $group
     *
     * @return Response
     */
    public function editAction(Request $request, Group $group)
    {
        $deleteForm = $this->createDeleteForm($group);
        $editForm = $this->createForm('PostparcBundle\Form\GroupType', $group, ['currentUser'=>$this->getUser()]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($group);
            $em->flush();

            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.updateSuccess');

            return $this->redirectToRoute('group_edit', ['id' => $group->getId()]);
        }

        return $this->render('group/edit.html.twig', [
                  'group' => $group,
                  'edit_form' => $editForm->createView(),
                  'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Delete a Group entity.
     *
     * @Route("/{id}/delete", name="group_delete", methods="GET")
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:Group')->batchDelete($ids, null, $this->getUser());
        $request->getSession()
                ->getFlashBag()
                ->add('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('group_index');
    }

    /**
     * Group batch actions.
     *
     * @Route("/batch", name="group_batch", methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        if ('batchDelete' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();
                $em->getRepository('PostparcBundle:group')->batchDelete($ids, null, $this->getUser());
                $request->getSession()
                        ->getFlashBag()
                        ->add('success', 'batch.deleteSuccess');
            } else {
                $request->getSession()
                        ->getFlashBag()
                        ->add('error', 'batch.noSelectedItemError');
            }
        }

        return $this->redirectToRoute('group_index');
    }

    /**
     * List all Personn and pfo associate to one group.
     *
     * @Route("/{id}/listPersonn", name="group_listPersonn")
     *
     * @param Group   $group
     * @param Request $request
     *
     * @return Response
     */
    public function listePersonnGroupAction(Group $group, Request $request)
    {
        $session = $request->getSession();

        $groupId = $group->getId();
        $activeTab = 'persons';
        if ($request->query->has('activeTab')) {
            $activeTab = $request->query->get('activeTab');
        }

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\PfoPersonGroupFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('PfoPersonGroupFilterType');

            return $this->redirect($this->generateUrl('group_listPersonn', ['id' => $groupId]));
        }

        // Filter action
        if ('filter' == $request->get('filter_action')) {
            // Bind values from the request
            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                // Save filter to session
                if ($filterForm->has('group')) {
                    $filterData['group'] = $filterForm->get('group')->getData();
                    $group = $filterData['group'];
                }
                $session->set('PfoPersonGroupFilterType', $filterData);
            }
        } elseif ($session->has('PfoPersonGroupFilterType')) {
            $filterData = $session->get('PfoPersonGroupFilterType');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\PfoPersonGroupFilterType', $filterData, ['data_class' => null]);
            $group = $filterData['group'];
        } else {
            $filterData = $session->get('PfoPersonGroupFilterType');
            $filterData['group'] = $group;
            $filterForm = $this->createForm('PostparcBundle\FormFilter\PfoPersonGroupFilterType', $filterData, ['data_class' => null]);
        }

        $groupsElements = $this->getGroupsElements([$group], $request);

        return $this->render('group/listPersonn.html.twig', [
                  'pagination' => $groupsElements['pagination'],
                  'representations' => $groupsElements['representations'],
                  'organizations' => $groupsElements['organizations'],
                  'group' => $group,
                  'search_form' => $filterForm->createView(),
                  'subFolder' => false,
                  'allResults' => $groupsElements['allResults'],
                  'activeTab' => $activeTab,
        ]);
    }

    /**
     * List all Personn and pfo associate to one group.
     *
     * @Route("/{id}/listSubGroupPersonn", name="subGroup_listPersonn")
     *
     * @param Group   $group
     * @param Request $request
     *
     * @return Response
     */
    public function listePersonnSubGroupAction(Group $group, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $request->getSession();

        $groupId = $group->getId();
        $activeTab = 'persons';
        if ($request->query->has('activeTab')) {
            $activeTab = $request->query->get('activeTab');
        }

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\PfoPersonGroupFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('PfoPersonSubGroupFilterType');

            return $this->redirect($this->generateUrl('subGroup_listPersonn', ['id' => $groupId]));
        }

        // Filter action
        if ('filter' == $request->get('filter_action')) {
            // Bind values from the request
            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                // Save filter to session
                if ($filterForm->has('group')) {
                    $filterData['group'] = $filterForm->get('group')->getData();
                    $group = $filterData['group'];
                }
                $session->set('PfoPersonSubGroupFilterType', $filterData);
            }
        } elseif ($session->has('PfoPersonSubGroupFilterType')) {
            $filterData = $session->get('PfoPersonSubGroupFilterType');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\PfoPersonGroupFilterType', $filterData, ['data_class' => null]);
            $group = $filterData['group'];
        } else {
            $filterData = $session->get('PfoPersonSubGroupFilterType');
            $filterData['group'] = $group;
            $filterForm = $this->createForm('PostparcBundle\FormFilter\PfoPersonGroupFilterType', $filterData, ['data_class' => null]);
        }
        $childrens = $em->getRepository('PostparcBundle:Group')->getChildren($node = $group, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);

        $groupsElements = $this->getGroupsElements($childrens, $request);

        return $this->render('group/listPersonn.html.twig', [
                  'pagination' => $groupsElements['pagination'],
                  'representations' => $groupsElements['representations'],
                  'organizations' => $groupsElements['organizations'],
                  'group' => $group,
                  'search_form' => $filterForm->createView(),
                  'subFolder' => true,
                  'allResults' => $groupsElements['allResults'],
                  'activeTab' => $activeTab,
        ]);
    }

    private function getGroupsElements(array $groupIds, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');

        // person and pfos
        $queryPfoPersonGroups = $em->getRepository('PostparcBundle:PfoPersonGroup')->listPersonnSubGroupQuery($groupIds);
        $pagination = $paginator->paginate(
            $queryPfoPersonGroups, /* query NOT result */
            $request->query->getInt('page', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page)/* limit per page */
        );
        // all results
        $allResults['persons'] = [];
        $allResults['pfos'] = [];
        $ppgs = $em->getRepository('PostparcBundle:PfoPersonGroup')->listPersonnSubGroupQuery($groupIds)->execute();
        foreach ($ppgs as $ppg) {
            if ($ppg->getPfo()) {
                $allResults['pfos'][] = $ppg->getPfo();
            }
            if ($ppg->getPerson()) {
                $allResults['persons'][] = $ppg->getPerson();
            }
        }

        // representations
        $queryRepresentationGroups = $em->getRepository('PostparcBundle:Representation')->listRepresentationSubGroupQuery($groupIds);
        $representations = $paginator->paginate(
            $queryRepresentationGroups, /* query NOT result */
            $request->query->getInt('pageRep', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
                  'pageParameterName' => 'pageRep',
                  'sortFieldParameterName' => 'sortRep',
                  'sortDirectionParameterName' => 'directionRep',
                  'defaultSortFieldName' => 'r.name',
                  'defaultSortDirection' => 'asc',
                ]
        );
        // all results
        $allResults['representations'] = $em->getRepository('PostparcBundle:Representation')->listRepresentationSubGroupQuery($groupIds)->execute();

        // organizations
        $queryOrganizationGroups = $em->getRepository('PostparcBundle:Organization')->listOrganizationSubGroupQuery($groupIds);
        $organizations = $paginator->paginate(
            $queryOrganizationGroups, /* query NOT result */
            $request->query->getInt('pageOrg', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
                  'pageParameterName' => 'pageOrg',
                  'sortFieldParameterName' => 'sortOrg',
                  'sortDirectionParameterName' => 'directionOrg',
                  'defaultSortFieldName' => 'o.name',
                  'defaultSortDirection' => 'asc',
                ]
        );
        // all results
        $allResults['organizations'] = $em->getRepository('PostparcBundle:Organization')->listOrganizationSubGroupQuery($groupIds)->execute();

        // addToBasket
        if ($request->query->has('addToBasket')) {
            $this->addAllResultsToSelection($request, $allResults);
        }

        return [
                  'pagination' => $pagination,
                  'representations' => $representations,
                  'organizations' => $organizations,
                  'subFolder' => true,
                  'allResults' => $allResults,
        ];
    }



    /**
     * Group batch actions.
     *
     * @Route("/{id}/batchPfoPersonGroup", name="pfoPersonGroup_batch", methods="GET|POST")
     *
     * @param Request $request
     * @param Group   $group
     *
     * @return Response
     */
    public function batchPfoPersonGroupAction(Request $request, Group $group)
    {
        if ($request->request->has('batch_action')) {
            $ids = $request->request->get('ids');
            $representationIds = $request->request->get('representationIds');
            $organizationIds = $request->request->get('organizationIds');

            if ($ids || $representationIds || $organizationIds) {
                $em = $this->getDoctrine()->getManager();
                $ppgs = $ids ? $em->getRepository('PostparcBundle:PfoPersonGroup')->findBy(['id' => $ids]) : [];
                $representations = $representationIds ? $em->getRepository('PostparcBundle:Representation')->findBy(['id' => $representationIds]) : [];
                $organizations = $organizationIds ? $em->getRepository('PostparcBundle:Organization')->findBy(['id' => $organizationIds]) : [];

                switch ($request->request->get('batch_action')) {
                    case 'batchAddBasket':
                        // construction des tableaux pfoIds et personIds
                        $pfoIds = [];
                        $personIds = [];

                        foreach ($ppgs as $ppg) {
                            if ($ppg->getPerson()) {
                                $personIds[] = $ppg->getPerson()->getId();
                            }
                            if ($ppg->getPfo()) {
                                $pfoIds[] = $ppg->getPfo()->getId();
                            }
                        }
                        $session = $request->getSession();
                        $selectionData = [];
                        if ($session->has('selection')) {
                            $selectionData = $session->get('selection');
                            if (is_array($personIds)) {
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
                            if (is_array($pfoIds)) {
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
                            if (is_array($representationIds)) {
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
                            if (is_array($organizationIds)) {
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
                        } else {
                            $selectionData['personIds'] = $personIds;
                            $selectionData['pfoIds'] = $pfoIds;
                            $selectionData['representationIds'] = $representationIds;
                            $selectionData['organizationIds'] = $organizationIds;
                        }

                        $session->set('selection', $selectionData);

                        $message = 'flash.addElementToSelectionSuccess';

                        $request->getSession()
                                ->getFlashBag()
                                ->add('success', $message);
                        break;
                    case 'batchDeleteFromGroup':
                        if ($ids && count($ids)) {
                            $em->getRepository('PostparcBundle:PfoPersonGroup')->batchDelete($ids);
                            $request->getSession()
                                    ->getFlashBag()
                                    ->add('success', 'batch.deleteMassivePersonFormGroupSuccess');
                        }
                        if ($representationIds && count($representationIds)) {
                            foreach ($representations as $representation) {
                                $representation->removeGroup($group);
                            }
                            $em->persist($representation);
                            $em->flush();
                            $request->getSession()
                                    ->getFlashBag()
                                    ->add('success', 'batch.deleteMassiveRepresentationFromGroupSuccess');
                        }
                        if ($organizationIds && count($organizations)) {
                            foreach ($organizations as $organization) {
                                $organization->removeGroup($group);
                            }
                            $em->persist($organization);
                            $em->flush();
                            $request->getSession()
                                    ->getFlashBag()
                                    ->add('success', 'batch.deleteMassiveOrganizationFromGroupSuccess');
                        }
                        break;
                    case 'batchAddOrganizationsPfosToBasket':
                        if ($organizationIds && count($organizationIds)) {
                            // récupération des pfos associées aux organismes
                            $currentEntityService = $this->container->get('postparc_current_entity_service');
                            $entityId = $currentEntityService->getCurrentEntityId();
                            $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
                            // gestion lecteur -> recherche si restriction
                            $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
                            $readerLimitations = $currentReaderLimitationService->getReaderLimitations();
                            $pfos = [];
                            $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
                            foreach ($organizationIds as $organizationId) {
                                $pfos = array_merge($pfos, $em->getRepository('PostparcBundle:Pfo')->getOrganizationPfos($organizationId, $entityId, $readerLimitations, $show_SharedContents)->getResult());
                            }
                            if (($pfos !== []) > 0) {
                                $pfoIds = [];
                                $session = $request->getSession();
                                $selectionData = [];

                                if ($session->has('selection')) {
                                    $selectionData = $session->get('selection');
                                }
                                if (array_key_exists('pfoIds', $selectionData)) {
                                    foreach ($pfos as $pfo) {
                                        if (!in_array($pfo->getId(), $selectionData['pfoIds'])) {
                                            $selectionData['pfoIds'][] = $pfo->getId();
                                        }
                                    }
                                } else {
                                    // not yet pfoIDs in selection
                                    $selectionData['pfoIds'] = [];
                                    foreach ($pfos as $pfo) {
                                        $selectionData['pfoIds'][] = $pfo->getId();
                                    }
                                }

                                $session->set('selection', $selectionData);
                                $message = 'flash.addElementToSelectionSuccess';
                                $request->getSession()
                                        ->getFlashBag()
                                        ->add('success', $message);
                            } else {
                                $request->getSession()
                                        ->getFlashBag()
                                        ->add('error', 'batch.noSelectedItemError');
                            }
                        }

                        break;
                }
            } else {
                $request->getSession()
                        ->getFlashBag()
                        ->add('error', 'batch.noSelectedItemError');
            }
        }
        if ($request->request->has('subFolder') && false == $request->request->get('subFolder')) {
            return $this->redirect($this->generateUrl('subGroup_listPersonn', ['id' => $group->getId()]));
        } else {
            return $this->redirect($this->generateUrl('group_listPersonn', ['id' => $group->getId()]));
        }
    }

    /**
     * Delete PfoPersonGroup.
     *
     * @Route("/{id}/deletePfoPersonGroup", name="ppg_delete")
     *
     * @param PfoPersonGroup $pfoPersonGroup The PfoPersonGroup entity
     * @param Request        $request
     *
     * @return Response
     */
    public function deletePfoPersonGroupAction(PfoPersonGroup $pfoPersonGroup, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->query->has('origin')) {
            $person = $pfoPersonGroup->getPerson();
            $pfo = $pfoPersonGroup->getPfo();
        }
        $group = $pfoPersonGroup->getGroup();
        $em->remove($pfoPersonGroup);
        $em->flush();

        $request->getSession()
                ->getFlashBag()
                ->add('success', 'flash.deleteSuccess');

        if ($request->query->has('origin')) {
            switch ($request->query->get('origin')) {
                case 'coordPerso':
                    return $this->redirect($this->generateUrl('person_show', ['id' => $person->getId(),'activSubTab' => 'groupsInfosPerson']) . '#coordPerso');
                case 'coordPfo':
                    return $this->redirect($this->generateUrl('person_show', ['id' => $pfo->getPerson()->getId(),'activSubTab' => 'groupsInfosPfo-' . $pfo->getId()]) . '#pfo-' . $pfo->getId());
                case 'subGroup_listPersonn':
                    return $this->redirectToRoute('subGroup_listPersonn', ['id' => $group->getId()]);
                case 'group_listPersonn':
                    return $this->redirectToRoute('group_listPersonn', ['id' => $group->getId()]);
            }
        }

        return $this->redirect($this->generateUrl('group_listPersonn', ['id' => $group->getId()]));
    }

    /**
     * list non associate group for select dom.
     *
     * @Route("/selectGroup", name="group_selectGroups", methods="GET")
     *
     * @param int     $personId
     * @param int     $pfoId
     * @param int     $organizationId
     * @param Request $request
     *
     * @return Response
     */
    public function selectGroupsAction($personId, $pfoId, $organizationId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $currentUser = $this->getUser();
        $groups = $em->getRepository('PostparcBundle:Group')->getGroupsForSelect($currentUser, $personId, $pfoId, $organizationId, $entityId, $show_SharedContents);
        $uniqId = uniqid();

        return $this->render('group/selectGroups.html.twig', [
                  'groups' => $groups,
                  'uniqId' => $uniqId,
        ]);
    }

    /**
     * add this ppg to the basket.
     *
     * @Route("/ppg/{id}/addBasket", name="ppg_addBasket", methods="GET")
     *
     * @param Request        $request
     * @param PfoPersonGroup $ppg
     *
     * @return Response
     */
    public function addPpgToBasketAction(Request $request, PfoPersonGroup $ppg)
    {
        $session = $request->getSession();
        $selectionData = [];
        $alreadyPfoExist = false;
        $alreadyPersonExist = false;
        $pfo = $ppg->getPfo();
        $person = $ppg->getPerson();
        // Get filter from session
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            if ($pfo) {
                if (array_key_exists('pfoIds', $selectionData)) {
                    if (!in_array($pfo->getId(), $selectionData['pfoIds'])) {
                        $selectionData['pfoIds'][] = $pfo->getId();
                    } else {
                        $alreadyPfoExist = true;
                    }
                } else {
                    $selectionData['pfoIds'] = [$pfo->getId()];
                }
            }
            if ($person) {
                if (array_key_exists('personIds', $selectionData)) {
                    if (!in_array($person->getId(), $selectionData['personIds'])) {
                        $selectionData['personIds'][] = $person->getId();
                    } else {
                        $alreadyPersonExist = true;
                    }
                } else {
                    $selectionData['personIds'] = [$person->getId()];
                }
            }
        } else {
            if ($pfo) {
                $selectionData['pfoIds'] = [$pfo->getId()];
            }
            if ($person) {
                $selectionData['personIds'] = [$person->getId()];
            }
        }

        $session->set('selection', $selectionData);
        if ($pfo) {
            if ($alreadyPfoExist) {
                $request->getSession()
                        ->getFlashBag()
                        ->add('error', 'Pfo.actions.addBasket.alreadyPresent');
            } else {
                $request->getSession()
                        ->getFlashBag()
                        ->add('success', 'Pfo.actions.addBasket.success');
            }
        }
        if ($person) {
            if ($alreadyPersonExist) {
                $request->getSession()
                        ->getFlashBag()
                        ->add('error', 'Person.actions.addBasket.alreadyPresent');
            } else {
                $request->getSession()
                        ->getFlashBag()
                        ->add('success', 'Person.actions.addBasket.success');
            }
        }

        if ($request->query->has('origin')) {
            switch ($request->query->get('origin')) {
                case 'group_listPersonn':
                    return $this->redirectToRoute('group_listPersonn', ['id' => $ppg->getGroup()->getId()]);
                case 'subGroup_listPersonn':
                    return $this->redirectToRoute('subGroup_listPersonn', ['id' => $ppg->getGroup()->getId()]);
            }
        }

        return $this->redirectToRoute('group_index');
    }

    /**
     * @Route("/{id}/exportListePersonnGroup", name="listePersonnGroup_export"))
     *
     * @param Group   $group
     * @param Request $request
     *
     * @return Response
     */
    public function exportListePersonnGroupAction(Group $group, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $pfoPersonGroups = $em->getRepository('PostparcBundle:PfoPersonGroup')->listPersonnGroup($group->getId());
        $representations = $em->getRepository('PostparcBundle:Representation')->listRepresentationGroupQuery($group->getId())->getResult();
        $organizations = $em->getRepository('PostparcBundle:Organization')->listOrganizationGroupQuery($group->getId())->getResult();

        return $this->exportListePersonnGroupExecute($group, $pfoPersonGroups, false, $representations, $organizations);
    }

    /**
     * @Route("/{id}/exportListePersonnSubGroup", name="listePersonnSubGroup_export"))
     *
     * @param Group   $group
     * @param Request $request
     *
     * @return Response
     */
    public function exportListePersonnSubGroupAction(Group $group, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $childrens = $em->getRepository('PostparcBundle:Group')->getChildren($node = $group, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
        $pfoPersonGroups = $em->getRepository('PostparcBundle:PfoPersonGroup')->listPersonnSubGroup($childrens);
        $representations = $em->getRepository('PostparcBundle:Representation')->listRepresentationSubGroupQuery($childrens)->getResult();
        $organizations = $em->getRepository('PostparcBundle:Organization')->listOrganizationSubGroupQuery($childrens)->getResult();

        return $this->exportListePersonnGroupExecute($group, $pfoPersonGroups, true, $representations, $organizations);
    }

    private function addAllResultsToSelection(Request $request, $allResults)
    {
        $session = $request->getSession();
        $selectionData = $session->has('selection') ? $session->get('selection') : [];
        if (array_key_exists('persons', $allResults)) {
            foreach ($allResults['persons'] as $person) {
                if (array_key_exists('personIds', $selectionData)) {
                    if (!in_array($person->getId(), $selectionData['personIds'])) {
                        $selectionData['personIds'][] = $person->getId();
                    }
                } else {
                    $selectionData['personIds'] = [$person->getId()];
                }
            }
        }
        if (array_key_exists('organizations', $allResults)) {
            foreach ($allResults['organizations'] as $organization) {
                if (array_key_exists('organizationIds', $selectionData)) {
                    if (!in_array($organization->getId(), $selectionData['organizationIds'])) {
                        $selectionData['organizationIds'][] = $organization->getId();
                    }
                } else {
                    $selectionData['organizationIds'] = [$organization->getId()];
                }
            }
        }
        if (array_key_exists('pfos', $allResults)) {
            foreach ($allResults['pfos'] as $pfo) {
                if (array_key_exists('pfoIds', $selectionData)) {
                    if (!in_array($pfo->getId(), $selectionData['pfoIds'])) {
                        $selectionData['pfoIds'][] = $pfo->getId();
                    }
                } else {
                    $selectionData['pfoIds'] = [$pfo->getId()];
                }
            }
        }
        if (array_key_exists('representations', $allResults)) {
            foreach ($allResults['representations'] as $representation) {
                if (array_key_exists('representationIds', $selectionData)) {
                    if (!in_array($representation->getId(), $selectionData['representationIds'])) {
                        $selectionData['representationIds'][] = $representation->getId();
                    }
                } else {
                    $selectionData['representationIds'] = [$representation->getId()];
                }
            }
        }
        if (count($selectionData) > 0) {
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'Group.actions.addBasket.success');
        }

        $session->set('selection', $selectionData);
    }

    /**
     * Creates a form to delete a Group entity.
     *
     * @param Group $group The Group entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Group $group)
    {
        return $this->createFormBuilder()
                        ->setAction($this->generateUrl('group_delete', ['id' => $group->getId()]))
                        ->setMethod('DELETE')
                        ->getForm()
        ;
    }

    /**
     * @param type $group
     * @param type $pfoPersonGroups
     * @param type $withSubFolder
     *
     * @return Response
     */
    private function exportListePersonnGroupExecute($group, $pfoPersonGroups, $withSubFolder, $representations, $organizations)
    {
        $translator = $this->get('translator');
        $slugify = new Slugify();
        $activeSheetNumber = 0;
        $title = $translator->trans('PfoPersonGroup.list') . ' ' . $group;

        if ($withSubFolder) {
            $title .= ' ' . $translator->trans('PfoPersonGroup.listSubGroup');
        }

        $phpExcelObject = $this->get('phpspreadsheet')->createSpreadsheet();
        $phpExcelObject->getProperties()->setCreator('Postparc')->setTitle($title);
        $phpExcelObject->createSheet();
        $phpExcelObject->getActiveSheet()->setTitle(substr($translator->trans('Person.labels'), 0, 30));
        $phpExcelObject->setActiveSheetIndex($activeSheetNumber);
        $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(1, 1, $translator->trans('Pfo.field.person'));
        $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(2, 1, $translator->trans('Pfo.field.personFunction'));
        $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(3, 1, $translator->trans('Pfo.field.additionalFunction'));
        $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(4, 1, $translator->trans('Pfo.field.service'));
        $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(5, 1, $translator->trans('Pfo.field.organization'));
        $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(6, 1, $translator->trans('Email.field.email'));
        $row = 1;
        foreach ($pfoPersonGroups as $pfoPersonGroup) {
            ++$row;
            if ($pfoPersonGroup->getPfo()) {
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(1, $row, $pfoPersonGroup->getPfo()->getPerson());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(2, $row, $pfoPersonGroup->getPfo()->getPersonFunction());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(3, $row, $pfoPersonGroup->getPfo()->getAdditionalFunction());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(4, $row, $pfoPersonGroup->getPfo()->getService());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(5, $row, $pfoPersonGroup->getPfo()->getOrganization());
                // emails
                
                $preferedEmailsString = '';
                $separator = '';
                if($pfoPersonGroup->getPfo()->getEmail()){
                    $preferedEmailsString = $pfoPersonGroup->getPfo()->getEmail()->getEmail();
                }
                foreach ($pfoPersonGroup->getPfo()->getPreferedEmails() as $email) {
                    $preferedEmailsString .= $separator . $email;
                    $separator = ';';
                }

                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(6, $row, $preferedEmailsString);
            }
            if ($pfoPersonGroup->getPerson()) {
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(1, $row, $pfoPersonGroup->getPerson());
                // emails
                $preferedEmailsString = '';
                $separator = '';
                if($pfoPersonGroup->getPerson()->getCoordinate() && $pfoPersonGroup->getPerson()->getCoordinate()->getEmail()) {
                    $preferedEmailsString = $pfoPersonGroup->getPerson()->getCoordinate()->getEmail()->getEmail();
                }
                foreach($pfoPersonGroup->getPerson()->getPreferedEmails() as $email) {
                    $preferedEmailsString .= $separator . $email;
                    $separator = ';';
                }

                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(6, $row, $preferedEmailsString);
            }
        }
        if (count($representations) > 0) {
            $activeSheetNumber = 1;
            $phpExcelObject->createSheet();
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber);
            $phpExcelObject->getActiveSheet()->setTitle(substr($translator->trans('Representation.labels'), 0, 30));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(1, 1, $translator->trans('Representation.field.person'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(2, 1, $translator->trans('Representation.field.name'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(3, 1, $translator->trans('Representation.field.elected'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(4, 1, $translator->trans('Representation.field.beginDate'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(5, 1, $translator->trans('Representation.field.mandatDuration'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(6, 1, $translator->trans('Representation.field.estimatedTime'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(7, 1, $translator->trans('Representation.field.estimatedCost'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(8, 1, $translator->trans('Representation.field.periodicity'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(9, 1, $translator->trans('Representation.field.mandateType'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(10, 1, $translator->trans('Representation.field.service'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(11, 1, $translator->trans('Representation.field.personFunction'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(12, 1, $translator->trans('Representation.field.specificCoordinate'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(13, 1, $translator->trans('Representation.field.organization'));
            $row = 1;
            foreach ($representations as $representation) {
                ++$row;
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(1, $row, $representation->getPfo() ? $representation->getPfo()->getPerson() : $representation->getPerson());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(2, $row, $representation);
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(3, $row, $translator->trans($representation->getElected() ? 'Representation.designated' : 'Representation.elected'));
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(4, $row, $representation->getBeginDate() ? $representation->getBeginDate()->format('d-m-Y') : '');
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(5, $row, $representation->getMandatDuration());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(6, $row, $representation->getEstimatedTime());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(7, $row, $representation->getEstimatedCost());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(8, $row, $representation->getPeriodicity());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(9, $row, $representation->getMandateType());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(10, $row, $representation->getService());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(11, $row, $representation->getPersonFunction());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(10, $row, $representation->getCoordinateObject());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(11, $row, $representation->getOrganization());
            }
        }
        if (count($organizations) > 0) {
            $activeSheetNumber = 2;
            $phpExcelObject->createSheet();
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber);
            $phpExcelObject->getActiveSheet()->setTitle(substr($translator->trans('Organization.labels'), 0, 30));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(1, 1, $translator->trans('Organization.field.name'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(2, 1, $translator->trans('Organization.field.abbreviation'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(3, 1, $translator->trans('Organization.field.organizationType'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(4, 1, $translator->trans('Organization.field.observation'));
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(5, 1, $translator->trans('Organization.field.description'));
            $row = 1;
            foreach ($organizations as $organization) {
                ++$row;
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(1, $row, $organization);
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(2, $row, $organization->getAbbreviation());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(3, $row, $organization->getOrganizationType());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(4, $row, $organization->getObservation());
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow(5, $row, $organization->getDescription());
            }
        }

        //$phpExcelObject->getActiveSheet()->setTitle(substr($slugify->slugify($group), 0, 31));
        // create the writer
        $writer = $this->get('phpspreadsheet')->createWriter($phpExcelObject);
        // create the response
        $response = $this->get('phpspreadsheet')->createStreamedResponse($writer);
        // adding headers
        $fileName = $slugify->slugify($title) . '.xls';

        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}

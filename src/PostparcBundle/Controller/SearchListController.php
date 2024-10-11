<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\SearchList;

/**
 * SearchList controller.
 *
 * @Route("/searchList")
 */
class SearchListController extends Controller
{
    /**
     * Lists all searchList entities.
     *
     * @Route("/", name="searchList_index", methods="GET|POST")
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
        $filterForm = $this->createForm('PostparcBundle\FormFilter\SearchListFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('searchListFilter');

            return $this->redirect($this->generateUrl('searchList_index'));
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
                $session->set('searchListFilter', $filterData);
            }
        } elseif ($session->has('searchListFilter')) {
            $filterData = $session->get('searchListFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\SearchListFilterType', $filterData, ['data_class' => null]);
        }
        $filterData['currentUser'] = $this->getUser();

        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');

        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $query = $em->getRepository('PostparcBundle:SearchList')->search($filterData, $entityId, $show_SharedContents);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page)/*limit per page*/
        );

        return $this->render('searchList/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * @Route("/batch", name="searchList_batch", methods="POST")
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
                $em->getRepository('PostparcBundle:SearchList')->batchDelete($ids, null, $this->getUser());
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('searchList_index');
    }

    /**
     * Creates a new SearchList entity.
     *
     * @Route("/new", name="searchList_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $session = $request->getSession();
        $searchParams = $session->has('searchParams') ? $session->get('searchParams') : null;
        $searchList = new SearchList();
        $form = $this->createForm('PostparcBundle\Form\SearchListType', $searchList,['currentUser'=>$this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $searchList->setSearchParams($searchParams);
            $em->persist($searchList);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            return $this->redirectToRoute('searchList_index');
        }

        return $this->render('searchList/new.html.twig', [
            'searchList' => $searchList,
            'form' => $form->createView(),
        ]);
    }

    /**
     * edit a  SearchList entity.
     *
     * @Route("/{id}/edit", name="searchList_edit", methods="GET|POST")
     *
     * @param Request    $request
     * @param SearchList $searchList
     *
     * @return Response
     */
    public function editAction(Request $request, SearchList $searchList)
    {
        $deleteForm = $this->createDeleteForm($searchList);
        $editForm = $this->createForm('PostparcBundle\Form\SearchListType', $searchList,['currentUser'=>$this->getUser()]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($searchList);
            $em->flush();

            $this->addFlash('success', 'flash.updateSuccess');

            return $this->redirectToRoute('searchList_edit', ['id' => $searchList->getId()]);
        }

        return $this->render('searchList/edit.html.twig', [
            'searchList' => $searchList,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * delete a  SearchList entity.
     *
     * @Route("/{id}/delete", name="searchList_delete", methods="GET")
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:SearchList')->batchDelete($ids, null, $this->getUser());

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('searchList_index');
    }

    /**
     * show SearchList criteria.
     *
     * @Route("/{id}/showCriterias", name="searchList_showCriterias", methods="GET")
     *
     * @param Request    $request
     * @param SearchList $searchList
     *
     * @return Response
     */
    public function showCriteriasAction(Request $request, SearchList $searchList)
    {
        $session = $request->getSession();
        $searchParams = $searchList->getSearchParams();
        // stockage des élements de recherche en session
        $session->set('searchParams', $searchParams);

        return $this->redirectToRoute('search-homepage');
    }

    /**
     * show search Results for one SearchList.
     *
     * @Route("/{id}/showResults", name="searchList_showResults", methods="GET")
     *
     * @param Request    $request
     * @param SearchList $searchList
     *
     * @return Response
     */
    public function showResultsAction(Request $request, SearchList $searchList)
    {
        $session = $request->getSession();
        $searchParams = $searchList->getSearchParams();
        // stockage des élements de recherche en session
        $session->set('searchParams', $searchParams);

        return $this->redirectToRoute('search');
    }

    /**
     * show search Results for one SearchList.
     *
     * @Route("/saveOnExisting", name="searchList_saveOnExisting", methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function saveOnExistingAction(Request $request)
    {
        $session = $request->getSession();
        $searchParams = $session->get('searchParams');
        if ($request->request->has('searchListId')) {
            $em = $this->getDoctrine()->getManager();
            $searchListId = $request->request->get('searchListId');
            $searchList = $em->getRepository('PostparcBundle:SearchList')->find($searchListId);
            $searchList->setSearchParams($searchParams);
            $em->persist($searchList);
            $em->flush();
            $this->addFlash('success', 'flash.updateSearchListSuccess');
        } else {
            $this->addFlash('error', 'flash.updateSearchListError');
        }

        return $this->redirectToRoute('search');
    }

    /**
     * Creates a form to delete a SearchList entity.
     *
     * @param SearchList $searchList The SearchList entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(SearchList $searchList)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('searchList_delete', ['id' => $searchList->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

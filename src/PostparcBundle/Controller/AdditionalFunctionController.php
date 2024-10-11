<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\AdditionalFunction;
use PostparcBundle\Form\AdditionalFunctionType;

/**
 * AdditionalFunction controller.
 *
 * @Route("/complement-function")
 */
class AdditionalFunctionController extends Controller
{
    /**
     * Lists all AdditionalFunction entities.
     *
     * @param Request $request
     * @Route("/", name="complement_function_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\AdditionalFunctionFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('additionalFunctionFilter');

            return $this->redirect($this->generateUrl('complement_function_index'));
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
                $session->set('additionalFunctionFilter', $filterData);
            }
        } elseif ($session->has('additionalFunctionFilter')) {
            $filterData = $session->get('additionalFunctionFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\AdditionalFunctionFilterType', $filterData, ['data_class' => null]);
        }

        $query = $em->getRepository('PostparcBundle:AdditionalFunction')->search($filterData);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page), /*limit per page*/
            [
                'defaultSortFieldName' => 'af.slug',
                'defaultSortDirection' => 'asc',
            ]
        );

        return $this->render('additionalfunction/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @param Request $request
     * @Route("/batch", name="complement_function_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        if ('batchDelete' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();
                $em->getRepository('PostparcBundle:additionalfunction')->batchDelete($ids);
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('complement_function_index');
    }

    /**
     * Creates a new AdditionalFunction entity.
     *
     * @param Request $request
     * @Route("/new", name="complement_function_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $additionalFunction = new AdditionalFunction();
        $form = $this->createForm(AdditionalFunctionType::class, $additionalFunction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($additionalFunction);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');
            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('complement_function_edit', ['id' => $additionalFunction->getId()]);
            } else {
                $additionalFunction = new AdditionalFunction();
                $form = $this->createForm(AdditionalFunctionType::class, $additionalFunction);
            }
        }

        return $this->render('additionalfunction/new.html.twig', [
            'additionalFunction' => $additionalFunction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a AdditionalFunction entity.
     *
     * @param AdditionalFunction $additionalFunction
     * @Route("/{id}", name="complement_function_show", methods="GET", requirements={"id":"\d+"})
     *
     * @return Response
     */
    public function showAction(AdditionalFunction $additionalFunction)
    {
        $deleteForm = $this->createDeleteForm($additionalFunction);

        return $this->render('additionalfunction/show.html.twig', [
            'additionalFunction' => $additionalFunction,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing AdditionalFunction entity.
     *
     * @param Request            $request
     * @param AdditionalFunction $additionalFunction
     * @Route("/{id}/edit", name="complement_function_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, AdditionalFunction $additionalFunction)
    {
        $deleteForm = $this->createDeleteForm($additionalFunction);
        $editForm = $this->createForm(AdditionalFunctionType::class, $additionalFunction);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($additionalFunction);
            $em->flush();
            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('complement_function_edit', ['id' => $additionalFunction->getId()]);
        }

        return $this->render('additionalfunction/edit.html.twig', [
            'additionalFunction' => $additionalFunction,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a AdditionalFunction entity.
     *
     * @param inetger $id
     * @Route("/{id}/delete", name="complement_function_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:Additionalfunction')->batchDelete($ids);
        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('complement_function_index');
    }

    /**
     * get AdditionalFunction form to add new one.
     *
     * @Route("/ajax/getForm", name="ajax_get_additionalFunction_form", options={"expose"=true}, methods="GET")
     *
     * @return Response
     */
    public function ajaxAdditionalFunctionFormAction()
    {
        // mise en place formulaire pour ajout additionalFunction
        $additionalFunction = new AdditionalFunction();
        $newAdditionalFunctionForm = $this->createForm(AdditionalFunctionType::class, $additionalFunction);

        return $this->render('additionalfunction/formModal.html.twig', [
            'form' => $newAdditionalFunctionForm->createView(),
        ]);
    }

    /**
     * Creates a new AdditionalFunction entity.
     *
     * @param Request $request
     * @Route("/new_by_ajax", name="ajax_add_new_additionalFunction", methods="POST")
     *
     * @return Response
     */
    public function newByAjaxAction(Request $request)
    {
        $additionalFunction = new AdditionalFunction();
        $form = $this->createForm(AdditionalFunctionType::class, $additionalFunction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($additionalFunction);
            $em->flush();
            $data = ['id' => $additionalFunction->getId(), 'name' => $additionalFunction->getName()];

            return new Response(json_encode($data), 201);
        }

        return new Response('ko', 200);
    }

    /**
     * Creates a form to delete a AdditionalFunction entity.
     *
     * @param AdditionalFunction $additionalFunction The AdditionalFunction entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(AdditionalFunction $additionalFunction)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('complement_function_delete', ['id' => $additionalFunction->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

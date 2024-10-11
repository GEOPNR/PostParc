<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\NatureOfRepresentation;
use PostparcBundle\Form\NatureOfRepresentationType;
use PostparcBundle\FormFilter\NatureOfRepresentationFilterType;

/**
 * NatureOfRepresentation controller.
 *
 * @Route("/natureOfRepresentation")
 */
class NatureOfRepresentationController extends Controller
{
    /**
     * Lists all NatureOfRepresentation entities.
     *
     * @param Request $request
     * @Route("/", name="natureOfRepresentation_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\NatureOfRepresentationFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('natureOfRepresentationFilter');

            return $this->redirect($this->generateUrl('natureOfRepresentation_index'));
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
                $session->set('natureOfRepresentationFilter', $filterData);
            }
        } elseif ($session->has('natureOfRepresentationFilter')) {
            $filterData = $session->get('natureOfRepresentationFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\NatureOfRepresentationFilterType', $filterData, ['data_class' => null]);
        }

        $query = $em->getRepository('PostparcBundle:NatureOfRepresentation')->search($filterData);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page), /*limit per page*/
            [
                'defaultSortFieldName' => 'n.slug',
                'defaultSortDirection' => 'asc',
            ]
        );

        return $this->render('natureOfRepresentation/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new NatureOfRepresentation entity.
     *
     * @param Request $request
     * @Route("/batch", name="natureOfRepresentation_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        if ('batchDelete' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();
                $em->getRepository('PostparcBundle:NatureOfRepresentation')->batchDelete($ids);
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('natureOfRepresentation_index');
    }

    /**
     * Creates a new NatureOfRepresentation entity.
     *
     * @param Request $request
     * @Route("/new", name="natureOfRepresentation_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $natureOfRepresentation = new NatureOfRepresentation();
        $form = $this->createForm(NatureOfRepresentationType::class, $natureOfRepresentation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($natureOfRepresentation);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('natureOfRepresentation_edit', ['id' => $natureOfRepresentation->getId()]);
            } else {
                $natureOfRepresentation = new NatureOfRepresentation();
                $form = $this->createForm(NatureOfRepresentationType::class, $natureOfRepresentation);
            }
        }

        return $this->render('natureOfRepresentation/new.html.twig', [
            'natureOfRepresentation' => $natureOfRepresentation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a NatureOfRepresentation entity.
     *
     * @param NatureOfRepresentation $natureOfRepresentation
     * @Route("/{id}", name="natureOfRepresentation_show", methods="GET", requirements={"id":"\d+"})
     *
     * @return Response
     */
    public function showAction(NatureOfRepresentation $natureOfRepresentation)
    {
        $deleteForm = $this->createDeleteForm($natureOfRepresentation);

        return $this->render('natureOfRepresentation/show.html.twig', [
            'natureOfRepresentation' => $natureOfRepresentation,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing NatureOfRepresentation entity.
     *
     * @param Request $request
     * @param NatureOfRepresentation $natureOfRepresentation
     * @Route("/{id}/edit", name="natureOfRepresentation_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, NatureOfRepresentation $natureOfRepresentation)
    {
        $deleteForm = $this->createDeleteForm($natureOfRepresentation);
        $editForm = $this->createForm(NatureOfRepresentationType::class, $natureOfRepresentation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($natureOfRepresentation);
            $em->flush();

            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('natureOfRepresentation_edit', ['id' => $natureOfRepresentation->getId()]);
        }

        return $this->render('natureOfRepresentation/edit.html.twig', [
            'natureOfRepresentation' => $natureOfRepresentation,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a NatureOfRepresentation entity.
     *
     * @param inetger $id
     * @Route("/{id}/delete", name="natureOfRepresentation_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:NatureOfRepresentation')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('natureOfRepresentation_index');
    }

    /**
     * Creates a form to delete a NatureOfRepresentation entity.
     *
     * @param NatureOfRepresentation $natureOfRepresentation The NatureOfRepresentation entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(NatureOfRepresentation $natureOfRepresentation)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('natureOfRepresentation_delete', ['id' => $natureOfRepresentation->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

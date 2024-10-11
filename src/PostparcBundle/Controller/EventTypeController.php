<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\EventType;
use PostparcBundle\Form\EventTypeType;

/**
 * EventType controller.
 *
 * @Route("/eventType")
 */
class EventTypeController extends Controller
{
    /**
     * Lists all EventType entities.
     *
     * @Route("/", name="eventType_index", methods="GET|POST")
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
        $filterForm = $this->createForm('PostparcBundle\FormFilter\EventTypeFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('eventTypeFilter');

            return $this->redirect($this->generateUrl('eventType_index'));
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
                $session->set('eventTypeFilter', $filterData);
            }
        } elseif ($session->has('eventTypeFilter')) {
            $filterData = $session->get('eventTypeFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\EventTypeFilterType', $filterData, ['data_class' => null]);
        }

        $query = $em->getRepository('PostparcBundle:EventType')->search($filterData);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page)/*limit per page*/
        );

        return $this->render('eventType/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @Route("/batch", name="eventType_batch", methods="POST")
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
                $em->getRepository('PostparcBundle:EventType')->batchDelete($ids);
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('eventType_index');
    }

    /**
     * Creates a new EventType entity.
     *
     * @Route("/new", name="eventType_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $eventType = new EventType();
        $form = $this->createForm('PostparcBundle\Form\EventTypeType', $eventType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($eventType);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('eventType_edit', ['id' => $eventType->getId()]);
            } else {
                $eventType = new EventType();
                $form = $this->createForm('PostparcBundle\Form\EventTypeType', $eventType);
            }
        }

        return $this->render('eventType/new.html.twig', [
            'eventType' => $eventType,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing EventType entity.
     *
     * @Route("/{id}/edit", name="eventType_edit", methods="GET|POST")
     *
     * @param Request   $request
     * @param EventType $eventType
     *
     * @return Response
     */
    public function editAction(Request $request, EventType $eventType)
    {
        $deleteForm = $this->createDeleteForm($eventType);
        $editForm = $this->createForm('PostparcBundle\Form\EventTypeType', $eventType);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($eventType);
            $em->flush();

            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('eventType_edit', ['id' => $eventType->getId()]);
        }

        return $this->render('eventType/edit.html.twig', [
            'eventType' => $eventType,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a EventType entity.
     *
     * @Route("/{id}/delete", name="eventType_delete", methods="GET")
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:EventType')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('eventType_index');
    }

    /**
     * Creates a form to delete a EventType entity.
     *
     * @param EventType $eventType The EventType entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(EventType $eventType)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('eventType_delete', ['id' => $eventType->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * get eventType form to add new one.
     *
     * @Route("/ajax/getForm", name="ajax_get_eventType_form", options={"expose"=true}, methods="GET")
     *
     * @return Response
     */
    public function ajaxEventTypeFormAction()
    {
        // mise en place formulaire pour ajout eventType
        $eventType = new EventType();
        $newEventTypeForm = $this->createForm(EventTypeType::class, $eventType);

        return $this->render('eventType/formModal.html.twig', [
            'form' => $newEventTypeForm->createView(),
        ]);
    }

    /**
     * Creates a new eventType entity.
     *
     * @Route("/new_by_ajax", name="ajax_add_new_eventType", methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newByAjaxAction(Request $request)
    {
        $eventType = new EventType();
        $form = $this->createForm(EventTypeType::class, $eventType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($eventType);
            $em->flush();
            $data = ['id' => $eventType->getId(), 'name' => $eventType->getName()];

            return new Response(json_encode($data), 201);
        }

        return new Response('ko', 200);
    }
}

<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\Service;
use PostparcBundle\Form\ServiceType;

/**
 * Service controller.
 *
 * @Route("/service")
 */
class ServiceController extends Controller
{
    /**
     * Lists all Service entities.
     *
     * @param Request $request
     * @Route("/", name="service_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\ServiceFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('serviceFilter');

            return $this->redirect($this->generateUrl('service_index'));
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
                $session->set('serviceFilter', $filterData);
            }
        } elseif ($session->has('serviceFilter')) {
            $filterData = $session->get('serviceFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\ServiceFilterType', $filterData, ['data_class' => null]);
        }

        $query = $em->getRepository('PostparcBundle:Service')->search($filterData);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page), /*limit per page*/
            [
                'defaultSortFieldName' => 's.slug',
                'defaultSortDirection' => 'asc',
            ]
        );

        return $this->render('service/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new Service entity.
     *
     * @param Request $request
     * @Route("/batch", name="service_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        if ('batchDelete' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();
                $em->getRepository('PostparcBundle:Service')->batchDelete($ids);
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('service_index');
    }

    /**
     * Creates a new Service entity.
     *
     * @param Request $request
     * @Route("/new", name="service_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($service);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('service_edit', ['id' => $service->getId()]);
            } else {
                $service = new Service();
                $form = $this->createForm(ServiceType::class, $service);
            }
        }

        return $this->render('service/new.html.twig', [
            'service' => $service,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Service entity.
     *
     * @param Service $service
     * @Route("/{id}", name="service_show", methods="GET", requirements={"id":"\d+"})
     *
     * @return Response
     */
    public function showAction(Service $service)
    {
        $deleteForm = $this->createDeleteForm($service);

        return $this->render('service/show.html.twig', [
            'service' => $service,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Service entity.
     *
     * @param Request $request
     * @param Service $service
     * @Route("/{id}/edit", name="service_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, Service $service)
    {
        $deleteForm = $this->createDeleteForm($service);
        $editForm = $this->createForm(ServiceType::class, $service);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($service);
            $em->flush();

            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('service_edit', ['id' => $service->getId()]);
        }

        return $this->render('service/edit.html.twig', [
            'service' => $service,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Service entity.
     *
     * @param inetger $id
     * @Route("/{id}/delete", name="service_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:Service')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('service_index');
    }

    /**
     * get Service form to add new one.
     *
     * @Route("/ajax/getForm", name="ajax_get_service_form", options={"expose"=true}, methods="GET")
     *
     * @return Response
     */
    public function ajaxServiceFormAction()
    {
        // mise en place formulaire pour ajout service
        $service = new Service();
        $newServiceForm = $this->createForm(ServiceType::class, $service);

        return $this->render('service/formModal.html.twig', [
            'form' => $newServiceForm->createView(),
        ]);
    }

    /**
     * Creates a new Service entity.
     *
     * @param Request $request
     * @Route("/new_by_ajax", name="ajax_add_new_service", methods="POST")
     *
     * @return Response
     */
    public function newByAjaxAction(Request $request)
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($service);
            $em->flush();
            $data = ['id' => $service->getId(), 'name' => $service->getName()];

            return new Response(json_encode($data), 201);
        }

        return new Response('ko', 200);
    }

    /**
     * Creates a form to delete a Service entity.
     *
     * @param Service $service The Service entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Service $service)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('service_delete', ['id' => $service->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

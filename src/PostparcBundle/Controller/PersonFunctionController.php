<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\PersonFunction;
use PostparcBundle\Form\PersonFunctionType;

/**
 * PersonFunction controller.
 *
 * @Route("/function")
 */
class PersonFunctionController extends Controller
{
    /**
     * Lists all PersonFunction entities.
     *
     * @Route("/", name="function_index", methods="GET|POST")
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
        $filterForm = $this->createForm('PostparcBundle\FormFilter\PersonFunctionFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('personFunctionFilter');

            return $this->redirect($this->generateUrl('function_index'));
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
                $session->set('personFunctionFilter', $filterData);
            }
        } elseif ($session->has('personFunctionFilter')) {
            $filterData = $session->get('personFunctionFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\PersonFunctionFilterType', $filterData, ['data_class' => null]);
        }
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');

        $query = $em->getRepository('PostparcBundle:PersonFunction')->search($filterData);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page), /*limit per page*/
            [
                'defaultSortFieldName' => 'pf.slug',
                'defaultSortDirection' => 'asc',
            ]
        );

        return $this->render('personfunction/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @Route("/batch", name="function_batch", methods="POST")
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
                $em->getRepository('PostparcBundle:PersonFunction')->batchDelete($ids);
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('function_index');
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @Route("/new", name="function_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $personFunction = new PersonFunction();
        $form = $this->createForm(PersonFunctionType::class, $personFunction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($personFunction);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('function_edit', ['id' => $personFunction->getId()]);
            } else {
                $personFunction = new PersonFunction();
                $form = $this->createForm(PersonFunctionType::class, $personFunction);
            }
        }

        return $this->render('personfunction/new.html.twig', [
            'personFunction' => $personFunction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a PersonFunction entity.
     *
     * @Route("/{id}", name="function_show", requirements={"id":"\d+"})
     * @Route("/{id}", name="personFunction_show", requirements={"id":"\d+"})
     *
     * @param PersonFunction $personFunction
     *
     * @return Response
     */
    public function showAction(PersonFunction $personFunction)
    {
        return $this->redirectToRoute('function_edit', ['id' => $personFunction->getId()]);
    }

    /**
     * Displays a form to edit an existing PersonFunction entity.
     *
     * @Route("/{id}/edit", name="function_edit", methods="GET|POST")
     *
     * @param Request        $request
     * @param PersonFunction $personFunction
     *
     * @return Response
     */
    public function editAction(Request $request, PersonFunction $personFunction)
    {
        $lockService = $this->container->get('postparc.lock_service');
        if ($lockService->isLock($personFunction, $this->getParameter('maxLockDuration'))) {
            return $this->redirect($this->generateUrl('lock-message', ['className' => $personFunction->getClassName(), 'objectId' => $personFunction->getId()]));
        }
        $deleteForm = $this->createDeleteForm($personFunction);
        $editForm = $this->createForm(PersonFunctionType::class, $personFunction);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($personFunction);
            $em->flush();
            $lockService->unlockObject($personFunction);
            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('function_edit', ['id' => $personFunction->getId()]);
        }

        return $this->render('personfunction/edit.html.twig', [
            'personFunction' => $personFunction,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a PersonFunction entity.
     *
     * @Route("/{id}/delete", name="function_delete", methods="GET|DELETE")
     * @Route("/{id}/delete", name="personFunction_delete", methods="GET|DELETE")
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:PersonFunction')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('function_index');
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @Route("/new_by_ajax", name="ajax_add_new_personFunction", methods="POST", options={"expose"=true})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newByAjaxAction(Request $request)
    {
        $personFunction = new PersonFunction();
        $form = $this->createForm(PersonFunctionType::class, $personFunction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($personFunction);
            $em->flush();
            $data = ['id' => $personFunction->getId(), 'name' => $personFunction->getName()];

            return new Response(json_encode($data), 201);
        }

        return new Response('ko', 200);
    }

    /**
     * get PersonFunction form to add new one.
     *
     * @Route("/ajax/getForm", name="ajax_get_personFunction_form", options={"expose"=true}, methods="GET")
     *
     * @return Response
     */
    public function ajaxPersonFunctionFormAction()
    {
        // mise en place formulaire pour ajout personFunction
        $personFunction = new PersonFunction();
        $newPersonFunctionForm = $this->createForm(PersonFunctionType::class, $personFunction);

        return $this->render('personfunction/formModal.html.twig', [
            'form' => $newPersonFunctionForm->createView(),
        ]);
    }

    /**
     * Creates a form to delete a PersonFunction entity.
     *
     * @param PersonFunction $personFunction The PersonFunction entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(PersonFunction $personFunction)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('function_delete', ['id' => $personFunction->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\Profession;
use PostparcBundle\Form\ProfessionType;

/**
 * Profession controller.
 *
 * @Route("/profession")
 */
class ProfessionController extends Controller
{
    /**
     * Lists all Profession entities.
     *
     * @Route("/", name="profession_index", methods="GET|POST")
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
        $filterForm = $this->createForm('PostparcBundle\FormFilter\ProfessionFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('professionFilter');

            return $this->redirect($this->generateUrl('profession_index'));
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
                $session->set('professionFilter', $filterData);
            }
        } elseif ($session->has('professionFilter')) {
            $filterData = $session->get('professionFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\ProfessionFilterType', $filterData, ['data_class' => null]);
        }

        $query = $em->getRepository('PostparcBundle:Profession')->search($filterData);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page)/*limit per page*/
        );

        return $this->render('profession/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @Route("/batch", name="profession_batch", methods="POST")
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
                $em->getRepository('PostparcBundle:Profession')->batchDelete($ids);
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('profession_index');
    }

    /**
     * Creates a new Profession entity.
     *
     * @Route("/new", name="profession_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $profession = new Profession();
        $form = $this->createForm(ProfessionType::class, $profession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($profession);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('profession_edit', ['id' => $profession->getId()]);
            } else {
                $profession = new Profession();
                $form = $this->createForm(ProfessionType::class, $profession);
            }
        }

        return $this->render('profession/new.html.twig', [
            'profession' => $profession,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Profession entity.
     *
     * @Route("/{id}", name="profession_show", methods="GET", requirements={"id":"\d+"})
     *
     * @param Profession $profession
     *
     * @return Response
     */
    public function showAction(Profession $profession)
    {
        $deleteForm = $this->createDeleteForm($profession);

        return $this->render('profession/show.html.twig', [
            'profession' => $profession,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Profession entity.
     *
     * @Route("/{id}/edit", name="profession_edit", methods="GET|POST")
     *
     * @param Request    $request
     * @param Profession $profession
     *
     * @return Response
     */
    public function editAction(Request $request, Profession $profession)
    {
        $deleteForm = $this->createDeleteForm($profession);
        $editForm = $this->createForm(ProfessionType::class, $profession);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($profession);
            $em->flush();

            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('profession_edit', ['id' => $profession->getId()]);
        }

        return $this->render('profession/edit.html.twig', [
            'profession' => $profession,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Profession entity.
     *
     * @Route("/{id}/delete", name="profession_delete", methods="GET")
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:Profession')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('profession_index');
    }

    /**
     * get Profession form to add new one.
     *
     * @Route("/ajax/getForm", name="ajax_get_profession_form", options={"expose"=true}, methods="GET")
     *
     * @return Response
     */
    public function ajaxProfessionFormAction()
    {
        // mise en place formulaire pour ajout profession
        $profession = new Profession();
        $newProfessionForm = $this->createForm(ProfessionType::class, $profession);

        return $this->render('profession/formModal.html.twig', [
            'form' => $newProfessionForm->createView(),
        ]);
    }

    /**
     * Creates a new Profession entity.
     *
     * @Route("/new_by_ajax", name="ajax_add_new_profession", methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newByAjaxAction(Request $request)
    {
        $profession = new Profession();
        $form = $this->createForm(ProfessionType::class, $profession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($profession);
            $em->flush();
            $data = ['id' => $profession->getId(), 'name' => $profession->getName()];

            return new Response(json_encode($data), 201);
        }

        return new Response('ko', 200);
    }

    /**
     * Creates a form to delete a Profession entity.
     *
     * @param Profession $profession The Profession entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Profession $profession)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('profession_delete', ['id' => $profession->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

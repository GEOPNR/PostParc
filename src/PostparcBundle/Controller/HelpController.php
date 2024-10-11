<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\Help;

/**
 * Help controller.
 *
 * @Route("/help")
 */
class HelpController extends Controller
{
    /**
     * Lists all Help entities.
     *
     * @Route("/", name="help_index", methods="GET|POST")
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
        $filterForm = $this->createForm('PostparcBundle\FormFilter\HelpFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('helpFilter');

            return $this->redirect($this->generateUrl('help_index'));
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
                $session->set('helpFilter', $filterData);
            }
        } elseif ($session->has('helpFilter')) {
            $filterData = $session->get('helpFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\HelpFilterType', $filterData, ['data_class' => null]);
        }

        $query = $em->getRepository('PostparcBundle:Help')->search($filterData);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page)/*limit per page*/
        );

        return $this->render('help/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @Route("/batch", name="help_batch", methods="POST")
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
                $em->getRepository('PostparcBundle:Help')->batchDelete($ids);
                $this->addFlash('success', 'flash.deleteMassiveSuccess');
            }
        }

        return $this->redirectToRoute('help_index');
    }

    /**
     * Creates a new Help entity.
     *
     * @Route("/new", name="help_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $help = new Help();
        $form = $this->createForm('PostparcBundle\Form\HelpType', $help);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($help);
            $em->flush();
            
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                $this->redirectToRoute('help_index');
            } else {
                $help = new Help();
                $form = $this->createForm('PostparcBundle\Form\HelpType', $help);
            }
        }

        return $this->render('help/new.html.twig', [
            'help' => $help,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Help entity.
     *
     * @Route("/{id}/edit", name="help_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param Help    $help
     *
     * @return Response
     */
    public function editAction(Request $request, Help $help)
    {
        if ($request->query->has('locale')) {
            $help->setTranslatableLocale($request->query->get('locale'));
        } else {
            $help->setTranslatableLocale($request->getLocale());
        }
        $editForm = $this->createForm('PostparcBundle\Form\HelpType', $help);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($help);
            $em->flush();
            
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.editSuccess');

            return $this->redirectToRoute('help_edit', ['id' => $help->getId()]);
        }

        return $this->render('help/edit.html.twig', [
            'help' => $help,
            'edit_form' => $editForm->createView(),
        ]);
    }

    /**
     * Deletes a Help entity.
     *
     * @Route("/{id}/delete", name="help_delete", methods="GET")
     *
     * @param Request $request
     * @param Help    $help
     *
     * @return Response
     */
    public function deleteAction(Request $request, Help $help)
    {
        $ids = [$help->getId()];
        
        $em = $this->getDoctrine()->getManager();
        $em->getRepository('PostparcBundle:Help')->batchDelete($ids);
            
        $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('help_index');
    }

}

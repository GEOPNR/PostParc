<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\PrintFormat;

/**
 * PrintFormat controller.
 *
 * @Route("/print-format")
 */
class PrintFormatController extends Controller
{
    /**
     * Lists all PrintFormat entities.
     *
     * @param Request $request
     * @Route("/", name="print_format_index", methods="GET")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        // pas des filtres pour le moment sur cette page
        $filterData = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $query = $em->getRepository('PostparcBundle:PrintFormat')->search($filterData, $entityId, $show_SharedContents);

        $paginator = $this->get('knp_paginator');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');

        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page)/*limit per page*/
        );

        return $this->render('printformat/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @param Request $request
     * @Route("/batch", name="print_format_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        if ('batchDelete' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();
                $em->getRepository('PostparcBundle:printFormat')->batchDelete($ids, $entityId, $this->getUser());
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('print_format_index');
    }

    /**
     * Creates a new PrintFormat entity.
     *
     * @param Request $request
     * @Route("/new", name="print_format_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $printFormat = new PrintFormat();
        $form = $this->createForm('PostparcBundle\Form\PrintFormatType', $printFormat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($printFormat);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                //return $this->redirectToRoute('print_format_show', array('id' => $printFormat->getId()));
                return $this->redirectToRoute('print_format_index');
            } else {
                $printFormat = new PrintFormat();
                $form = $this->createForm('PostparcBundle\Form\PrintFormatType', $printFormat);
            }
        }

        return $this->render('printformat/new.html.twig', [
            'printFormat' => $printFormat,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing PrintFormat entity.
     *
     * @param Request     $request
     * @param PrintFormat $printFormat
     * @Route("/{id}/edit", name="print_format_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, PrintFormat $printFormat)
    {
        $deleteForm = $this->createDeleteForm($printFormat);
        $editForm = $this->createForm('PostparcBundle\Form\PrintFormatType', $printFormat);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($printFormat);
            $em->flush();

            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('print_format_edit', ['id' => $printFormat->getId()]);
        }

        return $this->render('printformat/edit.html.twig', [
            'printFormat' => $printFormat,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a PrintFormat entity.
     *
     * @param PrintFormat $printFormat
     * @Route("/{id}", name="print_format_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction(Request $request, PrintFormat $printFormat)
    {
        $checkAccessService = $this->container->get('postparc.check_access');

        if (!$checkAccessService->checkAccess($printFormat)) {
            $referer = $request->headers->get('referer');
            $this->addFlash('info', 'flash.accessDenied');

            return $referer ? $this->redirect($referer) : $this->redirectToRoute('print_format_index');
        }

        $em = $this->getDoctrine()->getManager();
        $ids = [$printFormat->getId()];
        $em->getRepository('PostparcBundle:PrintFormat')->batchDelete($ids, null, $this->getUser());

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('print_format_index');
    }

    /**
     * Creates a form to delete a PrintFormat entity.
     *
     * @param PrintFormat $printFormat The PrintFormat entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(PrintFormat $printFormat)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('print_format_delete', ['id' => $printFormat->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

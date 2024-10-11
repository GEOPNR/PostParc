<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\DocumentTemplate;

/**
 * DocumentTemplate controller.
 *
 * @Route("/documentTemplate")
 */
class DocumentTemplateController extends Controller
{
    /**
     * Lists all DocumentTemplate entities.
     *
     * @Route("/", name="documentTemplate_index", methods="GET|POST")
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
        $filterForm = $this->createForm('PostparcBundle\FormFilter\DocumentTemplateFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('documentTemplateFilter');

            return $this->redirect($this->generateUrl('documentTemplate_index'));
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
                $session->set('documentTemplateFilter', $filterData);
            }
        } elseif ($session->has('documentTemplateFilter')) {
            $filterData = $session->get('documentTemplateFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\DocumentTemplateFilterType', $filterData, ['data_class' => null]);
        }
        $filterData['currentUser'] = $this->getUser();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $query = $em->getRepository('PostparcBundle:DocumentTemplate')->search($filterData, $entityId, $show_SharedContents);
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page)/*limit per page*/
        );

        return $this->render('documentTemplate/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @Route("/batch", name="documentTemplate_batch", methods="POST")
     *
     * @param Request $request
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
                $em->getRepository('PostparcBundle:DocumentTemplate')->batchDelete($ids, $entityId, $this->getUser());
                $this->addFlash('success', 'flash.deleteSuccess');
            }
        }

        return $this->redirectToRoute('documentTemplate_index');
    }

    /**
     * Creates a new DocumentTemplate entity.
     *
     * @Route("/new", name="documentTemplate_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $documentTemplate = new DocumentTemplate();
        $generalDocumentParameters = $this->container->getParameter('document');
        $documentTemplate->setMarginTop($generalDocumentParameters['marginTop']);
        $documentTemplate->setMarginBottom($generalDocumentParameters['marginBottom']);
        $documentTemplate->setMarginLeft($generalDocumentParameters['marginLeft']);
        $documentTemplate->setMarginRight($generalDocumentParameters['marginRight']);
        $documentTemplate->setEnv($this->container->get('kernel')->getEnvironment());

        $form = $this->createForm('PostparcBundle\Form\DocumentTemplateType', $documentTemplate, ['currentUser'=>$this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ajout baseUrl pour les nettoyage des urls des images
            $baseUrl = $request->getScheme() . '://' . $request->getHost() . $request->getBaseUrl();
            $body = str_replace('src="/uploads/documentTemplateImages', 'src="' . $baseUrl . '/uploads/documentTemplateImages', $documentTemplate->getBody());
            $documentTemplate->setBody($body);

            $em = $this->getDoctrine()->getManager();
            $em->persist($documentTemplate);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('documentTemplate_edit', ['id' => $documentTemplate->getId()]);
            } else {
                $documentTemplate = new DocumentTemplate();
                $generalDocumentParameters = $this->container->getParameter('document');
                $documentTemplate->setMarginTop($generalDocumentParameters['marginTop']);
                $documentTemplate->setMarginBottom($generalDocumentParameters['marginBottom']);
                $documentTemplate->setMarginLeft($generalDocumentParameters['marginLeft']);
                $documentTemplate->setMarginRight($generalDocumentParameters['marginRight']);
                $documentTemplate->setEnv($this->container->get('kernel')->getEnvironment());

                $form = $this->createForm('PostparcBundle\Form\DocumentTemplateType', $documentTemplate, ['currentUser'=>$this->getUser()]);
            }
        }

        return $this->render('documentTemplate/new.html.twig', [
            'documentTemplate' => $documentTemplate,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing DocumentTemplate entity.
     *
     * @Route("/{id}/edit", name="documentTemplate_edit", methods="GET|POST")
     *
     * @param Request          $request
     * @param DocumentTemplate $documentTemplate
     *
     * @return Response
     */
    public function editAction(Request $request, DocumentTemplate $documentTemplate)
    {
        $deleteForm = $this->createDeleteForm($documentTemplate);
        $oldImage = $documentTemplate->getImage();
        $editForm = $this->createForm('PostparcBundle\Form\DocumentTemplateType', $documentTemplate, ['currentUser'=>$this->getUser()]);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // ajout baseUrl pour les nettoyage des urls des images
            $baseUrl = $request->getScheme() . '://' . $request->getHost() . $request->getBaseUrl();
            $body = str_replace('src="/uploads/documentTemplateImages', 'src="' . $baseUrl . '/uploads/documentTemplateImages', $documentTemplate->getBody());
            $documentTemplate->setBody($body);

            $em = $this->getDoctrine()->getManager();
            $em->persist($documentTemplate);
            $em->flush();
            $newImage = $documentTemplate->getImage();
            if ($oldImage && !$newImage) {
                $documentTemplate->setImage($oldImage);
                $em->persist($documentTemplate);
                $em->flush();
            }

            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('documentTemplate_edit', ['id' => $documentTemplate->getId()]);
        }

        return $this->render('documentTemplate/edit.html.twig', [
            'documentTemplate' => $documentTemplate,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a DocumentTemplate entity.
     *
     * @Route("/{id}/delete", name="documentTemplate_delete", methods="GET")
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:DocumentTemplate')->batchDelete($ids, null, $this->getUser());

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('documentTemplate_index');
    }

    /**
     * Creates a form to delete a DocumentTemplate entity.
     *
     * @param DocumentTemplate $documentTemplate The DocumentTemplate entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(DocumentTemplate $documentTemplate)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('documentTemplate_delete', ['id' => $documentTemplate->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

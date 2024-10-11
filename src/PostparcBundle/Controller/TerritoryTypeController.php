<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\TerritoryType;

/**
 * TerritoryType controller.
 *
 * @Route("/territoryType")
 */
class TerritoryTypeController extends Controller
{
    /**
     * Lists all TerritoryType entities.
     *
     * @param Request $request
     * @Route("/", name="territoryType_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\TerritoryTypeFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('territoryTypeFilter');

            return $this->redirect($this->generateUrl('territoryType_index'));
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
                if ($filterForm->has('updatedBy') && $filterForm->get('updatedBy')->getData()) {
                    $filterData['updatedBy'] = $filterForm->get('updatedBy')->getData()->getId();
                }
                $session->set('territoryTypeFilter', $filterData);
            }
        } elseif ($session->has('territoryTypeFilter')) {
            $filterData = $session->get('territoryTypeFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\TerritoryTypeFilterType');
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
            if (array_key_exists('updatedBy', $filterData)) {
                $updatedBy = $em->getRepository('PostparcBundle:User')->find($filterData['updatedBy']);
                $filterForm->get('updatedBy')->setData($updatedBy);
            }
        }

        $query = $em->getRepository('PostparcBundle:TerritoryType')->search($filterData);

        $repo = $em->getRepository('PostparcBundle:TerritoryType');

        $options = ['decorate' => false];

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        return $this->render('territoryType/index.html.twig', [
            'search_form' => $filterForm->createView(),
            'htmlTree' => $htmlTree,
            'nbResults' => count($query->getArrayResult()),
        ]);
    }

    /**
     * Creates a new TerritoryType entity.
     *
     * @param Request $request
     * @Route("/new", name="territoryType_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $territoryType = new TerritoryType();
        $form = $this->createForm('PostparcBundle\Form\TerritoryTypeType', $territoryType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($territoryType);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('territoryType_edit', ['id' => $territoryType->getId()]);
            } else {
                $territoryType = new TerritoryType();
                $form = $this->createForm('PostparcBundle\Form\TerritoryTypeType', $territoryType);
            }
        }

        return $this->render('territoryType/new.html.twig', [
            'territoryType' => $territoryType,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @param Request $request
     * @Route("/batch", name="territoryType_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = $request->request->get('ids');

        if ($ids && $request->request->get('batch_action') === 'batchDelete') {
            $em = $this->getDoctrine()->getManager();
            $em->getRepository('PostparcBundle:TerritoryType')->batchDelete($ids);
            $this->addFlash('success', 'flash.deleteMassiveSuccess');
        }

        return $this->redirectToRoute('territoryType_index');
    }

    /**
     * Displays a form to edit an existing TerritoryType entity.
     *
     * @param Request       $request
     * @param TerritoryType $territoryType
     * @Route("/{id}/edit", name="territoryType_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, TerritoryType $territoryType)
    {
        $deleteForm = $this->createDeleteForm($territoryType);
        $editForm = $this->createForm('PostparcBundle\Form\TerritoryTypeType', $territoryType);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($territoryType);
            $em->flush();

            $this->addFlash('success', 'flash.updateSuccess');

            return $this->redirectToRoute('territoryType_edit', ['id' => $territoryType->getId()]);
        }

        return $this->render('territoryType/edit.html.twig', [
            'territoryType' => $territoryType,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a TerritoryType entity.
     *
     * @param int $id
     * @Route("/{id}/delete", name="territoryType_delete")
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:TerritoryType')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('territoryType_index');
    }

    /**
     * Creates a new TerritoryType entity.
     *
     * @param Request       $request
     * @param TerritoryType $parent
     * @Route("/new/{id}", name="territoryType_new_subTerritoryType", methods="GET|POST")
     *
     * @return Response
     */
    public function newsubTerritoryTypeAction(Request $request, TerritoryType $parent)
    {
        $territoryType = new TerritoryType();
        $territoryType->setParent($parent);
        $form = $this->createForm('PostparcBundle\Form\TerritoryTypeType', $territoryType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($territoryType);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('territoryType_edit', ['id' => $territoryType->getId()]);
            } else {
                $territoryType = new TerritoryType();
                $form = $this->createForm('PostparcBundle\Form\TerritoryTypeType', $territoryType);
            }
        }

        return $this->render('territoryType/new.html.twig', [
            'territoryType' => $territoryType,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a form to delete a TerritoryType entity.
     *
     * @param TerritoryType $territoryType The TerritoryType entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(TerritoryType $territoryType)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('territoryType_delete', ['id' => $territoryType->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

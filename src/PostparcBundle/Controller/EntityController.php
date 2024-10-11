<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\Entity;
use PostparcBundle\Form\EntityType;

/**
 * Entity controller.
 *
 * @Route("/admin/entity")
 */
class EntityController extends Controller
{
    /**
     * Lists all Entity entities.
     *
     * @Route("/", name="entity_index", methods="GET|POST")
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
        $filterForm = $this->createForm('PostparcBundle\FormFilter\EntityFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('entityFilter');

            return $this->redirect($this->generateUrl('entity_index'));
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
                $session->set('entityFilter', $filterData);
            }
        } elseif ($session->has('entityFilter')) {
            $filterData = $session->get('entityFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\EntityFilterType');
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
            if (array_key_exists('updatedBy', $filterData)) {
                $updatedBy = $em->getRepository('PostparcBundle:User')->find($filterData['updatedBy']);
                $filterForm->get('updatedBy')->setData($updatedBy);
            }
        }

        $query = $em->getRepository('PostparcBundle:Entity')->search($filterData);

        $repo = $em->getRepository('PostparcBundle:Entity');

        $options = ['decorate' => false];

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        return $this->render('entity/index.html.twig', [
            'search_form' => $filterForm->createView(),
            'htmlTree' => $htmlTree,
            'nbResults' => count($query->getArrayResult()),
        ]);
    }

    /**
     * batch routes for entity.
     *
     * @Route("/batch", name="entity_batch", methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = $request->request->get('ids');

        if ($ids && $request->request->get('batch_action') === 'batchDelete') {
            $em = $this->getDoctrine()->getManager();
            $em->getRepository('PostparcBundle:Entity')->batchDelete($ids, null, $this->getUser());
            $this->addFlash('success', 'flash.deleteMassiveSuccess');
        }

        return $this->redirectToRoute('entity_index');
    }

    /**
     * Displays a form to edit an existing Entity entity.
     *
     * @Route("/{id}/edit", name="entity_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param Entity  $entity
     *
     * @return Response
     */
    public function editAction(Request $request, Entity $entity)
    {
        $deleteForm = $this->createDeleteForm($entity);
        $editForm = $this->createForm('PostparcBundle\Form\EntityType', $entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('success', 'flash.updateSuccess');

            return $this->redirectToRoute('entity_edit', ['id' => $entity->getId()]);
        }

        return $this->render('entity/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Entity entity.
     *
     * @Route("/{id}/delete", name="entity_delete", methods="GET")
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:Entity')->batchDelete($ids, null, $this->getUser());

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('entity_index');
    }

    /**
     * Creates a new Entity entity.
     *
     * @Route("/new/{id}", name="entity_new_subEntity", methods="GET|POST")
     *
     * @param Request $request
     * @param Entity  $parent
     *
     * @return Response
     */
    public function newsubEntityAction(Request $request, Entity $parent)
    {
        $entity = new Entity();
        $entity->setParent($parent);
        $form = $this->createForm(EntityType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('entity_edit', ['id' => $entity->getId()]);
            } else {
                $entity = new Entity();
                $form = $this->createForm(EntityType::class, $entity);
            }
        }

        return $this->render('entity/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'parent' => $parent,
        ]);
    }

    /**
     * Creates a form to delete a Entity entity.
     *
     * @param Entity $entity The Entity entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Entity $entity)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('entity_delete', ['id' => $entity->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

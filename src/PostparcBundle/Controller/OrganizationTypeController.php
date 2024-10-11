<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\OrganizationType;

/**
 * OrganizationType controller.
 *
 * @Route("/organizationType")
 */
class OrganizationTypeController extends Controller
{
    /**
     * Lists all OrganizationType entities.
     *
     * @Route("/", name="organizationType_index", methods="GET|POST")
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
        $filterForm = $this->createForm('PostparcBundle\FormFilter\OrganizationTypeFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('organizationTypeFilter');

            return $this->redirect($this->generateUrl('organizationType_index'));
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
                $session->set('organizationTypeFilter', $filterData);
            }
        } elseif ($session->has('organizationTypeFilter')) {
            $filterData = $session->get('organizationTypeFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\OrganizationTypeFilterType');
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
            if (array_key_exists('updatedBy', $filterData)) {
                $updatedBy = $em->getRepository('PostparcBundle:User')->find($filterData['updatedBy']);
                $filterForm->get('updatedBy')->setData($updatedBy);
            }
        }

        $query = $em->getRepository('PostparcBundle:OrganizationType')->search($filterData);

        $repo = $em->getRepository('PostparcBundle:OrganizationType');

        $options = ['decorate' => false];

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        return $this->render('organizationType/index.html.twig', [
            'search_form' => $filterForm->createView(),
            'htmlTree' => $htmlTree,
            'nbResults' => count($query->getArrayResult()),
        ]);
    }

    /**
     * Creates a new OrganizationType entity.
     *
     * @Route("/new", name="organizationType_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $organizationType = new OrganizationType();
        $form = $this->createForm('PostparcBundle\Form\OrganizationTypeType', $organizationType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($organizationType);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');
            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('organizationType_edit', ['id' => $organizationType->getId()]);
            } else {
                $organizationType = new OrganizationType();
                $form = $this->createForm('PostparcBundle\Form\OrganizationTypeType', $organizationType);
            }
        }

        return $this->render('organizationType/new.html.twig', [
            'organizationType' => $organizationType,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @Route("/batch", name="organizationType_batch", methods="POST")
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
            $em->getRepository('PostparcBundle:OrganizationType')->batchDelete($ids);
            $this->addFlash('success', 'flash.deleteMassiveSuccess');
        }

        return $this->redirectToRoute('organizationType_index');
    }

    /**
     * Displays a form to edit an existing OrganizationType entity.
     *
     * @Route("/{id}/edit", name="organizationType_edit", methods="GET|POST")
     *
     * @param Request          $request
     * @param OrganizationType $organizationType
     *
     * @return Response
     */
    public function editAction(Request $request, OrganizationType $organizationType)
    {
        $deleteForm = $this->createDeleteForm($organizationType);
        $editForm = $this->createForm('PostparcBundle\Form\OrganizationTypeType', $organizationType);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($organizationType);
            $em->flush();
            $this->addFlash('success', 'flash.updateSuccess');

            return $this->redirectToRoute('organizationType_edit', ['id' => $organizationType->getId()]);
        }

        return $this->render('organizationType/edit.html.twig', [
            'organizationType' => $organizationType,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a OrganizationType entity.
     *
     * @Route("/{id}/delete", name="organizationType_delete", methods="GET")
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:OrganizationType')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('organizationType_index');
    }

    /**
     * Creates a new OrganizationType entity.
     *
     * @Route("/new/{id}", name="organizationType_new_subOrganizationType", methods="GET|POST")
     *
     * @param Request          $request
     * @param OrganizationType $parent
     *
     * @return Response
     */
    public function newsubOrganizationTypeAction(Request $request, OrganizationType $parent)
    {
        $organizationType = new OrganizationType();
        $organizationType->setParent($parent);
        $form = $this->createForm('PostparcBundle\Form\OrganizationTypeType', $organizationType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($organizationType);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('organizationType_edit', ['id' => $organizationType->getId()]);
            } else {
                $organizationType = new OrganizationType();
                $form = $this->createForm('PostparcBundle\Form\OrganizationTypeType', $organizationType);
            }
        }

        return $this->render('organizationType/new.html.twig', [
            'organizationType' => $organizationType,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a form to delete a OrganizationType entity.
     *
     * @param OrganizationType $organizationType The OrganizationType entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(OrganizationType $organizationType)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('organizationType_delete', ['id' => $organizationType->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\MandateType;
use PostparcBundle\Form\MandateTypeType;

/**
 * MandateType controller.
 *
 * @Route("/mandateType")
 */
class MandateTypeController extends Controller
{
    /**
     * Lists all MandateType entities.
     *
     * @param Request $request
     * @Route("/", name="mandateType_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\MandateTypeFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('mandateTypeFilter');

            return $this->redirect($this->generateUrl('mandateType_index'));
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
                $session->set('mandateTypeFilter', $filterData);
            }
        } elseif ($session->has('mandateTypeFilter')) {
            $filterData = $session->get('mandateTypeFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\MandateTypeFilterType');
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
            if (array_key_exists('updatedBy', $filterData)) {
                $updatedBy = $em->getRepository('PostparcBundle:User')->find($filterData['updatedBy']);
                $filterForm->get('updatedBy')->setData($updatedBy);
            }
        }

        $query = $em->getRepository('PostparcBundle:MandateType')->search($filterData);

        $repo = $em->getRepository('PostparcBundle:MandateType');

        $options = ['decorate' => false];

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        return $this->render('mandateType/index.html.twig', [
            'search_form' => $filterForm->createView(),
            'htmlTree' => $htmlTree,
            'nbResults' => count($query->getArrayResult()),
        ]);
    }

    /**
     * Creates a new MandateType entity.
     *
     * @param Request $request
     * @Route("/new", name="mandateType_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $mandateType = new MandateType();
        $form = $this->createForm('PostparcBundle\Form\MandateTypeType', $mandateType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($mandateType);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('mandateType_edit', ['id' => $mandateType->getId()]);
            } else {
                $mandateType = new MandateType();
                $form = $this->createForm('PostparcBundle\Form\MandateTypeType', $mandateType);
            }
        }

        return $this->render('mandateType/new.html.twig', [
            'mandateType' => $mandateType,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @param Request $request
     * @Route("/batch", name="mandateType_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = $request->request->get('ids');

        if ($ids && $request->request->get('batch_action') === 'batchDelete') {
            $em = $this->getDoctrine()->getManager();
            $em->getRepository('PostparcBundle:MandateType')->batchDelete($ids);
            $this->addFlash('success', 'flash.deleteMassiveSuccess');
        }

        return $this->redirectToRoute('mandateType_index');
    }

    /**
     * Displays a form to edit an existing MandateType entity.
     *
     * @param Request     $request
     * @param MandateType $mandateType
     * @Route("/{id}/edit", name="mandateType_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, MandateType $mandateType)
    {
        $deleteForm = $this->createDeleteForm($mandateType);
        $editForm = $this->createForm('PostparcBundle\Form\MandateTypeType', $mandateType);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($mandateType);
            $em->flush();

            $this->addFlash('success', 'flash.updateSuccess');

            return $this->redirectToRoute('mandateType_edit', ['id' => $mandateType->getId()]);
        }

        return $this->render('mandateType/edit.html.twig', [
            'mandateType' => $mandateType,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a MandateType entity.
     *
     * @param int $id
     * @Route("/{id}/delete", name="mandateType_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$id];
        $em->getRepository('PostparcBundle:MandateType')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('mandateType_index');
    }

    /**
     * Creates a new MandateType entity.
     *
     * @param Request     $request
     * @param MandateType $parent
     * @Route("/new/{id}", name="mandateType_new_subMandateType", methods="GET|POST")
     *
     * @return Response
     */
    public function newsubMandateTypeAction(Request $request, MandateType $parent)
    {
        $mandateType = new MandateType();
        $mandateType->setParent($parent);
        $form = $this->createForm('PostparcBundle\Form\MandateTypeType', $mandateType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($mandateType);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('mandateType_edit', ['id' => $mandateType->getId()]);
            } else {
                $mandateType = new MandateType();
                $form = $this->createForm('PostparcBundle\Form\MandateTypeType', $mandateType);
            }
        }

        return $this->render('mandateType/new.html.twig', [
            'mandateType' => $mandateType,
            'form' => $form->createView(),
        ]);
    }

    /**
     * get mandateType form to add new one.
     *
     * @Route("/ajax/getForm", name="ajax_get_mandateType_form", options={"expose"=true}, methods="GET")
     *
     * @return Response
     */
    public function ajaxMandateTypeFormAction()
    {
        // mise en place formulaire pour ajout mandateType
        $mandateType = new MandateType();
        $newMandateTypeForm = $this->createForm(MandateTypeType::class, $mandateType);

        return $this->render('mandateType/formModal.html.twig', [
            'form' => $newMandateTypeForm->createView(),
        ]);
    }

    /**
     * Creates a new mandateType entity.
     *
     * @Route("/new_by_ajax", name="ajax_add_new_mandateType", options={"expose"=true}, methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newByAjaxAction(Request $request)
    {
        $mandateType = new MandateType();
        $form = $this->createForm(MandateTypeType::class, $mandateType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($mandateType);
            $em->flush();
            $data = ['id' => $mandateType->getId(), 'name' => $mandateType->getName()];

            return new Response(json_encode($data), 201);
        }

        return new Response('ko', 200);
    }

    /**
     * Creates a form to delete a MandateType entity.
     *
     * @param MandateType $mandateType The MandateType entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(MandateType $mandateType)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('mandateType_delete', ['id' => $mandateType->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

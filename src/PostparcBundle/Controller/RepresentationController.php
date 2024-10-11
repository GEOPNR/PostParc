<?php

namespace PostparcBundle\Controller;

use PostparcBundle\Entity\Representation;
use PostparcBundle\Entity\Group;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Cocur\Slugify\Slugify;

/**
 * Representation controller.
 *
 * @Route("representation")
 */
class RepresentationController extends Controller
{
    /**
     * Lists all representation entities.
     *
     * @Route("/", name="representation_index", methods="GET")
     *
     * @return view
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $representations = $em->getRepository('PostparcBundle:Representation')->findAll();

        return $this->render('representation/index.html.twig', [
            'representations' => $representations,
        ]);
    }

    /**
     * Creates a new representation entity.
     *
     * @param Request $request
     * @param int     $personId id of the person
     * @param int     $pfoId    id of the pfo
     * @Route("/new", name="representation_new", methods="GET|POST")
     *
     * @return view
     */
    public function newAction(Request $request, $personId = null, $pfoId = null)
    {
        $em = $this->getDoctrine()->getManager();
        $representation = new Representation();
        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';

        $form = $this->createForm('PostparcBundle\Form\RepresentationType', $representation, [
            'action' => $this->generateUrl('representation_new') . '?personId=' . $personId . '&pfoId=' . $pfoId,
            'method' => 'POST',
        ]);

        $form->add('save', SubmitType::class, ['label' => 'actions.save', 'attr' => ['class' => 'btn btn-primary']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personId = $request->query->has('personId') ? $request->query->get('personId') : '';
            $pfoId = $request->query->has('pfoId') ? $request->query->get('pfoId') : '';
            $em = $this->getDoctrine()->getManager();
            $this->addFlash('success', 'flash.addSuccess');
            if ($pfoId) {
                $pfo = $em->getRepository('PostparcBundle:Pfo')->find($pfoId);
                if ($pfo !== null) {
                    $representation->setPfo($pfo);
                    $em->persist($representation);
                    $em->flush($representation);

                    return $this->redirectToRoute('pfo_show', ['id' => $pfoId]);
                }
            } elseif ($personId) {
                $person = $em->getRepository('PostparcBundle:Person')->find($personId);
                if ($person !== null) {
                    $representation->setPerson($person);
                    $em->persist($representation);
                    $em->flush($representation);

                    return $this->redirectToRoute('person_show', ['id' => $personId, 'origin' => $origin, 'activePfo' => $pfoId]);
                }
            }
        }

        return $this->render('representation/new.html.twig', [
            'representation' => $representation,
            'form' => $form->createView(),
            'origin' => $origin,
            'personId' => $personId,
            'pfoId' => $pfoId,
        ]);
    }

    /**
     * Finds and displays a representation entity.
     *
     * @param Representation $representation
     * @Route("/{id}", name="representation_show", methods="GET", requirements={"id":"\d+"})
     *
     * @return view
     */
    public function showAction(Representation $representation, Request $request)
    {
        $checkAccessService = $this->container->get('postparc.check_access');

        if (!$checkAccessService->checkAccess($representation)) {
            $referer = $request->headers->get('referer');
            $this->addFlash('info', 'flash.accessDenied');

            return $referer ? $this->redirect($referer) : $this->redirectToRoute('representation_index');
        }

        $deleteForm = $this->createDeleteForm($representation);

        return $this->render('representation/show.html.twig', [
            'representation' => $representation,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing representation entity.
     *
     * @param Request        $request
     * @param Representation $representation
     * @Route("/{id}/edit", name="representation_edit", methods="GET|POST")
     *
     * @return view
     */
    public function editAction(Request $request, Representation $representation)
    {
        $lockService = $this->container->get('postparc.lock_service');
        if ($lockService->isLock($representation, $this->getParameter('maxLockDuration'))) {
            return $this->redirect($this->generateUrl('lock-message', ['className' => $representation->getClassName(), 'objectId' => $representation->getId()]));
        }
        $deleteForm = $this->createDeleteForm($representation);
        $options = [];
        $allowChangePerson = false;
        $allowChangePfo = false;
        if ($representation->getPerson() !== null) {
            $options['attr']['allowChangePerson'] = true;
            $allowChangePerson = true;
        }
        if ($representation->getPfo() !== null) {
            $options['attr']['allowChangePfo'] = true;
            $allowChangePfo = true;
        }
        $editForm = $this->createForm('PostparcBundle\Form\RepresentationType', $representation, $options);
        $editForm->handleRequest($request);
        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'flash.editSuccess');
            $lockService->unlockObject($representation);
            if ($origin) {
                if ($representation->getPfo() !== null) {
                    $origin .= '?activePfo=' . $representation->getPfo()->getId();
                }

                return $this->redirect($origin);
            }

            return $this->redirectToRoute('representation_edit', ['id' => $representation->getId()]);
        }

        return $this->render('representation/edit.html.twig', [
            'representation' => $representation,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'origin' => $origin,
            'allowChangePerson' => $allowChangePerson,
            'allowChangePfo' => $allowChangePfo,
        ]);
    }

    /**
     * Deletes a representation entity.
     *
     * @param Request        $request
     * @param Representation $representation
     * @Route("/{id}", name="representation_delete", options={"expose" = true}, methods="DELETE")
     *
     * @return Response
     */
    public function deleteAction(Request $request, Representation $representation)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$representation->getId()];
        $em->getRepository('PostparcBundle:Representation')->batchDelete($ids, null, $this->getUser());

        return new Response('done.', 201);
    }

    /**
     * Deletes a representation entity.
     *
     * @param Representation $representation
     * @Route("/{id}/deleteFromOrigin", name="representation_delete_with_origin", methods="GET")
     *
     * @return Response
     */
    public function deleteWithRouteAction(Request $request, Representation $representation)
    {
        $em = $this->getDoctrine()->getManager();
        if ($representation->getDeletedAt()) {
            $em->remove($representation);
        } else {
            // soft delete
            $now = new \Datetime();
            $representation->setDeletedAt($now);
            $representation->setDeletedBy($this->getUser());
            $em->persist($representation);
        }

        $em->flush($representation);

        $request->getSession()->getFlashBag()->add('success', 'flash.deleteSuccess');

        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';

        return $this->redirect($origin);
    }

    /**
     * add this personn to the basket.
     *
     * @param Request        $request
     * @param Representation $representation
     * @Route("/{id}/addBasket", name="representation_addBasket", methods="GET")
     *
     * @return Response
     */
    public function addToBasketAction(Request $request, Representation $representation)
    {
        $session = $request->getSession();
        $selectionData = [];
        $alreadyExist = false;
        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';
        // Get filter from session
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            if (array_key_exists('representationIds', $selectionData)) {
                if (!in_array($representation->getId(), $selectionData['representationIds'])) {
                    $selectionData['representationIds'][] = $representation->getId();
                } else {
                    $alreadyExist = true;
                }
            } else {
                $selectionData['representationIds'] = [$representation->getId()];
            }
        } else {
            $selectionData['representationIds'] = [$representation->getId()];
        }

        $session->set('selection', $selectionData);

        if ($alreadyExist) {
            $request->getSession()
            ->getFlashBag()
            ->add('error', 'Representation.actions.addBasket.alreadyPresent');
        } else {
            $request->getSession()
            ->getFlashBag()
            ->add('success', 'Representation.actions.addBasket.success');
        }

        if ($origin) {
            if ($representation->getPfo() !== null) {
                $origin .= '?activePfo=' . $representation->getPfo()->getId();
            }

            return $this->redirect($origin);
        }

        return $this->redirectToRoute('search-homepage');
    }

    /**
     * remove this representation to one group.
     *
     * @param Request        $request
     * @param Representation $representation
     * @Route("/{id}/removeToGroup/{groupId}", name="representation_removeFromGroup", methods="GET")
     *
     * @return Response
     */
    public function removeToGroupAction(Representation $representation, $groupId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('PostparcBundle:Group')->find($groupId);
        $representation->removeGroup($group);
        $em->persist($representation);
        $em->flush();

        $request->getSession()
            ->getFlashBag()
            ->add('success', 'Representation.actions.removeFromGroup.success');

        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';
        if ($origin) {
            return $this->redirect($origin);
        }

        return $this->redirect($this->generateUrl('group_listPersonn', ['id' => $group->getId()]));
    }

    /**
     * Batch actions.
     *
     * @param Request $request
     * @Route("/batch", name="representation_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = $request->request->get('representationIds');
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();

        if ($ids) {
            switch ($request->request->get('batch_action')) {
                case 'batchDelete':
                    $em->getRepository('PostparcBundle:Representation')->batchDelete($ids, $entityId, $this->getUser());
                    $this->addFlash('success', 'flash.deleteMassiveSuccess');

                    break;

                case 'batchAddBasket':
                    $session = $request->getSession();
                    $selectionData = [];
                    if ($session->has('selection')) {
                        $selectionData = $session->get('selection');

                        if (array_key_exists('representationIds', $selectionData)) {
                            foreach ($ids as $id) {
                                if (!in_array($id, $selectionData['representationIds'])) {
                                    $selectionData['representationIds'][] = $id;
                                }
                            }
                        } else {
                            $selectionData['representationIds'] = $ids;
                        }
                    } else {
                        $selectionData['representationIds'] = $ids;
                    }

                    $session->set('selection', $selectionData);

                    $request->getSession()
                        ->getFlashBag()
                        ->add('success', 'Representation.actions.addBasket.successPlural');

                    break;
            }
        }
        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';

        return $this->redirect($origin);
    }

    



    /**
     * Creates a form to delete a representation entity.
     *
     * @param Representation $representation The representation entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Representation $representation)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('representation_delete', ['id' => $representation->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

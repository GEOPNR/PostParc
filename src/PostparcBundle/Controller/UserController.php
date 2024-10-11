<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\User;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use PostparcBundle\FormFilter\UserFilterType;

/**
 * User controller.
 *
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * show Event Alerts on Interface.
     *
     * @Route("/showEventAlertOnInterface", name="eventAlert_showOnInterface")
     *
     * @return Response
     */
    public function showAlertMessages()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $eventalerts = $em->getRepository('PostparcBundle:EventAlert')->getEventAlertsHaveToBeprintOnInterfaceForUser($user->getId());
        foreach ($eventalerts as $eventAlert) {
            $this->get('session')->getFlashBag()->add('modalAlertMessages', $eventAlert->getMessage());
            $eventAlert->setIsShowInInterfaceByOrganizator(1);
            $em->persist($eventAlert);
        }
        $em->flush();

        return $this->redirect($this->generateUrl('homepage'));
    }

    /**
     * Lists all User entities.
     *
     * @Route("/", name="user_index", methods="GET|POST")
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
        $filterForm = $this->createForm(UserFilterType::class);

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('userFilter');

            return $this->redirect($this->generateUrl('user_index'));
        }

        // Filter action
        if ('filter' == $request->get('filter_action')) {
            // Bind values from the request
            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                // Save filter to session
                if ($filterForm->has('lastName')) {
                    $filterData['lastName'] = $filterForm->get('lastName')->getData();
                }
                if ($filterForm->has('username')) {
                    $filterData['username'] = $filterForm->get('username')->getData();
                }
                $session->set('userFilter', $filterData);
            }
        } elseif ($session->has('userFilter')) {
            $filterData = $session->get('userFilter');
            if (count($filterData) > 0) {
                $filterForm = $this->createForm(UserFilterType::class, $filterData, ['data_class' => null]);
            } else {
                $filterForm = $this->createForm(UserFilterType::class);
            }
        }

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $query = $em->getRepository('PostparcBundle:User')->search($filterData, $user);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page)/*limit per page*/
        );

        return $this->render('user/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new User entity.
     *
     * @Route("/new", name="user_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm('PostparcBundle\Form\UserType', $user, ['user' => $this->get('security.token_storage')->getToken()->getUser()]);
        $form->handleRequest($request);

        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN') && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN_MULTI_INSTANCE')) {
            $user->setEntity($this->get('security.token_storage')->getToken()->getUser()->getEntity());
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('user_index');
            } else {
                $user = new User();
                $form = $this->createForm('PostparcBundle\Form\UserType', $user, ['user' => $this->get('security.token_storage')->getToken()->getUser()]);
            }
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/{id}", name="user_show", requirements={"id":"\d+"})
     *
     * @param User $user
     *
     * @return Response
     */
    public function showAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/{id}/edit", name="user_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param User    $user
     *
     * @return Response
     */
    public function editAction(Request $request, User $user)
    {
        if (true === $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->get('security.token_storage')->getToken()->getUser()->getId() === $user->getId()) {
            // Si admin ou si sa propre fiche user
        } elseif (true === $this->get('security.authorization_checker')->isGranted('ROLE_CONTRIBUTOR_PLUS')) {
            if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_CONTRIBUTOR_PLUS')) {
                $this->get('session')->getFlashBag()->add('danger', 'User.errors.not_allowed');

                return $this->redirect($this->generateUrl('user_index'));
            }
        } elseif (true === $this->get('security.authorization_checker')->isGranted('ROLE_CONTRIBUTOR')) {
            if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_CONTRIBUTOR_PLUS') || $user->hasRole('ROLE_CONTRIBUTOR')) {
                $this->get('session')->getFlashBag()->add('danger', 'User.errors.not_allowed');

                return $this->redirect($this->generateUrl('user_index'));
            }
        } elseif ($this->get('security.token_storage')->getToken()->getUser()->getId() != $user->getId()) {
            $this->get('session')->getFlashBag()->add('danger', 'User.errors.not_allowed');

            return $this->redirect($this->generateUrl('user_index'));
        }
        // nettoyage eventuels pour les doubles roles
        $deleteForm = $this->createDeleteForm($user);
        $editForm = $this->createForm('PostparcBundle\Form\UserType', $user, ['user' => $this->get('security.token_storage')->getToken()->getUser()]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /* Gestion changement mot de passe */
            $userManager = $this->get('fos_user.user_manager');
            $this->get('event_dispatcher')->dispatch(FOSUserEvents::CHANGE_PASSWORD_SUCCESS, new FormEvent($editForm, $request));
            $userManager->updateUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'flash.updateSuccess');

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a User entity.
     *
     * @Route("/{id}/delete", name="user_delete")
     *
     * @param Request $request
     * @param User    $user
     *
     * @return Response
     */
    public function deleteAction(Request $request, User $user)
    {
        $loggedUser = $this->container->get('security.authorization_checker');
        if (
            $loggedUser->isGranted('ROLE_SUPER_ADMIN') || ($loggedUser->isGranted('ROLE_ADMIN') || $loggedUser->isGranted('ROLE_CONTRIBUTOR_PLUS') && !$user->hasRole('ROLE_ADMIN') || $loggedUser->isGranted('ROLE_CONTRIBUTOR') && !$user->hasRole('ROLE_CONTRIBUTOR_PLUS') && $this->getUser()->getEntity()->getId() == $user->getEntity()->getId())
        ) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'flash.deleteSuccess');
        } else {
            $this->get('session')->getFlashBag()->add('danger', 'User.errors.not_allowed');
        }

        return $this->redirectToRoute('user_index');
    }
    
    /**
     * Batch actions.
     *
     * @param Request $request
     * @Route("/batch", name="user_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = $request->request->get('ids');

        if ($ids) {
            switch ($request->request->get('batch_action')) {
                case 'batchDelete':
                    $em->getRepository('PostparcBundle:User')->batchDelete($ids);
                    $this->addFlash('success', 'flash.deleteSuccess');

                    break;
            }
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * Creates a form to delete a User entity.
     *
     * @param User $user The User entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', ['id' => $user->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

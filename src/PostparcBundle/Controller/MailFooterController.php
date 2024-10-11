<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\MailFooter;

/**
 * MailFooter controller.
 *
 * @Route("/mailFooter")
 */
class MailFooterController extends Controller
{
    /**
     * Lists all MailFooter entities.
     *
     * @param Request $request
     * @Route("/", name="mailFooter_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $user = $this->get('security.token_storage')->getToken()->getUser();


        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\MailFooterFilterType', null, ['user' => $user]);

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('mailFooterFilter');

            return $this->redirect($this->generateUrl('mailFooter_index'));
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
                if ($filterForm->has('user') && null != $filterForm->get('user')->getData()) {
                    $filterData['user'] = $filterForm->get('user')->getData()->getId();
                }
                $session->set('mailFooterFilter', $filterData);
            }
        } elseif ($session->has('mailFooterFilter')) {
            $filterData = $session->get('mailFooterFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\MailFooterFilterType', $filterData, ['data_class' => null, 'user' => $user]);
            if (array_key_exists('user', $filterData)) {
                $user = $em->getRepository('PostparcBundle:user')->find($filterData['user']);
                $filterForm->get('user')->setData($user);
            }
        }
        $query = $em->getRepository('PostparcBundle:MailFooter')->search($filterData, $user);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $request->query->getInt('per_page', $default_items_per_page)/*limit per page*/
        );

        return $this->render('mailFooter/index.html.twig', [
            'pagination' => $pagination,
            'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @param Request $request
     * @Route("/batch", name="mailFooter_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        if ('batchDelete' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();
                $em->getRepository('PostparcBundle:MailFooter')->batchDelete($ids);
                $this->addFlash('success', 'flash.deleteMassiveSuccess');
            }
        }

        return $this->redirectToRoute('mailFooter_index');
    }

    /**
     * Creates a new MailFooter entity.
     *
     * @param Request $request
     * @Route("/new", name="mailFooter_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $mailFooter = new MailFooter();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $mailFooter->setUser($user);
        $form = $this->createForm('PostparcBundle\Form\MailFooterType', $mailFooter, ['user' => $user]);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($mailFooter);
            $em->flush();

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('mailFooter_index');
            } else {
                $mailFooter = new MailFooter();
                $mailFooter->setUser($user);
                $form = $this->createForm('PostparcBundle\Form\MailFooterType', $mailFooter, ['user' => $user]);
            }
            
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.addSuccess');
        }

        return $this->render('mailFooter/new.html.twig', [
            'mailFooter' => $mailFooter,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing MailFooter entity.
     *
     * @param Request    $request
     * @param MailFooter $mailFooter
     * @Route("/{id}/edit", name="mailFooter_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, MailFooter $mailFooter)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $editForm = $this->createForm('PostparcBundle\Form\MailFooterType', $mailFooter, ['user' => $user]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($mailFooter);
            $em->flush();
            
            $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.editSuccess');

            return $this->redirectToRoute('mailFooter_edit', ['id' => $mailFooter->getId()]);
        }

        return $this->render('mailFooter/edit.html.twig', [
            'mailFooter' => $mailFooter,
            'edit_form' => $editForm->createView()
        ]);
    }

    /**
     * Deletes a MailFooter entity.
     *
     * @param Request    $request
     * @param MailFooter $mailFooter
     * @Route("/{id}/delete", name="mailFooter_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction(Request $request, MailFooter $mailFooter)
    {
        $ids = [$mailFooter->getId()];
        
        $em = $this->getDoctrine()->getManager();
        $em->getRepository('PostparcBundle:MailFooter')->batchDelete($ids);
            
        $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.deleteSuccess');
        
        return $this->redirectToRoute('mailFooter_index');
    }


}

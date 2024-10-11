<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\City;

/**
 * City controller.
 *
 * @Route("/city")
 */
class CityController extends Controller
{
    /**
     * Lists all City entities.
     *
     * @param Request $request
     * @Route("/", name="city_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\CityFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('cityFilter');

            return $this->redirect($this->generateUrl('city_index'));
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
                if ($filterForm->has('insee')) {
                    $filterData['insee'] = $filterForm->get('insee')->getData();
                }
                if ($filterForm->has('zipCode')) {
                    $filterData['zipCode'] = $filterForm->get('zipCode')->getData();
                }
                if ($filterForm->has('department')) {
                    $filterData['department'] = $filterForm->get('department')->getData();
                }
                if ($filterForm->has('isActive')) {
                    $filterData['isActive'] = $filterForm->get('isActive')->getData();
                }
                if ($filterForm->has('territories')) {
                    $filterData['territories'] = $filterForm->get('territories')->getData();
                }
                if ($filterForm->has('id')) {
                    $filterData['id'] = $filterForm->get('id')->getData();
                }
                $session->set('cityFilter', $filterData);
            }
        } elseif ($session->has('cityFilter')) {
            $filterData = $session->get('cityFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\CityFilterType', $filterData, ['data_class' => null]);
        }

        $query = $em->getRepository('PostparcBundle:City')->search($filterData);
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
            'defaultSortFieldName' => 'c.slug',
            'defaultSortDirection' => 'asc',
            ]
        );

        return $this->render('city/index.html.twig', [
              'pagination' => $pagination,
              'search_form' => $filterForm->createView(),
        ]);
    }

    /**
     * Creates a new PersonFunction entity.
     *
     * @param Request $request
     * @Route("/batch", name="city_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        if ($request->request->has('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();

                switch ($request->request->get('batch_action')) {
                    case 'batchDelete':
                        $em->getRepository('PostparcBundle:City')->batchDelete($ids);
                        $this->addFlash('success', 'flash.deleteSuccess');
                        break;
                    case 'batchActivate':
                        $cities = $em->getRepository('PostparcBundle:City')->findBy(['id' => $ids]);
                        $haveToBeFlush = false;
                        foreach ($cities as $city) {
                            $city->setIsActive(true);
                            $em->persist($city);
                            $haveToBeFlush = true;
                        }
                        if ($haveToBeFlush) {
                            $em->flush();
                        }
                        $this->addFlash('success', 'flash.activateSuccess');
                        break;
                    case 'batchUnActivate':
                        $cities = $em->getRepository('PostparcBundle:City')->findBy(['id' => $ids]);
                        $haveToBeFlush = false;
                        foreach ($cities as $city) {
                            $city->setIsActive(false);
                            $em->persist($city);
                            $haveToBeFlush = true;
                        }
                        if ($haveToBeFlush) {
                            $em->flush();
                        }
                        $this->addFlash('success', 'flash.unactivateSuccess');
                        break;
                }
            } else {
                $this->addFlash('error', 'batch.noSelectedItemError');
            }
        }

        return $this->redirectToRoute('city_index');
    }

    /**
     * Creates a new City entity.
     *
     * @param Request $request
     * @Route("/new", name="city_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $city = new City();
        $form = $this->createForm('PostparcBundle\Form\CityType', $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($city);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('city_edit', ['id' => $city->getId()]);
            } else {
                $city = new City();
                $form = $this->createForm('PostparcBundle\Form\CityType', $city);
            }
        }

        return $this->render('city/new.html.twig', [
              'city' => $city,
              'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing City entity.
     *
     * @param Request $request
     * @param City    $city
     * @Route("/{id}/edit", name="city_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, City $city)
    {
        $deleteForm = $this->createDeleteForm($city);
        $editForm = $this->createForm('PostparcBundle\Form\CityType', $city);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($city);
            $em->flush();

            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('city_edit', ['id' => $city->getId()]);
        }

        return $this->render('city/edit.html.twig', [
              'city' => $city,
              'edit_form' => $editForm->createView(),
              'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a City entity.
     *
     * @param City $city
     * @Route("/{id}/delete", name="city_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction(City $city)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($city);
        $em->flush();

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('city_index');
    }

    /**
     * Creates a form to delete a City entity.
     *
     * @param City $city The City entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(City $city)
    {
        return $this->createFormBuilder()
                    ->setAction($this->generateUrl('city_delete', ['id' => $city->getId()]))
                    ->setMethod('DELETE')
                    ->getForm()
        ;
    }
}

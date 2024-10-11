<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\Territory;
use PostparcBundle\Entity\City;

/**
 * Territory controller.
 *
 * @Route("/territory")
 */
class TerritoryController extends Controller
{
    /**
     * Lists all Territory entities.
     *
     * @param Request $request
     * @Route("/", name="territory_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\TerritoryFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('territoryFilter');

            return $this->redirect($this->generateUrl('territory_index'));
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
                if ($filterForm->has('territoryType') && $filterForm->get('territoryType')->getData()) {
                    $filterData['territoryType'] = $filterForm->get('territoryType')->getData()->getId();
                }

                $session->set('territoryFilter', $filterData);
            }
        } elseif ($session->has('territoryFilter')) {
            $filterData = $session->get('territoryFilter');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\TerritoryFilterType');
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
            if (array_key_exists('territoryType', $filterData)) {
                $organizationType = $em->getRepository('PostparcBundle:TerritoryType')->find($filterData['territoryType']);
                $filterForm->get('territoryType')->setData($organizationType);
            }
        }

        $query = $em->getRepository('PostparcBundle:Territory')->search($filterData, );
        $repo = $em->getRepository('PostparcBundle:Territory');

        $options = ['decorate' => false];

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        return $this->render('territory/index.html.twig', [
            'search_form' => $filterForm->createView(),
            'htmlTree' => $htmlTree,
            'nbResults' => count($query->getArrayResult()),
        ]);
    }

    /**
     * Creates a new Territory entity.
     *
     * @param Request $request
     * @Route("/new", name="territory_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $territory = new territory();
        $form = $this->createForm('PostparcBundle\Form\TerritoryType', $territory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($territory);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('territory_edit', ['id' => $territory->getId()]);
            } else {
                $territory = new territory();
                $form = $this->createForm('PostparcBundle\Form\TerritoryType', $territory);
            }
        }

        return $this->render('territory/new.html.twig', [
            'territory' => $territory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Territory entity.
     *
     * @param Request   $request
     * @param Territory $territory
     * @Route("/{id}", name="territory_show", methods="GET", requirements={"id":"\d+"})
     *
     * @return Response
     */
    public function showAction(Request $request, Territory $territory)
    {
        $checkAccessService = $this->container->get('postparc.check_access');

        if (!$checkAccessService->checkAccess($territory)) {
            $referer = $request->headers->get('referer');
            $this->addFlash('info', 'flash.accessDenied');

            return $referer ? $this->redirect($referer) : $this->redirectToRoute('territory_index');
        }

        return $this->render('territory/show.html.twig', [
            'territory' => $territory,
        ]);
    }

    /**
     * Displays a form to edit an existing territory entity.
     *
     * @param Request   $request
     * @param Territory $territory
     * @Route("/{id}/edit", name="territory_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, Territory $territory)
    {
        $deleteForm = $this->createDeleteForm($territory);
        $editForm = $this->createForm('PostparcBundle\Form\TerritoryType', $territory);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($territory);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.updateSuccess');

            return $this->redirectToRoute('territory_edit', ['id' => $territory->getId()]);
        }

        return $this->render('territory/edit.html.twig', [
            'territory' => $territory,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Delete a territory entity.
     *
     * @param Request   $request
     * @param Territory $territory
     * @Route("/{id}/delete", name="territory_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction(Request $request, Territory $territory)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$territory->getId()];
        $em->getRepository('PostparcBundle:Territory')->batchDelete($ids, null, $this->getUser());
        $request->getSession()
        ->getFlashBag()
        ->add('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('territory_index');
    }

    /**
     * Creates a new Territory entity.
     *
     * @param Request   $request
     * @param Territory $parent
     * @Route("/new/{id}", name="territory_new_subTerritory", methods="GET|POST")
     *
     * @return Response
     */
    public function newSubTerritoryAction(Request $request, Territory $parent)
    {
        $territory = new Territory();
        $territory->setParent($parent);
        $form = $this->createForm('PostparcBundle\Form\TerritoryType', $territory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($territory);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('territory_edit', ['id' => $territory->getId()]);
            } else {
                $territory = new territory();
                $form = $this->createForm('PostparcBundle\Form\TerritoryType', $territory);
            }
        }

        return $this->render('territory/new.html.twig', [
            'territory' => $territory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * territory batch actions.
     *
     * @param Request $request
     * @Route("/batch", name="territory_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        if ('batchDelete' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();
                $em->getRepository('PostparcBundle:Territory')->batchDelete($ids, null, $this->getUser());
                $request->getSession()
                ->getFlashBag()
                ->add('success', 'batch.deleteSuccess');
            } else {
                $request->getSession()
                ->getFlashBag()
                ->add('error', 'batch.noSelectedItemError');
            }
        }

        return $this->redirectToRoute('territory_index');
    }

    /**
     * territory batch actions.
     *
     * @Route("/{id}/batch", name="territory_batchCities", methods="POST")
     *
     * @param Territory $territory
     * @param Request   $request
     *
     * @return Response
     */
    public function batchCitiesAction(Territory $territory, Request $request)
    {
        if ('batchDeleteFromTerritory' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $stmt = $this->getDoctrine()->getEntityManager()
                   ->getConnection()
                    ->prepare('DELETE FROM territories_cities where territory_id='.$territory->getId().' AND city_id IN ('.implode($ids, ',').')');
                $stmt->execute();

                $request->getSession()
                ->getFlashBag()
                ->add('success', 'batch.deleteSuccess');
            } else {
                $request->getSession()
                ->getFlashBag()
                ->add('error', 'batch.noSelectedItemError');
            }
        }

        return $this->redirect($this->generateUrl('territory_listCities', ['id' => $territory->getId()]));
    }

    /**
     * add city ton one territory.
     *
     * @Route("/{id}/addCity", name="territory_addCity", methods="POST")
     *
     * @param Territory $territory The Territory entity
     * @param Request   $request
     *
     * @return Response
     */
    public function addCityAction(Territory $territory, Request $request)
    {
        $cityId = $request->request->get('cityId');
        $alreadyAssociateCities = $territory->getCities();
        if ($cityId) {
            $em = $this->getDoctrine()->getManager();
            $city = $em->getRepository('PostparcBundle:City')->find($cityId);
            if ($city !== null) {
                if ($alreadyAssociateCities->contains($city)) {
                    $request->getSession()
                    ->getFlashBag()
                    ->add('error', 'Territory.actions.alreadyPresent');
                } else {
                    $territory->addCity($city);
                    $em->persist($territory);
                    $em->flush();
                    $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'flash.add_city_to_territorySuccess');
                }
            }
        } else {
            $request->getSession()
            ->getFlashBag()
            ->add('error', 'batch.noSelectedItemError');
        }

        return $this->redirect($this->generateUrl('territory_listCities', ['id' => $territory->getId()]));
    }

    /**
     * List all Cities ssociate to one Territory.
     *
     * @Route("/{id}/listCities", name="territory_listCities")
     *
     * @param Territory $territory The Territory entity
     * @param Request   $request
     *
     * @return Response
     */
    public function listCitiesAction(Territory $territory, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $request->getSession();

        $territoryId = $territory->getId();
        $name = null;

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\CityFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('TerritoryCitiesFilterType');

            return $this->redirect($this->generateUrl('territory_listCities', ['id' => $territoryId]));
        }

        // Filter action
        if ('filter' == $request->get('filter_action')) {
            // Bind values from the request
            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                // Save filter to session
                if ($filterForm->has('territories')) {
                    $filterData['territories'] = $filterForm->get('territories')->getData();
                    if ($filterData['territories']) {
                        $territory = $filterData['territories'];
                    }
                }
                if ($filterForm->has('name')) {
                    $filterData['name'] = $filterForm->get('name')->getData();
                    $name = $filterData['name'];
                }
                $session->set('TerritoryCitiesFilterType', $filterData);
            }
        } elseif ($session->has('TerritoryCitiesFilterType')) {
            $filterData = $session->get('TerritoryCitiesFilterType');
            if (array_key_exists('territories', $filterData) && $filterData['territories']) {
                $territory = $filterData['territories'];
            }
            if ($territory) {
                $territory = $this->getDoctrine()->getEntityManager()->merge($territory);
                if ($filterData['territories']) {
                    $territory = $filterData['territories'];
                }
            }
            $filterForm = $this->createForm('PostparcBundle\FormFilter\CityFilterType', $filterData, ['data_class' => null]);
            if ($filterData['territories']) {
                $territory = $em->merge($filterData['territories']);
            }
            $name = $filterData['name'];
        } else {
            $filterData = $session->get('TerritoryCitiesFilterType');
            $filterData['territories'] = $territory;
            $filterData['name'] = $name;
            $filterForm = $this->createForm('PostparcBundle\FormFilter\CityFilterType', $filterData, ['data_class' => null]);
        }

        $cities = $em->getRepository('PostparcBundle:City')->listTerritoryCities($territory ? $territory->getId() : $territoryId, $name);

        return $this->render('territory/listCities.html.twig', [
            'cities' => $cities,
            'territory' => $territory,
            'search_form' => $filterForm->createView(),
            'subFolder' => false,
        ]);
    }

    /**
     * List all Cities associate to one Territory en sub territories.
     *
     * @Route("/{id}/listSubTerritoryCities", name="territory_listSubTerritoryCities")
     *
     * @param Territory $territory The Territory entity
     * @param Request   $request
     *
     * @return Response
     */
    public function listSubTerritoryCitiesAction(Territory $territory, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $request->getSession();

        $territoryId = $territory->getId();
        $name = null;

        $filterData = [];
        $filterForm = $this->createForm('PostparcBundle\FormFilter\CityFilterType');

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('SubTerritoryCitiesFilterType');

            return $this->redirect($this->generateUrl('territory_listSubTerritoryCities', ['id' => $territoryId]));
        }

        // Filter action
        if ('filter' == $request->get('filter_action')) {
            // Bind values from the request
            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                // Save filter to session
                if ($filterForm->has('territories')) {
                    $filterData['territories'] = $filterForm->get('territories')->getData();
                    $territory = $filterData['territories'];
                }
                if ($filterForm->has('name')) {
                    $filterData['name'] = $filterForm->get('name')->getData();
                    $name = $filterData['name'];
                }
                $session->set('SubTerritoryCitiesFilterType', $filterData);
            }
        } elseif ($session->has('SubTerritoryCitiesFilterType')) {
            $filterData = $session->get('SubTerritoryCitiesFilterType');
            if (array_key_exists('territories', $filterData)) {
                $territory = $filterData['territories'];
            }
            if ($territory) {
                $territory = $this->getDoctrine()->getEntityManager()->merge($territory);
                $filterData['territories'] = $territory;
            }
            $filterForm = $this->createForm('PostparcBundle\FormFilter\CityFilterType', $filterData, ['data_class' => null]);
            $territory = $em->merge($filterData['territories']);
            $name = $filterData['name'];
        } else {
            $filterData = $session->get('SubTerritoryCitiesFilterType');
            $filterData['territories'] = $territory;
            $filterData['name'] = $name;
            $filterForm = $this->createForm('PostparcBundle\FormFilter\CityFilterType', $filterData, ['data_class' => null]);
        }
        $childrens = $em->getRepository('PostparcBundle:Territory')->getChildren($node = $territory, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
        $cities = $em->getRepository('PostparcBundle:City')->listSubTerritoryCities($childrens, $name);

        return $this->render('territory/listCities.html.twig', [
            'cities' => $cities,
            'territory' => $territory,
            'search_form' => $filterForm->createView(),
            'subFolder' => true,
        ]);
    }

    /**
     * Delete Association between one territory and one city.
     *
     * @Route("/{id}/deleteCity/{cityId}", name="territory_deleteCity")
     *
     * @param Request   $request
     * @param Territory $territory The Territory entity
     * @param int       $cityId    id of The City entity
     *
     * @return Response
     */
    public function deleteCityToTerritoryAction(Request $request, Territory $territory, $cityId)
    {
        $em = $this->getDoctrine()->getManager();
        $city = $em->getRepository('PostparcBundle:City')->find($cityId);

        $territory->removeCity($city);
        $city->removeTerritory($territory);
        $em->persist($city);
        $em->persist($territory);
        $em->flush();

        $request->getSession()
        ->getFlashBag()
        ->add('success', 'flash.deleteSuccess');

        return $this->redirect($this->generateUrl('territory_listCities', ['id' => $territory->getId()]));
    }

    /**
     * Delete All Association between one city ans territories.
     *
     * @Route("/{id}/deleteCity/{cityId}/massive", name="territory_SubdeleteCity")
     *
     * @param Request   $request
     * @param Territory $territory The Territory entity
     * @param int       $cityId    id of The City entity
     *
     * @return Response
     */
    public function deleteCityToMassiveTerritoryAction(Request $request, Territory $territory, $cityId)
    {
        $em = $this->getDoctrine()->getManager();
        $city = $em->getRepository('PostparcBundle:City')->find($cityId);

        $territories = $city->getTerritories();
        foreach ($territories as $territory) {
            $territory->removeCity($city);
            $city->removeTerritory($territory);
            $em->persist($city);
            $em->persist($territory);
        }
        $em->flush();

        $request->getSession()
        ->getFlashBag()
        ->add('success', 'flash.deleteMassiveSuccess');

        return $this->redirect($this->generateUrl('territory_listSubTerritoryCities', ['id' => $territory->getId()]));
    }

    /**
     * Creates a form to delete a Territory entity.
     *
     * @param Territory $territory The Territory entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Territory $territory)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('territory_delete', ['id' => $territory->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

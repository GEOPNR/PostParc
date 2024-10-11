<?php

namespace PostparcApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest; // alias pour toutes les annotations
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\View; // Utilisation de la vue de FOSRestBundle
use PostparcBundle\Entity\Group;
use PostparcBundle\Entity\Territory;
use PostparcBundle\Entity\PersonFunction;
use PostparcBundle\Entity\Tag;
use PostparcBundle\Entity\OrganizationType;
use PostparcBundle\Entity\Service;
use FOS\RestBundle\Controller\Annotations\QueryParam;

/**
 * List controller.
 *
 *  */
class ListRestController extends Controller
{

    /**
     * Lists all Group entities.
     * @Rest\View()
     * @Rest\Get("/groups" )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Lists all Group entities.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"full"}))
     *     )
     * )
     * @QueryParam(name="name", description="The name of the group.")
     * @QueryParam(name="groupIds[]", description="array of groups ids.")
     *
     *
     */
    public function getGroupsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $searchParams = $request->query->all();
        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        // query
        $repo = $em->getRepository('PostparcBundle:Group');
        $htmlTree = [];
        $groups = null;
        if (isset($searchParams['name']) &&  strlen($searchParams['name']) > 0) {
            $groups = $repo->autoComplete($searchParams['name'], $entityId, $currentEntityConfig['show_SharedContents']);
        }
        if (isset($searchParams['groupIds']) && count($searchParams['groupIds']) > 0) {
            $groups = $em->getRepository('PostparcBundle:Group')->findBy(['id' => $searchParams['groupIds']]);
        }
        if (!$groups) {
            $groups = $repo->getGroupsForSelect(null, null, $entityId, $currentEntityConfig['show_SharedContents']);
        }
        foreach ($groups as $group) {
            array_push($htmlTree, $group->getApiFormated());
        }
        // Récupération du view handler
        $viewHandler = $this->get('fos_rest.view_handler');
        // Création d'une vue FOSRestBundle
        $view = View::create($htmlTree);

        // Gestion de la réponse
        return $viewHandler->handle($view);
    }

    /**
     * Lists all Territory entities.
     * @Rest\View()
     * @Rest\Get("/territories" )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Lists all Territory entities.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Territory::class, groups={"full"}))
     *     )
     * )
     * @QueryParam(name="name", description="The name of the territory.")
     *
     *
     */
    public function getTerritoriesAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $searchParams = $request->query->all();
        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        // query
        $repo = $em->getRepository('PostparcBundle:Territory');
        $query = $repo->search($searchParams, $entityId, $currentEntityConfig['show_SharedContents']);
        $options = ['decorate' => false];
        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);
        // Récupération du view handler
        $viewHandler = $this->get('fos_rest.view_handler');
        // Création d'une vue FOSRestBundle
        $view = View::create($htmlTree);

        // Gestion de la réponse
        return $viewHandler->handle($view);
    }

    /**
     * Lists all PersonFunction entities.
     * @Rest\View()
     * @Rest\Get("/personFunctions" )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Lists all personFunction entities.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=PersonFunction::class, groups={"full"}))
     *     )
     * )
     * @QueryParam(name="name", description="The name of the personFunction.")
     *
     *
     */
    public function getPersonFunctionsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $searchParams = $request->query->all();
        $searchParams['orderBy'] = ['field' => 'pf.slug', 'direction' => 'ASC'];
        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');

        // pagination
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $page = $request->query->getInt('page', 1);/* page number */
        $nbMaxPerPage = $request->query->getInt('per_page', $default_items_per_page); /* limit per page */
        $firstResult = ($page - 1) * $nbMaxPerPage;

        // query
        $repo = $em->getRepository('PostparcBundle:PersonFunction');
        $query = $repo->search($searchParams, $entityId, $currentEntityConfig['show_SharedContents']);
        $query->setFirstResult($firstResult)->setMaxResults($nbMaxPerPage);
        $personFunctions = $query->getArrayResult();

        // Récupération du view handler
        $viewHandler = $this->get('fos_rest.view_handler');
        // Création d'une vue FOSRestBundle
        $view = View::create($personFunctions);

        // Gestion de la réponse
        return $viewHandler->handle($view);
    }

    /**
     * Lists all Services entities.
     * @Rest\View()
     * @Rest\Get("/services" )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Lists all service entities.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Service::class, groups={"full"}))
     *     )
     * )
     * @QueryParam(name="name", description="The name of the service.")
     *
     *
     */
    public function getServicesAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $searchParams = $request->query->all();
        $searchParams['orderBy'] = ['field' => 's.slug', 'direction' => 'ASC'];
        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');

        // pagination
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $page = $request->query->getInt('page', 1);/* page number */
        $nbMaxPerPage = $request->query->getInt('per_page', $default_items_per_page); /* limit per page */
        $firstResult = ($page - 1) * $nbMaxPerPage;

        // query
        $repo = $em->getRepository('PostparcBundle:Service');
        $query = $repo->search($searchParams, $entityId, $currentEntityConfig['show_SharedContents']);
        $query->setFirstResult($firstResult)->setMaxResults($nbMaxPerPage);
        $personFunctions = $query->getArrayResult();

        // Récupération du view handler
        $viewHandler = $this->get('fos_rest.view_handler');
        // Création d'une vue FOSRestBundle
        $view = View::create($personFunctions);

        // Gestion de la réponse
        return $viewHandler->handle($view);
    }

    /**
     * Lists all Tags entities.
     * @Rest\View()
     * @Rest\Get("/tags" )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Lists all tag entities.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Tag::class, groups={"full"}))
     *     )
     * )
     * @QueryParam(name="name", description="The name of the tag.")
     *
     *
     */
    public function getTagsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $searchParams = $request->query->all();
        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        // query
        $repo = $em->getRepository('PostparcBundle:Tag');
        $query = $repo->search($searchParams, $entityId, $currentEntityConfig['show_SharedContents']);
        $options = ['decorate' => false];
        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);
        // Récupération du view handler
        $viewHandler = $this->get('fos_rest.view_handler');
        // Création d'une vue FOSRestBundle
        $view = View::create($htmlTree);

        // Gestion de la réponse
        return $viewHandler->handle($view);
    }

    /**
     * Lists all organizationType entities.
     * @Rest\View()
     * @Rest\Get("/organizationTypes" )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Lists all organizationType entities.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Tag::class, groups={"full"}))
     *     )
     * )
     * @QueryParam(name="name", description="The name of the organizationType.")
     *
     *
     */
    public function getOrganizationTypesAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $searchParams = $request->query->all();
        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        // query
        $repo = $em->getRepository('PostparcBundle:OrganizationType');
        $query = $repo->search($searchParams, $entityId, $currentEntityConfig['show_SharedContents']);
        $options = ['decorate' => false];
        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);
        // Récupération du view handler
        $viewHandler = $this->get('fos_rest.view_handler');
        // Création d'une vue FOSRestBundle
        $view = View::create($htmlTree);

        // Gestion de la réponse
        return $viewHandler->handle($view);
    }
}

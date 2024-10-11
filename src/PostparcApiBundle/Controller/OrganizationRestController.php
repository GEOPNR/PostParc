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
use PostparcBundle\Entity\Organization;
use FOS\RestBundle\Controller\Annotations\QueryParam;

/**
 * Organization controller.
 *
 *  */
class OrganizationRestController extends Controller
{

    /**
     * Lists all Organization entities.
     * @Rest\View()
     * @Rest\Get("/organizations" )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Lists all Organization entities.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Organization::class, groups={"full"}))
     *     )
     * )
     * @QueryParam(name="name", description="The name of the organization.")
     * @QueryParam(name="organizationTypeIds[]", description="The type of the organization.")
     * @QueryParam(name="cityIds[]", description="array of cities ids.")
     * @QueryParam(name="departmentIds[]", description="array of departments ids.")
     * @QueryParam(name="groupIds[]", description="array of groups ids.")
     * @QueryParam(name="territoryIds[]", description="array of territories ids.")
     * @QueryParam(name="tagIds[]", description="array of tag ids.")
     * @QueryParam(name="group_sub", description="if value is 'on', search in sub groups")
     * @QueryParam(name="territory_sub", description="if value is 'on', search in sub territories")
     * @QueryParam(name="organizationType_sub", description="if value is 'on', search in sub organizationTypes")
     * @QueryParam(name="tag_sub", description="if value is 'on', search in sub tags")
     * @QueryParam(name="onlyWithEmail", description="boolean, to get only organisation with email")
     * @QueryParam(name="page", description="page number.")
     * @QueryParam(name="per_page", description="limit per page.")
     *
     */
    public function getOrganizationsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $filterData = $request->query->all();
        
        // instanciate params with array type
        $arrayFilters = ['organizationTypeIds','cityIds', 'departmentIds', 'groupIds', 'territoryIds','tagIds'];
        foreach ($arrayFilters as $filter){
            if(isset($filterData[$filter])){
                $filterData[$filter] = explode(',',str_replace(['[', ']'],'',$filterData[$filter]));
            }
        }

        $filterData['orderBy'] = ['field' => 'o.slug', 'direction' => 'ASC'];

        // surcharge pour le cas des sous groupes
        if (isset($filterData['group_sub']) && 'on' == $filterData['group_sub'] && isset($filterData['groupIds']) && count($filterData['groupIds']) > 0) {
            $groups = $em->getRepository('PostparcBundle:Group')->findBy(['id' => $filterData['groupIds']]);
            foreach ($groups as $group) {
                $subGroups = $em->getRepository('PostparcBundle:Group')->getChildren($node = $group, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subGroups as $subGroup) {
                    array_push($filterData['groupIds'], $subGroup->getId());
                }
            }
        }
        // surcharge pour le cas des sous territoires
        if (isset($filterData['territory_sub']) && 'on' == $filterData['territory_sub'] && isset($filterData['territoryIds']) && count($filterData['territoryIds']) > 0) {
            $territories = $em->getRepository('PostparcBundle:Territory')->findBy(['id' => $filterData['territoryIds']]);
            foreach ($territories as $territory) {
                $subTerritories = $em->getRepository('PostparcBundle:Territory')->getChildren($node = $territory, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subTerritories as $subTerritory) {
                    array_push($filterData['territoryIds'], $subTerritory->getId());
                }
            }
        }
        // surcharge pour le cas des sous organizationType
        if (isset($filterData['organizationType_sub']) && 'on' == $filterData['organizationType_sub'] && isset($filterData['organizationTypeIds']) && count($filterData['organizationTypeIds']) > 0) {
            $organizationTypes = $em->getRepository('PostparcBundle:OrganizationType')->findBy(['id' => $filterData['organizationTypeIds']]);
            foreach ($organizationTypes as $organizationType) {
                $subOrganizationTypes = $em->getRepository('PostparcBundle:OrganizationType')->getChildren($node = $organizationType, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subOrganizationTypes as $subOrganizationType) {
                    array_push($filterData['organizationTypeIds'], $subOrganizationType->getId());
                }
            }
        }
        // surcharge dans le cas de sous tags
        if (isset($filterData['tag_sub']) && 'on' == $filterData['tag_sub'] && isset($filterData['tagIds']) && count($filterData['tagIds']) > 0) {
            $tags = $em->getRepository('PostparcBundle:Tag')->findBy(['id' => $filterData['tagIds']]);
            foreach ($tags as $tag) {
                $subTags = $em->getRepository('PostparcBundle:Tag')->getChildren($node = $tag, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subTags as $subTag) {
                    array_push($filterData['tagIds'], $subTag->getId());
                }
            }
        }

        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');

        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();

        // pagination
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $page = $request->query->getInt('page', 1);/* page number */
        $nbMaxPerPage = $request->query->getInt('per_page', $default_items_per_page); /* limit per page */

        $firstResult = ($page - 1) * $nbMaxPerPage;

        $query = $em->getRepository('PostparcBundle:Organization')->advancedSearch($filterData, $entityId, $readerLimitations, $currentEntityConfig['show_SharedContents'], true);

        $totalItemsCount = count($query->getResult());

        $query->setFirstResult($firstResult)->setMaxResults($nbMaxPerPage);

        $organizations = $query->getResult();

        $formatted = [];
        foreach ($organizations as $organization) {
            $organizationsInfos = $organization->getApiFormated('list');
            $formatted[] = $organizationsInfos;
        }

        // Récupération du view handler
        $viewHandler = $this->get('fos_rest.view_handler');

        $return = [
          'items' => $formatted,
          'totalItemsCount' => $totalItemsCount,
          'page' => $page,
          'per_page' => $nbMaxPerPage,
        ];

        // Création d'une vue FOSRestBundle
        $view = View::create($return);


        // Gestion de la réponse
        return $viewHandler->handle($view);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/organizations/{id}")
     */
    public function getOrganizationAction(Request $request, Organization $organization)
    {

        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');

        $organizationInfos = $organization->getApiFormated();
        if ($currentEntityConfig['use_representation_module']) {
            $representationsInfos = [];
            foreach ($organization->getRepresentations() as $representation) {
                $representationsInfos[] = $representation->getApiFormated('list');
            }
            $organizationInfos['representations'] = $representationsInfos;
        }

        $viewHandler = $this->get('fos_rest.view_handler');
        $view = View::create($organizationInfos);


        return $viewHandler->handle($view);
    }
}

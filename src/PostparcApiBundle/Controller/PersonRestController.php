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
use PostparcBundle\Entity\Person;
use FOS\RestBundle\Controller\Annotations\QueryParam;

/**
 * Person controller.
 *
 *  */
class PersonRestController extends Controller
{

    /**
     * Lists all Person entities.
     * @Rest\View()
     * @Rest\Get("/persons" )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Lists all Person entities.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Person::class, groups={"full"}))
     *     )
     * )
     * @QueryParam(name="name", description="The name of the person.")
     * @QueryParam(name="cityIds[]", description="array of cities ids.")
     * @QueryParam(name="departmentIds[]", description="array of departments ids.")
     * @QueryParam(name="groupIds[]", description="array of groups ids.")
     * @QueryParam(name="mandateTypeIds[]", description="array of mandateTypes ids.")
     * @QueryParam(name="territoryIds[]", description="array of territories ids.")
     * @QueryParam(name="professionIds[]", description="array of professions ids.")
     * @QueryParam(name="tagIds[]", description="array of tags ids.")
     * @QueryParam(name="onlyWithEmail", description="boolean, to get only person with email")
     * @QueryParam(name="group_sub", description="if value is 'on', search in sub groups")
     * @QueryParam(name="territory_sub", description="if value is 'on', search in sub territories")
     * @QueryParam(name="organizationType_sub", description="if value is 'on', search in sub organizationTypes")
     * @QueryParam(name="mandateType_sub", description="if value is 'on', search in sub andateTypes")
     * @QueryParam(name="tag_sub", description="if value is 'on', search in sub tags")
     * @QueryParam(name="page", description="page number.")
     * @QueryParam(name="per_page", description="limit per page.")
     *
     */
    public function getPersonsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $searchParams = $request->query->all();
        
        // instanciate params with array type
        $arrayFilters = ['cityIds', 'groupIds','mandateTypeIds','territoryIds', 'professionIds', 'tagIds'];
        foreach ($arrayFilters as $filter){
            if(isset($searchParams[$filter])){
                $searchParams[$filter] = explode(',',str_replace(['[', ']'],'',$searchParams[$filter]));
            }
        }
        //var_dump($searchParams);die;
        $searchParams['orderBy'] = ['field' => 'p.slug', 'direction' => 'ASC'];
        // surcharge pour le cas des sous groupes
        if (isset($searchParams['group_sub']) && 'on' == $searchParams['group_sub'] && isset($searchParams['groupIds']) && count($searchParams['groupIds']) > 0) {
            $groups = $em->getRepository('PostparcBundle:Group')->findBy(['id' => $searchParams['groupIds']]);
            foreach ($groups as $group) {
                $subGroups = $em->getRepository('PostparcBundle:Group')->getChildren($node = $group, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subGroups as $subGroup) {
                    array_push($searchParams['groupIds'], $subGroup->getId());
                }
            }
        }
        // surcharge pour le cas des sous territoires
        if (isset($searchParams['territory_sub']) && 'on' == $searchParams['territory_sub'] && isset($searchParams['territoryIds']) && count($searchParams['territoryIds']) > 0) {
            $territories = $em->getRepository('PostparcBundle:Territory')->findBy(['id' => $searchParams['territoryIds']]);
            foreach ($territories as $territory) {
                $subTerritories = $em->getRepository('PostparcBundle:Territory')->getChildren($node = $territory, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subTerritories as $subTerritory) {
                    array_push($searchParams['territoryIds'], $subTerritory->getId());
                }
            }
        }
        // surcharge pour le cas des sous organizationType
        if (isset($searchParams['organizationType_sub']) && 'on' == $searchParams['organizationType_sub'] && isset($searchParams['organizationTypeIds']) && count($searchParams['organizationTypeIds']) > 0) {
            $organizationTypes = $em->getRepository('PostparcBundle:OrganizationType')->findBy(['id' => $searchParams['organizationTypeIds']]);
            foreach ($organizationTypes as $organizationType) {
                $subOrganizationTypes = $em->getRepository('PostparcBundle:OrganizationType')->getChildren($node = $organizationType, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subOrganizationTypes as $subOrganizationType) {
                    array_push($searchParams['organizationTypeIds'], $subOrganizationType->getId());
                }
            }
        }
        // surcharge pour le cas des sous mandateTypes
        if (isset($searchParams['mandateType_sub']) && 'on' == $searchParams['mandateType_sub'] && isset($searchParams['mandateTypeIds']) && count($searchParams['mandateTypeIds']) > 0) {
            $mandateTypes = $em->getRepository('PostparcBundle:MandateType')->findBy(['id' => $searchParams['mandateTypeIds']]);
            foreach ($mandateTypes as $mandateType) {
                $subMandateTypes = $em->getRepository('PostparcBundle:MandateType')->getChildren($node = $mandateType, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subMandateTypes as $subMandateType) {
                    array_push($searchParams['mandateTypeIds'], $subMandateType->getId());
                }
            }
        }
        // surcharge dans le cas de sous tags
        if (isset($searchParams['tag_sub']) && 'on' == $searchParams['tag_sub'] && isset($searchParams['tagIds']) && count($searchParams['tagIds']) > 0) {
            $tags = $em->getRepository('PostparcBundle:Tag')->findBy(['id' => $searchParams['tagIds']]);
            foreach ($tags as $tag) {
                $subTags = $em->getRepository('PostparcBundle:Tag')->getChildren($node = $tag, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = true);
                foreach ($subTags as $subTag) {
                    array_push($searchParams['tagIds'], $subTag->getId());
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
        $page = $request->query->getInt('page', 1); /* page number */
        $nbMaxPerPage = $request->query->getInt('per_page', $default_items_per_page); /* limit per page */
        $firstResult = ($page - 1) * $nbMaxPerPage;
        
        $query = $em->getRepository('PostparcBundle:Person')->advancedSearch($searchParams, $entityId, $readerLimitations, $currentEntityConfig['show_SharedContents']);
        $totalItemsCount = count($query->getResult());

        $query->setFirstResult($firstResult)->setMaxResults($nbMaxPerPage);
        $persons = $query->getResult();

        $formatted = [];
        foreach ($persons as $person) {
            $formatted[] = $person->getApiFormated('list');
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
     * @Rest\Get("/persons/{id}")
     */
    public function getPersonAction(Request $request, Person $person)
    {

        // récupération entité current user
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');

        $personInfos = $person->getApiFormated();
        if ($currentEntityConfig['use_representation_module']) {
            $representationsInfos = [];
            foreach ($person->getRepresentations() as $representation) {
                $representationsInfos[] = $representation->getApiFormated('list');
            }
            $personInfos['representations'] = $representationsInfos;
        }

        $viewHandler = $this->get('fos_rest.view_handler');
        $view = View::create($personInfos);


        return $viewHandler->handle($view);
    }
}

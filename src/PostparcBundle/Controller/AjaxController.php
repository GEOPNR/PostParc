<?php

namespace PostparcBundle\Controller;

use DateTime;
use Doctrine\ORM\EntityManager;
use PostparcBundle\Entity\City;
use PostparcBundle\Entity\Coordinate;
use PostparcBundle\Entity\Entity;
use PostparcBundle\Entity\EntityCoordinateDistance;
use PostparcBundle\Entity\User;
use PostparcBundle\Entity\UserCoordinateDistance;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ajax controller.
 *
 * @Route("/ajax")
 */
class AjaxController extends Controller
{
    /**
     * autocomplete city action. search only in active city.
     *
     * @Route("/autocomplete-city", name="autocomplete_city", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function autoCompleteCityAction(Request $request)
    {
        $q = $request->query->get('q');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:City')->autoComplete(str_replace('\'', '_', $q), true, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $label = '' . $entity['name'];
            if ($entity['zipCode']) {
                $label .= ' (' . $entity['zipCode'] . ')';
            }
            $items[] = ['id' => $entity['id'], 'text' => $label, 'label' => $label];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['length'] = count($entities);
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete city action. search in all city.
     *
     * @Route("/autocomplete-city-all", name="autocomplete_city_all", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function autoCompleteCityAllAction(Request $request)
    {
        $q = $request->query->get('q');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:City')->autoComplete(str_replace('\'', '_', $q), true, $request->query->get('page_limit'), $request->query->get('page'));

        foreach ($entities as $entity) {
            $label = '' . $entity['name'];
            if ($entity['zipCode']) {
                $label .= ' (' . $entity['zipCode'] . ')';
            }
            $items[] = ['id' => $entity['id'], 'text' => $label, 'label' => $label];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * init autocomplete city.
     *
     * @Route("/get-city", name="get_city", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function initAutoCompleteCityAction(Request $request)
    {
        $id = $request->query->get('id');
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('PostparcBundle:City')->find($id);
        if (!$entity instanceof City) {
            return null;
        }
        $label = $entity->__toString();
        $return = ['id' => $id, 'text' => $label, 'label' => $label];

        return new Response(json_encode($return), $return !== [] ? 200 : 404);
    }

    /**
     * autocomplete event action.
     *
     * @Route("/autocomplete-event", name="autocomplete_event", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteEventAction(Request $request)
    {
        $q = $request->query->get('q');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $entities = $em->getRepository('PostparcBundle:Event')->autoComplete(str_replace('\'', '_', $q), $entityId, $show_SharedContents, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete group action.
     *
     * @Route("/autocomplete-group", name="autocomplete_group", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function autoCompleteGroupAction(Request $request)
    {
        $q = $request->query->get('q');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $entities = $em->getRepository('PostparcBundle:Group')->autoComplete(str_replace('\'', '_', $q), $entityId, $show_SharedContents, $request->query->get('page_limit'), $request->query->get('page'), $this->getUser());
        foreach ($entities as $entity) {
            $groupeName = $entity->getName();
            if ($entity->getParent() && $entity->getParent()->getId() != $entity->getId()) {
                $groupeName = $entity->getParent()->getName() . "/ " . $groupeName;
                if ($entity->getParent()->getParent() && $entity->getParent()->getParent()->getId() != $entity->getParent()->getId()) {
                    $groupeName = $entity->getParent()->getParent()->getName() . "/ " . $groupeName;
                }
            }
            $items[] = ['id' => $entity->getId(), 'text' => $groupeName];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete person action.
     *
     * @Route("/autocomplete-person", name="autocomplete_person", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompletePersonAction(Request $request)
    {
        $q = $request->query->get('q');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $entities = $em->getRepository('PostparcBundle:Person')->autoComplete(str_replace('\'', '_', $q), $entityId, $show_SharedContents, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete person action.
     *
     * @Route("/autocomplete_person_for_event", name="autocomplete_person_for_event", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompletePersonForEventAction(Request $request)
    {
        $term = $request->query->get('term') ? $request->query->get('term') : $request->query->get('q');
        $eventId = $request->query->get('eventId') ? $request->query->get('eventId') : $request->query->get('eventId');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $entities = $em->getRepository('PostparcBundle:Person')->autoCompleteForEvent($eventId, $term, $entityId, $show_SharedContents, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete pfo action.
     *
     * @Route("/autocomplete-pfo", name="autocomplete_pfo", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompletePfoAction(Request $request)
    {
        $q = $request->query->get('q');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $entities = $em->getRepository('PostparcBundle:Pfo')->autoComplete(str_replace('\'', '_', $q), $entityId, $show_SharedContents, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete pfo action.
     *
     * @Route("/autocomplete_pfo_for_event", name="autocomplete_pfo_for_event", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompletePfoForEventAction(Request $request)
    {
        $term = $request->query->get('term') ? $request->query->get('term') : $request->query->get('q');
        $eventId = $request->query->get('eventId') ? $request->query->get('eventId') : $request->query->get('eventId');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $entities = $em->getRepository('PostparcBundle:Pfo')->autoCompleteForEvent($eventId, $term, $entityId, $show_SharedContents, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete representation action.
     *
     * @Route("/autocomplete-representation", name="autocomplete_representation", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteRepresentationAction(Request $request)
    {
        $q = $request->query->get('q');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $entities = $em->getRepository('PostparcBundle:Representation')->autoComplete(str_replace('\'', '_', $q), $entityId, $show_SharedContents, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete representation action.
     *
     * @Route("/autocomplete_representation_for_event", name="autocomplete_representation_for_event", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteRepresentationForEventAction(Request $request)
    {
        $term = $request->query->get('term') ? $request->query->get('term') : $request->query->get('q');
        $eventId = $request->query->get('eventId') ? $request->query->get('eventId') : $request->query->get('eventId');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $entities = $em->getRepository('PostparcBundle:Representation')->autoCompleteForEvent($eventId, $term, $entityId, $show_SharedContents, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete function action.
     *
     * @Route("/autocomplete-function", name="autocomplete_function", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteFunctionAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:PersonFunction')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));

        // gestion lecteur -> recherche si restriction
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->hasRole('ROLE_CONTRIBUTOR')) {
            $readerLimitation = $em->getRepository('PostparcBundle:ReaderLimitation')->findOneBy(['entity' => $user->getEntity()]);
            if ($readerLimitation !== null) {
                $limitations = $readerLimitation->getLimitations();
                if (array_key_exists('function_noLimitation', $limitations) && 'off' == $limitations['function_noLimitation']) {
                    $allowedIds = $limitations['functionIds'];
                }
            }
        }
        foreach ($entities as $entity) {
            if (isset($allowedIds) && in_array($entity->getId(), $allowedIds)) {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            } else {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            }
        }

        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete additionalFunction action.
     *
     * @Route("/autocomplete-additionalFunction", name="autocomplete_additionalFunction", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteAdditionalFunctionAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:AdditionalFunction')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete service action.
     *
     * @Route("/autocomplete-service", name="autocomplete_service", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteServiceAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:Service')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));

        // gestion lecteur -> recherche si restriction
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->hasRole('ROLE_CONTRIBUTOR')) {
            $readerLimitation = $em->getRepository('PostparcBundle:ReaderLimitation')->findOneBy(['entity' => $user->getEntity()]);
            if ($readerLimitation !== null) {
                $limitations = $readerLimitation->getLimitations();
                if (array_key_exists('service_noLimitation', $limitations) && 'off' == $limitations['service_noLimitation']) {
                    $allowedIds = $limitations['serviceIds'];
                }
            }
        }
        foreach ($entities as $entity) {
            if (isset($allowedIds) && in_array($entity->getId(), $allowedIds)) {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            } else {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            }
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete profession action.
     *
     * @Route("/autocomplete-profession", name="autocomplete_profession", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteProfessionAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:Profession')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete eventType action.
     *
     * @Route("/autocomplete-eventType", name="autocomplete_eventType", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteEventTypeAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:EventType')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete organization action.
     *
     * @Route("/autocomplete-organization", name="autocomplete_organization", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteOrganizationAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $entities = $em->getRepository('PostparcBundle:Organization')->autoComplete($q, $entityId, $show_SharedContents, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete organizationType action.
     *
     * @Route("/autocomplete-organizationType", name="autocomplete_organizationType", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteOrganizationTypeAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:OrganizationType')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));

        // gestion lecteur -> recherche si restriction
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->hasRole('ROLE_CONTRIBUTOR')) {
            $readerLimitation = $em->getRepository('PostparcBundle:ReaderLimitation')->findOneBy(['entity' => $user->getEntity()]);
            if ($readerLimitation !== null) {
                $limitations = $readerLimitation->getLimitations();
                if (array_key_exists('organizationType_noLimitation', $limitations) && 'off' == $limitations['organizationType_noLimitation']) {
                    $allowedIds = $limitations['organizationTypeIds'];
                }
            }
        }
        foreach ($entities as $entity) {
            if (isset($allowedIds) && in_array($entity->getId(), $allowedIds)) {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            } else {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            }
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete organization action.
     *
     * @Route("/autocomplete-abbreviation", name="autocomplete_abbreviation", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteAbbreviationAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:Organization')->autoCompleteAbbreviation($q, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete territory action.
     *
     * @Route("/autocomplete-territory", name="autocomplete_territory", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteTerritoryAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];


        $entities = $em->getRepository('PostparcBundle:Territory')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            //$items[] = array('id' => $entity->getId(), 'text' => $entity->__toString());
            $items[] = ['id' => $entity->getId(), 'text' => $entity->getCompletName()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete territoryType action.
     *
     * @Route("/autocomplete-territoryType", name="autocomplete_territoryType", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteTerritoryTypeAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:TerritoryType')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));
        foreach ($entities as $entity) {
            //$items[] = array('id' => $entity->getId(), 'text' => $entity->__toString());
            $items[] = ['id' => $entity->getId(), 'text' => $entity->getCompletName()];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete tag action.
     *
     * @Route("/autocomplete-tag", name="autocomplete_tag", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteTagAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:Tag')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));
        // gestion lecteur -> recherche si restriction
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->hasRole('ROLE_CONTRIBUTOR')) {
            $readerLimitation = $em->getRepository('PostparcBundle:ReaderLimitation')->findOneBy(['entity' => $user->getEntity()]);
            if ($readerLimitation !== null) {
                $limitations = $readerLimitation->getLimitations();
                if (array_key_exists('tag_noLimitation', $limitations) && $limitations && 'off' == $limitations['tag_noLimitation']) {
                    $allowedIds = $limitations['tagIds'];
                }
            }
        }
        foreach ($entities as $entity) {
            if (isset($allowedIds) && in_array($entity->getId(), $allowedIds)) {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            } else {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            }
        }

        $return['items'] = $items;
        $return['more'] = true;
        $return['results'] = $items;


        return new Response(json_encode($return), $return !== [] ? 200 : 404);
    }

    /**
     * autocomplete department action.
     *
     * @Route("/autocomplete-department", name="autocomplete_department", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteDepartmentAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $results = $em->getRepository('PostparcBundle:City')->autoCompleteDepartment($q);

        foreach ($results as $key => $value) {
            $items[] = ['id' => $key, 'text' => $value];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete user action.
     *
     * @Route("/autocomplete-user", name="autocomplete_user", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteUserAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $results = $em->getRepository('PostparcBundle:User')->autoComplete(str_replace('\'', '_', $q), $user, $request->query->get('page_limit'), $request->query->get('page'));

        foreach ($results as $value) {
            $items[] = [ 'id' => $value->getId(), 'text' => $value->getDisplayName() ];
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete mandateType action.
     *
     * @Route("/autocomplete-mandateType", name="autocomplete_mandateType", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteMandateTypeAction(Request $request)
    {
        $q = $request->query->get('q');
        $term = $request->query->get('term') ? $request->query->get('term') : $request->query->get('q');
        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $entities = $em->getRepository('PostparcBundle:MandateType')->autoComplete(str_replace('\'', '_', $q), $request->query->get('page_limit'), $request->query->get('page'));
        // gestion lecteur -> recherche si restriction
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->hasRole('ROLE_CONTRIBUTOR')) {
            $readerLimitation = $em->getRepository('PostparcBundle:ReaderLimitation')->findOneBy(['entity' => $user->getEntity()]);
            if ($readerLimitation !== null) {
                $limitations = $readerLimitation->getLimitations();

                if (array_key_exists('mandateType_noLimitation', $limitations) && 'off' == $limitations['mandateType_noLimitation']) {
                    $allowedIds = $limitations['mandateTypeIds'];
                }
            }
        }
        foreach ($entities as $entity) {
            if (isset($allowedIds) && in_array($entity->getId(), $allowedIds)) {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            } else {
                $items[] = ['id' => $entity->getId(), 'text' => $entity->__toString()];
            }
        }
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }
    
    /**
     * autocomplete entity action.
     *
     * @Route("/autocomplete-entity", name="autocomplete_entity", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function autoCompleteEntityAction(Request $request)
    {
        $q = $request->query->get('q');

        $em = $this->getDoctrine()->getManager();
        $return = [];
        $items = [];
        $results = $em->getRepository('PostparcBundle:Entity')->autoComplete(str_replace('\'', '_', $q));        

        foreach ($results as $value) {
            $items[] = [ 'id' => $value->getId(), 'text' => $value->__toString() ];
        }
        
        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } else {
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }



    /**
     * get documentTemplate infos via ajax.
     *
     * @Route("/ajax-documentTemplate", name="ajax_getDocumentTemplate", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function ajaxGetDocumentTemplateAction(Request $request)
    {
        $id = $request->query->get('id');
        $em = $this->getDoctrine()->getManager();

        $return = [];
        $documentTemplate = $em->getRepository('PostparcBundle:DocumentTemplate')->find($id);
        if ($documentTemplate !== null) {
            $return = ['body' => $documentTemplate->getBody(), 'subject' => $documentTemplate->getSubject()];
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * remove element via ajax.
     *
     * @Route("/ajax_removeElement", name="ajax_removeElement", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function ajaxDeleteElementAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $id = $request->query->get('id');
        $type = $request->query->get('type');

        $object = $em->getRepository('PostparcBundle:' . ucfirst($type))->find($id);
        if (is_callable([$object, 'setDeletedAt'])) {
            $now = new DateTime();
            $object->setDeletedAt($now);
            $object->setDeletedBy($this->getUser());
            $em->persist($object);
        } else {
            $em->remove($object);
        }

        $em->flush();

        return new Response('ok', 200);
    }
    /**
     * and or remove element to the user favorites.
     *
     * @Route("/ajax_addorremove_favorites", name="ajax_addOrRemoveFavorites", options={"expose"=true}, methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function ajaxAddorRemoveToMyFavorites(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $id = $request->request->get('id');
        $class = $request->request->get('class');
        $currentUser = $this->getUser();
        $favorites = $currentUser->getFavorites();
        $returnClass = "fa-star";
        // test si l'element est defja dans les favoris
        if (!array_key_exists($class, $favorites)) {
            $favorites[$class] = [];
        }
        if (in_array($id, $favorites[$class])) {
            // remove
            $returnClass = "fa-star-o";
            $favorites[$class] = array_diff($favorites[$class], [$id]);
        } else {
            // add
            $favorites[$class][] = $id;
        }

        // update user favorites
        $currentUser->setFavorites($favorites);
        $em->persist($currentUser);
        $em->flush();

        return new Response($returnClass, 200);
    }

    /**
     * remove element via ajax.
     *
     * @Route("/ajax_updateCoordinateGeolocInfos", name="ajax_updateCoordinateGeolocInfos", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function ajaxUpdateCoordinateGeolocInfos(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $id = $request->query->get('id');
        $coordinate = $em->getRepository('PostparcBundle:Coordinate')->find($id);
        $previousCoordinate = $coordinate->getCoordinate();
        if ($coordinate !== null) {
            $coordinate = $this->searchGeographicalInfo($coordinate);
            $em->persist($coordinate);
            $em->flush();
        }
        if (strlen($coordinate->getCoordinate()) && ($coordinate->getCoordinate() !== $previousCoordinate)) {
            // mise a jour infos distance entre entity et coordinate
            $entityCoordinateDistance = $em->getRepository('PostparcBundle:EntityCoordinateDistance')->findOneBy(['entity' => $this->getUser()->getEntity(), 'coordinate' => $coordinate]);
            if ($entityCoordinateDistance !== null) {
                $em->remove($entityCoordinateDistance);
            }
            $this->generateContactDistanceInfos($this->getUser()->getEntity(), $coordinate);

            // mise a jour infos entre user et coordinate
            $userCoordinateDistance = $em->getRepository('PostparcBundle:UserCoordinateDistance')->findOneBy(['user' => $this->getUser(), 'coordinate' => $coordinate]);
            if ($userCoordinateDistance !== null) {
                $em->remove($userCoordinateDistance);
            }
            $this->generateUserContactDistanceInfos($this->getUser(), $coordinate);
        }

        return new Response($coordinate->getCoordinate(), 200);
    }

    /**
     * remove element via ajax.
     *
     * @Route("/ajax_coordinateDistanceInfos", name="ajax_getCoordinateDistanceInfos", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function ajaxGetCoordinateDistanceInfos(Request $request)
    {
        $id = $request->query->get('id');
        $em = $this->getDoctrine()->getManager();
        $coordinate = $em->getRepository('PostparcBundle:Coordinate')->find($id);
        $entityCoordinateDistance = null;

        if ($this->getUser()->getCoordinate() && $this->getUser()->getCoordinate()->getCoordinate()) {
            $userCoordinateDistance = $em->getRepository('PostparcBundle:UserCoordinateDistance')->findOneBy(['user' => $this->getUser(), 'coordinate' => $coordinate]);
            if ($userCoordinateDistance === null) {
                $userCoordinateDistance = $this->generateUserContactDistanceInfos($this->getUser(), $coordinate);
            }
            if ($userCoordinateDistance) {
                return $this->render('default/userCoordinateDistanceInfos.html.twig', [
                  'userCoordinateDistance' => $userCoordinateDistance,
                ]);
            }
        }
        //don't do anything if instance don't have coordinate infos
        if ($this->getUser()->getEntity()->getCoordinate()) {
            $entityCoordinateDistance = $em->getRepository('PostparcBundle:EntityCoordinateDistance')->findOneBy(['entity' => $this->getUser()->getEntity(), 'coordinate' => $coordinate]);
            if ($entityCoordinateDistance === null) {
                $entityCoordinateDistance = $this->generateContactDistanceInfos($this->getUser()->getEntity(), $coordinate);
            }
        }

        return $this->render('default/entityCoordinateDistanceInfos.html.twig', [
              'entityCoordinateDistance' => $entityCoordinateDistance,
        ]);
    }
    /**
     * load possible email associate to on entity via ajax.
     *
     * @Route("/ajax_getPreferedEmailsSelectForMailMassifmodule", name="ajax_getPreferedEmailsSelectForMailMassifmodule", options={"expose"=true}, methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function ajaxGetPreferedEmailsSelectForMailMassifmodule(Request $request)
    {
        $id = $request->request->get('id');
        $type = $request->request->get('type');
        $selectedValues = $request->request->get('selectedValues');
        $label = $request->request->get('label');
        $em = $this->getDoctrine()->getManager();

        $randomId = rand();

        switch ($type) {
            case 'persons':
                $dql = $em->getRepository('PostparcBundle:Email')->retrievePossiblePersonEmails($id);
                $emails = $em->createQuery($dql)->getResult();
                break;
            case 'pfos':
                $pfo = $em->getRepository('PostparcBundle:Pfo')->find($id);
                $dql = $em->getRepository('PostparcBundle:Email')->retrievePossiblePfoEmails($pfo);
                $emails = $em->createQuery($dql)->getResult();
                break;
            case 'representations':
                $representation = $em->getRepository('PostparcBundle:Representation')->find($id);
                $dql = $em->getRepository('PostparcBundle:Email')->retrievePossibleRepresentationEmails($representation);
                $emails = $em->createQuery($dql)->getResult();
                break;
            default:
                $emails = $em->getRepository('PostparcBundle:Email')->findBy(['email'=>$selectedValues]);
                break;
        }

        return $this->render('mailMassiveModule/emailsSelect.html.twig', [
              'selectedValues' => explode(';', $selectedValues),
              'emails' => $emails,
              'id' => $id,
              'type' => $type,
              'label' => $label,
              'randomId' => $randomId
        ]);
    }

    /**
     * load possible coordinate associate to on entity via ajax.
     *
     * @Route("/ajax_getCoordinateSelectForSelectionExportmodule", name="ajax_getCoordinateSelectForSelectionExportmodule", options={"expose"=true}, methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function ajaxGetCoordinateSelectForSelectionExportmodule(Request $request)
    {
        $id = $request->request->get('id');
        $type = $request->request->get('type');
        $selectedValues = $request->request->get('selectedValues');
        $label = $request->request->get('label');
        $em = $this->getDoctrine()->getManager();

        $randomId = rand();
        $coordinatesInfos = [];
        switch ($type) {
            case 'persons':
                $person = $em->getRepository('PostparcBundle:Person')->find($id);
                $dql = $em->getRepository('PostparcBundle:Coordinate')->retrievePossiblePersonCoordinates($person);
                $coordinates = $em->createQuery($dql)->getResult();
                foreach ($coordinates as $coordinate) {
                    $coordinatesInfos[] = [
                        'id' => $coordinate->getId(),
                        'label' => $coordinate->__toString()
                    ];
                }
                break;
            case 'pfos':
                $pfo = $em->getRepository('PostparcBundle:Pfo')->find($id);
                $dql = $em->getRepository('PostparcBundle:Coordinate')->retrievePossiblePfoCoordinates($pfo);
                $coordinates = $em->createQuery($dql)->getResult();
                foreach ($coordinates as $coordinate) {
                    $coordinatesInfos[] = [
                        'id' => $coordinate->getId(),
                        'label' => $coordinate->__toString()
                    ];
                }
                break;
            case 'representations':
                $representation = $em->getRepository('PostparcBundle:Representation')->find($id);
                $dql = $em->getRepository('PostparcBundle:Coordinate')->retrievePossibleRepresentationCoordinates($representation);
                $coordinates = $em->createQuery($dql)->getResult();
                foreach ($coordinates as $coordinate) {
                    $coordinatesInfos[] = [
                        'id' => $coordinate->getId(),
                        'label' => $coordinate->__toString()
                    ];
                }
                break;
            case 'organizations':
                $organization = $em->getRepository('PostparcBundle:Organization')->find($id);
                if ($organization->getCoordinate()) {
                    $coordinatesInfos[] = [
                        'id' => $organization->getCoordinate()->getId(),
                        'label' => $organization->getCoordinate()->__toString()
                    ];
                }
                break;
        }

        return $this->render('selection/coordinatesSelectForExport.html.twig', [
              'selectedValues' => $selectedValues,
              'coordinatesInfos' => $coordinatesInfos,
              'id' => $id,
              'type' => $type,
              'label' => $label,
              'randomId' => $randomId
        ]);
    }


    /**
     * Call google api to get distance and duration between entity and coordinate.
     *
     *
     * @param Entity     $entity
     * @param Coordinate $coordinate
     *
     * @return EntityCoordinateDistance
     */
    private function generateContactDistanceInfos(Entity $entity, Coordinate $coordinate, $api = 'google')
    {
        $entityCoordinateDistance = null;
        $coordinateFinded = false;

        switch ($api) {
            case 'google':
                $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?';
                $url .= 'origins=' . $entity->getCoordinate();
                $url .= '&destinations=' . $coordinate->getCoordinate();
                $url .= '&language=fr-FR';
                break;
            case 'osrm':
                $url = 'http://router.project-osrm.org/route/v1/driving/' . $entity->getCoordinate() . ';' . $coordinate->getCoordinate() . '?overview=false';
                break;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, str_replace(' ', '', $url));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmp = curl_exec($ch);
        $result = json_decode($tmp, true);

        switch ($api) {
            case 'google':
                if ('OK' == $result['status']) {
                    $elements = $result['rows'][0]['elements'][0];
                    if (array_key_exists('distance', $elements) && array_key_exists('duration', $elements)) {
                        $distance = $elements['distance']['value'];
                        $duration = $elements['duration']['value'];
                        $distanceText = $elements['distance']['text'];
                        $durationText = $elements['duration']['text'];
                        $coordinateFinded = true;
                    }
                }
                break;
            case 'osrm':
                if ('Ok' == $result['code']) {
                    $elements = $result['routes'][0];
                    if (array_key_exists('distance', $elements) && array_key_exists('duration', $elements)) {
                        $distance = $elements['distance'];
                        $duration = $elements['duration'];
                        $distanceText = (round($distance / 1000)) . ' km';
                        $durationText = gmdate('H:i', $duration);
                        $coordinateFinded = true;
                    }
                }
                break;
        }

        if ($coordinateFinded) {
            $em = $this->getDoctrine()->getManager();
            $entityCoordinateDistance = new EntityCoordinateDistance();
            $entityCoordinateDistance->setEntity($entity);
            $entityCoordinateDistance->setCoordinate($coordinate);
            $entityCoordinateDistance->setDistanceText($distanceText);
            $entityCoordinateDistance->setDistanceValue($distance);
            $entityCoordinateDistance->setDurationText($durationText);
            $entityCoordinateDistance->setDurationValue($duration);
            $em->persist($entityCoordinateDistance);
            $em->flush();
        }

        return $entityCoordinateDistance;
    }

    /**
     * Call google api to get distance and duration between user coordinate and coordinate.
     *
     *
     * @param Entity     $entity
     * @param Coordinate $coordinate
     *
     * @return EntityCoordinateDistance
     */
    private function generateUserContactDistanceInfos(User $user, Coordinate $coordinate, $api = 'google')
    {
        $userCoordinateDistance = null;
        $coordinateFinded = false;

        switch ($api) {
            case 'google':
                $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?';
                $url .= 'origins=' . $user->getCoordinate()->getCoordinate();
                $url .= '&destinations=' . $coordinate->getCoordinate();
                $url .= '&language=fr-FR';
                break;
            case 'osrm':
                $url = 'http://router.project-osrm.org/route/v1/driving/' . $user->getCoordinate()->getCoordinate() . ';' . $coordinate->getCoordinate() . '?overview=false';
                break;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, str_replace(' ', '', $url));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmp = curl_exec($ch);
        $result = json_decode($tmp, true);

        switch ($api) {
            case 'google':
                if ('OK' == $result['status']) {
                    $elements = $result['rows'][0]['elements'][0];
                    if (array_key_exists('distance', $elements) && array_key_exists('duration', $elements)) {
                        $distance = $elements['distance']['value'];
                        $duration = $elements['duration']['value'];
                        $distanceText = $elements['distance']['text'];
                        $durationText = $elements['duration']['text'];
                        $coordinateFinded = true;
                    }
                }
                break;
            case 'osrm':
                if ('Ok' == $result['code']) {
                    $elements = $result['routes'][0];

                    if (array_key_exists('distance', $elements) && array_key_exists('duration', $elements)) {
                        $distance = $elements['distance'];
                        $duration = $elements['duration'];
                        $distanceText = (round($distance / 1000)) . ' km';
                        $durationText = gmdate('H:i', $duration);
                        $coordinateFinded = true;
                    }
                }
                break;
        }

        if ($coordinateFinded) {
            $em = $this->getDoctrine()->getManager();
            $userCoordinateDistance = new UserCoordinateDistance();
            $userCoordinateDistance->setUser($user);
            $userCoordinateDistance->setCoordinate($coordinate);
            $userCoordinateDistance->setDistanceText($distanceText);
            $userCoordinateDistance->setDistanceValue($distance);
            $userCoordinateDistance->setDurationText($durationText);
            $userCoordinateDistance->setDurationValue($duration);
            $em->persist($userCoordinateDistance);
            $em->flush();
        }

        return $userCoordinateDistance;
    }

    /**
     * search coordinate information for on address via google api.
     *
     * @param EntityManager $em
     * @param Coordinate    $coordinate
     *
     * @return Coordinate
     */
    private function searchGeographicalInfo(Coordinate $coordinate)
    {
        //$key = '&key=AIzaSyB8v1F-tNAmAdnJ2h3ontERJLp931Dez58';
        //$key = '';
        //$geocoder = 'http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false'.$key;
        $env = $this->container->get('kernel')->getEnvironment();
        $geocoder = 'https://nominatim.openstreetmap.org/search.php?q=%s&format=json&addressdetails=1&limit=1&polygon_svg=1&email=contact@' . $env . '.postparc.fr';

        $adresse = $coordinate->getAddressLine1();
        if ($coordinate->getCity()) {
            $adresse .= ', ' . $coordinate->getCity()->getZipCode();
            $adresse .= ', ' . strtoupper($coordinate->getCity()->getName());
            if ($coordinate->getCity()->getCountry() !== '' && $coordinate->getCity()->getCountry() !== '0') {
                $adresse .= ', ' . $coordinate->getCity()->getCountry();
            }
        }
        if (strlen($adresse) !== 0) {
            // Requête envoyée à l'API Geocoding
            $adresse = str_replace(', ', ' ', $adresse);
            $adresse = str_replace(['(', ')'], '', $adresse);
            $url = sprintf($geocoder, str_replace(' ', '+', $adresse));
            //$url = sprintf($geocoder, urlencode(utf8_encode($adresse)));
            $ch = curl_init();
            $userAgent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2';
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            curl_setopt($ch, CURLOPT_REFERER, 'https://' . $env . '.postparc.fr');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $tmp = curl_exec($ch);

            $result = json_decode($tmp, true);

            if (is_array($result) && count($result)) {
                $lat = $result['0']['lat'];
                $lng = $result['0']['lon'];
                $coordinate->setCoordinate(str_replace(' ', '', $lat . ',' . $lng));
            }
        }

        return $coordinate;
    }
}

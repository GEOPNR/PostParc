<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;
use Cocur\Slugify\Slugify;
use PostparcBundle\Entity\PfoPersonGroup;
use PostparcBundle\Entity\Person;
use PostparcBundle\Entity\Pfo;
use PostparcBundle\Entity\Organization;
use PostparcBundle\Entity\Representation;
use PostparcBundle\Entity\Coordinate;
use PostparcBundle\Entity\EventPersons;
use PostparcBundle\Entity\EventPfos;
use PostparcBundle\Entity\EventRepresentations;
use Symfony\Component\Finder\Finder;

/**
 * Group controller.
 *
 * @Route("/selection")
 */
class SelectionController extends Controller {

    /**
     * @param Request $request
     * @Route("/getNbSelectionElement", name="selection_nbElements"))
     *
     * @return Response
     */
    public function getNbSelectionElementAction(Request $request) {
        $nbElements = $this->getNbElementsInSelection($request);

        return $this->render('selection/selectionCounter.html.twig', [
                    'nbElements' => $nbElements,
        ]);
    }

    /**
     * @param Request $request
     * @Route("/", name="selection_show"))
     *
     * @return Response
     */
    public function getSelectionAction(Request $request) {
        $session = $request->getSession();
        $currentEntityConfig = $session->get('currentEntityConfig');
        $persons = null;
        $pfos = null;
        $organizations = null;
        $representations = null;
        $nbElements = 0;
        $paginator = $this->get('knp_paginator');
        // gestion tabs
        // gestion tabs
        $activeTab = 'persons'; // default value
        if(isset($currentEntityConfig['tabsOrder'])) {
            $tabsOrder = $currentEntityConfig['tabsOrder'];
            $activeTab = array_key_first($tabsOrder);
        }
        if ($request->query->has('activeTab')) {
            $activeTab = $request->query->get('activeTab');
        }
        $em = $this->getDoctrine()->getManager();
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');

            // Persons
            if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
                $queryPersons = $em->getRepository('PostparcBundle:Person')->getListForSelection($selectionData['personIds']);
                $persons = $paginator->paginate(
                        $queryPersons, /* query NOT result */
                        $request->query->getInt('PagePerson', 1)/* page number */,
                        1000/* limit per page */,
                        [
                            'pageParameterName' => 'PagePerson',
                            'sortFieldParameterName' => 'SortPerson',
                            'sortDirectionParameterName' => 'DirectionPerson',
                        ]
                );
                $persons->setParam('activeTab', 'persons');
                //$nbElements += $persons->getTotalItemCount();
                $nbElements += count($selectionData['personIds']);
            }
            // Pfos
            if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
                $queryPfos = $em->getRepository('PostparcBundle:Pfo')->getListForSelection($selectionData['pfoIds']);
                $pfos = $paginator->paginate(
                        $queryPfos, /* query NOT result */
                        $request->query->getInt('PagePfo', 1)/* page number */,
                        1000/* limit per page */,
                        [
                            'pageParameterName' => 'PagePfo',
                            'sortFieldParameterName' => 'SortPfo',
                            'sortDirectionParameterName' => 'DirectionPfo',
                        ]
                );
                $pfos->setParam('activeTab', 'pfos');
                //$nbElements += $pfos->getTotalItemCount();
                $nbElements += count($selectionData['pfoIds']);
            }
            // organizations
            if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
                $queryOrganizations = $em->getRepository('PostparcBundle:Organization')->getListForSelection($selectionData['organizationIds']);
                $organizations = $paginator->paginate(
                        $queryOrganizations, /* query NOT result */
                        $request->query->getInt('PageOrganization', 1)/* page number */,
                        1000/* limit per page */,
                        [
                            'pageParameterName' => 'PageOrganization',
                            'sortFieldParameterName' => 'SortOrganization',
                            'sortDirectionParameterName' => 'DirectionOrganization',
                        ]
                );
                $organizations->setParam('activeTab', 'organizations');
                //$nbElements += $organizations->getTotalItemCount();
                $nbElements += count($selectionData['organizationIds']);
            }

            // representations
            if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
                $queryRepresentations = $em->getRepository('PostparcBundle:Representation')->getListForSelection($selectionData['representationIds'], null, null, false);
                $representations = $paginator->paginate(
                        $queryRepresentations, /* query NOT result */
                        $request->query->getInt('PageRepresentation', 1)/* page number */,
                        1000/* limit per page */,
                        [
                            'pageParameterName' => 'PageRepresentation',
                            'sortFieldParameterName' => 'SortRepresentation',
                            'sortDirectionParameterName' => 'DirectionRepresentation',
                        ]
                );
                $representations->setParam('activeTab', 'representations');
                //$nbElements += $representations->getTotalItemCount();
                $nbElements += count($selectionData['representationIds']);
            }
        }

        //allResults
        $allResults = [
            'persons' => isset($queryPersons) ? $queryPersons->execute() : null,
            'pfos' => isset($queryPfos) ? $queryPfos->execute() : null,
            'organizations' => isset($queryOrganizations) ? $queryOrganizations->execute() : null,
        ];

        if (isset($queryRepresentations)) {
            $allResults['representations'] = $queryRepresentations->execute();
        }

        // récupération des groupes
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();

        return $this->render('selection/index.html.twig', [
                    'nbElements' => $nbElements,
                    'persons' => $persons,
                    'pfos' => $pfos,
                    'organizations' => $organizations,
                    'representations' => $representations,
                    'activeTab' => $activeTab,
                    'allResults' => $allResults,
        ]);
    }

    /**
     * @param Request $request
     * @Route("/addToGroup", name="selection_addToGroup"))
     *
     * @return Response
     */
    public function addToGroupAction(Request $request) {
        $session = $request->getSession();
        $nbPersonsAdded = 0;
        $nbPfosAdded = 0;
        $nbRepresentationsAdded = 0;
        $nbOrganizationsAdded = 0;

        if ($request->query->has('groupId') && $session->has('selection')) {
            $em = $this->getDoctrine()->getManager();
            $groupId = $request->query->get('groupId');

            $group = $em->getRepository('PostparcBundle:Group')->find($groupId);

            $selectionData = $session->get('selection');
            // persons
            if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
                $personIdsAlreadyInGroup = $em->getRepository('PostparcBundle:Person')->personIdsInGroup($groupId);
                // nettoyage des élements déjà associés au groupe
                $personIdsToBeAddedToGroup = array_diff($selectionData['personIds'], $personIdsAlreadyInGroup);
                if (($personIdsToBeAddedToGroup !== []) > 0) {
                    $persons = $em->getRepository('PostparcBundle:Person')->getListForSelection($personIdsToBeAddedToGroup)->getResult();
                    foreach ($persons as $person) {
                        $pfoPersonGroup = new PfoPersonGroup();
                        $pfoPersonGroup->setGroup($group);
                        $pfoPersonGroup->setPerson($person);
                        $em->persist($pfoPersonGroup);
                        ++$nbPersonsAdded;
                    }
                }
            }
            //pfos
            if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
                $pfoIdsAlreadyInGroup = $em->getRepository('PostparcBundle:Pfo')->pfoIdsInGroup($groupId);
                // nettoyage des élements déjà associés au groupe
                $pfoIdsToBeAddedToGroup = array_diff($selectionData['pfoIds'], $pfoIdsAlreadyInGroup);
                if (($pfoIdsToBeAddedToGroup !== []) > 0) {
                    $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForSelection($pfoIdsToBeAddedToGroup)->getResult();
                    foreach ($pfos as $pfo) {
                        $pfoPersonGroup = new PfoPersonGroup();
                        $pfoPersonGroup->setGroup($group);
                        $pfoPersonGroup->setPfo($pfo);
                        $em->persist($pfoPersonGroup);
                        ++$nbPfosAdded;
                    }
                }
            }
            // representation
            if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
                $representationIdsAlreadyInGroup = $em->getRepository('PostparcBundle:Representation')->representationIdsInGroup($groupId);
                // nettoyage des élements déjà associés au groupe
                $representationIdsToBeAddedToGroup = array_diff($selectionData['representationIds'], $representationIdsAlreadyInGroup);
                if (($representationIdsToBeAddedToGroup !== []) > 0) {
                    $representations = $em->getRepository('PostparcBundle:Representation')->getListForSelection($representationIdsToBeAddedToGroup)->getResult();
                    foreach ($representations as $representation) {
                        $representation->addGroup($group);
                        $em->persist($representation);
                        ++$nbRepresentationsAdded;
                    }
                }
            }
            // organization
            if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
                $organizationIdsAlreadyInGroup = $em->getRepository('PostparcBundle:Organization')->organizationIdsInGroup($groupId);
                // nettoyage des élements déjà associés au groupe
                $organizationIdsToBeAddedToGroup = array_diff($selectionData['organizationIds'], $organizationIdsAlreadyInGroup);
                if (($organizationIdsToBeAddedToGroup !== []) > 0) {
                    $organizations = $em->getRepository('PostparcBundle:Organization')->getListForSelection($organizationIdsToBeAddedToGroup)->getResult();
                    foreach ($organizations as $organization) {
                        $organization->addGroup($group);
                        $em->persist($organization);
                        ++$nbOrganizationsAdded;
                    }
                }
            }
            if ($nbPersonsAdded > 0 || $nbPfosAdded > 0 || $nbRepresentationsAdded > 0 || $nbOrganizationsAdded > 0) {
                $em->flush();
                $request->getSession()
                        ->getFlashBag()
                        ->add('success', 'flash.addToGroupFromSelectionSuccess');
            } else {
                $message = 'flash.NoElementaddToGroupFromSelectionError';
                if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
                    $message = 'flash.OrganizationCantBeAddedToGroupError';
                }
                $request->getSession()
                        ->getFlashBag()
                        ->add('error', $message);
            }
        }

        return $this->redirect($this->generateUrl('selection_show'));
    }

    /**
     * @param Request $request
     * @Route("/addToEvent", name="selection_addToEvent"), methods="POST")
     *
     * @return Response
     */
    public function addToEventAction(Request $request) {
        $session = $request->getSession();
        $nbPersonsAdded = 0;
        $nbPfosAdded = 0;
        $nbRepresentationsAdded = 0;
        if ($request->request->has('eventId') && $session->has('selection')) {
            $em = $this->getDoctrine()->getManager();
            $eventId = $request->request->get('eventId');
            $event = $em->getRepository('PostparcBundle:Event')->find($eventId);
            $selectionData = $session->get('selection');

            // persons
            if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
                $personIdsAlreadyInEvent = $em->getRepository('PostparcBundle:Person')->personIdsInEvent($eventId);
                // nettoyage des élements déjà associés à l'evenement
                $personIdsToBeAddedToEvent = array_diff($selectionData['personIds'], $personIdsAlreadyInEvent);
                if (($personIdsToBeAddedToEvent !== []) > 0) {
                    $persons = $em->getRepository('PostparcBundle:Person')->getListForSelection($personIdsToBeAddedToEvent)->getResult();
                    foreach ($persons as $person) {
                        $eventPersons = new EventPersons();
                        $eventPersons->setEvent($event);
                        $eventPersons->setPerson($person);
                        $em->persist($eventPersons);
                        ++$nbPersonsAdded;
                    }
                }
            }
            //pfos
            if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
                $pfoIdsAlreadyInEvent = $em->getRepository('PostparcBundle:Pfo')->pfoIdsInEvent($eventId);
                // nettoyage des élements déjà associés à l'evenement
                $pfoIdsToBeAddedToEvent = array_diff($selectionData['pfoIds'], $pfoIdsAlreadyInEvent);
                if (($pfoIdsToBeAddedToEvent !== []) > 0) {
                    $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForSelection($pfoIdsToBeAddedToEvent)->getResult();
                    foreach ($pfos as $pfo) {
                        $eventPfos = new EventPfos();
                        $eventPfos->setEvent($event);
                        $eventPfos->setPfo($pfo);
                        $em->persist($eventPfos);
                        ++$nbPfosAdded;
                    }
                }
            }
            //representations
            if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
                $representationIdsAlreadyInEvent = $em->getRepository('PostparcBundle:Representation')->representationIdsInEvent($eventId);
                // nettoyage des élements déjà associés à l'evenement
                $representationIdsToBeAddedToEvent = array_diff($selectionData['representationIds'], $representationIdsAlreadyInEvent);
                if (($representationIdsToBeAddedToEvent !== []) > 0) {
                    $representations = $em->getRepository('PostparcBundle:Representation')->getListForSelection($representationIdsToBeAddedToEvent)->getResult();
                    foreach ($representations as $representation) {
                        $eventRepresentations = new EventRepresentations();
                        $eventRepresentations->setEvent($event);
                        $eventRepresentations->setRepresentation($representation);
                        $em->persist($eventRepresentations);
                        ++$nbRepresentationsAdded;
                    }
                }
            }
            if ($nbPersonsAdded > 0 || $nbPfosAdded > 0 || $nbRepresentationsAdded > 0) {
                $em->flush();
                $request->getSession()
                        ->getFlashBag()
                        ->add('success', 'flash.addToEventFromSelectionSuccess');
            } else {
                $request->getSession()
                        ->getFlashBag()
                        ->add('error', 'flash.NoElementaddToEventFromSelectionError');
            }
        }

        return $this->redirect($this->generateUrl('selection_show'));
    }

    /**
     * @param Request $request
     * @param string  $modele
     * @param int     $id
     * @Route("/remove/{modele}/{id}", name="selection_remove"))
     *
     * @return Response
     */
    public function removeFormSelectionAction(Request $request, $modele, $id) {
        $session = $request->getSession();
        $selectionData = $session->get('selection');
        switch ($modele) {
            case 'person':
                $index = array_search($id, $selectionData['personIds']);
                unset($selectionData['personIds'][$index]);
                $activeTabs = 'persons';
                break;
            case 'pfo':
                $index = array_search($id, $selectionData['pfoIds']);
                unset($selectionData['pfoIds'][$index]);
                $activeTabs = 'pfos';
                break;
            case 'organization':
                $index = array_search($id, $selectionData['organizationIds']);
                unset($selectionData['organizationIds'][$index]);
                $activeTabs = 'organizations';
                break;
            case 'representation':
                $index = array_search($id, $selectionData['representationIds']);
                unset($selectionData['representationIds'][$index]);
                $activeTabs = 'representations';
                break;
            default:
                $activeTabs = 'persons';
                break;
        }
        $session->set('selection', $selectionData);

        $request->getSession()
                ->getFlashBag()
                ->add('success', 'flash.deleteFromSelectionSuccess');

        return $this->redirect($this->generateUrl('selection_show') . '#' . $activeTabs);
    }

    /**
     * @param Request $request
     * @Route("/removeAll", name="selection_removeAll"))
     *
     * @return Response
     */
    public function removeAllSelection(Request $request) {
        $session = $request->getSession();
        $session->set('selection', []);

        $request->getSession()
                ->getFlashBag()
                ->add('success', 'flash.deleteAllSelectionSuccess');

        if ($request->query->has('fromUrl')) {
            return $this->redirect($request->query->get('fromUrl'));
        }

        return $this->redirect($this->generateUrl('selection_show'));
    }

    /**
     * @param Request $request
     * @Route("/exportSelectionVcard", name="selection_export_vcard"))
     *
     * @return Response
     */
    public function exportSelectionVcardAction(Request $request) {
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $selectionData = $session->get('selection');
        $user = $this->getUser();
        $content = '';
        // persons
        if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
            $persons = $em->getRepository('PostparcBundle:Person')->findBy(['id' => $selectionData['personIds']]);
            foreach ($persons as $person) {
                if (!$person->getDontWantToBeContacted() && (($user->hasRole('ROLE_CONTRIBUTOR') || $user->hasRole('ROLE_CONTRIBUTOR_PLUS') || $user->hasRole('ROLE_ADMIN')) || !$person->getDontShowCoordinateForReaders())
                ) {
                    $content .= $person->generateVcardContent($personnalFieldsRestriction);
                }
            }
        }
        // pfos
        if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
            $pfos = $em->getRepository('PostparcBundle:Pfo')->findBy(['id' => $selectionData['pfoIds']]);
            foreach ($pfos as $pfo) {
                $content .= $pfo->generateVcardContent($personnalFieldsRestriction);
            }
        }
        // organizations
        if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
            $organizations = $em->getRepository('PostparcBundle:Organization')->findBy(['id' => $selectionData['organizationIds']]);
            foreach ($organizations as $organization) {
                $content .= $organization->generateVcardContent();
            }
        }
        // representations
        if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
            $representations = $em->getRepository('PostparcBundle:Representation')->findBy(['id' => $selectionData['representationIds']]);
            foreach ($representations as $representation) {
                $content .= $representation->generateVcardContent();
            }
        }

        $response = new Response();
        $response->setContent($content);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/x-vcard');
        $response->headers->set('Content-Disposition', 'attachment; filename="postparc_selection_export_Vcad.vcf"');
        $response->headers->set('Content-Length', mb_strlen($content, 'utf-8'));

        return $response;
    }

    /**
     * @param Request $request
     * @Route("/exportSelection", name="selection_export"))
     *
     * @return Response
     */
    public function exportSelectionAction(Request $request) {
        $session = $request->getSession();
        $selectionData = $session->get('selection');
        $translator = $this->get('translator');
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $exportOptions = [
            'person' => [
                'person[civility]' => $translator->trans('Person.field.civility'),
                'person[lastName]' => $translator->trans('Person.field.name'),
                'person[firstName]' => $translator->trans('Person.field.firstName'),
                'person[birthDate]' => $translator->trans('Person.field.birthDate'),
                'person[birthLocation]' => $translator->trans('Person.field.birthLocation'),
                'person[profession]' => $translator->trans('Person.field.profession'),
                'person[tags]' => $translator->trans('genericFields.tags'),
                'person[observation]' => $translator->trans('Person.field.observation'),
                'person[pfoPersonGroups]' => $translator->trans('Person.field.pfoPersonGroups'),
                'person[organizations]' => $translator->trans('Person.field.organizations'),
                'person[coordinate]' => $translator->trans('Person.field.coordinate'),                
            ],
            'organization' => [
                'organization[name]' => $translator->trans('Organization.field.name'),
                'organization[abbreviation]' => $translator->trans('Organization.field.abbreviation'),
                'organization[organizationType]' => $translator->trans('Organization.field.organizationType'),
                'organization[observation]' => $translator->trans('Organization.field.observation'),
                'organization[tags]' => $translator->trans('genericFields.tags'),
                'organization[pfoPersonGroups]' => $translator->trans('Pfo.field.pfoPersonGroups'),
                'organization[coordinate]' => $translator->trans('Organization.field.coordinate'),                
            ],
            'pfo' => [
                'pfo[person]' => $translator->trans('Pfo.field.person'),
                'pfo[organization]' => $translator->trans('Pfo.field.organization'),
                'pfo[particleFunction]' => $translator->trans('Pfo.field.particleFunction'),
                'pfo[function]' => $translator->trans('Pfo.field.personFunction'),
                'pfo[additionalFunction]' => $translator->trans('Pfo.field.additionalFunction'),
                'pfo[service]' => $translator->trans('Pfo.field.service'),
                'pfo[observation]' => $translator->trans('Pfo.field.observation'),
                'pfo[preferedEmails]' => $translator->trans('Person.field.preferedEmails'),
                'pfo[pfoPersonGroups]' => $translator->trans('Pfo.field.pfoPersonGroups'),
                'pfo[connectingCity]' => $translator->trans('Pfo.field.connectingCity'),
                'pfo[phone]' => $translator->trans('Pfo.field.phone'),
                'pfo[mobilePhone]' => $translator->trans('Pfo.field.mobilePhone'),
                'pfo[fax]' => $translator->trans('Pfo.field.fax'),
                'pfo[email]' => $translator->trans('Pfo.field.email'),
                'pfo[tags]' => $translator->trans('genericFields.tags'),
                'pfo[preferedCoordinateAddress]' => $translator->trans('Pfo.field.preferedCoordinateAddress'),
            ],
            'coordinate' => [
                'coordinate[addressLine1]' => $translator->trans('Coordinate.field.addressLine1'),
                'coordinate[addressLine2]' => $translator->trans('Coordinate.field.addressLine2'),
                'coordinate[addressLine3]' => $translator->trans('Coordinate.field.addressLine3'),
                'coordinate[zipCode]' => $translator->trans('City.field.zipCode'),
                'coordinate[city]' => $translator->trans('Coordinate.field.city'),
                'coordinate[cedex]' => $translator->trans('Coordinate.field.cedex'),
                'coordinate[phone]' => $translator->trans('Coordinate.field.phone'),
                'coordinate[mobilePhone]' => $translator->trans('Coordinate.field.mobilePhone'),
                'coordinate[fax]' => $translator->trans('Coordinate.field.fax'),
                'coordinate[email]' => $translator->trans('Coordinate.field.email'),
                'coordinate[webSite]' => $translator->trans('Coordinate.field.webSite'),
                'coordinate[coordinate]' => $translator->trans('Coordinate.field.geographicalCoordinate'),
            ],
            'options' => [
                'options[use_prefered_address]' => $translator->trans('Selection.export.options.use_prefered_address'),
            ],
        ];
        if ('udaf' == $this->container->get('kernel')->getEnvironment()) {
            $exportOptions['person']['person[nbMinorChildreen]'] = $translator->trans('Person.field.nbMinorChildreen');
            $exportOptions['person']['person[nbMajorChildreen]'] = $translator->trans('Person.field.nbMajorChildreen');
        }
        if ($currentEntityConfig['use_representation_module']) {
            $exportOptions['representation'] = [
                'representation[person]' => $translator->trans('Representation.field.person'),
                'representation[name]' => $translator->trans('Representation.field.name'),
                'representation[elected]' => $translator->trans('Representation.field.elected'),
                'representation[beginDate]' => $translator->trans('Representation.field.beginDate'),
                'representation[mandatDuration]' => $translator->trans('Representation.field.mandatDuration'),
                'representation[estimatedTime]' => $translator->trans('Representation.field.estimatedTime'),
                'representation[estimatedCost]' => $translator->trans('Representation.field.estimatedCost'),
                'representation[periodicity]' => $translator->trans('Representation.field.periodicity'),
                'representation[mandateType]' => $translator->trans('Representation.field.mandateType'),
                'representation[service]' => $translator->trans('Representation.field.service'),
                'representation[personFunction]' => $translator->trans('Representation.field.personFunction'),
                'representation[natureOfRepresentation]' => $translator->trans('Representation.field.natureOfRepresentation'),
                'representation[specificCoordinate]' => $translator->trans('Representation.field.specificCoordinate'),
                'representation[organization]' => $translator->trans('Representation.field.organization'),
            ];
        }

        $nbElements = $this->getNbElementsInSelection($request);
        $selectionDataCoordinatesDetails = $this->getSelectionDataCoordinatesDetails($request);

        return $this->render('selection/exportSelection.html.twig', [
                    'selectionData' => $selectionData,
                    'exportOptions' => $exportOptions,
                    'nbElements' => $nbElements,
                    'selectionDataCoordinatesDetails' => $selectionDataCoordinatesDetails,
        ]);
    }

    private function getSelectionDataCoordinatesDetails($request) {
        $selectionDataCoordinatesDetails = [];
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $selectionData = $session->get('selection');
        //dump($selectionData);

        if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
            $persons = $em->getRepository(Person::class)->getListForSelection($selectionData['personIds'], 'export')->getResult();
            foreach ($persons as $person) {
                $selectionDataCoordinatesDetails['persons'][$person->getId()] = [
                    'label' => $person->__toString(),
                    'coordinate' => $person->getCoordinate()
                ];
            }
        }
        if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
            $organizations = $em->getRepository(Organization::class)->getListForSelection($selectionData['organizationIds'])->getResult();
            //dump($organizations);
            foreach ($organizations as $organization) {
                $selectionDataCoordinatesDetails['organizations'][$organization->getId()] = [
                    'label' => $organization->__toString(),
                    'coordinate' => $organization->getCoordinate()
                ];
            }
        }
        if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
            $pfos = $em->getRepository(Pfo::class)->getListForSelection($selectionData['pfoIds'])->getResult();
            foreach ($pfos as $pfo) {
                $selectionDataCoordinatesDetails['pfos'][$pfo->getId()] = [
                    'label' => $pfo->__toString(),
                    'coordinate' => $pfo->getCoordinate()
                ];
            }
        }
        if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
            $representations = $em->getRepository(Representation::class)->getListForSelection($selectionData['representationIds'])->getResult();
            //dump($representations);
            foreach ($representations as $representation) {
                $selectionDataCoordinatesDetails['representations'][$representation->getId()] = [
                    'label' => $representation->__toString(),
                    'coordinate' => $representation->getCoordinate()
                ];
            }
        }

        return $selectionDataCoordinatesDetails;
    }

    /**
     * @param Request $request
     * @Route("/exportSelectionExecute", name="selection_export_execute"))
     *
     * @return Response
     */
    public function exportSelectionExecuteAction(Request $request) {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        $selectionData = $session->get('selection');
        $nbElements = $this->getNbElementsInSelection($request);
        if (!$session->has('selection') || 0 == $nbElements) {
            return $this->redirect($this->generateUrl('selection_export'));
        }
        $translator = $this->get('translator');
        $activeSheetNumber = 0;

        //print_r($request->request->all());die;;
        $phpExcelObject = $this->get('phpspreadsheet')->createSpreadsheet();
        $phpExcelObject->getProperties()->setCreator('Postparc')->setTitle('Export Selection');

        // Persons
        if ($request->request->has('person') && isset($selectionData['personIds']) && count($selectionData['personIds'])) {
            // mise en place des entêtes de colonnes
            $this->setExportColsHeader('person', $activeSheetNumber, $phpExcelObject, $request);
            // peuplement
            $persons = $em->getRepository('PostparcBundle:Person')->getListForSelection($selectionData['personIds'], 'export')->getResult();
            $this->insertPersonsInfos($persons, $activeSheetNumber, $phpExcelObject, $request);

            $phpExcelObject->getActiveSheet()->setTitle(substr($translator->trans('Person.labels'), 0, 30));
            ++$activeSheetNumber;
        }

        // pfos
        if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
            $phpExcelObject->createSheet();
            // mise en place des entêtes de colonnes
            $this->setExportColsHeader('pfo', $activeSheetNumber, $phpExcelObject, $request);
            // peuplement
            $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForSelection($selectionData['pfoIds'])->getResult();
            $this->insertPfosInfos($pfos, $activeSheetNumber, $phpExcelObject, $request);

            $phpExcelObject->getActiveSheet()->setTitle(substr($translator->trans('Pfo.labels'), 0, 30));
            ++$activeSheetNumber;
        }

        // Organizations
        if ($request->request->has('organization') && isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
            $phpExcelObject->createSheet();
            // mise en place des entêtes de colonnes
            $this->setExportColsHeader('organization', $activeSheetNumber, $phpExcelObject, $request);
            // peuplement
            $organizations = $em->getRepository('PostparcBundle:Organization')->getListForSelection($selectionData['organizationIds'])->getResult();
            $this->insertOrganizationsInfos($organizations, $activeSheetNumber, $phpExcelObject, $request);

            $phpExcelObject->getActiveSheet()->setTitle(substr($translator->trans('Organization.labels'), 0, 30));
            ++$activeSheetNumber;
        }

        // Representations
        if ($request->request->has('representation') && isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
            $phpExcelObject->createSheet();
            // mise en place des entêtes de colonnes
            $this->setExportColsHeader('representation', $activeSheetNumber, $phpExcelObject, $request);
            // peuplement
            $representations = $em->getRepository('PostparcBundle:Representation')->getListForSelection($selectionData['representationIds'], null, null, false)->getResult();
            $this->insertRepresentationsInfos($representations, $activeSheetNumber, $phpExcelObject, $request);

            $phpExcelObject->getActiveSheet()->setTitle(substr($translator->trans('Representation.labels'), 0, 30));
            ++$activeSheetNumber;
        }

        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpspreadsheet')->createWriter($phpExcelObject);

        // create the response
        $response = $this->get('phpspreadsheet')->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'export_selection.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * Show page infos for print selection sticker's.
     *
     * @param Request $request
     * @Route("/printSelection", name="print_selection_home"))
     *
     * @return Response
     * */
    public function getPrintSelectionHome(Request $request) {
        $nbElements = $this->getNbElementsInSelection($request);

        return $this->render('selection/printSelection.html.twig', [
                    'nbElements' => $nbElements,
        ]);
    }

    /**
     * Show print option block.
     *
     * @Route("/printoptions", name="print_options"))
     *
     * @return Response
     * */
    public function getPrintOptionsAction(Request $request, $forGenerationMassiveDocument = false) {
        // get print format in database
        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $entityId = $currentEntityService->getCurrentEntityId();

        $printFormats = $em->getRepository('PostparcBundle:PrintFormat')->getPrintFormatsForSelect($entityId);
        $translator = $this->get('translator');
        $printOptions = [
            'person' => [
                'person[civility]' => $translator->trans('Person.field.civility'),
                'person[lastName]' => $translator->trans('Person.field.name'),
                'person[firstName]' => $translator->trans('Person.field.firstName'),
                'person[coordinate]' => $translator->trans('Person.field.coordinate'),
            ],
            'coordinate' => [
                'coordinate[addressLine1]' => $translator->trans('Coordinate.field.addressLine1'),
                'coordinate[addressLine2]' => $translator->trans('Coordinate.field.addressLine2'),
                'coordinate[addressLine3]' => $translator->trans('Coordinate.field.addressLine3'),
                'coordinate[zipCode]' => $translator->trans('City.field.zipCode'),
                'coordinate[city]' => $translator->trans('Coordinate.field.city'),
                'coordinate[cedex]' => $translator->trans('Coordinate.field.cedex'),
                'coordinate[country]' => $translator->trans('City.field.country'),
            ],
            'organization' => [
                'organization[name]' => $translator->trans('Organization.field.name'),
                'organization[abbreviation]' => $translator->trans('Organization.field.abbreviation'),
                'organization[coordinate]' => $translator->trans('Organization.field.coordinate'),
            ],
            'pfo' => [
                'pfo[person]' => $translator->trans('Pfo.field.person'),
                'pfo[organization]' => $translator->trans('Pfo.field.organization'),
                'pfo[particleFunction]' => $translator->trans('Pfo.field.particleFunction'),
                'pfo[function]' => $translator->trans('Pfo.field.personFunction'),
                'pfo[additionalFunction]' => $translator->trans('Pfo.field.additionalFunction'),
                'pfo[service]' => $translator->trans('Pfo.field.service'),
                'pfo[coordinate]' => $translator->trans('Pfo.field.coordinate'),
            ],
            'options' => [
                'options[downloadGeneratedFile]' => $translator->trans('Selection.print.options.download_generated_file'),
                'options[deleteDuplicateCoordinnate]' => $translator->trans('Selection.print.options.deleteDuplicateCoordinnate'),
            ],
        ];
        if (array_key_exists('use_representation_module', $currentEntityConfig) && $currentEntityConfig['use_representation_module']) {
            $printOptions['representation'] = [
                'representation[person]' => $translator->trans('Pfo.field.person'),
                'representation[service]' => $translator->trans('Representation.field.service'),
                'representation[civilityFunction]' => $translator->trans('Representation.field.civilityFunction'),
                'representation[function]' => $translator->trans('Pfo.field.personFunction'),
                'representation[particleFunction]' => $translator->trans('Pfo.field.particleFunction'),
                'representation[coordinate]' => $translator->trans('Representation.field.specificCoordinate'),
                'representation[organization]' => $translator->trans('Representation.field.organization'),
            ];
        }

        return $this->render('selection/printOptions.html.twig', [
                    'printFormats' => $printFormats,
                    'printOptions' => $printOptions,
                    'forGenerationMassiveDocument' => $forGenerationMassiveDocument,
        ]);
    }

    /**
     * print selection sticker's.
     *
     * @param Request $request
     * @param bool    $onlyWithoutMail
     * @Route("/printSelection/execute", name="print_selection_execute"))
     *
     * @return Response
     * */
    public function getPrintSelectionExecute(Request $request, $onlyWithoutMail = false) {
        $em = $this->getDoctrine()->getManager();
        $slugify = new Slugify();
        $postRequest = $request->request;
        $printFormatId = $postRequest->get('printFormatId');
        if (is_null($printFormatId)) {
            $printFormatId = 1;
        }
        $printFormat = $em->getRepository('PostparcBundle:PrintFormat')->find($printFormatId);
        $pdf = $this->container->get('white_october.tcpdf')->create();

        $pdfPageFormat = $printFormat->getFormat();
        $pdfPageOrientation = $printFormat->getOrientation();
        $pdfMarginLeft = $printFormat->getMarginLeft();
        $pdfMarginRight = $printFormat->getMarginRight();
        $pdfMarginTop = $printFormat->getMarginTop();
        $pdfMarginBottom = $printFormat->getMarginBottom();

        // info concernant les etiquettes
        $hauteurEtiquette = $printFormat->getStickerHeight();
        $largeurEtiquette = $printFormat->getStickerWidth();
        $margeHorizontaleInterEtiquette = $printFormat->getPaddingHorizontalInterSticker();
        $margeVerticalInterEtiquette = $printFormat->getPaddingVerticalInterSticker();
        $nbEtiquetteParLigne = $printFormat->getNumberPerRow();
        $retraitVerticalInterEtiquette = $printFormat->getMarginVerticalInterSticker();
        $retraitHorizontalInterEtiquette = $printFormat->getMarginHorizontalInterSticker();
        $stickerFonts = $printFormat->getStickerFonts();
        $stickerFontsize = $printFormat->getStickerFontsize();

        $docTitle = 'Impression étiquette';
        $docSubject = "Document permettant l'impression des etiquettes";
        $docKeywords = 'impression etiquettes';

        $pageWitdth = (int) $pdf->getPageWidth();
        $pageHeight = (int) $pdf->getPageHeight();

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(PDF_AUTHOR);
        $pdf->SetTitle($docTitle);
        $pdf->SetSubject($docSubject);
        $pdf->SetKeywords($docKeywords);

        //set margins
        $pdf->SetMargins($pdfMarginLeft, $pdfMarginTop, $pdfMarginRight);

        //set auto page breaks
        $pdf->SetAutoPageBreak(true, $pdfMarginBottom);
        $pdf->SetHeaderMargin($pdfMarginTop);
        $pdf->SetFooterMargin($pdfMarginBottom);

        $pdf->SetFont($stickerFonts, '', $stickerFontsize);

        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

        // remove page header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        //initialize document
        //$pdf->AliasNbPages(); n'existe pas
        $pdf->AddPage();

        // initialisation du pointeur
        $pdf->setY($pdfMarginLeft);
        $pdf->setX($pdfMarginTop);
        $currentYposition = $pdf->getY() + $retraitHorizontalInterEtiquette;
        $currentXposition = $pdf->getX() + $retraitVerticalInterEtiquette;

        $numEtiquette = 0;
        $numPpage = 1;

        $deleteDuplicateCoordinnate = false;
        if ($postRequest->has('options') && isset($postRequest->get('options')['deleteDuplicateCoordinnate'])) {
            $deleteDuplicateCoordinnate = true;
        }

        $session = $request->getSession();
        if (!$session->has('selection')) {
            return $this->redirect($this->generateUrl('selection_show'));
        }
        $selectionData = $session->get('selection');
        $alreadyAdded = [];
        // persons
        if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
            if ($onlyWithoutMail) {
                $persons = $em->getRepository('PostparcBundle:Person')->getPersonWithoutEmails($selectionData['personIds']);
            } else {
                $persons = $em->getRepository('PostparcBundle:Person')->getListForSelection($selectionData['personIds'], 'print')->getResult();
            }
            // filtrage pour éviter les doublons
            foreach ($persons as $key => $object) {
                $string = $object->getCoordinateStringForDuplicateSearch();
                $slug = $slugify->slugify($string);
                if (in_array($slug, $alreadyAdded) && $deleteDuplicateCoordinnate) {
                    unset($persons[$key]);
                } else {
                    $alreadyAdded[] = $slug;
                }
            }
            $this->printEntitiesSticker($pdf, $currentXposition, $currentYposition, $numEtiquette, $nbEtiquetteParLigne, $largeurEtiquette, $hauteurEtiquette, $pdfMarginLeft, $pdfMarginTop, $pageHeight, $retraitHorizontalInterEtiquette, $retraitVerticalInterEtiquette, $margeHorizontaleInterEtiquette, $margeVerticalInterEtiquette, $persons, $postRequest);
        }
        // pfos
        if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
            if ($onlyWithoutMail) {
                $pfos = $em->getRepository('PostparcBundle:Pfo')->getPfoWithoutEmails($selectionData['pfoIds']);
            } else {
                $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForSelection($selectionData['pfoIds'])->getResult();
            }
            // filtrage pour éviter les doublons
            //$alreadyAdded = [];
            foreach ($pfos as $key => $object) {
                $string = $object->getCoordinateStringForDuplicateSearch();
                $slug = $slugify->slugify($string);
                if (in_array($slug, $alreadyAdded) && $deleteDuplicateCoordinnate) {
                    unset($pfos[$key]);
                } else {
                    $alreadyAdded[] = $slug;
                }
            }
            $this->printEntitiesSticker($pdf, $currentXposition, $currentYposition, $numEtiquette, $nbEtiquetteParLigne, $largeurEtiquette, $hauteurEtiquette, $pdfMarginLeft, $pdfMarginTop, $pageHeight, $retraitHorizontalInterEtiquette, $retraitVerticalInterEtiquette, $margeHorizontaleInterEtiquette, $margeVerticalInterEtiquette, $pfos, $postRequest);
        }
        // organizations
        if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
            if ($onlyWithoutMail) {
                $organizations = $em->getRepository('PostparcBundle:Organization')->getOrganizationWithoutEmails($selectionData['organizationIds']);
            } else {
                $organizations = $em->getRepository('PostparcBundle:Organization')->getListForSelection($selectionData['organizationIds'])->getResult();
            }
            // filtrage pour éviter les doublons
            //$alreadyAdded = [];
            foreach ($organizations as $key => $object) {
                $string = $object->getCoordinateStringForDuplicateSearch();
                $slug = $slugify->slugify($string);
                if (in_array($slug, $alreadyAdded) && $deleteDuplicateCoordinnate) {
                    unset($organizations[$key]);
                } else {
                    $alreadyAdded[] = $slug;
                }
            }
            $this->printEntitiesSticker($pdf, $currentXposition, $currentYposition, $numEtiquette, $nbEtiquetteParLigne, $largeurEtiquette, $hauteurEtiquette, $pdfMarginLeft, $pdfMarginTop, $pageHeight, $retraitHorizontalInterEtiquette, $retraitVerticalInterEtiquette, $margeHorizontaleInterEtiquette, $margeVerticalInterEtiquette, $organizations, $postRequest);
        }
        // representations
        if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
            if ($onlyWithoutMail) {
                $representations = $em->getRepository('PostparcBundle:Representation')->getRepresentationWithoutEmails($selectionData['representationIds']);
            } else {
                $representations = $em->getRepository('PostparcBundle:Representation')->getListForSelection($selectionData['representationIds'], isset($selectionData['personIds']) ? $selectionData['personIds'] : null, isset($selectionData['pfoIds']) ? $selectionData['pfoIds'] : null, false)->getResult();
            }
            // filtrage pour éviter les doublons
            //$alreadyAdded = [];
            foreach ($representations as $key => $object) {
                $string = $object->getCoordinateStringForDuplicateSearch();
                $slug = $slugify->slugify($string);
                if (in_array($slug, $alreadyAdded) && $deleteDuplicateCoordinnate) {
                    unset($representations[$key]);
                } else {
                    $alreadyAdded[] = $slug;
                }
            }
            $this->printEntitiesSticker($pdf, $currentXposition, $currentYposition, $numEtiquette, $nbEtiquetteParLigne, $largeurEtiquette, $hauteurEtiquette, $pdfMarginLeft, $pdfMarginTop, $pageHeight, $retraitHorizontalInterEtiquette, $retraitVerticalInterEtiquette, $margeHorizontaleInterEtiquette, $margeVerticalInterEtiquette, $representations, $postRequest);
        }
        
        //print_r($alreadyAdded);die;

        // Close and output PDF document
        if ($postRequest->has('options') && isset($postRequest->get('options')['downloadGeneratedFile'])) {
            return new StreamedResponse(function () use ($pdf) {
                        $pdf->Output('export_postparc_' . date('d-m-Y_H-i') . '.pdf', 'D'); // en attachement
                    });
        } else {
            return new StreamedResponse(function () use ($pdf) {
                        $pdf->Output('export_postparc_' . date('d-m-Y_H-i') . '.pdf');
                    });
        }
    }

    /**
     * print selection sticker's only for enties without email.
     *
     * @param Request $request
     * @Route("/printSelectionWithoutEmail/execute", name="print_selection_withoutEmail_execute"))
     *
     * @return Response
     * */
    public function getPrintSelectionWithoutEmailExecute(Request $request) {
        return $this->getPrintSelectionExecute($request, 1);
    }

    /**
     * send email massif homepage.
     *
     * @param Request $request
     * @Route("/sendEmailMassif", name="send_email_massif"))
     *
     * @return Response
     * */
    public function sendEmailMassif(Request $request) {
        $session = $request->getSession();
        $nbElements = 0;
        $emails = [];
        $itemsWhitoutEmail = [];
        $personWithoutEmails =[];
        $organizationWithoutEmails =[];
        $representationWithoutEmails=[];
        $pfoWithoutEmails=[];
        $nbItemsWhitoutEmail = 0;
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            $nbElements = $this->getNbElementsInSelection($request);
            $em = $this->getDoctrine()->getManager();
            // récupération des emails
            $emails = $em->getRepository('PostparcBundle:Email')->getSelectionEmails($selectionData);
            // récupération des persons sans emails
            if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
                $personWithoutEmails = $em->getRepository('PostparcBundle:Person')->getPersonWithoutEmails($selectionData['personIds']);
                if ($personWithoutEmails) {
                    $itemsWhitoutEmail['persons'] = $personWithoutEmails;
                    $nbItemsWhitoutEmail += count($personWithoutEmails);
                }
            }
            // récupération des organizations sans emails
            if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
                $organizationWithoutEmails = $em->getRepository('PostparcBundle:Organization')->getOrganizationWithoutEmails($selectionData['organizationIds']);
                if ($organizationWithoutEmails) {
                    $itemsWhitoutEmail['organizations'] = $organizationWithoutEmails;
                    $nbItemsWhitoutEmail += count($organizationWithoutEmails);
                }
            }
            // récupération des pfos sans emails
            if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
                $pfoWithoutEmails = $em->getRepository('PostparcBundle:Pfo')->getPfoWithoutEmails($selectionData['pfoIds']);
                if ($pfoWithoutEmails) {
                    $itemsWhitoutEmail['pfos'] = $pfoWithoutEmails;
                    $nbItemsWhitoutEmail += count($pfoWithoutEmails);
                }
            }
            // récupération des representations sans emails
            if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
                $representationWithoutEmails = $em->getRepository('PostparcBundle:Representation')->getRepresentationWithoutEmails($selectionData['representationIds']);
                if ($representationWithoutEmails) {
                    $itemsWhitoutEmail['representations'] = $representationWithoutEmails;
                    $nbItemsWhitoutEmail += count($representationWithoutEmails);
                }
            }
        }
        $emails = array_unique($emails);
        $nbEmails = count($emails);

        return $this->render('selection/sendMailMassifSelection.html.twig', [
                    'nbElements' => $nbElements,
                    'emails' => $emails,
                    'nbEmails' => $nbEmails,
                    'itemsWhitoutEmail' => $itemsWhitoutEmail,
                    'nbItemsWhitoutEmail' => $nbItemsWhitoutEmail,
                    'personWithoutEmails' => $personWithoutEmails,
                    'organizationWithoutEmails' => $organizationWithoutEmails,
                    'pfoWithoutEmails' => $pfoWithoutEmails,
                    'representationWithoutEmails' => $representationWithoutEmails
        ]);
    }

    /**
     * export selection emails.
     *
     * @param Request $request
     * @Route("/exportCsvEmails", name="selection_exportCsvEmails"))
     *
     * @return Response
     * */
    public function exportCsvEmails(Request $request) {
        $session = $request->getSession();
        $emails = [];
        if ($session->has('selection')) {
            $em = $this->getDoctrine()->getManager();
            $selectionData = $session->get('selection');
            $emails = $em->getRepository('PostparcBundle:Email')->getSelectionEmails($selectionData);
        }
        $response = new StreamedResponse();
        $response->setCallback(function () use ($emails) {
            $handle = fopen('php://output', 'w+');
            // Nom des colonnes du CSV
            fputcsv($handle, ['Email'], ';');
            //Champs
            foreach ($emails as $email) {
                fputcsv($handle, [$email], ';');
            }
            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');

        return $response;
    }

    /**
     * homepage of generation document.
     *
     * @Route("/generate_massive_documents", name="generate_massive_document"))
     *
     * @return Response
     * */
    public function generateMassiveDocuments(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = array_key_exists('show_SharedContents', $currentEntityConfig) ? $currentEntityConfig['show_SharedContents'] : false;
        $documentTemplates = $em->getRepository('PostparcBundle:DocumentTemplate')->getActiveDocumentTemplates($entityId, $show_SharedContents, false, $this->getUser());
        
        // search already uploaded files
        $uploadedModelFiles = [];
        $filePathDir = $this->get('kernel')->getRootDir() . '/../web/uploads/documents/models/'.$this->get('kernel')->getEnvironment();
        // check if folder exist, create it if not
        if(empty(glob($filePathDir))){
            mkdir($filePathDir, 0777, true);
        }
        $finder = new Finder();
        $finder->files()->in($filePathDir);
        foreach ($finder as $file) {
            $uploadedModelFiles[] = $file->getRelativePathname();
        }

        return $this->render('selection/generateMassiveDocuments.html.twig', [
            'documentTemplates' => $documentTemplates,
            'uploadedModelFiles' => $uploadedModelFiles
        ]);
    }

    /**
     * execution of generation document.
     *
     * @param Request $request
     * @Route("/generate_massive_documents_execute", name="generate_massive_document_execute"))
     *
     * @return Response
     * */
    public function generateMassiveDocumentsExecute(Request $request) {
        // first case, upload model file
        if ($request->files->has('documentModelFile') && $request->files->get('documentModelFile')) {
            $file = $request->files->get('documentModelFile');
            $allowed_file_extension = ['odt','odg'];
            $extension = $file->guessExtension();
            if (!in_array($extension, $allowed_file_extension)) {
                $request->getSession()
                        ->getFlashBag()
                        ->add('error', 'flash.fileExtensionNotValid');
            }
            return $this->generateMassiveDocumentFormModelFile($request, $file);
        }
        // second case, use already uploaded file
        if ($request->request->has('uploadedModelFile') && $request->request->get('uploadedModelFile')) {
            $filename = $request->request->get('uploadedModelFile');
            $filePathDir = $this->get('kernel')->getRootDir() . '/../web/uploads/documents/models/'.$this->get('kernel')->getEnvironment();
            $file = new UploadedFile($filePathDir.'/'.$filename, $filename);
            
            return $this->generateMassiveDocumentFormModelFile($request, $file, true);
        }
        // third case, use documentTemplate
        $dateFieldDetails = $request->request->get('dateFieldDetails');
        $documentTemplateId = $request->request->get('documentTemplateId');
        if(!$documentTemplateId) {
            $request->getSession()
                        ->getFlashBag()
                        ->add('error', 'flash.noFileWasGenerated');
            return $this->redirectToRoute('generate_massive_document');
        }
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $slugify = new Slugify();

        $documentTemplate = $em->getRepository('PostparcBundle:DocumentTemplate')->find($documentTemplateId);
        $documents = [];
        $twig = $this->container->get('twig');
        $globals = $twig->getGlobals();
        $availableVariables = $globals['documentTemplate_availableFields'];

        $postRequest = $request->request;
        $deleteDuplicateCoordinnate = false;
        if ($postRequest->has('options') && isset($postRequest->get('options')['deleteDuplicateCoordinnate'])) {
            $deleteDuplicateCoordinnate = true;
        }
        $downloadGeneratedFile = false;
        if ($postRequest->has('options') && isset($postRequest->get('options')['downloadGeneratedFile'])) {
            $downloadGeneratedFile = true;
        }


        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            // persons
            if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
                $persons = $em->getRepository('PostparcBundle:Person')->getListForMassiveDocumentGeneration($selectionData['personIds']);
                $alreadyAdded = [];
                foreach ($persons as $person) {
                    $object = $person['object'];
                    $string = $object->getCoordinateStringForDuplicateSearch();
                    $slug = $slugify->slugify($string);
                    if (!in_array($slug, $alreadyAdded) || (in_array($slug, $alreadyAdded) && !$deleteDuplicateCoordinnate)) {
                        $documents[] = $this->injectValueInDocument($request, $documentTemplate, $person, $availableVariables);
                        $alreadyAdded[] = $slug;
                    }
                }
            }
            // pfos
            if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
                $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForMassiveDocumentGeneration($selectionData['pfoIds']);
                // filtrage pour éviter les doublons
                $alreadyAdded = [];
                foreach ($pfos as $pfo) {
                    $object = $pfo['object'];
                    $string = $object->getCoordinateStringForDuplicateSearch();
                    $slug = $slugify->slugify($string);
                    if (!in_array($slug, $alreadyAdded) || (in_array($slug, $alreadyAdded) && !$deleteDuplicateCoordinnate)) {
                        $documents[] = $this->injectValueInDocument($request, $documentTemplate, $pfo, $availableVariables);
                        $alreadyAdded[] = $slug;
                    }
                }
            }
            // organizations
            if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
                $organizations = $em->getRepository('PostparcBundle:Organization')->getListForMassiveDocumentGeneration($selectionData['organizationIds']);
                $alreadyAdded = [];
                foreach ($organizations as $organization) {
                    $object = $organization['object'];
                    $string = $object->getCoordinateStringForDuplicateSearch();
                    $slug = $slugify->slugify($string);
                    if (!in_array($slug, $alreadyAdded) || (in_array($slug, $alreadyAdded) && !$deleteDuplicateCoordinnate)) {
                        $documents[] = $this->injectValueInDocument($request, $documentTemplate, $organization, $availableVariables);
                        $alreadyAdded[] = $slug;
                    }
                }
            }

            // representations
            if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
                $representations = $em->getRepository('PostparcBundle:Representation')->getListForSelection(
                        $selectionData['representationIds'], 
                        isset($selectionData['personIds']) ? $selectionData['personIds'] : null, 
                        isset($selectionData['pfoIds']) ? $selectionData['pfoIds'] : null
                    )->getResult();
                foreach ($representations as $representation) {
                    if ($representation->getPerson()) {
                        $persons = $em->getRepository('PostparcBundle:Person')->getListForMassiveDocumentGeneration([$representation->getPerson()->getId()]);
                        $alreadyAdded = [];
                        foreach ($persons as $person) {
                            $object = $person['object'];
                            $string = $object->getCoordinateStringForDuplicateSearch();
                            $slug = $slugify->slugify($string);
                            if (!in_array($slug, $alreadyAdded) || (in_array($slug, $alreadyAdded) && !$deleteDuplicateCoordinnate)) {
                                $documents[] = $this->injectValueInDocument($request, $documentTemplate, $person, $availableVariables);
                                $alreadyAdded[] = $slug;
                            }
                        }
                    } elseif ($representation->getPfo()) {
                        $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForMassiveDocumentGeneration([$representation->getPfo()->getId()]);
                        $alreadyAdded = [];
                        foreach ($pfos as $pfo) {
                            $object = $pfo['object'];
                            $string = $object->getCoordinateStringForDuplicateSearch();
                            $slug = $slugify->slugify($string);
                            if (!in_array($slug, $alreadyAdded) || (in_array($slug, $alreadyAdded) && !$deleteDuplicateCoordinnate)) {
                                $documents[] = $this->injectValueInDocument($request, $documentTemplate, $pfo, $availableVariables);
                                $alreadyAdded[] = $slug;
                            }
                        }
                    }
                }
            }
        }

        return $this->generateMassiveDocument($documents, $documentTemplate, $dateFieldDetails, $deleteDuplicateCoordinnate, $downloadGeneratedFile);
    }

    /**
     * generate massive document form a model file send via form.
     *
     * @param Request      $request
     * @param UploadedFile $file
     *
     * @return type
     */
    private function generateMassiveDocumentFormModelFile(Request $request, UploadedFile $file, $useUploadedModelFile = false) {
        $final_Extension = $request->request->get('exportFormat');
        $kernel = $this->get('kernel');
        $filePathDir = $kernel->getRootDir() . '/../web/uploads/documents/models/'.$kernel->getEnvironment().'/';
        $phpCliCommand = $this->container->hasParameter('phpCliCommand') ? $this->container->getParameter('phpCliCommand') : 'php';
        // deplacement du fichier dans le dossier
        $fileName = $file->getClientOriginalName();
        $role = $this->getUser()->getRoles()[0];
        if(!$useUploadedModelFile) {
            $file->move($filePathDir, $fileName);
        }
        $session = $request->getSession();
        $selectionData = $session->get('selection');

        
        $command = $phpCliCommand . ' ' . str_replace('app', 'bin', $kernel->getRootDir()) . '/console postparc:generateMassiveDocumentFormModelFile \'' . $filePathDir . '\' \'' . $fileName . '\' \'' . json_encode($selectionData) . '\' \'' . $final_Extension . '\' \'' . $this->getUser()->getEmail() . '\' \'' . $request->getHost() . '\' \'' . $role . '\' --env=' . $kernel->getEnvironment() . ' > /dev/null 2>&1 &';
        //dump($command); die;
        $process = new Process($command);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->run();

        $request->getSession()
                ->getFlashBag()
                ->add('success', 'flash.onemailWasSentWithFinalFile');

        return $this->redirectToRoute('generate_massive_document');
    }

    /**
     * generate Response for one file.
     *
     * @param type $fileName
     *
     * @return Response
     */
    private function downloadFileResponse($fileName) {
        $response = new Response();
        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', mime_content_type($fileName));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($fileName) . '";');
        $response->headers->set('Content-length', filesize($fileName));

        // Send headers before outputting anything
        $response->sendHeaders();

        $response->setContent(readfile($fileName));

        return $response;
    }

    /**
     * add element to selection via ajax.
     *
     * @Route("/ajax-addToSelection", name="ajax_addToSelection", options={"expose"=true}, methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     * */
    public function ajaxAddtoSelectionAction(Request $request) {
        $session = $request->getSession();
        $selectionData = [];
        $alreadyExist = false;
        $id = $request->query->get('id');
        $type = $request->query->get('type');

        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            if (array_key_exists($type . 'Ids', $selectionData)) {
                if (!in_array($id, $selectionData[$type . 'Ids'])) {
                    $selectionData[$type . 'Ids'][] = $id;
                } else {
                    $alreadyExist = true;
                }
            } else {
                $selectionData[$type . 'Ids'] = [$id];
            }
        } else {
            $selectionData[$type . 'Ids'] = [$id];
        }

        $session->set('selection', $selectionData);

        if ($alreadyExist) {
            return new Response('alreadyExist', 200);
        } else {
            return new Response($this->getNbElementsInSelection($request), 200);
        }
    }

    /**
     * @param type $pdf
     * @param type $currentXposition
     * @param type $currentYposition
     * @param int  $numEtiquette
     * @param type $nbEtiquetteParLigne
     * @param type $largeurEtiquette
     * @param type $hauteurEtiquette
     * @param type $pdfMarginLeft
     * @param type $pdfMarginTop
     * @param type $pageHeight
     * @param type $retraitHorizontalInterEtiquette
     * @param type $retraitVerticalInterEtiquette
     * @param type $margeHorizontaleInterEtiquette
     * @param type $margeVerticalInterEtiquette
     * @param type $entities
     * @param type $postRequest
     */
    private function printEntitiesSticker(&$pdf, &$currentXposition, &$currentYposition, &$numEtiquette, $nbEtiquetteParLigne, $largeurEtiquette, $hauteurEtiquette, $pdfMarginLeft, $pdfMarginTop, $pageHeight, $retraitHorizontalInterEtiquette, $retraitVerticalInterEtiquette, $margeHorizontaleInterEtiquette, $margeVerticalInterEtiquette, $entities, $postRequest) {
        $currentEntityConfig = $this->get('session')->get('currentEntityConfig');
        $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];
        $user = $this->getUser();
        foreach ($entities as $entity) {
            // echo "current_x_position".$currentXposition."<br/>";// pour debug
            if ($numEtiquette == $nbEtiquetteParLigne) {
                $currentXposition += $hauteurEtiquette;
                $currentYposition = $pdfMarginLeft + $retraitHorizontalInterEtiquette;
                $numEtiquette = 0;
            }
            if (($currentXposition + $hauteurEtiquette) >= $pageHeight) {
                // on déborde sur la page suivante
                $pdf->AddPage();
                $pdf->setY($pdfMarginLeft);
                $pdf->setX($pdfMarginTop);
                $currentYposition = $pdf->getY() + $retraitHorizontalInterEtiquette;
                $currentXposition = $pdf->getX() + $retraitVerticalInterEtiquette;
            }
            //$content = $entity;
            $content = $entity->getPrintForSticker($postRequest, $user, 0, $personnalFieldsRestriction);

            $pdf->writeHTMLCell($largeurEtiquette, $hauteurEtiquette, $currentYposition, $currentXposition, $content, 0, 0, 0);

            $currentYposition += $largeurEtiquette + $margeHorizontaleInterEtiquette;
            ++$numEtiquette;
        }
    }

    /**
     * fonction permettant de mettre en place les entets de colonnes dans le fichier export selection selon l'onglet.
     *
     * @param string  $tab
     * @param inetger $activeSheetNumber
     * @param object  $phpExcelObject
     * @param Request $request
     *
     * */
    private function setExportColsHeader($tab, $activeSheetNumber, $phpExcelObject, $request) {
        $col = 1;
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];
        $translator = $this->get('translator');
        switch ($tab) {
            case 'person':
                $tabFields = $request->request->has('person') ? $request->request->get('person') : [];
                // ajout des coordonnées eventuels
                if ($request->request->has('coordinate') && isset($tabFields['coordinate'])) {
                    unset($tabFields['coordinate']);
                    $coordinateFields = $request->request->get('coordinate');
                    // mise en place restriction accès coordonnées personnelles
                    foreach ($personnalFieldsRestriction as $restriction) {
                        if (array_key_exists($restriction, $coordinateFields)) {
                            unset($coordinateFields[$restriction]);
                        }
                    }
                    $tabFields = array_merge($tabFields, $coordinateFields);
                }
                foreach ($tabFields as $field) {
                    $field = $translator->trans('Pfo.field.person') . '_' . $field;
                    $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, 1, $field);
                    ++$col;
                }
                break;
            case 'pfo':
                $tabFields = $request->request->has('pfo') ? $request->request->get('pfo') : [];
                // ajout terme personne qualifiée devant chaque champ de $tabFieldPersons
                foreach ($tabFields as $key => $field) {
                    $tabFields[$key] = $translator->trans('Pfo.label') . '_' . $field;
                }
                if ($request->request->has('person') && isset($tabFields['person'])) {
                    unset($tabFields['person']);
                    $tabFieldPersons = $request->request->get('person');

                    // ajout des coordonnées eventuels
                    if (isset($tabFieldPersons['coordinate']) && $request->request->has('coordinate')) {
                        unset($tabFieldPersons['coordinate']);
                        $tabFieldPersons = array_merge($tabFieldPersons, $request->request->get('coordinate'));
                    }
                    // ajout terme Personne devant chaque champ de $tabFieldPersons
                    foreach ($tabFieldPersons as $key => $field) {
                        $tabFieldPersons[$key] = $translator->trans('Pfo.field.person') . '_' . $field;
                    }
                    // ajout infos person en tête de tableau
                    $tabFields = array_merge(array_values($tabFieldPersons), $tabFields);
                }
                if (isset($tabFields['preferedCoordinateAddress'])) {
                    unset($tabFields['preferedCoordinateAddress']);
                    $tabFields = array_merge($tabFields, array_values($request->request->get('coordinate')));
                }

                if ($request->request->has('organization') && isset($tabFields['organization'])) {
                    unset($tabFields['organization']);
                    $tabFieldOrganizations = $request->request->get('organization');

                    // ajout des coordonnées eventuels
                    if (isset($tabFieldOrganizations['coordinate']) && $request->request->has('coordinate')) {
                        unset($tabFieldOrganizations['coordinate']);
                        $tabFieldOrganizations = array_merge($tabFieldOrganizations, $request->request->get('coordinate'));
                    }
                    // ajout terme Organisme devant chaque champ de $tabFieldOrganizations
                    foreach ($tabFieldOrganizations as $key => $field) {
                        $tabFieldOrganizations[$key] = $translator->trans('Pfo.field.organization') . '_' . $field;
                    }
                    // ajout infos organization en fin de tableau
                    $tabFields = array_merge($tabFields, array_values($tabFieldOrganizations));
                }

                foreach ($tabFields as $field) {
                    $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, 1, $field);
                    ++$col;
                }
                break;
            case 'organization':
                $tabFields = $request->request->has('organization') ? $request->request->get('organization') : [];
                // ajout des coordonnées eventuels
                if (isset($tabFields['coordinate']) && $request->request->has('coordinate')) {
                    unset($tabFields['coordinate']);
                    $tabFields = array_merge($tabFields, $request->request->get('coordinate'));
                }
                foreach ($tabFields as $field) {
                    $field = $translator->trans('Pfo.field.organization') . '_' . $field;
                    $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, 1, $field);
                    ++$col;
                }
                break;
            case 'representation':
                $tabFields = $request->request->has('representation') ? $request->request->get('representation') : [];

                if ($request->request->has('person') && isset($tabFields['person'])) {
                    unset($tabFields['person']);
                    $tabFieldPersons = $request->request->get('person');
                    // ajout des coordonnées eventuels
                    if (isset($tabFieldPersons['coordinate']) && $request->request->has('coordinate')) {
                        unset($tabFieldPersons['coordinate']);
                        $tabFieldPersons = array_merge($tabFieldPersons, $request->request->get('coordinate'));
                    }
                    // ajout terme Personne devant chaque champ de $tabFieldPersons
                    foreach ($tabFieldPersons as $key => $field) {
                        $tabFieldPersons[$key] = $translator->trans('Pfo.field.person') . '_' . $field;
                    }
                    // ajout infos person en tête de tableau
                    $tabFields = array_merge(array_values($tabFieldPersons), $tabFields);
                }
                if (isset($tabFields['specificCoordinate'])) {
                    unset($tabFields['specificCoordinate']);
                    $tabFields = array_merge($tabFields, array_values($request->request->get('coordinate')));
                }

                if ($request->request->has('organization') && isset($tabFields['organization'])) {
                    unset($tabFields['organization']);
                    $tabFieldOrganizations = $request->request->get('organization');
                    // ajout des coordonnées eventuels
                    if (isset($tabFieldOrganizations['coordinate']) && $request->request->has('coordinate')) {
                        unset($tabFieldOrganizations['coordinate']);
                        $tabFieldOrganizations = array_merge($tabFieldOrganizations, $request->request->get('coordinate'));
                    }
                    // ajout terme Organisme devant chaque champ de $tabFieldOrganizations
                    foreach ($tabFieldOrganizations as $key => $field) {
                        $tabFieldOrganizations[$key] = $translator->trans('Pfo.field.organization') . '_' . $field;
                    }
                    // ajout infos organization en fin de tableau
                    $tabFields = array_merge($tabFields, array_values($tabFieldOrganizations));
                }

                foreach ($tabFields as $field) {
                    $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, 1, $field);
                    ++$col;
                }
                break;
        }
    }

    /**
     * insert persons infos in phpExcelObject.
     *
     * @param collection $persons
     * @param int        $activeSheetNumber
     * @param object     $phpExcelObject
     * @param Request    $request
     *
     * @return Response
     * */
    private function insertPersonsInfos($persons, $activeSheetNumber, $phpExcelObject, $request) {
        $row = 2;

        foreach ($persons as $person) {
            $col = 1;
            $this->insertPersonInfos($row, $col, $person, $activeSheetNumber, $phpExcelObject, $request);
            ++$row;
        }
    }

    /**
     * * Insert person info in export file.
     *
     * @param int     $row
     * @param int     $col
     * @param object  $person
     * @param int     $activeSheetNumber
     * @param object  $phpExcelObject
     * @param Request $request
     *
     * @return int $col
     * */
    private function insertPersonInfos($row, $col, Person $person, $activeSheetNumber, $phpExcelObject, $request, $pfo = null) {
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];
        $tabFields = $request->request->has('person') ? $request->request->get('person') : [];

        $user = $this->getUser();
        if (isset($tabFields['civility'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $person->getCivility());
            ++$col;
        }
        if (isset($tabFields['lastName'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $person->getName());
            ++$col;
        }
        if (isset($tabFields['firstName'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $person->getFirstName());
            ++$col;
        }
        if (isset($tabFields['birthDate'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $person->getBirthDate() !== null ? $person->getBirthDate()->format('d-m-Y') : '');
            ++$col;
        }
        if (isset($tabFields['birthLocation'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $person->getBirthLocation());
            ++$col;
        }
        if (isset($tabFields['profession'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $person->getProfession());
            ++$col;
        }
        if (isset($tabFields['tags'])) {
            $tags = $person->getTags();
            $tagsInfos = '';
            $separator = '';
            foreach ($tags as $tag) {
                $tagsInfos .= $separator . $tag;
                $separator = '|';
            }
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $tagsInfos);
            ++$col;
        }
        if (isset($tabFields['observation'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $person->getObservation());
            ++$col;
        }
        if (isset($tabFields['nbMinorChildreen'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $person->getNbMinorChildreen());
            ++$col;
        }
        if (isset($tabFields['nbMajorChildreen'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $person->getNbMajorChildreen());
            ++$col;
        }
        
        if (isset($tabFields['pfoPersonGroups'])) {
            $personGroups = $person->getPfoPersonGroups();
            $personGroupsInfos = '';
            $separator = '';
            foreach ($personGroups as $personeGroup) {
                /*@var $group PfoPersonGroup */
                $personGroupsInfos .= $separator . $personeGroup->getGroup();
                $separator = '|';
            }
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $personGroupsInfos);
            ++$col;
        }       
        
        if (isset($tabFields['organizations'])) {
            $personPfos = $person->getPfos();
            
            $personOrganizationsInfos = '';
            $separator = '';
            foreach ($personPfos as $pfo) {
                /*@var $pfo Pfo */
                if($pfo->getOrganization()) {
                    $personOrganizationsInfos .= $separator . $pfo->getOrganization()->getName();
                    $separator = '|';
                }
            }
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $personOrganizationsInfos);
            ++$col;
        }          
            
        if (isset($tabFields['coordinate'])) {
            $em = $this->getDoctrine()->getManager();
            // get override coordinate for all elements in selection
            $coordinateOverrideInfos = $request->request->has('coordinateOverrideInfos') ? $request->request->get('coordinateOverrideInfos') : [];
            $coordinate = null;
            if ($pfo && $pfo->getPreferedCoordinateAddress()) {
                $coordinate = $pfo->getPreferedCoordinateAddress();
                if (array_key_exists('pfos', $coordinateOverrideInfos) && array_key_exists($pfo->getId(), $coordinateOverrideInfos['pfos']) && (!$coordinate || $coordinateOverrideInfos['pfos'][$pfo->getId()] != $coordinate->getId())) {
                    $coordinate = $em->getRepository('PostparcBundle:Coordinate')->find($coordinateOverrideInfos[pfos][$pfo->getId()]);
                }
            } elseif ($person) {
                if (($user->hasRole('ROLE_CONTRIBUTOR') || $user->hasRole('ROLE_CONTRIBUTOR_PLUS') || $user->hasRole('ROLE_ADMIN')) || !$person->getDontShowCoordinateForReaders()) {
                    $coordinate = $person->getCoordinate();
                    if (array_key_exists('persons', $coordinateOverrideInfos) && array_key_exists($person->getId(), $coordinateOverrideInfos['persons']) && (!$coordinate || $coordinateOverrideInfos['persons'][$person->getId()] != $coordinate->getId())) {
                        $coordinate = $em->getRepository('PostparcBundle:Coordinate')->find($coordinateOverrideInfos['persons'][$person->getId()]);
                    }
                }
            }
            if ($coordinate) {
                $col = $this->insertCoordinateInfos($row, $col, $activeSheetNumber, $phpExcelObject, $coordinate, $request, $personnalFieldsRestriction);
            } else {
                $coordinateFields = $request->request->get('coordinate');
                // mise en place restriction accès coordonnées personnelles
                if (is_array($personnalFieldsRestriction)) {
                    foreach ($personnalFieldsRestriction as $restriction) {
                        if (array_key_exists($restriction, $coordinateFields)) {
                            unset($coordinateFields[$restriction]);
                        }
                    }
                }
                $col += count($coordinateFields);
            }
     
        }
        
        return $col;
    }

    /**
     * insert organizations infos in phpExcelObject.
     *
     * @param collection $organizations
     * @param int        $activeSheetNumber
     * @param object     $phpExcelObject
     * @param Request    $request
     * */
    private function insertOrganizationsInfos($organizations, $activeSheetNumber, $phpExcelObject, $request) {
        $row = 2;

        foreach ($organizations as $organization) {
            $col = 1;
            $this->insertOrganizationInfos($row, $col, $organization, $activeSheetNumber, $phpExcelObject, $request);
            ++$row;
        }
    }

    /**
     * * insert organization infos in export file.
     *
     * @param int integer $row
     * @param int integer $col
     * @param object      $organization
     * @param int         $activeSheetNumber
     * @param object      $phpExcelObject
     * @param Request     $request
     * @param Coordinate  $coordinate
     *
     * @return int $col
     * */
    private function insertOrganizationInfos($row, $col, Organization $organization, $activeSheetNumber, $phpExcelObject, $request, $coordinate = null) {
        $tabFields = $request->request->has('organization') ? $request->request->get('organization') : [];

        if (isset($tabFields['name'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, trim($organization->getName()));
            ++$col;
        }
        if (isset($tabFields['abbreviation'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $organization->getAbbreviation());
            ++$col;
        }
        if (isset($tabFields['organizationType'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $organization->getOrganizationType());
            ++$col;
        }
        if (isset($tabFields['observation'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $organization->getObservation());
            ++$col;
        }
        
        if (isset($tabFields['tags'])) {
            $tags = $organization->getTags();
            $tagsInfos = '';
            $separator = '';
            foreach ($tags as $tag) {
                $tagsInfos .= $separator . $tag;
                $separator = '|';
            }
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $tagsInfos);
            ++$col;
        }

        if (isset($tabFields['pfoPersonGroups'])) {
            $groups = $organization->getGroups();
//            dump($groups);die;
            $groupsInfos = '';
            $separator = '';
            foreach ($groups as $group) {
                $groupsInfos .= $separator . $group;
                $separator = '|';
            }
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $groupsInfos);
            ++$col;
        }        
        
        if (isset($tabFields['coordinate'])) {
            if ($coordinate === null) {
                $coordinate = $organization->getCoordinate();
            }
            if ($coordinate) {
                $em = $this->getDoctrine()->getManager();
                // get override coordinate for all elements in selection
                $coordinateOverrideInfos = $request->request->has('coordinateOverrideInfos') ? $request->request->get('coordinateOverrideInfos') : [];
                if (array_key_exists('organizations', $coordinateOverrideInfos) && array_key_exists($organization->getId(), $coordinateOverrideInfos['organizations']) && (!$coordinate || $coordinateOverrideInfos['organizations'][$organization->getId()] != $coordinate->getId())) {
                    $coordinate = $em->getRepository('PostparcBundle:Coordinate')->find($coordinateOverrideInfos['organizations'][$organization->getId()]);
                }
                $col = $this->insertCoordinateInfos($row, $col, $activeSheetNumber, $phpExcelObject, $coordinate, $request);
            }
        }

        return $col;
    }

    /**
     * insert pfos infos in phpExcelObject.
     *
     * @param collection $pfos
     * @param int        $activeSheetNumber
     * @param object     $phpExcelObject
     * @param Request    $request
     * */
    private function insertPfosInfos($pfos, $activeSheetNumber, $phpExcelObject, $request) {
        $row = 2;
        $tabFields = $request->request->has('pfo') ? $request->request->get('pfo') : [];
//dump($request->request->get('person'));die;
        foreach ($pfos as $pfo) {
            $col = 1;
            if (isset($tabFields['person'])) {
                $person = $pfo->getPerson();
                if ($person) {
                    $col = $this->insertPersonInfos($row, $col, $person, $activeSheetNumber, $phpExcelObject, $request);
                } else {
                    $col = count(is_array($request->request->get('person')) ? $request->request->get('person') : []) + count(is_array($request->request->get('coordinate')) ? $request->request->get('coordinate') : []) - 1;
                }
            }


           
            if (isset($tabFields['particleFunction'])) {
                $particleFunction = '';
                if ($pfo->getPersonFunction()) {
                    $particleFunction = $pfo->getPersonFunction()->getMenParticle();
                    if ($pfo->getPerson() && 'female' == $pfo->getPerson()->getSexe()) {
                        $particleFunction = $pfo->getPersonFunction()->getWomenParticle();
                    }
                }
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $particleFunction);
                ++$col;
            }
            if (isset($tabFields['function'])) {
                $function = '';
                if ($pfo->getPersonFunction()) {
                    $function = $pfo->getPersonFunction()->getName();
                    if ($pfo->getPerson() && 'female' == $pfo->getPerson()->getSexe() && $pfo->getPersonFunction()->getWomenName()) {
                        $function = $pfo->getPersonFunction()->getWomenName();
                    }
                }
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, trim($function));
                ++$col;
            }
            if (isset($tabFields['additionalFunction'])) {
                $additionalFunction = '';
                if ($pfo->getAdditionalFunction()) {
                    $additionalFunction = $pfo->getAdditionalFunction()->getName();
                    if ($pfo->getPerson() && 'female' == $pfo->getPerson()->getSexe() && $pfo->getAdditionalFunction()->getWomenName()) {
                        $additionalFunction = $pfo->getAdditionalFunction()->getWomenName();
                    }
                }
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, trim($additionalFunction));
                ++$col;
            }
            if (isset($tabFields['service'])) {
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $pfo->getService());
                ++$col;
            }

            if (isset($tabFields['observation'])) {
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $pfo->getObservation());
                ++$col;
            }
            if (isset($tabFields['preferedEmails'])) {
                $preferedEmails = $pfo->getPreferedEmails();
                $preferedEmailsString = '';
                $separator = '';
                foreach ($preferedEmails as $email) {
                    $preferedEmailsString .= $separator . $email;
                    $separator = ';';
                }
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $preferedEmailsString);
                ++$col;
            }
            
            if (isset($tabFields['pfoPersonGroups'])) {
                /*@var $pfo Pfo */
                $pfoPersonGroups = $pfo->getPfoPersonGroups();
                $pfoPersonGroupsString = '';
                $separator = '';
                foreach ($pfoPersonGroups as $pfoPersonGroup) {
                    /*@var $pfoPersonGroup PfoPersonGroup */
                    if ($pfoPersonGroup->getGroup() && !$pfoPersonGroup->getGroup()->getDeletedAt() 
                            &&
                            (
                            $this->getUser()->hasRole('ROLE_SUPER_ADMIN')
                            || $request->getSession()->get('currentEntityId') == $pfoPersonGroup->getGroup()->getEntity()->getId()
                            ||
                            ($request->getSession()->get('currentEntityId') != $pfoPersonGroup->getGroup()->getEntity()->getId() && $pfoPersonGroup->getGroup()->getIsShared())
                            )
                            ){
                    $pfoPersonGroupsString .= $separator . $pfoPersonGroup->getGroup();
                    $separator = ' | ';                          
                    }
 
                }
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $pfoPersonGroupsString);
                ++$col;
            }
            
            
            if (isset($tabFields['connectingCity'])) {
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $pfo->getConnectingCity());
                ++$col;
            }
            if (isset($tabFields['phone'])) {
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $pfo->getPhone());
                ++$col;
            }
            if (isset($tabFields['mobilePhone'])) {
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $pfo->getMobilePhone());
                ++$col;
            }
            if (isset($tabFields['fax'])) {
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $pfo->getFax());
                ++$col;
            }
            if (isset($tabFields['email'])) {
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $pfo->getEmail());
                ++$col;
            }
            if (isset($tabFields['tags'])) {
                $tags = $pfo->getTags();
                $tagsInfos = '';
                $separator = '';
                foreach ($tags as $tag) {
                    $tagsInfos .= $separator . $tag;
                    $separator = '|';
                }
                $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $tagsInfos);
                ++$col;
            }
            if (isset($tabFields['preferedCoordinateAddress'])) {
                $coordinate = $pfo->getPreferedCoordinateAddress() ? $pfo->getPreferedCoordinateAddress() : null;
                $em = $this->getDoctrine()->getManager();
                // get override coordinate for all elements in selection
                $coordinateOverrideInfos = $request->request->has('coordinateOverrideInfos') ? $request->request->get('coordinateOverrideInfos') : [];
                if (array_key_exists('pfos', $coordinateOverrideInfos) && array_key_exists($pfo->getId(), $coordinateOverrideInfos['pfos']) && (!$coordinate || $coordinateOverrideInfos['pfos'][$pfo->getId()] != $coordinate->getId())) {
                    $coordinate = $em->getRepository('PostparcBundle:Coordinate')->find($coordinateOverrideInfos['pfos'][$pfo->getId()]);
                }

                if ($coordinate) {
                    $col = $this->insertCoordinateInfos($row, $col, $activeSheetNumber, $phpExcelObject, $coordinate, $request);
                } else {
                    $col += count($request->request->get('coordinate'));
                }
            }
            
            if (isset($tabFields['organization'])) {
                $organization = $pfo->getOrganization();
                if ($organization) {
                    $coordinate = $organization->getCoordinate();

                    // gestion option Utiliser l'adresse préférée pour l'organisme
                    if ($request->request->has('options') && isset($request->request->get('options')['use_prefered_address'])) {
                        $coordinate = $pfo->getPreferedCoordinateAddress() ? $pfo->getPreferedCoordinateAddress() : null;
                    }
                    $col = $this->insertOrganizationInfos($row, $col, $organization, $activeSheetNumber, $phpExcelObject, $request, $coordinate);
                }
                $col++;
            }            

            ++$row;
        }
    }

    /**
     * * insert coordinate infos in export file.
     *
     * @param int integer $row
     * @param int integer $col
     * @param int         $activeSheetNumber
     * @param object      $phpExcelObject
     * @param Coordinate  $coordinate
     * @param Request     $request
     * @param array       $personnalFieldsRestriction
     *
     * @return int $col
     */
    private function insertCoordinateInfos($row, $col, $activeSheetNumber, $phpExcelObject, Coordinate $coordinate, $request, $personnalFieldsRestriction = []) {
        $tabFields = $request->request->has('coordinate') ? $request->request->get('coordinate') : [];
        // mise en place restriction accès coordonnées personnelles
        if (is_array($personnalFieldsRestriction)) {
            foreach ($personnalFieldsRestriction as $restriction) {
                if (array_key_exists($restriction, $tabFields)) {
                    unset($tabFields[$restriction]);
                }
            }
        }
        if (isset($tabFields['addressLine1'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getAddressLine1() : '');
            ++$col;
        }
        if (isset($tabFields['addressLine2'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getAddressLine2() : '');
            ++$col;
        }
        if (isset($tabFields['addressLine3'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getAddressLine3() : '');
            ++$col;
        }
        if (isset($tabFields['zipCode'])) {
            $zipcode = ($coordinate && $coordinate->getCity()) ? $coordinate->getCity()->getZipCode() : '';
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $zipcode);
            ++$col;
        }
        if (isset($tabFields['city'])) {
            $zipcode = ($coordinate && $coordinate->getCity()) ? $coordinate->getCity()->getName() : '';
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $zipcode);
            ++$col;
        }
        if (isset($tabFields['cedex'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getCedex() : '');
            ++$col;
        }
        if (isset($tabFields['phone'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getPhone() : '');
            ++$col;
        }
        if (isset($tabFields['mobilePhone'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getMobilePhone() : '');
            ++$col;
        }
        if (isset($tabFields['fax'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getFax() : '');
            ++$col;
        }
        if (isset($tabFields['email'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getEmail() : '');
            ++$col;
        }
        if (isset($tabFields['webSite'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getWebSite() : '');
            ++$col;
        }
        if (isset($tabFields['coordinate'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $coordinate ? $coordinate->getCoordinate() : '');
            ++$col;
        }

        return $col;
    }

    /**
     * insert representations infos in phpExcelObject.
     *
     * @param collection $representations
     * @param int        $activeSheetNumber
     * @param object     $phpExcelObject
     * @param Request    $request
     * */
    private function insertRepresentationsInfos($representations, $activeSheetNumber, $phpExcelObject, $request) {
        $row = 2;

        foreach ($representations as $representation) {
            $col = 1;
            $this->insertRepresentationInfos($row, $col, $activeSheetNumber, $phpExcelObject, $request, $representation);
            ++$row;
        }
    }

    /**
     * * insert representation infos in export file.
     *
     * @param int integer    $row
     * @param int integer    $col
     * @param int            $activeSheetNumber
     * @param object         $phpExcelObject
     * @param Request        $request
     * @param Representation $representation
     *
     * @return int $col
     */
    private function insertRepresentationInfos($row, $col, $activeSheetNumber, $phpExcelObject, $request, Representation $representation) {
        $translator = $this->get('translator');

        $tabFields = $request->request->has('representation') ? $request->request->get('representation') : [];

        // person
        if (isset($tabFields['person'])) {
            $pfo = null;
            if ($representation->getPfo() !== null) {
                $pfo = $representation->getPfo();
                $person = $representation->getPfo()->getPerson();
            } else {
                $person = $representation->getPerson();
            }
            if ($person !== null) {
                $col = $this->insertPersonInfos($row, $col, $person, $activeSheetNumber, $phpExcelObject, $request, $pfo);
            } else {
                $col += count($request->request->get('person')) + count($request->request->get('coordinate')) - 1;
            }
        }

        // name
        if (isset($tabFields['name'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getName());
            ++$col;
        }
        // elected
        if (isset($tabFields['elected'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $translator->trans($representation->getElected()));
            ++$col;
        }
        // beginDate
        if (isset($tabFields['beginDate'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getBeginDate() ? $representation->getBeginDate()->format('d/m/Y') : '');
            ++$col;
        }
        // mandatDuration
        if (isset($tabFields['mandatDuration'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getMandatDuration());
            ++$col;
        }
        // estimatedTime
        if (isset($tabFields['estimatedTime'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getEstimatedTime());
            ++$col;
        }
        // estimatedCost
        if (isset($tabFields['estimatedCost'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getEstimatedCost());
            ++$col;
        }
        // periodicity
        if (isset($tabFields['periodicity'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getPeriodicity());
            ++$col;
        }
        // mandateType
        if (isset($tabFields['mandateType'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getMandateType());
            ++$col;
        }
        // service
        if (isset($tabFields['service'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getService());
            ++$col;
        }
        // service
        if (isset($tabFields['personFunction'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getPersonFunction());
            ++$col;
        }
        // natureOfRepresentation
        if (isset($tabFields['natureOfRepresentation'])) {
            $phpExcelObject->setActiveSheetIndex($activeSheetNumber)->setCellValueByColumnAndRow($col, $row, $representation->getNatureOfRepresentation());
            ++$col;
        }
        // specificCoordinate
        if (isset($tabFields['specificCoordinate'])) {
            if ($representation->getCoordinateObject() && $representation->getCoordinateObject()->getCity()) {
                $coordinate = $representation->getCoordinateObject();
                $em = $this->getDoctrine()->getManager();
                // get override coordinate for all elements in selection
                $coordinateOverrideInfos = $request->request->has('coordinateOverrideInfos') ? $request->request->get('coordinateOverrideInfos') : [];
                if (array_key_exists('representations', $coordinateOverrideInfos) && array_key_exists($representation->getId(), $coordinateOverrideInfos['representations']) && (!$coordinate || $coordinateOverrideInfos['representations'][$representation->getId()] != $coordinate->getId())) {
                    $coordinate = $em->getRepository('PostparcBundle:Coordinate')->find($coordinateOverrideInfos['representations'][$representation->getId()]);
                }
                if ($coordinate) {
                    $col = $this->insertCoordinateInfos($row, $col, $activeSheetNumber, $phpExcelObject, $coordinate, $request);
                } else {
                    $col += count($request->request->get('coordinate'));
                }
            } else {
                $col += count($request->request->get('coordinate'));
            }
        }
        // organization
        if (isset($tabFields['organization']) && $representation->getOrganization()) {
            $col = $this->insertOrganizationInfos($row, $col, $representation->getOrganization(), $activeSheetNumber, $phpExcelObject, $request);
        }
    }

    /**
     * @param object $documentTemplate
     * @param array  $scalar
     * @param array  $availableVariables
     * @param object $representation
     *
     * @return type
     */
    private function injectValueInDocument($request, $documentTemplate, $scalar, $availableVariables, $representation = null) {
        $subject = $documentTemplate ? $documentTemplate->getSubject() : '';
        $body = $documentTemplate ? $documentTemplate->getBody() : '';
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];

        // traitement particulier dans le cas d'une representation
        if ($representation) {
            if ($representation->getOrganization()) {
                $subject = str_replace('[[o_name]]', $representation->getOrganization(), $subject);
                $body = str_replace('[[o_name]]', $representation->getOrganization(), $body);
            }
            if ($representation->getPersonFunction()) {
                $personFunction = $representation->getPersonFunction();
                if ('female' == $representation->getSexe()) {
                    $personFunctionString = $personFunction->getWomenParticle() . ' ' . $personFunction->getWomenName();
                } else {
                    $personFunctionString = $personFunction->getMenParticle() . ' ' . $personFunction->getName();
                }
                $subject = str_replace('[[rep_function]]', $personFunctionString, $subject);
                $body = str_replace('[[rep_function]]', $personFunctionString, $body);
            }
            if ($representation->getMandateType()) {
                $subject = str_replace('[[mt_name]]', $representation->getMandateType(), $subject);
                $body = str_replace('[[mt_name]]', $representation->getMandateType(), $body);
            }
            if ($representation->getCoordinate()) {
                $subject = str_replace('[[coord_bloc]]', $representation->getCoordinate()->getFormatedAddress(), $subject);
                $body = str_replace('[[coord_bloc]]', $representation->getCoordinate()->getFormatedAddress(), $body);
            }
        }

        // remplacement bloc coordinate
        $object = null;
        if (array_key_exists('object', $scalar) && method_exists($scalar['object'], 'getCoordinate') && $scalar['object']->getCoordinate()) {
            $object = $scalar['object'];
            $subject = str_replace('[[coord_bloc]]', $scalar['object']->getCoordinate()->getFormatedAddress($personnalFieldsRestriction), $subject);
            $body = str_replace('[[coord_bloc]]', $scalar['object']->getCoordinate()->getFormatedAddress($personnalFieldsRestriction), $body);
        }

        foreach ($availableVariables as $documentVariable) {
            if (isset($scalar[$documentVariable])) {
                $val = $scalar[$documentVariable];
                $subject = str_replace('[[' . $documentVariable . ']]', $val, $subject);
                $body = str_replace('[[' . $documentVariable . ']]', $val, $body);
            }
        }

        // nettoyage des variables non remplacées
        foreach ($availableVariables as $documentVariable) {
            $subject = str_replace('[[' . $documentVariable . ']]', '', $subject);
            $body = str_replace('[[' . $documentVariable . ']]', '', $body);
        }

        // mise en place bloc coordonnées en fonction des options
        $user = $this->getUser();
        if ($representation) {
            $coordinate = $representation->getPrintForSticker($request->request, $user);
        } elseif ('Person' == $scalar['object']->getClassName()) {
            $coordinate = $scalar['object']->getPrintForSticker($request->request, $user, 0, $personnalFieldsRestriction);
        } else {
            $coordinate = $scalar['object']->getPrintForSticker($request->request, $user);
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'coordinate' => $coordinate,
            'object' => $object
        ];
    }

    /**
     * @param array $scalar
     *
     * @return string
     */
    private function generateBlocCoordinateForDocument($scalar) {
        // gestion bloc coodinate
        $coord = '';
        $separator = '';

        if (isset($scalar['o_name']) && '' != $scalar['o_name']) {
            $coord .= $separator . $scalar['o_name'];
            $separator = '<br/>';
        }
        if (isset($scalar['p_name']) && '' != $scalar['p_name']) {
            $coord .= $separator . $scalar['p_name'];
            if (isset($scalar['p_firstName']) && '' != $scalar['p_firstName']) {
                $coord .= ' ' . $scalar['p_firstName'];
            }
            $separator = '<br/>';
        }
        if (isset($scalar['addressLine1']) && '' != $scalar['addressLine1']) {
            $coord .= $separator . $scalar['addressLine1'];
            $separator = '<br/>';
        }
        if (isset($scalar['addressLine2']) && '' != $scalar['addressLine2']) {
            $coord .= $separator . $scalar['addressLine2'];
            $separator = '<br/>';
        }
        if (isset($scalar['addressLine3']) && '' != $scalar['addressLine3']) {
            $coord .= $separator . $scalar['addressLine3'];
            $separator = '<br/>';
        }
        if (isset($scalar['zipcode'])) {
            $coord .= $separator . $scalar['zipcode'] . ' ' . $scalar['cityName'] . ' ' . $scalar['cedex'];
        }

        return $coord;
    }

    /**
     * @param collecton $documents
     * @param object    $documentTemplate
     *
     * @return Response
     */
    private function generateMassiveDocument($documents, $documentTemplate, $dateFieldDetails = null, $deleteDuplicateCoordinnate = true, $downloadGeneratedFile = false) {
        $generalDocumentParameters = $this->container->getParameter('document');
        if (!$generalDocumentParameters) {
            $generalDocumentParameters['marginTop'] = 10;
            $generalDocumentParameters['marginBottom'] = 5;
            $generalDocumentParameters['marginLeft'] = 5;
            $generalDocumentParameters['marginRight'] = 10;
        }
        $marginTop = ($documentTemplate && $documentTemplate->getMarginTop()) ? $documentTemplate->getMarginTop() : $generalDocumentParameters['marginTop'];
        $marginBottom = ($documentTemplate && $documentTemplate->getMarginBottom()) ? $documentTemplate->getMarginBottom() : $generalDocumentParameters['marginBottom'];
        $marginLeft = ($documentTemplate && $documentTemplate->getMarginLeft()) ? $documentTemplate->getMarginLeft() : $generalDocumentParameters['marginLeft'];
        $marginRight = ($documentTemplate && $documentTemplate->getMarginRight()) ? $documentTemplate->getMarginRight() : $generalDocumentParameters['marginRight'];
        $date = new \Datetime();

        // set document information
        $pdfObj = $this->get('white_october.tcpdf')->create();
        $pdfObj->SetCreator(PDF_CREATOR);
        $pdfObj->SetAuthor('Postparc');
        $pdfObj->SetTitle('generation_massive-' . $date->format('d-m-Y'));
        $pdfObj->SetSubject('');
        $pdfObj->SetKeywords('massive');
        // remove default header/footer
        $pdfObj->setPrintHeader(false);
        $pdfObj->setPrintFooter(false);
        $pdfObj->setFooterData([0, 64, 0], [0, 64, 128]);
        $pdfObj->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdfObj->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdfObj->setPageOrientation('P');

        // set default monospaced font
        $pdfObj->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdfObj->SetMargins($marginLeft, $marginTop, $marginRight);

        // set auto page breaks
        $pdfObj->SetAutoPageBreak(true, $marginBottom - 15);

        $pdfObj->SetFont('helvetica', '', 10, '', true);
        $pdfObj->setImageScale(PDF_IMAGE_SCALE_RATIO);
        //$pdfObj->AddPage();
        $pdfObj->setStartingPageNumber(1);

        $coordinateHashs = [];

        foreach ($documents as $document) {
            $coordinate = $document['coordinate'];
            $object = $document['object'];
            $coordinateHash = sha1($coordinate);
            if (!in_array($coordinateHash, $coordinateHashs) || !$deleteDuplicateCoordinnate) {
                $coordinateHashs[] = $coordinateHash;
                $pdfObj->AddPage();
                $pdfObj->SetFont('helvetica', '', 10, '', true);
                $subject = $document['subject'];
                $body = $document['body'];

                $html = $this->renderView('document/document.html.twig', [
                    'subject' => $subject,
                    'body' => $body,
                    'coordinate' => $coordinate,
                    'date' => $dateFieldDetails,
                ]);
                // affichage logo du parc
                if ($documentTemplate && $documentTemplate->getImage() && ($documentTemplate->getPrintImage() || $documentTemplate->getPrintImageAsBackground())) {
                    if ($documentTemplate->getPrintImageAsBackground()) {
                        $pdfObj->Image($documentTemplate->getFullImagePath(), 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
                    } elseif ($documentTemplate->getPrintImage()) {
                        $pdfObj->_header($documentTemplate->getFullImagePath());
                    }
                }
                $pdfObj->writeHTML($html, true, false, true, false, '');
                // affichage pied particulier
                if ($documentTemplate && $documentTemplate->getPrintFooter() && $documentTemplate->getFooter()) {
                    $pdfObj->_footer($documentTemplate->getFooter());
                }
            }
        }
        if (count($documents) > 0) {
            $pdfObj->lastPage();
        }

        if ($downloadGeneratedFile) {
            return new StreamedResponse(function () use ($pdfObj, $date) {
                        $pdfObj->Output('generation_massive-' . $date->format('d-m-Y') . '.pdf', 'D'); // en attachement
                    });
        } else {
            return new StreamedResponse(function () use ($pdfObj, $date) {
                        $pdfObj->Output('generation_massive-' . $date->format('d-m-Y') . '.pdf');
                    });
        }
    }

    /**
     * return total number element in selection.
     *
     * @param Request $request
     *
     * @return int
     * */
    private function getNbElementsInSelection(Request $request) {
        $nbElements = 0;
        $session = $request->getSession();
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');

            // Persons
            if (isset($selectionData['personIds'])) {
                $nbElements += count($selectionData['personIds']);
            }
            // Pfos
            if (isset($selectionData['pfoIds'])) {
                $nbElements += count($selectionData['pfoIds']);
            }
            // organizations
            if (isset($selectionData['organizationIds'])) {
                $nbElements += count($selectionData['organizationIds']);
            }
            // representations
            if (isset($selectionData['representationIds'])) {
                $nbElements += count($selectionData['representationIds']);
            }
        }

        return $nbElements;
    }

}

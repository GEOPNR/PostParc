<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\Person;
use PostparcBundle\Entity\Organization;

/**
 * Duplicate controller.
 *
 * @Route("/admin/duplicate")
 */
class DuplicateController extends Controller
{
    /**
     * Lists all Help entities.
     *
     * @Route("/", name="duplicate_index", methods="GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $duplicatePersons = $em->getRepository('PostparcBundle:Person')->searchDuplicateElements($entityId);
        $duplicateOrganizations = $em->getRepository('PostparcBundle:Organization')->searchDuplicateElements($entityId);
        $duplicatePersonFunctions = $em->getRepository('PostparcBundle:PersonFunction')->searchDuplicateElements();

        return $this->render('duplicate/index.html.twig', [
            'duplicatePersons' => $duplicatePersons,
            'duplicateOrganizations' => $duplicateOrganizations,
            'duplicatePersonFunctions' => $duplicatePersonFunctions,
        ]);
    }


    /**
     *List all elements duplicate with given object.
     *
     * @Route("/{type}/{id}/listDuplicates", name="duplicate_details", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function showDuplicateAction(Request $request, $type, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $class = ucfirst($type);

        if ($request->request->has('masterId')) {
            $masterId = $request->request->get('masterId');
            $master = $em->getRepository('PostparcBundle:' . $class)->find($masterId);
            // récupération des autres éléments identiques
            $duplicates = $em->getRepository('PostparcBundle:' . $class)->getDuplicatesElements($master, $entityId, $masterId);

            $this->cleanDuplicatesElements($master, $duplicates, $type);
            $this->addFlash('success', 'flash.deleteDuplicateElements');

            return $this->redirectToRoute('duplicate_index', ['activTab' => $type]);
        } else {
            // recherche de tout les éléments étant identiques avec le contenus
            // chargement objet:
            $objet = $em->getRepository('PostparcBundle:' . $class)->find($id);
            $duplicates = $em->getRepository('PostparcBundle:' . $class)->getDuplicatesElements($objet, $entityId);

            return $this->render('duplicate/details.html.twig', [
                'duplicates' => $duplicates,
                'duplicateType' => $type,
                'showObjectRoute' => $type . '_show'
            ]);
        }
    }

    private function cleanDuplicatesElements($master, $duplicates, $type)
    {
        $em = $this->getDoctrine()->getManager();
        switch ($type) {
            case 'person':
                foreach ($duplicates as $person) {
                    // $pfos
                    foreach ($person->getPfos() as $pfo) {
                        $pfo->setPerson($master);
                        $em->persist($pfo);
                    }
                    foreach ($person->getPfoPersonGroups() as $pfoPersonGroup) {
                        $pfoPersonGroup->setPerson($master);
                    }
                    foreach ($person->getRepresentations() as $representation) {
                        $representation->setPerson($master);
                        $em->persist($representation);
                    }
                    foreach ($person->getEventPersons() as $eventPerson) {
                        $eventPerson->setPerson($master);
                        $em->persist($eventPerson);
                    }
                    // finaly delete element
                    $em->remove($person);
                }

                $em->flush();
                break;

            case 'organization':
                foreach ($duplicates as $organization) {
                    foreach ($organization->getPfos() as $pfo) {
                        $pfo->setOrganization($master);
                        $em->persist($pfo);
                    }
                    foreach ($organization->getRepresentations() as $representation) {
                        $representation->setOrganization($master);
                        $em->persist($representation);
                    }
                    // finaly delete element
                    $em->remove($organization);
                }
                $em->flush();
                break;

            case 'personFunction':
                foreach ($duplicates as $personFunction) {
                    foreach ($personFunction->getPfos() as $pfo) {
                        $pfo->setPersonFunction($master);
                    }
                    foreach ($personFunction->getRepresentations() as $representation) {
                        $representation->setPersonFunction($master);
                    }
                    // finaly delete element
                    $em->remove($personFunction);
                }
                $em->flush();
                break;
        }
    }
}

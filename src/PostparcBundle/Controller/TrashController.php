<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Entity controller.
 *
 * @Route("/admin/trash")
 */
class TrashController extends Controller
{
    /**
     * Lists all trashed entities.
     *
     * @Route("/", name="trash_index", methods="GET|POST")
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

        // chargement des élements supprimés
        $printFormats = $em->getRepository('PostparcBundle:PrintFormat')->getTrashedElements($entityId);
        $groups = $em->getRepository('PostparcBundle:Group')->getTrashedElements($entityId);
        $pfos = $em->getRepository('PostparcBundle:Pfo')->getTrashedElements($entityId);
        $persons = $em->getRepository('PostparcBundle:Person')->getTrashedElements($entityId);
        $organizations = $em->getRepository('PostparcBundle:Organization')->getTrashedElements($entityId);
        $searchLists = $em->getRepository('PostparcBundle:SearchList')->getTrashedElements($entityId);
        $documentTemplates = $em->getRepository('PostparcBundle:DocumentTemplate')->getTrashedElements($entityId);
        $territories = $em->getRepository('PostparcBundle:Territory')->getTrashedElements();
        $representations = $em->getRepository('PostparcBundle:Representation')->getTrashedElements($entityId);
        $entities = $em->getRepository('PostparcBundle:Entity')->getTrashedElements($entityId);

        $trashed_entities = [
           'PrintFormat' => $printFormats,
           'Group' => $groups,
           'Pfo' => $pfos,
           'Person' => $persons,
           'Organization' => $organizations,
           'SearchList' => $searchLists,
           'DocumentTemplate' => $documentTemplates,
           'Territory' => $territories,
           'Representation' => $representations,
           'Entity' => $entities,
        ];
        ksort($trashed_entities);

        $totalElements = 0;
        foreach ($trashed_entities as $entities) {
            $totalElements += count($entities);
        }

        return $this->render('trash/index.html.twig', [
           'trashed_entities' => $trashed_entities,
           'totalElements' => $totalElements,
        ]);
    }

    /**
     * Batch actions.
     *
     * @param Request $request
     * @Route("/batch", name="trash_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = $request->request->get('ids');
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();

        $entities = $request->request->all();
        unset($entities['batch_action']);

        if (($entities !== []) > 0) {
            switch ($request->request->get('batch_action')) {
                case 'batchDelete':
                    foreach ($entities as $entityName => $ids) {
                        $em->getRepository('PostparcBundle:' . str_replace('Ids', '', $entityName))->batchDelete($ids, $entityId);
                    }
                    $this->addFlash('success', 'flash.deleteSuccess');
                    break;

                case 'batchRestore':
                    foreach ($entities as $entityName => $ids) {
                        $em->getRepository('PostparcBundle:' . str_replace('Ids', '', $entityName))->batchRestore($ids, $entityId);
                    }
                    $request->getSession()
                        ->getFlashBag()
                        ->add('success', 'batch.batchRestoreSuccess');
                    break;
            }
        }

        return $this->redirectToRoute('trash_index');
    }
}

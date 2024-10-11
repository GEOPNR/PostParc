<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\ReaderLimitation;

/**
 * ReaderLimitation controller.
 *
 * @Route("/admin/readerLimitation")
 */
class ReaderLimitationController extends Controller
{
    /**
     * Creates a new readerLimitation entity.
     *
     * @Route("/edit", name="readerLimitation_manage", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function editAction(Request $request)
    {
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entity = $currentEntityService->getCurrentEntity();
        $em = $this->getDoctrine()->getManager();
        $postRequest = $request->request;

        // recherche readerLimitation exitant
        $readerLimitation = $em->getRepository('PostparcBundle:ReaderLimitation')->findOneBy(['entity' => $entity]);
        if ($readerLimitation === null) {
            $readerLimitation = new ReaderLimitation();
            $readerLimitation->setEntity($entity);
            $em->persist($readerLimitation);
            $em->flush();
        }
        // soummission du formulaire
        if ($request->isMethod('POST')) {
            $limitations = [
                'functionIds' => $postRequest->get('filterFunction'),
                'serviceIds' => $postRequest->get('filterService'),
                'organizationTypeIds' => $postRequest->get('filterOrganizationType'),
                'tagIds' => $postRequest->get('filterTag'),
                'mandateTypeIds' => $postRequest->get('filterMandateType'),
                'function_noLimitation' => $postRequest->has('filterFunction_noLimitation') ? $postRequest->get('filterFunction_noLimitation') : 'off',
                'service_noLimitation' => $postRequest->has('filterService_noLimitation') ? $postRequest->get('filterService_noLimitation') : 'off',
                'organizationType_noLimitation' => $postRequest->has('filterOrganizationType_noLimitation') ? $postRequest->get('filterOrganizationType_noLimitation') : 'off',
                'tag_noLimitation' => $postRequest->has('filterTag_noLimitation') ? $postRequest->get('filterTag_noLimitation') : 'off',
                'mandateType_noLimitation' => $postRequest->has('filterMandateType_noLimitation') ? $postRequest->get('filterMandateType_noLimitation') : 'off',
                ];
            $readerLimitation->setLimitations($limitations);
            $em->persist($readerLimitation);
            $em->flush();
            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.editSuccess');
        }

        // chargement des objets enregistrés eventuels
        $limitations = $readerLimitation->getLimitations();
        $readerLimitationObject = [];
        if (isset($limitations['functionIds']) && count($limitations['functionIds'])) {
            $readerLimitationObject['functions'] = $em->getRepository('PostparcBundle:PersonFunction')->findBy(['id' => $limitations['functionIds']]);
        }
        if (isset($limitations['serviceIds']) && count($limitations['serviceIds'])) {
            $readerLimitationObject['services'] = $em->getRepository('PostparcBundle:Service')->findBy(['id' => $limitations['serviceIds']]);
        }
        if (isset($limitations['organizationTypeIds']) && count($limitations['organizationTypeIds'])) {
            $readerLimitationObject['organizationTypes'] = $em->getRepository('PostparcBundle:OrganizationType')->findBy(['id' => $limitations['organizationTypeIds']]);
        }
        if (isset($limitations['tagIds']) && count($limitations['tagIds'])) {
            $readerLimitationObject['tags'] = $em->getRepository('PostparcBundle:Tag')->findBy(['id' => $limitations['tagIds']]);
        }
        if (isset($limitations['mandateTypeIds']) && count($limitations['mandateTypeIds'])) {
            $readerLimitationObject['mandateTypes'] = $em->getRepository('PostparcBundle:MandateType')->findBy(['id' => $limitations['mandateTypeIds']]);
        }
        if (isset($limitations['function_noLimitation'])) {
            $readerLimitationObject['function_noLimitation'] = $limitations['function_noLimitation'];
        }
        if (isset($limitations['service_noLimitation'])) {
            $readerLimitationObject['service_noLimitation'] = $limitations['service_noLimitation'];
        }
        if (isset($limitations['organizationType_noLimitation'])) {
            $readerLimitationObject['organizationType_noLimitation'] = $limitations['organizationType_noLimitation'];
        }
        if (isset($limitations['tag_noLimitation'])) {
            $readerLimitationObject['tag_noLimitation'] = $limitations['tag_noLimitation'];
        }
        if (isset($limitations['mandateType_noLimitation'])) {
            $readerLimitationObject['mandateType_noLimitation'] = $limitations['mandateType_noLimitation'];
        }

        return $this->render('readerLimitation/edit.html.twig', [
            'readerLimitation' => $readerLimitation,
            'readerLimitationObject' => $readerLimitationObject,
        ]);
    }
}

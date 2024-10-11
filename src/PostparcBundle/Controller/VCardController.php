<?php

namespace PostparcBundle\Controller;

use Cocur\Slugify\Slugify;
use PostparcBundle\Entity\Organization;
use PostparcBundle\Entity\Person;
use PostparcBundle\Entity\Pfo;
use PostparcBundle\Entity\Representation;
use PostparcBundle\Entity\ScanQrcodeStat;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Public controller.
 *
 * @Route("/vCard")
 */
class VCardController extends Controller
{    
    /**
     * @Route("/{className}/{id}/scanQrCode", name="scan_qrCode", methods="GET")
     */
    public function scanQrCode(Request $request) {
        
        $objectId = $request->attributes->get('id');
        $className = $request->attributes->get('className');

        $em = $this->getDoctrine()->getManager();
        $object = $em->getRepository('PostparcBundle:' . $className)->find($objectId);
        
        // add scanStat
        $entityID = $object->getEntity()->getId();
        $scanQrcodeStat = new ScanQrcodeStat();
        $scanQrcodeStat->setEntityID($entityID);
        $scanQrcodeStat->setClassName($className);
        $scanQrcodeStat->setCompleteName($object->__toString());
        $scanQrcodeStat->setObjectId($objectId);
        $em->persist($scanQrcodeStat);
        $em->flush();
        
        // return vcard
        $exportVcardMethod = 'export'.$className.'Vcard';
        
        return $this->$exportVcardMethod($request, $object);
    }
    
    
    /**
     * @Route("/Person/{id}/exportVcard", name="person_exportVcard", methods="GET")
     *
     * @param Person $person
     */
    public function exportPersonVcard(Request $request, Person $person)
    {
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $personnalFieldsRestriction= [];
        if($currentEntityConfig) {
            $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];
        }
        $content = $person->generateVcardContent($personnalFieldsRestriction);

        $response = new Response();
        $response->setContent($content);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/x-vcard');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $person->getSlug() . '.vcf"');
        $response->headers->set('Content-Length', mb_strlen($content, '8bit'));

        return $response;
    }
    
    /**
     * @Route("/Organization/{id}/exportVcard", name="organization_exportVcard", methods="GET")
     *
     * @param Organization $organization
     */
    public function exportOrganizationVcard(Request $request, Organization $organization)
    {
        $content = $organization->generateVcardContent();

        $response = new Response();
        $response->setContent($content);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/x-vcard');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $organization->getSlug() . '.vcf"');
        $response->headers->set('Content-Length', mb_strlen($content, '8bit'));

        return $response;
    }
    
    /**
     * @Route("/Pfo/{id}/exportVcard", name="pfo_exportVcard", methods="GET")
     *
     * @param Pfo $pfo
     */
    public function exportPfoVcard(Request $request, Pfo $pfo)
    {
        // response
        $slugify = new Slugify();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $personnalFieldsRestriction= [];
        if($currentEntityConfig) {
            $personnalFieldsRestriction = $currentEntityConfig['personnalFieldsRestriction'];
        }
        $filename = $slugify->slugify($pfo->__toString());

        $content = $pfo->generateVcardContent($personnalFieldsRestriction);
        $response = new Response();
        $response->setContent($content);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/x-vcard');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.vcf"');
        $response->headers->set('Content-Length', mb_strlen($content, '8bit'));

        return $response;
    }
    
    /**
     * @Route("/Representation/{id}/exportVcard", name="representation_exportVcard", methods="GET|POST")
     *
     * @param $representation
     */
    public function exportRepresentationVcard(Request $request, Representation $representation)
    {
        // response
        $slugify = new Slugify();
        $filename = $slugify->slugify($representation->__toString());

        $content = $representation->generateVcardContent();

        $response = new Response();
        $response->setContent($content);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/x-vcard');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.vcf"');
        $response->headers->set('Content-Length', mb_strlen($content, '8bit'));

        return $response;
    }
}

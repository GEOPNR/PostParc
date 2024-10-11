<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\OrganizationLink;
use PostparcBundle\Entity\Organization;

/**
 * OrganizationLink controller.
 *
 * @Route("/organizationLink")
 */
class OrganizationLinkController extends Controller
{
    /**
     * Deletes a OrganizationLink entity.
     *
     * @Route("/{id}/delete", name="organizationLink_delete", methods="GET")
     *
     * @param OrganizationLink $organizationLink
     * @param Request          $request
     *
     * @return Response
     */
    public function deleteAction(OrganizationLink $organizationLink, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $organizationId = $request->query->get('organizationId');
        $em->remove($organizationLink);
        $em->flush();
        //$backUrl = $this->generateUrl('organization_show', array('id' => $organizationId)) . '#organizationLinks';
        $backUrl = $this->generateUrl('organization_show', ['id' => $organizationId]);
        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirect($backUrl);
    }

    /**
     * add Organization Link entity.
     *
     * @Route("/{id}/add_organizationLink", name="add_organizationLink", methods="POST")
     *
     * @param Organization $organization
     * @param Request      $request
     *
     * @return Response
     */
    public function addOrganizationLinkAction(Organization $organization, Request $request)
    {
        $organizationLink = new OrganizationLink();
        $organizationLink->setOrganizationOrigin($organization);
        $formOrganizationLink = $this->createForm('PostparcBundle\Form\OrganizationLinkType', $organizationLink, [
            'action' => $this->generateUrl('add_organizationLink', ['id' => $organization->getId()]),
            'method' => 'POST',
        ]);
        $formOrganizationLink->handleRequest($request);
        //$backUrl = $this->generateUrl('organization_show', array('id' => $organization->getId())) . '#organizationLinks';
        $backUrl = $this->generateUrl('organization_show', ['id' => $organization->getId()]);
        if ($formOrganizationLink->isSubmitted() && $formOrganizationLink->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($organizationLink);
            $em->flush();
            $this->addFlash('success', 'flash.addSuccess');

            return $this->redirect($backUrl);
        } else {
            $this->addFlash('error', 'flash.addFailure');
        }

        return $this->redirect($backUrl);
    }

    /**
     * delete Link between two Organization.
     *
     * @Route("/{organizationParent}/remove_organizationLink/{organisationLinked}", name="remove_organizationLink", methods="GET")
     *
     * @param Organization $organizationParent
     * @param Organization $organisationLinked
     * @param Request      $request
     *
     * @return Response
     */
    public function deleteOrganizationLinkByOrganiationIDsAction(Organization $organizationParent, Organization $organisationLinked, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $organizationLink = $em->getRepository('PostparcBundle:OrganizationLink')->findOneBy(['organizationOrigin' => $organizationParent, 'organizationLinked' => $organisationLinked]);
        if ($organizationLink !== null) {
            $em->remove($organizationLink);
            $em->flush();
            $this->addFlash('success', 'flash.deleteSuccess');
        }

        return $this->redirect($this->generateUrl('organization_show', ['id' => $organizationParent->getId()]));
    }
}

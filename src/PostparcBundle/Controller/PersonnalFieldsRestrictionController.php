<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\PersonnalFieldsRestriction;

/**
 * PersonnalFieldsRestriction controller.
 *
 * @Route("/admin/personnalFieldsRestriction")
 */
class PersonnalFieldsRestrictionController extends Controller
{
    /**
     * Creates or edit personnalFieldsRestriction entity.
     *
     * @Route("/manage", name="personnalFieldsRestriction_manage", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function manageAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        // recherche personnalFieldsRestriction exitant
        $entity = $this->getUser()->getEntity();
        $personnalFieldsRestriction = $em->getRepository('PostparcBundle:PersonnalFieldsRestriction')->findOneBy(['entity' => $entity]);
        if ($personnalFieldsRestriction === null) {
            $personnalFieldsRestriction = new PersonnalFieldsRestriction();
            $personnalFieldsRestriction->setEntity($entity);
            $em->persist($personnalFieldsRestriction);
            $em->flush();
        }
        if ($request->isMethod('POST')) {
            $postRequest = $request->request;
            $restrictions = $postRequest->get('personnalFieldsRestriction');
            $personnalFieldsRestriction->setRestrictions($restrictions);
            $em->persist($personnalFieldsRestriction);
            $em->flush();

            $currentEntityConfig = $session->get('currentEntityConfig');
            $newRestrictions = [];
            $role = $this->getUser()->getRoles()[0];
            if (is_array($personnalFieldsRestriction->getRestrictions()) && array_key_exists($role, $personnalFieldsRestriction->getRestrictions())) {
                $newRestrictions = $personnalFieldsRestriction->getRestrictions()[$role];
            }
            $currentEntityConfig['personnalFieldsRestriction'] = $newRestrictions;
            $session->set('currentEntityConfig', $currentEntityConfig);

            $this->addFlash('success', 'flash.editSuccess');
        }

        return $this->render('personnalFieldsRestriction/manage.html.twig', [
            'personnalFieldsRestriction' => $personnalFieldsRestriction,
        ]);
    }
}

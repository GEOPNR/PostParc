<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\Pfo;
use PostparcBundle\Entity\PfoPersonGroup;
use PostparcBundle\Entity\Organization;
use PostparcBundle\Entity\Person;
use PostparcBundle\Form\PfoType;
use Cocur\Slugify\Slugify;

/**
 * Pfo controller.
 *
 * @Route("/pfo")
 */
class PfoController extends Controller
{
    /**
     * cas particulier affichage d'une fiche n'ayant pas de personne associée.
     *
     * @Route("/{id}", name="pfo_show", methods="GET", requirements={"id":"\d+"})
     *
     * @param Request $request
     * @param Pfo     $pfo
     *
     * @return Response
     */
    public function showAction(Request $request, Pfo $pfo)
    {
        $checkAccessService = $this->container->get('postparc.check_access');

        if (!$checkAccessService->checkAccess($pfo)) {
            $referer = $request->headers->get('referer');
            $this->addFlash('info', 'flash.accessDenied');

            return $referer ? $this->redirect($referer) : $this->redirectToRoute('homepage');
        }
        $qrCodeService = $this->container->get('postparc_qrCodeService');
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        // gestion lecteur -> recherche si restriction
        $currentReaderLimitationService = $this->container->get('postparc_current_reader_limitations');
        $readerLimitations = $currentReaderLimitationService->getReaderLimitations();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $show_SharedContents = (is_array($currentEntityConfig) && array_key_exists('show_SharedContents', $currentEntityConfig)) ? $currentEntityConfig['show_SharedContents'] : false;
        $person = $pfo->getPerson();
        $em = $this->getDoctrine()->getManager();

        if ($person) {
            $pfos = $em->getRepository('PostparcBundle:Pfo')->getPersonPfos($person->getId(), $entityId, $readerLimitations, $show_SharedContents);
            $personQrCodeInfos = $qrCodeService->generateVcardQrCode($person);
        } else {
            $pfos = [$pfo];
        }
        
        // Generate QRCode for Pfos
        $pfoQrCodeInfos[$pfo->getId()] = $qrCodeService->generateVcardQrCode($pfo);

        return $this->render('person/show.html.twig', [
                'person' => $person,
                'pfos' => $pfos,
                'activePfo' => $pfo->getId(),
                'qrCodeUri' => ($person)?$personQrCodeInfos['uri']:null,
                'pfoQrCodeInfos' => $pfoQrCodeInfos,
        ]);
    }

    /**
     * Displays a form to edit an existing Pfo entity.
     *
     * @Route("/{id}/edit", name="pfo_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param Pfo     $pfo
     *
     * @return Response
     */
    public function editAction(Request $request, Pfo $pfo)
    {
        $lockService = $this->container->get('postparc.lock_service');
        if ($lockService->isLock($pfo, $this->getParameter('maxLockDuration'))) {
            return $this->redirect($this->generateUrl('lock-message', ['className' => $pfo->getClassName(), 'objectId' => $pfo->getId()]));
        }
        $editForm = $this->createForm(PfoType::class, $pfo);
        $editForm->handleRequest($request);
        $origin = $request->query->has('origin') ? $request->query->get('origin') : '';

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            // update name of associate representations
            if ($pfo->getRepresentations()) {
                foreach ($pfo->getRepresentations() as $representation) {
                    $representation->updateName();
                    $em->persist($representation);
                }
            }
            $em->persist($pfo);
            $em->flush();

            $request->getSession()
                 ->getFlashBag()
                 ->add('success', 'flash.updateSuccess');
            $lockService->unlockObject($pfo);

            switch ($origin) {
                case 'organization':
                    return $this->redirectToRoute('organization_show', ['id' => $pfo->getOrganization()->getId()]);
                case 'search':
                    return $this->redirectToRoute('search', []);
                default:
                    return $this->redirectToRoute('pfo_show', ['id' => $pfo->getId()]);
            }
        }

        return $this->render('pfo/edit.html.twig', [
                'pfo' => $pfo,
                'origin' => $origin,
                'edit_form' => $editForm->createView(),
        ]);
    }

    /**
     * Delete a pfo entity.
     *
     * @Route("/{id}/delete", name="pfo_delete", methods="GET")
     *
     * @param Request $request
     * @param Pfo     $pfo
     *
     * @return Response
     */
    public function deleteAction(Request $request, Pfo $pfo)
    {
        $em = $this->getDoctrine()->getManager();
        $organisation = $pfo->getOrganization();
        $person = $pfo->getPerson();
        $currentUser = $this->getUser();

        $ids = [$pfo->getId()];
        $em->getRepository('PostparcBundle:Pfo')->batchDelete($ids, null, $currentUser);
        $request->getSession()
              ->getFlashBag()
              ->add('success', 'flash.deleteSuccess');

        if ($request->query->has('origin')) {
            switch ($request->query->get('origin')) {
                case 'organization':
                    return $this->redirectToRoute('organization_show', ['id' => $organisation->getId()]);
                case 'search':
                    return $this->redirectToRoute('search');
                default:
                    return $this->render('person/show.html.twig', ['person' => $person]);
            }
        }
        if ($person) {
            return $this->redirectToRoute('person_show', ['id' => $person->getId()]);
        }
        if ($organisation) {
            return $this->redirectToRoute('organization_show', ['id' => $organisation->getId()]);
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * add this pfon to the basket.
     *
     * @Route("/{id}/addBasket", name="pfo_addBasket", methods="GET")
     *
     * @param Request $request
     * @param Pfo     $pfo
     *
     * @return Response
     */
    public function addToBasketAction(Request $request, Pfo $pfo)
    {
        $session = $request->getSession();
        $selectionData = [];
        $alreadyExist = false;
        // Get filter from session
        if ($session->has('selection')) {
            $selectionData = $session->get('selection');
            if (array_key_exists('pfoIds', $selectionData)) {
                if (!in_array($pfo->getId(), $selectionData['pfoIds'])) {
                    $selectionData['pfoIds'][] = $pfo->getId();
                } else {
                    $alreadyExist = true;
                }
            } else {
                $selectionData['pfoIds'] = [$pfo->getId()];
            }
        } else {
            $selectionData['pfoIds'] = [$pfo->getId()];
        }

        $session->set('selection', $selectionData);

        if ($alreadyExist) {
            $request->getSession()
                 ->getFlashBag()
                 ->add('error', 'Pfo.actions.addBasket.alreadyPresent');
        } else {
            $request->getSession()
                 ->getFlashBag()
                 ->add('success', 'Pfo.actions.addBasket.success');
        }

        if ($request->query->has('origin')) {
            switch ($request->query->get('origin')) {
                case 'organization':
                    return $this->redirectToRoute('organization_show', ['id' => $pfo->getOrganization()->getId()]);
                case 'search':
                    return $this->redirectToRoute('search', ['activeTab' => 'pfos']);
                case 'event':
                    $eventId = $request->query->get('eventId');

                    return $this->redirectToRoute('event_show', ['id' => $eventId]);
            }
        }

        return $this->redirectToRoute('person_index');
    }

    /**
     * add this pfon to the basket.
     *
     * @Route("/{id}/addGroup/{origin}", name="pfo_addGroup", methods="GET|POST")
     *
     * @param Request $request
     * @param Pfo     $pfo
     * @param string  $origin
     *
     * @return Response
     */
    public function addGroupAction(Request $request, Pfo $pfo)
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->request->has('groupIds')) {
            foreach ($request->request->get('groupIds') as $groupId) {
                $ppg = new PfoPersonGroup();
                $group = $em->getRepository('PostparcBundle:Group')->find($groupId);
                $ppg->setPfo($pfo);
                $ppg->setGroup($group);
                $em->persist($ppg);
            }
            $em->flush();
            $request->getSession()
                 ->getFlashBag()
                 ->add('success', 'Pfo.actions.addGroup.success');
        }
        if ($pfo->getPerson()) {
            return $this->redirect($this->generateUrl('person_show', ['id' => $pfo->getPerson()->getId(),'activSubTab' => 'groupsInfosPfo-' . $pfo->getId()]) . '#pfo-' . $pfo->getId());
        } else {
            return $this->redirectToRoute('pfo_show', ['id' => $pfo->getId()]);
        }
    }

    /**
     * Creates a new pfo entity form organization.
     *
     * @Route("/{id}/newpfoFromOrganization", name="organization_new_pfo", methods="GET|POST")
     *
     * @param Request      $request
     * @param Organization $organization
     *
     * @return Response
     */
    public function newPfoFromOrganizationAction(Request $request, Organization $organization)
    {
        $pfo = new Pfo();
        $pfo->setOrganization($organization);
        // récupération des tags de l'organization pour pre-remplissage
        $tags = $organization->getTags();
        foreach ($tags as $tag) {
            $pfo->addTag($tag);
        }
        $form = $this->createForm(PfoType::class, $pfo);
        $form->remove('organization');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($pfo);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            return $this->redirectToRoute('organization_show', ['id' => $pfo->getOrganization()->getId(), 'activeTab' => 'personnalList']);
        }

        return $this->render('pfo/new.html.twig', [
                'form' => $form->createView(),
                'organization' => $organization,
                'helpId' => 18,
        ]);
    }

    /**
     * Creates a new pfo entity form organization.
     *
     * @Route("/{id}/newPfoFromPerson", name="person_new_pfo", methods="GET|POST")
     *
     * @param Request $request
     * @param Person  $person
     *
     * @return Response
     */
    public function newPfoFromPersonAction(Request $request, Person $person)
    {
        $pfo = new Pfo();
        $pfo->setPerson($person);
        $form = $this->createForm(PfoType::class, $pfo);
        $form->remove('person');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($pfo);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');

            return $this->redirectToRoute('person_show', ['id' => $pfo->getPerson()->getId()]);
        }

        return $this->render('pfo/new.html.twig', [
                'form' => $form->createView(),
                'person' => $person,
                'helpId' => 18,
        ]);
    }

    
}

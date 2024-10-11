<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use PostparcBundle\Entity\Event;
use PostparcBundle\Entity\EventAlert;

/**
 * EventAlert controller.
 *
 * @Route("/eventAlert")
 */
class EventAlertController extends Controller
{
    /**
     * Displays a form to edit an existing EventAlert entity.
     *
     * @param EventAlert $eventAlert
     * @param Request    $request
     * @Route("/{id}/edit", name="eventAlert_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(EventAlert $eventAlert, Request $request)
    {
        $deleteForm = $this->createDeleteForm($eventAlert);
        $editForm = $this->createForm('PostparcBundle\Form\EventAlertType', $eventAlert, [
            'noreplyEmails' => $this->getParameter('noreplyEmails'),
            'event'=>$eventAlert->getEvent()
            ]);
        $editForm->handleRequest($request);
        $em = $this->getDoctrine()->getManager();

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // calcul date et heure effective lancement
            $dateEffective = clone $eventAlert->getEvent()->getDate();
            // construct interval
            $intervalString = 'P';
            $intervalString .= 'H' == $eventAlert->getUnit() ? 'T' : '';
            if ('W' == $eventAlert->getUnit()) { // week
                $intervalString .= ($eventAlert->getGap() * 7) . 'D';
            } else {
                $intervalString .= $eventAlert->getGap() . $eventAlert->getUnit();
            }
            if ('add' == $eventAlert->getDirection()) {
                $dateEffective->add(new \DateInterval($intervalString));
            } else {
                $dateEffective->sub(new \DateInterval($intervalString));
            }
            $eventAlert->setEffectiveDate($dateEffective);
            $em->persist($eventAlert);
            $em->flush();            

            $this->addFlash('success', 'flash.editSuccess');

            return $this->redirectToRoute('event_show', ['id' => $eventAlert->getEvent()->getId(), 'activeTab' => 'eventAlerts']);
        }

        // récupération des modèles de document
        $documentTemplates = $em->getRepository('PostparcBundle:DocumentTemplate')->findBy(['isActive' => 1, 'mailable' => 1, 'deletedAt'=>null], ['name' => 'desc']);

        return $this->render('eventAlert/edit.html.twig', [
            'eventAlert' => $eventAlert,
            'edit_form' => $editForm->createView(),
            'documentTemplates' => $documentTemplates,
            'delete_form' => $deleteForm->createView(),            
        ]);
    }

    /**
     * Deletes a EventAlert entity.
     *
     * @param EventAlert $eventAlert
     * @Route("/{id}/delete", name="eventAlert_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction(EventAlert $eventAlert)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$eventAlert->getId()];
        $eventId = $eventAlert->getEvent()->getId();
        $em->getRepository('PostparcBundle:EventAlert')->batchDelete($ids);

        $this->addFlash('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('event_show', ['id' => $eventId, 'activeTab' => 'eventAlerts']);
    }

    /**
     * batch action for emailAlert.
     *
     * @param Event   $event
     * @param Request $request
     * @Route("/{id}/batch", name="eventAlert_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Event $event, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $eventId = $event->getId();
        $ids = $request->request->get('ids');

        if ($ids && $request->request->get('batch_action') === 'batchDelete') {
            $em = $this->getDoctrine()->getManager();
            $em->getRepository('PostparcBundle:EventAlert')->batchDelete($ids);
            $this->addFlash('success', 'flash.deleteMassiveSuccess');
        }

        return $this->redirectToRoute('event_show', ['id' => $eventId, 'activeTab' => 'eventAlerts']);
    }

    /**
     * @param EventAlert $eventAlert
     * @Route("/{id}/launch_manualy", name="eventAlert_launchManualy")
     *
     * @return Response
     */
    public function launchManualy(EventAlert $eventAlert)
    {
        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
           'command' => 'postparc:sendEventMessagesCommand',
           'eventAlertID' => $eventAlert->getId(),
        ]);
        
        $output = new NullOutput();
        $application->run($input, $output);

        $this->addFlash('success', 'EventAlert.alerts.launchManualySuccess');

        return $this->redirectToRoute('event_show', ['id' => $eventAlert->getEvent()->getId(), 'activeTab' => 'eventAlerts']);
    }

    /**
     * Create new eventAlert form an other eventAlert.
     *
     * @param EventAlert $eventAlert
     * @Route("/{id}/copy", name="eventAlert_copy", methods="GET")
     *
     * @return view
     */
    public function copyAction(EventAlert $eventAlert)
    {
        $em = $this->getDoctrine()->getManager();
        $translator = $this->get('translator');
        $newEventAlert = clone $eventAlert;

        $newEventAlert->setName($eventAlert->getName() . ' - ' . $translator->trans('copy'));
        $newEventAlert->setIsSended(false);
        $newEventAlert->setSendedDate(null);
        $newEventAlert->setIsSendedManualy(false);
        $newEventAlert->setRecipientEmails(null);
        $newEventAlert->setRejectedEmails(null);
        $newEventAlert->setSlug($eventAlert->getSlug() . '_copy_' . uniqid());
        $em->persist($newEventAlert);
        $em->flush();

        $this->addFlash('success', 'EventAlert.alerts.copySuccess');

        return $this->redirectToRoute('event_show', ['id' => $eventAlert->getEvent()->getId(), 'activeTab' => 'eventAlerts']);
    }

    /**
     * @param EventAlert $eventAlert
     * @Route("/{id}", name="eventalert_show", requirements={"id":"\d+"})
     *
     * @return Response
     */
    public function showAction(EventAlert $eventAlert)
    {
        return $this->redirectToRoute('event_show', ['id' => $eventAlert->getEvent()->getId(), 'activeTab' => 'eventAlerts']);
    }

    /**
     * Creates a form to delete a EventAlert entity.
     *
     * @param EventAlert $eventAlert The Event entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(EventAlert $eventAlert)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('eventAlert_delete', ['id' => $eventAlert->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

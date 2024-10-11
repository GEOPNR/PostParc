<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use PostparcBundle\Entity\Note;

/**
 * Note controller.
 *
 * @Route("/note")
 */
class NoteController extends Controller
{
    /**
     * Lists all notes associate ton an object.
     *
     * @param Request $request
     * @param string  $className
     * @param int     $objectId
     * @Route("/{className}/{objectId}/notes", name="object_notes", methods="GET|POST")
     *
     * @return Response
     */
    public function getTabContentNotesAction(Request $request, $className, $objectId)
    {
        $em = $this->getDoctrine()->getManager();
        $currentUser = $this->getUser();
        //récupération de l'object
        $object = $em->getRepository('PostparcBundle:' . $className)->find($objectId);

        $notes = $em->getRepository('PostparcBundle:Note')->getObjectNotes($className, $objectId, $currentUser->getId());

        // add new note form
        $note = new Note();
        $note->setClassName($className);
        $note->setObjectId($objectId);
        $form = $this->createForm('PostparcBundle\Form\NoteType', $note);

        return $this->render('note/tabContent.html.twig', [
          'notes' => $notes,
          'object' => $object,
          'form' => $form->createView(), ]);
    }

    /**
     * Lists all notes associate ton an object for print mode.
     *
     * @param Request $request
     * @param string  $className
     * @param int     $objectId
     * @Route("/{className}/{objectId}/notes/print", name="print_notes", methods="GET")
     *
     * @return Response
     */
    public function printNotesAction(Request $request, $className, $objectId)
    {
        $em = $this->getDoctrine()->getManager();
        $currentUser = $this->getUser();
        //récupération de l'object
        $object = $em->getRepository('PostparcBundle:' . $className)->find($objectId);

        $notes = $em->getRepository('PostparcBundle:Note')->getObjectNotes($className, $objectId, $currentUser->getId());

        return $this->render('note/printNotes.html.twig', [
          'notes' => $notes,
          'object' => $object,
        ]);
    }

    /**
     * get template for tab dom.
     *
     * @param Request $request
     * @param string  $className
     * @param int     $objectId
     *
     *
     * @return Response
     */
    public function getTabDomAction($className, $objectId)
    {
        $em = $this->getDoctrine()->getManager();
        $currentUser = $this->getUser();
        //récupération de l'object
        $object = $em->getRepository('PostparcBundle:' . $className)->find($objectId);

        $notes = $em->getRepository('PostparcBundle:Note')->getObjectNotes($className, $objectId, $currentUser->getId());

        return $this->render('note/tab.html.twig', [
          'notes' => $notes,
          'object' => $object,
        ]);
    }

    /**
     * add new note to one Object.
     *
     * @param Request $request
     * @param string  $className
     * @param int     $objectId
     * @Route("/{className}/{objectId}/add", name="note_add", methods="POST")
     *
     * @return Response
     */
    public function addAction(Request $request, $className, $objectId)
    {
        $em = $this->getDoctrine()->getManager();
        $note = new Note();
        $note->setClassName($className);
        $note->setObjectId($objectId);
        $form = $this->createForm('PostparcBundle\Form\NoteType', $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($note);
            $em->flush();

            $this->addFlash('success', 'flash.addSuccess');
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * add new note to one Object.
     *
     * @param Request $request
     * @param int     $note
     * @Route("/{id}/edit", name="note_edit", methods="POST")
     *
     * @return Response
     */
    public function editAction(Request $request, Note $note)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('PostparcBundle\Form\NoteType', $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($note);
            $em->flush();

            $this->addFlash('success', 'flash.editSuccess');
        }

        return $this->redirect($request->headers->get('referer'));
    }
    
    /**
     * add new note to one Object.
     *
     * @param Request $request
     * @param int     $note
     * @Route("/{id}/getEditDom", name="note_getEditDom", methods="POST")
     *
     * @return Response
     */
    public function getEditDomAction(Request $request, Note $note)
    {
        $form = $this->createForm('PostparcBundle\Form\NoteType', $note);
        
        return $this->render('note/editDom.html.twig', [
          'note' => $note,
          'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a Person entity.
     *
     * @param Request $request
     * @param Note    $note
     * @Route("/{id}/delete", name="note_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction(Request $request, Note $note)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($note);
        $request->getSession()
                ->getFlashBag()
                ->add('success', 'flash.deleteSuccess');

        return $this->redirect($request->headers->get('referer'));
    }
}

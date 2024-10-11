<?php

namespace PostparcBundle\Controller;

use PostparcBundle\Entity\Tag;
use PostparcBundle\Form\TagType;
use PostparcBundle\FormFilter\TagFilterType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tag controller.
 *
 * @Route("/tag")
 */
class TagController extends Controller
{
    /**
     * Lists all Tag entities.
     *
     * @param Request $request
     * @Route("/", name="tag_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $filterData = [];
        $filterForm = $this->createForm(TagFilterType::class);

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('TagFilterType');

            return $this->redirect($this->generateUrl('tag_index'));
        }

        // Filter action
        if ('filter' == $request->get('filter_action')) {
            // Bind values from the request
            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                // Save filter to session
                if ($filterForm->has('name')) {
                    $filterData['name'] = $filterForm->get('name')->getData();
                }

                $session->set('TagFilterType', $filterData);
            }
        } elseif ($session->has('TagFilterType')) {
            $filterData = $session->get('TagFilterType');
            $filterForm = $this->createForm('PostparcBundle\FormFilter\TagFilterType');
            if (array_key_exists('name', $filterData)) {
                $filterForm->get('name')->setData($filterData['name']);
            }
        }

        $query = $em->getRepository('PostparcBundle:Tag')->search($filterData);
        $repo = $em->getRepository('PostparcBundle:Tag');

        $options = ['decorate' => false];

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        return $this->render('tag/index.html.twig', [
            'search_form' => $filterForm->createView(),
            'htmlTree' => $htmlTree,
            'nbResults' => count($query->getArrayResult()),
        ]);
    }

    /**
     * Creates a new Tag entity.
     *
     * @param Request $request
     * @Route("/new", name="tag_new", methods="GET|POST")
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $tag = new tag();
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($tag);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.addSuccess');

            if (!$request->request->has('createAndContinue')) {
                return $this->redirectToRoute('tag_edit', ['id' => $tag->getId()]);
            } else {
                $tag = new tag();
                $form = $this->createForm(TagType::class, $tag);
            }
        }

        return $this->render('tag/new.html.twig', [
            'tag' => $tag,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a new tag entity.
     *
     * @Route("/new_by_ajax", name="ajax_add_new_tag", methods="POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newByAjaxAction(Request $request)
    {
        $tag = new Tag();
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($tag);
            $em->flush();
            $data = ['id' => $tag->getId(), 'name' => $tag->getName()];

            return new Response(json_encode($data), 201);
        }
        //dump($form->getErrors()); die;

        return new Response('ko', 200);
    }

    /**
     * get tag form to add new one.
     *
     * @Route("/ajax/getForm", name="ajax_get_tag_form", options={"expose"=true}, methods="GET")
     *
     * @return Response
     */
    public function ajaxTagFormAction()
    {
        // mise en place formulaire pour ajout tag
        $tag = new Tag();
        $newTagForm = $this->createForm(TagType::class, $tag);

        return $this->render('tag/formModal.html.twig', [
            'form' => $newTagForm->createView(),
        ]);
    }

    /**
     * Finds and displays a Tag entity.
     *
     * @param Tag $tag
     * @Route("/{id}", name="tag_show", methods="GET", requirements={"id":"\d+"})
     *
     * @return Response
     */
    public function showAction(Tag $tag)
    {
        return $this->render('tag/show.html.twig', [
            'tag' => $tag,
        ]);
    }

    /**
     * Displays a form to edit an existing tag entity.
     *
     * @param Request $request
     * @param Tag     $tag
     * @Route("/{id}/edit", name="tag_edit", methods="GET|POST")
     *
     * @return Response
     */
    public function editAction(Request $request, Tag $tag)
    {
        $deleteForm = $this->createDeleteForm($tag);
        $editForm = $this->createForm(TagType::class, $tag);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($tag);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.updateSuccess');

            return $this->redirectToRoute('tag_edit', ['id' => $tag->getId()]);
        }

        return $this->render('tag/edit.html.twig', [
            'tag' => $tag,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Delete a tag entity.
     *
     * @param Request $request
     * @param Tag     $tag
     * @Route("/{id}/delete", name="tag_delete", methods="GET")
     *
     * @return Response
     */
    public function deleteAction(Request $request, Tag $tag)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = [$tag->getId()];
        $em->getRepository('PostparcBundle:Tag')->batchDelete($ids);
        $request->getSession()
        ->getFlashBag()
        ->add('success', 'flash.deleteSuccess');

        return $this->redirectToRoute('tag_index');
    }

    /**
     * Creates a new Tag entity.
     *
     * @param Request $request
     * @param Tag     $parent
     * @Route("/new/{id}", name="tag_new_subTag", methods="GET|POST")
     *
     * @return Response
     */
    public function newSubTagAction(Request $request, Tag $parent)
    {
        $tag = new Tag();
        $tag->setParent($parent);
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($tag);
            $em->flush();

            $request->getSession()
            ->getFlashBag()
            ->add('success', 'flash.addSuccess');

            return $this->redirectToRoute('tag_edit', ['id' => $tag->getId()]);
        }

        return $this->render('tag/new.html.twig', [
            'tag' => $tag,
            'form' => $form->createView(),
        ]);
    }

    /**
     * tag batch actions.
     *
     * @param Request $request
     * @Route("/batch", name="tag_batch", methods="POST")
     *
     * @return Response
     */
    public function batchAction(Request $request)
    {
        if ('batchDelete' === $request->request->get('batch_action')) {
            $ids = $request->request->get('ids');

            if ($ids) {
                $em = $this->getDoctrine()->getManager();
                $em->getRepository('PostparcBundle:Tag')->batchDelete($ids);
                $request->getSession()
                ->getFlashBag()
                ->add('success', 'batch.deleteSuccess');
            } else {
                $request->getSession()
                ->getFlashBag()
                ->add('error', 'batch.noSelectedItemError');
            }
        }

        return $this->redirectToRoute('tag_index');
    }

    /**
     * Creates a form to delete a Tag entity.
     *
     * @param Tag $tag The Tag entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Tag $tag)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('tag_delete', ['id' => $tag->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

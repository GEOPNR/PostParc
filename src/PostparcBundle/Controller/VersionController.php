<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/version")
 */
class VersionController extends Controller
{
    /**
     * @param string $class
     * @param int    $objectId
     * @Route("/{class}/{objectId}", name="version_liste"))
     *
     * @return Response
     */
    public function indexAction($class, $objectId)
    {
        $em = $this->getDoctrine()->getManager();
        // first check our log entries
        $repo = $em->getRepository('Gedmo\Loggable\Entity\LogEntry'); // we use default log entry class

        $object = $em->find('PostparcBundle\Entity\\' . $class, $objectId);
        $versions = $repo->getLogEntries($object);

        switch ($class) {
            case 'PersonFunction':
                $route = 'function_edit';
                break;
            case 'DocumentTemplate':
                $route = 'documentTemplate_edit';
                break;
            case 'MailFooter':
                $route = 'mailFooter_edit';
                break;
            case 'PrintFormat':
                $route = 'print_format_edit';
                break;
            case 'MandateType':
                $route = 'mandateType_edit';
                break;
            case 'Entity':
                $route = 'entity_edit';
                break;
            case 'City':
                $route = 'city_edit';
                break;
            case 'AdditionalFunction':
                $route = 'complement_function_edit';
                break;
            case 'Group':
                $route = 'group_edit';
                break;
            case 'Help':
                $route = 'help_edit';
                break;
            case 'SearchList':
                $route = 'searchList_edit';
                break;
            case 'Service':
                $route = 'service_edit';
                break;
            case 'Territory':
                $route = 'territory_edit';
                break;
            case 'TerritoryType':
                $route = 'territoryType_edit';
                break;

            default:
                $route = strtolower($class) . '_show';
                break;
        }
        $urlShowObject = $this->generateUrl($route, ['id' => $objectId]);

        return $this->render('version/index.html.twig', [
            'versions' => $versions,
            'class' => $class,
            'object' => $object,
            'urlShowObject' => $urlShowObject,
        ]);
    }

    /**
     * @param Request $request
     * @param string  $class
     * @param int     $objectId
     * @param int     $versionId
     * @Route("/{class}/{objectId}/{versionId}", name="version_revert"))
     *
     * @return Response
     */
    public function revertAction(Request $request, $class, $objectId, $versionId)
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('Gedmo\Loggable\Entity\LogEntry');

        $object = $em->find('PostparcBundle\Entity\\' . $class, $objectId);
        $repo->revert($object, $versionId);

        $em->persist($object);
        $em->flush();
        $request->getSession()
        ->getFlashBag()
        ->add('success', 'flash.revertSuccess');

        return $this->redirectToRoute('version_liste', ['class' => $class, 'objectId' => $objectId]);
    }
}

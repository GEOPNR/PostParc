<?php

namespace PostparcBundle\Controller;

use Cocur\Slugify\Slugify;
use PostparcBundle\Entity\ScanQrcodeStat;
use PostparcBundle\FormFilter\QrCodeStatFilterType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Public controller.
 *
 * @Route("/qrCodeStat")
 */
class QrCodeStatController extends Controller
{    
    /**
     * Lists all Tag entities.
     *
     * @param Request $request
     * @Route("/", name="qrCodeStat_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');

        $filterData = [];
        $filterForm = $this->createForm(QrCodeStatFilterType::class);
        
        

        // Reset filter
        if ('reset' == $request->get('filter_action')) {
            $session->remove('QrCodeStatFilter');

            return $this->redirect($this->generateUrl('qrCodeStat_index'));
        }

        // Filter action
        if ('filter' == $request->get('filter_action')) {
            // Bind values from the request
            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                // Save filter to session
                if ($filterForm->has('startDate')) {
                    $filterData['startDate'] = $filterForm->get('startDate')->getData();
                }
                if ($filterForm->has('endDate')) {
                    $filterData['endDate'] = $filterForm->get('endDate')->getData();
                }
                if ($filterForm->has('className')) {
                    $filterData['className'] = $filterForm->get('className')->getData();
                }
                $session->set('QrCodeStatFilter', $filterData);
                
            }
        } elseif ($session->has('QrCodeStatFilter')) {
            $filterData = $session->get('QrCodeStatFilter');
            $filterForm = $this->createForm(QrCodeStatFilterType::class);
            if (array_key_exists('startDate', $filterData) && $filterData['startDate']) {
                $filterForm->get('startDate')->setData($filterData['startDate']);
            }
            if (array_key_exists('endDate', $filterData) && $filterData['endDate']) {
                $filterForm->get('endDate')->setData($filterData['endDate']);
            }
            if (array_key_exists('className', $filterData) && count($filterData['className'])) {
                $filterForm->get('className')->setData($filterData['className']);
            }
        }

        
        $filterData['entityID'] = $currentEntityService->getCurrentEntityId();
        $filterData['sort'] = $request->query->has('sort')?$request->query->get('sort'):'s.completeName';
        $filterData['direction'] = $request->query->has('direction')?$request->query->get('direction'):'asc';
        $filterData['page'] = $request->query->getInt('page', 1);
        $filterData['per_page'] = $request->query->getInt('per_page', $default_items_per_page);
        
        $stats = $em->getRepository('PostparcBundle:ScanQrcodeStat')->getStatsQuery($filterData);
        if ($request->query->get('exportXls')) {
            return $this->exportCSV($stats);
        }
        
        // for link labels, use knp paginator
        $query = $stats;        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/* page number */,
            $request->query->getInt('per_page', $default_items_per_page), /* limit per page */
            [
            'defaultSortFieldName' => 's.completeName',
            'defaultSortDirection' => 'asc',
            ]
        );

        return $this->render('qrCodeStat/index.html.twig', [
              'stats' => $stats,  
              'pagination' => $pagination,
              'search_form' => $filterForm->createView(),
        ]);

    }
    
    public function exportCsv($stats)
    {
        $translator = $this->get('translator');
        $slugify = new Slugify();
        $rows = [$translator->trans('QrCodeStat.field.completeName').';Nb'];
        foreach($stats as $result) {
            $data = 
                [
                    $result['completeName'],
                    $result['nb']
                ];
            $rows[] = implode(';', $data);
        }
        
        $content = implode("\n", $rows);
        
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Cache-Control', 'no-cache');
        $filename = $slugify->slugify($translator->trans('QrCodeStat.list'));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT, "$filename.csv"
        ));

        return $response;
        
    }
    
    
    
}

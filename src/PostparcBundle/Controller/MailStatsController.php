<?php

namespace PostparcBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * MailStats controller.
 *
 * @Route("/mailStats")
 */
class MailStatsController extends Controller {

    /**
     * index of Stats.
     *
     * @Route("/", name="mailStats_index", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        // recuperation du user courant
        $currentUser = $this->getUser();

        $stats = $em->getRepository('PostparcBundle:MailStats')->getStatsByMonth($currentUser);
        $detailedStatsQuey = $em->getRepository('PostparcBundle:MailStats')->getDetailedStats($currentUser);

        $paginator = $this->get('knp_paginator');
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        $default_items_per_page = isset($currentEntityConfig['default_items_per_page']) ? $currentEntityConfig['default_items_per_page'] : $this->container->getParameter('per_page_global');
        $detailedStats = $paginator->paginate(
                $detailedStatsQuey, /* query NOT result */
                $request->query->getInt('page', 1)/* page number */,
                $request->query->getInt('per_page', $default_items_per_page)/* limit per page */
        );

        $this->updateNbOpenedEmailFields($detailedStatsQuey->getResult());

        return $this->render('mailStats/index.html.twig', [
                    'stats' => $stats,
                    'detailedStats' => $detailedStats,
        ]);
    }

    /**
     * update nbOpenedEmail fields by claaling piwik API.
     *
     * @param type $mailStat
     */
    private function updateNbOpenedEmailFields($mailStats) {
        $em = $this->getDoctrine()->getManager();
        $havetoBeFlush = false;
        $piwikSatsService = $this->container->get('postparc.piwik_stats');

        foreach ($mailStats as $mailStat) {
            $nbEmail = $mailStat->getNbEmail();
            $actualNbOpenedEmail = $mailStat->getNbOpenedEmail();
            if ($mailStat->getToken() && ($actualNbOpenedEmail < $nbEmail) ) { // prevent recall api for full open email
                $piwikInfos = $piwikSatsService->getPiwikOpenedNewsletterInfos($mailStat->getToken());
                if ($piwikInfos) {
                    $nbVisite = $piwikInfos['sum_daily_nb_uniq_visitors'];
                    if ($nbVisite && $mailStat->getNbOpenedEmail() < $nbVisite) {
                        $mailStat->setNbOpenedEmail($nbVisite);
                        $em->persist($mailStat);
                        $havetoBeFlush = true;
                    }
                }
            }
            // prevent up to 100%
            if ($mailStat->getNbOpenedEmail() > $nbEmail) {
                $mailStat->setNbOpenedEmail($nbEmail);
                $em->persist($mailStat);
                $havetoBeFlush = true;
            }
        }
        if ($havetoBeFlush) {
            $em->flush();
        }
    }

}

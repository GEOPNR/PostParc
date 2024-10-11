<?php

namespace PostparcBundle\Controller;

use PostparcBundle\Enum\SummernoteFontFamily;
use PostparcBundle\Enum\SummernoteFontSize;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Config controller.
 */
class ConfigController extends Controller
{
    /**
     * config homepage.
     *
     * @param Request $request
     * @Route("/admin/config/modules", name="config_module", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $entity = $this->getUser()->getEntity();
        $entityID = null;
        if (true == $this->container->getParameter('isMultiInstance') && $request->query->has('entityID') && $this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
            $entityID = $request->query->get('entityID');
            $entity = $em->getRepository('PostparcBundle:Entity')->find($entityID);
        }
        $configs = $entity->getConfigs();
        if (!is_array($configs)) {
            $configs = [];
        }
        if (!array_key_exists('personnalFieldsRestriction', $configs)) {
            $configs['personnalFieldsRestriction'] = [];
        }
        if (!array_key_exists('emptySpecificMessageField', $configs)) {
            $configs['emptySpecificMessageField'] = false;
        }
        if (!array_key_exists('hideSpecificMessageField', $configs)) {
            $configs['hideSpecificMessageField'] = false;
        }
        if (!array_key_exists('hideBlocSendWithSendingMailSoftware', $configs)) {
            $configs['hideBlocSendWithSendingMailSoftware'] = false;
        }
        if (!isset($configs['summernote_font_family'])) {
            $configs['summernote_font_family'] = $this->container->getParameter('summernote_default_font_family');
        }
        
        if (!isset($configs['summernote_font_size'])) {
            $configs['summernote_font_size'] = $this->container->getParameter('summernote_default_font_size');
        }
        if (!isset($configs['tabsOrder'])) {
            $configs['tabsOrder'] = [
                'persons' => 1,
                'pfos' => 2,
                'organizations' => 3,
                'representations' => 4,
            ];
        }

        if ($request->isMethod('POST')) {
            
            if ($this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
                $configs['use_massiveMail'] = 'on' == $request->request->get('use_massiveMail');
                $configs['max_email_per_month'] = $request->request->get('max_email_per_month');
                $configs['domains_alowed'] = explode(';', $request->request->get('domains_alowed'));
            }
            $configs['use_event_module'] = $request->request->has('use_event_module');
            $configs['use_representation_module'] = $request->request->has('use_representation_module');
            $configs['use_readerLimitations_module'] = $request->request->has('use_readerLimitations_module');
            $configs['use_sendInBlue_module'] = $request->request->has('use_sendInBlue_module');
            $configs['sendInBlue_apiKey'] = $request->request->get('sendInBlue_apiKey');
            $configs['hideSpecificMessageField'] = $request->request->has('hideSpecificMessageField');
            $configs['emptySpecificMessageField'] = $request->request->has('emptySpecificMessageField');
            $configs['hideBlocSendWithSendingMailSoftware'] = $request->request->has('hideBlocSendWithSendingMailSoftware');
            $configs['summernote_font_family'] = $request
                    ->request
                    ->get('summernote_font-family');
            $configs['summernote_font_size'] = $request
                    ->request
                    ->get('summernote_font_size');
            if (true == $this->container->getParameter('isMultiInstance')) {
                $configs['show_SharedContents'] = $request->request->has('show_SharedContents');
                $configs['shared_contents'] = $request->request->has('shared_contents');
            }
            $tabsOrder = $request
                    ->request
                    ->get('tabsOrder');
            asort($tabsOrder);
            $configs['tabsOrder'] = $tabsOrder;

            $entity->setConfigs($configs);
            $em->persist($entity);
            $em->flush();
            if ($entity->getId() == $this->getUser()->getEntity()->getId()) {
                $session->set('currentEntityConfig', $configs);
            }
            $this->addFlash('success', 'flash.updateSuccess');
        }

        return $this->render('config/index.html.twig', [
            'configs' => $configs,
            'entityID' => $entityID,
            'summernote_font_families' => SummernoteFontFamily::all(),
            'summernote_font_sizes' => SummernoteFontSize::all()
        ]);
    }

    /**
     * @param Request $request
     * @Route("/user/configs", name="user_configs", methods="GET|POST")
     *
     * @return Response
     */
    public function userConfigsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $user = $this->getUser();
        $configs = $user->getConfigs();
        $sessionConfigs = $session->get('currentEntityConfig');
        if (count($configs) === 0) {
            $configs['show_SharedContents'] = $sessionConfigs['show_SharedContents'];
            $configs['default_items_per_page'] = $this->container->getParameter('per_page_global');
            $configs['empty_search_on_load'] = false;
            
        }
        if (!isset($configs['default_items_per_page'])) {
            $configs['default_items_per_page'] = $this->container->getParameter('per_page_global');
        }
        if (!array_key_exists('empty_search_on_load', $configs)) {
            $configs['empty_search_on_load'] = false;
        }
        if (!array_key_exists('emptySpecificMessageField', $configs)) {
            $configs['emptySpecificMessageField'] = false;
        }
        if (!array_key_exists('hideSpecificMessageField', $configs)) {
            $configs['hideSpecificMessageField'] = false;
        }
        if (!array_key_exists('hideBlocSendWithSendingMailSoftware', $configs)) {
            $configs['hideBlocSendWithSendingMailSoftware'] = false;
        }
        
        if (!isset($configs['summernote_font_family'])) {
            $configs['summernote_font_family'] = $this->container->getParameter('summernote_default_font_family');
        }
        
        if (!isset($configs['summernote_font_size'])) {
            $configs['summernote_font_size'] = $this->container->getParameter('summernote_default_font_size');
        }
        if (!isset($configs['tabsOrder'])) {
            $configs['tabsOrder'] = [
                'persons' => 1,
                'pfos' => 2,
                'organizations' => 3,
                'representations' => 4,
            ];
        }
        
        if ($request->isMethod('POST')) {  
            $configs['show_SharedContents'] = $request->request->has('show_SharedContents');
            $configs['empty_search_on_load'] = $request->request->has('empty_search_on_load');
            $configs['hideSpecificMessageField'] = $request->request->has('hideSpecificMessageField');
            $configs['emptySpecificMessageField'] = $request->request->has('emptySpecificMessageField');
            $configs['hideBlocSendWithSendingMailSoftware'] = $request->request->has('hideBlocSendWithSendingMailSoftware');
            $configs['default_items_per_page'] = ($request->request->has('default_items_per_page') && $request->request->get('default_items_per_page') > 0) ? $request->request->get('default_items_per_page') : $this->container->getParameter('per_page_global');
            $configs['summernote_font_family'] = $request
                    ->request
                    ->get('summernote_font-family');
            $configs['summernote_font_size'] = $request
                    ->request
                    ->get('summernote_font_size');
            $tabsOrder = $request
                    ->request
                    ->get('tabsOrder');
            asort($tabsOrder);
            $configs['tabsOrder'] = $tabsOrder; 
            $user->setConfigs($configs);
            $em->persist($user);
            $em->flush();
            $session->set('currentEntityConfig', array_merge($sessionConfigs, $configs));
            $this->addFlash('success', 'flash.updateSuccess');
        }
        

        return $this->render('config/user_configs.html.twig', [
            'configs' => $configs,
            'summernote_font_families' => SummernoteFontFamily::all(),
            'summernote_font_sizes' => SummernoteFontSize::all()
        ]);
    }
}

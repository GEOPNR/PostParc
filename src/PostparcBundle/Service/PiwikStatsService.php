<?php

namespace PostparcBundle\Service;

use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\DependencyInjection\Container;

class PiwikStatsService
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getPiwikOpenedNewsletterInfos($referrerName = false)
    {
        $content = null;

        if ($this->container->hasParameter('piwik')) {
            $piwikParams = $this->container->getParameter('piwik');
            if ($piwikParams['enable']) {
                $token_auth = $piwikParams['token_auth'];
                $url = $piwikParams['scheme'] . '://' . $piwikParams['piwirlUrl'] . '/index.php';
                $url .= '?module=API&method=Actions.getPageTitles&rec=1';
                $url .= '&idSite=' . $piwikParams['piwikStatsMailId'];
                $url .= '&period=range';
                $url .= '&date=last30';
                //$url .= "&date=20181231";
                $url .= '&format=JSON&filter_limit=300';
                $url .= "&token_auth=$token_auth";

                if ($referrerName) {
                    //$url .= '&segment=referrerType==campaign;referrerName=='.$referrerName;
                    $url .= "&label=email-opened-" . $referrerName;
                }
                //echo $url.'<br/>';
                try {
                    $fetched = file_get_contents($url);
                    //echo $fetched;
                    //die;
                    $result = json_decode($fetched, true);
                    //print_r($result);
                    //die;

                    $content = (is_array($result) && array_key_exists('0', $result)) ? $result[0] : false;                    
                } catch (ContextErrorException $exc) {
                    echo $exc->getTraceAsString();
                }


            }
        }
        //dump($content);die;
        return $content;
    }
}

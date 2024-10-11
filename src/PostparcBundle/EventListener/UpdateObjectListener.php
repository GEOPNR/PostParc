<?php

namespace PostparcBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManager;
use PostparcBundle\Entity\Coordinate;
use PostparcBundle\Entity\Entity;
use PostparcBundle\Entity\EntityCoordinateDistance;

class UpdateObjectListener
{
    /**
     * @var \Swift_Mailer|mixed
     */
    public $mailer;
    /**
     * @var \Symfony\Component\Translation\TranslatorInterface|mixed
     */
    public $translator;
    /**
     * @var mixed
     */
    public $env;
    private $current_user;

    /**
     * @param type                $security_context
     * @param \Swift_Mailer       $mailer
     * @param TranslatorInterface $translator
     */
    public function __construct($security_context, \Swift_Mailer $mailer, TranslatorInterface $translator, $env = '')
    {
        if (null != $security_context->getToken()) {
            $this->current_user = $security_context->getToken()->getUser();
        }
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->env = $env;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        // test if object has method getUpdatedBy
        if (is_callable([$entity, 'getUpdatedBy']) && is_callable([$entity, 'getCreatedBy']) && $entity->getCreatedBy() && $entity->getUpdatedBy() && ($entity->getCreatedBy()->getId() != $entity->getUpdatedBy()->getId())) {
            $creator = $entity->getCreatedBy();
            $updator = $entity->getUpdatedBy();
            if ($creator && $creator->getWishesToBeInformedOfChanges() && $creator->getId() != $updator->getId() && filter_var($creator->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $subject = $this->translator->trans('Notification.mail.subject');
                $body = $this->translator->trans('Notification.mail.body', ['%content%' => $entity, '%user%' => $updator]);

                $message = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom('no-reply@postparc.fr')
                        ->setTo($creator->getEmail())
                        ->setBody($body, 'text/html')
                ;
                $this->mailer->send($message);
            }
        }

        if ($entity instanceof Coordinate && !$entity->getCoordinate()) {
            $entity = $this->searchGeographicalInfo($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args = null)
    {
        if (is_array($args)) {
            $entity = $args->getEntity();
            $entityManager = $args->getEntityManager();
            if ($entity instanceof Coordinate) {
                // search geoloc with api
                if (!$entity->getCoordinate()) {
                    $entity = $this->searchGeographicalInfo($entity);
                }
                if ($entity->getCoordinate()) {
                    // search if EntityCoordinateDistance object associate to entity ans coordinate existe
                    $userEntity = null;
                    if ($entity->getCreatedBy()) {
                        $userEntity = $entity->getCreatedBy()->getEntity();
                    } elseif ($this->current_user) {
                        $userEntity = $this->current_user->getEntity();
                    }
                    if ($userEntity !== null) {
                        $entityCoordinateDistance = $entityManager->getRepository('PostparcBundle:EntityCoordinateDistance')->findOneBy(['entity' => $userEntity, 'coordinate' => $entity]);
                        if (!$entityCoordinateDistance && $entity->getCoordinate()) {
                            $this->generateContactDistanceInfos($entityManager, $userEntity, $entity);
                        }
                    }
                }
            }
        }
    }

    /**
     * search coordinate information for on address via google api.
     *
     * @param EntityManager $em
     * @param Coordinate    $coordinate
     *
     * @return Coordinate
     */
    private function searchGeographicalInfo(Coordinate $coordinate)
    {
//        $key = '&key=AIzaSyB8v1F-tNAmAdnJ2h3ontERJLp931Dez58';
//        $key = '';
//        $geocoder = 'http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false'.$key;
        $env = $this->env;
        if ($env) {
            $env .= ".";
        }
        $geocoder = 'https://nominatim.openstreetmap.org/search.php?q=%s&format=json&addressdetails=1&limit=1&polygon_svg=1&email=contact@' . $env . 'postparc.fr';

        $adresse = $coordinate->getAddressLine1();
        if ($coordinate->getCity()) {
            $adresse .= ', ' . $coordinate->getCity()->getZipCode();
            $adresse .= ', ' . $coordinate->getCity()->getName();
            if ($coordinate->getCity()->getCountry() !== '' && $coordinate->getCity()->getCountry() !== '0') {
                $adresse .= ', ' . $coordinate->getCity()->getCountry();
            }
        }
        if (strlen($adresse) !== 0) {
            // Requête envoyée à l'API Geocoding
            $adresse = str_replace(', ', ' ', $adresse);
            $adresse = str_replace(['(', ')'], '', $adresse);
            $url = sprintf($geocoder, str_replace(' ', '+', $adresse));

            //$url = sprintf($geocoder, urlencode(utf8_encode($adresse)));
            $ch = curl_init();
            $userAgent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2';
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            curl_setopt($ch, CURLOPT_REFERER, 'https://' . $env . 'postparc.fr');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $tmp = curl_exec($ch);

            $result = json_decode($tmp, true);

//            if ('OK' === $result['status']) {
//                $lat = $result['results']['0']['geometry']['location']['lat'];
//                $lng = $result['results']['0']['geometry']['location']['lng'];
//                $coordinate->setCoordinate(str_replace(' ', '', $lat.','.$lng));
//            }
            if (is_array($result) && count($result)) {
                $lat = $result['0']['lat'];
                $lng = $result['0']['lon'];
                $coordinate->setCoordinate(str_replace(' ', '', $lat . ',' . $lng));
            }
        }

        return $coordinate;
    }

    /**
     * Call google api to get distance and duration between entity and coordinate.
     *
     * @param EntityManager $em
     * @param Entity        $entity
     * @param Coordinate    $coordinate
     * @param string        $api        wich api use
     *
     * @return EntityCoordinateDistance
     */
    private function generateContactDistanceInfos(EntityManager $em, Entity $entity, $coordinate, $api = 'google')
    {
        $entityCoordinateDistance = null;
        $coordinateFinded = false;

        switch ($api) {
            case 'google':
                $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?';
                $url .= 'origins=' . $entity->getCoordinate();
                $url .= '&destinations=' . $coordinate->getCoordinate();
                $url .= '&language=fr-FR';
                break;
            case 'osrm':
                $url = 'http://router.project-osrm.org/route/v1/driving/' . $entity->getCoordinate() . ';' . $coordinate->getCoordinate() . '?overview=false';
                break;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, str_replace(' ', '', $url));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmp = curl_exec($ch);
        $result = json_decode($tmp, true);

        switch ($api) {
            case 'google':
                if ('OK' == $result['status']) {
                    $entityCoordinateDistance = new EntityCoordinateDistance();
                    $elements = $result['rows'][0]['elements'][0];
                    if (array_key_exists('distance', $elements) && array_key_exists('duration', $elements)) {
                        $distance = $elements['distance']['value'];
                        $duration = $elements['duration']['value'];
                        $distanceText = $elements['distance']['text'];
                        $durationText = $elements['duration']['text'];
                        $coordinateFinded = true;
                    }
                }
                break;
            case 'osrm':
                if ('Ok' == $result['code']) {
                    $entityCoordinateDistance = new EntityCoordinateDistance();
                    $elements = $result['routes'][0];

                    if (array_key_exists('distance', $elements) && array_key_exists('duration', $elements)) {
                        $distance = $elements['distance'];
                        $duration = $elements['duration'];
                        $distanceText = (round($distance / 1000)) . ' km';
                        $durationText = gmdate('H:i', $duration);
                        $coordinateFinded = true;
                    }
                }
                break;
        }

        if ($coordinateFinded) {
            $entityCoordinateDistance = new EntityCoordinateDistance();
            $entityCoordinateDistance->setEntity($entity);
            $entityCoordinateDistance->setCoordinate($coordinate);
            $entityCoordinateDistance->setDistanceText($distanceText);
            $entityCoordinateDistance->setDistanceValue($distance);
            $entityCoordinateDistance->setDurationText($durationText);
            $entityCoordinateDistance->setDurationValue($duration);
            $em->persist($entityCoordinateDistance);
            $em->flush();
        }

        return $entityCoordinateDistance;
    }
}

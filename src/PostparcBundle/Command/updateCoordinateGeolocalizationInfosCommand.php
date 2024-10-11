<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * pdateCoordinateGeolocalizationInfos.
 */
class updateCoordinateGeolocalizationInfosCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
                ->setName('postparc:updateAddressCoordinates')
                ->setDescription('update Coordinate goeloc infos')
                ->addArgument('limit', InputArgument::OPTIONAL, 'number limit of adress to be upgrade')
                ->addArgument('coordinateID', InputArgument::OPTIONAL, 'id of specific coordinate to be upgrade')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $env = $this->getContainer()->get('kernel')->getEnvironment();


        // before we clean non validate coordinate fields :
        $this->purgeCoordinate($em, $output);

        $limit = $input->getArgument('limit') ? $input->getArgument('limit') : 500;
        $coordinateID = $input->getArgument('coordinateID') ? $input->getArgument('coordinateID') : null;

        $coordinates = $em->getRepository('PostparcBundle:Coordinate')->findCoordinatesWithNoGeolocalizationInfos($limit, $coordinateID);

        $haveToFlush = false;

        $counter = 0;
        $counterEmpty = 0;
        $geocoder = 'https://nominatim.openstreetmap.org/search.php?q=%s&format=json&addressdetails=1&limit=1&polygon_svg=1&email=contact@' . $env . '.postparc.fr';

        foreach ($coordinates as $coordinate) {
            $adresse = '';

            $trySearchWithGoogleApi = false;

            if ($coordinate->getCity()) {
                if (strlen(trim($coordinate->getAddressLine1())) !== 0) {
                    $adresse .= $coordinate->getAddressLine1() . '+';
                    $trySearchWithGoogleApi = true;
                }
                if (strlen(trim($coordinate->getAddressLine2())) !== 0) {
                    $adresse .= $coordinate->getAddressLine2() . '+';
                    $trySearchWithGoogleApi = true;
                }
                if ($trySearchWithGoogleApi) {
                    $adresse .= $coordinate->getCity()->getZipcode() . '+' . $coordinate->getCity()->getName();
                    if ($coordinate->getCity()->getDepartment()) {
                        //$adresse .= '+'.$coordinate->getCity()->getDepartment();
                    }
                    if ($coordinate->getCity()->getCountry()) {
                        $adresse .= '+' . $coordinate->getCity()->getCountry();
                    }
                    //$url = str_replace(array(', ', ' '), '+', $apigoogle).',FR&sensor=false'.$key;
                    $adresse = str_replace(', ', ' ', $adresse);
                    $adresse = str_replace(['(', ')'], '', $adresse);
                    $url = sprintf($geocoder, str_replace(' ', '+', $adresse));
                    $output->writeln("url appelée : <info>" . $url . "</info>");
                    $ch = curl_init();
                    $userAgent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2';
                    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
                    curl_setopt($ch, CURLOPT_REFERER, 'https://' . $env . '.postparc.fr');
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $tmp = curl_exec($ch);
                    $result = json_decode($tmp, true);
                    $lat = null;
                    $lng = null;
                    if (is_array($result) && count($result)) {
                        $lat = $result['0']['lat'];
                        $lng = $result['0']['lon'];
                    }
                    if ($lat && $lng) {
                        $output->writeln('lat : ' . $lat);
                        $output->writeln('lng : ' . $lng);
                        $coordinate->setCoordinate(str_replace(' ', '', $lat . ',' . $lng));
                        $em->persist($coordinate);
                        $output->writeln(" coordonnees de l'adresse  :<info> " . $coordinate->getId() . '</info> mises a jour');
                    } elseif (strlen(trim($coordinate->getCity()->getCoordinate())) !== 0) {
                        $output->writeln(' récupération coordonnees de la ville associée pour coordinate id  :<info> ' . $coordinate->getId() . '</info>');
                        $coordinate->setCoordinate(str_replace(' ', '', $coordinate->getCity()->getCoordinate()));
                        $em->persist($coordinate);
                    }
                    ++$counter;
                    $haveToFlush = true;
                    if (200 == $counter) {
                        $counter = 0;
                        $em->flush();
                    }
                } elseif (strlen(trim($coordinate->getCity()->getCoordinate())) !== 0) {
                    $output->writeln(' récupération coordonnees de la ville associée pour coordinate id  :<info> ' . $coordinate->getId() . '</info>');
                    $coordinate->setCoordinate($coordinate->getCity()->getCoordinate());
                    $em->persist($coordinate);
                } else {
                    ++$counterEmpty;
                }
            }
        }
        if ($counterEmpty !== 0) {
            $output->writeln('<info> ' . $counterEmpty . '</info> non mises à jour car lat et lng vide au retour de google api');
        }

        if ($haveToFlush) {
            $em->flush();
        }
    }

    private function purgeCoordinate($em, OutputInterface $output)
    {
        $output->writeln('<info>Mise à NULL des champs coordinate pour les coordonnées sans adresse ni ville </info> ');
        $coordinateToBeDeleted = $em->getRepository('PostparcBundle:Coordinate')->getCoordinateWithUnValidCoordinate();
        foreach ($coordinateToBeDeleted as $coordinate) {
            $coordinate->setCoordinate(null);
            $em->persist($coordinate);
        }
        if (count($coordinateToBeDeleted) > 0) {
            $output->writeln('Le champ coordinate de ' . count($coordinateToBeDeleted) . ' coordonnée(s) a été passé à NULL.');
        } else {
            $output->writeln('Nothing To do ... ');
        }
    }
}

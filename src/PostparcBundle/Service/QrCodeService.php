<?php

  namespace PostparcBundle\Service;

  use Symfony\Component\DependencyInjection\Container;
  use Endroid\QrCode\Builder\BuilderInterface;
  use Endroid\QrCode\Encoding\Encoding;
  use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
  use Endroid\QrCode\QrCode;
  use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
  use Endroid\QrCode\Writer\PngWriter;
  use Endroid\QrCodeBundle\Response\QrCodeResponse;
  use Endroid\QrCode\Label\Label;
  use Endroid\QrCode\Logo\Logo;
  use Endroid\QrCode\Color\Color;
  use Endroid\QrCode\Label\Font\NotoSans;
  use Symfony\Bundle\FrameworkBundle\Routing\Router;

  class QrCodeService {

    private $container;
    private $router;

    public function __construct(Container $container, Router $router) {
      $this->container = $container;
      $this->router = $router;
    }

    public function generateVcardQrCode($object) {
      $className = strtolower($object->getClassName());
      $filePathDir = $this->container->get('kernel')->getRootDir() . '/../web/uploads/qrCodes/';
      $env = $this->container->get('kernel')->getEnvironment();
      $filePath = $filePathDir . $env . '_QrCode_' . $className . '-' . $object->getId() . '.png';
      $host = 'https://' . $env . '.postparc.fr';
      
      if ($env == 'prod') { // special case for preprod
        //$host = 'https://postparc.p6-preprod-php74.probesys.net';
      }
      if($env == 'dev'){
          $host = 'http://172.30.101.192:8001';
      }

      $url = $host . $this->router->generate('scan_qrCode', ['id' => $object->getId(), 'className' => $object->getClassName()]);
      //dump($url);die;

      $writer = new PngWriter();
      $qrCode = new QrCode($url);
      $qrCode->create($url)
              ->setEncoding(new Encoding('UTF-8'))
              ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
              ->setSize(300)
              ->setMargin(10)
              ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
              ->setForegroundColor(new Color(0, 0, 0))
              ->setBackgroundColor(new Color(255, 255, 255));

      // add postparc logo on center of the QRCODE image
      $logo = Logo::create($this->container->get('kernel')->getRootDir() . '/../web/bundles/postparc/images/logo-postparc-qrcode.png')
              ->setResizeToHeight(40);
      
      //$logo=null;// disable logo insertion

      $label = Label::create('Généré par postparc')->setFont(new NotoSans(8));

      $result = $writer->write($qrCode,
              $logo,
              $label
      );

      // save qrCodeImg file
      if (!file_exists($filePath)) {
        $result->saveToFile($filePath);
      }
      $uri = $result->getDataUri();

      return ['fileName' => $filePath, 'uri' => $uri];
    }

  }
  
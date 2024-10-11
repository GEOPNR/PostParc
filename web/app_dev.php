<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
// for more information
umask(0000);

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
/* if (isset($_SERVER['HTTP_CLIENT_IP'])
  || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
  || !(in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', 'fe80::1', '::1']) || php_sapi_name() === 'cli-server')
  ) {
  header('HTTP/1.0 403 Forbidden');
  exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
  } */
$whiteList = array(
    '127.0.0.1',
    'localhost',
    '::1',
    /* nouvelles IP */
    '92.154.6.35', // IP Chamrousse
    '172.30.101.8', // ip phil
    '172.30.101.34', // ip yann
    '172.30.101.9', // ip xav
    '172.30.101.16', // ip herve
    '172.30.102.59', // ip charline
    '172.30.102.60', // ip charline 2
    '172.30.110.14', // ip bruno
    '172.31.1.26', // ip phil home via vpn
    '172.20.0.1' // docker
);

if (!in_array(@$_SERVER['SERVER_NAME'], $whiteList)) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file('.$_SERVER['REMOTE_ADDR'].'). Check ' . basename(__FILE__) . ' for more information.');
}

// verfification existance conf du sous domaine
if (getenv('PARCNAME') and ! file_exists(__DIR__ . '/../app/clients/config/config_' . getenv('PARCNAME') . '.yml')) {
    header('Location: http://www.postparc.fr');
}

/**
 * @var Composer\Autoload\ClassLoader $loader
 */
$loader = require __DIR__ . '/../app/autoload.php';
Debug::enable();

$kernel = new AppKernel(getenv('PARCNAME') ? str_replace('.', '_', getenv('PARCNAME')) : 'dev', true);
//$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);

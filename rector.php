<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Rector\Core\ValueObject\PhpVersion;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Rector\Symfony\Set\SymfonySetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(SymfonySetList::SYMFONY_34);
    $containerConfigurator->import(SetList::CODE_QUALITY);
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // Define what rule sets will be applied

    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_73);
    
    $parameters->set(
        Option::SYMFONY_CONTAINER_XML_PATH_PARAMETER,
        __DIR__ . '/var/cache/dev/appDevDebugProjectContainer.xml'
    );
    
    // get services (needed for register a single rule)
    $services = $containerConfigurator->services();

    // register a single rule
    $services->set(TypedPropertyRector::class);
};

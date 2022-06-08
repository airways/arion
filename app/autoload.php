<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

// If CLI and no SYMFONY_ENV, we're in PHPUNIT maybe, so don't init Rollbar
if(php_sapi_name() != 'cli' || getenv('SYMFONY_ENV'))
{

    Rollbar::init(array(
        'access_token' => 'XXXXXXXXXX8',
        'environment' => getenv('SYMFONY_ENV') ? getenv('SYMFONY_ENV') : 'dev',
    ));
}

return $loader;

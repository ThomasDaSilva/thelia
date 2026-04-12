<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Composer\Autoload\ClassLoader;
use Symfony\Component\Dotenv\Dotenv;

/** @var ClassLoader $loader */
$loader = require dirname(__DIR__).'/vendor/autoload.php';

$propelCacheDir = dirname(__DIR__).'/var/propel/test/model';
if (is_dir($propelCacheDir)) {
    $loader->addPsr4('', $propelCacheDir);
    $loader->addPsr4('TheliaMain\\', dirname(__DIR__).'/var/propel/test/database/TheliaMain');
}

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// In test mode, Symfony Dotenv skips .env.local by design (test isolation).
// However, the database connection is typically the same in dev and test.
// Bridge DATABASE_* vars from .env.local when they are not already defined
// (by CI env, phpunit.xml, .env.test, or .env.test.local).
if (empty($_SERVER['DATABASE_HOST'])) {
    $envLocalFile = dirname(__DIR__).'/.env.local';
    if (file_exists($envLocalFile)) {
        $localVars = (new Dotenv())->parse(file_get_contents($envLocalFile));
        $dbKeys = ['DATABASE_HOST', 'DATABASE_PORT', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASSWORD'];
        foreach ($dbKeys as $key) {
            if (isset($localVars[$key]) && empty($_SERVER[$key])) {
                $_SERVER[$key] = $_ENV[$key] = $localVars[$key];
            }
        }
    }
}

if ($_SERVER['APP_DEBUG']) {
    umask(0o000);
}

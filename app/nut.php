<?php
/*
 * This could be loaded on a very old version of PHP so no syntax/methods over 5.2 in this file.
 */

$minVersion = '5.5.9';
if (version_compare(PHP_VERSION, $minVersion, '<')) {
    echo sprintf("\033[37;41mBolt requires PHP \033[1m%s\033[22m or higher. You have PHP \033[1m%s\033[22m, so Bolt will not run on your current setup.\033[39;49m%s", $minVersion, PHP_VERSION, PHP_EOL);
    exit(1);
}

/** @var \Silex\Application $app */
$app = require __DIR__ . '/../vendor/bolt/bolt/app/bootstrap.php';
$app->boot();

/** @var \Symfony\Component\Console\Application $nut Nut Console Application */
$nut = $app['nut'];
$nut->run();

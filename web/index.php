<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = new \App\Application();

// register global error handler
$app->error(function (\Exception $e, $code) use ($app) {
    return $app->json(array("error" => $e->getMessage()), $code);
});

// register providers
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// configure app
$app['debug'] = false;

// mount modules
$app->mount('/v1/', new \App\Controller\OrdersController());

$app->run();

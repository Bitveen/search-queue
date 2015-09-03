<?php
require 'vendor/autoload.php';

$app = new \Slim\Slim();
$loader = new Twig_Loader_Filesystem(__DIR__.'/views');
$twig = new Twig_Environment($loader, array(
    'cache' => false
));

$app->get('/', function() use($twig) {
    echo $twig->render('base.html');
});

$app->post('/', function() use($app, $twig) {

    $regNumber = $app->request->post('regNumber');

    if ($regNumber) {
        // запрос к базе
    }




});




$app->run();

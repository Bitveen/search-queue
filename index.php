<?php
require 'vendor/autoload.php';

$config = require_once 'config.php';

/* функция для разбора и вставки записей в бд */
function insertValues($result, mysqli $connection) {
    $query = "";
    foreach ($result as $item) {
        $item = trim($item);
        if (!$item) {
            $query .= "NULL ";
            continue;
        }
        $query .= "$item ";
    }
    $query = str_replace(' ', ',', trim($query));

    if ($connection->query("INSERT INTO rest_data VALUES ($query)")) {
        return true;
    }
    return false;
}


$app = new \Slim\Slim();
$loader = new Twig_Loader_Filesystem(__DIR__.'/views');
$twig = new Twig_Environment($loader, array(
    'cache' => false //'./views/cache'
));



$app->get('/', function() use($twig) {
    echo $twig->render('base.html');
});
$app->post('/', function() use($app, $twig, $config) {

    $regNumber = $app->request->post('regNumber');
    $data = array();

    if (is_numeric($regNumber)) {
        try {
            $connection = new mysqli($config['host'], $config['db_user'], $config['db_password'], $config['db_name'], $config['port']);

            if (!$connection) {
                throw new Exception('Ошибка подключения к базе данных.');
            }

            $stmt = $connection->prepare("SELECT * FROM rest_data WHERE id=?");

            if (!$stmt) {
                throw new Exception('Ошибка запроса.');
            }

            $stmt->bind_param("i", $regNumber);
            $stmt->execute();

            $stmt->bind_result(
                $data['id'],
                $data['breath'],
                $data['digestion'],
                $data['sight'],
                $data['nervous_system'],
                $data['urogenital_system'],
                $data['blood_circulation'],
                $data['skin_diseases']
            );

            $stmt->fetch();
            $stmt->close();

        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }




    echo $twig->render('result.html', array(
        'data' => $data
    ));






});



$app->get('/upload', function() use($app, $twig, $config) {

    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        $app->response->headers->set('WWW-Authenticate', 'Basic');
        $app->response->status(401);
        return $app->response();
    }

    if ($_SERVER['PHP_AUTH_USER'] === $config['auth_login'] && $_SERVER['PHP_AUTH_PW'] === $config['auth_password']) {
        echo $twig->render('upload.html');
    }

});


$app->post('/upload', function() use($app, $twig, $config) {

    if (isset($_FILES['document']) && !empty($_FILES['document']['name'])) {

        if ($_FILES['document']['type'] === 'text/csv') {
            try {
                $handler = fopen($_FILES['document']['tmp_name'], 'r');

                if (!$handler) {
                    throw new Exception('Невозможно открыть файл.');
                }

                $connection = new mysqli($config['host'], $config['db_user'], $config['db_password'], $config['db_name'], $config['port']);

                if (!$connection) {
                    throw new Exception('Невозможно установить связь с БД.');
                }

                $connection->query("DELETE FROM rest_data");

                while ($result = fgetcsv($handler, null, ';')) {

                    if (count($result) != 8) {
                        continue;
                    }

                    if (!is_numeric($result[0])) {
                        continue;
                    }

                    if (!insertValues($result, $connection)) {
                        echo $twig->render('upload.html', array('status' => 'Ошибка при обработке файла.'));
                        exit;
                    }
                }


                echo $twig->render('upload.html', array('status' => 'Файл успешно загружен.'));


            } catch (Exception $e) {
                echo $e->getMessage();
            }


        } else {
            echo $twig->render('upload.html', array('status' => 'Неверный формат файла.'));
        }

    } else {
        echo $twig->render('upload.html');
    }

});



$app->run();

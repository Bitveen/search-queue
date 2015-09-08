<?php
require 'vendor/autoload.php';

$config = require_once 'config.php';

/* функция для разбора и вставки записей в бд */
function insertValues($result, mysqli $connection) {
    $query = "";
    foreach ($result as $item) {
        $item = trim($item);
        if ($item == '-') {
            $query .= "NULL ";
            continue;
        }
        $query .= "$item ";
    }
    $query = str_replace(' ', ',', trim($query));

    $connection->query("INSERT INTO rest_data VALUES ($query)");
}


$app = new \Slim\Slim();
$loader = new Twig_Loader_Filesystem(__DIR__.'/views');
$twig = new Twig_Environment($loader, array(
    'cache' => './views/cache'
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

    if (isset($_FILES['document'])) {

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

                while ($result = fgetcsv($handler)) {
                    // первый элемент - айдишник

                    if (count($result) != 8) {
                        continue;
                    }

                    $id = $result[0];

                    $check = $connection->query("SELECT * FROM rest_data WHERE id=$id");

                    if ($check && $check->num_rows == 0) {

                        insertValues($result, $connection);

                    } else if ($check && $check->num_rows == 1) {

                        $connection->query("DELETE FROM rest_data WHERE id=$id");

                        insertValues($result, $connection);

                    }

                }



            } catch (Exception $e) {
                echo $e->getMessage();
            }






        }

    }

    echo $twig->render('upload.html', array('status' => 'Файл успешно загружен.'));

});



$app->run();

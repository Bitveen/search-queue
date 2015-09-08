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

$app->get('/upload', function() use($app, $twig) {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        $app->response->headers->set('WWW-Authenticate', 'Basic');
        $app->response->status(401);
        return $app->response();
    }

    if ($_SERVER['PHP_AUTH_USER'] == 'megauploader' && $_SERVER['PHP_AUTH_PW'] == 'givemeupload') {
        echo $twig->render('upload.html', array('status' => -1));
    }




});

$app->post('/upload', function() use($app, $twig) {

    if (isset($_FILES['document']) && !empty($_FILES['document'])) {
        // обработка CSV файла

        if ($_FILES['document']['type'] == 'text/csv') {
            $handler = fopen($_FILES['document']['tmp_name'], 'r');
            $connection = new mysqli('localhost', 'root', 'root', 'search_q', '8889');

            while ($result = fgetcsv($handler)) {
                // первый элемент - айдишник
                $id = $result[0];

                //$action = 'Create';

                $check = $connection->query("SELECT * FROM rest_data WHERE id=$id");

                if ($check && $check->num_rows == 0) {
                    //$action = 'Create';
                    // 8 ячеек вставить

                    $query = "";
                    foreach ($result as $item) {
                        $item = trim($item);
                        if ($item == '-') {
                            // записываем NULL
                            $query .= "NULL ";
                            continue;
                        }
                        $query .= "$item ";
                    }
                    $query = str_replace(' ', ',', trim($query));

                    $connection->query("INSERT INTO rest_data VALUES ($query)");



                } else if ($check && $check->num_rows == 1) {
                    //$action = 'Update';

                    $connection->query("DELETE FROM rest_data WHERE id=$id");

                    $query = "";
                    foreach ($result as $item) {
                        $item = trim($item);
                        if ($item == '-') {
                            // записываем NULL
                            $query .= "NULL ";
                            continue;
                        }
                        $query .= "$item ";
                    }
                    $query = str_replace(' ', ',', trim($query));

                    $connection->query("INSERT INTO rest_data VALUES ($query)");


                }





            }
            $status = 0;
        } else {

            $status = 2;

        }



    } else {

        $status = 1;

    }

    echo $twig->render('upload.html', array('status' => $status));


});


$app->post('/', function() use($app, $twig) {

    $regNumber = $app->request->post('regNumber');

    $data = array();

    if (is_numeric($regNumber)) {
        try {
            $connection = new mysqli('localhost', 'root', 'root', 'search_q', '8889');

            if (!$connection) {
                throw new Exception('Ошибка подключения к базе данных.', 2);
            }

            $stmt = $connection->prepare("SELECT * FROM rest_data WHERE id=?");

            if (!$stmt) {
                throw new Exception('Ошибка создания подготовленного запроса', 3);
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


        }

    }











    echo $twig->render('result.html', array(
        'data' => $data
    ));






});




$app->run();

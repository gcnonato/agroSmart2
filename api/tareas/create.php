<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../php-jwt/src/BeforeValidException.php';
require_once '../php-jwt/src/ExpiredException.php';
require_once '../php-jwt/src/SignatureInvalidException.php';
require_once '../php-jwt/src/JWT.php';
include_once '../config/database.php';
include_once '../object/tareas.php';
include_once '../object/jwt.php';
include_once '../vendor/autoload.php';

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

$data = json_decode(file_get_contents("php://input"));

if(isset($data->jwt)){
    $validToken = new myjwt();
    $validToken->jwt = $data->jwt;
    $token=$validToken->tokenlife();
    if($token){
        $database = new Database();
        $db=$database->getConnection();
        $tareas = new tareas($db);
        if(
            !empty($data->tarea) &&
            !empty($data->inicio) &&
            !empty($data->fin) &&
            !empty($data->estado) || $data->estado==0 &&
            !empty($data->channel)
        ){
            $tareas->tarea=$data->tarea;
            $tareas->idparcela=$data->idparcela;
            $tareas->inicio=$data->inicio;
            $tareas->fin=$data->fin;
            $tareas->estado=$data->estado;
            if($tareas->create()){
                $client = new Client(new Version2X('http://localhost:3000'));
                $client->initialize();
                $client->emit('chat:channel', ['channel' => $data->channel]);
                $client->emit('tarea:create', [
                    'id'=> $tareas->id,
                    'tarea' => $tareas->tarea,
                    'inicio' => $tareas->inicio,
                    'fin'=> $tareas->fin,
                    'estado'=> $tareas->estado
                ]);
                $client->close();
                http_response_code(201);
                echo json_encode(array("message"=>"Tarea creada.", "id"=>$tareas->id));
            }else{
                http_response_code(503);
                echo json_encode(array("message"=>"Tarea no creada"));
            }
        }else{
            http_response_code(400);
            echo json_encode(array("message"=>"Data incompleta."));
        }
    }else{
        http_response_code(401);
        echo json_encode(array("message"=>"no autorizado"));
    }
}else{
    http_response_code(400);
    echo json_encode(array("message"=>"sesion no iniciada."));
}


?>

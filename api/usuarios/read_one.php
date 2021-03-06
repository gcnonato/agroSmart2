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
include_once '../object/usuarios.php';
include_once '../object/jwt.php';


$data = json_decode(file_get_contents("php://input"));

if(isset($data->jwt)){
    $validToken = new myjwt();
    $validToken->jwt = $data->jwt;
    $token=$validToken->tokenlife();
    if($token && $validToken->nivel==0 && isset($data->id)){
        $database = new Database();
        $db = $database->getConnection();
        $usuarios = new usuarios($db);
        $usuarios->id=$data->id;
        $usuarios->readOne();
        if($usuarios->nombre!=null){
            $usuarios_arr = array(
                "id"=>$usuarios->id,
                "nombre"=>$usuarios->nombre,
                "apellido"=>$usuarios->apellido,
                "email"=>$usuarios->email,
                "nivel"=>$usuarios->nivel
            );
            http_response_code(200);
            echo json_encode($usuarios_arr);
        }else{
            http_response_code(404);
            echo json_encode(array("message"=>"usuario no existente."));
        }
    }else{
        http_response_code(401);
        echo json_encode(array("message"=>"no autorizado o datos incompletos."));
    }
}else{
    http_response_code(400);
    echo json_encode(array("message"=>"sesion no iniciada."));
}
?>
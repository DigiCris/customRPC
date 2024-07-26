<?php
$autentificado = false;
/*
Todas las condiciones de autentificacion que quieras
*/
$request_body = file_get_contents('php://input');
$request_data = json_decode($request_body, true);

session_start();
if(isset($_SESSION['rpcAuth'])) {
    if($_SESSION['rpcAuth']>0) {
        $_SESSION['rpcAuth'] --;
        $autentificado = true;
    }
}

if(!$autentificado) {
    $result = array(
        'jsonrpc' => '2.0',
        'id' => $request_data['id'],
        'error' => array(
            'code' => -32600,
            'message' => 'Must be authenticated!'
        )
    );
    echo json_encode($result);
    exit();    
}
?>
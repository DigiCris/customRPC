<?php

include_once "./conditionRPC.php";

// Cargar el archivo .env
$dotenv = parse_ini_file('.env');

// Array de métodos RPC permitidos
$allowed_methods = array('eth_blockNumber');

// Filtrar las variables de entorno que comienzan con "RPC_NODE"
$alchemy_node_urls = array_filter($dotenv, function($key) {
    return strpos($key, 'RPC_NODE') === 0;
}, ARRAY_FILTER_USE_KEY);

// Crear el array de URLs de Alchemy
$alchemy_node_url = array_values($alchemy_node_urls);
$nodes_quantity = count($alchemy_node_url);


$request_body = file_get_contents('php://input');
$request_data = json_decode($request_body, true);

// Verificar si se trata de una solicitud RPC
if (isset($request_data['jsonrpc']) && $request_data['jsonrpc'] === '2.0' && isset($request_data['method'])) {
    // Obtener el método RPC solicitado
    $rpc_method = $request_data['method'];

    // Verificar si el método RPC está permitido
    if (in_array($rpc_method, $allowed_methods)) {
        // Inicializar el número de intentos
        $max_attempts = $nodes_quantity;
        $attempt = 0;
        $success = false;

        // Bucle para realizar varios intentos
        while ($attempt < $max_attempts && !$success) {
            // Reenviar la solicitud RPC al nodo de Alchemy
            $ch = curl_init($alchemy_node_url[$attempt]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($request_body)
            ));
            $alchemy_response = curl_exec($ch);
            $alchemy_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Verificar si la respuesta fue exitosa
            if ($alchemy_status == 200) {
                $success = true;
                // Devolver la respuesta de Alchemy
                http_response_code($alchemy_status);
                echo $alchemy_response;
            } else {
                // Incrementar el número de intentos
                $attempt++;
                // Esperar un tiempo antes de intentar de nuevo. de 0 a 0.1s. Pensado para nodos de distinto proveedor, sino deberíamos quizá poner 1s+random(0s-1s)
                $random_delay = mt_rand(0, 100000) / 1000000;
                sleep($random_delay);
            }
        }

        
        if (!$success) {
            // Si no se pudo completar la solicitud después de varios intentos
            http_response_code(500);
            echo json_encode($alchemy_response);
        }
    } else {
        // Método RPC no permitido
        http_response_code(403);
        $response = array(
            'error' => array(
                'code' => -32601,
                'message' => 'Not permited RPC method'
            ),
            'id' => $request_data['id']
        );
        echo json_encode($response);
    }
} else {
    // No es una solicitud RPC válida
    http_response_code(400);
    $response = array(
        'error' => array(
            'code' => -32600,
            'message' => 'invalid RPC request'
        )
    );
    echo json_encode($alchemy_response);
}
?>
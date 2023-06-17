<?php
require_once 'config.php';

use src\Classes\API;
if (!preg_match('/Basic\s([0-9a-z=]+)/is', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Token not found in request';
    exit;
}

if (count($_FILES) > 0) {
    $data = json_decode(file_get_contents($_FILES['commonInfo']['tmp_name']));
    $uri = $_SERVER['REQUEST_URI'];
    $path = explode('/', substr($uri, 1));
    if ($path[0] == 'api' && $path[1] == 'v2.3') {
        $action = filter_var($path[2], FILTER_SANITIZE_STRING);
        $api = new API($action, $data, $matches[1]);
    } else {
        parseErrors($path);
    }
} else {
    parseErrors('NoCommonInfo');
}

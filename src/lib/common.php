<?php
function loadClasses($class)
{
    $cDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $class) . '.php';
    include $cDir;
}

spl_autoload_register('loadClasses');


function logIt($msg, $tag = "LOG", $file = 'log.txt')
{
    $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs';
    @mkdir($logDir);
    if (is_array($msg) || is_object($msg)) $msg = print_r($msg, 1);
    file_put_contents($logDir . DIRECTORY_SEPARATOR . $file, date(DATE_W3C) . ' [' . $tag . '] ' . $msg . "\n", FILE_APPEND);
}

function parseErrors($data){
    // todo: Обработка ошибок не подпадающих под нужные параметры
}
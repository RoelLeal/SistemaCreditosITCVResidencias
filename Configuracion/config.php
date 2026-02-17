<?php
$protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

if ($host == 'localhost' || $host == '127.0.0.1') {
    if (!defined('BASE_URL'))
        define('BASE_URL', $protocolo . "://" . $host . "/SistemaCreditos/");
} else {
    if (!defined('BASE_URL'))
        define('BASE_URL', $protocolo . "://" . $host . "/");
}
?>
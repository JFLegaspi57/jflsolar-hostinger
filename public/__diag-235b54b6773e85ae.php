<?php
// Temporary: which request headers reach PHP behind Hostinger's CDN. Remove after use.
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');
echo 'REMOTE_ADDR=', $_SERVER['REMOTE_ADDR'] ?? '', "\n";
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_') && ! in_array($k, ['HTTP_COOKIE', 'HTTP_AUTHORIZATION'], true)) {
        echo $k, '=', is_string($v) ? substr($v, 0, 120) : '', "\n";
    }
}

<?php

$scriptdir = dirname(__FILE__);

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    require $scriptdir . '/WEB-INF/views/selector.inc';
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: GET');
    echo 'Unsupported request method';
}

?>

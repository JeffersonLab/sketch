<?php

$scriptdir = dirname(__FILE__);
require $scriptdir . '/WEB-INF/functions/view-lib.inc';
require $scriptdir . '/WEB-INF/classes/DiagramFactory.php';

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    try {

        if(!array_key_exists('hostname', $_GET) || trim($_GET['hostname']) == "") {
            throw new Exception("A CED Hostname must be specified");
        }

        if(!array_key_exists('zone', $_GET) || trim($_GET['zone']) == "") {
            throw new Exception("A Zone must be specified");
        }

        $cedHostname = $_GET['hostname'];
        $zoneName = $_GET['zone'];
        $workspaceName = array_key_exists('workspace', $_GET) ? $_GET['workspace'] : null;
        $isProperties = (array_key_exists('properties', $_GET) && $_GET['properties'] == 'Y');
        $isCluster = (array_key_exists('cluster', $_GET) && $_GET['cluster'] == 'Y');
        $isConnect = (array_key_exists('connect', $_GET) && $_GET['connect'] == 'Y');
        $isLinkCed = (array_key_exists('link', $_GET) && $_GET['link'] == 'Y');
        $isPaginate = true;

        $factory = new \sketch\DiagramFactory($cedHostname, $isProperties, $isCluster, $isConnect, $isPaginate, $isLinkCed);
        $diagram = $factory->getDiagramFromJson($zoneName, $workspaceName);

        $scriptdir = dirname(__FILE__);
        require $scriptdir . '/WEB-INF/views/book-view.inc';
    } catch(Exception $e) {
        echo '<html><head><title>Error</title></head><body>Unable to generate SVG: ' . \sketch\encode($e->getMessage()) . '</body></html>';
        error_log("Unable to generate HTML book");
        error_log($e);
    }
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: GET');
    echo 'Unsupported request method';
}

?>

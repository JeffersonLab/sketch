<?php

$scriptdir = dirname(__FILE__);
require $scriptdir . '/WEB-INF/functions/view-lib.inc';
require $scriptdir . '/WEB-INF/classes/DiagramFactory.php';

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    header('Content-Type: image/svg+xml; charset=utf-8');
    try {

        if(!array_key_exists('zone', $_GET) || trim($_GET['zone']) == "") {
            throw new Exception("A Zone must be specified");
        }

        if(!array_key_exists('hostname', $_GET) || trim($_GET['hostname']) == "") {
            throw new Exception("A CED hostname must be specified");
        }

        $zoneName = $_GET['zone'];
        $cedHostname = $_GET['hostname'];
        $workspaceName = array_key_exists('workspace', $_GET) ? $_GET['workspace'] : null;
        $isProperties = (array_key_exists('properties', $_GET) && $_GET['properties'] == 'Y');
        $isCluster = (array_key_exists('cluster', $_GET) && $_GET['cluster'] == 'Y');
        $isConnect = (array_key_exists('connect', $_GET) && $_GET['connect'] == 'Y');
        $isLinkCed = (array_key_exists('link', $_GET) && $_GET['link'] == 'Y');
        $isPaginate = false;

        $factory = new \sketch\DiagramFactory($cedHostname, $isProperties, $isCluster, $isConnect, $isPaginate, $isLinkCed);
        $diagram = $factory->getDiagramFromJson($zoneName, $workspaceName);

        $scriptdir = dirname(__FILE__);
        require $scriptdir . '/WEB-INF/views/diagram-view.inc';
    } catch(Exception $e) {
        echo '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 800 600" width="800" height="600"><text x="50" y="50">Unable to generate SVG: ' . \sketch\encode($e->getMessage()) . '</text></svg>';
        error_log("Unable to generate SVG");
        error_log($e);
    }
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: GET');
    echo 'Unsupported request method';
}

?>

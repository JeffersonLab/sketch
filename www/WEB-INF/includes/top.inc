<?php 

$scriptdir = dirname(__FILE__);
require $scriptdir . '/../functions/view-lib.inc';

$contextPath = '/sketch';
$resourceVersion = 1;

 ?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Sketch</title>
        <link rel="shortcut icon" href="<?php echo $contextPath ?>/resources/img/favicon.ico"/>
        <link rel="stylesheet" type="text/css" href="//ace.jlab.org/cdn/jquery-ui/1.10.3/theme/smoothness/jquery-ui.min.css"/>
        <link rel="stylesheet" type="text/css" href="//ace.jlab.org/cdn/jlab-theme/smoothness/1.4/css/smoothness.min.css"/>
        <?php
            if(isset($stylesheet_array)) {
                foreach($stylesheet_array as $sheet) {
                    echo '<link rel="stylesheet" type="text/css" href="' . $contextPath . '/resources/css/' . $sheet . '?v=' . $resourceVersion  . '"/>' . "\n";
                }
            }
        ?>
    </head>
    <body>
        <div id="page">
            <header>
                <h1>
                    <span id="page-header-logo"></span>
                    <span id="page-header-text">Sketch</span>
                </h1>
                <div id="auth">
                    <div id="username-container"><?php \sketch\out(array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : ""); ?></div>
                </div>
                <div id="primary-nav">
                </div>
            </header>
            <div id="content">
                <div id="content-liner">

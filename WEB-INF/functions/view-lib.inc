<?php
namespace sketch;

function encode($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function out($string) {
    echo encode($string);
}
?>

<?php

$request_url = explode('/r/', str_replace('?', '&', $_SERVER['REQUEST_URI']) );
$subfolder = array_shift($request_url);
$params = array_shift($request_url);

// Internal rewriting so wordpress will think that we are accessing this page
$_SERVER['REQUEST_URI'] = "{$subfolder}/wpmvc/?{$params}";

include 'index.php';
exit;
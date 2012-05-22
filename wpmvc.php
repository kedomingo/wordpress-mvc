<?php

/*
Plugin Name: WP MVC
Plugin URI: 
Description: Wordpress MVC Framework
Version: 0.0.1
Author: Kyle Domingo
*/

$this_plugin = preg_replace('/^.+\/([^\/]+\/[^\/]+)$/', '$1', str_replace('\\', '/', __FILE__));

if ( !(strpos($_SERVER['REQUEST_URI'], 'plugins.php')) and strpos($_SERVER['REQUEST_URI'], '%2F') > 0 )
{
    header('Location: '.str_replace('%2F', '/', $_SERVER['REQUEST_URI']));
    exit;
}

//ini_set('display_errors', true);
//error_reporting(E_ALL);

if ( !session_id() ) session_start();


require_once 'includes/hooks.php';
require_once 'includes/autoload.php';
require_once 'includes/globals.php';

wpmvc_autoload_register( __FILE__ );
WPMVC::add_route('', '/<controller>(/<action>(/<id>))');

<?php

if ( !defined('DS') )
    define('DS', DIRECTORY_SEPARATOR); 
    

if(!defined('WPMVC_PLUGIN_DIR'))
{
    define('WPMVC_PLUGIN_DIR', str_replace('/', DS, WP_PLUGIN_DIR.DS.array_pop(explode(DS, str_replace('/', DS, realpath(dirname(__FILE__).'/..'))))));
    define('WPMVC_PLUGIN_URL', WP_PLUGIN_URL.'/'.array_pop(explode('/', str_replace(DS, '/', realpath(dirname(__FILE__).'/..')))));
}

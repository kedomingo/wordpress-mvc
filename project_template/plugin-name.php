<?php

/*
Plugin Name: {PLUGIN_NAME}
Plugin URI:  {PLUGIN_URI}
Description: {PLUGIN_DESCRIPTION}
Version:     {PLUGIN_VERSION}
Author:      {PLUGIN_AUTHOR}
Author URI:  {PLUGIN_AUTHOR_URI}
License:     
*/

# DO NOT REMOVE THIS
#
if (!class_exists('WPMVC'))
    return;
WPMVC::register( __FILE__ );

# When you are using your own namespace, or if you want to add
# additional routes aside from the default, it is necessary to add
# routes SPECIFIC to your controllers:
#
# Route::add('va_affiliates', '/products(/<action>(/<id>))', array('controller' => 'products'));
# Route::add('va_affiliates', '/affiliates(/<action>(/<id>(/<filter>)))', array('controller' => 'affiliates'), array('filter'=>'pending|paid|void'));
# ...

# Include your library files here:
# 
# require 'lib/custom_functions.php';
# require 'lib/foxycart/Foxycart.php';
# ...

# DO NOT REMOVE THESE
#
require 'includes/globals.inc.php';
require 'includes/hooks.inc.php';
require 'includes/filters.inc.php';
require 'includes/shortcodes.inc.php';
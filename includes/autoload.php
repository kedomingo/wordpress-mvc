<?php


function wpmvc_autoload_register( $main_plugin_file )
{
    spl_autoload_register( function($class) use ($main_plugin_file) {

        // remove namespace
        $class = preg_replace('/^.*\\\/', '', $class);
        
        // For helpers, class names end in "Helper" but file names do not have that
        $helper_class = preg_replace('/Helper$/', '', $class);
        
        /* CORE */
        if ( file_exists( dirname($main_plugin_file)."/core/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/core/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/core/model/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/core/model/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/core/model/behaviors/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/core/model/behaviors/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/core/view/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/core/view/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/core/view/helpers/{$helper_class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/core/view/helpers/{$helper_class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/core/controller/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/core/controller/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/core/controller/components/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/core/controller/components/{$class}.php";
            return;
        }
        
        /* User defined */
        elseif ( file_exists( dirname($main_plugin_file)."/classes/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/classes/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/classes/models/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/classes/models/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/classes/models/behaviors/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/classes/models/behaviors/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/classes/views/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/classes/views/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/classes/views/helpers/{$helper_class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/classes/views/helpers/{$helper_class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/classes/controllers/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/classes/controllers/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/classes/controllers/components/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/classes/controllers/components/{$class}.php";
            return;
        }
        elseif ( file_exists( dirname($main_plugin_file)."/classes/widgets/{$class}.php" ) )
        {
            require_once dirname($main_plugin_file)."/classes/widgets/{$class}.php";
            return;
        }
    });
}

/* End of file autoload.php */
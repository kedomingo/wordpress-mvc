<?php

class View {
    
    var $route;
    var $controller;
    
    var $errors = array();
    
    /* SETTINGS */
    /* Render the view immediately */
    static $immediate_output = false;
    
    /* Exits after immediate output */
    static $stop_after_render = false;
    
    /* output only the output generated by the controller (do not include the view file) */
    static $controller_output_only = false;
    
    static $controller_output;
    static $view_file;
    
    static $view_vars = array();
    static $helpers = array();
    
    public static function factory( /* Route */ $route = null )
    {
        if (
            !empty($route) and
            (
                empty($route->callback['controller'])
                or empty($route->callback['action'])
                or !is_string($route->callback['controller'])
                or !is_string($route->callback['action'])
            )
        )
            throw new Exception( _('Route is not defined properly') );
        
        static::$view_file = WP_PLUGIN_DIR."/{$route->namespace}/views/{$route->callback['controller']}/{$route->callback['action']}.html";
        $view = new View();
        return $view;
    }
    
    public static function render()
    {
        echo static::$controller_output;
        
        if (static::$controller_output_only)
            return;
        
        static::$view_file = str_replace(array('\\', '/'), array('/', DS), static::$view_file);
        $view_file = realpath( static::$view_file );
        if ( empty( $view_file ) )
        {
            echo '<div class="wrap"><h2>Oops!</h2><p>'._('Could not find view for this action. Expecting')." : ".static::$view_file.'</p></div>';
            return;
        }
        extract( static::$view_vars );
        extract( static::$helpers );
        
        include_once $view_file;        
    }
    
    public static final function set_controller_output( $controller_output )
    {
        static::$controller_output = $controller_output;
    }
    
    public final function copy_controller_helpers( )
    {
        // Copy helpers
        if ( !empty($this->controller->helpers) and is_array($this->controller->helpers) )
        {
            static::$helpers = array();
        
            foreach( $this->controller->helpers as $k => $helper )
            {
                static::$helpers[ Inflector::underscore($helper) ] = $this->controller->$helper;
            }
        }
        
    }
    
    public final function set_controller( $controller )
    {
        $this->controller = $controller;
    }
    
    public function set( $var, $val, $__origin = null )
    {
        // 
        // For template setting, make sure the view knows where the template is
        //        
        if ( $var == 'template' and $__origin == null )
        {
            // Get the plugin root of the calling class
            $trace = array_shift(array_shift(debug_backtrace()));
            if(preg_match('/^.+plugins.?[^\/\\\]+/', $trace, $matches))
            {
                $__origin = array_shift($matches);
            }
        }
    
        switch( $var )
        {
            case 'title':
            
                // Change browser title bar
                add_filter('wp_title', function($title) use ($val) {
                    return $val;
                }, '');
                
                // Change post title
                add_filter('the_title', function($title) use ($val) {
                    return $val;
                });
                
                // TODO override titles enforced by several SEO plugins like SEO Ultimate, All-in-one SEO, etc
                
                break;
                
            case 'template':
                
                // Change the page template (page_template uses absolute file name of tempalte name)
                add_filter('page_template', function($template) use ($val, $__origin){
                    $new_template = preg_replace('/^(.+[\/\\\]+)([a-zA-Z0-9]+)(\.[a-z]+)$/', "\$1{$val}\$3", $template);
                    if( ! file_exists($new_template) )
                    {
                        $new_template = "{$__origin}/views/templates/{$val}.php";
                    }
                    if( !file_exists($new_template) )
                    {
                        echo sprintf( _('Template file missing. Expecting file %s'), $new_template );
                        return $template;
                    }
                    return $new_template;
                });
            
                break;
                
            default :
                static::$view_vars[ $var ] = $val;
        }
        
    }
    
}
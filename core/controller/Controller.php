<?php

abstract class Controller {
    
    var $route;
    var $view;
    
    /* Settings */
    var $autorender = true;
    
    /** models */
    var $uses;
    
    /** components */
    var $components;
    
    /** helpers */
    var $helpers;
    
    var $error = null;
    
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
        
        // Camelize the underscored controller
        $namespace  = !empty( $route->namespace ) ? "\\{$route->namespace}" : '';
        $controller = "{$namespace}\\".implode('', explode(' ', ucwords(implode(' ', explode('_', $route->callback['controller']))))).'Controller';
        
        if ( class_exists( $controller ) )
        {
            $controller = new $controller;
            $controller->route = $route;
        }
        else{
            static::__controller_not_found( $controller );
            return false;
        }
        return $controller;
    }
    
    function set_view( $view )
    {
        $this->view = $view;
    }
    
    /**
     * Triggers the action defined in the matched route
     */
    public final function action( )
    {
        $this->action = $this->route->callback['action'];
        // ------------------------------------------------------------
    
        // nested ob_start with wordpress        
        ob_start();
        $this->before_filter();
        
        // ------------------------------------------------------------
        $this->__initialize();
        
        if ( method_exists( $this, $this->route->callback['action'] ) )
        {
            call_user_func_array( array($this, $this->route->callback['action']), $this->route->parameters );
        }
        else
        {
            $this->__action_not_found();
            $this->error = _( sprintf('Action %s not found for controller %s', $this->route->callback['action'], get_class($this0)) );
        }
        
        $s = ob_get_contents();
        ob_end_clean();
                
        View::set_controller_output( $s );
        
        // ------------------------------------------------------------
        
        $this->after_filter();
    }
    
    /**
     * Alias to WPMVC::redirect
     */
    public final function redirect( $url )
    {
        WPMVC::redirect( $url );
    }
    
    /**
     * Alias for View::set
     */
    public final function set( $key, $value )
    {
        $origin = null;
        
        // 
        // For template setting, make sure the view knows where the template is
        //        
        if ( $key == 'template' )
        {
            // Get the plugin root of the calling class
            $trace = array_shift(array_shift(debug_backtrace()));
            if(preg_match('/^.+plugins.?[^\/\\\]+/', $trace, $matches))
            {
                $origin = array_shift($matches);
            }
        }
    
        $this->view->set( $key, $value, $origin );
    }
    
    public final function get_namespace()
    {
        if ( !empty($this->route->namespace) )
        {
            return $this->route->namespace;
        }
        // else
        $reflectionClass = new ReflectionObject($this);
        $filename = $reflectionClass->getFileName();        
        $namespace = array_shift( explode(DS, str_replace( str_replace('/', DS, WP_PLUGIN_DIR).DS, '', $reflectionClass->getFileName())));
        return $namespace;
    }
    
    private function __initialize()
    {
        if ( empty($this->namespace) )
        {
            $this->namespace = $this->get_namespace();
        }
        
        // Autoload models
        if ( !empty($this->uses) and is_array($this->uses) )
        {
            foreach( $this->uses as $model )
            {
                try
                {
                    $m = Model::factory( $model );
                    // remove namespace
                    $model = preg_replace('/^.*\\\/', '', $model);
                    $this->$model = $m;
                    $this->$model->set_controller( $this );
                }
                catch(Exception $e)
                {
                    // now, what do???
                    throw $e;
                }
            }
        }
        
        // Autoload helpers
        if ( !empty($this->helpers) and is_array($this->helpers) )
        {
            foreach( $this->helpers as $k => $helper )
            {
                try
                {
                    $h = Helper::factory( $helper );
                    $this->$helper = $h;
                }
                catch(Exception $e)
                {
                    // now, what do???
                    throw $e;
                }
            }
        }
    }
    
    protected function before_filter(){}
    
    protected function after_filter(){}
    
    
    private static function __controller_not_found( $controller )
    {
        include WPMVC_PLUGIN_DIR.DS.'includes'.DS.'templates'.DS.'controller_not_found.html';
    }
    private function __action_not_found()
    {
        include WPMVC_PLUGIN_DIR.DS.'includes'.DS.'templates'.DS.'action_not_found.html';
    }
    
}
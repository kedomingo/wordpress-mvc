<?php

class WPMVC {
    
    /**
     * Alias for wpmvc_autoload_register
     */
    public static function register( $file )
    {
        wpmvc_autoload_register( $file );
    }
    
    /**
     * Alias for Route::add
     */
    public static function add_route( $namespace, $pattern, $callback = null )
    {
        return Route::add( $namespace, $pattern, $callback );
    }
    
    
    public static function bootstrap( )
    {
        static::request( );
    }
    
    public static function request($url = null)
    {
        $route = Route::match( $url );
        
        if ( ! empty($route->callback) )
        {
            static::render( $route );
        }
        else
        {
            // echo "Fail";
            // exit;;
            // Let wordpress handle it
            // header('Location: ' .get_bloginfo('url').'/not-found');
        }
        
    }
    
    /**
     * Alias of Route::redirect
     */
    public static function redirect($url)
    {
        Route::redirect( $url );
    }
    
    public static function render( $route )
    {
        // The controller needs the route to perform the action for the controller
        ob_start();
        $controller = \Controller::factory( $route );
        $s = ob_get_contents();
        ob_end_clean();
                
        if ( !$controller )
        {
            View::$controller_output_only = true;
            $view = \View::factory( $route );
            $view->set_controller_output( $s );
        }
        else
        {
            // The view needs the route to know the namespace (for including view files)
            $route->namespace = $controller->get_namespace();
            
            $view       = \View::factory( $route );
            
            // The model needs the route to know the namespace (for including view files)
            //$model      = \Model::factory( $route );
            //$model->save();
            
            
            // The controller and the view must know each other
            $controller->set_view( $view );
            $view->set_controller( $controller );
            
            // Share the $_POST and $GET data with the controller
            $controller->data = & $_REQUEST;
            if ( isset($controller->data['page']) )
            {
                unset($controller->data['page']);
            }
            
            // perform the controller action
            call_user_func( array($controller, 'action') );
            
            // Auto-render turned off? Done here.
            if( ! $controller->autorender )
                return;
            
            // This copies helpers loded from the controller onto the view
            $view->copy_controller_helpers();
            
        }
        
        // Errors in controller? or view is unset inside the controller?
        if( is_object($controller) and !empty($controller->error) or empty($controller->view) )
        {
            View::$controller_output_only = true;
        }
        
        // By default, the view is rendered via shortcode. Except when
        // view::immediate_output is set to TRUE
        //
        if( View::$immediate_output )
        {
            View::render();
            if( View::$stop_after_render )
            {
                exit;
            }
        }
        else
        {
            // For admin pages, we can't have shortcodes to this has to
            // render right away
            // The Route matching function determined if the route
            // is to an admin page
            if ( Route::is_admin() )
            {
                // Assign the view to the phantom sidebar link
                // This is necessary so wordpress won't issue an error that the
                // page does not exist. The page MUST be assigned to an existing menu item
                $_GET['original_page'] = $_GET['page'];
                $_GET['page'] = 'wpmvc';
                
                // We must change the page seen by wordpress to be able to show the output
                //$_GET['page'] = 'wpmvc';
                add_action('all_admin_notices', array('View', 'render'));
            }
            else
            {
                add_shortcode( 'wpmvc', array('View', 'render') );
            }
        }
    }
    
}
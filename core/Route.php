<?php

class Route
{
    const DEFAULT_CONTROLLER_PATTERN = '[a-zA-Z_][a-z0-9_]+';
    const DEFAULT_ACTION_PATTERN = '[a-zA-Z_][a-zA-Z0-9_]+';
    const DEFAULT_ID_PATTERN = '[0-9]+';
    
    static $routes;
    
    private static $__admin = false;
    
    function __construct( $namespace, $parameters )
    {
        if ( !empty($parameters['controller']) and !empty($parameters['action']) )
        {
            $this->callback = array(
                'controller' => $parameters['controller'],
                'action'     => $parameters['action']
            );
        }
        else
        {
            // For pages from other plugins added via add_menu_page, add_submenu_page, add_*_page
            // the wpmvc callback must remain invalid so it will not to catch the hook
        }
        
        $this->namespace = $namespace;
        
        unset($parameters['controller']);
        unset($parameters['action']);
        $this->parameters = $parameters;        
    }
    
    
    /**
     * Adds to globally known routes
     */
    public function add( $namespace, $pattern, $callback = null, $regex_array = null )
    {
        if ( !empty(static::$routes[ $pattern ]) )
            return false;
        
        if ( !is_string($namespace) )
            throw new Exception( _('Invalid namespace type. String required.').' '.gettype($namespace)." found." );
            
        // extract parameters and replace them with regex
        preg_match_all('/<[^>]+>/', $pattern, $matches);
        
        $parameters = array_shift($matches);
        if ( !empty($parameters) )
        {
            array_walk($parameters, function( & $parameter){
                $parameter = trim($parameter, '<>');
            });
        }
        
        $regex = str_replace(array(')', '/'), array(')?', '\/'), $pattern);
        
        $controller_defined = false;
        $action_defined = false;
        foreach( $parameters as $parameter )
        {
            $subregex = null;
            switch($parameter)
            {
                case 'controller':
                    $subregex = ( ! empty( $regex_array['controller'] ) )  ? $regex_array['controller'] : Route::DEFAULT_CONTROLLER_PATTERN;
                    $controller_defined = true;
                    break;
                        
                case 'action':
                    $subregex = ( ! empty( $regex_array['action'] ) )  ? $regex_array['action'] : Route::DEFAULT_ACTION_PATTERN;
                    $action_defined = true;
                    break;
                        
                case 'id':
                    $subregex = ( ! empty( $regex_array['id'] ) )  ? $regex_array['id'] : Route::DEFAULT_ID_PATTERN;
                    break;
                
                default;
                    if ( empty( $regex_array[$parameter] ) )
                        throw new Exception( _('Undefined pattern for parameter').": {$parameter}" );
                    else
                        $subregex = $regex_array[$parameter];
            }
            
            $regex = str_replace("<{$parameter}>", "({$subregex})", $regex);
        }
        
        if( (!$controller_defined and empty($callback['controller'])) or (!$action_defined and empty($callback['action']) ) )
        {
            throw new Exception( _('Routes must include both the controller and action for the specified route') .": [{$namespace}]{$pattern}" );
        }
        
        static::$routes[ $pattern ] = array(
                                        'callback'  => $callback,
                                        'regex'     => "/^{$regex}$/",
                                        'namespace' => $namespace
                                    );
        
        return true;
    }
    
    /**
     * Matches the current request URI to the list of globally known routes
     */
    public static function match( $url = '' )
    {
        if ( empty($url) )
        {
            $url = $_SERVER['REQUEST_URI'];
        }
                
        $tomatch = static::__extract_route( $url );
        
        /*
        WHAT IS THIS FOR?
        $get_parameters = array();
        
        foreach($request_uri as $parameter)
        {
            $parameter = explode('=', $parameter);
            $get_parameters[ array_shift($parameter) ] = array_shift($parameter);
        }
        if ( static::$__admin )
        {
            unset( $get_parameters['page'] );
        }
        */
        
        
        $possible_routes = array();
        foreach( static::$routes as $pattern => $route )
        {
            // echo "matching {$route['regex']} with {$tomatch}...";
            if( preg_match_all($route['regex'], $tomatch, $matches ) )
            {
                // echo "ok!";
                $possible_routes[ $pattern ] = array('namespace' => $route['namespace'], 'callback' => $route['callback']);
            }
            // echo "\n";
        }
        
        if(!empty( $possible_routes ))
        {
            $route_points = array();
            foreach( $possible_routes as $possible_route => $route )
            {
                $parameters = static::__translate_pattern_to_parameters( $tomatch, $possible_route );
                $points = 0;
                foreach($parameters as $k => $v)
                {
                    if( $k == $v )
                    {
                        $points++;
                        // This is not usable as this is hardcoded string for the route
                        unset($parameters[ $k ]);
                    }
                }
                $route_points[] = array(
                    'points' => $points,
                    'parameters' => $parameters,
                    'route' => $route
                );
            }
            
            // reverse sort, highest points first
            usort($route_points, function ($a, $b){ 
                if ( $a['points'] == $b['points']) return 0;
                return ($a['points'] < $b['points']) ? 1 : -1;
            });
            
            $highest = array_shift($route_points);
            
            $route = array_pop($highest);
            $parameters = array_pop($highest);
            if ( !empty($route['callback']) and is_array($route['callback']) )
            {
                $parameters = array_merge($parameters, $route['callback']);
            }
            return new Route( $route['namespace'], $parameters );
            
        }
        return false;
    }
    
    /**
     * There are different url patterns that this can match
     * <path>/<path>/...
     * wpmvc?<path>/<path>/...
     * admin.php?page=<path>/<path>/...
     * 
     * URLs passed are assumed to have no GET parameters but in case they do, those are not stripped out
     */
    private static function __extract_route( $url )
    {
        // Admin
        if ( preg_match('/\/wp\-admin\/[a-zA-Z0-9-]+\.php\?/', $url) and !empty($_GET['page']) )
        {
            return '/'.ltrim($_GET['page'], '/');
        }
        // Front facing
        elseif ( preg_match('/\/wpmvc\/\?([^&]+)(\&.*)?$/', $url, $matches) )
        {
            return ( !empty($matches[1]) ) ? '/'.ltrim($matches[1], '/') : '';
        }
        
        // Else Direct call
        return '/'.ltrim($url, '/');
        
        /*
        print_r( $url );
        exit;
        
        // Prepare the URL segments
        $request_uri = explode( '?', $url );
        $request_uri = explode( '&', array_pop($request_uri) );
        
        $tomatch = '';
        // ROUTING INSIDE ADMIN
        if ( static::is_admin() )
        {
            if ( !empty($_GET['page']) and strpos($_GET['page'], '/') > 0 )
            {
                $tomatch = '/'.$_GET['page'];                
            }
            else
            {
                $tomatch = $url;
            }
        }
        // ROUTING OUTSIDE ADMIN
        // This is internally re-written such that the url string after
        // /r/ are converted to GET parameters: e.g.
        // /r/controller/action > /r/wpmvc/?controller/action
        else
        {
            $tomatch = '/'.array_shift($request_uri);
        }
        return $tomatch;
        */
    }
    
    private static function __translate_pattern_to_parameters( $tomatch, $pattern )
    {
        $pattern = explode('/', str_replace(array('(', ')'), array('', ''), ltrim($pattern, '/')));
        $tomatch = explode('/', ltrim($tomatch, '/'));
        $parameters = array();
        for( $i = 0, $j = count( $pattern ); $i < $j; $i++)
        {
            if( ! array_key_exists( $i, $tomatch ) )
                break;
            $parameters[ trim($pattern[$i], '<>') ] = $tomatch[ $i ];
        }
        return $parameters;
    }
    
    /**
     * Finds URL for specified action.
     *
     * Returns a URL pointing at the provided parameters.
     *
     * @param mixed $url Either a relative string url like `/products/view/23` or
     *    //// TODO an array of url parameters.  Using an array for urls will allow you to leverage
     *    the reverse routing features of CakePHP.
     * @param boolean $full If true, the full base URL will be prepended to the result
     * @return string  Full translated URL with base path.
     * @access public
     * @link http://book.cakephp.org/view/1448/url
     */
	function url($url = null, $full = false) {
        
        //  TODO. Why doesn't "static::" work?
		if ( Route::is_admin() )
        {
            return get_bloginfo('url').'/wp-admin/admin.php?page='.ltrim($url, '/');
        }
        else
        {
            return 'TODO lalalala';
        }
	}
    
    public static function redirect( $url )
    {
        // Absolute URL
        // http:/sadasdasd/ or asdasd.asdasd
        if( preg_match_all( array('/^([a-z]+\:\/\/)[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*/', '/^([a-z]+\:\/\/)?[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+/'), $url) )
        {
            // do nothing
        }
        else
        {
            if( static::is_admin() )
            {
                $_GET['page'] = $url;
                $url = $_SERVER['SCRIPT_NAME'].'?'.str_replace('%2F', '/', http_build_query($_GET));
            }
            else
            {
                // public facing redirect
                // TODO
            }
        }
        header("Location: {$url}");
        exit;
    }
    
    public static function is_admin()
    {
        if ( empty(static::$__admin) )
        {
            static::$__admin = preg_match('/\/wp\-admin\//', $_SERVER['REQUEST_URI']) ? true : false;
        }
        return static::$__admin;
    }
    
}
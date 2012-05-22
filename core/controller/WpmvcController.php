<?php

class WpmvcController extends Controller {
    
    /**
     * The settings page
     */
    function settings()
    {
    }
    
    /**
     * Make a new plugin
     */
    function new_plugin()
    {
        if ( !empty($this->data['plugin_name']) )
        {
            $slug = strtolower( Inflector::slug( $this->data['plugin_name'], '-' ) );
            $src  = str_replace( array('\\', '/'), array('/', DS), WPMVC_PLUGIN_DIR.'/project_template' );
            $dest = rtrim( str_replace( array('\\', '/'), array('/', DS), WP_PLUGIN_DIR.'/'.$slug ), DS);
            
            require_once WPMVC_PLUGIN_DIR.DS.'lib'.DS.'smart_copy.php';
            
             $result = smartCopy( $src, $dest );
            // TODO success message
             if( ! $result )
            {
                // TODO fail message
            }
            
            
            // rename plugin-name.php
            rename( $dest.DS.'plugin-name.php', $dest.DS.$slug.'.php' );
            $file_contents = file_get_contents($dest.DS.$slug.'.php');
            
            if( $f = fopen($dest.DS.$slug.'.php', 'w') )
            {
                $file_contents = str_replace( '{PLUGIN_NAME}', $this->data['plugin_name'], $file_contents );
                
                $current_user = wp_get_current_user();
                
                if ( !empty($current_user->data->first_name) or !empty($current_user->data->last_name) )  
                {
                    $file_contents = str_replace( '{PLUGIN_AUTHOR}', "{$current_user->data->first_name} {$current_user->data->last_name}", $file_contents );
                }
                elseif ( !empty($current_user->data->user_login))
                {
                    $file_contents = str_replace( '{PLUGIN_AUTHOR}', $current_user->data->user_login, $file_contents );
                }
                
                
                if ( !empty($current_user->data->user_url) )  
                {
                    $file_contents = str_replace( '{PLUGIN_AUTHOR_URI}', $current_user->data->user_url, $file_contents );
                }
                
                fwrite( $f, $file_contents );
                fclose( $f );
            }            
            
            exit;
            
        }
    }
    
}
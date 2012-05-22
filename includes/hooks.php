<?php


if(is_admin())
{
    add_action('plugins_loaded', array('WPMVC', 'bootstrap'), 20);
 

    // ADMINISTRATION MENU
    add_action( 'admin_menu', function(){
        
        // The main WPMVC Settings page
        add_options_page(_('WPMVC'), _('WPMVC'), 'administrator', 'wpmvc/settings', array('WPMVC', 'bootstrap'));
        $page = add_submenu_page('wpmvc', _('WPMVC Phantom menu'), _('WPMVC Phantom menu'), 'administrator', 'wpmvc', array('WPMVC', 'bootstrap'));
    });

    
    add_filter( 'custom_menu_order', function( $menu ){
        return true;
    });
    
    /**
     * The "view" pages are attached to a phantom submenu item named "wpmvc."
     * Without making any changes to the menu structure, all wpmvc views won't
     * be highlighted in the menu (because they are attached to the hidden wpmvc menu)
     *
     * This function interferes with the default wordpress behavior by fooling
     * it about the current menu item.
     * 
     * The menu structure is stored in the global variable $submenu. Wordpress
     * depends on $_GET['page'] and the global $plugin_page when selecting which
     * menu item to highlight.
     *
     * $_GET['page'] now contains "wpmvc" and the original value is stored in
     * $_GET['original_page'].
     *
     * 1. This function looks for an exact match of the original page
     *    from the menu structure. If there is one, that is chosen to
     *    be the one highlighted
     * 
     * 2. If there is no exact match, the plugin root is compared to
     *    the plugin root of the items from the menu. The first match
     *    is used and highlighted. For example the page is /hp/view/1
     *    and the menu has an item /hp/index, this menu item is
     *    highlighted for /hp/view/1
     *
     * 3. If no matches were found, the original page, is replaced by the
     *    referring page and steps 1 and 2 are repeated.
     *
     * The problem with this method is if the links lead to different
     * links with different plugin_roots and they go deeper than 2.
     * Ex:
     * only /a/index exists in the menu item
     * /a/b/c => /d/e/f -- /a/index is highlighted because the plugin
     *                    root /a is used (from the referer)
     * /d/e/f => /g/h/i -- nothing is highlighted because
     *                     1) /g/* is not in the menu item (page)
     *                     2) /d/* is not in the menu item (referer)
     *
     */
    add_filter( 'menu_order', function( ){
        
        global $menu, $submenu, $parent_file, $plugin_page;
                
        if ( !empty($_GET['original_page']) )
        {
            // This is the original path before assigning the page
            // to the phantom menu item "wpmvc". (See WPMVC.php)            
            $original_page = $_GET['original_page'];
            
            // This is the MVC plugin for the current page string
            // used in grouping pages and assigning them to one
            // menu item (so this menu item wil be displayed in bold)
            $mvcroot = preg_replace('/\/.*$/', '', $original_page);
            
            // This is the selected menu item
            $highlighted_page = null;
            
            foreach( $submenu as $parentmenu => $submenuitems )
            {
                // If the plugin_page has an exact match from parent items, use that
                if( $parentmenu == $original_page )
                {
                    $highlighted_page = $original_page;
                    break;
                }
                foreach( $submenuitems as $menuitem )
                {   
                    if( !empty($menuitem[2]) )
                    {
                        // If the plugin_page has an exact match from sub menu items, use that
                        if( $menuitem[2] == $original_page )
                        {
                            $highlighted_page = $original_page;
                            break 2;
                        }
                    }
                }
            }
            
            if( empty($highlighted_page) ):
            
                foreach( $submenu as $parentmenu => $submenuitems )
                {
                    for($i = 0; $i < 2; $i++)
                    {
                        // See if the page has any "relatives" from the menu
                        foreach( $submenuitems as $menuitem )
                        {   
                            if( !empty($menuitem[2]) )
                            {
                                // Similar mvc root, can use this
                                $menu_mvcroot = preg_replace('/\/.*$/', '', $menuitem[2]);
                                if ( $mvcroot == $menu_mvcroot )
                                {
                                    $highlighted_page = $menuitem[2];
                                    break 3;
                                }
                            }
                        }
                        
                        // Nothing?
                        // See if the page's REFERER has an exact match or
                        // any "relatives" from the menu
                        
                        // Get the route from the referers "page" GET parameter
                        // parse_str creates variables from the params
                        parse_str(array_pop(explode('?', $_SERVER['HTTP_REFERER'])));
                        // Found a relative? use that (and break)
                        if (!empty( $page ))
                        {
                            // TODO: FIND MATCHES AND RELATIVES FOR $page
                            $highlighted_page = $page;
                            break 2;
                        }
                    }
                }
            endif;
            
            // Fool wordpress into thinking that the current page is
            // the one in $highlighted_page so it will highlight that
            // from the menu item
            if( !empty($highlighted_page) )
            {
                $_GET['page'] = $highlighted_page;
                $plugin_page = $highlighted_page;
            }
        }
        
        return array();
    });
    
}
else
{
    add_action('wp', array('WPMVC', 'bootstrap'));
}


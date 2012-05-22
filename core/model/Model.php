<?php

/**
 * Since wordpress is pretty much MySQL-centric, I won't bother supporting
 * different (generic) datasources
 */
class Model {
    
    /**
     * Datasource object
     */
    var $db;
    
    /**
     * Controller using this model
     */
    var $controller;
    
    /**
     * Data being saved or loaded
     */
    var $data;
    
    /**
     * Validation Errors
     */
    var $errors;
    
    
    var $validated = false;
    
    /**
     * Default find modes/methods
     */
    var $find_methods = array(
        'first' =>  true,
        'all' =>  true,
        'count' =>  true,
        'list' =>  true
    );
    
    /**
     * The mode being used in finding records
     */
    protected $_find_type;
    
    /**
     * The result for everything
     */
    protected $_result;
    
    
    
    /** ----------------------------------------------------------- **/
    
    
    function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        
    }
    
    
    public static function factory( $model )
    {   
        $model = new $model;
        
        /* Set up behaviors */
        $behaviors = array();
        if( !empty($model->act_as) and is_array($model->act_as)) 
        {
            foreach( $model->act_as as $behavior )
            {
                $behaviors[] = "{$namespace}\\{$behavior}Behavior";
            }
        }
        $model->act_as = $behaviors;
        
        /* Set up database */
        $model->table = preg_replace('/^.+\\\/', '', get_class( $model ));
        $model->table = Inflector::underscore( $model->table );
        $model->table = $model->db->prefix.Inflector::tableize( $model->table );
        
        return $model;
    }
    
    public function set_controller( & $controller )
    {
        $this->controller = $controller;
    }
    
    
    /*
     * ----------------------------------------------------------------
     * O R M   F U N C I O N S
     * ----------------------------------------------------------------
     */
    public final function _find( $type = 'first' )
    {
        // execute this hook
        $this->before_find();
        
        // The extra parameters passed to the call to find
        $params = func_get_args();
        // Throw away the type parameter
        array_shift($params);
        
        /* ----------------------------------------------- */
        if ( method_exists( $this, "_find_{$type}" ) )
        {
            $this->_find_type = $type;
            $this->_result = call_user_func_array(array( $this, "_find_{$type}" ), $params);
        }
        
        /* ----------------------------------------------- */
        // execute this hook
        $this->after_find();
        
        return $this->_result;
    }
    
    /**
     * Inserts or update depending on primary key
     */
    public final function _save( $data = null )
    {
        $this->errors = array();
        
        // Replace data attribute if it's provided
        if (!empty($data))
        {
            $this->data = $data;
        }
        
        // Auto validate if not yet validated        
        if ( !$this->validated )
        {   
            if( !$this->validate() )
            {
                return false;
            }
        }
        
        // Determine if data is for update or insert depending on id key
        $update = false;
        if ( !empty($this->data['id']) )
        {
            $exists = $this->find('count', array('id' => $this->data['id']));
            if ( $exists )
            {
                $update = true;
            }
        }
        if($update)
        {
            // execute this hook
            // $this->before_update();
            $this->_result = $this->db->update($this->table, $this->data, array('id' => $this->data['id'])); 
            // $this->after_update();
        }
        else
        {
            // execute this hook
            $this->before_save();
            $this->_result = $this->db->insert($this->table, $this->data); 
            $this->after_save();
        }
        
        
        return $this->_result;
        
    }
    
    /**
     * Delete the record this object points to
     */
    public final function _delete( $where )
    {
        // execute this hook
        $this->before_delete();
        /* ----------------------------------------------- */
        
        if ( !empty($where) and is_array($where) )
        {
            $where = 'WHERE '.call_user_func_array(array($this->db, 'prepare'), $where);
        }
        else
        {
            $where = '';
        }
        
        $this->_result = $this->db->query( "DELETE FROM {$this->table} {$where}" );
        
        /* ----------------------------------------------- */
        // execute this hook
        $this->after_delete();
        
        return $this->_result;
    }
    
    /*
     * ----------------------------------------------------------------
     * F I N D E R S
     * ----------------------------------------------------------------
     */
     
    /**
     * Will return one result, you’d use this for any case where you
     * expect only one result
     */
    function _find_first( $fields, $where )
    {
        if ( !is_array($fields) or empty($fields) )
        {
            $fields = '*';
        }
        elseif ( is_array($fields) )
        {
            $fields = implode(', ', $fields);
        }
        
        if ( !is_array($where) or empty($where) )
        {
            $where = '';
        }
        elseif ( is_array($where) )
        {
            $where = 'WHERE '.call_user_func_array( array($this->db, 'prepare'), $where );
        }
        
        return $this->db->get_row( "SELECT {$fields} FROM {$this->table} {$where}" );
    }
    
    /**
     * returns an integer value
     */
    function _find_count( $where )
    {
        return $this->db->get_var( $this->db->prepare( "SELECT COUNT(*) FROM {$this->table}" ) );
    }
    
    /**
     * returns an array of (potentially multiple) results
     */
    function _find_all( $fields = null, $where = null )
    {
        if ( !is_array($fields) or empty($fields) )
        {
            $fields = '*';
        }
        elseif ( is_array($fields) )
        {
            $fields = implode(', ', $fields);
        }
        
        if ( !is_array($where) or empty($where) )
        {
            $where = '';
        }
        elseif ( is_array($where) )
        {
            $where = 'WHERE '.call_user_func_array( array($this->db, 'prepare'), $where );
        }
        
        $query = "SELECT {$fields} FROM {$this->table} {$where}";
                
        return $this->db->get_results( $query );
    }
    
    /**
     * returns an indexed array, useful for any place where you would
     * want a list such as for populating input select boxes
     */
    function _find_list( $fields, $where = null )
    {
        if ( count($fields) != 2 )
            throw new Exception( _('Finding by list requires exactly 2 fields to build the mapping') );
        
        $results = $this->_find_all( $fields, $where );
        $key = array_shift( $fields );
        $value = array_shift( $fields );
        $map = array();
        foreach($results as $row)
        {
            if ( empty( $row->$key ))
                continue;
            if ( empty( $row->$value ) )
                $row->$value = null;
                
            $map[ $row->$key ] = $row->$value;
        }
        return $map;
    }
    
    function validate( $validate = null )
    {
        $this->validated = true;
    
        //----------------------------------------------------
        $this->before_validate();
        //----------------------------------------------------
    
        $this->errors = array();
        
        if ( empty($validate) and !empty($this->validate) ) 
            $validate = $this->validate;
            
        if ( empty($validate) )
            return true;
        
        foreach( $validate as $field => $rule )
        {
            // Get the value to check
            $check = ( !empty($this->data[$field]) ) ? $this->data[$field] : null;
            
            // Get the formatted rules array for field $field
            $rules = $this->__get_validation_rule_message( $rule );
            
            if(empty($rules))
                throw new Exception( sprintf( _('Empty rules for field %s'), $field) );
            
            
            // Execute all validation rules on field $field
            foreach($rules as $ruleset)
            {
                // Is this rule applicable to the current action
                if ( !empty($ruleset['on']) and !in_array($this->controller->action, $ruleset['on']) )
                    continue;
                    
                // Does this rule allow this field to have empty values
                if ( !empty($ruleset['allowEmpty']) and $ruleset['allowEmpty'] and empty($check) and !is_numeric($check) )
                    continue;
            
                $rule = $ruleset['rule'];
                $message = $ruleset['error_message'];
                
                // Built-in validation methods
                if ( method_exists( 'Validation', $rule ) )
                {
                    if( ! call_user_func_array( array('Validation', $rule), array_merge( array($check), $ruleset['parameters'] ) ) )
                    {
                        $this->errors[ $field ] = sprintf( $message, $field );
                    }
                }
                // Custom validation methods from Model subclass
                elseif ( method_exists( $this, $rule ) )
                {
                    if( ! call_user_func_array( array($this, $rule), array_merge( array($check), $ruleset['parameters'] ) ) )
                    {
                        $this->errors[ $field ] = sprintf( $message, $field );
                    }
                }
                // Validation method not available
                else
                {
                    $this->errors[ $field ] = sprintf( _('Validation method %s does not exist'), $rule);
                }
                
            }
        }
        
        return empty($this->errors);
    }
    
    
    /*
     * Get the rule function and the corresponding error message
     * 
     * Rule formats
     *     + "ruleString" (string)
     *     + array("ruleString", 2) (array of rule with parameter)
     *     + array( 'rule' => "RuleString" ) (array with rule string as key)
     *     + array of the above three
     *
     * TODO: THIS REALLY LOOKS COMPLICATED. NEED TO REFACTOR
     *
     */
    private function __get_validation_rule_message( $ruleset )
    {
        if ( is_string($ruleset) ) 
        {
            return array(array(
                    'rule' => $ruleset,
                    'parameters' => array(),
                    'error_message' => call_user_func( array('ValidationMessage', $ruleset) ) 
            ));
        }
        elseif ( is_array($ruleset) )
        {
            $return = null;
            
            // For implicit non-string rules and multiple rules -- array of rules
            if ( !array_key_exists('rule', $ruleset) )
            {
                //
                // For validation methods which accept parameters,
                // the first ruleset element is a string
                // array( 'minLength', 8 )
                //
                reset($ruleset);
                $rulestring = $ruleset[key($ruleset)];
                if(is_string($rulestring))
                {
                    array_shift($ruleset);
                    $message = call_user_func( array('ValidationMessage', $rulestring) );
                    return array(array(
                            'rule' => $rulestring,
                            'error_message' => $message,
                            'parameters' => $ruleset,
                    ));
                }
                //
                // For validation methods, the first ruleset element is an array
                //
                // array(
                //     'rulename1' => array('minLength', 2),
                //     'rulename2' => array('rule' => 'alphaNumeric', ...),
                // )
                //
                else
                {
                    $rules = array();
                    foreach( $ruleset as $rule )
                    {
                        $rules[] = array_shift($this->__get_validation_rule_message( $rule ));
                    }
                    return $rules;
                }
            }
            //
            // For single rule definitions with other attributes
            //
            else
            {
                $parameters = array();
                
                //
                // Single string rule
                //
                if(is_string($ruleset['rule']))
                {
                    $rulestring = $ruleset['rule'];
                }
                //
                // Explicit single non-string rule
                //
                elseif(is_array($ruleset['rule']))
                {
                    $rulestring = array_shift($ruleset['rule']);
                    $parameters = $ruleset['rule'];
                    
                    if ( !is_string($rulestring) )
                    {
                        return false;
                    }
                }
                else
                {
                    return false;
                }
                
                $message = (!empty($ruleset['message'])) ? $ruleset['message'] : call_user_func( array('ValidationMessage', $rulestring) );
                
                return array(array(
                        'rule'          => $rulestring,
                        'error_message' => $message,
                        'parameters'    => $parameters,
                        'on'            => (!empty($ruleset['on'])) ? explode(',', $ruleset['on']) : array(),
                        'allowEmpty'    => (!empty($ruleset['allowEmpty'])) ? explode(',', $ruleset['allowEmpty']) : array(),
                ));
            }
            return false;
        }
        return false;
    }
    
    /*
     * ----------------------------------------------------------------
     * W P D B   F U N C I O N S
     * ----------------------------------------------------------------
     */
     
    public final function _query( $execute_only = true, $sql_incomplete )
    {
        $args = func_get_args();
        array_shift( $args );
        
        if ($execute_only)
            return $this->db->query( call_user_func_array( array($this->db, 'prepare'), $args ) );
        
        return $this->db->get_results( call_user_func_array( array($this->db, 'prepare'), $args ) );
    }
    
    /*
     * ----------------------------------------------------------------
     * M O D E L / B E H A V I O R   O V E R R I D E S
     * ----------------------------------------------------------------
     */
    public function __call( $func, $args )
    {
        // Behavior override available?
        if ( !empty($this->act_as) and is_array($this->act_as) )
        {
            foreach( $this->act_as as $behavior )
            {
                // TODO Fire behavior function
            }
        }
        // No overrides, use model functions        
        return call_user_func_array( array($this, '_'.$func), $args );
    }
     
    /* ----------------------------------------------------------------
       H O O K S.
       O V E R R I D E   T H E S E
       ------------------------------------------------------------- */
    protected function before_validate()
    {
        $this->__fire_behavior_hook( __FUNCTION__ );
    }
    
    protected function before_find()
    {
        $this->__fire_behavior_hook( __FUNCTION__ );
    }
    
    protected function after_find()
    {
        $this->__fire_behavior_hook( __FUNCTION__ );
    }
    
    protected function before_save()
    {
        $this->__fire_behavior_hook( __FUNCTION__ );
        $this->validate();
    }
    
    protected function after_save()
    {
        $this->__fire_behavior_hook( __FUNCTION__ );
    }
    
    protected function before_delete()
    {
        $this->__fire_behavior_hook( __FUNCTION__ );
    }
    
    protected function after_delete()
    {
        $this->__fire_behavior_hook( __FUNCTION__ );
    }
    
    private function __fire_behavior_hook( $hook )
    {   
        if(empty($this->act_as) or !is_array($this->act_as))
            return false;
        
        foreach( $this->act_as as $behavior )
        {
            $b = new $behavior;
            call_user_func( array($b, ltrim($hook, '_') ) );
        }
    }
    
}
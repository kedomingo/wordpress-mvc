<?php

/**
 * Validation default error messages
 * Messages are stored as static instead of const to be able to make
 * use of the gettext function. The static attributes are private to
 * somehow protect it from change from outside (to make it behave like
 * const)
 */
class ValidationMessage 
{
    private static $__alphaNumeric;
    private static $__between;
    private static $__blank;
    private static $__boolean;
    private static $__cc;
    private static $__comparison;
    private static $__date;
    private static $__decimal;
    private static $__email;
    private static $__equalTo;
    private static $__extension;
    private static $__file;
    private static $__ip;
    private static $__isUnique;
    private static $__minLength;
    private static $__maxLength;
    private static $__money;
    private static $__multiple;
    private static $__inList;
    private static $__numeric;
    private static $__notEmpty;
    private static $__phone;
    private static $__postal;
    private static $__range;
    private static $__ssn;
    private static $__url;
    
    private static $__messages_set = false;
    
    private static function __set_messages()
    {
        static::$__alphaNumeric = _('The field %s is formatted incorrectly. Expecting alpha-numeric.');
        static::$__between      = _('The field %s out of range.');
        static::$__blank        = _('The field %s should be left blank.');
        static::$__boolean      = _('The field %s is invalid. Expecting boolean (true, false, 1, 0).');
        static::$__cc           = _('The credit card number you supplied was invalid.');
        static::$__comparison   = _('The field %s is invalid.');
        static::$__date         = _('The field %s is not a valid date.');
        static::$__decimal      = _('The field %s is not a valid decimal number.');
        static::$__email        = _('The field %s is not a valid email address.');
        static::$__equalTo      = _('');
        static::$__extension    = _('');
        static::$__file         = _('');
        static::$__ip           = _('');
        static::$__isUnique     = _('');
        static::$__minLength    = _('');
        static::$__maxLength    = _('');
        static::$__money        = _('');
        static::$__multiple     = _('');
        static::$__inList       = _('');
        static::$__numeric      = _('');
        static::$__notEmpty     = _('');
        static::$__phone        = _('');
        static::$__postal       = _('');
        static::$__range        = _('');
        static::$__ssn          = _('');
        static::$__url          = _('');
        
        static::$__messages_set = true;
    }
    
    public static function __callStatic($func, $args)
    {
        if ( !static::$__messages_set )
        {
            static::__set_messages();
        }
        $func = '__'.$func;
        if ( !empty( static::$$func ) )
        {
            return static::$$func;
        }
        return _('Unknown error for field %s');
    }
    
}
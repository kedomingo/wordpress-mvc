<?php

class Helper {
    
    public static function factory( $helper )
    {   
        $helper .= 'Helper';
        $helper = new $helper;
        
        /* Set up behaviors */
        return $helper;
    }
    
    /**
     * Finds URL for specified action.
     *
     * Returns a URL pointing at the provided parameters.
     *
     * @param mixed $url Either a relative string url like `/products/view/23` or
     *    an array of url parameters.  Using an array for urls will allow you to leverage
     *    the reverse routing features of CakePHP.
     * @param boolean $full If true, the full base URL will be prepended to the result
     * @return string  Full translated URL with base path.
     * @access public
     * @link http://book.cakephp.org/view/1448/url
     */
	function url($url = null, $full = false) {
        return Route::url($url, $full);
	}
    
    
    /* Cake methods */ 
/**
 * Returns a space-delimited string with items of the $options array. If a
 * key of $options array happens to be one of:
 *
 * - 'compact'
 * - 'checked'
 * - 'declare'
 * - 'readonly'
 * - 'disabled'
 * - 'selected'
 * - 'defer'
 * - 'ismap'
 * - 'nohref'
 * - 'noshade'
 * - 'nowrap'
 * - 'multiple'
 * - 'noresize'
 *
 * And its value is one of:
 *
 * - '1' (string)
 * - 1 (integer)
 * - true (boolean)
 * - 'true' (string)
 *
 * Then the value will be reset to be identical with key's name.
 * If the value is not one of these 3, the parameter is not output.
 *
 * 'escape' is a special option in that it controls the conversion of
 *  attributes to their html-entity encoded equivalents.  Set to false to disable html-encoding.
 *
 * If value for any option key is set to `null` or `false`, that option will be excluded from output.
 *
 * @param array $options Array of options.
 * @param array $exclude Array of options to be excluded, the options here will not be part of the return.
 * @param string $insertBefore String to be inserted before options.
 * @param string $insertAfter String to be inserted after options.
 * @return string Composed attributes.
 * @access public
 */
	function _parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		if (is_array($options)) {
			$options = array_merge(array('escape' => true), $options);

			if (!is_array($exclude)) {
				$exclude = array();
			}
			$keys = array_diff(array_keys($options), array_merge($exclude, array('escape')));
			$values = array_intersect_key(array_values($options), $keys);
			$escape = $options['escape'];
			$attributes = array();

			foreach ($keys as $index => $key) {
				if ($values[$index] !== false && $values[$index] !== null) {
					$attributes[] = $this->__formatAttribute($key, $values[$index], $escape);
				}
			}
			$out = implode(' ', $attributes);
		} else {
			$out = $options;
		}
		return $out ? $insertBefore . $out . $insertAfter : '';
	}

/**
 * Formats an individual attribute, and returns the string value of the composed attribute.
 * Works with minimized attributes that have the same value as their name such as 'disabled' and 'checked'
 *
 * @param string $key The name of the attribute to create
 * @param string $value The value of the attribute to create.
 * @return string The composed attribute.
 * @access private
 */
	function __formatAttribute($key, $value, $escape = true) {
		$attribute = '';
		$attributeFormat = '%s="%s"';
		$minimizedAttributes = array('compact', 'checked', 'declare', 'readonly', 'disabled',
			'selected', 'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize');
		if (is_array($value)) {
			$value = '';
		}

		if (in_array($key, $minimizedAttributes)) {
			if ($value === 1 || $value === true || $value === 'true' || $value === '1' || $value == $key) {
				$attribute = sprintf($attributeFormat, $key, $key);
			}
		} else {
			$attribute = sprintf($attributeFormat, $key, ($escape ? h($value) : $value));
		}
		return $attribute;
	}

/**
 * Sets this helper's model and field properties to the dot-separated value-pair in $entity.
 *
 * @param mixed $entity A field name, like "ModelName.fieldName" or "ModelName.ID.fieldName"
 * @param boolean $setScope Sets the view scope to the model specified in $tagValue
 * @return void
 * @access public
 */
	function setEntity($entity, $setScope = false) {
		$view =& ClassRegistry::getObject('view');

		if ($setScope) {
			$view->modelScope = false;
		} elseif (!empty($view->entityPath) && $view->entityPath == $entity) {
			return;
		}

		if ($entity === null) {
			$view->model = null;
			$view->association = null;
			$view->modelId = null;
			$view->modelScope = false;
			$view->entityPath = null;
			return;
		}

		$view->entityPath = $entity;
		$model = $view->model;
		$sameScope = $hasField = false;
		$parts = array_values(Set::filter(explode('.', $entity), true));

		if (empty($parts)) {
			return;
		}

		$count = count($parts);
		if ($count === 1) {
			$sameScope = true;
		} else {
			if (is_numeric($parts[0])) {
				$sameScope = true;
			}
			$reverse = array_reverse($parts);
			$field = array_shift($reverse);
			while(!empty($reverse)) {
				$subject = array_shift($reverse);
				if (is_numeric($subject)) {
					continue;
				}
				if (ClassRegistry::isKeySet($subject)) {
					$model = $subject;
					break;
				}
			}
		}

		if (ClassRegistry::isKeySet($model)) {
			$ModelObj =& ClassRegistry::getObject($model);
			for ($i = 0; $i < $count; $i++) {
				if (
					is_a($ModelObj, 'Model') && 
					($ModelObj->hasField($parts[$i]) || 
					array_key_exists($parts[$i], $ModelObj->validate))
				) {
					$hasField = $i;
					if ($hasField === 0 || ($hasField === 1 && is_numeric($parts[0]))) {
						$sameScope = true;
					}
					break;
				}
			}

			if ($sameScope === true && in_array($parts[0], array_keys($ModelObj->hasAndBelongsToMany))) {
				$sameScope = false;
			}
		}

		if (!$view->association && $parts[0] == $view->field && $view->field != $view->model) {
			array_unshift($parts, $model);
			$hasField = true;
		}
		$view->field = $view->modelId = $view->fieldSuffix = $view->association = null;

		switch (count($parts)) {
			case 1:
				if ($view->modelScope === false) {
					$view->model = $parts[0];
				} else {
					$view->field = $parts[0];
					if ($sameScope === false) {
						$view->association = $parts[0];
					}
				}
			break;
			case 2:
				if ($view->modelScope === false) {
					list($view->model, $view->field) = $parts;
				} elseif ($sameScope === true && $hasField === 0) {
					list($view->field, $view->fieldSuffix) = $parts;
				} elseif ($sameScope === true && $hasField === 1) {
					list($view->modelId, $view->field) = $parts;
				} else {
					list($view->association, $view->field) = $parts;
				}
			break;
			case 3:
				if ($sameScope === true && $hasField === 1) {
					list($view->modelId, $view->field, $view->fieldSuffix) = $parts;
				} elseif ($hasField === 2) {
					list($view->association, $view->modelId, $view->field) = $parts;
				} else {
					list($view->association, $view->field, $view->fieldSuffix) = $parts;
				}
			break;
			case 4:
				if ($parts[0] === $view->model) {
					list($view->model, $view->modelId, $view->field, $view->fieldSuffix) = $parts;
				} else {
					list($view->association, $view->modelId, $view->field, $view->fieldSuffix) = $parts;
				}
			break;
			default:
				$reverse = array_reverse($parts);

				if ($hasField) {
						$view->field = $field;
						if (!is_numeric($reverse[1]) && $reverse[1] != $model) {
							$view->field = $reverse[1];
							$view->fieldSuffix = $field;
						}
				}
				if (is_numeric($parts[0])) {
					$view->modelId = $parts[0];
				} elseif ($view->model == $parts[0] && is_numeric($parts[1])) {
					$view->modelId = $parts[1];
				}
				$view->association = $model;
			break;
		}

		if (!isset($view->model) || empty($view->model)) {
			$view->model = $view->association;
			$view->association = null;
		} elseif ($view->model === $view->association) {
			$view->association = null;
		}

		if ($setScope) {
			$view->modelScope = true;
		}
	}

/**
 * Gets the currently-used model of the rendering context.
 *
 * @return string
 * @access public
 */
	function model() {
		$view =& ClassRegistry::getObject('view');
		if (!empty($view->association)) {
			return $view->association;
		} else {
			return $view->model;
		}
	}

}

/* Probably a clean function from cake */
function h( $str )
{
    // do nothing
    return $str;
}
/* End of file Helper.php */
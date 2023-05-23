<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty in_array modifier plugin
 * Type:     modifier
 * Name:     in_array
 * Purpose:  Checks if a value exists in an array
 *
 * @param string  $needle
 * @param array   $haystack
 *
 * @return boolean
 */
function smarty_modifier_in_array($needle, $haystack)
{
    return in_array($needle, $haystack);
}

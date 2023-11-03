<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty md5 modifier plugin
 * Type:     modifier
 * Name:     md5
 * Purpose:  Checks if a value exists in an array
 *
 * @param string  $needle
 * @param array   $haystack
 *
 * @return boolean
 */
function smarty_modifier_md5($string)
{
    return md5($string);
}

<?php
/**
 * @version    $Id$
 * @package    JSN Extension Framework 2
 * @author     JoomlaShine Team <support@joomlashine.com>
 * @copyright  Copyright (C) 2012 JoomlaShine.com. All Rights Reserved.
 * @license    GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.joomlashine.com
 * Technical Support:  Feedback - http://www.joomlashine.com/contact-us/get-support.html
 */

// No direct access to this file.
defined('_JEXEC') or die('Restricted access');

/**
 * Class for handling text related actions.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwText
{

	/**
	 * Translate an array of text string.
	 *
	 * @param   array  $keys  An array of text string.
	 * @param   boolean  $json  Whether to return the result as JSON-encoded string.
	 *
	 * @return  mixed  Either an array or a JSON-encoded string that maps the given text keys with real text strings.
	 */
	public static function translate($keys, $json = false)
	{
		$map = array();

		foreach ($keys as $key)
		{
			$map[strtoupper($key)] = JText::_($key, $json);
		}

		return $json ? json_encode($map) : $map;
	}

	/**
	 * Find a JSON encoded data in the given string and parse to array.
	 *
	 * @param   string  $str  String to find JSON encoded data.
	 *
	 * @return  mixed
	 */
	public static function parseJson($str)
	{
		if (preg_match('/\{"|\[([\{\[\d"]|true|false)/', $str, $match))
		{
			$json = array_slice(explode($match[0], $str), 1);
			$json = $match[0] . implode($match[0], $json);

			if ($json = json_decode($json, true))
			{
				return $json;
			}
		}

		return $str;
	}

	/**
	 * JSON encode a variable and refine the JSON encoded string for safely use as value for HTML tag attribute.
	 *
	 * @param   mixed  $var  Variable to be converted to JSON encoded string.
	 *
	 * @return  string
	 */
	public static function toJson($var)
	{
		return str_replace('"', '&quot;', json_encode($var, JSON_UNESCAPED_SLASHES));
	}
}

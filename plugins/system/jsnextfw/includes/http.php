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

// Import necessary libraries.
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Class for handling HTTP request.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwHttp
{

	/**
	 * Send HTTP request using GET method.
	 *
	 * @param   string   $link   The URL to send request to.
	 * @param   string   $cache  The number of seconds to cache request results.
	 * @param   boolean  $force  Whether to send request even when cached results available?
	 *
	 * @return  mixed
	 */
	public static function get($link, $cache = 0, $force = false)
	{
		$result = null;

		// Get results from cache file if not expired.
		$cacheFile = JFactory::getConfig()->get('tmp_path') . '/jsnextfw/' . md5($link);

		if (!$force && is_numeric($cache) && $cache > 0)
		{
			if (JFile::exists($cacheFile) && time() - filemtime($cacheFile) < $cache)
			{
				$result = file_get_contents($cacheFile);
			}
		}

		// Send request if cached results not available.
		if (empty($result))
		{
			$client = new JHttp();
			$result = $client->get($link);

			if ($result)
			{
				$result = $result->body;

				// Cache results.
				if (is_numeric($cache) && $cache > 0 && JFolder::create(dirname($cacheFile)))
				{
					JFile::write($cacheFile, $result);
				}
			}
			elseif (JFile::exists($cacheFile))
			{
				$result = file_get_contents($cacheFile);

				// Update last modification time of the cache file.
				touch($cacheFile);
			}
			else
			{
				throw new Exception(JText::sprintf('JSN_EXTFW_HTTP_REQUEST_FAIL', parse_url($link, PHP_URL_HOST)));
			}
		}

		return JsnExtFwText::parseJson($result);
	}

	/**
	 * Send HTTP request using POST method.
	 *
	 * @param   string   $link  The URL to send request to.
	 * @param   array    $data  Data to post.
	 *
	 * @return  mixed
	 */
	public static function post($link, $data = array())
	{
		// Send request.
		$client = new JHttp();
		$result = $client->post($link, $data);

		if (!$result)
		{
			throw new Exception(JText::sprintf('JSN_EXTFW_HTTP_REQUEST_FAIL', parse_url($link, PHP_URL_HOST)));
		}

		return JsnExtFwText::parseJson($result->body);
	}
}

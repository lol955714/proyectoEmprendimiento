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
 * Class for handling product update.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwUpdate
{

	/**
	 * Check if the specified component has product update.
	 *
	 * @param   mixed  $component  Component to check for product update.
	 * @param   array  $data       Array of JSN extension info.
	 *
	 * @return  mixed
	 */
	public static function check($component = null, $data = null)
	{
		// Prepare list of extensions to check for update.
		if (!is_array($component))
		{
			// Verify component.
			$option = JsnExtFwHelper::getComponent($component);

			// Get component identified name.
			$component = array(
				JsnExtFwHelper::getConstant('IDENTIFIED_NAME', $component) => JsnExtFwHelper::getConstant('VERSION', $component)
			);

			// Get identified name of component dependencies.
			foreach (JsnExtFwHelper::getDependencies($option) as $dep)
			{
				// Check installed dependency version.
				$dbo = JFactory::getDbo();
				$nfo = json_decode(
					$dbo->setQuery(
						$dbo->getQuery(true)
							->select('manifest_cache')
							->from('#__extensions')
							->where('type = ' . $dbo->quote($dep['type']))
							->where('folder = ' . $dbo->quote($dep['folder']))
							->where('element = ' . $dbo->quote($dep['name'])))
						->loadResult());

				$component[(string) $dep['identified_name']] = $nfo->version;
			}
		}

		// Get latest product info from JSN LightCart if not provided.
		if (empty($data))
		{
			// Generate path to cache file.
			$link = JSN_VERSIONING_URL . '?category=cat_extension';
			$data = JsnExtFwHttp::get($link, 60 * 60);

			if (empty($data))
			{
				return false;
			}
		}

		// Loop thru JSN extension info to check for available update.
		$has_update = array();

		foreach ($data['items'] as $ext)
		{
			if (!empty($ext['identified_name']) && array_key_exists($ext['identified_name'], $component))
			{
				if (version_compare($ext['version'], $component[$ext['identified_name']], '>'))
				{
					$has_update[] = $ext;
				}
			}

			if (isset($ext['items']) && is_array($ext['items']) && count($ext['items']))
			{
				if ($sub_has_update = self::check($component, $ext))
				{
					$has_update = array_merge($has_update, $sub_has_update);
				}
			}
		}

		return count($has_update) ? $has_update : false;
	}
}

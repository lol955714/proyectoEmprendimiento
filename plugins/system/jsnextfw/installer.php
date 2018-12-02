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
 * Installer class.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class PlgSystemJsnExtFwInstallerScript
{

	/**
	 * Implement preflight hook.
	 *
	 * This step will be verify permission for install/update process.
	 *
	 * @param   string  $route  Route type: install, update or uninstall.
	 * @param   object  $_this  The installer object.
	 *
	 * @return  boolean
	 */
	public function preflight($route, $_this)
	{}

	/**
	 * Implement postflight hook.
	 *
	 * @param   string  $route  Route type: install, update or uninstall.
	 * @param   object  $_this  The installer object.
	 *
	 * @return  boolean
	 */
	public function postflight($route, $_this)
	{
		// Get a database connector object.
		$dbo = JFactory::getDbo();

		// Enable plugin by default.
		$dbo->setQuery(
			$dbo->getQuery(true)
				->update('#__extensions')
				->set(array(
				'enabled = 1',
				'protected = 1',
				'ordering = -999'
			))
				->where("element = 'jsnextfw'")
				->where("type = 'plugin'")
				->where("folder = 'system'"))
			->execute();
	}
}

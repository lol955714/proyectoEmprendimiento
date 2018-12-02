<?php
/**
 * @version    $Id$
 * @package    JSN_PageBuilder3
 * @author     JoomlaShine Team <support@joomlashine.com>
 * @copyright  Copyright (C) 2012 JoomlaShine.com. All Rights Reserved.
 * @license    GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.joomlashine.com
 * Technical Support:  Feedback - http://www.joomlashine.com/contact-us/get-support.html
 */

// No direct access to this file
defined( '_JEXEC' ) OR die( 'Restricted access' );

/**
 * Subinstall script for finalizing JSN PageBuilder 3 content plugin for loading module via ID.
 *
 * @package  JSN_PageBuilder3
 */
class PlgContentPB3InstallerScript
{
	/**
	 * Implement preflight hook.
	 *
	 * This step will be verify permission for install/update process.
	 *
	 * @param   string  $mode    Install or update?
	 * @param   object  $parent  JInstaller object.
	 *
	 * @return  boolean
	 */
	public function preflight( $mode, $parent )
	{
		$app = JFactory::getApplication();

		// Check current Joomla! version, only allow install if version >= 3.0
		$JVersion = new JVersion;

		if ( version_compare( $JVersion->RELEASE, '3.0', '<' ) )
		{
			$app->enqueueMessage( 'Plugin is not compatible with current Joomla! version, installation fail.', 'error' );

			return false;
		}
	}

	/**
	 * Enable JSN PageBuilder 3 content plugin.
	 *
	 * @param   string  $route  Route type: install, update or uninstall.
	 * @param   object  $_this  The installer object.
	 *
	 * @return  boolean
	 */
	public function postflight( $route, $_this )
	{
		// Get a database connector object
		$db = JFactory::getDbo();

		try
		{
			// Enable plugin by default
			$q = $db->getQuery( true );

			$q
				->update( '#__extensions' )
				->set( array( 'enabled = 1' ) )
				->where( "element = 'pagebuilder3'" )
				->where( "type = 'plugin'", 'AND' )
				->where( "folder = 'content'", 'AND' );

			$db->setQuery( $q )->execute();
		}
		catch ( Exception $e )
		{
			throw $e;
		}
	}
}

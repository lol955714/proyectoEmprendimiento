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
jimport('joomla.filesystem.folder');

/**
 * Subinstall script for finalizing JSN PageBuilder 3 system plugin.
 *
 * @package  JSN_PageBuilder3
 */
class PlgSystemPageBuilder3InstallerScript {
	/**
	 * Implement preflight hook.
	 *
	 * This step will be verify permission for install/update process.
	 *
	 * @param   string $mode   Install or update?
	 * @param   object $parent JInstaller object.
	 *
	 * @return  boolean
	 */
	public function preflight( $mode, $parent ) {
		$app = JFactory::getApplication();

		// Check current Joomla! version, only allow install if version >= 3.0
		$JVersion = new JVersion;

		if ( version_compare( $JVersion->RELEASE, '3.0', '<' ) )
		{
			$app->enqueueMessage( 'Plugin is not compatible with current Joomla! version, installation fail.', 'error' );

			return false;
		}
        // Clear old build file
        try {
            if (JFolder::exists(JPATH_ROOT . '/plugins/system/pagebuilder3/assets')) {
                JFolder::delete(JPATH_ROOT . '/plugins/system/pagebuilder3/assets');
            }
        } catch (Exception $e) {
		    throw new $e;
        }
	}

	/**
	 * Enable JSN PageBuilder 3 system plugin.
	 *
	 * @param   string $route Route type: install, update or uninstall.
	 * @param   object $_this The installer object.
	 *
	 * @return  boolean
	 */
	public function postflight( $route, $_this ) {
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
				->where( "folder = 'system'", 'AND' );

			$db->setQuery( $q )->execute();
		}
		catch ( Exception $e )
		{
			throw $e;
		}
	}
}

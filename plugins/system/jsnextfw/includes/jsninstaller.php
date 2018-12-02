<?php
/**
 * @version    $Id$
 * @package    JSN_Framework
 * @author     JoomlaShine Team <support@joomlashine.com>
 * @copyright  Copyright (C) 2012 JoomlaShine.com. All Rights Reserved.
 * @license    GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.joomlashine.com
 * Technical Support:  Feedback - http://www.joomlashine.com/contact-us/get-support.html
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Never declare the class twice.
if (class_exists('JSNInstallerScript'))
{
	return;
}

// Disable notice and warning by default for our products.
// The reason for doing this is if any notice or warning appeared then handling JSON string will fail in our code.
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

/**
 * Class for finalizing JSN extension installation.
 *
 * @package  JSN_Framework
 * @since    2.1.6
 */
abstract class JSNInstallerScript
{
	/**
	 * Implement preflight hook.
	 *
	 * This step will be verify permission for install/update process.
	 *
	 * @param   string  $route      Route type: install, update or uninstall.
	 * @param   object  $installer  The installer object.
	 *
	 * @return  boolean
	 */
	public function preflight($route, $installer)
	{}

	/**
	 * Implement postflight hook.
	 *
	 * @param   string  $route      Route type: install, update or uninstall.
	 * @param   object  $installer  The installer object.
	 *
	 * @return  boolean
	 */
	public function postflight($route, $installer)
	{}
}

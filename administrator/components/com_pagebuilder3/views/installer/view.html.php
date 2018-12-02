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
defined('_JEXEC') or die('Restricted access');

// Import JSN Installer library
require_once JPATH_COMPONENT_ADMINISTRATOR . '/libraries/joomlashine/installer/view.php';

/**
 * Installer view
 *
 * @package  JSN_PageBuilder3
 * @since    1.0.0
 */
class JSNPageBuilder3ViewInstaller extends JSNInstallerView
{
	/**
	 * Method to get the model object
	 *
	 * @param   string  $name  The name of the model (optional)
	 *
	 * @return  object
	 */
	public function getModel($name = '')
	{
		require_once JPATH_COMPONENT_ADMINISTRATOR . '/models/installer.php';

		$model = new JSNPageBuilder3ModelInstaller;

		return $model;
	}
}

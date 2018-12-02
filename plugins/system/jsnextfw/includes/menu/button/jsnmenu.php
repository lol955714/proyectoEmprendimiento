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

// Generate to include file.
if (is_dir(JPATH_ROOT . '/plugins/system/jsnframework/libraries/joomlashine/menu/button'))
{
	$path = JPATH_ROOT . '/plugins/system/jsnframework/libraries/joomlashine/menu/button';
}
else
{
	$path = dirname(__FILE__);
}

// Get installed Joomla version.
$version = new JVersion();

// Include file depends on Joomla version.
if (version_compare($version->getShortVersion(), '3.0'))
{
	return require_once "{$path}/compat/jsnmenu_j30.php";
}
elseif (version_compare($version->getShortVersion(), '2.5'))
{
	return require_once "{$path}/compat/jsnmenu_j25.php";
}

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

// Check if JoomlaShine extension framework is disabled?
$framework = JTable::getInstance('Extension');
$framework->load(array(
	'element' => 'jsnextfw',
	'type' => 'plugin',
	'folder' => 'system'
));

if ($framework->extension_id && !$framework->enabled)
{
	try
	{
		// Enable our extension framework
		$framework->enabled = 1;
		$framework->store();
	}
	catch (Exception $e)
	{
		JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
	}
}

// Get admin component directory
$path = dirname(__FILE__);

// Load constant definition file
require_once "{$path}/pagebuilder3.defines.php";

// Setup necessary include paths
JTable::addIncludePath("{$path}/tables");

JModelLegacy::addIncludePath("{$path}/models");
JModelLegacy::addTablePath("{$path}/tables");

JHtml::addIncludePath("{$path}/elements/html");

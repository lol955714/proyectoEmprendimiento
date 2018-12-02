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

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Get the current settings.
$settings = JsnExtFwHelper::getSettings($component);

// @formatter:off
?>
<div
	class="jsn-bootstrap4"
	data-render="api.Privacy"
	data-enabled="<?php echo isset($settings['allow_tracking']) ? $settings['allow_tracking'] : 0; ?>"
	data-extension="<?php echo $component; ?>"
	data-text-mapping="<?php
		echo JsnExtFwText::toJson(JsnExtFwText::translate(
			array(
				'JSN_EXTFW_CONFIGURATION_PRIVACY_INTRO',
				'JSN_EXTFW_TRACKING_CONFIRMATION_AGREE_BUTTON',
				'JSN_EXTFW_SAVE_SETTINGS'
			)
		));
	?>"
></div>

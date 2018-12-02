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

// Generate component text key.
$txtKey = strtoupper(substr($component, 4));

// @formatter:off
?>
<div
	class="jsn-bootstrap4"
	data-render="api.Update"
	data-trigger="a.update-jsn-product"
	data-extension="<?php echo $component; ?>"
	data-updates="<?php echo JsnExtFwText::toJson($updates); ?>"
	data-text-mapping="<?php
		echo JsnExtFwText::toJson(JsnExtFwText::translate(
			array(
				$txtKey,

				'JSN_EXTFW_UPDATE_MODAL_TITLE',
				'JSN_EXTFW_UPDATE_INSTALL_BUTTON_TEXT',
				'JSN_EXTFW_UPDATE_CANCEL_BUTTON_TEXT',
				'JSN_EXTFW_UPDATE_MODAL_CONFIRM_MESSAGE',
				'JSN_EXTFW_UPDATE_MODAL_CONFIRM_IMPORTANT_INFO',
				'JSN_EXTFW_UPDATE_MODAL_CONFIRM_IMPORTANT_NOTE',
				'JSN_EXTFW_UPDATE_MODAL_CONFIRM_IMPORTANT_FRAMEWORK',
				'JSN_EXTFW_UPDATE_MODAL_CONFIRM_IMPORTANT_EXTENSION',
				'JSN_EXTFW_UPDATE_MODAL_CONFIRM_IMPORTANT_EXTENSION_ONLY',

				'JSN_EXTFW_UPDATE_MODAL_INSTALL_MESSAGE',
				'JSN_EXTFW_UPDATE_DOWNLOAD_AND_INSTALL_EXTENSION',

				'JSN_EXTFW_CLOSE'
			)
		));
	?>"
></div>

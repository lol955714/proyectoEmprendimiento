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

// Check for component update.
$hasUpdate = JsnExtFwUpdate::check($component);

// Generate component text key.
$txtKey = strtoupper(substr($component, 4));

// @formatter:off
?>
<div
	class="jsn-bootstrap4"
	data-render="api.Footer"
	data-extension="<?php echo $component; ?>"
	data-version="<?php echo JsnExtFwHelper::getConstant('VERSION', $component); ?>"
	data-framework-version="<?php echo JSNEXTFW_VERSION; ?>"
	data-updates="<?php echo JsnExtFwText::toJson($updates); ?>"
	data-text-mapping="<?php
		echo JsnExtFwText::toJson(JsnExtFwText::translate(
			array(
				$txtKey,

				'JSN_EXTFW_UPDATE_TO',
				'JSN_EXTFW_POWERED_BY'
			)
		));
	?>"
></div>
<?php
if ($hasUpdate) {
	JsnExtFwHtml::renderUpdateComponent($component, $hasUpdate);
}

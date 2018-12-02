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

// @formatter:off
?>
<div
	class="jsn-bootstrap4"
	data-render="api.ElementForm"
	data-form="<?php echo JsnExtFwText::toJson($form); ?>"
	data-values="<?php echo JsnExtFwText::toJson((object) $current); ?>"
	data-text-mapping="<?php echo JsnExtFwText::toJson((object) $textMapping); ?>"
	data-save-handler="<?php echo $save; ?>"
	<?php if (!empty($selector)) : ?>
	data-inline="false"
	data-save-button="<?php echo $selector; ?>"
	<?php endif; ?>
></div>

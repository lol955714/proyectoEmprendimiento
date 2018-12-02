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
	id="jsn-header-bar"
	class="jsn-bootstrap4"
	data-render="api.Header"
	data-extension="<?php echo $component; ?>"
	data-renew-link="<?php echo JSN_CUSTOMER_AREA; ?>"
	data-purchase-link="<?php echo JsnExtFwHelper::getConstant('BUY_LINK', $component); ?>"
	data-text-mapping="<?php echo JsnExtFwText::toJson($text); ?>"
></div>

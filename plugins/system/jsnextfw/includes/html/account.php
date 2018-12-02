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
	data-render="api.Account"
	data-extension="<?php echo $component; ?>"
	data-text-mapping="<?php
		echo JsnExtFwText::toJson(array_merge(JsnExtFwText::translate(
			array(
				'JSN_EXTFW_USER_ACCOUNT_USER_DETAILS',
				'JSN_EXTFW_USER_ACCOUNT_YOU_ARE_REGISTERED_WITH_THE_FOLLOWING_ACCOUNT',
				'JSN_EXTFW_USERNAME',
				'JSN_EXTFW_USER_ACCOUNT_TOKEN_KEY',
				'JSN_EXTFW_USER_ACCOUNT_PRODUCT_LICENSE',
				'JSN_EXTFW_USER_ACCOUNT_DOMAIN_IS_REGISTERED_AT_THE_FOLLOWING_LICENSE',
				'JSN_EXTFW_USER_ACCOUNT_EDITION',
				'JSN_EXTFW_TRY_PRO',
				'JSN_EXTFW_RELATED_TO',
				'JSN_EXTFW_USER_ACCOUNT_EXPIRATION_DATE',
				'JSN_EXTFW_NEVER',
				'JSN_EXTFW_USER_ACCOUNT_REFRESH_LICENSE',
				'JSN_EXTFW_USER_ACCOUNT_UNLINK_ACCOUNT'
			)
		), $text));
	?>"
></div>

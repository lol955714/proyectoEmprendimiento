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
<div class="jsn-bootstrap4 jsn-content-main jsn-settings">
	<div class="horizontal-form">
		<ul class="nav nav-tabs" role="tablist">
			<li class="nav-item active">
				<a class="nav-link" id="configuration-tab" data-toggle="tab" href="#configuration-pane" role="tab">
					<?php echo JText::_('JSN_EXTFW_CONFIGURATION'); ?>
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="languages-tab" data-toggle="tab" href="#languages-pane" role="tab">
					<?php echo JText::_('JSN_EXTFW_CONFIGURATION_LANGUAGE'); ?>
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="user-account-tab" data-toggle="tab" href="#user-account-pane" role="tab">
					<?php echo JText::_('JSN_EXTFW_CONFIGURATION_USER_ACCOUNT'); ?>
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="privacy-settings-tab" data-toggle="tab" href="#privacy-settings-pane" role="tab">
					<?php echo JText::_('JSN_EXTFW_CONFIGURATION_PRIVACY_SETTINGS'); ?>
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="global-params-tab" data-toggle="tab" href="#global-params-pane" role="tab">
					<?php echo JText::_('JSN_EXTFW_CONFIGURATION_GLOBAL_PARAMETERS'); ?>
				</a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane fade in active" id="configuration-pane" role="tabpanel">
				<?php JsnExtFwHtml::renderSettingsForm($component, '#toolbar-apply .button-apply'); ?>
			</div>
			<div class="tab-pane fade" id="languages-pane" role="tabpanel">
				<?php JsnExtFwHtml::renderLanguageForm($component, '#save-languages'); ?>

				<hr />

				<button id="save-languages" type="button" class="btn btn-primary d-block mx-auto">
					<?php echo JText::_('JSN_EXTFW_INSTALL_SELECTED_LANGUAGES'); ?>
				</button>
			</div>
			<div class="tab-pane fade" id="user-account-pane" role="tabpanel">
				<?php JsnExtFwHtml::renderAccountPane($component); ?>
			</div>
			<div class="tab-pane fade" id="privacy-settings-pane" role="tabpanel">
				<?php JsnExtFwHtml::renderPrivacySettings($component); ?>
			</div>
			<div class="tab-pane fade" id="global-params-pane" role="tabpanel">
				<?php JsnExtFwHtml::renderSettingsForm('jsnextfw', '#toolbar-apply .button-apply', null, 'config/framework.json'); ?>
			</div>
		</div>
	</div>
</div>

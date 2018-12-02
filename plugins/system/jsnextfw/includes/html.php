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

// No direct access to this file.
defined('_JEXEC') or die('Restricted access');

// Import necessary libraries.
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Class for rendering HTML output.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwHtml
{

	/**
	 * Render the configuration page.
	 *
	 * @param   string  $component  Affected component.
	 *
	 * @return  void
	 */
	public static function renderConfigurationPage($component = null)
	{
		// Load template file.
		include JSNEXTFW_PATH . '/includes/html/configuration.php';
	}

	/**
	 * Render settings form.
	 *
	 * @param   string  $component  Affected component.
	 * @param   string  $selector   CSS selector of the button to save settings.
	 * @param   string  $save       URL that handles saving settings.
	 * @param   mixed   $form       Either form declaration array or path to .json file.
	 * @param   array   $current    Current component settings.
	 *
	 * @return  void
	 */
	public static function renderSettingsForm($component = null, $selector = null, $save = null, $form = 'config.json', $current = null)
	{
		// Verify component.
		$component = JsnExtFwHelper::getComponent($component);

		// Prepare save handler.
		if (empty($save))
		{
			$save = "action=update&component={$component}&" . JSession::getFormToken() . '=1';
			$save = JRoute::_("index.php?option=com_ajax&format=json&plugin=jsnextfw&context=settings&{$save}", false);
		}

		// Load settings from JSON file.
		if (is_string($form))
		{
			if (!JFile::exists($form))
			{
				// Try to find for form declaration file.
				if ($component === 'jsnextfw')
				{
					$form = JPATH_ROOT . "/plugins/system/jsnextfw/{$form}";
				}
				else
				{
					$form = JPATH_ADMINISTRATOR . "/components/{$component}/{$form}";
				}

				if (!JFile::exists($form))
				{
					error_log("Not found form declaration file {$form}.");

					return;
				}
			}

			// Get form declaration from .json file.
			$form = json_decode(file_get_contents($form), true);
		}

		// Verify form declaration.
		if (!is_array($form))
		{
			throw new Exception(sprintf('Invalid form declaration %s', print_r($form, true)));
		}

		// Add component for event tracking.
		if (!function_exists('prepareFormForEventTracking'))
		{
			function prepareFormForEventTracking($form, $ext)
			{
				foreach ($form as $k => $v)
				{
					if ($k === 'controls')
					{
						foreach ($v as $option => $define)
						{
							if (array_key_exists('data-event-tracking', $define))
							{
								$form[$k][$option]['data-event-tracking'] = $ext;

								break;
							}
						}
					}
					elseif (is_array($v))
					{
						$form[$k] = prepareFormForEventTracking($v, $ext);
					}
				}

				return $form;
			}
		}

		$form = prepareFormForEventTracking($form, JsnExtFwHelper::getComponent());

		// Get the current settings.
		if (empty($current))
		{
			$current = JsnExtFwHelper::getSettings($component);
		}

		// Get all translatable strings from form declaration.
		$textMapping = JsnExtFwText::translate(
			array_merge(JsnExtFwHelper::getTranslatableString($form),
				array(
					'JSN_EXTFW_LANGUAGE_EDITOR',
					'JSN_EXTFW_CONFIRM_REVERT_LANGUAGE_FILES',
					'JSN_EXTFW_CHANGES_SAVED_SUCCESSFULLY'
				)));

		// Load required library.
		JsnExtFwAssets::loadNoty();
		JsnExtFwAssets::loadJsnElements();

		// Load template file.
		include JSNEXTFW_PATH . '/includes/html/settings.php';
	}

	/**
	 * Render languages form.
	 *
	 * @param   string  $component  Affected component.
	 * @param   string  $selector   CSS selector of the button to save settings.
	 * @param   string  $save       URL that handles saving settings.
	 *
	 * @return  void
	 */
	public static function renderLanguageForm($component = null, $selector = null, $save = null)
	{
		// Verify component.
		$component = JsnExtFwHelper::getComponent($component);

		// Prepare save handler.
		if (empty($save))
		{
			$save = "action=install&component={$component}&" . JSession::getFormToken() . '=1';
			$save = JRoute::_("index.php?option=com_ajax&format=json&plugin=jsnextfw&context=language&{$save}", false);
		}

		// Get available admin languages.
		if (JFolder::exists(JPATH_ADMINISTRATOR . "/components/{$component}/language/admin"))
		{
			foreach (JFolder::folders(JPATH_ADMINISTRATOR . "/components/{$component}/language/admin") as $lang)
			{
				$languages[$lang]['admin'] = JFolder::files(JPATH_ADMINISTRATOR . "/components/{$component}/language/admin/{$lang}",
					'\.ini$');
			}
		}

		// Get available site languages.
		if (JFolder::exists(JPATH_ADMINISTRATOR . "/components/{$component}/language/site"))
		{
			foreach (JFolder::folders(JPATH_ADMINISTRATOR . "/components/{$component}/language/site") as $lang)
			{
				$languages[$lang]['site'] = JFolder::files(JPATH_ADMINISTRATOR . "/components/{$component}/language/site/{$lang}",
					'\.ini$');
			}
		}

		// Build language form.
		$items = array();
		$values = array();

		foreach ($languages as $lang => $types)
		{
			$item = array(
				'key' => $lang,
				'disabled' => ( $lang === 'en-GB' )
			);

			foreach ($types as $type => $files)
			{
				$item['options'][] = array(
					'label' => JText::_('JSN_EXTFW_LANGUAGE_' . strtoupper(str_replace('-', '_', $lang . '_' . $type))),
					'value' => $type
				);

				// Check if language is installed?
				$installed = true;

				foreach ($files as $file)
				{
					if (!JFile::exists(( $type == 'admin' ? JPATH_ADMINISTRATOR : JPATH_ROOT ) . "/language/{$lang}/{$file}"))
					{
						$installed = false;

						break;
					}
				}

				$values[$lang][$type] = (int) $installed;
			}

			$items[] = $item;
		}

		$form = array(
			'controls' => array(
				'languages' => array(
					'type' => 'language',
					'items' => $items,
					'label' => 'JSN_EXTFW_LANGUAGE_TITLE',
					'extension' => $component
				)
			)
		);

		// Render settings form.
		self::renderSettingsForm($component, $selector, $save, $form, array(
			'languages' => $values
		));
	}

	/**
	 * Render user account.
	 *
	 * @param   string  $component  Affected component.
	 * @param   array   $text       Array of text translation mapping.
	 *
	 * @return  void
	 */
	public static function renderAccountPane($component = null, $text = null)
	{
		// Verify component.
		$component = JsnExtFwHelper::getComponent($component);

		// Prepare text translation.
		if (empty($text))
		{
			$text = array(
				'account-cta-name' => 'CTA Settings'
			);
		}

		// Load required library.
		JsnExtFwAssets::loadJsnComponents();
		JsnExtFwAssets::loadEditionManager();

		// Load template file.
		include JSNEXTFW_PATH . '/includes/html/account.php';
	}

	/**
	 * Render privacy settings.
	 *
	 * @param   string  $component  Affected component.
	 *
	 * @return  void
	 */
	public static function renderPrivacySettings($component = null)
	{
		// Verify component.
		$component = JsnExtFwHelper::getComponent($component);

		// Load required library.
		JsnExtFwAssets::loadJsnComponents();

		// Load template file.
		include JSNEXTFW_PATH . '/includes/html/privacy.php';
	}

	/**
	 * Render the help page.
	 *
	 * @param   string  $component  Affected component.
	 * @param   array   $items      Array of help items.
	 *
	 * @return  void
	 */
	public static function renderHelpPage($component = null, $items = null)
	{
		// Verify component.
		$component = JsnExtFwHelper::getComponent($component);

		// Define default help items if not specified.
		if (empty($items))
		{
			$items = array(
				array(
					'title' => JText::_('JSN_EXTFW_HELP_DOCUMENTATION_TITLE'),
					'image' => JSNEXTFW_URL . '/assets/joomlashine/img/help-documentation.png',
					'description' => JText::_('JSN_EXTFW_HELP_DOCUMENTATION_DESCRIPTION'),
					'link' => JsnExtFwHelper::getConstant('DOC_LINK', $component),
					'link_text' => JText::_('JSN_EXTFW_HELP_DOCUMENTATION_LINK_TEXT'),
					'data-event-tracking' => $component,
					'data-event-category' => 'Help',
					'data-event-action' => 'Read Documentation'
				),
				array(
					'title' => JText::_('JSN_EXTFW_HELP_FORUM_TITLE'),
					'image' => JSNEXTFW_URL . '/assets/joomlashine/img/help-forum.png',
					'description' => JText::_('JSN_EXTFW_HELP_FORUM_DESCRIPTION'),
					'link' => JSN_SUPPORT_URL,
					'link_text' => JText::_('JSN_EXTFW_HELP_FORUM_LINK_TEXT'),
					'data-event-tracking' => $component,
					'data-event-category' => 'Help',
					'data-event-action' => 'Visit Forum'
				)
			);
		}

		// Load required library.
		JsnExtFwAssets::loadJsnComponents();

		// Load template file.
		include JSNEXTFW_PATH . '/includes/html/help.php';
	}

	/**
	 * Render the about page.
	 *
	 * @param   string  $component  Affected component.
	 * @param   string  $logo       URL to product logo.
	 * @param   array   $text       Array of text translation mapping.
	 *
	 * @return  void
	 */
	public static function renderAboutPage($component = null, $logo = '', $text = null)
	{
		// Verify component.
		$component = JsnExtFwHelper::getComponent($component);

		// Prepare text translation.
		if (empty($text))
		{
			$text = array(
				'about-cta-name' => 'CTA About'
			);
		}

		// Load required library.
		JsnExtFwAssets::loadJsnComponents();
		JsnExtFwAssets::loadEditionManager();

		// Load template file.
		include JSNEXTFW_PATH . '/includes/html/about.php';
	}

	/**
	 * Render the product update component.
	 *
	 * @param   string  $component  Affected component.
	 * @param   string  $updates    Product update data.
	 *
	 * @return  void
	 */
	public static function renderUpdateComponent($component = null, $updates = null)
	{
		static $rendered;

		if (!isset($rendered))
		{
			// Verify component.
			$component = JsnExtFwHelper::getComponent($component);

			// Get update data if not specified.
			if (empty($updates))
			{
				$updates = JsnExtFwUpdate::check($component);
			}

			// Load required library.
			JsnExtFwAssets::loadJsnComponents();

			// Load template file.
			include JSNEXTFW_PATH . '/includes/html/update.php';

			$rendered = true;
		}
	}

	/**
	 * Render the header component.
	 *
	 * @param   string  $component  Affected component.
	 * @param   string  $text       Text translation mapping.
	 *
	 * @return  void
	 */
	public static function renderHeaderComponent($component = null, $text = array())
	{
		// Verify component.
		$component = JsnExtFwHelper::getComponent($component);

		// Generate component text key.
		$txtKey = strtoupper(substr($component, 4));

		// Prepare text translation mapping.
		$text = array_merge(
			array(
				$txtKey => JText::_($txtKey),
				'header-cta-name' => 'Header',
				'header-cta-message-for-free-user' => JText::_('JSN_EXTFW_HEADER_CTA_MESSAGE_FREE'),
				'header-cta-message-for-trial-user' => JText::_('JSN_EXTFW_HEADER_CTA_MESSAGE_TRIAL'),
				'header-cta-message-for-expired-trial-user' => JText::_('JSN_EXTFW_HEADER_CTA_MESSAGE_TRIAL_EXPIRED'),
				'header-cta-message-for-pro-user' => JText::_('JSN_EXTFW_HEADER_CTA_MESSAGE_PRO'),
				'header-cta-message-for-expired-pro-user' => JText::_('JSN_EXTFW_HEADER_CTA_MESSAGE_PRO_EXPIRED'),
				'header-cta-button-for-free-user' => JText::_('JSN_EXTFW_HEADER_CTA_BUTTON_FREE'),
				'header-cta-button-for-trial-user' => JText::_('JSN_EXTFW_HEADER_CTA_BUTTON_TRIAL'),
				'header-cta-button-for-pro-user' => JText::_('JSN_EXTFW_HEADER_CTA_BUTTON_PRO')
			), (array) $text);

		// Load required assets.
		JsnExtFwAssets::loadJsnStyles();
		JsnExtFwAssets::loadJsnComponents();

		// Load template file.
		include JSNEXTFW_PATH . '/includes/html/header.php';
	}

	/**
	 * Render the footer component.
	 *
	 * @param   string  $component  Affected component.
	 * @param   string  $updates    Product update data.
	 * @param   string  $screen     Name of the current screen.
	 *
	 * @return  void
	 */
	public static function renderFooterComponent($component = null, $updates = null, $screen = null)
	{
		// Verify component.
		$component = JsnExtFwHelper::getComponent($component);

		// Get update data if not specified.
		if (empty($updates))
		{
			if ($updates = JsnExtFwUpdate::check($component))
			{
				// Add event tracking.
				foreach ($updates as &$update) {
					$update['data-event-tracking'] = $component;
					$update['data-event-category'] = 'Update';
					$update['data-event-action'] = 'Update';
					$update['data-event-label'] = 'CTA Footer' . ($screen ? ": {$screen}" : '');
				}
			}
		}

		// Load required assets.
		JsnExtFwAssets::loadJsnStyles();
		JsnExtFwAssets::loadJsnComponents();

		// Load template file.
		include JSNEXTFW_PATH . '/includes/html/footer.php';
	}
}

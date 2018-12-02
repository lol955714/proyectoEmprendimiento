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
jimport('joomla.filesystem.file');

/**
 * Class for handling assets related actions.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 *
 * @method   void  loadJsnComponents($inline = false, $type = 'both')  Load JSN Components Javascript library.
 * @method   void  loadJsnHelpers($inline = false, $type = 'both')     Load JSN Helpers Javascript library.
 * @method   void  loadJsnMixins($inline = false, $type = 'both')      Load JSN Mixins Javascript library.
 * @method   void  loadJsnStyles($inline = false, $type = 'both')      Load JSN stylesheets.
 *
 * @method   void  loadBase64($inline = false, $type = 'both')       Load Base64 Javascript library.
 * @method   void  loadBootstrap($inline = false, $type = 'both')    Load Bootstrap CSS framework.
 * @method   void  loadCookie($inline = false, $type = 'both')       Load Cookie Javascript library.
 * @method   void  loadFontAwesome($inline = false, $type = 'both')  Load FontAwesome CSS library.
 * @method   void  loadInteract($inline = false, $type = 'both')     Load Interact Javascript library.
 * @method   void  loadNoty($inline = false, $type = 'both')         Load Noty Javascript library.
 * @method   void  loadPopper($inline = false, $type = 'both')       Load Popper Javascript library.
 * @method   void  loadReact($inline = false, $type = 'both')        Load React and ReactDOM Javascript library.
 * @method   void  loadSortable($inline = false, $type = 'both')     Load Sortable Javascript library.
 * @method   void  loadUnderscore($inline = false, $type = 'both')   Load Underscore Javascript library.
 */
class JsnExtFwAssets
{

	/**
	 * Define assets.
	 *
	 * @var  array
	 */
	protected static $assets = array(
		/**
		 * JSN assets.
		 */
		'jsn-common' => 'assets/joomlashine/js/common.js',
		'jsn-components' => array(
			'jsn-elements',
			'assets/joomlashine/js/components.js'
		),
		'jsn-elements' => array(
			'jsn-styles',
			'jsn-mixins',
			'assets/joomlashine/js/elements.js'
		),
		'jsn-helpers' => array(
			'base64',
			'jsn-common',
			'assets/joomlashine/js/helpers.js'
		),
		'jsn-mixins' => array(
			'react',
			'jsn-helpers',
			'assets/joomlashine/js/mixins.js'
		),
		'jsn-styles' => array(
			'bootstrap',
			'font-awesome',
			'assets/joomlashine/css/style.css',
			'assets/joomlashine/css/custom.css'
		),

		/**
		 * 3rd-party assets.
		 */
		'base64' => 'assets/vendors/base64.min.js',
		'bootstrap' => array(
			'assets/vendors/bootstrap/css/bootstrap.css'
		),
		'cookie' => 'assets/vendors/js.ck.js',
		'font-awesome' => 'assets/vendors/font-awesome/css/font-awesome.min.css',
		'interact' => 'assets/vendors/interact.min.js',
		'noty' => array(
			'assets/vendors/noty/animate.css',
			'jquery',
			'assets/vendors/noty/jquery.noty.js'
		),
		'popper' => 'assets/vendors/popper.min.js',
		'react' => array(
			'assets/vendors/react/react.min.js',
			'assets/vendors/react/react-dom.min.js'
		),
		'sortable' => 'assets/vendors/html.sortable.min.js',
		'spectrum' => array(
			'assets/vendors/spectrum/spectrum.css',
			'assets/vendors/spectrum/spectrum.js'
		),
		'underscore' => 'assets/vendors/underscore-min.js'
	);

	/**
	 * Array of loaded script.
	 *
	 * @param  array
	 */
	protected static $loaded = array();

	/**
	 * Load assets.
	 *
	 * @param   mixed    $assets  Assets to load.
	 * @param   boolean  $inline  Whether to print the HTML code immediately.
	 * @param   string   $type    Whether 'css', 'js' or 'both'.
	 *
	 * @return  void
	 */
	public static function load($assets, $inline = false, $type = 'both')
	{
		foreach ((array) $assets as $url)
		{
			// Prepare asset URL.
			if (!preg_match('#^(https?:/)?/#i', $url))
			{
				$method = 'load' . implode(array_map('ucfirst', explode('-', $url)));

				if (method_exists(__CLASS__, $method))
				{
					call_user_func(array(
						__CLASS__,
						$method
					), $inline);

					continue;
				}
				elseif (array_key_exists($url, self::$assets))
				{
					self::load(self::$assets[$url], $inline, $type);

					continue;
				}
				elseif (JFile::exists(JSNEXTFW_PATH . '/' . $url))
				{
					$url = JSNEXTFW_URL . '/' . $url;
				}
				elseif (JFile::exists(JPATH_ROOT . '/' . $url))
				{
					$url = JUri::root(true) . '/' . $url;
				}
				else
				{
					continue;
				}
			}

			// Simply continue if asset is loaded before.
			if (in_array($url, self::$loaded))
			{
				continue;
			}

			// Detect asset type.
			$asset_type = current(explode('?', $url));
			$asset_type = substr($asset_type, strrpos($asset_type, '.') + 1);

			// Load asset if allowed.
			if ($asset_type == 'css' && ( $type == 'css' || $type == 'both' ))
			{
				self::loadStylesheet($url, $inline);
			}
			elseif ($asset_type == 'js' && ( $type == 'js' || $type == 'both' ))
			{
				self::loadScript($url, $inline);
			}
		}
	}

	/**
	 * Load a stylesheet.
	 *
	 * @param   string   $url     Stylesheet link.
	 * @param   boolean  $inline  Whether to print the HTML code immediately.
	 *
	 * @return  void
	 */
	public static function loadStylesheet($url, $inline = false)
	{
		if (!in_array($url, self::$loaded))
		{
			self::$loaded[] = $url;

			// Generate version string.
			if (preg_match('#/(components/com_[^/]+|plugins/[^/]+/[^/]+)/#', $url, $match))
			{
				if ($version = JsnExtFwHelper::getConstant('version', basename($match[1])))
				{
					$url .= (strpos($url, '?') === false ? '?' : '&') . md5($version);
				}
			}

			if ($inline)
			{
				echo '<link href="' . $url . '" rel="stylesheet" />';
			}
			else
			{
				JFactory::getDocument()->addStyleSheet($url);
			}
		}
	}

	/**
	 * Load a script.
	 *
	 * @param   string   $url     Script link.
	 * @param   boolean  $inline  Whether to print the HTML code immediately.
	 *
	 * @return  void
	 */
	public static function loadScript($url, $inline = false)
	{
		if (!in_array($url, self::$loaded))
		{
			self::$loaded[] = $url;

			// Generate version string.
			if (preg_match('#/(components/com_[^/]+|plugins/[^/]+/[^/]+)/#', $url, $match))
			{
				if ($version = JsnExtFwHelper::getConstant('version', basename($match[1])))
				{
					$url .= (strpos($url, '?') === false ? '?' : '&') . md5($version);
				}
			}

			if ($inline)
			{
				echo '<script src="' . $url . '"></script>';
			}
			else
			{
				JFactory::getDocument()->addScript($url);
			}
		}
	}

	/**
	 * Load inline style.
	 *
	 * @param   string   $code    CSS code to load.
	 * @param   boolean  $inline  Whether to print the HTML code immediately.
	 *
	 * @return  void
	 */
	public static function loadInlineStyle($code, $inline = false)
	{
		if (!in_array(md5($code), self::$loaded))
		{
			if ($inline)
			{
				echo '<style type="text/css">' . $code . '</style>';
			}
			else
			{
				JFactory::getDocument()->addStyleDeclaration($code);
			}

			self::$loaded[] = md5($code);
		}
	}

	/**
	 * Load inline script.
	 *
	 * @param   string   $code    Javascript code to load.
	 * @param   boolean  $inline  Whether to print the HTML code immediately.
	 *
	 * @return  void
	 */
	public static function loadInlineScript($code, $inline = false)
	{
		if (!in_array(md5($code), self::$loaded))
		{
			if ($inline)
			{
				echo '<script type="text/javascript">' . $code . '</script>';
			}
			else
			{
				JFactory::getDocument()->addScriptDeclaration($code);
			}

			self::$loaded[] = md5($code);
		}
	}

	/**
	 * Load jQuery library.
	 *
	 * @param   boolean  $inline  Whether to print the script code to load the specified script immediately.
	 *
	 * @return  void
	 */
	public static function loadJquery($inline = false)
	{
		if ($inline)
		{
			self::loadScript(JUri::root(true) . '/media/jui/js/jquery.js', true);
			self::loadScript(JUri::root(true) . '/media/jui/js/jquery-noconflict.js', true);
			self::loadScript(JUri::root(true) . '/media/jui/js/jquery-migrate.js', true);
		}
		else
		{
			JHtml::_('jquery.framework');
		}
	}

	/**
	 * Load JSN Common Javascript library.
	 *
	 * @param   boolean  $inline  Whether to print the script code to load the specified script immediately.
	 *
	 * @return  void
	 */
	public static function loadJsnCommon($inline = false)
	{
		self::load(self::$assets['jsn-common'], $inline);

		self::loadInlineScript(
			';(function(api) {
				api.urls = ' . json_encode(
				array(
					'root' => JUri::root(true),
					'plugin' => JSNEXTFW_URL,
					'ajaxBase' => 'index.php?option=com_ajax&plugin=jsnextfw&format=json&' . JSession::getFormToken() . '=1'
				)) . ';

				api.Text.setData(' . JsnExtFwText::translate(
				array(
					'JSN_EXTFW_EDIT',
					'JSN_EXTFW_REVERT',
					'JSN_EXTFW_SAVE',
					'JSN_EXTFW_SELECT',
					'JSN_EXTFW_CANCEL',
					'JSN_EXTFW_USERNAME',
					'JSN_EXTFW_PASSWORD',
					'JSN_EXTFW_CLOSE',
					'JSN_EXTFW_NEVER'
				), true) . ');
			})( (JSN = window.JSN || {}) );', $inline);
	}

	/**
	 * Load JSN Elements Javascript library.
	 *
	 * @param   boolean  $inline  Whether to print the script code to load the specified script immediately.
	 *
	 * @return  void
	 */
	public static function loadJsnElements($inline = false)
	{
		self::load(self::$assets['jsn-elements'], $inline);

		// Define path/URL mapping to load custom input controls.
		$paths = array(
			JSNEXTFW_PATH . '/assets/joomlashine/js/inputs' => JSNEXTFW_URL . '/assets/joomlashine/js/inputs'
		);

		// Trigger an event to allow 3rd-party to add more custom input controls.
		JFactory::getApplication()->triggerEvent('onJsnExtFwGetInputControlPath', array(
			&$paths
		));

		// Loop thru path/URL mapping to find all custom input controls.
		$inputs = array();

		foreach ($paths as $path => $url)
		{
			if ($files = glob("{$path}/*.js"))
			{
				if (count($files))
				{
					foreach ($files as $file)
					{
						$inputs[$url][] = substr(basename($file), 0, -3);
					}
				}
			}
		}

		// Pass custom input controls to client.
		if (count($inputs))
		{
			self::loadInlineScript(
				';(function(api) {
					api.inputs = ' . json_encode($inputs) . ';
					api.Text.setData(' . JsnExtFwText::translate(
					array(
						'JSN_EXTFW_LOADING',
						'JSN_EXTFW_NORMAL',
						'JSN_EXTFW_SELECT_ARTICLE',
						'JSN_EXTFW_EDIT_ARTICLE',
						'JSN_EXTFW_SELECT_CONTENT_CATEGORY',
						'JSN_EXTFW_SELECT_CUSTOM_FONT',
						'JSN_EXTFW_SET_HTML_CONTENT',
						'JSN_EXTFW_SELECT_GOOGLE_FONT',
						'JSN_EXTFW_GOOGLE_FONT_CATEGORIES',
						'JSN_EXTFW_GOOGLE_FONT_SUBSETS',
						'JSN_EXTFW_GOOGLE_FONT_TOTAL',
						'JSN_EXTFW_GOOGLE_FONT_SEARCH',
						'JSN_EXTFW_GOOGLE_FONT_VARIANTS',
						'JSN_EXTFW_GOOGLE_FONT_VARIANT',
						'JSN_EXTFW_GOOGLE_FONT_SUBSET',
						'JSN_EXTFW_SELECT_AN_IMAGE',
						'JSN_EXTFW_SELECT_MENU_ITEM',
						'JSN_EXTFW_EDIT_MENU_ITEM',
						'JSN_EXTFW_SELECT_MODULE',
						'JSN_EXTFW_EDIT_MODULE',
						'JSN_EXTFW_PRO_BADGE_TEXT'
					), true) . ');
				})( (JSN = window.JSN || {}) );', $inline);
		}
	}

	/**
	 * Load edition manager.
	 *
	 * @param   string  $callback   A Javascript function that interact with edition manager.
	 * @param   string  $component  Affected component.
	 * @param   array   $params     Parameters.
	 *
	 * @return  void
	 */
	public static function loadEditionManager($callback = null, $component = null, $params = array())
	{
		// Make sure the user is logged in.
		if (JFactory::getUser()->id > 0)
		{
			static $loaded;

			// Verify component.
			$component = JsnExtFwHelper::getComponent($component);

			if (!isset($loaded) || !isset($loaded[$component]))
			{
				// Prepare save handler.
				$save = "component={$component}&" . JSession::getFormToken() . '=1';
				$save = JRoute::_("index.php?option=com_ajax&format=json&plugin=jsnextfw&context=account&{$save}", false);

				// Load required library.
				self::loadJsnElements();

				// Define parameters.
				$txtKey = strtoupper(substr($component, 4));
				$params = array_merge(array(
					'url' => $save,
					'extension' => $component,
					'textMapping' => JsnExtFwText::translate(
						array(
							$txtKey,
							"{$txtKey}_TRY_PRO_TITLE",
							"{$txtKey}_TRY_PRO_MESSAGE",

							'JSN_EXTFW_USER_VERIFICATION_TITLE',
							'JSN_EXTFW_USER_VERIFICATION_SELECT_EXISTING_ACCOUNT',
							'JSN_EXTFW_USER_VERIFICATION_USE_ANOTHER_ACCOUNT',
							'JSN_EXTFW_USER_VERIFICATION_INPUT_CUSTOMER_ACCOUNT',
							'JSN_EXTFW_USER_VERIFICATION_ONE_TIME_REQUIREMENT',
							'JSN_EXTFW_USER_VERIFICATION_FORGOT_ACCOUNT',
							'JSN_EXTFW_USER_VERIFICATION_VERIFY_BUTTON',
							'JSN_EXTFW_USER_VERIFICATION_CANCEL_AND_LEAVE_BUTTON',

							'JSN_EXTFW_PRODUCT_VERIFICATION_TITLE',
							'JSN_EXTFW_PRODUCT_VERIFICATION_FREE_EDITION',
							'JSN_EXTFW_PRODUCT_VERIFICATION_GOT_IT',
							'JSN_EXTFW_PRODUCT_VERIFICATION_ALL_DONE',
							'JSN_EXTFW_PRODUCT_VERIFICATION_INTRODUCTION',
							'JSN_EXTFW_PRODUCT_VERIFICATION_EDITION',
							'JSN_EXTFW_PRODUCT_VERIFICATION_EXPIRATION',
							'JSN_EXTFW_PRODUCT_VERIFICATION_NEVER_EXPIRE',
							'JSN_EXTFW_PRODUCT_VERIFICATION_THANK_YOU',
							'JSN_EXTFW_PRODUCT_VERIFICATION_LETS_GET_STARTED',

							'JSN_EXTFW_TRACKING_CONFIRMATION_TITLE',
							'JSN_EXTFW_TRACKING_CONFIRMATION_CONTENT',
							'JSN_EXTFW_TRACKING_CONFIRMATION_AGREE_BUTTON',
							'JSN_EXTFW_TRACKING_CONFIRMATION_DECLINE_BUTTON',

							'JSN_EXTFW_PRO_INTRODUCTION_TITLE',
							'JSN_EXTFW_PRO_INTRODUCTION_MESSAGE',
							'JSN_EXTFW_PRO_INTRODUCTION_TRY_PRO_BUTTON',
							'JSN_EXTFW_PRO_INTRODUCTION_BUY_PRO_BUTTON',
							'JSN_EXTFW_PRO_INTRODUCTION_LATER_BUTTON',
							'JSN_EXTFW_PRO_INTRODUCTION_TRIAL_EXPIRED',
							'JSN_EXTFW_PRO_INTRODUCTION_PURCHASE_PRO',

							'JSN_EXTFW_TRIAL_REGISTRATION_DONE_TITLE',
							'JSN_EXTFW_TRIAL_REGISTRATION_DONE_MESSAGE',
							'JSN_EXTFW_TRIAL_REGISTRATION_DONE_BUTTON',

							'JSN_EXTFW_TRIAL_REGISTRATION_FAIL_TITLE',
							'JSN_EXTFW_TRIAL_REGISTRATION_FAIL_MESSAGE',
							'JSN_EXTFW_TRIAL_REGISTRATION_FAIL_BUTTON'
						)),
					'forgotUsername' => 'https://www.joomlashine.com/username-reminder-request.html',
					'forgotPassword' => 'https://www.joomlashine.com/password-reset.html',
					'callback' => $callback
				), $params);

				$loaded[$component] = true;
			}
			else
			{
				// Define parameters.
				$params = array_merge(array(
					'callback' => $callback
				), $params);
			}

			// Initialize edition manager.
			self::loadInlineScript(
				';(function(api) {
					(function render() {
						if (api.Edition) {
							api.Edition.init(' . json_encode($params) . ');
						} else {
							setTimeout(render, 100);
						}
					})();
				})( (JSN = window.JSN || {}) );');
		}
	}

	/**
	 * Magic method to load assets by name.
	 *
	 * @param   string  $name       Method name being called.
	 * @param   array   $arguments  Arguments passed thru.
	 *
	 * @return  void
	 */
	public static function __callStatic($name, $arguments)
	{
		if (strcasecmp(substr($name, 0, 4), 'load') == 0)
		{
			$name = ltrim(
				preg_replace_callback('/[A-Z]/', function ($matches)
				{
					return '-' . strtolower($matches[0]);
				}, substr($name, 4)), '-');

			if (array_key_exists($name, self::$assets))
			{
				array_unshift($arguments, self::$assets[$name]);

				call_user_func_array(array(
					__CLASS__,
					'load'
				), $arguments);
			}
		}
	}
}

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
jimport('joomla.filesystem.folder');

/**
 * Helper class.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwHelper
{

	/**
	 * Get component name.
	 *
	 * @param   string  $component  Component folder name.
	 *
	 * @return  string
	 */
	public static function getComponent($component = null)
	{
		if (empty($component))
		{
			$component = JFactory::getApplication()->input->getCmd('option');
		}

		// Load necessary language file.
		JFactory::getLanguage()->load($component);

		return $component;
	}

	/**
	 * Get component dependencies.
	 *
	 * @param   string  $component  Component folder name.
	 *
	 * @return  array
	 */
	public static function getDependencies($component = null)
	{
		// Verify component.
		$component = self::getComponent($component);

		// Search for dependencies declared in component's manifest file only once.
		static $dependencies;

		if (!isset($dependencies) || empty($dependencies[$component]))
		{
			$deps = array();

			if (JFile::exists($xml = JPATH_ADMINISTRATOR . "/components/{$component}/" . substr($component, 4) . '.xml') &&
				 $xml = simplexml_load_file($xml))
			{
				foreach ($xml->xpath('//subinstall/extension') as $dep)
				{
					if (!empty($dep['identified_name']))
					{
						$deps[] = array(
							'type' => $dep['type'],
							'folder' => empty($dep['folder']) ? null : $dep['folder'],
							'name' => $dep['name'],
							'identified_name' => $dep['identified_name'],
							'title' => (string) $dep
						);
					}
				}
			}

			$dependencies[$component] = $deps;
		}

		return $dependencies[$component];
	}

	/**
	 * Get constant value.
	 *
	 * @param   string  $name       Raw constant name.
	 * @param   string  $component  Component folder name.
	 *
	 * @return  mixed  Constant value or null if constant is not defined.
	 */
	public static function getConstant($name, $component = null)
	{
		// Verify component.
		$component = preg_replace('/^com_/i', '', self::getComponent($component));

		// Generate constant name.
		$const = strtoupper((strpos($component, 'jsn') === false ? 'jsn_' : '') . "{$component}_{$name}");

		// Get constant value.
		if (!defined($const))
		{
			if (JFile::exists(JPATH_ADMINISTRATOR . "/components/com_{$component}/{$component}.defines.php"))
			{
				include_once JPATH_ADMINISTRATOR . "/components/com_{$component}/{$component}.defines.php";
			}
			elseif (JFile::exists(JPATH_ADMINISTRATOR . "/components/com_{$component}/defines.{$component}.php"))
			{
				include_once JPATH_ADMINISTRATOR . "/components/com_{$component}/defines.{$component}.php";
			}
		}

		if (defined($const))
		{
			eval('$const = ' . $const . ';');
		}
		else
		{
			$const = null;
		}

		return $const;
	}

	/**
	 * Get component parameters.
	 *
	 * @param   string   $component  Component folder name.
	 * @param   boolean  $allParams  Whether to return all parameters.
	 *
	 * @return  array
	 */
	public static function getSettings($component = null, $allParams = false)
	{
		// Verify component.
		$component = self::getComponent($component);

		// Load extension parameters only once.
		static $settings;

		if (!isset($settings) || !isset($settings[$component]))
		{
			// Query 'extensions' table for component parameters.
			$dbo = JFactory::getDbo();

			if ($component === 'jsnextfw')
			{
				$params = $dbo->setQuery(
					$dbo->getQuery(true)
						->select('params')
						->from('#__extensions')
						->where('type = "plugin"')
						->where('folder = "system"')
						->where('element = ' . $dbo->quote($component)))
					->loadResult();
			}
			else
			{
				$params = $dbo->setQuery(
					$dbo->getQuery(true)
						->select('params')
						->from('#__extensions')
						->where('type = "component"')
						->where('element = ' . $dbo->quote($component)))
					->loadResult();
			}

			$settings[$component] = empty($params) ? array() : json_decode($params, true);

			// Apply default settings.
			if ($component === 'jsnextfw')
			{
				$config = JSNEXTFW_PATH . '/config/framework';
			}
			else
			{
				$config = array(
					JSNEXTFW_PATH . '/config/extension',
					JPATH_ADMINISTRATOR . "/components/{$component}/config"
				);
			}

			foreach ((array) $config as $file)
			{
				if (JFile::exists("{$file}.json"))
				{
					// Merge saved settings with default settings.
					$default = self::getDefaultSettings(json_decode(file_get_contents("{$file}.json"), true));

					foreach ($default as $k => $v)
					{
						if (!array_key_exists($k, $settings[$component]) || $settings[$component][$k] === '')
						{
							$settings[$component][$k] = $v;
						}
					}
				}
				elseif (JFolder::exists($file))
				{
					foreach (JFolder::files($file, '\.json$', true, true) as $f)
					{
						// Merge saved settings with default settings.
						$default = self::getDefaultSettings(json_decode(file_get_contents($f), true));

						foreach ($default as $k => $v)
						{
							if (!array_key_exists($k, $settings[$component]) || $settings[$component][$k] === '')
							{
								$settings[$component][$k] = $v;
							}
						}
					}
				}
			}
		}

		if ($allParams)
		{
			return $settings[$component];
		}

		// Unset parameters not belong to component settings.
		$params = json_decode(json_encode($settings[$component]), true);

		unset($params['username']);
		unset($params['token']);

		return $params;
	}

	/**
	 * Method to search for default settings from a form declaration array.
	 *
	 * @param   array  $form  A form declaration array.
	 *
	 * @return  array
	 */
	protected static function getDefaultSettings($form)
	{
		$settings = array();

		foreach ($form as $k => $v)
		{
			if ($k === 'controls')
			{
				foreach ($v as $option => $define)
				{
					if (array_key_exists('default', $define))
					{
						$settings[$option] = $define['default'];
					}
				}
			}
			elseif (is_array($v))
			{
				$settings = array_merge($settings, self::getDefaultSettings($v));
			}
		}

		return $settings;
	}

	/**
	 * Method to search for setting filters from a form declaration array.
	 *
	 * @param   mixed  $form  Whether a component folder name or a form declaration array.
	 *
	 * @return  array
	 */
	public static function getSettingFilters($form = null)
	{
		// Verify component.
		if (!is_array($form))
		{
			$component = self::getComponent($form);
			$form = array();

			if ($component === 'jsnextfw')
			{
				$config = JSNEXTFW_PATH . '/config/framework';
			}
			else
			{
				$config = array(
					JSNEXTFW_PATH . '/config/extension',
					JPATH_ADMINISTRATOR . "/components/{$component}/config"
				);
			}

			foreach ((array) $config as $file)
			{
				if (JFile::exists("{$file}.json"))
				{
					$form = array_merge_recursive(json_decode(file_get_contents("{$file}.json"), true), $form);
				}
				elseif (JFolder::exists($file))
				{
					foreach (JFolder::files($file, '\.json$', true, true) as $f)
					{
						$form = array_merge_recursive(json_decode(file_get_contents($f), true), $form);
					}
				}
			}
		}

		// Get setting filters.
		$filters = array();

		foreach ($form as $k => $v)
		{
			if ($k === 'controls')
			{
				foreach ($v as $option => $define)
				{
					if (array_key_exists('filter', $define))
					{
						$filters[$option] = $define['filter'];
					}
					else
					{
						$filters[$option] = '';
					}
				}
			}
			elseif (is_array($v))
			{
				$filters = array_merge($filters, self::getSettingFilters($v));
			}
		}

		return $filters;
	}

	/**
	 * Get all translatable strings from an array.
	 *
	 * @param   array  $data  Data array to get translatable strings from.
	 *
	 * @retrun  array
	 */
	public static function getTranslatableString($data)
	{
		$text = array();

		foreach ($data as $k => $v)
		{
			if (is_string($k) && in_array($k,
				array(
					'title',
					'label',
					'description',
					'hint',
					'prefix',
					'suffix'
				)))
			{
				$text[] = $v;
			}
			elseif ($k === 'default' && is_string($v))
			{
				$text[] = $v;
			}
			elseif (is_array($v))
			{
				$text = array_merge($text, self::getTranslatableString($v));
			}
		}

		return $text;
	}

	/**
	 * Get all available content languages.
	 *
	 *  @return array
	 */
	public static function getContentLanguages()
	{
		$result = array();
		$languages = JHtml::_('contentlanguage.existing');

		if (count($languages))
		{
			foreach ($languages as $language)
			{
				$result[$language->value] = (array) $language;
			}
		}

		return $result;
	}

	/**
	 * Get all available site menus.
	 *
	 * @param   boolean  $include_items  Whether or not to get items for every menu?
	 * @param   boolean  $level          The number of level of menu items in the tree to retrieve.
	 *
	 * @return  array
	 */
	public static function getSiteMenus($include_items = false, $level = 1)
	{
		$languageExisting = self::getContentLanguages();

		// Get Joomla's database object.
		$dbo = JFactory::getDbo();

		// Get list of menu type.
		$query = $dbo->getQuery(true);
		$query->select('menutype as value, title as text')
			->from($dbo->quoteName('#__menu_types'))
			->order('title');

		$dbo->setQuery($query);

		$menus = $dbo->loadObjectList();

		// Get list of published menu.
		$query = $dbo->getQuery(true);
		$query->select('menutype, language')
			->from($dbo->quoteName('#__menu'))
			->where($dbo->quoteName('published') . ' = 1')
			->group('menutype');

		$dbo->setQuery($query);

		$menuLangs = $dbo->loadAssocList('menutype');

		// Get home menu.
		$query = $dbo->getQuery(true);
		$query->select('menutype, language')
			->from($dbo->quoteName('#__menu'))
			->where($dbo->quoteName('home') . ' = 1')
			->where($dbo->quoteName('published') . ' = 1');

		$dbo->setQuery($query);

		$homeLangs = $dbo->loadAssocList('menutype');

		// Prepare return data.
		if (is_array($menuLangs) && is_array($homeLangs))
		{
			array_unshift($menuLangs, $homeLangs);

			$menuLangs = array_unique($menuLangs, SORT_REGULAR);
		}

		if (is_array($menus) && is_array($menuLangs))
		{
			foreach ($menus as & $menu)
			{
				$menu->text = $menu->text . ' [' . $menu->value . ']';
				$menu->language = isset($menuLangs[$menu->value]) ? $menuLangs[$menu->value]['language'] : '*';
				$menu->language_text = isset($languageExisting[$menu->language]) ? $languageExisting[$menu->language]['text'] : $menu->language;

				if ($include_items)
				{
					// Get all items for the current menu.
					$query = $dbo->getQuery(true);
					$query->select('id, title, level')
						->from($dbo->quoteName('#__menu'))
						->where($dbo->quoteName('menutype') . ' = ' . $dbo->quote($menu->value))
						->where($dbo->quoteName('level') . ' <= ' . intval($level))
						->where($dbo->quoteName('published') . ' = 1')
						->order('lft');

					$dbo->setQuery($query);

					$menu->items = $dbo->loadObjectList();
				}
			}
		}
		return $menus;
	}

	/**
	 * Retrieve current version of Joomla
	 *
	 * @return  string
	 */
	public static function getJoomlaVersion($size = null, $includeDot = true)
	{
		$joomlaVersion = new JVersion();
		$versionPieces = explode('.', $joomlaVersion->getShortVersion());

		if (is_numeric($size) && $size > 0 && $size < count($versionPieces))
		{
			$versionPieces = array_slice($versionPieces, 0, $size);
		}

		return implode($includeDot === true ? '.' : '', $versionPieces);
	}

	/**
	 * Save component settings.
	 *
	 * @param   string   $component  Affected component.
	 * @param   array    $settings   New settings.
	 * @param   boolean  $merge      Whether to merge settings instead of override?
	 *
	 * @return  void
	 */
	public static function saveSettings($component, $settings, $merge = false)
	{
		// Verify component.
		$component = self::getComponent($component);

		// Get Joomla application object.
		$app = JFactory::getApplication();

		// Trigger an event to allow 3rd-party extension to hook in.
		$app->triggerEvent('onJsnExtFwBeforeSaveComponentSettings', array(
			&$settings,
			$component
		));

		// Check if new settings should be merged with current settings?
		if ($merge)
		{
			$settings = array_merge(self::getSettings($component, true), $settings);
		}

		// Get Joomla database connector object.
		$dbo = JFactory::getDbo();

		// Store final settings to the #__extensions table.
		if ($component === 'jsnextfw')
		{
			$dbo->setQuery(
				$dbo->getQuery(true)
					->update('#__extensions')
					->set('params = ' . $dbo->quote(json_encode($settings)))
					->where('type = "plugin"')
					->where('folder = "system"')
					->where('element = ' . $dbo->quote($component)));
		}
		else
		{
			$dbo->setQuery(
				$dbo->getQuery(true)
					->update('#__extensions')
					->set('params = ' . $dbo->quote(json_encode($settings)))
					->where('type = "component"')
					->where('element = ' . $dbo->quote($component)));
		}

		if (!$dbo->execute())
		{
			throw new Exception(JText::sprintf('JSN_EXTFW_AJAX_FAILED_TO_SAVE_COMPONENT_SETTINGS', strtoupper(substr($this->component, 4))));
		}

		// Trigger an event to allow 3rd-party extension to hook in.
		$app->triggerEvent('onJsnExtFwAfterSaveComponentSettings', array(
			&$settings,
			$component
		));
	}

	/**
	 * Method to check if the requested page belongs to admin panel.
	 *
	 * @return  boolean
	 */
	public static function isAdmin()
	{
		// Get Joomla application object.
		$app = JFactory::getApplication();

		return ( method_exists($app, 'isClient') ? $app->isClient('administrator') : $app->isAdmin() );
	}

	/**
	 * Method to check if the requested page belongs to front-end site.
	 *
	 * @return  boolean
	 */
	public static function isSite()
	{
		// Get Joomla application object.
		$app = JFactory::getApplication();

		return ( method_exists($app, 'isClient') ? $app->isClient('site') : $app->isSite() );
	}
}

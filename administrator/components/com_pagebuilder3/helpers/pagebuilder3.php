<?php
/**
 * @version    $Id$
 * @package    JSN_PageBuilder3
 * @author     JoomlaShine Team <support@joomlashine.com>
 * @copyright  Copyright (C) 2012 JoomlaShine.com. All Rights Reserved.
 * @license    GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.joomlashine.com
 * Technical Support:  Feedback - http://www.joomlashine.com/contact-us/get-support.html
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * PageBuilder3 component helper.
 *
 * @package  JSN_PageBuilder3
 * @since    1.0.0
 */
class JSNPageBuilder3Helper
{

	/**
	 * Add toolbars.
	 *
	 * @param   string  $title   Page title.
	 * @param   string  $screen  THe current screen.
	 *
	 * @return  void
	 */
	public static function addToolbars($title, $screen = '', $icon = '')
	{
		// Set the toolbar.
		JToolbarHelper::title($title, $icon);

		// Add help button.
		//JToolbarHelper::link('index.php?option=com_pagebuilder3&view=help', JText::_('JTOOLBAR_HELP'), 'question-sign');
		$bar = JToolbar::getInstance('toolbar');
		$bar->appendButton('Link', 'question-sign', JText::_('JTOOLBAR_HELP'), 'index.php?option=com_pagebuilder3&view=help');

		// Register sidebar links.
		foreach (array(
			'manager' => array(
				'name' => JText::_('JSN_PAGEBUILDER3_MENU_PAGE_MANAGER'),
				'link' => JRoute::_('index.php?option=com_pagebuilder3&view=manager')
			),
			'config' => array(
				'name' => JText::_('JSN_PAGEBUILDER3_MENU_CONFIGURARTION_TEXT'),
				'link' => JRoute::_('index.php?option=com_pagebuilder3&view=configuration')
			),
			'about' => array(
				'name' => JText::_('JSN_PAGEBUILDER3_MENU_ABOUT_TEXT'),
				'link' => JRoute::_('index.php?option=com_pagebuilder3&view=about')
			),
			'help' => array(
				'name' => JText::_('JSN_PAGEBUILDER3_MENU_HELP_TEXT'),
				'link' => JRoute::_('index.php?option=com_pagebuilder3&view=help')
			)
		) as $slug => $item)
		{
			JHtmlSidebar::addEntry($item['name'], $item['link'], $slug === $screen);
		}
	}

	/**
	 * Add assets
	 *
	 * @return	void
	 */
	public static function addAssets()
	{
		// Make sure JSN Extension Framework 2 is installed.
		if (!class_exists('JsnExtFwAssets'))
		{
			JFactory::getApplication()->redirect('index.php?option=com_poweradmin&view=installer');
		}

		// Load required libraries.
		JsnExtFwAssets::loadJsnElements();

		// Generate base URL to assets folder.
		$base_url = JUri::root(true) . '/administrator/components/com_pagebuilder3/assets';

		// Load stylesheet of JSN PageBuilder 3.
		JsnExtFwAssets::loadStylesheet("{$base_url}/css/pagebuilder3.css");
	}

	/**
	 * Get configuration for JSN PageBuilder.
	 *
	 * @return  array
	 */
	public static function getConfig()
	{
		static $config;

		if (!isset($config) && class_exists('JsnExtFwHelper'))
		{
			$config = JsnExtFwHelper::getSettings('com_pagebuilder3');
		}

		return ( isset($config) ? $config : array() );
	}
}

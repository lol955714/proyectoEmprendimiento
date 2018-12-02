<?php
/**
 * @version    $Id$
 * @package    JSN_Framework
 * @author     JoomlaShine Team <support@joomlashine.com>
 * @copyright  Copyright (C) 2012 JoomlaShine.com. All Rights Reserved.
 * @license    GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.joomlashine.com
 * Technical Support:  Feedback - http://www.joomlashine.com/contact-us/get-support.html
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Import library
require_once ( dirname(__FILE__) . '/menu/button/jsnmenu.php' );

/**
 * Helper class for creating and rendering menu.
 *
 * @package  JSN_Framework
 * @since    1.0.0
 */
abstract class JsnExtFwMenu
{

	/**
	 * Flag to indicate whether the sub-menu renderer class is registered.
	 *
	 * @var  boolean
	 */
	protected static $registered;

	/**
	 * Method to add a menu item to submenu.
	 *
	 * !!!NOTE!!! Set 'toolbar' value for $parent parameter will add a menu button to Joomla toolbar.
	 *
	 * @param   string  $id      An unique string to identify this sub-menu item.
	 * @param   string  $name    Name of sub-menu item.
	 * @param   string  $link    Link of sub-menu item.
	 * @param   bool    $active  True if the item is active, false otherwise.
	 * @param   string  $parent  Dot separated parent menu item id, e.g. root.2nd_level.3rd_level
	 * @param   string  $icon    Icon of sub-menu item.
	 * @param   string  $class   Link class.
	 *
	 * @return  void
	 */
	public static function addEntry($id, $name = '', $link = 'javascript:void(0)', $active = false, $icon = '', $parent = 'jsnmenu', $class = '')
	{
		if (!$parent or $parent == 'toolbar')
		{
			// Register the menu renderer class
			self::register();

			// Add the menu item with type 'jsnmenu'
			$menu = JToolBar::getInstance('toolbar');
			$menu->appendButton('jsnmenu', $id, $name, $link, $active, $icon, $parent, $class);
		}
		else
		{
			// Add the menu item as child of another menu item
			$version = new JVersion();

			if (version_compare($version->getShortVersion(), '3.0'))
			{
				JToolbarButtonJSNMenu::addEntry($id, $name, $link, $active, $icon, $parent, $class);
			}
			else
			{
				JButtonJSNMenu::addEntry($id, $name, $link, $active, $icon, $parent, $class);
			}
		}
	}

	/**
	 * Method to add a separator to submenu.
	 *
	 * @param   string  $parent  Dot separated parent menu item id, e.g. root.2nd_level.3rd_level
	 *
	 * @return  void
	 */
	public static function addSeparator($parent = 'jsnmenu')
	{
		static $index;

		// Generate id for separator
		$index = isset($index) ? $index + 1 : 1;

		// Let's add the separator as a normal menu item
		self::addEntry("separator-{$index}", '', '', false, '', $parent);
	}

	/**
	 * Method to render list of root menu item.
	 *
	 * @param   string  $parent  Dot separated parent menu item id, e.g. root.2nd_level.3rd_level, to get menu item from
	 *
	 * @return  void
	 */
	public static function render($parent = 'jsnmenu')
	{
		// Instantiate a JSN Menu object
		$version = new JVersion();

		if (version_compare($version->getShortVersion(), '3.0'))
		{
			$renderer = new JToolbarButtonJSNMenu();
		}
		else
		{
			$renderer = new JButtonJSNMenu();
		}

		// Generate markup tag for sub-menu
		try
		{
			$html = $renderer->renderMenu($parent);
		}
		catch (Exception $e)
		{
			$html = '';
		}

		if (version_compare($version->getShortVersion(), '3.0'))
		{
			echo $html;
		}
		else
		{
			// In Joomla 2.5, set the HTML as content for 'submenu' module position
			JFactory::getDocument()->setBuffer($html, array(
				'type' => 'modules',
				'name' => 'submenu'
			));
		}
	}

	/**
	 * Method to reigster sub-menu renderer class.
	 *
	 * @return  void
	 */
	protected static function register()
	{
		if (!isset(self::$registered) or !self::$registered)
		{
			// Register path to look for menu toolbar button renderer class
			$bar = JToolBar::getInstance('toolbar');
			$bar->addButtonPath(dirname(__FILE__) . '/button');

			// State that the class path is registered
			self::$registered = true;
		}
	}
}

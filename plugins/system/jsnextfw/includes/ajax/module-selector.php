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

/**
 * Module selector widget.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjaxModuleSelector extends JsnExtFwAjaxSelector
{

	public function indexAction()
	{
		// Get request data.
		$this->filter_search = $this->input->getString('search', '');
		$this->filter_state = $this->input->getString('state', '');
		$this->filter_position = $this->input->getCmd('position', '');
		$this->filter_type = $this->input->getCmd('type', '');
		$this->filter_access = $this->input->getCmd('access', '');
		$this->filter_language = $this->input->getCmd('language', '');

		// Load language file.
		JFactory::getLanguage()->load('com_modules');

		parent::indexAction();
	}

	/**
	 * Render a list of module positions.
	 *
	 * @param   string  $name        Name of input field.
	 * @param   string  $selected    Currently selected option.
	 * @param   string  $parameters  Additional attributes of input field.
	 *
	 * @return  void
	 */
	public function renderModulePositionOptions($name, $selected, $parameters = '')
	{
		// Get a list of module positions.
		$query = $this->dbo->getQuery(true)
			->select('DISTINCT(position)')
			->from('#__modules')
			->where('client_id = 0')
			->order('position');

		$this->dbo->setQuery($query);

		try
		{
			$positions = $this->dbo->loadColumn();
			$positions = is_array($positions) ? $positions : array();
		}
		catch (RuntimeException $e)
		{
			return array();
		}

		// Build options.
		$options = array(
			JHtml::_('select.option', '', JText::_('COM_MODULES_OPTION_SELECT_POSITION'))
		);

		foreach ($positions as $position)
		{
			if (!$position)
			{
				$options[] = JHtml::_('select.option', 'none', JText::_('JSN_EXTFW_MODULE_POSITION_NONE'));
			}
			else
			{
				$options[] = JHtml::_('select.option', $position, $position);
			}
		}

		// Render module positions select box.
		echo JHTML::_('select.genericlist', $options, $name, $parameters, 'value', 'text', $selected);
	}

	/**
	 * Render a list of module types.
	 *
	 * @param   string  $name        Name of input field.
	 * @param   string  $selected    Currently selected option.
	 * @param   string  $parameters  Additional attributes of input field.
	 *
	 * @return  void
	 */
	public function renderModuleTypeOptions($name, $selected, $parameters = '')
	{
		// Get a list of installed modules.
		$query = $this->dbo->getQuery(true)
			->select('element AS value, name AS text')
			->from('#__extensions as e')
			->where('e.client_id = 0')
			->where('type = ' . $this->dbo->quote('module'))
			->join('LEFT', '#__modules as m ON m.module=e.element AND m.client_id=e.client_id')
			->where('m.module IS NOT NULL')
			->group('element,name');

		$modules = $this->dbo->setQuery($query)->loadObjectList();
		$lang = JFactory::getLanguage();

		foreach ($modules as $i => $module)
		{
			$extension = $module->value;
			$source = JPATH_SITE . "/modules/$extension";

			$lang->load("$extension.sys", JPATH_SITE, null, false, true) || $lang->load("$extension.sys", $source, null, false, true);

			$modules[$i]->text = JText::_($module->text);
		}

		$modules = JArrayHelper::sortObjects($modules, 'text', 1, true, true);

		// Render module types select box.
		$types = array_merge(array(
			JHtml::_('select.option', '', JText::_('COM_MODULES_OPTION_SELECT_TYPE'))
		), $modules);

		echo JHTML::_('select.genericlist', $types, $name, $parameters, 'value', 'text', $selected);
	}

	/**
	 * Get query to retrieve all modules based on specified filters.
	 *
	 * @return string
	 */
	public function getListQuery()
	{
		// Create a new query object.
		$query = $this->dbo->getQuery(true);

		// Select the required fields.
		$query->select(
			'a.id, a.title, a.note, a.position, a.module, a.language, a.checked_out, a.checked_out_time, a.published AS published, e.enabled AS enabled, a.access, a.ordering, a.publish_up, a.publish_down');

		// From modules table.
		$query->from('#__modules AS a');

		// Join over the language.
		$query->select('l.title AS language_title, l.image AS language_image')->join('LEFT',
			'#__languages AS l ON l.lang_code = a.language');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor')->join('LEFT', '#__users AS uc ON uc.id = a.checked_out');

		// Join over the asset groups.
		$query->select('ag.title AS access_level')->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

		// Join over the module menus.
		$query->select('MIN(mm.menuid) AS pages')->join('LEFT', '#__modules_menu AS mm ON mm.moduleid = a.id');

		// Join over the extensions.
		$query->select('e.name AS name')->join('LEFT', '#__extensions AS e ON e.element = a.module');

		// Group (careful with PostgreSQL).
		$query->group(
			'a.id, a.title, a.note, a.position, a.module, a.language, a.checked_out, a.checked_out_time, a.published, a.access, a.ordering, l.title, l.image, uc.name, ag.title, e.name, l.lang_code, uc.id, ag.id, mm.moduleid, e.element, a.publish_up, a.publish_down, e.enabled');

		// Filter by client.
		$query->where('a.client_id = 0 AND e.client_id = 0');

		// Filter by access level.
		if ($access = $this->filter_access)
		{
			$query->where('a.access = ' . (int) $access);
		}

		// Filter by published state.
		$state = $this->filter_state;

		if (is_numeric($state))
		{
			$query->where('a.published = ' . (int) $state);
		}
		elseif ($state == '')
		{
			$query->where('a.published IN (0, 1)');
		}

		// Filter by position.
		if ($position = $this->filter_position)
		{
			$query->where('a.position = ' . $this->dbo->quote(( $position === 'none' ) ? '' : $position));
		}

		// Filter by module.
		if ($type = $this->filter_type)
		{
			$query->where('a.module = ' . $this->dbo->quote($type));
		}

		// Filter by search in title or note or id:.
		$search = $this->filter_search;

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $this->dbo->quote('%' . strtolower($search) . '%');

				$query->where('(LOWER(a.title) LIKE ' . $search . ' OR LOWER(a.note) LIKE ' . $search . ')');
			}
		}

		// Filter on the language.
		if ($language = $this->filter_language)
		{
			if ($language === 'current')
			{
				$query->where('a.language IN (' . $this->dbo->quote(JFactory::getLanguage()->getTag()) . ', "*")');
			}
			else
			{
				$query->where('a.language = ' . $this->dbo->quote($language));
			}
		}

		return $query;
	}

	/**
	 * Get items.
	 *
	 * @return  array
	 */
	public function getItems()
	{
		// Get all items.
		$results = $this->dbo->setQuery($this->getListQuery())
			->loadObjectList();

		// Prepare limitation.
		$this->total = count($results);
		$this->limit = 20;
		$this->start = $this->input->getInt('limitstart', 0);

		if ($this->start >= $this->total)
		{
			$this->start = ( ceil($this->total / $this->limit) - 1 ) * $this->limit;
		}

		return array_slice($results, $this->start, $this->limit);
	}
}

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
 * Article selector widget.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjaxArticleSelector extends JsnExtFwAjaxSelector
{

	public function indexAction()
	{
		// Get request data.
		$this->filter_search = $this->input->getString('search', '');
		$this->filter_state = $this->input->getString('state', '');
		$this->filter_category = $this->input->getCmd('category', '');
		$this->filter_access = $this->input->getCmd('access', '');
		$this->filter_author = $this->input->getCmd('uauthor', '');
		$this->filter_language = $this->input->getCmd('language', '');

		parent::indexAction();
	}

	/**
	 * Render list of article state for filtering.
	 *
	 * @param   string  $name        Name of input field.
	 * @param   string  $selected    Currently selected option.
	 * @param   string  $parameters  Additional attributes of input field.
	 *
	 * @return  void
	 */
	public function renderStatusOptions($name, $selected, $parameters = '')
	{
		echo JHTML::_('select.genericlist',
			array(
				JHtml::_('select.option', '', JText::_('JOPTION_SELECT_PUBLISHED')),
				JHtml::_('select.option', '-2', JText::_('JTRASHED')),
				JHtml::_('select.option', '0', JText::_('JUNPUBLISHED')),
				JHtml::_('select.option', '1', JText::_('JPUBLISHED')),
				JHtml::_('select.option', '2', JText::_('JARCHIVED')),
				JHtml::_('select.option', '*', JText::_('JALL'))
			), $name, $parameters, 'value', 'text', $selected);
	}

	/**
	 * Render list of article category for filtering.
	 *
	 * @param   string  $name        Name of input field.
	 * @param   string  $selected    Currently selected option.
	 * @param   string  $parameters  Additional attributes of input field.
	 *
	 * @return  void
	 */
	public function renderArticleCategoryOptions($name, $selected, $parameters = '')
	{
		// Build the filter options.
		$filters = array();

		if ($this->filter_state != '' && $this->filter_state != '*')
		{
			$filters['filter.published'] = explode(',', $this->filter_state);
		}

		if ($this->filter_language != '')
		{
			$filters['filter.language'] = explode(',', $this->filter_language);
		}

		$options = JHtml::_('category.options', 'com_content', $filters);

		// Displays language code if not set to All.
		foreach ($options as $option)
		{
			// Create a new query object.
			$query = $this->dbo->getQuery(true)
				->select('language')
				->where('id = ' . (int) $option->value)
				->from('#__categories');

			$this->dbo->setQuery($query);

			$language = $this->dbo->loadResult();

			if ($language !== '*')
			{
				$option->text = $option->text . ' (' . $language . ')';
			}
		}

		array_unshift($options, (object) array(
			'text' => JText::_('JOPTION_SELECT_CATEGORY'),
			'value' => ''
		));

		echo JHTML::_('select.genericlist', $options, $name, $parameters, 'value', 'text', $selected);
	}

	/**
	 * Render list of article author for filtering.
	 *
	 * @param   string  $name        Name of input field.
	 * @param   string  $selected    Currently selected option.
	 * @param   string  $parameters  Additional attributes of input field.
	 *
	 * @return  void
	 */
	public function renderArticleAuthorOptions($name, $selected, $parameters = '')
	{
		// Build the filter options.
		$query = $this->dbo->getQuery(true)
			->select('u.id AS value, u.name AS text')
			->from('#__users AS u')
			->join('INNER', '#__content AS c ON c.created_by = u.id')
			->group('u.id, u.name')
			->order('u.name');

		$this->dbo->setQuery($query);

		if (!( $options = $this->dbo->loadObjectList() ))
		{
			$options = array();
		}

		array_unshift($options, (object) array(
			'text' => JText::_('JOPTION_SELECT_AUTHOR'),
			'value' => ''
		));

		echo JHTML::_('select.genericlist', $options, $name, $parameters, 'value', 'text', $selected);
	}

	/**
	 * Get query to retrieve all articles based on specified filters.
	 *
	 * @return  string
	 */
	public function getListQuery()
	{
		// Create a new query object.
		$query = $this->dbo->getQuery(true);

		// Select from content table.
		$query->select('a.id, a.title, a.state, a.language')->from('#__content AS a');

		// Join with categories table.
		$query->select('c.title AS category')->join('LEFT', '#__categories AS c ON c.extension = "com_content" AND c.id = a.catid');

		// And languages table.
		$query->select('l.title AS language_title, l.image AS language_image')->join('LEFT',
			'#__languages AS l ON l.lang_code = a.language');

		// And users table.
		$query->select('u.name AS author')->join('LEFT', '#__users AS u ON u.id = a.created_by');

		// And view levels table.
		$query->select('ag.title AS access_level')->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

		// Filter by article state.
		if (is_numeric($state = $this->filter_state))
		{
			$query->where('a.state = ' . (int) $state);
		}

		// Filter by article category.
		if (!empty($category = $this->filter_category))
		{
			$query->where('a.catid = ' . (int) $category);
		}

		// Filter by access level.
		if (!empty($access = $this->filter_access))
		{
			$query->where('a.access = ' . (int) $access);
		}

		// Filter by author.
		if (!empty($author = $this->filter_author))
		{
			$query->where('a.created_by = ' . (int) $author);
		}

		// Filter on the language.
		if (!empty($language = $this->filter_language))
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

		// Filter by keyword.
		if (!empty($search = $this->filter_search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $this->dbo->quote('%' . strtolower($search) . '%');

				$query->where('(LOWER(a.title) LIKE ' . $search . ' OR LOWER(a.introtext) LIKE ' . $search . ')');
			}
		}

		return $query;
	}
}

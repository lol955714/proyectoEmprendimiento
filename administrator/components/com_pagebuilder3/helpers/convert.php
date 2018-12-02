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
include_once JPATH_ROOT . '/components/com_content/helpers/route.php';

/**
 * JSN PageBuilder3 content helper.
 *
 * @package  JSN_PageBuilder3
 * @since    1.0.0
 */
class JSNPageBuilder3ConvertHelper
{

	public $tables = array(
		'content' => 'introtext',
		'modules' => 'content'
	);
	/**
	 * Variable to hold the active Joomla application.
	 *
	 * @var  JApplication
	 * @since 1.0.0
	 */
	protected $app;

	// If any others component use our PB, list its table and column in database here
	/**
	 * Variable to hold the active Joomla database connector.
	 *
	 * @var  JDatabaseDriver
	 * @since 1.0.0
	 */
	protected $dbo;

	public function __construct()
	{
		// Get Joomla application.
		$this->app = JFactory::getApplication();

		// Get Joomla database connector.
		$this->dbo = JFactory::getDbo();
	}

	/**
	 * Get list id of old pagebuilder data
	 * @return array
	 *
	 * @since version
	 */
	public function getOldContentListID()
	{
		$list = array();
		foreach ($this->tables as $t => $r)
		{

			if (count($ids = $this->getListId($t, $r)) > 0)
			{
				$list[$t] = $ids;
			}
		}

		return $list;

	}

	/**
	 * Query list id of old pagebuilder data
	 *
	 * @param string $table  table name
	 * @param string $column column name
	 *
	 * @since 1.4.0
	 * @return array
	 */
	public function getListId($table, $column)
	{
		$query = $this->dbo->getQuery(true);
		$query->select($this->dbo->quoteName('id'))
			->from($this->dbo->quoteName('#__' . $table))
			->where($this->dbo->quoteName($column) . ' LIKE ' . $this->dbo->quote('%<!-- Start PageBuilder HTML -->%'))
			->where($this->dbo->quoteName($column) . ' NOT LIKE ' . $this->dbo->quote('%<!-- Start New PageBuilder HTML -->%'));
		$this->dbo->setQuery($query);
		$results = $this->dbo->loadObjectList();
		$arr     = array();
		if (count($results) > 0)
		{
			foreach ($results as $result)
			{
				foreach ($result as $id)
				{
					$arr[] = $id;
				}
			}
		}

		return $arr;
	}

	public function getContentByID($id, $type)
	{
		$q = $this->dbo->getQuery(true);
		$q->select($this->dbo->quoteName($this->tables[$type]))
			->from($this->dbo->quoteName('#__' . $type))
			->where($this->dbo->quoteName('id') . ' = ' . $id);
		$this->dbo->setQuery($q);
		$result = $this->dbo->loadResult();

		return $result;
	}

	public function saveData($data, $id, $type)
	{
		if ($id !== 0 && $type !== '' && $data !== '')
		{
			$q = $this->dbo->getQuery(true);
			$q->update($this->dbo->quoteName('#__' . $type))
				->set($this->dbo->quoteName($this->tables[$type]) . ' = ' . $this->dbo->quote($data))
				->where($this->dbo->quoteName('id') . ' = ' . $id);
			$this->dbo->setQuery($q);
			$result = $this->dbo->execute();

			return $result;
		}
	}

	public function removeAllBackup()
	{
		$this->dbo->setQuery('TRUNCATE TABLE `#__jsn_pagebuilder3_backup`');

		return $this->dbo->execute();
	}

	public function removeBackup($id, $type)
	{
		$q = $this->dbo->getQuery(true);

		$conditions = array(
			$this->dbo->quoteName('page_id') . ' = ' . $id,
			$this->dbo->quoteName('type') . ' = ' . $this->dbo->quote($type)
		);
		$q->delete($this->dbo->quoteName('#__jsn_pagebuilder3_backup'))
			->where($conditions);
		$this->dbo->setQuery($q);

		return $this->dbo->execute();
	}

	public function checkBackUp()
	{
		$this->dbo->setQuery('SELECT COUNT( id ) FROM  `#__jsn_pagebuilder3_backup`');

		return $this->dbo->loadResult();
	}

	public function backupContent($id, $type)
	{
		$this->removeBackup($id, $type);
		$data    = $this->getContentByID($id, $type);
		$q       = $this->dbo->getQuery(true);
		$columns = array('page_id', 'type', 'data');
		$values  = array($id, $this->dbo->quote($type), $this->dbo->quote($data));
		$q->insert($this->dbo->quoteName('#__jsn_pagebuilder3_backup'))
			->columns($this->dbo->quoteName($columns))
			->values(implode(',', $values));

		$this->dbo->setQuery($q);

		return $this->dbo->execute();
	}

	public function getTitle($id, $type)
	{
		$q = $this->dbo->getQuery(true);
		$q->select($this->dbo->quoteName('title'))
			->from($this->dbo->quoteName('#__' . $type))
			->where($this->dbo->quoteName('id') . ' = ' . $id);
		$this->dbo->setQuery($q);

		return $this->dbo->loadResult();
	}

	public function revertBackUp()
	{
		$data = $this->getAllBackupData();
		if (count($data) > 0)
		{
			$result = array();
			foreach ($data as $v)
			{
				$q = $this->dbo->getQuery(true);
				$q->update($this->dbo->quoteName('#__' . $v->type))
					->set($this->dbo->quoteName($this->tables[$v->type]) . ' = ' . $this->dbo->quote($v->data))
					->where($this->dbo->quoteName('id') . ' = ' . $v->page_id);
				$this->dbo->setQuery($q);
				$result[] = $this->dbo->execute();
			}

			$this->removeAllBackup();

			return $result;
		}


		return false;
	}

	public function getAllBackupData()
	{
		$q = $this->dbo->getQuery(true);
		$q->select($this->dbo->quoteName(array('page_id', 'title', 'type', 'data')))
			->from($this->dbo->quoteName('#__jsn_pagebuilder3_backup'));
		$this->dbo->setQuery($q);

		return $this->dbo->loadObjectList();
	}

}
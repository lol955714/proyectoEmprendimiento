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

/**
 * Class for saving component settings.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjaxSettings extends JsnExtFwAjax
{

	/**
	 * Save component settings.
	 *
	 * @param   boolean  $update  Whether to keep or remove omitted options.
	 * @param   array    $filter  Custom filter if updating just few params.
	 *
	 * @return  void
	 */
	public function saveAction($update = false, $filter = array())
	{
		// Get affected component.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Get posted data.
		$data = $this->input->getArray(array_merge(JsnExtFwHelper::getSettingFilters($this->component), $filter), $_POST);

		// Refine posted data.
		foreach ($data as $k => $v)
		{
			if ($this->app->input->getString($k, 'not exist') === 'not exist')
			{
				unset($data[$k]);
			}
		}

		// Check if there is any option has been omitted?
		if (!$update)
		{
			// Get current settings.
			$settings = JsnExtFwHelper::getSettings($this->component, true);

			foreach (array_diff(array_keys($settings), array_keys($data)) as $omitted)
			{
				if (!in_array($omitted, array(
					'username',
					'token'
				)))
				{
					unset($settings[$omitted]);
				}
			}

			// Merge settings.
			$data = array_merge($settings, $data);
		}

		// Save settings.
		JsnExtFwHelper::saveSettings($this->component, $data, $update);
	}

	/**
	 * Update component settings.
	 *
	 * @return  void
	 */
	public function updateAction()
	{
		$this->saveAction(true, array(
			'allow_tracking' => 'uint'
		));
	}
}

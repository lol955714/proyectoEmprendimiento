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
 * Class for handling language (un-)installation.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjaxLanguage extends JsnExtFwAjax
{

	/**
	 * Install selected languages.
	 *
	 * @return  void
	 */
	public function installAction()
	{
		// Get affected component.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Get posted data.
		$data = $this->input->getArray(array(
			'languages' => 'array'
		), $_POST);

		// Trigger an event to allow 3rd-party extension to hook in.
		$this->app->triggerEvent('onJsnExtFwBeforeSaveComponentLanguage', array(
			&$data,
			$this->component
		));

		// Loop thru posted data to (un-)install languages.
		foreach ($data['languages'] as $lang => $options)
		{
			foreach ($options as $type => $checked)
			{
				// Generate paths to (un-)install language files.
				$path = JPATH_ADMINISTRATOR . "/components/{$this->component}/language/{$type}/{$lang}";
				$dest = ( $type == 'admin' ? JPATH_ADMINISTRATOR : JPATH_ROOT ) . "/language/{$lang}";

				// Copy language files to Joomla's language directory.
				foreach (JFolder::files($path, '\.ini$') as $file)
				{
					if ((int) $checked)
					{
						if (!JFile::exists("{$dest}/{$file}"))
						{
							if (!JFolder::create($dest) || !JFile::copy("{$path}/{$file}", "{$dest}/{$file}"))
							{
								throw new Exception(JText::sprintf('JSN_EXTFW_LANGUAGE_FAILED_TO_COPY_FILE', $file));
							}
						}
					}
					else
					{
						if (!JFile::delete("{$dest}/{$file}"))
						{
							throw new Exception(JText::sprintf('JSN_EXTFW_LANGUAGE_FAILED_TO_DELETE_FILE', $file));
						}
					}
				}
			}
		}

		// Trigger an event to allow 3rd-party extension to hook in.
		$this->app->triggerEvent('onJsnExtFwAfterSaveComponentLanguage', array(
			&$data,
			$this->component
		));
	}

	/**
	 * Edit language files.
	 *
	 * @return  void
	 */
	public function editAction()
	{
		// Get affected component.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Get request parameters.
		$lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : null;
		$client = isset($_REQUEST['client']) ? $_REQUEST['client'] : null;

		// Get available language files.
		$files = array();

		if (@JFolder::exists(JPATH_ADMINISTRATOR . "/components/{$this->component}/language/{$client}/{$lang}"))
		{
			foreach (JFolder::files(JPATH_ADMINISTRATOR . "/components/{$this->component}/language/{$client}/{$lang}", '\.ini$') as $file)
			{
				$path = ( $client === 'admin' ? JPATH_ADMINISTRATOR : JPATH_ROOT );

				if (JFile::exists("{$path}/language/{$lang}/{$file}"))
				{
					$files[$file] = file_get_contents("{$path}/language/{$lang}/{$file}");
				}
				else
				{
					$files[$file] = file_get_contents(
						JPATH_ADMINISTRATOR . "/components/{$this->component}/language/{$client}/{$lang}/{$file}");
				}
			}
		}

		// Load required assets.
		JsnExtFwAssets::loadJsnComponents();

		// Render HTML.
		$this->render('edit', array(
			'lang' => $lang,
			'client' => $client,
			'files' => $files
		));
	}

	/**
	 * Save language files.
	 *
	 * @return  void
	 */
	public function saveAction()
	{
		// Get affected component.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Get request parameters.
		$lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : null;
		$client = isset($_REQUEST['client']) ? $_REQUEST['client'] : null;
		$files = isset($_POST['files']) ? $_POST['files'] : array();

		// Get edited language files.
		foreach ($files as $file => $mapping)
		{
			// Prepare file content.
			$content = array();

			foreach ($mapping as $k => $v)
			{
				$content[] = "{$k}=\"{$v}\"";
			}

			// Save language file.
			$path = ( $client === 'admin' ? JPATH_ADMINISTRATOR : JPATH_ROOT );

			file_put_contents("{$path}/language/{$lang}/{$file}", implode("\n", $content));
		}
	}

	/**
	 * Revert language files.
	 *
	 * @return  void
	 */
	public function revertAction()
	{
		// Get affected component.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Get request parameters.
		$lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : null;
		$client = isset($_REQUEST['client']) ? $_REQUEST['client'] : null;

		// Get available language files.
		if (@JFolder::exists(JPATH_ADMINISTRATOR . "/components/{$this->component}/language/{$client}/{$lang}"))
		{
			foreach (JFolder::files(JPATH_ADMINISTRATOR . "/components/{$this->component}/language/{$client}/{$lang}", '\.ini$') as $file)
			{
				$orig = JPATH_ADMINISTRATOR . "/components/{$this->component}/language/{$client}/{$lang}/{$file}";
				$path = ( $client === 'admin' ? JPATH_ADMINISTRATOR : JPATH_ROOT );

				// Copy the original language file over.
				if (!JFile::copy($orig, "{$path}/language/{$lang}/{$file}"))
				{
					throw new Exception(JText::sprintf('JSN_EXTFW_LANGUAGE_FAILED_TO_COPY_FILE', $file));
				}
			}
		}
	}
}

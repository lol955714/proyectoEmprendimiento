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
 * Class for handling Ajax request for updating product.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjaxUpdate extends JsnExtFwAjax
{

	/**
	 * Download product update.
	 *
	 * @param   string  $task       Task to execute.
	 * @param   string  $id         The identified string of the component being updated.
	 * @param   string  $filePath   Path to store downloaded update package.
	 *
	 * @return  void
	 */
	public function downloadAction($task = null, $id = null, $filePath = null)
	{
		// Verify request.
		$id = empty($id) ? $this->input->getString('id') : $id;

		if (empty($id) || empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Get token.
		$settings = JsnExtFwHelper::getSettings($this->component, true);

		if (empty($settings) || empty($settings['token']))
		{
			throw new Exception(JText::_('JSN_EXTFW_MISSING_TOKEN_KEY'));
		}

		// Prepare URL to download product update.
		if ($id === JsnExtFwHelper::getConstant('IDENTIFIED_NAME', $this->component))
		{
			$downloadUrl = JSN_GET_PRODUCT_UPDATE_URL;
		}
		else
		{
			$downloadUrl = JSN_GET_DEPENDENCY_UPDATE_URL;
		}

		$downloadUrl .= '&identified_name=' . $id;
		$downloadUrl .= '&joomla_version=' . JsnExtFwHelper::getJoomlaVersion(2);
		$downloadUrl .= '&ip=' . $_SERVER['SERVER_ADDR'];
		$downloadUrl .= '&token=' . $settings['token'];
		$downloadUrl .= '&domain=' . JUri::getInstance()->toString(array(
			'host'
		));

		// Generate local file path.
		$filePath = empty($filePath) ? JFactory::getConfig()->get('tmp_path') . '/jsn-' . $id . '.zip' : $filePath;

		// Verify request data.
		$task = empty($task) ? $this->input->getCmd('task', 'download') : $task;

		if (in_array($task, array(
			'download',
			'status'
		)))
		{
			(new JsnExtFwAjaxDownloader())->indexAction($downloadUrl, $filePath);
		}
		elseif (!JFile::exists($filePath))
		{
			throw new Exception(JText::_('JSN_EXTFW_UPDATE_DOWNLOAD_FAIL'));
		}
		elseif (filesize($filePath) < ( 10 * 1024 ))
		{
			$res = file_get_contents($filePath);

			if (( $error = json_decode($res) ) && $error->result == 'failure')
			{
				$error_code = $error->error_code;
			}
			elseif (preg_match('/(ERR|API_ERROR_)\d+/', $res, $match))
			{
				$error_code = $match[0];
			}
			else
			{
				$error_code = '';
			}

			// Prepare error message.
			$key = "JSN_EXTFW_LIGHTCART_{$error_code}";
			$msg = JText::_($key);

			if (strcasecmp($key, $msg) == 0)
			{
				$key = "JSN_EXTFW_LIGHTCART_ERROR_{$error_code}";
				$msg = JText::_($key);

				if (strcasecmp($key, $msg) == 0)
				{
					$msg = $error ? $error->message : $res;
				}
			}

			throw new Exception($msg);
		}
	}

	/**
	 * Install product update.
	 *
	 * @return  void
	 */
	public function installAction()
	{
		// Verify request.
		$id = $this->input->getString('id');

		if (empty($id))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Generate local file path.
		$filePath = JFactory::getConfig()->get('tmp_path') . '/jsn-' . $id . '.zip';

		if (!JFile::exists($filePath))
		{
			throw new Exception(JText::_('JSN_EXTFW_UPDATE_NOT_FOUND_UPDATE_PACKAGE'));
		}

		// Turn off debug mode to catch install error.
		$conf = JFactory::getConfig();

		$conf->set('debug', 0);

		// Install update package.
		$unpacked = JInstallerHelper::unpack($filePath);
		$installer = new JInstaller();

		$installer->setUpgrade(true);

		$result = $installer->install($unpacked['dir']);

		// Clean up temporary data.
		JInstallerHelper::cleanupInstall($filePath, $unpacked['dir']);

		// Check if install failed.
		if (class_exists('JError'))
		{
			$error = JError::getError();

			if (!empty($error))
			{
				throw $error;
			}
		}
	}
}

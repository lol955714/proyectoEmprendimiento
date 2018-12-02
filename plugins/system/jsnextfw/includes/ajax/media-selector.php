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

// Import necessary libraries.
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Media selector widget.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjaxMediaSelector extends JsnExtFwAjax
{

	/**
	 * Define supported file types.
	 *
	 * @var  array
	 */
	protected static $supported_file_types = array(
		'image' => '/\.(bmp|gif|ico|jpe?g|png)$/i',
		'font' => '/\.(eot|otf|svg|ttf|woff|woff2)$/i'
	);

	/**
	 * Working directory (absolute path).
	 *
	 * @var  string
	 */
	protected $abs_dir;

	/**
	 * Working directory (relative path).
	 *
	 * @var  string
	 */
	protected $rel_dir;

	/**
	 * Requested file type.
	 *
	 * @var  string
	 */
	protected $type;

	public function __construct()
	{
		parent::__construct();

		// Get plugin parameters.
		$config = JsnExtFwHelper::getSettings('jsnextfw');

		if (!$config['enable_media_selector'])
		{
			$field_id = $this->app->input->getString('fieldid');

			$this->app->redirect(
				JRoute::_("index.php?option=com_media&view=images&tmpl=component&author=&fieldid={$field_id}&ismoo=0&folder=", false));
		}

		// Get working directory.
		$this->rel_dir = $this->app->getUserStateFromRequest('jsnextfw.media-selector.dir', 'dir');

		if (trim($this->rel_dir, '\\/') == '')
		{
			$this->abs_dir = JPATH_ROOT;
		}
		else
		{
			$this->abs_dir = JPATH_ROOT . '/' . $this->rel_dir;
		}

		// Make sure the working directory is not outside the Joomla root.
		if (strpos(realpath($this->abs_dir), realpath(JPATH_ROOT)) !== 0)
		{
			$this->abs_dir = JPATH_ROOT;
			$this->rel_dir = '';
		}

		// Get requested file type.
		$this->type = $this->app->getUserStateFromRequest('jsnextfw.media-selector.type', 'type');

		// Make sure file type is supported.
		if (!array_key_exists($this->type, self::$supported_file_types))
		{
			$this->type = 'dir';
		}
	}

	public function indexAction()
	{
		// Get Joomla document object.
		$doc = JFactory::getDocument();

		// Get base URLs.
		$root = JURI::root(true);

		// Clear all currently loaded assets.
		$doc->_styleSheets = $doc->_style = $doc->_scripts = $doc->_script = array();

		// Load required stylesheets.
		$doc->addStylesheet("{$root}/plugins/system/jsnextfw/assets/vendors/font-awesome/css/font-awesome.min.css");
		$doc->addStylesheet("{$root}/plugins/system/jsnextfw/assets/bravebits/bootstrap.min.css");
		$doc->addStylesheet("{$root}/plugins/system/jsnextfw/assets/bravebits/jsn-override.css");

		// Load required scripts.
		$doc->addScript("{$root}/media/jui/js/jquery.min.js");
		$doc->addScript("{$root}/plugins/system/jsnextfw/assets/vendors/react/react.min.js");
		$doc->addScript("{$root}/plugins/system/jsnextfw/assets/vendors/react/react-dom.min.js");
		$doc->addScript("{$root}/plugins/system/jsnextfw/assets/vendors/underscore-min.js");
		$doc->addScript("{$root}/plugins/system/jsnextfw/assets/bravebits/bb-media-selector.js");

		$this->render('index');
	}

	/**
	 * Send list of file in a directory.
	 *
	 * @return  void
	 */
	public function getListFilesAction()
	{
		// Send response.
		echo json_encode($this->listFiles());

		exit();
	}

	/**
	 * Handle uploading file.
	 *
	 * @return  void
	 */
	public function uploadFileAction()
	{
		if (isset($_POST['data_uri']) && isset($_POST['filename']))
		{
			// Verify uploaded file type.
			if (!preg_match(self::$supported_file_types[$this->type], $_POST['filename']))
			{
				jexit(
					json_encode(
						array(
							'success' => false,
							'message' => JText::_('JSN_EXTFW_UPLOADED_FILE_IS_NOT_ALLOWED')
						)));
			}

			// Check if file already exists?
			$path = $this->abs_dir . '/' . $_POST['filename'];

			if (JFile::exists($path))
			{
				jexit(
					json_encode(
						array(
							'message' => JText::_('JSN_EXTFW_FILE_ALREADY_EXISTS'),
							'uri' => $_POST['filename'],
							'list' => $this->listFiles()
						)));
			}

			// Create file.
			$data = $_POST['data_uri'];

			list($type, $data) = explode(';', $data);
			list($temp, $data) = explode(',', $data);

			$data = base64_decode($data);

			JFile::write($path, $data);

			// Verify file content.
			if (!JsnExtFwUpload::checkFile($path) || !JsnExtFwUpload::checkXss($path))
			{
				unlink($path);

				jexit(
					json_encode(
						array(
							'success' => false,
							'message' => JText::_('JSN_EXTFW_UPLOADED_FILE_IS_NOT_ALLOWED')
						)));
			}
		}

		jexit(json_encode(array(
			'message' => 'done',
			'uri' => $_POST['filename'],
			'list' => $this->listFiles()
		)));
	}

	/**
	 * Create a directory.
	 *
	 * @return  void
	 */
	public function createFolderAction()
	{
		$name = $this->app->input->getString('name', '');
		$path = $this->abs_dir . '/' . $name;

		if (JFolder::exists($path))
		{
			$result = array(
				'success' => false,
				'message' => JText::_('JSN_EXTFW_FOLDER_ALREADY_EXISTS'),
				'path' => $this->rel_dir . '/' . $name
			);
		}
		elseif (JFolder::create($path))
		{
			$result = array(
				'success' => true,
				'message' => JText::_('JSN_EXTFW_NEW_FOLDER_SUCCESSFULLY_CREATED'),
				'path' => $this->rel_dir . '/' . $name
			);
		}
		else
		{
			$result = array(
				'success' => false,
				'message' => JText::_('JSN_EXTFW_CREATING_NEW_FOLDER_FAILED'),
				'path' => $this->rel_dir . '/' . $name
			);
		}

		echo json_encode($result);

		exit();
	}

	/**
	 * Delete a directory.
	 *
	 * @return  void
	 */
	public function deleteFolderAction()
	{
		if (JFolder::exists($this->abs_dir))
		{
			if (JFolder::delete($this->abs_dir))
			{
				$result = array(
					'success' => true,
					'message' => JText::sprintf('JSN_EXTFW_THE_FOLDER_HAS_BEEN_DELETED', $this->rel_dir),
					'path' => $this->rel_dir
				);
			}
			else
			{
				$result = array(
					'success' => false,
					'message' => JText::_('JSN_EXTFW_DELETING_FOLDER_FAILED'),
					'path' => $this->rel_dir
				);
			}
		}
		else
		{
			$result = array(
				'success' => false,
				'message' => JText::_('JSN_EXTFW_FOLDER_DOES_NOT_EXIST'),
				'path' => $this->rel_dir
			);
		}

		echo json_encode($result);

		exit();
	}

	/**
	 * Delete a file.
	 *
	 * @return  void
	 */
	public function deleteFileAction()
	{
		if (JFile::exists($this->abs_dir))
		{
			if (JFile::delete($this->abs_dir))
			{
				$result = array(
					'success' => true,
					'message' => JText::sprintf('JSN_EXTFW_THE_FILE_HAS_BEEN_DELETED', $this->rel_dir),
					'path' => $this->rel_dir
				);
			}
			else
			{
				$result = array(
					'success' => false,
					'message' => JText::_('JSN_EXTFW_DELETING_FILE_FAILED'),
					'path' => $this->rel_dir
				);
			}
		}
		else
		{
			$result = array(
				'success' => false,
				'message' => JText::_('JSN_EXTFW_FILE_DOES_NOT_EXIST'),
				'path' => $this->rel_dir
			);
		}

		echo json_encode($result);

		exit();
	}

	/**
	 * Rename a directory.
	 *
	 * @return  void
	 */
	public function renameFolderAction()
	{
		$newPath = trim($this->app->input->getString('newPath', ''), '\\/');

		if (empty($newPath))
		{
			$result = array(
				'success' => false,
				'message' => JText::_('JSN_EXTFW_ERROR_OCCURRED_TRY_AGAIN'),
				'path' => $this->rel_dir,
				'newPath' => $newPath
			);
		}
		elseif (JFolder::exists(JPATH_ROOT . '/' . $newPath))
		{
			$result = array(
				'success' => false,
				'message' => JText::_('JSN_EXTFW_FOLDER_ALREADY_EXISTS'),
				'path' => $this->rel_dir,
				'newPath' => $newPath
			);
		}
		elseif (JFolder::move($this->abs_dir, JPATH_ROOT . '/' . $newPath))
		{
			$result = array(
				'success' => true,
				'message' => JText::_('JSN_EXTFW_RENAMED_FOLDER_SUCCESSFULLY'),
				'path' => $this->rel_dir,
				'newPath' => $newPath
			);
		}
		else
		{
			$result = array(
				'success' => false,
				'message' => JText::_('JSN_EXTFW_ERROR_OCCURRED_TRY_AGAIN'),
				'path' => $this->rel_dir,
				'newPath' => $newPath
			);
		}

		echo json_encode($result);

		exit();
	}

	/**
	 * Rename a file.
	 *
	 * @return  void
	 */
	public function renameFileAction()
	{
		$newPath = trim($this->app->input->getString('newPath', ''), '\\/');

		if (empty($newPath))
		{
			$result = array(
				'success' => false,
				'message' => JText::_('JSN_EXTFW_ERROR_OCCURRED_TRY_AGAIN'),
				'path' => $this->rel_dir,
				'newPath' => $newPath
			);
		}
		elseif (JFile::exists(JPATH_ROOT . '/' . $newPath))
		{
			$result = array(
				'success' => false,
				'message' => JText::_('JSN_EXTFW_FILE_ALREADY_EXISTS'),
				'path' => $this->rel_dir,
				'newPath' => $newPath
			);
		}
		else
		{
			if (JFile::move($this->abs_dir, JPATH_ROOT . '/' . $newPath))
			{
				$result = array(
					'success' => true,
					'message' => JText::_('JSN_EXTFW_RENAMED_FILE_SUCCESSFULLY'),
					'path' => $this->rel_dir,
					'newPath' => $newPath
				);
			}
			else
			{
				$result = array(
					'success' => false,
					'message' => JText::_('JSN_EXTFW_ERROR_OCCURRED_TRY_AGAIN'),
					'path' => $this->rel_dir,
					'newPath' => $newPath
				);
			}
		}

		echo json_encode($result);

		exit();
	}

	/**
	 * Get list of file in a directory.
	 *
	 * @return  array
	 */
	protected function listFiles()
	{
		// Scan base directory for sub-directories and supported files.
		$files = array();
		$dirs = array();

		$width = $height = null;

		if ($handle = opendir($this->abs_dir))
		{
			$count = 0;
			$ignore = array(
				'cgi-bin',
				'.',
				'..',
				'._'
			);

			while (false !== ( $file = readdir($handle) ))
			{
				$path = $this->abs_dir . '/' . $file;

				if (in_array($file, $ignore) || substr($file, 0, 1) == '.' ||
					 ( $this->type != 'dir' && JFile::exists($path) && !preg_match(self::$supported_file_types[$this->type], $file) ))
				{
					continue;
				}

				if ($this->type == 'image')
				{
					if (is_file($path))
					{
						list($width, $height) = getimagesize($path);
					}
				}
				else
				{
					$width = $height = null;
				}

				$file_size = round(filesize($path) / 1024, 2);

				$obj = array(
					'name' => $file,
					'key' => $count++,
					'file_size' => $file_size,
					'image_width' => $width,
					'image_height' => $height
				);

				if (JFolder::exists($path))
				{
					$obj['type'] = 'dir';

					$dirs[] = $obj;
				}
				else
				{
					$obj['type'] = 'file';

					$files[] = $obj;
				}
			}

			closedir($handle);
		}

		if ($this->type == 'dir')
		{
			return $dirs;
		}

		return array_merge($dirs, $files);
	}
}

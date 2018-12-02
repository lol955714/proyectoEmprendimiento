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
 * Class for handling Ajax request.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjax
{

	/**
	 * Joomla application object.
	 *
	 * @var  JApplicationCms
	 */
	protected $app;

	/**
	 * Joomla database object.
	 *
	 * @var  JDatabaseDriver
	 */
	protected $dbo;

	/**
	 * Input object.
	 *
	 * @var JInput
	 */
	protected $input;

	/**
	 * Session object.
	 *
	 * @var JSession
	 */
	protected $session;

	/**
	 * Affected extension.
	 *
	 * @var array
	 */
	protected $component;

	/**
	 * Base Ajax URL.
	 *
	 * @var array
	 */
	protected $baseUrl;

	/**
	 * Response content.
	 *
	 * @var mixed
	 */
	protected $responseContent;

	/**
	 * Execute the requested Ajax action.
	 *
	 * @return  boolean
	 */
	public static function execute()
	{
		// Get Joomla's application instance.
		$app = JFactory::getApplication();

		// Prepare to execute Ajax action.
		$context = $app->input->getCmd('context', '');
		$action = $app->input->getCmd('action', 'index');
		$format = $app->input->getCmd('format', 'json');
		$component = $app->input->getCmd('component', null);

		if (empty($action))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Set necessary header for JSON response.
		if ($format == 'json')
		{
			header('Content-Type: application/json');
		}

		try
		{
			// Verify token.
			if (!JSession::checkToken('get'))
			{
				throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_TOKEN'));
			}

			// Checking user permission.
			if ($component && !JFactory::getUser()->authorise('core.manage', $component))
			{
				// Set 403 header.
				header('HTTP/1.1 403 Forbidden');

				throw new Exception('JERROR_ALERTNOAUTHOR');
			}

			// Generate context class.
			$contextClass = 'JsnExtFwAjax' . str_replace(' ', '', ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $context)));

			if (!class_exists($contextClass))
			{
				throw new Exception(JText::sprintf('JSN_EXTFW_AJAX_INVALID_CONTEXT', $context));
			}

			// Create a new instance of the request context.
			$contextObject = new $contextClass();

			// Generate method name.
			$method = str_replace('-', '', $action) . 'Action';

			if (method_exists($contextObject, $method))
			{
				call_user_func(array(
					$contextObject,
					$method
				));
			}
			elseif (method_exists($contextObject, 'invoke'))
			{
				call_user_func(array(
					$contextObject,
					'invoke'
				), $action);
			}
			else
			{
				throw new Exception(JText::sprintf('JSN_EXTFW_AJAX_INVALID_ACTION', $action));
			}

			// Send response back.
			if ($format != 'json')
			{
				echo $contextObject->getResponse();
			}
			else
			{
				echo json_encode(array(
					'success' => true,
					'type' => 'success',
					'data' => $contextObject->getResponse()
				));
			}
		}
		catch (Exception $e)
		{
			if ($format != 'json')
			{
				echo $e->getMessage();
			}
			else
			{
				echo json_encode(array(
					'success' => false,
					'type' => 'error',
					'data' => $e->getMessage()
				));
			}
		}

		return true;
	}

	/**
	 * Constructor.
	 *
	 * @param   string  $component  The component associated with the current Ajax request.
	 *
	 * @return  void
	 */
	public function __construct($component = null)
	{
		// Get necessary objects.
		$this->app = JFactory::getApplication();
		$this->dbo = JFactory::getDbo();
		$this->input = $this->app->input;
		$this->session = JFactory::getSession();
		$this->component = empty($component) ? $this->input->getCmd('component') : $component;

		// Build base Ajax URL.
		$this->baseUrl = "index.php?option=com_ajax&plugin=jsnextfw&component={$this->component}" . '&context=' .
			 strtolower(preg_replace('/([a-z])([A-Z])/', '\\1-\\2', substr(get_class($this), 12))) . '&format=' .
			 $this->input->getCmd('format', 'json') . '&' . JSession::getFormToken() . '=1';
	}

	/**
	 * Get all available content categories.
	 *
	 * @return  void
	 */
	public function getContentCategoryAction()
	{
		// Get request variables.
		$component = $this->input->getCmd('component', 'com_content');
		$state = $this->input->getString('state', '1');
		$lang = $this->input->getString('lang', '');

		// Get list of content category.
		$categories = JHtml::_('category.options', $component,
			array(
				'filter.published' => empty($state) ? null : explode(',', $state),
				'filter.language' => empty($lang) ? null : explode(',', $lang)
			));

		array_unshift($categories, JHtml::_('select.option', 'all', JText::_('JSN_EXTFW_ALL')));

		$this->setResponse($categories);
	}

	/**
	 * Method to get list of Google fonts.
	 *
	 * @return  void
	 */
	public function getGoogleFontsAction()
	{
		// Get list of Google fonts.
		$link = 'https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyCHuPGfMBxIWzmUz_CeqAJ7_X8INFG8h5Q';
		$data = JsnExtFwHttp::get($link, 24 * 60 * 60);

		if (empty($data) || isset($data['error']))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_FAILED_TO_GET_GOOGLE_FONTS_LIST'));
		}

		$this->setResponse($data);
	}

	/**
	 * Render view.
	 *
	 * @param   string  $tmpl  Template file name to render.
	 *
	 * @return  void
	 */
	protected function render($tmpl = 'index', $data = array())
	{
		$context = $this->input->getCmd('context');
		$tplFile = JSNEXTFW_PATH . '/includes/ajax/tmpl/' . $context . '/' . $tmpl . '.php';

		if (!JFile::exists($tplFile) || !is_readable($tplFile))
		{
			throw new Exception('Template file not found: ' . $tplFile);
		}

		// Extract data to seperated variables
		extract($data);

		// Start output buffer
		ob_start();

		// Load template file
		include $tplFile;

		// Send rendered content to client
		$this->responseContent = ob_get_clean();
	}

	/**
	 * Set response content.
	 *
	 * @param   mixed  $content  Content will be sent to client
	 * @return  void
	 */
	protected function setResponse($content)
	{
		$this->responseContent = $content;
	}

	/**
	 * Get response content.
	 *
	 * @return mixed
	 */
	protected function getResponse()
	{
		return $this->responseContent;
	}
}

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
 * Class for retrieving banner.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjaxBanner extends JsnExtFwAjax
{

	/**
	 * Request JoomlaShine server for banner data.
	 *
	 * @return  void
	 */
	public function indexAction()
	{
		// Get affected component.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Get banner category.
		$category = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

		if (empty($category))
		{
			throw new Exception(JText::_(JSN_EXTFW_AJAX_INVALID_PARAMETERS));
		}

		// Get current settings.
		$params = JsnExtFwHelper::getSettings($this->component, true);

		if (empty($params) || empty($params['token']))
		{
			throw new Exception('JSN_EXTFW_MISSING_TOKEN_KEY');
		}

		// Build URL for requesting banner data.
		$link = 'https://www.joomlashine.com/index.php?option=com_lightcart&view=adsbanners&task=adsbanners.getBanners&tmpl=component&type=json';
		$link .= "&category_alias={$category}";
		$link .= "&token={$params['token']}";

		// Get banner data.
		$this->setResponse(JsnExtFwHttp::get($link, 60 * 60));
	}
}

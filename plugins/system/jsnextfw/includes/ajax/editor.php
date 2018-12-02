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
 * Editor widget
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjaxEditor extends JsnExtFwAjax
{

	public function indexAction()
	{
		// Get Joomla document object.
		$doc = JFactory::getDocument();

		// Load admin template's stylesheet.
		$doc->addStylesheet(
			JUri::base(true) . '/templates/' . $this->app->getTemplate() . '/css/template' . ( $doc->direction == 'rtl' ? '-rtl' : '' ) .
				 '.css');

		// Load required scripts.
		JHtml::_('behavior.core');
		JHtml::_('bootstrap.framework');

		$this->render('index');
	}
}

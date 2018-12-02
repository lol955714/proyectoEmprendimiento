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

/**
 * Manager view
 *
 * @package  JSN_PageBuilder3
 * @since    1.0.0
 */
class JSNPageBuilder3ViewManager extends JViewLegacy
{

	/**
	 * Display method
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return	void
	 */
	function display($tpl = null)
	{
        $doc = JFactory::getDocument();
        $doc->addScriptDeclaration('window.pagefly_data = ' . json_encode(array(
                'baseURL' => JUri::root(),
                'view' => 'manage'
            )) . ';');

        // Setup toolbar menu
		JSNPageBuilder3Helper::addToolbars(JText::_('JSN_PAGEBUILDER3_PAGE_MANAGER_TITLE'), 'page-manager', 'file pb-page-manager');
		// Add assets
		JSNPageBuilder3Helper::addAssets();
        JsnExtFwAssets::loadScript(JURI::root(true) . '/plugins/editors/pagebuilder3/assets/app/core/' . PAGEFLY_VERSION . '/main.js');
		JsnExtFwAssets::loadStylesheet('//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');

		// Display the template
		parent::display($tpl);
	}
}

<?php
/**
 * @version     $Id
 * @package     JSNPagebuilder
 * @subpackage  Plugin
 * @author      JoomlaShine Team <support@joomlashine.com>
 * @copyright   Copyright (C) @JOOMLASHINECOPYRIGHTYEAR@ JoomlaShine.com. All Rights Reserved.
 * @license     GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.joomlashine.com
 * Technical Support:  Feedback - http://www.joomlashine.com/contact-us/get-support.html
 */
defined('_JEXEC') or die('Restricted access');
/**
 * Pagebuilder Content Plugin
 *
 * @package Joomla.Plugin
 *
 * @subpackage Content.joomla
 *
 * @since 1.6
 */
class plgContentPagebuilder3 extends JPlugin
{

    /**
     * Define PageBuilder signatures.
     *
     * @var string
     * @since 1.0.0
     */
    public $start_html = '<!-- Start PageFly HTML -->';
    public $end_html = '<!-- End PageFly HTML -->';

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
    }

    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
	    $app = JFactory::getApplication();
	    if ($app->isSite())
	    {
		    // Don't run this plugin when the content is being indexed
		    if ($context === 'com_finder.indexer')
		    {
			    return true;
		    }

		    if (is_null($params) || is_string($params))
		    {
			    return true;
		    }

		    $supported = array(
			    'com_content.article',
			    'com_k2.item'
		    );

		    $filters = array('/\t/', '/\r/', '/\n/');

		    if (in_array($context, $supported))
		    {
			    if ($context == 'com_content.article')
			    {
				    if (!(bool) $params->get('show_intro'))
				    {
					    if (!empty($article->introtext) && !empty($article->fulltext))
					    {
						    if (false !== strpos($article->introtext, $this->start_html) && false !== strpos($article->fulltext, $this->end_html))
						    {
							    $introText = preg_replace($filters, '', $article->introtext);

							    preg_match_all('#<style[^>]+type="text/css"[^>]*>(.*)</style>#', $introText, $matches, PREG_SET_ORDER);
							    if (count($matches))
							    {
								    $style         = @$matches[0][0];
								    $article->text = $this->start_html . $style . $article->text;
							    }
						    }
					    }
				    }
			    }
			    elseif ($context == 'com_k2.item')
			    {
				    if (!empty($article->introtext) && !empty($article->fulltext))
				    {
					    if (false !== strpos($article->introtext, $this->start_html) && false !== strpos($article->fulltext, $this->end_html))
					    {
						    if (false === strpos($article->text, $this->start_html))
						    {
							    $introText = preg_replace($filters, '', $article->introtext);
							    preg_match_all('#<style[^>]+type="text/css"[^>]*>(.*)</style>#', $introText, $matches, PREG_SET_ORDER);
							    if (count($matches))
							    {
								    $style         = @$matches[0][0];
								    $article->text = str_replace('{K2Splitter}', '', $article->text);
								    $article->text = '{K2Splitter}' . $this->start_html . $article->text . $style;
							    }
						    }
					    }
				    }
			    }
			    else
			    {
				    //do nothing
			    }
		    }
	    }
    }

    public function onContentPrepareData($context, $data)
    {
		$user = JFactory::getUser();

		if (!(int) $user->get('id'))
		{
			return;
		}

        $checkedData = '';
		// Check if the current screen is for editing an article.
        if ($context === 'com_content.article')
        {
			$checkedData = $data->articletext;
		}
		elseif ($context === 'com_modules.module' && (string) $data->module === 'mod_custom')
		{
			$checkedData = $data->content;
		}
		else
		{
			return;
		}
		
		if (trim($checkedData) == '')
		{
			return;
		}
		
		// Check if the editing article is created using JSN PageBuilder.
		if (strpos($checkedData, PlgSystemPageBuilder3::$start_html) !== false)
		{
			if (strpos($checkedData, PlgSystemPageBuilder3::$end_html) !== false)
			{
				$found = true;
			}
		}
		elseif (strpos($checkedData, PlgSystemPageBuilder3::$start_data) !== false)
		{
			
			if (strpos($checkedData, PlgSystemPageBuilder3::$end_data) !== false)
			{
				$found = true;
			}
		}
		elseif (strpos($checkedData, PlgSystemPageBuilder3::$start_hash) !== false)
		{
			if (strpos($checkedData, PlgSystemPageBuilder3::$end_hash) !== false)
			{
				$found = true;
			}
		}

		if (!empty($found))
		{
			// Get the default editor.
			$user = JFactory::getUser();
			$params = json_decode($user->get('params'));

			if (empty($params->editor))
			{
				$params->editor = JFactory::getConfig()->get('editor');
			}

			// Set a notice message if the default editor is not JSN PageBuilder.
			if ($params->editor !== 'pagebuilder3')
			{
				JFactory::getApplication()->enqueueMessage(
					JText::_('JSN_PB3_ARTICLE_IS_CREATED_WITH_PAGE_BUILDER_3_NOTICE'), 'notice');
			}
		}

		return;
        
    }
}

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
defined('_JEXEC') or die;

/**
 * Plug-in to enable loading modules into content (e.g. articles).
 * This uses the {{{pb3loadmodule id}}} syntax.
 */
class PlgContentPB3LoadModule extends JPlugin
{
	public static $instance;

	/**
	 * Variable to hold all module instances.
	 *
	 * @var  array
	 */
	protected static $modules;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 *
	 * @return  void
	 */
	public function __construct($subject, $option = array())
	{
		parent::__construct($subject, $option);

		self::$instance = &$this;
	}

	/**
	 * Plugin that loads module within content.
	 *
	 * @param   string   $context   The context of the content being passed to the plugin.
	 * @param   object   &$article  The article object.  Note $article->text is also available
	 * @param   mixed    &$params   The article params
	 * @param   integer  $page      The 'page' number
	 *
	 * @return  mixed  Boolean true if there is an error. Void otherwise.
	 */
	public function onContentPrepare( $context, &$article, &$params, $page = 0 )
	{
        $content = $article->text;
        if ( strpos( $article->text, '{loadmodule') !== false && class_exists('PlgSystemPageBuilder3') && false !== strpos($content, PlgSystemPageBuilder3::$start_html))
        {
           try {
               $temp = explode(PlgSystemPageBuilder3::$end_html, $content, 2);
               $from = strpos($temp[0], PlgSystemPageBuilder3::$start_html);
               $html = substr($temp[0], $from + strlen(PlgSystemPageBuilder3::$start_html));

               $data = PlgSystemPageBuilder3::getPageBuilderData($temp, $html);
               $page = $data['page'];
               if ($page) {
                   $version = isset($page->created_version) ? $page->created_version : isset($page->pb_version) ? $page->pb_version : 100;
                   $html = '<div data-pb-version="' . $version . '" style="display: none;"></div>' . $html;
                   $html = PlgSystemPageBuilder3::renderDynamicContent($page, $html);
               }
               $html = PlgSystemPageBuilder3::handleLink($html);

               $article->text = $html;
               return true;
           } catch (Exception $e) {
               echo $e->getMessage();
           }
        } else {
            // Don't run this plugin when the content is being indexed.
            if ( $context == 'com_finder.indexer' )
            {
                return true;
            }

            // Simple performance check to determine whether bot should process further.
            if ( strpos( $article->text, 'pb3loadmodule') === false )
            {
                return true;
            }


            // Expression to search for module to load.
            $regex = '/{pb3loadmodule\s(.*?)}/i';
            // Find all instances of plugin and put in $matches.
            if (preg_match_all( $regex, $article->text, $matches, PREG_SET_ORDER ) )
            {
                foreach ( $matches as $match )
                {
                    $parts = explode( ',', $match[1] );

                    // We may not have a module style so fall back to the plugin default.
                    if ( ! array_key_exists( 1, $parts ) )
                    {
                        $parts[1] = $this->params->def( 'style', null );
                    }

                    // Render the module.
                    $id     = trim( $parts[0] );
                    $style  = trim( $parts[1] );
                    $output = $this->_loadmod( $id, $style );

                    // We should replace only first occurrence in order to
                    // allow positions with the same name to regenerate their content.
                    $article->text = preg_replace(
                        addcslashes( "|$match[0]|", '()' ),
                        addcslashes( $output, '\\$' ),
                        $article->text,
                        1
                    );
                }
            }
        }
	}

	/**
	 * Render the module instance specified by ID.
	 *
	 * @param   string  $id     The ID of the module instance to render.
	 * @param   string  $style  The style of the module.
	 *
	 * @return  mixed
	 */
	protected function _loadmod( $id, $style = null )
	{
		// Get the specified module instance if not retrieved before.
		if ( ! isset( self::$modules ) || ! isset( self::$modules[ $id ] ) )
		{
			// Get necessary variables.
			$app      = JFactory::getApplication();
			$groups   = implode( ',', JFactory::getUser()->getAuthorisedViewLevels() );
			$lang     = JFactory::getLanguage()->getTag();
			$clientId = ( int ) $app->getClientId();

			// Build query to get data of the specified module instance.
			$dbo = JFactory::getDbo();
			$qry = $dbo->getQuery( true );

			$date     = JFactory::getDate();
			$now      = $date->toSql();
			$nullDate = $dbo->getNullDate();

			$qry
				->select( 'm.id, m.title, m.module, m.position, m.content, m.showtitle, m.params' )
				->from( '#__modules AS m' )
				->join( 'LEFT', '#__extensions AS e ON e.element = m.module AND e.client_id = m.client_id' )
				->where( 'e.enabled = 1' )
				->where( 'm.published = 1' )
				->where( 'm.id = ' . ( int ) $id )
				->where( '(m.publish_up = ' . $dbo->quote( $nullDate ) . ' OR m.publish_up <= ' . $dbo->quote( $now ) . ')' )
				->where( '(m.publish_down = ' . $dbo->quote( $nullDate ) . ' OR m.publish_down >= ' . $dbo->quote( $now ) . ')' )
				->where( 'm.access IN (' . $groups . ')' )
				->where( 'm.client_id = ' . $clientId );

			// Filter by language.
			if ( method_exists( $app, 'getLanguageFilter' ) && $app->getLanguageFilter() )
			{
				$qry->where( 'm.language IN (' . $dbo->quote( $lang ) . ',' . $dbo->quote( '*' ) . ')' );
			}

			// Get the specified module instance.
			$dbo->setQuery( $qry );

			try
			{
				self::$modules[ $id ] = $dbo->loadObject();

				if ( self::$modules[ $id ] )
				{
					// Get the module renderer.
					$renderer = JFactory::getDocument()->loadRenderer( 'module' );

					// Set module style if specified.
					$attribs = array();

					if ( ! empty( $style ) )
					{
						$attribs['params'] = json_encode( array( 'style' => $style ) );
					}

					// Render module.
					ob_start();

					echo $renderer->render( self::$modules[ $id ], $attribs );

					self::$modules[ $id ]->rendered = ob_get_clean();
				}
			}
			catch ( RuntimeException $e )
			{
				JLog::add( JText::sprintf( 'JLIB_APPLICATION_ERROR_MODULE_LOAD', $e->getMessage() ), JLog::WARNING, 'jerror' );

				return '';
			}
		}

		return @self::$modules[ $id ]->rendered;
	}
}

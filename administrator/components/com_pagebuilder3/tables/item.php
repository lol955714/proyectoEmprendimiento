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

defined('_JEXEC') or die('Restricted access');

jimport('joomla.database.tableasset');

/**
 * Class for working with item table.
 *
 * @package  JSN_PageBuilder3
 * @since    1.0.0
 */
class JSNPageBuilder3TableItem extends JTable
{
	/**
	 * Object constructor to set table and key fields.  In most cases this will
	 * be overridden by child classes to explicitly set the table and key fields
	 * for a particular database table.
	 *
	 * @param   JDatabase  &$db  JDatabase connector object.
	 */
	function __construct(&$db)
	{
		parent::__construct('#__jsn_pagebuilder3_items', 'item_id', $db);
	}

	/**
	 * Method to load PageBuilder data from link source.
	 *
	 * @param   string  $link_type  Type of link, e.g. com_content, com_modules, etc.
	 * @param   int     $link_id    ID of the source that links to PageBuilder.
	 *
	 * @return  boolean
	 */
	public function loadFromLinkSource( $link_type, $link_id )
	{
		// Query database for linked PageBuilder item.
		$dbo   = JFactory::GetDbo();
		$query = $dbo->getQuery( true );

		$query->select( '*' )->from( '#__jsn_pagebuilder3_items' )->where( 'link_type = ' . $query->quote( $link_type ) . ' AND link_id = ' . ( int ) $link_id );

		if ( $result = $dbo->setQuery( $query )->loadObject() )
		{
			$this->bind( ( array ) $result );
		}
	}
}

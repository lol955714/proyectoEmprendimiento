<?php
/**
 * @package	HikaShop for Joomla!
 * @version	4.0.1
 * @author	hikashop.com
 * @copyright	(C) 2010-2018 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class WarehouseController extends hikashopController {
	var $type='warehouse';
	var $pkey = 'warehouse_id';
	var $table = 'warehouse';
	var $orderingMap ='warehouse_ordering';

	function __construct($config = array()) {
		parent::__construct($config);
		$this->display[]='selection';
		$this->display[]='findValue';
		$this->modify[]='useselection';
	}
	function selection(){
		hikaInput::get()->set( 'layout', 'selection'  );
		return parent::display();
	}
	function useselection(){
		hikaInput::get()->set( 'layout', 'useselection'  );
		return parent::display();
	}

	function findValue() {
		$displayFormat = hikaInput::get()->getVar('displayFormat', '');
		$search = hikaInput::get()->getVar('search', null);

		$nameboxType = hikashop_get('type.namebox');
		$options = array(
			'displayFormat' => $displayFormat
		);
		$ret = $nameboxType->getValues($search, $this->type, $options);
		if(!empty($ret)) {
			echo json_encode($ret);
			exit;
		}
		echo '[]';
		exit;
	}
}

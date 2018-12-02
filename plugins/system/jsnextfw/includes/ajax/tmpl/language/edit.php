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

// Get Joomla document object.
$doc = JFactory::getDocument();

// Prepare save handler.
$save = "{$this->baseUrl}&format=json&action=save&lang={$lang}&client={$client}";

// @formatter:off
?>
<!DOCTYPE html>
<html lang="<?php echo $doc->language; ?>" dir="<?php echo $doc->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<?php
	// Load and render document head.
	$head = $doc->loadRenderer('head');

	echo $head->render('');
	?>
</head>
<body class="jsn-bootstrap4 pt-0">
	<div
		id="language-editor"
		data-render="api.Language"
		data-save="<?php echo $save; ?>"
		data-files="<?php echo JsnExtFwText::toJson($files); ?>"
		data-text-mapping="<?php
			echo JsnExtFwText::toJson(JsnExtFwText::translate(
				array(
					'JSN_EXTFW_SEARCH_FOR'
				)
			));
		?>"
	></div>
</body>
</html>

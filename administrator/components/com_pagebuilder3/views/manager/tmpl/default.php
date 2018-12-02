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
?>
<div id="j-sidebar-container" class="span2">
	<?php echo JHtmlSidebar::render(); ?>
</div>
<div id="j-main-container" class="span10">
	<div id="pb-manager-root"></div>
</div>
<?php
// Render footer.
JsnExtFwHtml::renderFooterComponent();

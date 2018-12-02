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

// Get Joomla document object.
$doc = JFactory::getDocument();

// Get default editor.
$editor = JFactory::getConfig()->get('editor');

if (strpos($editor, 'pagebuilder') !== false)
{
	$editor = 'none';
}

$isNone = $editor == 'none' ? true : false;

// Init and render editor.
$editor = JEditor::getInstance($editor);
$setter = $editor->setContent('editor', '%CONTENT%');
$editor = $editor->display('editor', '', '100%', '100%', '80', '25', array(
	'readmore',
	'pagebreak'
));

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
	<style type="text/css">
		body {
			margin: 0;
			border: 0;
			padding: 0;
		}

		textarea#editor, .toggle-editor {
			margin-bottom: 0;
		}
	</style>
</head>
<body class="jsn-bootstrap4 px-0 py-0">
	<div id="jsnextfw-widget-editor">
		<?php echo $editor; ?>
	</div>
	<script type="text/javascript">
		jQuery(function($) {
			$(window).load(function() {
				var
				wrapper = document.getElementById('jsnextfw-widget-editor'),
				wrapperCss = window.getComputedStyle(wrapper),
				textArea = document.getElementById('editor'),
				iframe = document.getElementById('editor_ifr'),

				// Fit editor to window height.
				handle_resize = function() {
					var
					clientHeight = ( document.documentElement || document.body ).clientHeight,
					scrollHeight = ( document.documentElement || document.body ).scrollHeight;

					if (iframe && scrollHeight > clientHeight && textArea.style.display == 'none') {
						iframe.style.height = (
							parseInt(iframe.style.height)
							- (scrollHeight - clientHeight)
							- parseInt( wrapperCss.getPropertyValue('padding-bottom') )
						) + 'px';
					}

					if (iframe) {
						var container = iframe.parentNode;

						while ( ! container.classList.contains('mce-tinymce') && container.nodeName != 'BODY' ) {
							container = container.parentNode;
						}

						if ( container.classList.contains('mce-tinymce') ) {
							var containerCss = window.getComputedStyle(container);

							textArea.style.margin  = containerCss.getPropertyValue('margin' );
							textArea.style.border  = containerCss.getPropertyValue('border' );
							textArea.style.padding = containerCss.getPropertyValue('padding');
							textArea.style.width   = containerCss.getPropertyValue('width'  );
							textArea.style.height  = containerCss.getPropertyValue('height' );
						}
					} else {
						var textAreaCss = window.getComputedStyle(textArea);

						textArea.style.width  = (
							parseInt( wrapperCss.getPropertyValue('width') )
							- parseInt( textAreaCss.getPropertyValue('border-left-width') )
							- parseInt( textAreaCss.getPropertyValue('border-right-width') )
							- parseInt( textAreaCss.getPropertyValue('padding-left') )
							- parseInt( textAreaCss.getPropertyValue('padding-right') )
						) + 'px';

						textArea.style.height = (
							parseInt( textAreaCss.getPropertyValue('height') )
							- (scrollHeight - clientHeight)
							- parseInt( wrapperCss.getPropertyValue('padding-bottom') )
						) + 'px';
					}
				};

				window.addEventListener('resize', function() {
					handle_resize.timer && clearTimeout(handle_resize.timer);

					handle_resize.timer = setTimeout(handle_resize, 100);
				});

				handle_resize();

				// Define function to set content for editor.
				window.JsnExtFwEditorSetContent = function(content) {
					<?php if ($isNone) : ?>
					document.querySelector('textarea#editor').value = content;
					<?php
					else :

					echo preg_replace('/["\']*%CONTENT%["\']*/', 'content', $setter);

					endif;
					?>
				};
			});
		});
	</script>
</body>
</html>

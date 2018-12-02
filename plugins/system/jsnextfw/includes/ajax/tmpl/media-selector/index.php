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
		#media-selector > div {
			height: 100vh !important;
		}
	</style>
</head>
<body>
	<div id="media-selector"></div>
	<script type="text/javascript">
		(function renderMediaSelector() {
			if (window.BBMediaSelector) {
				// Render BB Media Selector.
				const config = {
					baseURL: '<?php echo JUri::root(); ?>',
					getAllFiles: '<?php echo "{$this->baseUrl}&action=getListFiles"; ?>',
					uploadFile: '<?php echo "{$this->baseUrl}&action=uploadFile"; ?>',
					createFolder: '<?php echo "{$this->baseUrl}&action=createFolder"; ?>',
					deleteFolder: '<?php echo "{$this->baseUrl}&action=deleteFolder"; ?>',
					deleteFile: '<?php echo "{$this->baseUrl}&action=deleteFile"; ?>',
					renameFolder: '<?php echo "{$this->baseUrl}&action=renameFolder"; ?>',
					renameFile: '<?php echo "{$this->baseUrl}&action=renameFile"; ?>',
				};

				ReactDOM.render(
					React.createElement(BBMediaSelector, {config: config, fileType: 'TYPE_FILE'}),
					document.getElementById('media-selector')
				);

				// Initialize select action.
				var
				updater = '<?php echo JFactory::getApplication()->input->getString('handler'); ?>',
				fieldid = '<?php echo JFactory::getApplication()->input->getString('fieldid'); ?>',
				editor = '<?php echo JFactory::getApplication()->input->getString('editor'); ?>';

				if ( window.parent && (updater != '' || fieldid != '' || window.parent.jInsertFieldValue) ) {
					var
					selected,
					button = window.parent.document.querySelector('#sbox-window[aria-hidden="false"] .modal-footer .btn-primary')
						|| window.parent.document.querySelector('.mce-window.mce-in .mce-panel.mce-foot .btn-primary')
						|| window.parent.document.querySelector('.modal.in .modal-footer .btn-primary')
						|| window.parent.document.querySelector('.modal.show .modal-footer .btn-primary'),
					addEvent = function(elm, evt, fn) {
						if (typeof elm.addEventListener == 'function') {
							elm.addEventListener(evt, fn);
						} else if (typeof elm.attachEvent == 'function') {
							elm.attachEvent(evt, fn);
						}
					},
					removeEvent = function(elm, evt, fn) {
						if (typeof elm.removeEventListener == 'function') {
							elm.removeEventListener(evt, fn);
						} else if (typeof elm.detachEvent == 'function') {
							elm.detachEvent(evt, fn);
						}
					},
					triggerEvent = function(elm, evt) {
						if (typeof elm.dispatchEvent == 'function') {
							elm.dispatchEvent( new window.Event(evt) );
						} else if (typeof elm.fireEvent == 'function') {
							elm.fireEvent( 'on' + evt, document.createEventObject() );
						}
					},
					closeModal = function() {
						var w = window.parent || window;

						// Close the modal.
						if (w.jModalClose) {
							w.jModalClose();
						} else {
							var close = w.document.querySelector('#sbox-window[aria-hidden="false"] #sbox-btn-close')
								|| w.document.querySelector('.mce-window.mce-in .mce-close')
								|| w.document.querySelector('.modal.in [data-dismiss="modal"]')
								|| w.document.querySelector('.modal.show [data-dismiss="modal"]');

							if (close) {
								triggerEvent(close, 'click');

								if (close.parentNode.id == 'sbox-window') {
									close.parentNode.classList.remove('modal');
								}
							}
						}
					},
					select = function(event) {
						event.preventDefault();

						// Make sure there is a selection.
						if ( ! selected ) {
							return alert('<?php echo JText::_('JSN_EXTFW_MEDIA_SELECTOR_NO_FILE_SELECTED'); ?>');
						}

						try {
							// If there is a callback function, call it.
							if (updater && window.parent[updater]) {
								window.parent[updater](selected, fieldid);
							}

							// If default select handler available, call it.
							else if (fieldid && window.parent.jInsertFieldValue) {
								window.parent.jInsertFieldValue(selected, fieldid);
							}

							// Query for the affected field.
							else {
								var field = fieldid ? window.parent.document.getElementById(fieldid) : null;

								if (field) {
									field.value = selected;

									// Trigger a change event on the affected field.
									return triggerEvent(field, 'change');
								}

								// If not found any affected field, update editor content.
								else {
									var tag = '<img src="' + selected + '" />';

									if (window.parent.Joomla && window.parent.Joomla.editors.instances.hasOwnProperty(editor)) {
										window.parent.Joomla.editors.instances[editor].replaceSelection(tag);
									}
									else if (window.parent.jInsertEditorText) {
										window.parent.jInsertEditorText(tag, editor);
									}
								}
							}

							// Close the modal.
							closeModal();
						} catch (e) {
							// Do nothing.
						}
					},
					change = function(event) {
						selected = event.detail;

						if (button) {
							// Enable select button.
							button.disabled = false;
						} else {
							select(event);
						}
					},
					deselect = function(event) {
						selected = null;

						if (button) {
							// Disable select button.
							button.disabled = true;
						}
					};

					// Listen to 'select-file' event on the document.
					addEvent(document, 'select-file', change);

					// Listen to 'deselect-file' event on the document.
					addEvent(document, 'deselect-file', deselect);

					// Check if modal is created with SqueezeBox?
					var modal = window.parent.document.getElementById('sbox-window');

					if (modal) {
						// Convert to Bootstrap modal.
						modal.classList.add('modal');

						modal.style.left = null;
						modal.style.top = null;
						modal.style.width = null;
						modal.style.height = null;

						modal.firstElementChild.classList.add('modal-body');

						// Setup close button.
						var btn = window.parent.document.querySelector('#sbox-window[aria-hidden="false"] .modal-footer .btn-default');

						if (btn) {
							btn.onclick = closeModal;
						}
					}

					if (!button) {
						// Automatically generate a button to select media file.
						var btn = window.parent.document.querySelector('#sbox-window[aria-hidden="false"] .modal-footer .btn')
							|| window.parent.document.querySelector('.mce-window.mce-in .mce-panel.mce-foot .mce-btn button')
							|| window.parent.document.querySelector('.modal.in .modal-footer .btn')
							|| window.parent.document.querySelector('.modal.show .modal-footer .btn');

						if (!btn && modal) {
							// Create modal footer.
							var footer = document.createElement('div');
							footer.className = 'modal-footer';

							btn = document.createElement('button');
							btn.onclick = closeModal;
							btn.className = 'btn btn-default';
							btn.textContent = '<?php echo JText::_('JSN_EXTFW_CLOSE'); ?>';

							footer.appendChild(btn);
							modal.insertBefore(footer, modal.lastElementChild);
						}

						if (btn) {
							button = document.createElement(btn.nodeName);

							for (var i = 0; i < btn.attributes.length; i++) {
								button.setAttribute(btn.attributes[i].name, btn.attributes[i].value);
							}

							button.classList.add('btn');
							button.classList.add('btn-primary');
							button.classList.add('select-image');
							button.classList.remove('btn-default');

							button.textContent = '<?php echo JText::_('JSN_EXTFW_SELECT'); ?>';
							button.style.width = null;
							button.style.height = null;

							if (btn.parentNode.classList.contains('mce-btn')) {
								btn.parentNode.parentNode.insertBefore(button, btn.parentNode);
							} else {
								btn.parentNode.insertBefore(button, btn);
							}
						}
					}

					// Listen to 'click' event on the select button.
					addEvent(button, 'click', select);

					// Listen to 'beforeunload' event on the window.
					window.onbeforeunload = function(event) {
						// Stop listening to 'click' event on the select button.
						removeEvent(button, 'click', select);
					};
				}
			} else {
				setTimeout(renderMediaSelector, 100);
			}
		})();

		// change modal height
		(function setHeightForModal() {
		 const modal = window.top.document.querySelectorAll('#imageModal_jform_images_image_intro')[0]
		 if(modal) {
		    const modalBody = modal.querySelectorAll('.modal-body')[0]
			const modalHeader = modal.querySelectorAll('.modal-header')[0]
			const modalFooter = modal.querySelectorAll('.modal-footer')[0]
		    if(modalBody && modalHeader && modalFooter) {
			    modalBody.style.height = '100%'
			    modalBody.style.maxHeight = 'none'
				const modalHeaderHeight = parseInt(window.top.getComputedStyle(modalHeader).height.replace('px',''))
				const modalFooterHeight = parseInt(window.top.getComputedStyle(modalFooter).height.replace('px',''))
		        const iframe = modalBody.querySelectorAll('iframe')[0]
		        if(iframe) {
		            iframe.style.transition = '0.1s'
		            iframe.style.height = (window.top.innerHeight -  modalHeaderHeight - modalFooterHeight) * 0.75 + 'px'
		        }
		     }
		 }
		})();
	</script>
</body>
</html>

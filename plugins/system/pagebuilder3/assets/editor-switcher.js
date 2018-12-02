"use strict";

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

window.addEventListener("load", function() {
	var editor_switcher = document.getElementById("jsn-pb3-editor-switcher");
	var _window = window,
		pagefly_data = _window.pagefly_data;

	if (editor_switcher) {
		setTimeout(function() {
			jQuery("#jsn-pb2-editor-switcher").addClass("hidden");
			// Get the editor switcher of PageBuilder v1.
			var pb1_editor_switcher = document.getElementById("pb-editor-switcher");
			if (pb1_editor_switcher && window.pagefly) {
				jQuery(pb1_editor_switcher)
					.find('button:contains("Default Editor")')
					.trigger("click")
					.addClass("hidden");

				pb1_editor_switcher.setAttribute("class", "hidden");
			}
		}, 1000);
		if (editor_switcher && document.querySelector("textarea")) {
			if (pagefly_data) {
				// If user is create new article, pass the editor data check
				if (!pagefly_data.isNewContent) {
					try {
						// if jommla version >= 3.7, change style of editor button
						if (pagefly_data.isJoomla37)
							editor_switcher.style.lineHeight = "24px";
					} catch (e) {}
				}
			}

			// Show button to switch editor
			editor_switcher.classList.remove("hidden");
			editor_switcher.addEventListener("click", function(e) {
				e.preventDefault();
				e.stopPropagation();
				jQuery(editor_switcher.parentNode).toggleClass("open");
			});
			// Handle click event to switch editor
			editor_switcher.nextElementSibling.addEventListener("click", function(
				event
			) {
				if (event.target.nodeName == "A") {
					event.preventDefault();

					checkEditorData(
						event.target.href.substr(event.target.href.indexOf("#") + 1)
					);

					return false;
				}
			});
		}
	} else {
		if (Array.isArray(window.pb_available_editors)) {
			var editors = window.pb_available_editors
				.map(function(e) {
					return (
						'<a class="pb3-switch-editor" data-key="' +
						e +
						'" href="#">' +
						e +
						"</a>"
					);
				})
				.join("");
			// Create custom editor switcher
			var toolbar = jQuery(".btn-toolbar")[0];
			toolbar &&
			jQuery(toolbar).append(
				'<div class="pb3-dropdown btn-group">\n  <button id="pb3-switch-editor-button" class="btn pb3-dropbtn">Switch Editor</button>\n  <div id="pb3-myDropdown" class="pb3-dropdown-content" style="z-index: 9999;">\n   ' +
				editors +
				"\n  </div>\n</div>"
			);
		}
	}

	jQuery("#pb3-switch-editor-button").on("click", function(e) {
		e.preventDefault();
		document.getElementById("pb3-myDropdown").classList.toggle("pb3-show");
	});
	jQuery("a.pb3-switch-editor").on("click", function(e) {
		e.preventDefault();
		var newEditor = jQuery(e.target).data("key");
		checkEditorData(newEditor);
	});

	// Close the dropdown menu if the user clicks outside of it
	window.onclick = function(event) {
		if (!event.target.matches(".pb3-dropbtn")) {
			var dropdowns = document.getElementsByClassName("pb3-dropdown-content");
			var i;
			for (i = 0; i < dropdowns.length; i++) {
				var openDropdown = dropdowns[i];
				if (openDropdown.classList.contains("pb3-show")) {
					openDropdown.classList.remove("pb3-show");
				}
			}
		}
	};

	var switchPB3 = document.getElementById("pb-switch-pagebuilder3");
	if (switchPB3) {
		switchPB3.addEventListener("click", function(e) {
			localStorage.setItem("previewMode", "false");
			e.preventDefault();
			e.stopPropagation();
			requestSwitchEditor("pagebuilder3");
		});
	}

	function requestSwitchEditor() {
		var editor =
			arguments.length > 0 && arguments[0] !== undefined
				? arguments[0]
				: "pagebuilder3";

		// Show processing state
		editor_switcher && (editor_switcher.innerHTML = "Switching Editor...");
		// Send Ajax request to switch editor
		jQuery.ajax({
			url: "index.php?pb3ajax=1&task=switchEditor&editor=" + editor,
			complete: function complete(data) {
				insertParam(
					"switchFrom",
					pagefly_data ? pagefly_data.current_editor || "none" : "none"
				);
			}
		});
	}

	function insertParam(key, value) {
		key = encodeURI(key);
		value = encodeURI(value);
		var kvp = document.location.search.substr(1).split("&");
		var i = kvp.length;
		var x = void 0;
		while (i--) {
			x = kvp[i].split("=");

			if (x[0] == key) {
				x[1] = value;
				kvp[i] = x.join("=");
				break;
			}
		}
		if (i < 0) {
			kvp[kvp.length] = [key, value].join("=");
		}
		//this will reload the page, it's likely better to store this until finished
		var newSearch = kvp.join("&");
		if (location.search !== newSearch) {
			document.location.search = newSearch;
		} else {
			location.reload();
		}
	}

	function checkEditorData(editor) {
		var content =
			document.getElementById(window.pb_textarea_id) ||
			document.querySelector("textarea[id^='jform']") ||
			document.querySelector("textarea[id^='text']");
		if (
			content &&
			typeof content.value === "string" &&
			content.value.length > 10
		) {
			if (
				!/<!-- Start/.test(content.value) &&
				!pagefly_data &&
				editor === "pagebuilder3"
			) {
				if (
					confirmSwitch(
						"This article content is not from PageBuilder 3, editing this may cause layout broken, are you sure to edit?"
					)
				) {
					window.stop();
					requestSwitchEditor(editor);
				}
				return;
			} else if (/<!-- Start/.test(content.value) && pagefly_data) {
				if (
					confirmSwitch(
						"This article content is from PageBuilder 3, editing this may cause layout broken, are you sure to edit?"
					)
				) {
					window.stop();
					requestSwitchEditor(editor);
				}
				return;
			}
		}
		requestSwitchEditor(editor);
	}

	// If user disable popup, this function will return false -> no auto switch.
	function confirmSwitch(msg) {
		var startTime = new Date().getTime();
		if (confirm(msg)) {
			return true;
		}
		var endTime = new Date().getTime();
		return endTime - startTime < 50;
	}
});

var data = {
	page_id: ""
};
if (!window.pb2_editor_data) {
	window.pb2_editor_data = data;
}
if (!window.pb_editor_data) {
	window.pb_editor_data = data;
}

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

/**
 * Class for validating uploaded file.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwUpload
{

	/**
	 * Method to check an uploaded file for potential security risks.
	 *
	 * @param   array  $file     An uploaded file descriptor as stored in $_FILES.
	 * @param   array  $options  Verification options.
	 *
	 * @return  boolean
	 */
	public static function checkFile($file, $options = array())
	{
		// Prepare options.
		$options = array_merge($options,
			array(
				'null_byte' => true, // Check for null byte in file name.
				'forbidden_extensions' => array( // Check if file extension contains forbidden string (e.g. php matched .php, .xxx.php, .php.xxx and so on).
					'php',
					'phps',
					'php5',
					'php3',
					'php4',
					'inc',
					'pl',
					'cgi',
					'fcgi',
					'java',
					'jar',
					'py'
				),
				'php_tag_in_content' => true, // Check if file content contains <?php tag.
				'shorttag_in_content' => true, // Check if file content contains short open tag.
				'shorttag_extensions' => array( // File extensions that need to check if file content contains short open tag.
					'inc',
					'phps',
					'class',
					'php3',
					'php4',
					'php5',
					'txt',
					'dat',
					'tpl',
					'tmpl'
				),
				'fobidden_ext_in_content' => true, // Check if file content contains forbidden extensions.
				'fobidden_ext_extensions' => array( // File extensions that need to check if file content contains forbidden extensions.
					'zip',
					'rar',
					'tar',
					'gz',
					'tgz',
					'bz2',
					'tbz'
				)
			));

		// Check file name.
		$temp_name = is_array($file) ? $file['tmp_name'] : $file;
		$intended_name = is_array($file) ? $file['name'] : $file;

		// Check for null byte in file name.
		if ($options['null_byte'] && strstr($intended_name, "\x00"))
		{
			return false;
		}

		// Check if file extension contains forbidden string (e.g. php matched .php, .xxx.php, .php.xxx and so on).
		if (!empty($options['forbidden_extensions']))
		{
			$exts = explode('.', $intended_name);
			$exts = array_reverse($exts);

			array_pop($exts);

			$exts = array_map('strtolower', $exts);

			foreach ($options['forbidden_extensions'] as $ext)
			{
				if (in_array($ext, $exts))
				{
					return false;
				}
			}
		}

		// Check file content.
		if ($options['php_tag_in_content'] || $options['shorttag_in_content'] ||
			 ( $options['fobidden_ext_in_content'] && !empty($options['forbidden_extensions']) ))
		{
			$data = file_get_contents($temp_name);

			// Check if file content contains <?php tag.
			if ($options['php_tag_in_content'] && stristr($data, '<?php'))
			{
				return false;
			}

			// Check if file content contains short open tag.
			if ($options['shorttag_in_content'])
			{
				$suspicious_exts = $options['shorttag_extensions'];

				if (empty($suspicious_exts))
				{
					$suspicious_exts = array(
						'inc',
						'phps',
						'class',
						'php3',
						'php4',
						'txt',
						'dat',
						'tpl',
						'tmpl'
					);
				}

				// Check if file extension is in the list that need to check file content for short open tag.
				$found = false;

				foreach ($suspicious_exts as $ext)
				{
					if (in_array($ext, $exts))
					{
						$found = true;

						break;
					}
				}
			}

			// Check if file content contains forbidden extensions.
			if ($options['fobidden_ext_in_content'] && !empty($options['forbidden_extensions']))
			{
				$suspicious_exts = $options['fobidden_ext_extensions'];

				if (empty($suspicious_exts))
				{
					$suspicious_exts = array(
						'zip',
						'rar',
						'tar',
						'gz',
						'tgz',
						'bz2',
						'tbz'
					);
				}

				// Check if file extension is in the list that need to check file content for forbidden extensions.
				$found = false;

				foreach ($suspicious_exts as $ext)
				{
					if (in_array($ext, $exts))
					{
						$found = true;

						break;
					}
				}

				if ($found)
				{
					foreach ($options['forbidden_extensions'] as $ext)
					{
						if (strstr($data, '.' . $ext))
						{
							return false;
						}
					}
				}
			}

			// Make sure any string, that need to be check in file content, does not truncated due to read boundary.
			$data = substr($data, -10);
		}

		return true;
	}

	/**
	 * Method to check a file for potential XSS content.
	 *
	 * @param   string  $file  Absolute path to the file needs to be checked.
	 *
	 * @return  boolean
	 */
	public static function checkXss($file)
	{
		// Make sure the specified file does not contain unwanted tags.
		$xss_check = file_get_contents(is_array($file) ? $file['tmp_name'] : $file);
		$xss_check = substr($xss_check, -1, 256);

		$html_tags = array(
			'abbr',
			'acronym',
			'address',
			'applet',
			'area',
			'audioscope',
			'base',
			'basefont',
			'bdo',
			'bgsound',
			'big',
			'blackface',
			'blink',
			'blockquote',
			'body',
			'bq',
			'br',
			'button',
			'caption',
			'center',
			'cite',
			'code',
			'col',
			'colgroup',
			'comment',
			'custom',
			'dd',
			'del',
			'dfn',
			'dir',
			'div',
			'dl',
			'dt',
			'em',
			'embed',
			'fieldset',
			'fn',
			'font',
			'form',
			'frame',
			'frameset',
			'h1',
			'h2',
			'h3',
			'h4',
			'h5',
			'h6',
			'head',
			'hr',
			'html',
			'iframe',
			'ilayer',
			'img',
			'input',
			'ins',
			'isindex',
			'keygen',
			'kbd',
			'label',
			'layer',
			'legend',
			'li',
			'limittext',
			'link',
			'listing',
			'map',
			'marquee',
			'menu',
			'meta',
			'multicol',
			'nobr',
			'noembed',
			'noframes',
			'noscript',
			'nosmartquotes',
			'object',
			'ol',
			'optgroup',
			'option',
			'param',
			'plaintext',
			'pre',
			'rt',
			'ruby',
			's',
			'samp',
			'script',
			'select',
			'server',
			'shadow',
			'sidebar',
			'small',
			'spacer',
			'span',
			'strike',
			'strong',
			'style',
			'sub',
			'sup',
			'table',
			'tbody',
			'td',
			'textarea',
			'tfoot',
			'th',
			'thead',
			'title',
			'tr',
			'tt',
			'ul',
			'var',
			'wbr',
			'xml',
			'xmp',
			'!DOCTYPE',
			'!--'
		);

		foreach ($html_tags as $tag)
		{
			// A tag is '<tagname ', so we need to add < and a space or '<tagname>'.
			if (stristr($xss_check, '<' . $tag . ' ') || stristr($xss_check, '<' . $tag . '>'))
			{
				return false;
			}
		}

		return true;
	}
}

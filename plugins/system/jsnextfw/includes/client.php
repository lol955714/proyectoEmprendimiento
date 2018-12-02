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

/**
 * Class for getting client info.
 *
 * @package  JSN Extension Framework 2
 * @since    1.1.0
 */
class JsnExtFwClient
{

	/**
	 * Method for posting client information to JoomlaShine server.
	 *
	 * @return  void
	 */
	public static function postInfo($token = '')
	{
		if (!JsnExtFwHelper::isAdmin())
		{
			return false;
		}

		$user = JFactory::getUser();

		if (!$user->authorise('core.admin'))
		{
			return false;
		}

		$framework = JTable::getInstance('Extension');

		$framework->load(array(
			'type' => 'plugin',
			'folder' => 'system',
			'element' => 'jsnextfw'
		));

		// Check if JoomlaShine extension framework is disabled?
		if (!(int) $framework->extension_id || !(int) $framework->enabled)
		{
			return false;
		}

		// Define data to post to JoomlaShine server.
		$data = array(
			'phpInfo' => self::getPhpSettings(),
			'userInfo' => self::getUserInfo(),
			'systemInfo' => self::getSystemInfo(),
			'installedExtList' => self::getInstalledExtensionList()
		);

		if ($token == '')
		{
			$params = json_decode($framework->params, true);

			if ($params && !empty($params['token_key']))
			{
				$token = $params['token_key'];
			}
			else
			{
				$params = JsnExtFwHelper::getSettings(null, true);

				if (!empty($params['token']))
				{
					$token = $params['token'];
				}
			}
		}

		$secret_key = md5($data['userInfo']['domain'] . $data['userInfo']['server_ip']);

		try
		{
			JsnExtFwHttp::post(JSN_POST_CLIENT_INFO_URL,
				http_build_query(
					array(
						'client_information' => json_encode($data),
						'secret_key' => $secret_key,
						'token' => $token
					), null, '&'));
		}
		catch (Exception $e)
		{
			// Do nothing.
		}
	}

	/**
	 * Method to get PHP settings.
	 *
	 * @return  array
	 */
	public static function getPhpSettings()
	{
		$phpSettings = array();

		$phpSettings['php_built_on'] = php_uname();
		$phpSettings['php_version'] = phpversion();

		return $phpSettings;
	}

	/**
	 * Method to get system information.
	 *
	 * @return  array
	 *
	 */
	public static function getSystemInfo()
	{
		$version = new JVersion();
		$platform = new JPlatform();
		$db = JFactory::getDbo();

		$sysInfo = array(
			'database_version' => $db->getVersion(),
			'database_collation' => $db->getCollation(),
			'web_server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : getenv('SERVER_SOFTWARE'),
			'server_api' => php_sapi_name(),
			'joomla_version' => $version->getLongVersion(),
			'joomla_platform_version' => $platform->getLongVersion()
		);

		return $sysInfo;
	}

	/**
	 * Method to get user information.
	 *
	 * @return  array
	 *
	 */
	public static function getUserInfo()
	{
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$customerUsername = $app->getUserState('jsn.installer.customer.username', '');
		$userInfo = array(
			'domain' => JUri::root(),
			'server_ip' => self::getServerAddress()
		);

		if ($customerUsername != '')
		{
			$userInfo['client_customer_username'] = $customerUsername;
		}

		return $userInfo;
	}

	/**
	 * Method to get list of installed extensions.
	 *
	 * @return  array
	 *
	 */
	public static function getInstalledExtensionList()
	{
		$installedExtensionList = array();

		$db = JFactory::getDbo();

		$db->setQuery(
			$db->getQuery(true)
				->select('*')
				->from('#__extensions')
				->where("type = 'component'")
				->where("manifest_cache LIKE '%JoomlaShine%'"));

		try
		{
			$extensions = $db->loadObjectList();

			if (count($extensions))
			{
				foreach ($extensions as $extension)
				{
					$manifest = json_decode($extension->manifest_cache);

					$oldDefineFile = JPATH_ADMINISTRATOR . '/components/' . $extension->element . '/defines.' .
						 str_replace('com_', '', $extension->element) . '.php';

					$defineFile = JPATH_ADMINISTRATOR . '/components/' . $extension->element . '/' .
						 str_replace('com_', '', $extension->element) . '.defines.php';

					$installedExtensionList[$extension->element]['edition'] = '';

					if (file_exists($defineFile))
					{
						$constName = 'JSN_' . strtoupper(str_replace('com_', '', $extension->element)) . '_EDITION';
						$constIdentifiedName = 'JSN_' . strtoupper(str_replace('com_', '', $extension->element)) . '_IDENTIFIED_NAME';

						$defineFileContent = file_get_contents($defineFile);

						if (preg_match('#DEFINE\(\'' . $constName . '\',\s*\'(.*)\'\)\s*;#i', $defineFileContent, $match))
						{
							$installedExtensionList[$extension->element]['edition'] = $match[1];
						}

						if (preg_match('#DEFINE\(\'' . $constIdentifiedName . '\',\s*\'(.*)\'\)\s*;#i', $defineFileContent,
							$matchIdentifiedName))
						{
							$identifiedName = $matchIdentifiedName[1];
						}
					}
					elseif (file_exists($oldDefineFile))
					{
						$constName = 'JSN_' . strtoupper(str_replace('com_', '', $extension->element)) . '_EDITION';
						$constIdentifiedName = 'JSN_' . strtoupper(str_replace('com_', '', $extension->element)) . '_IDENTIFIED_NAME';

						$oldDefineFileContent = file_get_contents($oldDefineFile);

						if (preg_match('#DEFINE\(\'' . $constName . '\',\s*\'(.*)\'\)\s*;#i', $oldDefineFileContent, $match))
						{
							$installedExtensionList[$extension->element]['edition'] = $match[1];
						}

						if (preg_match('#DEFINE\(\'' . $constIdentifiedName . '\',\s*\'(.*)\'\)\s*;#i', $oldDefineFileContent,
							$matchIdentifiedName))
						{
							$identifiedName = $matchIdentifiedName[1];
						}
					}
					else
					{
						$installedExtensionList[$extension->element]['edition'] = '';
					}

					$installedExtensionList[$extension->element]['version'] = $manifest->version;
					$installedExtensionList[$extension->element]['name'] = strtoupper(str_replace('_', ' ', $extension->element));
					$installedExtensionList[$extension->element]['identifiedName'] = $identifiedName;
				}
			}

			return $installedExtensionList;
		}
		catch (Exception $e)
		{
			return $installedExtensionList;
		}
	}

	/**
	 * Method to get server address.
	 *
	 * @return  string
	 *
	 */
	public static function getServerAddress()
	{
		if (array_key_exists('SERVER_ADDR', $_SERVER))
		{
			if ($_SERVER['SERVER_ADDR'] == '::1')
			{
				if (array_key_exists('SERVER_NAME', $_SERVER))
				{
					return gethostbyname($_SERVER['SERVER_NAME']);
				}
				else
				{
					// Running CLI
					if (stristr(PHP_OS, 'WIN'))
					{
						return gethostbyname(php_uname("n"));
					}
					else
					{
						$ifconfig = shell_exec('/sbin/ifconfig eth0');

						preg_match('/addr:([\d\.]+)/', $ifconfig, $match);

						return $match[1];
					}
				}
			}

			return $_SERVER['SERVER_ADDR'];
		}
		elseif (array_key_exists('LOCAL_ADDR', $_SERVER))
		{
			return $_SERVER['LOCAL_ADDR'];
		}
		elseif (array_key_exists('SERVER_NAME', $_SERVER))
		{
			return gethostbyname($_SERVER['SERVER_NAME']);
		}
		else
		{
			// Running CLI
			if (stristr(PHP_OS, 'WIN'))
			{
				return gethostbyname(php_uname("n"));
			}
			else
			{
				$ifconfig = shell_exec('/sbin/ifconfig eth0');

				preg_match('/addr:([\d\.]+)/', $ifconfig, $match);

				return $match[1];
			}
		}

		return '';
	}
}

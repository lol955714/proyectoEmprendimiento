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

// Import necessary libraries.
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Class for managing customer account.
 *
 * @package  JSN Extension Framework 2
 * @since    1.0.0
 */
class JsnExtFwAjaxAccount extends JsnExtFwAjax
{

	/**
	 * Get current info.
	 *
	 * @throws  Exception
	 * @return  void
	 */
	public function getInfoAction()
	{
		// Get extension parameters.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		$params = JsnExtFwHelper::getSettings($this->component, true);

		// Query for existing user account.
		$this->dbo->setQuery(
			$this->dbo->getQuery(true)
				->select('element, params')
				->from('#__extensions')
				->where('element NOT LIKE ' . $this->dbo->quote($this->component))
				->where('manifest_cache LIKE "%JoomlaShine%"')
				->where('params LIKE "%username%"')
				->where('params LIKE "%token%"'));

		foreach ($this->dbo->loadObjectList() as $ext)
		{
			if (( $_params = json_decode($ext->params) ) && !empty($_params->username))
			{
				$accounts[$_params->username] = array(
					'label' => $_params->username,
					'value' => $ext->element
				);
			}
		}

		$this->setResponse(
			array(
				'token' => isset($params['token']) ? $params['token'] : '',
				'username' => isset($params['username']) ? $params['username'] : '',
				'accounts' => isset($accounts) ? array_values($accounts) : array(),
				'license' => $this->getLicenseAction(true)
			));
	}

	public function getTokenAction()
	{
		// Get request parameters.
		$username = $this->input->getString('username');
		$password = $this->input->getString('password');

		// Verify parameters.
		if (empty($this->component) || empty($username) || empty($password))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Prepare data.
		$domain = JUri::getInstance()->toString(array(
			'host'
		));
		$random = self::genRandomString();
		$secret = md5($random . $domain);

		// Send request.
		$result = JsnExtFwHttp::post(JSN_GET_TOKEN_URL,
			array(
				'domain' => $domain,
				'username' => $username,
				'password' => $password,
				'rand_code' => $random,
				'secret_key' => $secret
			));

		if (empty($result))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_ACCOUNT_FAILED_TO_GET_RESPONSE_FROM_JSN_SERVER'));
		}

		if ($result['result'] === 'error')
		{
			$key = 'JSN_EXTFW_LIGHTCART_' . strtoupper(isset($result['error_code']) ? $result['error_code'] : $result['message']);
			$msg = JText::_($key);

			if ($msg == $key)
			{
				$msg = $result['message'];
			}

			throw new Exception($msg);
		}

		// Store token key.
		try
		{
			JsnExtFwHelper::saveSettings($this->component, array(
				'username' => $username,
				'token' => $result['token']
			), true);
		}
		catch (Exception $e)
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_ACCOUNT_FAILED_TO_STORE_TOKEN_TO_DATABASE'));
		}

		// Clear cached license data.
		$cache = JFactory::getConfig()->get('tmp_path') . "/{$this->component}/license.data";

		if (JFile::exists($cache))
		{
			JFile::delete($cache);
		}

		$this->setResponse($result['token']);
	}

	public function copyTokenAction()
	{
		// Get request parameters.
		$from = $this->input->getString('from');

		// Verify parameters.
		if (empty($this->component) || empty($from))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Detect extension type.
		$prefix = substr($from, 0, 4);

		if ($prefix == 'com_')
		{
			$type = 'component';
		}
		elseif ($prefix == 'mod_')
		{
			$type = 'module';
		}
		else
		{
			$type = 'template';
		}

		// Get token data from the specified extension.
		$params = self::getExtensionParams($type, $from);

		if (empty($params['username']) || empty($params['token']))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Store token key.
		try
		{
			JsnExtFwHelper::saveSettings($this->component,
				array(
					'username' => $params['username'],
					'token' => $params['token']
				), true);
		}
		catch (Exception $e)
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_ACCOUNT_FAILED_TO_STORE_TOKEN_TO_DATABASE'));
		}

		// Clear cached license data.
		$cache = JFactory::getConfig()->get('tmp_path') . "/{$this->component}/license.data";

		if (JFile::exists($cache))
		{
			JFile::delete($cache);
		}

		$this->setResponse($params['token']);
	}

	public function getLicenseAction($return = true)
	{
		// Verify parameters.
		if (empty($this->component))
		{
			if ($return)
			{
				return null;
			}

			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Get extension parameters.
		$params = JsnExtFwHelper::getSettings($this->component, true);

		// Get token.
		if (!empty($params['token']))
		{
			$token = $params['token'];
		}

		if (empty($token))
		{
			if ($return)
			{
				return null;
			}

			throw new Exception(JText::_('JSN_EXTFW_MISSING_TOKEN_KEY'));
		}

		// Look for license data in the temporary directory first.
		$cache = JFactory::getConfig()->get('tmp_path') . "/{$this->component}/license.data";

		if (JFile::exists($cache) && ( time() - filemtime($cache) < 24 * 60 * 60 ) && ( $license = file_get_contents($cache) ) != '')
		{
			return $license;
		}

		// Build URL for requesting license data.
		$link = JSN_GET_LICENSE_URL;
		$link .= '&identified_name=' . JsnExtFwHelper::getConstant('IDENTIFIED_NAME', $this->component);
		$link .= '&domain=' . JUri::getInstance()->toString(array(
			'host'
		));
		$link .= '&ip=' . $_SERVER['SERVER_ADDR'];
		$link .= '&token=' . $token;

		// Send a request to JoomlaShine server to get license data.
		try
		{
			$result = JsnExtFwHttp::get($link);

			if ($result && $result['result'] === 'success')
			{
				// Cache license data to a local file.
				if (JFolder::create(dirname($cache)))
				{
					JFile::write($cache, $result['message']);
				}

				if ($return)
				{
					return $result['message'];
				}

				$this->setResponse($result['message']);
			}
			elseif (!$result || $result['result'] === 'failure')
			{
				if ($return)
				{
					return null;
				}

				if ($result)
				{
					$key = 'JSN_EXTFW_LIGHTCART_' . strtoupper($result['error_code'] ? $result['error_code'] : $result['message']);
					$msg = JText::_($key);

					if ($msg == $key)
					{
						$msg = $result['message'];
					}
				}

				throw new Exception($result ? $msg : json_last_error_msg());
			}
		}
		catch (Exception $e)
		{
			// Reuse cache file if available.
			if (JFile::exists($cache) && ( $license = file_get_contents($cache) ) != '')
			{
				// Refresh cache file after 1 day.
				touch($cache);

				if ($return)
				{
					return $license;
				}

				$this->setResponse($license);
			}
			else
			{
				if ($return)
				{
					return null;
				}

				throw $e;
			}
		}
	}

	public function clearLicenseAction()
	{
		// Verify parameters.
		if (empty($this->component))
		{
			if ($return)
			{
				return null;
			}

			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Remove license data in the temporary directory.
		$cache = JFactory::getConfig()->get('tmp_path') . "/{$this->component}/license.data";

		if (JFile::exists($cache))
		{
			JFile::delete($cache);
		}
	}

	public function tryProAction()
	{
		// Verify parameters.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Get extension parameters.
		$params = JsnExtFwHelper::getSettings($this->component, true);

		// Get token.
		if (!empty($params['token']))
		{
			$token = $params['token'];
		}

		if (empty($token))
		{
			throw new Exception(JText::_('JSN_EXTFW_MISSING_TOKEN_KEY'));
		}

		// Build URL for requesting license data.
		$link = JSN_JOIN_TRIAL_URL;
		$link .= '&identified_name=' . JsnExtFwHelper::getConstant('IDENTIFIED_NAME', $this->component);
		$link .= '&domain=' . JUri::getInstance()->toString(array(
			'host'
		));
		$link .= '&ip=' . $_SERVER['SERVER_ADDR'];
		$link .= '&token=' . $token;

		// Send a request to JoomlaShine server to register Trial license.
		$result = JsnExtFwHttp::get($link);

		if ($result && $result['result'] === 'success')
		{
			// Clear cached license data.
			$cache = JFactory::getConfig()->get('tmp_path') . "/{$this->component}/license.data";

			if (JFile::exists($cache))
			{
				JFile::delete($cache);
			}

			// Get new license data.
			$this->getLicenseAction();
		}
		elseif (!$result || $result['result'] == 'failure')
		{
			if ($result)
			{
				$key = 'JSN_EXTFW_LIGHTCART_' . strtoupper($result['error_code'] ? $result['error_code'] : $result['message']);
				$msg = JText::_($key);

				if ($msg == $key)
				{
					$msg = $result['message'];
				}
			}

			throw new Exception($result ? $msg : json_last_error_msg());
		}
	}

	public function buyProAction()
	{
		// Verify parameters.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Clear cached license data.
		$cache = JFactory::getConfig()->get('tmp_path') . "/{$this->component}/license.data";

		if (JFile::exists($cache))
		{
			JFile::delete($cache);
		}

		// Redirect to extension introduction page at JoomlaShine website.
		$this->app->redirect(JsnExtFwHelper::getConstant('BUY_LINK', $this->component));
	}

	public function unlinkAction()
	{
		// Verify parameters.
		if (empty($this->component))
		{
			throw new Exception(JText::_('JSN_EXTFW_AJAX_INVALID_PARAMETERS'));
		}

		// Clear cached license data.
		$cache = JFactory::getConfig()->get('tmp_path') . "/{$this->component}/license.data";

		if (JFile::exists($cache))
		{
			JFile::delete($cache);
		}

		// Get current settings.
		$settings = JsnExtFwHelper::getSettings($this->component, true);

		// Clear username and token.
		$settings['username'] = null;
		$settings['token'] = null;

		JsnExtFwHelper::saveSettings($this->component, $settings);
	}

	/**
	 * Generate a randon string.
	 *
	 * @return  string
	 */
	protected static function genRandomString()
	{
		$length = 4;
		$chars = 'abcdefghijklmnopqrstuvwxyz';
		$chars_length = ( strlen($chars) - 1 );
		$string = $chars{rand(0, $chars_length)};

		for ($i = 1; $i < $length; $i = strlen($string))
		{
			$r = $chars{rand(0, $chars_length)};

			if ($r != $string{$i - 1})
			{
				$string .= $r;
			}
		}

		$fullString = dechex(time() + mt_rand(0, 10000000)) . $string;
		$result = strtoupper(substr($fullString, 2, 10));

		return $result;
	}

	/**
	 * Get extension parameters stored in the 'extensions' table.
	 *
	 * @param   string  $type     Either 'component', 'module', 'plugin' or 'template'.
	 * @param   string  $element  Extension's element name.
	 * @param   string  $group    Plugin group, required for 'plugin'.
	 *
	 * @return  array
	 */
	protected static function getExtensionParams($type, $element, $group = '')
	{
		$dbo = JFactory::getDbo();
		$qry = $dbo->getQuery(true)
			->select('params')
			->from('#__extensions')
			->where('type = ' . $dbo->quote($type))
			->where('element = ' . $dbo->quote($element));

		if ('plugin' == $type)
		{
			$qry->where('folder = ' . $dbo->quote($group));
		}

		$dbo->setQuery($qry);

		if (!( $params = json_decode($dbo->loadResult(), true) ))
		{
			$params = array();
		}

		return $params;
	}
}

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

/**
 * Configuration controller.
 *
 * @package  JSN_PageBuilder3
 * @since    1.0.0
 */
class JSNPageBuilder3ControllerConfiguration extends JSNConfigController
{
    /**
     * Verify Token key
     *
     * @return  json type
     */
    public function getToken()
    {
        // Check token
        JSession::checkToken('get') or die('Invalid Token');

        // Get input object
        $input = JFactory::getApplication()->input;

        $method = $input->getMethod();

        // Checking customer information
        $username = $input->getUsername('username', '');
        $password = $input->$method->get('password', '', 'RAW');

        if ($username == '' || $password == '') {
            exit(json_encode(array('message' => JText::_('JSN_EXTFW_LIGHTCART_ERROR_TOKEN_ERR01'), 'result' => 'error')));
        }

        $isAllowedUser = JFactory::getUser()->authorise('core.admin');

        if (!$isAllowedUser) {
            exit(json_encode(array('message' => JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), 'result' => 'error')));
        }


        $randCode = $this->createRandCode();
        $domain = JURI::root();

        preg_match('@^(?:http://www\.|http://|www\.|http:|https://www\.|https://|www\.|https:)?([^/]+)@i', $domain, $domainFilter);
        $domain = $domainFilter[1];
        $secretKey = md5($randCode . $domain);
        $query = array();

        $query['rand_code'] = $randCode;
        $query['domain'] = $domain;
        $query['secret_key'] = $secretKey;
        $query['username'] = $username;
        $query['password'] = $password;

        $url = JSN_EXT_GET_TOKEN_URL;
        $arguments = array();
        $arguments["RequestMethod"] = "POST";
        $arguments["PostValues"] = $query;
        // Get results

        try {
            $result = JSNUtilsHttp::getWithOption($url, '', false, $arguments);

            // JSON-decode the result
            $result = json_decode($result['body']);

            if (is_null($result)) {
                exit(json_encode(array('message' => JText::_('JSN_EXTFW_ERROR_FAILED_TO_CONNECT_OUR_SERVER'), 'result' => 'error')));
            }

            if ((string)$result->result == 'error') {
                exit(json_encode(array('message' => JText::_('JSN_EXTFW_LIGHTCART_ERROR_' . $result->message), 'result' => 'error')));
            }

            require_once JPATH_ROOT . '/plugins/system/jsnframework/libraries/joomlashine/client/client.php';

            try {
                // Post client information
                JSNClientInformation::postClientInformation($result->token);
                $this->updatePageBuilder3Token($result->token, $username);
            } catch (Exception $e) {
                exit(json_encode(array('message' => JText::_('JSN_EXTFW_CONFIG_TOKEN_IS_VALID'), 'result' => 'success', 'token' => $result->token)));
            }

            exit(json_encode(array('message' => JText::_('JSN_EXTFW_CONFIG_TOKEN_IS_VALID'), 'result' => 'success', 'token' => $result->token)));
        } catch (Exception $e) {
            exit(json_encode(array('message' => JText::_('JSN_EXTFW_ERROR_FAILED_TO_CONNECT_OUR_SERVER'), 'type' => 'error')));
        }
    }

    public function refreshToken() {
        JFolder::delete(JPATH_ROOT . '/tmp');

        die('Your token data is refreshed');
    }

    private function updatePageBuilder3Token($token, $username)
    {
        $oldParams = $this->getPB3ExtParams();

        // Then merge with new params.
        $params = array_merge($oldParams, array('token' => $token, 'username' => $username));


        // Store to database.
        $dbo = JFactory::getDbo();
        $q = $dbo->getQuery(true);
        $q
            ->update('#__extensions')
            ->set('params = ' . $q->quote(json_encode($params)))
            ->where('element = ' . $dbo->quote('com_pagebuilder3'))
            ->where('type = ' . $dbo->quote('component'));

        $dbo->setQuery($q);
        return $dbo->execute();
    }

    private function getPB3ExtParams()
    {
        $dbo = JFactory::getDbo();
        $q = $dbo->getQuery(true);

        $q->select($dbo->quoteName(array('params')))
            ->from($dbo->quoteName('#__extensions'))
            ->where('element = ' . $dbo->quote('com_pagebuilder3'))
            ->where('type = ' . $dbo->quote('component'));
        $dbo->setQuery($q);
        $result = json_decode($dbo->loadResult(), true);

        return $result;
    }

}

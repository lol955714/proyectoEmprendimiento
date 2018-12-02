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
jimport('joomla.filesystem.folder');


/**
 * JSN PageBuilder3 Ajax handler.
 *
 * @package  JSN_PageBuilder3
 * @since    1.0.0
 */
class JSNPageBuilder3Ajax
{
    /**
     * Variable to hold the active Joomla application.
     *
     * @var  JApplication
     * @since 1.0.0
     */
    protected $app;

    /**
     * Variable to hold the active Joomla database connector.
     *
     * @var  JDatabaseDriver
     * @since 1.0.0
     */
    protected $dbo;
//    protected $limitFolder = '/images';

    protected $content;

    public function __construct()
    {
        // Get Joomla application.
        $this->app = JFactory::getApplication();

        // Get Joomla database connector.
        $this->dbo = JFactory::getDbo();

        if (!class_exists('JSNPageBuilder3ContentHelper')) {
            require_once JPATH_ROOT . '/administrator/components/com_pagebuilder3/helpers/content.php';
        }
        $this->content = new JSNPageBuilder3ContentHelper();

        // Only disable error reporting and display when accessing pb3ajax
        ini_set('error_reporting', E_STRICT);
        ini_set('display_errors', 'Off');
    }

    public function handleRequest()
    {
        // Get requested task.
        $task = $this->app->input->getCmd('task');
        // If requested task is valid, execute it.
        $data = array();

        if (method_exists($this, $task)) {
            $data = call_user_func(array($this, $task));
        }
        return $data;
    }

    protected function getDefaultPage()
    {
        return '';
    }

    protected function getPageBuilder3Banner()
    {
        $this->authorizeRequest();
        $qry = $this->dbo->getQuery(true)
            ->select('params')
            ->from('#__extensions')
            ->where('type = "component" ')
            ->where('element = "com_pagebuilder3"');

        $this->dbo->setQuery($qry);
        if (!($params = json_decode($this->dbo->loadResult(), true))) {
            $params = array();
        }
        if (isset($params['token'])) {
            $token = $params['token'];
            $result = JSNPageBuilder3ContentHelper::fetchHttp('https://www.joomlashine.com/index.php?option=com_lightcart&view=adsbanners&task=adsbanners.getBanners&tmpl=component&type=json&category_alias=jsn-pagebuilder-3-inspector-panel&token=' . $token);
            die($result);
        }
        return null;
    }

    protected function savePageData()
    {
        header('Content-Type: application/json');
        $this->authorizeRequest();

        $data = isset($_POST['data']) ? $_POST['data'] : '';
        $page_hash = $this->app->input->getString('page_hash', '');

        if ($page_hash !== '') {
            $existed = $this->content->select('data', '#__jsn_pagebuilder3_pages', "`page_hash` =  '$page_hash'", true);
            if (!empty($existed)) {
                $fields = array($this->dbo->quoteName('data') . ' = ' . $this->dbo->quote($data));
                $conditions = array(
                    $this->dbo->quoteName('page_hash') . ' = ' . $this->dbo->quote($page_hash),
                );
                if ($this->content->update($fields, $conditions, '#__jsn_pagebuilder3_pages') == 'true') {
                    return array('success' => true);
                } else {
                    return array('success' => false, 'message' => 'Failed to update data, please try again!');
                }
            }


            $columns = array('page_hash', 'data');
            $values = array($this->dbo->quote($page_hash), $this->dbo->quote($data));
            $result = $this->content->create($columns, $values, '#__jsn_pagebuilder3_pages');
            return array('success' => $result[0], 'id' => $result[1]);
        } else {
            return array('success' => false, 'message' => 'Missing parameters!');
        }
    }

    protected function getPageData()
    {
        header('Content-Type: application/json');
        $page_hash = $this->app->input->getString('page_hash', '');
        $data = $this->content->select('data', '#__jsn_pagebuilder3_pages', "`page_hash` =  '$page_hash'", true);

        return !empty($data->data) ? array('success' => true, 'data' => json_decode($data->data)) : array('success' => false);
    }

    protected function deletePageData()
    {
        header('Content-Type: application/json');
        $this->authorizeRequest();
        $page_hash = $this->app->input->getString('page_hash', '');
        if ($page_hash !== '') {
            return array('success' => $this->content->delete($page_hash, 'page_hash', '#__jsn_pagebuilder3_pages'));
        }

        return array('success' => false, 'message' => 'Missing parameter!');
    }

    /**
     * @return array
     * @since 1.1.1
     */
    public function getRevisions()
    {
        $page_hash = $this->app->input->getString('page_hash', null);
        if (empty($page_hash)) {
            return array('success' => false, 'data' => 'Missing parameter!');
        }
        $result = $this->getRevisionsByPageHash($page_hash);
        if (!empty($result)) {
            return array('success' => true, 'data' => $result);
        } else {
            return array('success' => false, 'data' => array());
        }
    }

    public function getElements()
    {
        return array('success' => true, 'data' => $this->getAllElement());
    }

    public function saveElement()
    {
        if (empty($_POST['data'])) {
            return array('success' => false, 'data' => 'Missing parameter!');
        }
        $data = $this->dbo->quote($_POST['data']);
        $element_id = !empty($_POST['element_id']) ? $_POST['element_id'] : null;
        $html = $this->dbo->quote(!empty($_POST['html']) ? $_POST['html'] : '');
        $element_type = $this->dbo->quote(!empty($_POST['element_type']) ? $_POST['element_type'] : 'normal');
        $status = $this->dbo->quote(!empty($_POST['status']) ? $_POST['status'] : 'normal');
        if ($element_id === null) {
            $columns = array('data', 'html', 'status', 'type');
            $values = array($data, $html, $status, $element_type);
            $result = $this->content->create($columns, $values, '#__jsn_pagebuilder3_elements');

        } else {
            $fields = array(
                $this->dbo->quoteName('data') . ' = ' . $data,
                $this->dbo->quoteName('type') . ' = ' . $element_type,
                $this->dbo->quoteName('status') . ' = ' . $status,
                $this->dbo->quoteName('html') . ' = ' . $html
            );
            $conditions = array(
                $this->dbo->quoteName('id') . ' = ' . $element_id,
            );
            $result = $this->content->update($fields, $conditions, '#__jsn_pagebuilder3_elements');
        }
        return array('success' => $result, 'data' => $this->getAllElement());
    }

    public function deleteElement()
    {
        $element_id = !empty($_POST['element_id']) ? $_POST['element_id'] : null;
        if ($element_id === null) {
            return array('success' => false, 'data' => 'Missing parameter element ID!');
        }

        return array('success' => $this->content->delete($element_id, 'id', '#__jsn_pagebuilder3_elements'));
    }

    private function getAllElement()
    {
        return $this->content->select('id, type, data, html, status, updated', '#__jsn_pagebuilder3_elements', "", false, "updated DESC");
    }

    private function getRevisionsByPageHash($page_hash)
    {
        $result = $this->content->select('id, revision_data, status, updated', '#__jsn_pagebuilder3_revisions', "`page_hash` =  '$page_hash'", false, "updated DESC");
        if (!empty($result) && is_array($result)) {
            foreach ($result as $k => $r) {
                $result[$k]->revision_html_url = JUri::root() . "index.php?option=com_ajax&format=html&plugin=pagebuilder3&task=getRevisionHTML&id={$r->id}";
            }
        }
        return $result;
    }

    public function getRevisionHTML()
    {
        header('Content-Type: text/html; charset=utf-8');
        $id = $this->app->input->getString('id', null);
        if (empty($id)) {
            die('Not Found!');
        }
        $html = $this->content->select('revision_html', '#__jsn_pagebuilder3_revisions', "`id` =  '$id'", true);
        die($html->revision_html);
    }

    /**
     * @return array
     * @since 1.1.1
     */
    public function saveRevision()
    {
        $page_hash = $this->app->input->getString('page_hash', '');
        $revision_data = !empty($_POST['revision_data']) ? $_POST['revision_data'] : '';
        $revision_html = !empty($_POST['revision_html']) ? $_POST['revision_html'] : '';
        $status = !empty($_POST['status']) ? $_POST['status'] : '';

        if (empty($page_hash) || empty($revision_data)) {
            return array('success' => false, 'data' => 'Missing parameter!');
        }
        if ($status === 'draft') {
            $query = $this->dbo->getQuery(true);
            // delete all page draft.
            $conditions = array(
                $this->dbo->quoteName('page_hash') . ' = ' . $this->dbo->quote($page_hash),
                $this->dbo->quoteName('status') . ' = ' . $this->dbo->quote('draft')
            );
            $query->delete($this->dbo->quoteName('#__jsn_pagebuilder3_revisions'));
            $query->where($conditions);
            $this->dbo->setQuery($query);
            $this->dbo->execute();
        }

        $limit = 30;
        $data = $this->getRevisionsByPageHash($page_hash);
        if (class_exists('JsnExtFwHelper')) {
            $config = JsnExtFwHelper::getSettings('com_pagebuilder3');
            $revision_limit = $config['revision_limit'];
            $limit = !empty($revision_limit) ? $revision_limit : 30;
        }
        if ($limit < count($data)) {
            $this->dbo->setQuery("DELETE FROM `#__jsn_pagebuilder3_revisions` WHERE `page_hash` = " . $this->dbo->quote($page_hash) . " ORDER BY updated ASC LIMIT 1");
            $this->dbo->execute();
        }
        $columns = array('page_hash', 'revision_data', 'revision_html', 'status');
        $values = array($this->dbo->quote($page_hash), $this->dbo->quote($revision_data), $this->dbo->quote($revision_html), $this->dbo->quote($status));
        $result = $this->content->create($columns, $values, '#__jsn_pagebuilder3_revisions');
        $data = $this->getRevisionsByPageHash($page_hash);
        return array('success' => isset($result[0]) ? $result[0] : false, 'data' => $data);
    }

    /**
     * @return array
     * @since 1.1.1
     */
    public function deleteRevisionById()
    {
        $revision_id = $this->app->input->getString('revision_id', '');

        return array('success' => $this->content->delete($revision_id, 'id', '#__jsn_pagebuilder3_revisions'));
    }

    /**
     * @return array
     * @since 1.1.1
     */
    public function deleteRevisionByPageHash()
    {
        $page_hash = $this->app->input->getString('page_hash', '');
        return array('success' => $this->content->delete($page_hash, 'page_hash', '#__jsn_pagebuilder3_revisions'));
    }


    protected function savePreset()
    {

    }

    protected function getPreset()
    {

    }

    protected function deletePreset()
    {

    }

    /**
     *
     * @return mixed
     *
     * @since version
     */
    protected function listPageBuilderArticles()
    {
        header('Content-Type: application/json');
        $type = $this->app->input->getString('type', '');
        $limit = $this->app->input->getString('limit', '0,1000');
        $search = $this->app->input->getString('search', '');
        $search = $search !== '' ? " AND `a`.`title` LIKE '%{$search}%' " : '';
        jimport('joomla.database.table');
        switch ($type) {
            default:
            case 'articles':
                {
                    $content =& JTable::getInstance("content");
                    $query = "SELECT `a`.`id` ,  `a`.`title` ,  `a`.`publish_up`, `c`.`title` as `cTitle`   FROM  `#__content` as `a`  JOIN `#__categories` as `c` ON `a`.`catid` = `c`.`id`  WHERE  `introtext` LIKE  '<!-- Start%' OR  `fulltext` LIKE  '<!-- Start%' {$search} LIMIT {$limit} ";
                    $this->dbo->setQuery($query);
                    $result = $this->dbo->loadObjectList();
                    foreach ($result as $key => $article) {
                        $content->load($article->id);
                        $result[$key]->state = $content->get('state');
                    }
                    break;
                }
            case 'modules':
                {
                    $query = "SELECT `id` ,  `title` ,  `position`, `published` as `state` FROM  `#__modules`  WHERE  `content` LIKE  '<!-- Start%' {$search} LIMIT {$limit} ";
                    $this->dbo->setQuery($query);
                    $result = $this->dbo->loadObjectList();
                    break;
                }
        }

        return $result;

    }


    protected function uploadFile()
    {

        // Verify token. Only access in administrator
        $this->authorizeRequest();

        $d = $this->app->input->getString('dir', '');
        $root = JPATH_ROOT . '/' . $d;
        if ($_POST['data_uri']) {
            $uri = $root . $_POST['filename'];
            if (is_file($uri)) {
                return array(
                    "message" => 'Filename already exists!',
                    'uri' => $_POST['filename'],
                    'list' => $this->listFiles($d)
                );
            }
            $data = $_POST['data_uri'];
            list($type, $data) = explode(';', $data);
            if (preg_match('/image|svg|pdf|video/', $type, $matches)) {
                list(, $data) = explode(',', $data);
                $data = base64_decode($data);
                file_put_contents($uri, $data);
                return array("message" => "done", 'uri' => $_POST['filename'], 'list' => $this->listFiles($d));
            } else {
                return array("message" => "Invalid File type!");

            }
        }

    }

    protected function uploadFileNew()
    {
        // Verify token. Only access in administrator
        $this->authorizeRequest();
        $d = $this->app->input->getString('dir', '');
        $target_dir = JPATH_ROOT . '/' . $d;
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $message = array();
        // Check if image file is a actual image or fake image
        if (isset($_POST["submit"])) {
            $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
            if ($check !== false) {
                $message [] = "File is an image - " . $check["mime"] . ".";
                $uploadOk = 1;
            } else {
                $message [] = "File is not an image.";
                $uploadOk = 0;
            }
        }
        // Check if file already exists
        if (file_exists($target_file)) {
            $message [] = "Sorry, file already exists.";
            $uploadOk = 0;
        }
        // Check file size
        if ($_FILES["fileToUpload"]["size"] > 500000) {
            $message [] = "Sorry, your file is too large.";
            $uploadOk = 0;
        }
        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif") {
            $message [] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            $message [] = "Sorry, your file was not uploaded.";
            // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $message [] = "The file " . basename($_FILES["fileToUpload"]["name"]) . " has been uploaded.";
            } else {
                $message [] = "Sorry, there was an error uploading your file.";
            }
        }
        return array('message' => $message);
    }


    protected function getListFiles()
    {

        // Verify token.
        $this->authorizeRequest();

        $d = $this->app->input->getString('dir', '');
        $type = $this->app->input->getString('type', '');

        return $this->listFiles($d, $type);

    }

    protected function createFolder()
    {
        $this->authorizeRequest();

        $d = $this->app->input->getString('dir', '');
        $name = $this->app->input->getString('name', '');
        try {
            if (file_exists(JPATH_ROOT . $d . $name)) {
                return array('success' => false, 'message' => 'The folder name is already exists!', 'path' => $d . $name);

            }
            $created = JFolder::create(JPATH_ROOT . $d . $name);
            if ($created) {
                return array('success' => true, 'message' => 'New folder successfully created!', 'path' => $d . $name);
            } else {
                return array('success' => false, 'message' => 'Failed to create new folder, please try again!', 'path' => $d . $name);
            }
        } catch (Exception $e) {
            return $e;
        }
    }

    protected function deleteFolder()
    {
        $this->authorizeRequest();

        $d = $this->app->input->getString('dir', '');
        try {
            if (file_exists(JPATH_ROOT . $d)) {
                $deleted = JFolder::delete(JPATH_ROOT . $d);

                if ($deleted === true) {
                    return array('success' => true, 'message' => 'The folder ' . $d . ' has been deleted!', 'path' => $d);
                } else {
                    return array('success' => false, 'message' => 'Failed to delete folder, please try again!', 'path' => $d);
                }
            }
        } catch (Exception $e) {
            return $e;
        }
    }

    protected function renameFolder()
    {
        $this->authorizeRequest();

        $d = $this->app->input->getString('dir', '');
        $newPath = $this->app->input->getString('newPath', '');
        try {
            if (file_exists(JPATH_ROOT . $newPath)) {
                return array('success' => false, 'message' => 'A folder with this name is already exists!', 'path' => $d, 'newPath' => $newPath);
            } else {
                $moved = JFolder::move(JPATH_ROOT . $d, JPATH_ROOT . $newPath);
                if ($moved === true) {
                    return array('success' => $moved, 'message' => 'Successfully moved/renamed folder!', 'path' => $d, 'newPath' => $newPath);
                } else {
                    return array('success' => $moved, 'message' => 'An error occurred, please try again', 'path' => $d, 'newPath' => $newPath);
                }
            }
        } catch (Exception $e) {
            return $e;
        }
    }

    protected function renameFile()
    {
        $this->authorizeRequest();

        $d = $this->app->input->getString('dir', '');
        $newPath = $this->app->input->getString('newPath', '');
        try {
            if (file_exists(JPATH_ROOT . $newPath)) {
                return array('success' => false, 'message' => 'A file with this name is already exists!', 'path' => $d, 'newPath' => $newPath);
            } else {
                $moved = JFile::move(JPATH_ROOT . $d, JPATH_ROOT . $newPath);
                if ($moved === true) {
                    return array('success' => $moved, 'message' => 'Successfully moved/renamed file!', 'path' => $d, 'newPath' => $newPath);
                } else {
                    return array('success' => $moved, 'message' => 'An error occurred, please try again', 'path' => $d, 'newPath' => $newPath);
                }
            }
        } catch (Exception $e) {
            return $e;
        }
    }

    protected function deleteFile()
    {
        $this->authorizeRequest();

        $d = $this->app->input->getString('dir', '');
        try {
            if (file_exists(JPATH_ROOT . $d)) {
                $deleted = JFile::delete(JPATH_ROOT . $d);

                if ($deleted === true) {
                    return array('success' => true, 'message' => 'The folder ' . $d . ' has been deleted!', 'path' => $d);
                } else {
                    return array('success' => false, 'message' => 'Failed to delete folder, please try again!', 'path' => $d);
                }
            }
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Switch content editor.
     *
     * @param   string $new_editor Editor to switch to.
     *
     * @return  string
     */
    public function switchEditor($new_editor = null)
    {
        // Get new editor to switch to.
        $new_editor = $this->app->input->get('editor', $new_editor);

        if (!is_null($new_editor)) {
            // Get current user.
            $user = JFactory::getUser();

            // Get current user parameters.
            $params = json_decode($user->get('params'));

            if (!$params) {
                $params = new stdClass();
            }

            // Store default editor of the current user to session storage if necessary.
            if ($new_editor == 'pagebuilder3') {
                // Get Joomla session handler.
                $session = JFactory::getApplication()->getSession();

                if (!$session->has('pb3_user_editor')) {
                    if ($params->editor == 'pagebuilder3') {
                        $params->editor = 'global';
                    }

                    $session->set('pb3_user_editor', empty($params->editor) ? 'global' : $params->editor);
                }
            } elseif ($new_editor == 'global') {
                $new_editor = '';
            }

            // Set new editor into the user parameters.
            $user->setParam('editor', $new_editor);
            $config_editor = new JConfig();
            $this->app->set('editor', $new_editor == '' ? $config_editor->editor : $new_editor);

            $params->editor = $new_editor;

            // Save new user parameters.
            $table = $user->getTable();

            $table->load($user->get('id'));

            $table->params = json_encode($params);

            $table->store();

            return $new_editor->name;
        }
    }

    /**
     * Get list of PageBuilder template.
     *
     * @return  void
     */
    protected function getPackages()
    {
        // Import PageBuilder plugins.
        JPluginHelper::importPlugin('pagebuilder3');

        // Get list of PageBuilder element.
        $results = JEventDispatcher::getInstance()->trigger('getElementList');
        $packages = array();

        foreach ($results as $result) {
            $package = array(
                'name' => isset($result['name']) ? $result['name'] : '',
                'title' => isset($result['title']) ? $result['title'] : 'Untitled',
                'scripts' => isset($result['scripts']) ? $result['scripts'] : array(),
                'elements' => isset($result['elements']) ? $result['elements'] : array()
            );

            foreach ($package['scripts'] as &$path) {
                $path = JURI::root() . "plugins/pagebuilder3/{$result['name']}/{$path}";
            }

            // Get element templates.
            $package['templates'] = array();

            $this->getTemplates("{$result['name']}/templates", '', $package['templates']);

            $packages[] = $package;
        }

        return $packages;
    }

    protected function getModules()
    {
        // Verify token.
        JSession::checkToken('get') or jexit('Invalid Token');

        // Prepare request data.
        $module_type = $this->app->input->getCmd('type');
        $filter_text = $this->app->input->getString('filter');

        // Get database object.
        $this->dboo = JFactory::getDbo();

        // Build query.
        $qry = $this->dboo->getQuery(true);

        $qry->select('*')->from('#__modules')->where('client_id = 0')->where('published = 1');

        if (!empty($module_type)) {
            $qry->where("module LIKE '%{$module_type}%'");
        }

        if (!empty($filter_text)) {
            $qry->where("title LIKE '%{$filter_text}%'");
        }

        // Query for results.
        $modules = array();

        if ($results = $this->dboo->setQuery($qry)->loadObjectList()) {
            // Prepare data to return.
            foreach ($results as $result) {
                $modules[] = array(
                    'id' => $result->id,
                    'type' => $result->module,
                    'title' => $result->title
                );
            }
        }

        return $modules;
    }

    protected function getModuleStyles()
    {
        $moduleStyles = array();

        // Define system template.
        $templates = array(
            ( object )array(
                'element' => 'system',
                'name' => 'system',
                'enabled' => 1
            )
        );

        // Get active template.
        $dbo = JFactory::getDbo();
        $qry = $dbo->getQuery(true);

        $qry
            ->select('e.element, e.name, e.enabled')
            ->from('#__extensions as e')
            ->join('inner', '#__template_styles as t ON t.template = e.element')
            ->where('e.type = ' . $dbo->quote('template'))
            ->where('t.client_id = 0')
            ->where('t.home = 1');

        $dbo->setQuery($qry);

        $templates[] = $dbo->loadObject();

        // Get all available module chromes.
        foreach ($templates as $template) {
            $modulesFilePath = JPATH_SITE . "/templates/{$template->element}/html/modules.php";

            // Is there modules.php for that template?
            if (is_file($modulesFilePath)) {
                $modulesFileData = file_get_contents($modulesFilePath);
                $pattern = '/function[\s\t]*modChrome\_([a-z0-9\-\_]*)[\s\t]*\(/i';

                if (preg_match_all($pattern, $modulesFileData, $styles)) {
                    $moduleStyles[$template->element] = $styles[1];
                }
            }
        }

        return $moduleStyles;
    }

    protected function getModuleHTML()
    {
        // Get module ID.
        $id = $this->app->input->getCmd('moduleID');

        // Emulate an article to let our load module plugin render the module.
        $article = ( object )array('text' => '{pb3loadmodule ' . $id . '}');
        $params = array();

        // Import content plugin.
        JPluginHelper::importPlugin('content');

        // Trigger onContentPrepare event.
        JEventDispatcher::getInstance()->trigger('onContentPrepare', array('', &$article, &$params, 0));

        echo new JResponseJson(array('html' => $article->text, 'id' => $id));

        exit;
    }

    protected function getArticles()
    {
        if (!file_exists(JPATH_PLUGINS . '/system/pagebuilder3/includes/articles.php')) {
            return array('Failed to load Articles.');
        }
        // Load model to get articles.
        include_once JPATH_PLUGINS . '/system/pagebuilder3/includes/articles.php';

        $articlesModel = new JSNPB3ArticlesModel();

        // Prepare attributes to get articles.
        $attributes = array(
            // Filter article by categories.
            'articlelist_filter_categories' => $this->app->input->getString('filter_category', $this->app->input->getString('categories', '')),
            // Filter article by authors
            'articlelist_filter_authors' => $this->app->input->getString('filter_author', $this->app->input->getString('authors'), ''),
            // The number of article to retrieve.
            'articlelist_amount' => $this->app->input->get('limit', 30, 'INTEGER'),
            // Sort article by: a.ordering, a.id, a.title, fp.ordering, ...
            'articlelist_sort_by' => $this->app->input->get('sort', 'a.publish_up', 'STRING'),
            // Order direction: ASC, DESC.
            'articlelist_sort_order' => $this->app->input->get('order', 'DESC', 'STRING'),
            // Filter article by date.
            'articlelist_filter_date' => $this->app->input->get('date_filter', '', 'STRING'),
            // Name of date field: a.created, a.modified, a.publish_up
            'articlelist_date_field' => $this->app->input->get('date_field', 'a.publish_up', 'STRING'),
            // Number of days from the date specified to get articles.
            'articlelist_relative_date' => $this->app->input->get('days', 30, 'NUMBER'),
            // Get articles from this start date...
            'articlelist_range_date_start' => $this->app->input->get('date_start', '', 'STRING'),
            // ...to this end date.
            'articlelist_range_date_end' => $this->app->input->get('date_end', '', 'STRING'),
            // Limit article text to the number of word specified.
            'articlelist_article_text_limitation' => $this->app->input->get('text_limit', 0, 'NUMBER'),
            // Convert article date to the format specified.
            'articlelist_article_date_format' => $this->app->input->get('date_format', 'l, F j, Y', 'STRING'),
            // strip tags or not.
            'articlelist_article_strip_tags' => $this->app->input->get('strip_tags', 'true', 'STRING')
        );

        // Get articles based on the specified attributes.
        $results = $articlesModel->getArticlesByAttributes($attributes);
        $articles = array();
        $word_limit = $this->app->input->getInt('word_limit', null);

        if (!empty($results)) {
            // Load content helper route.
            if (!class_exists('ContentHelperRoute')) {
                require_once JPATH_ROOT . '/components/com_content/helpers/route.php';
            }

            foreach ($results as $result) {
                $result['direct_url'] = $this->replaceBaseUrl(JRoute::_(ContentHelperRoute::getArticleRoute($result['id'], $result['catid']), false));
                $result['category_direct_url'] = $this->replaceBaseUrl(JRoute::_(ContentHelperRoute::getCategoryRoute($result['catid']), false));


                foreach ($result as $key => $value) {
                    switch ($key) {
                        case 'images':
                        case 'urls':
                        case 'attribs':
                        case 'metadata':
                            $result[$key] = json_decode($value);
                            break;

                        case 'text':
                        case 'introtext':
                        case 'fulltext':
                            if ($word_limit !== null || $attributes['articlelist_article_text_limitation'] === 0) {
                                if ($word_limit === null) {
                                    $word_limit = 20;
                                }
                                $result[$key] = $this->truncateText(
                                    $value,
                                    $word_limit,
                                    true
                                );

                            } else if ($attributes['articlelist_article_text_limitation'] > 0) {
                                // Truncate content.
                                $result[$key] = $this->truncateText(
                                    $value,
                                    $attributes['articlelist_article_text_limitation']
                                );
                            }
                            break;

                        case 'created':
                        case 'modified':
                        case 'publish_up':
                            if (!empty($attributes['articlelist_article_date_format'])) {
                                $result[$key] = date(
                                    $attributes['articlelist_article_date_format'],
                                    strtotime($value)
                                );
                            }
                            break;
                    }
                }
                if (empty($result['images'])) {
                    $result['images'] = (object)array();
                }

                $articles[] = $result;
            }
        }

        return $articles;
    }

    protected function getArticlesFilter()
    {
        // Query data.
        $results = new stdClass();

        $results->categories = $this->getCategories('joomla');
        $results->author = $this->getActiveAuthors('joomla');

        return $results;
    }

    protected function clearTmp() {
        JFolder::delete(JPATH_ROOT . '/tmp');
    }

    protected function getK2Items()
    {
        if (!is_file(JPATH_ADMINISTRATOR . '/components/com_k2/models/items.php')) {
            return array();
        }

        // Load model to get K2 items.
        include_once JPATH_ADMINISTRATOR . '/components/com_k2/models/model.php';
        include_once JPATH_ADMINISTRATOR . '/components/com_k2/models/items.php';

        $model = new K2ModelItems();

        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        // Prepare attributes to get K2 items.
        $this->app->setUserState('global.list.limit', $this->app->input->getInt('limit', 30));
        $this->app->setUserState($option.$view.'.limitstart', $this->app->input->getInt('limitstart', 0));
	    $this->app->setUserState($option.$view.'filter_order', str_replace('a.', 'i.', $this->app->input->getString('sort', 'i.id')));

//        $this->app->setUserState($option.$view.'filter_order', $this->app->input->getCmd('filter_order', 'i.id'));
        $this->app->setUserState($option.$view.'filter_order_Dir', $this->app->input->getWord('filter_order_Dir', $this->app->input->getWord('order', 'DESC')));

        $this->app->setUserState($option.$view.'filter_trash', $this->app->input->getInt('filter_trash', 0));
        $this->app->setUserState($option.$view.'filter_featured', $this->app->input->getInt('filter_featured', -1));
        $this->app->setUserState($option.$view.'filter_category', $this->app->input->getInt('categories', 0));
        $this->app->setUserState($option.$view.'filter_author', $this->app->input->getInt('authors', $this->app->input->getInt('filter_author', 0)));
        $this->app->setUserState($option.$view.'filter_state', $this->app->input->getInt('filter_state', 1));

        $this->app->setUserState($option.$view.'search', $this->app->input->getString('search', ''));
        $this->app->setUserState($option.$view.'tag', $this->app->input->getInt('tag', 0));
        $this->app->setUserState($option.$view.'language', $this->app->input->getString('language', ''));

        $this->app->setUserState('text_limit', $this->app->input->getInt('text_limit', 0));
        $this->app->setUserState('strip_tags', $this->app->input->getString('strip_tags', 'true'));
        $this->app->setUserState('date_format', $this->app->input->getString('date_format', 'l, F j, Y'));

        // Get K2 items based on the specified attributes.
        $results = $model->getData();
        $articles = array();

        if (!empty($results)) {
            // Load content helper route.
            if (!class_exists('K2HelperRoute')) {
                require_once JPATH_ROOT . '/components/com_k2/helpers/route.php';
            }
            $word_limit = $this->app->input->getInt('word_limit', null);


            foreach ($results as $result) {
                $result->direct_url = $this->replaceBaseUrl(JRoute::_(K2HelperRoute::getItemRoute($result->id, $result->catid), false));
                $result->category_direct_url = $this->replaceBaseUrl(JRoute::_(K2HelperRoute::getCategoryRoute($result->catid), false));
                //Get K2 intro image.
                if (JFile::exists(JPATH_SITE . DS . 'media' . DS . 'k2' . DS . 'items' . DS . 'cache' . DS . md5("Image" . $result->id) . '_XL.jpg')) {
                    $result->images->image_intro = 'media/k2/items/cache/' . md5("Image" . $result->id) . '_XL.jpg';
                }

                foreach ($result as $key => $value) {
                    switch ($key) {
                        case 'params':
                        case 'metadata':
                            $result->{$key} = json_decode($value);
                            break;

                        case 'text':
                        case 'introtext':
                        case 'fulltext':

                            if ($word_limit !== null || $this->app->getUserState('text_limit') === 0) {
                                if ($word_limit === null) {
                                    $word_limit = 20;
                                }
                                $result->{$key} = $this->truncateText(
                                    $value,
                                    $word_limit,
                                    true
                                );

                            } else if ($this->app->getUserState('text_limit') > 0) {
                                // Truncate content.
                                $result->{$key} = $this->truncateText(
                                    $value,
                                    $this->app->getUserState('text_limit')
                                );
                            }

                            break;

                        case 'created':
                        case 'modified':
                        case 'publish_up':
                            if ($this->app->getUserState('date_format') != '') {
                                $result->{$key} = date(
                                    $this->app->getUserState('date_format'),
                                    strtotime($value)
                                );
                            }
                            break;
                    }
                }
                $articles[] = $result;
            }
        }

        return $articles;
    }

    protected function getK2ItemsFilter()
    {
        // Query data.
        $results = new stdClass();

        $results->categories = $this->getCategories('k2');
        $results->author = $this->getActiveAuthors('k2');
        $results->tags = $this->getK2Tags();

        return $results;
    }

    protected function getEasyBlogPosts()
    {
        if (!is_file(JPATH_ADMINISTRATOR . '/components/com_easyblog/models/blogs.php')) {
            return array();
        }

        // Load model to get EasyBlog posts.
        include_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/easyblog.php';
        include_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/model.php';
        include_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/blogs.php';

        $model = new EasyBlogModelBlogs();

        // Prepare attributes to get EasyBlog posts.
        $this->app->setUserState('com_easyblog.blogs.limit', $this->app->input->getInt('limit', 30));
        $this->app->setUserState('com_easyblog.blogs.search', $this->app->input->getString('search', ''));
        $this->app->setUserState('com_easyblog.blogs.filter_sort_by', $this->app->input->getString('sort', ''));
        $this->app->setUserState('com_easyblog.blogs.filter_order', $this->app->input->getCmd('filter_order', 'a.id'));
        $this->app->setUserState('com_easyblog.blogs.filter_order_Dir', $this->app->input->getWord('filter_order_Dir', $this->app->input->getWord('order', 'DESC')));
        $this->app->setUserState('com_easyblog.blogs.filter_state', $this->app->input->getWord('filter_state', 'P'));
        $this->app->setUserState('com_easyblog.blogs.filter_category', $this->app->input->getInt('categories', 0));
        $this->app->setUserState('com_easyblog.blogs.filter_blogger', $this->app->input->getInt('filter_blogger', $this->app->input->getInt('filter_author', 0)));
        $this->app->setUserState('com_easyblog.blogs.filter_language', $this->app->input->getString('language', ''));
        $this->app->setUserState('text_limit', $this->app->input->getInt('text_limit', 0));
        $this->app->setUserState('strip_tags', $this->app->input->getString('strip_tags', 'true'));
        $this->app->setUserState('date_format', $this->app->input->getString('date_format', 'l, F j, Y'));
        $word_limit = $this->app->input->getInt('word_limit', null);
        // Get EB items based on the specified attributes.
        $results = $model->getData();

        $articles = array();
        $category = $this->getCategories('easyblog');

        if (!empty($results)) {

            foreach ($results as $result) {
                if (!empty($result->image)) {
                    try {
                        if (!class_exists('EBMM') && file_exists(JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/mediamanager/mediamanager.php')) {
                            require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/mediamanager/mediamanager.php';
                        }
                        $result->image = EBMM::getUrl($result->image, true);
                    } catch (Exception $e) {
                    }
                } else {
                    $result->image = JUri::root() . 'plugins/system/pagebuilder3/assets/img/place-holder.svg';
                }
                $result->created_by_who = $this->getEasyBlogAuthor($result->created_by);
                $result->direct_url = 'index.php?option=com_easyblog&view=entry&id=' . $result->id;
                $result->category_direct_url = 'index.php?option=com_easyblog&view=categories&layout=listings&id=' . $result->category_id;
                foreach ($category as $k => $v) {
                    if ($v->id == $result->category_id) {
                        $result->category_title = $v->title;
                    }
                }
                $result->category_title = !empty($result->category_title) ? $result->category_title : 'Uncategorized';

                foreach ($result as $key => $value) {
                    switch ($key) {
                        case 'document':
                            $result->{$key} = json_decode($value);
                            break;


                        case 'content':
                        case 'intro':
                        case 'excerpt':
                            if ($word_limit !== null || $this->app->getUserState('text_limit') === 0) {
                                if ($word_limit === null) {
                                    $word_limit = 20;
                                }
                                $result->{$key} = $this->truncateText(
                                    $value,
                                    $word_limit,
                                    true
                                );

                            } else if ($this->app->getUserState('text_limit') > 0) {
                                // Truncate content.
                                $result->{$key} = $this->truncateText(
                                    $value,
                                    $this->app->getUserState('text_limit')
                                );
                            }
                            break;

                        case 'created':
                        case 'modified':
                        case 'publish_up':
                            if ($this->app->getUserState('date_format') != '') {
                                $result->{$key} = date(
                                    $this->app->getUserState('date_format'),
                                    strtotime($value)
                                );
                            }
                            break;
                    }
                }

                $articles[] = $result;
            }
        }

        return $articles;
    }

    protected function getEasyBlogPostsFilter()
    {
        // Query data.
        $results = new stdClass();

        $results->categories = $this->getCategories('easyblog');
        $results->author = $this->getActiveAuthors('easyblog');

        return $results;
    }

    protected function getTemplates($root, $path, &$array)
    {
        $fullPath = JPATH_PLUGINS . "/pagebuilder3/{$root}{$path}";

        if (!file_exists($fullPath)) {
            return;
        }

        if ($handle = opendir($fullPath)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && is_dir("{$fullPath}/{$entry}")) {
                    $array[] = array(
                        'type' => 'dir',
                        'path' => $path,
                        'name' => $entry,
                    );

                    $this->getTemplates($root, "{$path}/{$entry}", $array);
                } else {
                    $file_parts = pathinfo("{$fullPath}/{$entry}");

                    switch ($file_parts['extension']) {
                        case 'json':
                            $data = file_get_contents("{$fullPath}/{$entry}");

                            $array[] = array(
                                'type' => 'file',
                                'path' => $path,
                                'name' => str_replace('.json', '', $entry),
                                'data' => json_decode($data)
                            );
                            break;
                    }
                }
            }

            closedir($handle);
        }
    }

    protected function getCategories($source = 'joomla')
    {
        try {
            $qry = $this->dbo->getQuery(true);

            switch ($source) {
                case 'k2' :
                    $qry->select('id, name AS title')->from('#__k2_categories');
                    break;

                case 'easyblog' :
                    $qry->select('id, title')->from('#__easyblog_category');
                    break;

                case 'joomla' :
                default :
                    $qry->select('id, title')->from('#__categories')->where('extension = "com_content"');
                    break;
            }

            $qry->where('published = 1')->order('title');

            $this->dbo->setQuery($qry);

            $results = $this->dbo->loadObjectList();
        } catch (Exception $e) {
            $results = array();
        }

        return $results;
    }

    protected function getActiveAuthors($source = 'joomla')
    {
        try {
            $qry = $this->dbo->getQuery(true);

            $qry->select('id, name, username')->from('#__users');

            switch ($source) {
                case 'k2' :
                    $table = '#__k2_items';
                    break;

                case 'easyblog' :
                    $table = '#__easyblog_post';
                    break;

                case 'joomla' :
                default :
                    $table = '#__content';
                    break;
            }

            $sub = $this->dbo->getQuery(true)->select('distinct(created_by)')->from($table);

            $qry->where('id IN (' . $sub . ')')->order('name');

            $this->dbo->setQuery($qry);

            $results = $this->dbo->loadAssocList('id');
        } catch (Exception $e) {
            $results = array();
        }

        return $results;
    }

    protected function getK2Tags()
    {
        try {
            $qry = $this->dbo->getQuery(true);

            $qry->select('id, name AS tag')->from('#__k2_tags')->where('published = 1')->order('name');

            $this->dbo->setQuery($qry);

            $results = $this->dbo->loadObjectList();
        } catch (Exception $e) {
            $results = array();
        }

        return $results;
    }

    private function replaceBaseUrl($url)
    {
        return preg_replace('/^(.*)(index.php.*)$/i', '$2', $url);
    }

    private function listFiles($d, $type = '')
    {
        $root = JPATH_ROOT . '/' . $d;

        $files = array();
        $dirs = array();
        $directories = array();
        $last_letter = $root[strlen($root) - 1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;

        $directories[] = $root;

        while (sizeof($directories)) {
            $dir = array_pop($directories);
            if ($handle = opendir($dir)) {
                $count = 0;
                $ignore = array('cgi-bin', '.', '..', '._', '_installation', 'cache', 'bin', 'cli', 'logs', 'tmp');
                while (false !== ($file = readdir($handle))) {
                    if (in_array($file, $ignore) || substr($file, 0, 1) == '.') {
                        continue;
                    }

                    $path = $dir . $file;
                    list($width, $height) = getimagesize($path);
                    $file_size = filesize($path);
                    $obj = new stdClass();
                    $obj->name = $file;
                    $obj->key = $count++;
                    $obj->file_size = round($file_size / 1024, 2);
                    $obj->image_width = $width;
                    $obj->image_height = $height;

                    if (is_dir($dir . $file)) {
                        $obj->type = 'dir';
                        $dirs[] = $obj;
                    } else {
                        $obj->type = 'file';
                        $files[] = $obj;
                    }
                }
                closedir($handle);
            }
        }
        if ($type === 'file') {
            return $files;
        } elseif ($type === 'dir') {
            return $dirs;
        }

        return array_merge($dirs, $files);
    }


    private function getEasyBlogAuthor($id)
    {
        $q = $this->dbo->getQuery(true);
        $q->select($this->dbo->quoteName('nickname'))
            ->from($this->dbo->quoteName('#__easyblog_users'))
            ->where($this->dbo->quoteName('id') . ' = ' . $id);
        $this->dbo->setQuery($q);

        return $this->dbo->loadResult();
    }

    private function truncateText($text, $limit, $limit_by_word = false)
    {
        // Clear all <!-- ... --> comment tags.
        $parts = explode('<!--', $text);
        $text = $parts[0];

        for ($i = 1, $n = count($parts); $i < $n; $i++) {
            $tempo = explode('-->', $parts[$i]);
            $text .= $tempo[1];
        }

        // Clear all <style> tag.
        $parts = explode('<style', $text);
        $text = $parts[0];

        for ($i = 1, $n = count($parts); $i < $n; $i++) {
            $tempo = explode('</style>', $parts[$i]);
            $text .= $tempo[1];
        }

        // Clear all <script> tag.
        $parts = explode('<script', $text);
        $text = $parts[0];

        for ($i = 1, $n = count($parts); $i < $n; $i++) {
            $tempo = explode('</script>', $parts[$i]);
            $text .= $tempo[1];
        }

        // Insert a space between sibling close and open HTML tag.
        $text = preg_replace('#(</[^>]+>)(<[^\>]+>)#', '\\1 \\2', trim($text));
        $text = preg_replace('/{.*}/', ' ', $text);
        // Truncate text.
        if ($limit_by_word) {
            $text = $this->limit_text(strip_tags($text), $limit);
        } else {
            $text = \Joomla\String\StringHelper::substr(strip_tags($text), 0, $limit);
        }
        return html_entity_decode($text);
    }

    private function limit_text($text, $limit)
    {
        $arr_words = explode(" ", trim($text), $limit + 1);
        array_pop($arr_words);
        return implode(" ", $arr_words);
    }

    public function json_fix($data)
    {
        # Process arrays
        if (is_array($data))
        {
            $new = array();
            foreach ($data as $k => $v)
            {
                $new[$k] = $this->json_fix($v);
            }
            $data = $new;
        } # Process objects
        else if (is_object($data))
        {
            $datas = get_object_vars($data);
            foreach ($datas as $m => $v)
            {
                $data->$m = $this->json_fix($v);
            }
        } # Process strings
        else if (is_string($data) && !json_encode($data)) {
            $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
            $data = preg_replace($regex, '$1', $data);
        }

        return $data;
    }

    private function authorizeRequest()
    {

        if (!JFactory::getUser()->authorise('core.manage') || !JSession::checkToken('get')) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array('success' => false, 'message' => 'Unauthorized request!')
            );
            die;
        }
    }

    public function sendEmail()
    {
        header('Content-type: application/json');
		$input      = JFactory::getApplication()->input;
		$post       = $input->post->getArray();

		$jconfig 	= JFactory::getConfig();
		$secret 	= $jconfig->get('secret');

        if (!isset($post[md5($secret)]))
        {
            echo json_encode(array('success' => false, 'message' => 'Invalid Token'));
            exit();
        }

		jimport('joomla.mail.helper');


        $exclusive  = array('recipient', 'subject', 'from_name', 'from_email', 'custom_message', md5($secret));

        if (count((array) $post))
        {
            $mailer             = JFactory::getMailer();
            $recipient          = '';
            $fromName           = '';
            $fromEmail          = '';
            $customMessage      = '';
            $subject            = '';
            $body               = '';

            // Check Recipient
            if (isset($post['recipient']))
            {
                $recipient = trim(strip_tags($post['recipient']));
            }

            if ($recipient == '')
            {
                $recipient = $jconfig->get('mailfrom');
            }
            // Check Recipient

            // Check Recipient
            if (isset($post['subject']))
            {
                $subject = trim($post['subject']);
            }

            if ($subject == '')
            {
                $subject = $jconfig->get('sitename');
            }
            // Check Recipient

            // Check From Name
            if (isset($post['from_name']))
            {
                $fromName = trim(strip_tags($post['from_name']));
            }

            if ($fromName == '')
            {
                $fromName = $jconfig->get('fromname');
            }
            // Check From Name

            // Check From Email
            if (isset($post['from_email']))
            {
                $fromEmail = trim(strip_tags($post['from_email']));
            }

            if ($fromEmail == '')
            {
                $fromEmail = $jconfig->get('mailfrom');
            }
            // Check From Email

            // Check Customer Message
            if (isset($post['custom_message']))
            {
                $customMessage = trim(strip_tags($post['custom_message']));
            }

            if ($customMessage == '')
            {
                $customMessage = 'Email was sent successfully!';
            }
            // Check From Email

            if ($recipient == '' || !JMailHelper::isEmailAddress($recipient))
            {
                echo json_encode(array('success' => false, 'message' => 'Recipient email address is invalid!'));
                exit();
            }

            foreach ($post as $key => $item)
            {
 				if (!in_array($key, $exclusive))
                {
                    if (is_array($item))
                    {
                        if (count((array) $item))
                        {
                            $tmpItem    = $item;
                            $tmpString  = '';

                            foreach ($tmpItem as $subKey => $subItem)
                            {
                                if (!is_numeric($subKey))
                                {
                                    if ($subItem != '')
                                    {
                                        $tmpString .= '<li><strong>' . ucwords(str_replace('_', ' ', (string) $subKey)) . '</strong>: ' . (string) $subItem . '</li>';
                                    }
                                }
                                else
                                {
                                    $tmpString .= '<li>' . (string) $subItem . '</li>';
                                }
                            }


                            if ($tmpString == '')
                            {
                                $item = '';
                            }
                            else
                            {
                                $item = '<ul>' . $tmpString . '</ul>';
                            }

                        }
						else
						{
							$item = '';
						}
                    }
                    else
                    {
                        $item = strip_tags((string) $item);

                        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])T(?:2[0-4]|[01][1-9]|10):([0-5][0-9])$/", $item))
                        {
                            $item = date('Y-m-d H:i:s', strtotime($item));
                        }
                    }

                    if ((string) $item != '')
                    {
                        $body .= '<p><strong>' . ucwords(str_replace('_', ' ', (string) $key)) . '</strong>: ' . (string) $item . '</p>';
                    }
                }
            }

            if ($body == '')
            {
                echo json_encode(array('success' => false, 'message' => 'The data is invalid!'));
                exit();
            }

            $mailer->setSender(array($fromEmail, $fromName));
            $mailer->setSubject(stripslashes($subject));
            $mailer->IsHtml(true);

            $mailer->setBody($body);
            $mailer->addRecipient($recipient);
            // Send the Mail
            $rs = $mailer->Send();

            // Check for an error
            if ($rs instanceof Exception)
            {
                echo json_encode(array('success' => false, 'message' => $rs->getMessage()));
                exit();
            }
            elseif (empty($rs))
            {
                echo json_encode(array('success' => false, 'message' => 'The mail could not be sent!'));
                exit();
            }
            else
            {
                echo json_encode(array('success' => true, 'message' => $customMessage));
                exit();
            }
        }

        echo json_encode(array('success' => false, 'message' => 'The data is invalid!'));
        exit();
    }

    protected function fetchPageBuilder3TemplateData()
    {
        //header('Content-Type: application/json');
        $this->authorizeRequest();

        $input  = JFactory::getApplication()->input;
        $url    = trim($input->getString('url', ''));

		if ($url == '')
		{
			echo json_encode(array('success' => false, 'message' => 'The data is invalid!'));
        	exit();
		}

        $result = JSNPageBuilder3ContentHelper::fetchHttp($url);

        echo $result;
        exit();

    }
}

// Fallback support for old pb3ajax=1.
if ($this->app->input->getInt('pb3ajax') === 1) {
    $ajax = new JSNPageBuilder3Ajax();
    $data = $ajax->handleRequest();
    if ($result = json_encode($data)) {
        echo $result;
    } else {
        echo json_encode($ajax->json_fix($data));
    }
    exit;
}

<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+ 
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 * 
 */
/**
 * Dummy Index Controller
 */ 
class Editor_IndexController extends ZfExtended_Controllers_Action {
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * @var Zend_Config
     */
    protected $config;
    
    public function init() {
        parent::init();
        $this->config = Zend_Registry::get('config');
    }
    
    /**
     * 
     This is to be able to start a worker as a developer indepently through the browser
     
     public function startworkerAction() {
     
        $this->_helper->viewRenderer->setNoRender();
        $taskGuid = $this->getParam('taskGuid');
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        
        // init worker and queue it
        if (!$worker->init($taskGuid, array('resourcePool' => 'import'))) {
            $this->log('TermTaggerImport-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue();
    }
    */
    public function indexAction() {
        $this->_helper->layout->disableLayout();
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->view->pathToIMAGES = APPLICATION_RUNDIR.$this->config->runtimeOptions->server->pathToIMAGES;
        $extJs = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
              'ExtJs'
          );
        /* @var $extJs ZfExtended_Controller_Helper_ExtJs */
        $this->view->extJsCss = $extJs->getCssPath();
        $this->view->extJsBasepath = $extJs->getHttpPath();
        $this->view->extJsVersion = $extJs->getVersion();
        
        $this->view->buildType = 'development';
        
        $this->view->publicModulePath = APPLICATION_RUNDIR.'/modules/'.Zend_Registry::get('module');
        $this->view->locale = $this->_session->locale;

        $css = $this->getAdditionalCss();
        foreach($css as $oneCss) {
          $this->view->headLink()->appendStylesheet(APPLICATION_RUNDIR."/".$oneCss);
        }

        $this->view->appVersion = $this->getAppVersion();
        $this->setJsVarsInView();
        $this->checkForUpdates();
        
        //$this->startTestCode();
    }
    
    public function startTestCode(){
        $taskGuid="{ed03996b-37fd-4ecd-b988-b118ccbe5069}";
        
        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        $analysisAssoc=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc->setTaskGuid($task->getTaskGuid());
        $analysisId=$analysisAssoc->save();
        
        $analysis=new editor_Plugins_MatchAnalysis_Analysis($task,$analysisId);
        /* @var $analysis editor_Plugins_MatchAnalysis_Analysis */
        
        
        $analysis->calculateMatchrate();
    }
    
    /**
     * Logs the users userAgent and screen size for usability improvements
     */
    public function logbrowsertypeAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        
        settype($_POST['appVersion'], 'string');
        settype($_POST['userAgent'], 'string');
        settype($_POST['browserName'], 'string');
        settype($_POST['maxWidth'], 'integer');
        settype($_POST['maxHeight'], 'integer');
        settype($_POST['usedWidth'], 'integer');
        settype($_POST['usedHeight'], 'integer');
        $userSession = new Zend_Session_Namespace('user');
        
        $log = ZfExtended_Factory::get('editor_Models_BrowserLog');
        /* @var $log editor_Models_BrowserLog */
        
        $log->setDatetime(NOW_ISO);
        $log->setLogin($userSession->data->login);
        $log->setUserGuid($userSession->data->userGuid);
        $log->setAppVersion($_POST['appVersion']);
        $log->setUserAgent($_POST['userAgent']);
        $log->setBrowserName($_POST['browserName']);
        $log->setMaxWidth($_POST['maxWidth']);
        $log->setMaxHeight($_POST['maxHeight']);
        $log->setUsedWidth($_POST['usedWidth']);
        $log->setUsedHeight($_POST['usedHeight']);
        
        $log->save();
    }
    
    protected function checkForUpdates() {
        $downloader = ZfExtended_Factory::get('ZfExtended_Models_Installer_Downloader', array(APPLICATION_PATH.'/..'));
        /* @var $downloader ZfExtended_Models_Installer_Downloader */
        
        $userSession = new Zend_Session_Namespace('user');
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        
        $isAllowed = $acl->isInAllowedRoles($userSession->data->roles,'getUpdateNotification');
        try {
            if(!$isAllowed || $downloader->applicationIsUptodate()) {
                return;
            }
            $msgBoxConf = $this->view->Php2JsVars()->get('messageBox');
            settype($msgBoxConf->initialMessages, 'array');
            $msg = 'Eine neue Version von Translate5 ist verfügbar. Bitte benutzen Sie das Installations und Update Script um die aktuellste Version zu installieren.';
            $msgBoxConf->initialMessages[] = $this->translate->_($msg);
        } catch (Exception $e) {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError('Latest translate5 version information could not be fetched!', (string) $e);
        }
    }

    /**
     * Gibt die zusätzlich konfigurierte CSS Dateien als Array zurück
     * @return array
     */
    protected function getAdditionalCss() {
        $config =Zend_Registry::get('config');
        if(empty($config->runtimeOptions->publicAdditions)){
            return array();
        }
        /* @var $css Zend_Config */
        $css = $config->runtimeOptions->publicAdditions->css;
        if(empty($css)) {
            return array();
        }
        if(is_string($css)){
            return array($css);
        }
        return $css->toArray();
    }
    
    protected function setJsVarsInView() {
        $rop = $this->config->runtimeOptions;
        
        $this->view->enableJsLogger = $rop->debug && $rop->debug->enableJsLogger;
        
        $restPath = APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/';
      $this->view->Php2JsVars()->set('restpath', $restPath);
      $this->view->Php2JsVars()->set('basePath', APPLICATION_RUNDIR);
      $this->view->Php2JsVars()->set('moduleFolder', $this->view->publicModulePath.'/');
      $this->view->Php2JsVars()->set('appFolder', $this->view->publicModulePath.'/js/app');
      $this->view->Php2JsVars()->set('pluginFolder', $restPath.'plugins/js');
      $extJs = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'ExtJs'
        );
      $this->view->Php2JsVars()->set('pathToHeaderFile', $rop->headerOptions->pathToHeaderFile);
      
      $disabledList = $rop->segments->disabledFields->toArray();
      $this->view->Php2JsVars()->create('segments.column');
      foreach($disabledList as $disabled){
        if(empty($disabled)){
          continue;
        }
        $this->view->Php2JsVars()->set('segments.column.'.$disabled.'.hidden', true);
      }
      
      $this->setJsSegmentFlags('segments.qualityFlags', $rop->segments->qualityFlags->toArray());
      $manualStates = $rop->segments->stateFlags->toArray();
      $manualStates[0] = $this->translate->_('Nicht gesetzt');
      $this->setJsSegmentFlags('segments.stateFlags', $manualStates);
      $this->view->Php2JsVars()->set('segments.showStatus', (boolean)$rop->segments->showStatus);
      $this->view->Php2JsVars()->set('segments.showQM', (boolean)$rop->segments->showQM);
      $this->view->Php2JsVars()->set('segments.userCanIgnoreTagValidation', (boolean)$rop->segments->userCanIgnoreTagValidation);
      $this->view->Php2JsVars()->set('segments.userCanModifyWhitespaceTags', (boolean)$rop->segments->userCanModifyWhitespaceTags);
      $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
      /* @var $states editor_Models_Segment_AutoStates */
      $this->setJsSegmentFlags('segments.autoStateFlags', $states->getLabelMap());
      $this->view->Php2JsVars()->set('segments.roleAutoStateMap', $states->getRoleToStateMap());

      $tagPath = APPLICATION_RUNDIR.'/'.$rop->dir->tagImagesBasePath.'/';
      $this->view->Php2JsVars()->set('segments.shortTagPath', $tagPath);
      $this->view->Php2JsVars()->set('segments.fullTagPath', $tagPath);
      $this->view->Php2JsVars()->set('segments.matchratetypes', []); //needed to give plugins the abilty to add own icons as matchrate types
      
      if($rop->editor->enableQmSubSegments) {
          $this->view->Php2JsVars()->set('segments.subSegment.tagPath', $tagPath);
      }
      $this->view->Php2JsVars()->set('enable100pEditWarning', (boolean) $rop->editor->enable100pEditWarning);
      
      $this->view->Php2JsVars()->set('preferences.alikeBehaviour', $rop->alike->defaultBehaviour);
      $this->view->Php2JsVars()->set('loginUrl', APPLICATION_RUNDIR.$rop->loginUrl);
      
      //inject helUrl variable used in frontend
      $this->view->Php2JsVars()->set('helpUrl',$rop->helpUrl);
      
      //maintenance start date
      if(isset($rop->maintenance->startDate)) {
          $startDate = date(DATE_ISO8601, strtotime($rop->maintenance->startDate));
      }
      else{
          $startDate = '';
      }
      $this->view->Php2JsVars()->set('maintenance.startDate',$startDate);
      //maintenance warning panel is showed
      $this->view->Php2JsVars()->set('maintenance.timeToNotify',isset($rop->maintenance->timeToNotify)?$rop->maintenance->timeToNotify:'');
      //minutes before the point in time of the update the application is locked for new log-ins
      $this->view->Php2JsVars()->set('maintenance.timeToLoginLock',isset($rop->maintenance->timeToLoginLock)?$rop->maintenance->timeToLoginLock:'');
      
      $this->view->Php2JsVars()->set('messageBox.delayFactor', $rop->messageBox->delayFactor);
      
      $this->view->Php2JsVars()->set('headerOptions.height', (int)$rop->headerOptions->height);
      $this->view->Php2JsVars()->set('languages', $this->getAvailableLanguages());
      $this->view->Php2JsVars()->set('translations', $this->translate->getAvailableTranslations());
      
      //Editor.data.enableSourceEditing → still needed for enabling / disabling the whole feature (Checkbox at Import).
      $this->view->Php2JsVars()->set('enableSourceEditing', (boolean) $rop->import->enableSourceEditing);
      
      $supportedFiles = ZfExtended_Factory::get('editor_Models_Import_SupportedFileTypes');
      /* @var $supportedFiles editor_Models_Import_SupportedFileTypes */
      $this->view->Php2JsVars()->set('import.validExtensions', array_keys($supportedFiles->getSupportedTypes()));
      
      $this->view->Php2JsVars()->set('columns.widthFactorHeader', (float)$rop->editor->columns->widthFactorHeader);
      $this->view->Php2JsVars()->set('columns.widthOffsetEditable', (integer)$rop->editor->columns->widthOffsetEditable);
      $this->view->Php2JsVars()->set('columns.widthFactorErgonomic', (float)$rop->editor->columns->widthFactorErgonomic);
      $this->view->Php2JsVars()->set('columns.maxWidth', (integer)$rop->editor->columns->maxWidth);
      
      $this->view->Php2JsVars()->set('browserAdvice', $rop->browserAdvice);
      if($rop->showSupportedBrowsersMsg) {
          $this->view->Php2JsVars()->set('supportedBrowsers', $rop->supportedBrowsers->toArray());
      }
      
      //default state configuration for frontend components(grid)
      $this->view->Php2JsVars()->set('frontend.defaultState', $rop->frontend->defaultState->toArray());
      
      $this->view->Php2JsVars()->set('frontend.importTask.fieldsDefaultValue', $rop->frontend->importTask->fieldsDefaultValue->toArray());
      
      //flag if the segment count status strip component should be displayed
      $this->view->Php2JsVars()->set('segments.enableCountSegmentLength', (boolean)$rop->segments->enableCountSegmentLength);
      
      $this->setJsAppData();
    }

    /**
     * Set the several data needed vor authentication / user handling in frontend
     */
    protected function setJsAppData() {
        $userSession = new Zend_Session_Namespace('user');
        $userSession->data->passwd = '********';
        $userRoles = $userSession->data->roles;
        
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
                
        $workflow = ZfExtended_Factory::get('editor_Workflow_Default');
        /* @var $workflow editor_Workflow_Default */
        
        $ed = $this->config->runtimeOptions->editor;
        
        $php2js = $this->view->Php2JsVars();
        $php2js->set('app.controllers', $this->getFrontendControllers());
        
        if(empty($this->_session->taskGuid)) {
            $php2js->set('app.initMethod', 'openAdministration');
        }
        else {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            //FIXME TRANSLATE-55 if a taskguid remains in the session, 
            //the user will be caught in a zend 404 Screen instead of getting the adminpanel.
            $task->loadByTaskGuid($this->_session->taskGuid);
            $taskData = $task->getDataObject();
            unset($taskData->qmSubsegmentFlags);
            
            $php2js->set('task', $taskData);
            $openState = $this->_session->taskOpenState ? 
                    $this->_session->taskOpenState : 
                    $workflow::STATE_WAITING; //in doubt read only
            $php2js->set('app.initState', $openState);
            $php2js->set('app.initMethod', 'openEditor');
        }
         
        $php2js->set('app.viewport', $ed->editorViewPort);
        $php2js->set('app.startViewMode', $ed->startViewMode);
        $php2js->set('app.branding', (string) $this->translate->_($ed->branding));
        $php2js->set('app.user', $userSession->data);
        
        $allRoles = $acl->getRoles();
        $roles = array();
        foreach($allRoles as $role) {
            if($role == 'noRights' || $role == 'basic') {
                continue;
            }
            //set the setable, if the user is able to set/modify this role
            $roles[$role] = [
                    'label' => $this->translate->_(ucfirst($role)),
                    'setable' => $acl->isInAllowedRoles($userRoles, "setaclrole", $role)
            ];
        }
        $php2js->set('app.roles', $roles);
        
        $wm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wm editor_Workflow_Manager */
        $php2js->set('app.workflows', $wm->getWorkflowData());
        
        $php2js->set('app.userRights', $acl->getFrontendRights($userRoles));
        
        $php2js->set('app.version', $this->view->appVersion);
    }
    
    protected function getAppVersion() {
        return ZfExtended_Utils::getAppVersion();
    }
    
    /**
     * returns a list with used JS frontend controllers
     * @return array
     */
    protected function getFrontendControllers() {
        $userSession = new Zend_Session_Namespace('user');
        
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        
        $ed = $this->config->runtimeOptions->editor;
        
        $controllers = array('ServerException', 'ViewModes', 'Segments', 
            'Preferences', 'MetaPanel', 'Editor', 'Fileorder',
            'ChangeAlike', 'Comments','SearchReplace','Termportal');
        
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $pluginFrontendControllers = $pm->getActiveFrontendControllers();
        if(!empty($pluginFrontendControllers)) {
            $controllers = array_merge($controllers, $pluginFrontendControllers);
        }
        
        if($acl->isInAllowedRoles($userSession->data->roles,'headPanelFrontendController')){
            $controllers[] = 'HeadPanel';
        }
        if($acl->isInAllowedRoles($userSession->data->roles,'userPrefFrontendController')){
            $controllers[] = 'UserPreferences';
        }
        
        if($ed->enableQmSubSegments){
            $controllers[] = 'QmSubSegments';
        }
        if($acl->isInAllowedRoles($userSession->data->roles,'taskOverviewFrontendController')){
            $controllers[] = 'admin.TaskOverview';
            $controllers[] = 'admin.TaskPreferences'; //FIXME add a own role?
        }
        if($acl->isInAllowedRoles($userSession->data->roles,'adminUserFrontendController')){
            $controllers[] = 'admin.TaskUserAssoc';
            $controllers[] = 'admin.User';
        }
        
        if($acl->isInAllowedRoles($userSession->data->roles,'frontend','customerAdministration')){
            $controllers[] = 'admin.Customer';
        }

        //Localizer must be the last one!
        $controllers[] = 'Localizer';
        return $controllers;
    }
    
    /**
     * Returns all configured languages in an array for displaying in frontend
     */
    protected function getAvailableLanguages() {
        /* @var $langs editor_Models_Languages */
        $langs = ZfExtended_Factory::get('editor_Models_Languages');
        $langs = $langs->loadAll();
        $result = array();
        foreach ($langs as $lang) {
            $name = $this->translate->_($lang['langName']);
            $result[$name] = array($lang['id'], $name.' ('.$lang['rfc5646'].')', $lang['rtl'],$lang['rfc5646']);
        }
        ksort($result); //sort by name of language
        if(empty($result)){
            throw new Zend_Exception('No languages defined. Please use /docs/003fill-LEK-languages-after-editor-sql or define them otherwhise.');
        }
        return array_values($result);
    }
    
    protected function setJsSegmentFlags($type, array $qualityFlags) {
      $result = array();
      foreach($qualityFlags as $key => $value){
        if(empty($value)){
          continue;
        }
        $flag = new stdClass();
        $flag->id = $key;
        $flag->label = $this->translate->_($value);
        $result[] = $flag;
      }
      
      $this->view->Php2JsVars()->set($type, $result);
    }
    
    public function applicationstateAction() {
        $this->_helper->layout->disableLayout();
        $this->view->applicationstate = ZfExtended_Debug::applicationState();
    }
    
    public function generatesmalltagsAction() {
      set_time_limit(0);
      $path = array(APPLICATION_PATH, '..', 'public', 
          $this->config->runtimeOptions->dir->tagImagesBasePath.'/');
      $path = join(DIRECTORY_SEPARATOR, $path);

      /* @var $single ImageTag_Single */
      $single = ZfExtended_Factory::get('editor_ImageTag_Single');
      $single->setSaveBasePath($path);
      
      $singleLocked = ZfExtended_Factory::get('editor_ImageTag_Single');
      /* @var $singleLocked ImageTag_SingleLocked */
      $singleLocked->setSaveBasePath($path);
      
      /* @var $left ImageTag_Left */
      $left = ZfExtended_Factory::get('editor_ImageTag_Left');
      $left->setSaveBasePath($path);
      
      /* @var $right ImageTag_Right */
      $right = ZfExtended_Factory::get('editor_ImageTag_Right');
      $right->setSaveBasePath($path);
      
      for($i = 1; $i <= 100; $i++) {
        $single->create('<'.$i.'/>');
        $singleLocked->create('<locked'.$i.'/>');
        $left->create('<'.$i.'>');
        $right->create('</'.$i.'>');
        
        $single->save($i);
        $singleLocked->save('locked'.$i);
        $left->save($i);
        $right->save($i);
      }
      
      exit;
    }
    
    public function generateqmsubsegmenttagsAction() {
      set_time_limit(0);
      $path = array(APPLICATION_PATH, '..', 'public', 
          $this->config->runtimeOptions->dir->tagImagesBasePath.'/');
      $path = join(DIRECTORY_SEPARATOR, $path);

      /* @var $left editor_ImageTag_QmSubSegmentLeft */
      $left = ZfExtended_Factory::get('editor_ImageTag_QmSubSegmentLeft');
      $left->setSaveBasePath($path);
      
      /* @var $right editor_ImageTag_QmSubSegmentRight */
      $right = ZfExtended_Factory::get('editor_ImageTag_QmSubSegmentRight');
      $right->setSaveBasePath($path);
      
      for($i = 1; $i < 120; $i++) {
        $left->create('[ '.$i);
        $right->create($i.' ]');
        $left->save('qmsubsegment-'.$i);
        $right->save('qmsubsegment-'.$i);
      }
      
      exit;
    }
    
    public function localizedjsstringsAction() {
      $this->getResponse()->setHeader('Content-Type', 'text/javascript', TRUE);
      
      $this->view->frontendControllers = $this->getFrontendControllers();
      
      $this->view->appViewport = $this->config->runtimeOptions->editor->initialViewPort;
      $this->_helper->layout->disableLayout();
    }
    
    public function wdhehelpAction() {
        $this->_helper->layout->disableLayout();
        //$this->_helper->viewRenderer->setNoRender();
    }

    /**
     * To prevent LFI attacks load existing Plugin JS filenames and use them as whitelist
     * Currently this Method is not reusable, its only for JS.
     */
    public function pluginpublicAction() {
        $types = array(
                'js' => 'text/javascript',
                'css' => 'text/css',
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'svg' => 'image/svg',
                'woff' => 'application/woff',
                'woff2' => 'application/woff2',
                'ttf' => 'application/ttf',
                'eot' => 'application/eot',
                'html'=> 'text/html'
        );
        $slash = '/';
        // get requested file from router
        $requestedType =$this->getParam(1);
        $requestedFile =$this->getParam(2);
        $js = explode($slash, $requestedFile);
        $extension = pathinfo($requestedFile, PATHINFO_EXTENSION);
        
        //pluginname is alpha characters only so check this for security reasons
        //ucfirst is needed, since in JS packages start per convention with lowercase, Plugins in PHP with uppercase! 
        $plugin = ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', array_shift($js)));
        if(empty($plugin)) {
            throw new ZfExtended_NotFoundException();
        }
        
        //get the plugin instance to the key
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $plugin = $pm->get($plugin);
        /* @var $plugin ZfExtended_Plugin_Abstract */
        if(empty($plugin)) {
            throw new ZfExtended_NotFoundException();
        }
        
        // check if requested "fileType" is allowed
        if (!$plugin->isPublicFileType($requestedType)) {
            throw new ZfExtended_NotFoundException();
        }

        //get public files of the plugin to make a whitelist check of the file string from userland
        $allowedFiles = $plugin->getPublicFiles($requestedType, $absolutePath);
        $file = join($slash, $js);
        if(!$allowedFiles){
            return;
        }
        if(!in_array($file, $allowedFiles)) {
            throw new ZfExtended_NotFoundException();
        }
        //concat the absPath from above with filepath
        $wholePath = $absolutePath.'/'.$file;
        if(!file_exists($wholePath)){
            throw new ZfExtended_NotFoundException();
        }
        //currently this method is fixed to JS:
        header('Content-Type: '.$types[$extension]);
        readfile($wholePath);
        exit;
    }
    
    public function testnotifyAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        
        $class = 'editor_Workflow_Notification';
        
        
        $config = ZfExtended_Factory::get('editor_Workflow_Actions_Config');
        /* @var $config editor_Workflow_Actions_Config */
        $config->workflow = ZfExtended_Factory::get('editor_Workflow_Default');
        $config->newTua = null;
        $config->oldTask = ZfExtended_Factory::get('editor_Models_Task');
        $config->oldTask->init([
            'taskGuid' => '{97789a10-0bbb-4de5-b4b0-c5caceba3b25}',
            'taskNr' => '',
            'taskName' => 'Test Task',
            'sourceLang' => 5,
            'targetLang' => 4,
            'relaisLang' => 4,
            'state' => 'open',
            'workflow' => 'default',
            'workflowStep' => '1',
            'workflowStepName' => 'lectoring',
            'pmGuid' => '{dab18309-7dfd-4185-b27e-f490c3dcb888}',
            'pmName' => 'PM Username',
            'wordCount' => '123',
            'targetDeliveryDate' => '2017-12-21 00:00:00',
            'realDeliveryDate' => null,
            'orderdate' => '2017-12-20 00:00:00',
        ]);
        $config->task = $config->oldTask;
        $config->importConfig = new editor_Models_Import_Configuration();
        $config->importConfig->importFolder = APPLICATION_PATH.'/needed/';
        $config->importConfig->setLanguages('de', 'it', '', ZfExtended_Languages::LANG_TYPE_RFC5646);
        $config->importConfig->userGuid = '{F1D11C25-45D2-11D0-B0E2-444553540101}';
        $config->importConfig->userName = 'Thomas Lauria';
        
        $notify = ZfExtended_Factory::get($class);
        /* @var $notify editor_Workflow_Notification */
        $notify->init($config);
        $notify->testNotifications();
        echo "Sent dummy test Mails";
        exit;
    }
}


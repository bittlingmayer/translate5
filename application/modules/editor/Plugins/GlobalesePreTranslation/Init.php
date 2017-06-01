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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Initial Class of Plugin "GlobalesePreTranslation"
 */
class editor_Plugins_GlobalesePreTranslation_Init extends ZfExtended_Plugin_Abstract {
    
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
            'pluginGlobalesePreTranslationGlobalese' => 'Editor.plugins.GlobalesePreTranslation.controller.Globalese'
    );
            
    protected $localePath = 'locales';
            
    public function init() {
        if(ZfExtended_Debug::hasLevel('plugin', 'GlobalesePreTranslation')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
        $this->log = ZfExtended_Factory::get('ZfExtended_Log', array(false));
        $this->initEvents();
        $this->initRoutes();
    }
    
    
    public function getFrontendControllers() {
        return $this->getFrontendControllersFromAcl();
    }
    
    protected function initEvents() {
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        $this->eventManager->attach('editor_TaskController', 'afterPostAction', array($this, 'handleAfterTaskControllerPostAction'),10);
    }
    
    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $f->addControllerDirectory(APPLICATION_PATH.'/'.$this->getPluginPath().'/Controllers', '_plugins_'.__CLASS__);
        $r = $f->getRouter();
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
                'editor' => array('plugins_globalesepretranslation_globalese',),
        ));
        $r->addRoute('plugins_globalesepretranslation_restdefault', $restRoute);
        
        $groupsRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_globalesepretranslation_globalese/groups',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_globalesepretranslation_globalese',
                        'action' => 'groups'
                ));
        $r->addRoute('plugins_globalesepretranslation_groups', $groupsRoute);
        
        
        $enginesRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_globalesepretranslation_globalese/engines',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_globalesepretranslation_globalese',
                        'action' => 'engines'
                ));
        $r->addRoute('plugins_globalesepretranslation_engines', $enginesRoute);
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        $view->Php2JsVars()->set('plugins.GlobalesePreTranslation.api.username', $this->getConfig()->api->username);
        $view->Php2JsVars()->set('plugins.GlobalesePreTranslation.api.password', $this->getConfig()->api->password);
        $view->Php2JsVars()->set('plugins.GlobalesePreTranslation.api.apiKey', $this->getConfig()->api->apiKey);
    }
    
    
    public function handleAfterTaskControllerPostAction(Zend_EventManager_Event $event) {
        $worker = ZfExtended_Factory::get('editor_Plugins_GlobalesePreTranslation_Worker');
        /* @var $worker editor_Plugins_GlobalesePreTranslation_Worker */
        
        $globaleseSession = new Zend_Session_Namespace('GlobalesePreTranslation');
        
        //send the parametars from the session in the workier init method parametar
        $sessionParametars = null;
        
        $task = $event->getParam('entity');
        
        $workerDb = new ZfExtended_Models_Db_Worker();
        $row = $workerDb->fetchRow([
                'taskGuid = ?' => $task->getTaskGuid(),
                'worker = ?' => 'editor_Models_Import_Worker',
        ]);
        
        $parentWorkerId = $row->id;
        $params=[
                'group'=>$globaleseSession->group,
                'engine'=>$globaleseSession->engine,
                'apiUsername'=>$globaleseSession->apiUsername,
                'apiKey'=>$globaleseSession->apiKey,
        ];
        
        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), $params)) {
            $this->log->logError('GlobalesePreTranslation-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue($parentWorkerId);
    }
}
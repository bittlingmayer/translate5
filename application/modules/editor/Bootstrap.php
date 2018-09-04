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
 * Klasse zur Portalinitialisierung
 *
 * - In initApplication können Dinge zur Portalinitialisierung aufgerufen werden
 * - Alles für das Portal nötige ist jedoch in Resource-Plugins ausgelagert und
 *   wird über die application.ini definiert und dann über Zend_Application
 *   automatisch initialisert
 *
 */
class Editor_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected $front;

    public function __construct($application) {
        require_once APPLICATION_PATH . '/../library/querypath/src/qp.php';
        parent::__construct($application);
        
        //Binding the worker clean up to the after import event, since import
        // is currently the main use case for workers
        $eventManager = Zend_EventManager_StaticEventManager::getInstance();
        /* @var $eventManager Zend_EventManager_StaticEventManager */
        
        $eventManager->attach('editor_Models_Import', 'afterImport', function(){
            $worker = ZfExtended_Factory::get('ZfExtended_Worker_GarbageCleaner');
            /* @var $worker ZfExtended_Worker_GarbageCleaner */
            $worker->init();
            $worker->queue(); // not parent ID here, since the GarbageCleaner should run without a parent relation
        }, 0);
        
        $cleanUp = function(){
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->cleanupLockedJobs();
            
            $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
            /* @var $tua editor_Models_TaskUserAssoc */
            $tua->cleanupLocked();
        };
        
        $eventManager->attach('ZfExtended_Resource_GarbageCollector', 'cleanUp', $cleanUp);
        $eventManager->attach('LoginController', 'afterLogoutAction', $cleanUp);
        $eventManager->attach('editor_SessionController', 'afterDeleteAction', $cleanUp);
    }
    
    
    public function _initController()
    {
        $this->front = Zend_Controller_Front::getInstance();
    }
    
    
    public function _initREST()
    {
        $this->front->setRequest(new REST_Controller_Request_Http);

        // register the RestHandler plugin
        $this->front->registerPlugin(new ZfExtended_Controllers_Plugins_RegisterRestControllerPluginRestHandler());

        // add REST contextSwitch helper
        $contextSwitch = new REST_Controller_Action_Helper_ContextSwitch();
        Zend_Controller_Action_HelperBroker::addHelper($contextSwitch);

        // add restContexts helper
        $restContexts = new REST_Controller_Action_Helper_RestContexts();
        Zend_Controller_Action_HelperBroker::addHelper($restContexts);
    }
    
    
    public function _initRestRoutes()
    {
        
        $restRoute = new Zend_Rest_Route($this->front, array(), array(
            'editor' => array(  'file', 'segment', 'alikesegment', 'customer', 'referencefile', 'qmstatistics', 'comment',
                                'task', 'user', 'taskuserassoc', 'segmentfield', 'workflowuserpref', 'worker','taskmeta',
                                'config', 'segmentuserassoc', 'session', 'language','termcollection','resource','taskassoc',
                                'tmmt','instanttranslate'),
        ));
        $this->front->getRouter()->addRoute('editorRestDefault', $restRoute);

        //this is not standard controller action route
        //when this route is triggered, a coresponding event from the given controller will be fired
        //ex: editor/task/123/analysis/operation
        //    - an event called analysisOperation will be fired from task controller(task entity with id 123)
        $this->front->getRouter()->addRoute('editorOperationHandler', new ZfExtended_Controller_RestLikeRoute(
            //'editor/:entity/:id/:operation/operation',
            'editor/:controller/:id/:operation/operation',
            array(
                'module' => 'editor',
                'action' => '',
            )
        ));
        
        //FIXME convert to RestLikeRoute (remove echo json_encode in action then)
        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/filemap/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'filemap'
            ));
        $this->front->getRouter()->addRoute('editorFilemap', $filemapRoute);
        
        //must be added before the default RestRoutes 
        $this->front->getRouter()->addRoute('editorSegmentPosition', new ZfExtended_Controller_RestLikeRoute(
            'editor/segment/:segmentNrInTask/position',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'position'
            )));
        
        $this->front->getRouter()->addRoute('editorTaskTriggerWorkflow', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/workflow',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'workflow'
            )
        ));
        
        $this->front->getRouter()->addRoute('editorTaskClone', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/clone',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'clone'
            )
        ));
        
        $this->front->getRouter()->addRoute('editorTaskImport', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/import',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'import'
            )
        ));
        
        //FIXME convert me to RestLikeRoute (see filemap)
        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/nextsegments/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'nextsegments'
            ));
        $this->front->getRouter()->addRoute('editorNextSegments', $filemapRoute);

        //FIXME convert me to RestLikeRoute (see filemap)
        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/matchratetypes/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'matchratetypes'
            ));
        $this->front->getRouter()->addRoute('editorMatchratetypes', $filemapRoute);

        $searchRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/segment/search/*',
                array(
                        'module' => 'editor',
                        'controller' => 'segment',
                        'action' => 'search'
                ));
        $this->front->getRouter()->addRoute('editorSearchSegment', $searchRoute);
        
        $replaceAllRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/segment/replaceall/*',
                array(
                        'module' => 'editor',
                        'controller' => 'segment',
                        'action' => 'replaceall'
                ));
        $this->front->getRouter()->addRoute('editorReplaceallSegment', $replaceAllRoute);

        $authUserRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/user/authenticated/*',
            array(
                'module' => 'editor',
                'controller' => 'user',
                'action' => 'authenticated'
            ));
        $this->front->getRouter()->addRoute('editorAuthUser', $authUserRoute);

        $pmRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/user/pm/*',
                array(
                        'module' => 'editor',
                        'controller' => 'user',
                        'action' => 'pm'
                ));
        $this->front->getRouter()->addRoute('editorUserPm', $pmRoute);
        
        $termsRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/terms/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'terms'
            ));
        $this->front->getRouter()->addRoute('editorTerms', $termsRoute);

        $exportRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/task/export/*',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'export'
            ));
        $this->front->getRouter()->addRoute('editorExport', $exportRoute);
        
        $taskStat = new ZfExtended_Controller_RestLikeRoute(
            'editor/task/statistics/*',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'statistics'
            ));
        $this->front->getRouter()->addRoute('editorTaskStat', $taskStat);
        
        $workerRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/worker/queue/*',
            array(
                'module' => 'editor',
                'controller' => 'worker',
                'action' => 'queue'
            ));
        $this->front->getRouter()->addRoute('editorQueue', $workerRoute);
        
        $termCollectionImport = new ZfExtended_Controller_RestLikeRoute(
                'editor/termcollection/import/*',
                array(
                        'module' => 'editor',
                        'controller' => 'termcollection',
                        'action' => 'import'
                ));
        $this->front->getRouter()->addRoute('termCollectionImport', $termCollectionImport);
        
        $termCollectionExport = new ZfExtended_Controller_RestLikeRoute(
                'editor/termcollection/export/*',
                array(
                        'module' => 'editor',
                        'controller' => 'termcollection',
                        'action' => 'export'
                ));
        $this->front->getRouter()->addRoute('termCollectionExport', $termCollectionExport);
        
        
        $getCollectionAttributes= new ZfExtended_Controller_RestLikeRoute(
                'editor/termcollection/testgetattributes/*',
                array(
                        'module' => 'editor',
                        'controller' => 'termcollection',
                        'action' => 'testgetattributes'
                ));
        $this->front->getRouter()->addRoute('testgetattributes', $getCollectionAttributes);
        
        $searchTermCollection= new ZfExtended_Controller_RestLikeRoute(
                'editor/termcollection/search/*',
                array(
                        'module' => 'editor',
                        'controller' => 'termcollection',
                        'action' => 'search'
                ));
        $this->front->getRouter()->addRoute('searchtermcollection', $searchTermCollection);
        
        $searchAttributeTermCollection= new ZfExtended_Controller_RestLikeRoute(
                'editor/termcollection/searchattribute/*',
                array(
                        'module' => 'editor',
                        'controller' => 'termcollection',
                        'action' => 'searchattribute'
                ));
        $this->front->getRouter()->addRoute('searchattributetermcollection', $searchAttributeTermCollection);
        
        # Language resources rutes start
        //WARNING: Order of the route definition is important!
        // the catchall like download route must be defined before the more specific query/search routes!
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/tmmt/:id/:type',
            array(
                'module' => 'editor',
                'controller' => 'tmmt',
                'action' => 'download'
            ));
        $this->front->getRouter()->addRoute('languageresources_tmmt_download', $queryRoute);

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/tmmt/:tmmtId/query',
            array(
                'module' => 'editor',
                'controller' => 'tmmt',
                'action' => 'query'
            ));
        $this->front->getRouter()->addRoute('languageresources_tmmt_query', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/tmmt/:tmmtId/search',
            array(
                'module' => 'editor',
                'controller' => 'tmmt',
                'action' => 'search'
            ));
        $this->front->getRouter()->addRoute('languageresources_tmmt_search', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/tmmt/:id/import',
            array(
                'module' => 'editor',
                'controller' => 'tmmt',
                'action' => 'import'
            ));
        $this->front->getRouter()->addRoute('languageresources_tmmt_import', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/tmmt/:id/tasks',
            array(
                'module' => 'editor',
                'controller' => 'tmmt',
                'action' => 'tasks'
            ));
        $this->front->getRouter()->addRoute('languageresources_tmmt_tasks', $queryRoute);
        #Language resource rutes end
        
        $sdlTranslateCloud= new ZfExtended_Controller_RestLikeRoute(
            'editor/instanttranslate/translate/*',
            array(
                'module' => 'editor',
                'controller' => 'instanttranslate',
                'action' => 'translate'
            ));
        $this->front->getRouter()->addRoute('instanttranslate_translate', $sdlTranslateCloud);
    }
    
    
    public function _initOtherRoutes()
    {
        $localizedJsRoute = new Zend_Controller_Router_Route(
            'editor/js/app-localized.js',
            array(
                'module' => 'editor',
                'controller' => 'index',
                'action' => 'localizedjsstrings',
            ));
        $this->front->getRouter()->addRoute('editorLocalizedJs', $localizedJsRoute);
        
        $pluginJs = new Zend_Controller_Router_Route_Regex(
            'editor/plugins/([^/]+)/([a-z0-9_\-./]*)',
            array(
                'module' => 'editor',
                'controller' => 'index',
                'action' => 'pluginpublic'
            ));
        $this->front->getRouter()->addRoute('editorPluginPublic', $pluginJs);
    }
}
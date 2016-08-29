<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Initial Class of Plugin "TermTagger"
 */
class editor_Plugins_TermTagger_Bootstrap extends ZfExtended_Plugin_Abstract {
    /**
     * @var ZfExtended_Log
     */
    protected $log;
    
    /**
     * Fieldname of the source-field of this task
     * @var string
     */
    private $sourceFieldName = '';
    
    /**
     * Fieldname of the source-field of this task if the task is editable
     * @var string
     */
    private $sourceFieldNameOriginal = '';
    
    /**
     * @var editor_Plugins_TermTagger_RecalcTransFound
     */
    private $markTransFound = null;
    
    public function init() {
        $this->log = ZfExtended_Factory::get('ZfExtended_Log', array(false));

        if(!$this->assertConfig()) {
            return false;
        }
        
        // event-listeners
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterTaskImport'),100);
        $this->eventManager->attach('editor_Models_Import_MetaData', 'importMetaData', array($this, 'handleImportMeta'));
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'handleAfterIndex'));
        $this->eventManager->attach('editor_Workflow_Default', array('doView', 'doEdit'), array($this, 'handleAfterTaskOpen'));
        $this->eventManager->attach('Editor_SegmentController', 'beforePutSave', array($this, 'handleBeforePutSave'));
        $this->eventManager->attach('Editor_IndexController', 'afterApplicationstateAction', array($this, 'termtaggerStateHandler'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'beforeSaveAlike', array($this, 'handleBeforeSaveAlike'));
    }
    
    /**
     * Invokes to the meta file parsing of task, adds TBX parsing
     * @param Zend_EventManager_Event $event
     */
    public function handleImportMeta(Zend_EventManager_Event $event) {
        $meta = $event->getParam('metaImporter');
        /* @var $meta editor_Models_Import_MetaData */
        $importer = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        $meta->addImporter($importer);
    }
    
    protected function assertConfig() {
        $config = Zend_Registry::get('config');
        $c = $config->runtimeOptions->termTagger->url;

        if (!isset($c->default) || !isset($c->import) || !isset($c->gui)) {
            $this->log->logError('Plugin TermTagger URL config default, import or gui not defined',
                                 'One of the required config-settings default, import or gui under runtimeOptions.termTagger.url is not defined in configuration.');
            return false;
        }
        
        $defaultUrl = $c->default->toArray();
        if (empty($defaultUrl)) {
            $this->log->logError('Plugin TermTagger config not set',
                                 'The required config-setting runtimeOptions.termTagger.url.default is not set in configuration. Value is empty');
            return false;
        }
        return true;
    }
    
    public function handleAfterTaskImport(Zend_EventManager_Event $event) {
        $config = Zend_Registry::get('config');
        $c = $config->runtimeOptions->termTagger->switchOn->import;
        if((boolean)$c === false)
            return;
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        if (!$task->getTerminologie()) {
            return;
        }
        
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
        
        // Create segments_meta-field 'termtagState' if not exists
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        $meta->addMeta('termtagState', $meta::META_TYPE_STRING, $worker::SEGMENT_STATE_UNTAGGED, 'Contains the TermTagger-state for this segment while importing', 36);
        
        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), array('resourcePool' => 'import'))) {
            $this->log->logError('TermTaggerImport-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue($event->getParam('parentWorkerId'));
    }
    
    /**
     * handler for event: Editor_IndexController#afterIndexAction
     * 
     * Writes runtimeOptions.termTagger.segmentsPerCall for use in ExtJS
     * into JsVar Editor.data.plugins.termTagger.segmentsPerCall
     * 
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterIndex(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        
        $config = Zend_Registry::get('config');
        $termTaggerSegmentsPerCall = $config->runtimeOptions->termTagger->segmentsPerCall;
        
        $view->Php2JsVars()->set('plugins.termTagger.segmentsPerCall', $termTaggerSegmentsPerCall);
    }
    
    /**
     * handler for event(s): editor_Workflow_Default#[doView, doEdit]
     * 
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterTaskOpen(Zend_EventManager_Event $event) {
    }
    
    
    /**
     * Re-TermTag the (modified) segment-text.
     */
    public function handleBeforePutSave(Zend_EventManager_Event $event) {
        $config = Zend_Registry::get('config');
        $c = $config->runtimeOptions->termTagger->switchOn->GUI;
        if((boolean)$c === false) {
            return;
        }
        
        $segment = $event->getParam('entity');
        /* @var $segment editor_Models_Segment */
        $taskGuid = $segment->getTaskGuid();
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        // stop if task has no terminologie
        if (!$task->getTerminologie()||!$segment->isDataModified()) {
            return;
        }

        $serverCommunication = $this->fillServerCommunication($task, $segment);
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTagger');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTagger */
        if (!$worker->init($taskGuid, array('serverCommunication' => $serverCommunication, 'resourcePool' => 'gui'))) {
            $this->log->logError('TermTagger-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        
        if (!$worker->run()) {
            $messages = Zend_Registry::get('rest_messages');
            /* @var $messages ZfExtended_Models_Messages */
            $messages->addError('Termini des zuletzt bearbeiteten Segments konnten nicht ausgezeichnet werden.');
            return false;
        }
        $results = $worker->getResult();
        $sourceTextTagged = false;
        foreach ($results as $result) {
            if ($result->field == 'SourceOriginal') {
                $segment->set($this->sourceFieldNameOriginal, $result->source);
                continue;
            }
            
            if (!$sourceTextTagged) {
                $segment->set($this->sourceFieldName, $result->source);
                $sourceTextTagged = true;
            }
            
            $segment->set($result->field, $result->target);
        }
        
        return true;
    }
    
    /**
     * inclusive all fields of the provided $segment
     * Creates a ServerCommunication-Object initialized with $task
     * 
     * @param editor_Models_Task $task
     * @param editor_Models_Segment $segment
     * @return editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private function fillServerCommunication (editor_Models_Task $task, editor_Models_Segment $segment) {
        
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication', array($task));
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($task->getTaskGuid());
        
        $this->sourceFieldName = $fieldManager->getFirstSourceName();
        $sourceText = $segment->get($this->sourceFieldName);
        
        if ($task->getEnableSourceEditing()) {
            $this->sourceFieldNameOriginal = $this->sourceFieldName;
            $sourceTextOriginal = $sourceText;
            $this->sourceFieldName = $fieldManager->getEditIndex($fieldManager->getFirstSourceName());
            $sourceText = $segment->get($this->sourceFieldName);
        }
        
        $fields = $fieldManager->getFieldList();
        $firstField = true;
        foreach ($fields as $field) {
            if($field->type != editor_Models_SegmentField::TYPE_TARGET || !$field->editable) {
                continue;
            }
            
            $targetFieldName = $fieldManager->getEditIndex($field->name);
            
            // if source is editable compare original Source with first targetField
            if ($firstField && $task->getEnableSourceEditing()) {
                $serverCommunication->addSegment($segment->getId(), 'SourceOriginal', $sourceTextOriginal, $segment->get($targetFieldName));
                $firstField = false;
            }
            
            $serverCommunication->addSegment($segment->getId(), $targetFieldName, $sourceText, $segment->get($targetFieldName));
        }
        
        return $serverCommunication;
    }
    
    public function termtaggerStateHandler(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->applicationstate->termtagger = $this->termtaggerState();
    }
    
    public function termtaggerState() {
        $termtagger = new stdClass();
        $ttService = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $ttService editor_Plugins_TermTagger_Service */
        $termtagger->configured = $ttService->getConfiguredUrls();
        $allUrls = array_unique(call_user_func_array('array_merge', (array)$termtagger->configured));
        $running = array();
        $version = array();
        $termtagger->runningAll = true;
        foreach($allUrls as $url) {
            $running[$url] = $ttService->testServerUrl($url, $version[$url]);
            $termtagger->runningAll = $running[$url] && $termtagger->runningAll;
        }
        $termtagger->running = $running;
        $termtagger->version = $version;
        return $termtagger;
    }
    
    /**
     * When using change alikes, the transFound information in the source has to be changed.
     * This is done by this handler.
     * 
     * @param Zend_EventManager_Event $event
     */
    public function handleBeforeSaveAlike(Zend_EventManager_Event $event) {
        $isSourceEditable = (boolean) $event->getParam('isSourceEditable');
        $masterSegment = $event->getParam('masterSegment');
        /* @var $masterSegment editor_Models_Segment */
        $alikeSegment = $event->getParam('alikeSegment');
        /* @var $alikeSegment editor_Models_Segment */
        
        // take over source original only for non editing source, see therefore TRANSLATE-549
        // Attention for alikes and if source is editable:
        //   - the whole content (including term trans[Not]Found info) must be changed in the editable field, 
        //     this is done in the AlikeController
        //   - in the original only the transFound infor has to be updated, this is done here
        
        //lazy instanciation of markTransFound
        if(empty($this->markTransFound)) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($masterSegment->getTaskGuid());
            $this->markTransFound = ZfExtended_Factory::get('editor_Plugins_TermTagger_RecalcTransFound', array($task));
        }
        $sourceOrig = $alikeSegment->getSource();
        $targetEdit = $alikeSegment->getTargetEdit();
        $alikeSegment->setSource($this->markTransFound->recalc($sourceOrig, $targetEdit));
    }
}

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
 * editor_Plugins_TermTagger_Worker_TermTaggerImport Class
 */
class editor_Plugins_TermTagger_Worker_TermTaggerImport extends editor_Plugins_TermTagger_Worker_Abstract {
    /**
     * 
     * @var editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private $serverCommunication = NULL;
    
    /**
     * Fieldname of the source-field of this task
     * @var string
     */
    private $sourceFieldName = '';
        
    
    /**
     * Flag to use the target original field for tagging instead edited
     * @var boolean
     */
    private $useTargetOriginal = false;
    
    /**
     * Flag to keep target original field untouched, must be disabled for import (default false)
     * Must be enabled for (true) for retagging segments!
     * @var boolean
     */
    private $keepTargetOriginal = false;
    
    /**
     * Special Paramters:
     * 
     * $parameters['resourcePool']
     * sets the resourcePool for slot-calculation depending on the context.
     * Possible values are all values out of $this->allowedResourcePool
     * 
     * $parameters['useTargetOriginal']
     * set to true to use the target original field instead of the target edited field 
     * default is false
     * 
     * $parameters['keepTargetOriginal']
     * set to true to leave the target original field unmodified!
     * default is false, since not needed for import, but for retagging of segments 
     * 
     * On very first init:
     * seperate data from parameters which are needed while processing queued-worker.
     * All informations which are only relevant in 'normal processing (not queued)'
     * are not needed to be saved in DB worker-table (aka not send to parent::init as $parameters)
     * 
     * ATTENTION:
     * for queued-operating $parameters saved in parent::init MUST have all necessary paramters
     * to call this init function again on instanceByModel
     * 
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::init()
     */
    public function init($taskGuid = NULL, $parameters = array()) {
        $this->useTargetOriginal = !empty($parameters['useTargetOriginal']);
        $parameters['useTargetOriginal'] = $this->useTargetOriginal;
        $this->keepTargetOriginal = !empty($parameters['keepTargetOriginal']);
        $parameters['keepTargetOriginal'] = $this->keepTargetOriginal;
        
        return parent::init($taskGuid, $parameters);
    }
    
    /**
     * Method for CallBack Workers to reset the termtag state
     * @param unknown $taskGuid
     */
    public function resetTermtagState($taskGuid) {
        $segMetaDb = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $segMetaDb editor_Models_Db_SegmentMeta */
        $segMetaDb->update(array('termtagState' => self::SEGMENT_STATE_UNTAGGED), array('taskGuid = ?' => $taskGuid));
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        return parent::run();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $taskGuid = $this->workerModel->getTaskGuid();
        $segmentIds = $this->loadUntaggedSegmentIds($taskGuid);
        
        if (empty($segmentIds)) {
            $segmentIds = $this->loadNextRetagSegmentId();
            $state = self::SEGMENT_STATE_DEFECT;
            if(empty($segmentIds)) {
                $this->reportDefectSegments($taskGuid);
                return false;
            }
        }
        
        $serverCommunication = $this->fillServerCommunication($segmentIds);
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $termTagger = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $termTagger editor_Plugins_TermTagger_Service */
        
        try {
            $this->checkTermTaggerTbx($this->workerModel->getSlot(), $serverCommunication->tbxFile);
            $result = $termTagger->tagterms($this->workerModel->getSlot(), $serverCommunication);
            $this->saveSegments($this->markTransFound($result->segments));
        }
        catch(editor_Plugins_TermTagger_Exception_Malfunction $e) {
            if (empty($state)) {
                $state = self::SEGMENT_STATE_RETAG;
            }
            $this->setTermtagState($segmentIds, $state);
        }
        catch(editor_Plugins_TermTagger_Exception_Abstract $exception) {
            $this->setTermtagState($segmentIds, self::SEGMENT_STATE_UNTAGGED);
            $url = $this->workerModel->getSlot();
            $exception->setMessage('TermTagger '.$url.' (task '.$this->taskGuid.') could not tag segments on import! Reason: '."\n".$exception->getMessage(), false);
            $this->log->logException($exception);
            sleep(60);
        }
        
        // initialize an new worker-queue-entry to continue 'chained'-import-process
        $this->createNewWorkerChainEntry($taskGuid);
    }
    
    
    private function createNewWorkerChainEntry($taskGuid) {
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
        
        if (!$worker->init($taskGuid, $this->workerModel->getParameters())) {
            $this->log->logError('TermTaggerImport-Error on new worker init()'
                                , __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue($this->workerModel->getParentId());
    }
    
    /**
     * Loads a list of segmentIds where terms are not tagged yet.
     * Limit for this list is $config->runtimeOptions->termTagger->segmentsPerCall
     * 
     * @param string $taskGuid
     */
    private function loadUntaggedSegmentIds($taskGuid) {
        $config = Zend_Registry::get('config');
        $limit = $config->runtimeOptions->termTagger->segmentsPerCall;
        
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $db editor_Models_Db_Segments */
        $dbName = $db->info($db::NAME);
        
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        $dbMetaName = $dbMeta->info($dbMeta::NAME);
        
        $dbWorker = ZfExtended_Factory::get('ZfExtended_Models_Db_Worker');
        /* @var $dbWorker ZfExtended_Models_Db_Worker */
        $dbWorkerName = $dbWorker->info($dbWorker::NAME);
        
        $dbSegmentField = ZfExtended_Factory::get('editor_Models_Db_SegmentField');
        /* @var $dbSegmentField editor_Models_Db_SegmentField */
        $dbSegmentFieldName = $dbSegmentField->info($dbSegmentField::NAME);
        
        $dbSegmentData = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $dbSegmentField editor_Models_Db_SegmentData */
        $dbSegmentDataName = $dbSegmentData->info($dbSegmentData::NAME);
        
        $db->getAdapter()->query("lock tables `".$dbMetaName."` WRITE, ".$dbWorkerName." WRITE, ".$dbName." WRITE, ".$dbSegmentFieldName." WRITE, ".$dbSegmentDataName." WRITE");
        
        //TODO TRANSLATE-351 - The "inprogress" setting can be improved as described in the issue!
        
        $select = $this->getNextSegmentSelect($db);
        $sql = $select->where($dbMetaName.'.termtagState IS NULL OR '.$dbMetaName.'.termtagState IN (?)',
                            array($this::SEGMENT_STATE_UNTAGGED)) //, $this::SEGMENT_STATE_RETAG)) // later there may will be a state 'targetnotfound'
                    ->order($dbName.'.id')
                    ->limit($limit);
        $segmentIds = $db->fetchAll($sql)->toArray();
        
        foreach ($segmentIds as $segmentId) {
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($segmentId['id']);
            $segment->meta()->setTermtagState($this::SEGMENT_STATE_INPROGRESS);
            $segment->meta()->save();
        }
        $db->getAdapter()->query('unlock tables');
        
        return $segmentIds;
    }
    
    /**
     * Loads a list with the next segmentId where terms are marked as to be "retag"ged
     * returns only one segment since this segments has to be single tagged
     * 
     * @param string $taskGuid
     * @return array
     */
    private function loadNextRetagSegmentId() {
        // get list of untagged segments
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $db editor_Models_Db_Segments */
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        $dbMetaName = $dbMeta->info($dbMeta::NAME);
        $select = $this->getNextSegmentSelect($db);
        $sql = $select->where($dbMetaName.'.termtagState = ?',$this::SEGMENT_STATE_RETAG)
                    ->limit(1);
        return $db->fetchAll($sql)->toArray();
    }
    
    /**
     * Helper function
     * @param editor_Models_Db_Segments $db
     * @param string $taskGuid
     * @return Zend_Db_Table_Select
     */
    private function getNextSegmentSelect(editor_Models_Db_Segments $db) {
        $dbName = $db->info($db::NAME);
        /* @var $db editor_Models_Db_Segments */
        return $db->select()
                    ->from($dbName, $dbName.'.id')
                    ->joinLeft($dbName.'_meta', $dbName.'.id = '.$dbName.'_meta'.'.segmentId', array())
                    ->where($dbName.'.taskGuid = ?', $this->workerModel->getTaskGuid());
    }
    
    /**
     * Creates a ServerCommunication-Object initialized with $task
     * inclusive all field of alls segments provided in $segmentIds
     * 
     * @param array $segmentIds
     * @return editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private function fillServerCommunication (array $segmentIds) {
        
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication', array($this->task));
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($this->workerModel->getTaskGuid());
        $segmentFields = $fieldManager->getFieldList();
        $this->sourceFieldName = $fieldManager->getFirstSourceName();
        
        foreach ($segmentIds as $segmentId) {
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($segmentId['id']);
            
            $sourceText = $segment->get($this->sourceFieldName);
            
            foreach ($segmentFields as $field) {
                if($field->type != editor_Models_SegmentField::TYPE_TARGET || !$field->editable) {
                    continue;
                }
                $targetText = $this->useTargetOriginal ? $segment->getTarget() : $segment->getTargetEdit();
                $serverCommunication->addSegment($segment->getId(), $field->name, $sourceText, $targetText);
            }
        }
        
        return $serverCommunication;
    }
    
    /**
     * Save TermTagged-segments for $task povided in $segments
     * 
     * @param unknown $segments
     */
    private function saveSegments($segments) {
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($this->workerModel->getTaskGuid());
        
        $responses = $this->groupResponseById($segments);
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        foreach ($responses as $segmentId => $responseGroup) {
            $segment->load($segmentId);
        
            $segment->set($this->sourceFieldName, $responseGroup[0]->source);
            if ($this->task->getEnableSourceEditing()) {
                $segment->set($fieldManager->getEditIndex($this->sourceFieldName), $responseGroup[0]->source);
            }
        
            foreach ($responseGroup as $response) {
                if(! $this->keepTargetOriginal) {
                    $segment->set($response->field, $response->target);
                }
                if(! $this->useTargetOriginal) {
                    $segment->set($fieldManager->getEditIndex($response->field), $response->target);
                }
            }
        
            $segment->save();
        
            $segment->meta()->setTermtagState($this::SEGMENT_STATE_TAGGED);
            $segment->meta()->save();
        }
    }
    
    /**
     * sets the meta TermtagState of the given segment ids to SEGMENT_STATE_RETAG
     * @param editor_Models_Task $task
     * @param array $segments
     * @param string $state
     */
    private function setTermtagState(array $segments, $state) {
        $ids = array_map(function($seg){
            return $seg['id'];
        }, $segments);
        
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        foreach ($ids as $segmentId) {
            try {
                $meta->loadBySegmentId($segmentId);
            } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                $meta->init(array('taskGuid' => $this->task->getTaskGuid(), 'segmentId' => $segmentId));
            }
            $meta->setTermtagState($state);
            $meta->save();
        }
    }
    
    /**
     * In case of multiple target-fields in one segment, there are multiple responses for the same segment.
     * This function groups this different responses under the same segmentId
     * 
     * @param array $responses
     * @return array grouped
     */
    private function groupResponseById($responses) {
        $return = array();
        
        foreach ($responses as $response) {
            $return[$response->id][] = $response;
        }
        
        return $return;
    }
    
    private function reportDefectSegments($taskGuid) {
        // get list of defect segments
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $db editor_Models_Db_Segments */
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        $dbMetaName = $dbMeta->info($dbMeta::NAME);
        $select = $this->getNextSegmentSelect($db);
        $sql = $select->where($dbMetaName.'.termtagState = ?',$this::SEGMENT_STATE_DEFECT);
        $defectSegments = $db->fetchAll($sql)->toArray();
        
        if (empty($defectSegments)) {
            return;
        }
        
        $msg = 'While importing Task "'.$this->task->getTaskName().'" with $taskGuid: '.$taskGuid
                .' the following Segments where marked as defect:'."\n";
        //$msg .= '  $defectSegments: '.print_r($defectSegments, true);
        foreach ($defectSegments as $defectsegment) {
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($defectsegment['id']);
            
            $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
            /* @var $fieldManager editor_Models_SegmentFieldManager */
            $fieldManager->initFields($taskGuid);
            $segmentFields = $fieldManager->getFieldList();
            $sourceFieldName = $fieldManager->getFirstSourceName();
            
            $msg .= '- # '.$segment->getSegmentNrInTask().'; Source-Text: '.strip_tags($segment->get($sourceFieldName))."\n";
        }
                
        
        $this->log->logError('TermTagger-Import defect Segments ', __CLASS__.' -> '.__FUNCTION__."\n". $msg);
    }
}
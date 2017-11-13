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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Stellt Methoden zur Verarbeitung der vom Parser ermittelteten Segment Daten bereit
 * - speichert die ermittelten Segment Daten als Segmente in die DB
 */
class editor_Models_Import_SegmentProcessor_ProofRead extends editor_Models_Import_SegmentProcessor {
    /**
     * @var Zend_Db_Adapter_Mysqli
     */
    protected $db = NULL;
    
    /**
     * @var Zend_Config
     */
    protected $taskConf;
    
    /**
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;
    
    /**
     * @var int
     */
    protected $segmentNrInTask = 0;
    
    /**
     * @param editor_Models_Task $task
     * @param editor_Models_Import_Configuration $config
     */
    public function __construct(editor_Models_Task $task, editor_Models_Import_Configuration $config){
        parent::__construct($task);
        $this->importConfig = $config;
        $this->db = Zend_Registry::get('db');
        $this->taskConf = $this->task->getAsConfig();
    }

    public function process(editor_Models_Import_FileParser $parser){
        $seg = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $seg editor_Models_Segment */
        //statische, Task spezifische Daten zum Segment
        $seg->setUserGuid($this->importConfig->userGuid);
        $seg->setUserName($this->importConfig->userName);

        $seg->setTaskGuid($this->taskGuid);
        
        //Segment Spezifische Daten
        $mid = $parser->getMid();
        $seg->setMid($mid); 
        $seg->setFileId($this->fileId);
        
        $attributes = $parser->getSegmentAttributes($mid);
        $seg->setMatchRate($attributes->matchRate);
        $seg->setMatchRateType($attributes->matchRateType);
        $seg->setEditable($attributes->editable);
        $seg->setAutoStateId($attributes->autoStateId);
        $seg->setPretrans($attributes->pretrans);
        
        $this->segmentNrInTask++;
        $seg->setSegmentNrInTask($this->segmentNrInTask);
        $seg->setFieldContents($parser->getSegmentFieldManager(), $parser->getFieldContents());
        
        $segmentId = $seg->save();
        $this->processSegmentMeta($seg, $attributes);
        return $segmentId; 
    }
    
    /**
     * Processes additional segment attributes which are stored in the segment meta table
     * @param editor_Models_Segment $seg
     * @param editor_Models_Import_FileParser_SegmentAttributes $attributes
     */
    protected function processSegmentMeta(editor_Models_Segment $seg, editor_Models_Import_FileParser_SegmentAttributes $attributes) {
        $modified = false;
        $meta = $seg->meta();
        if(!empty($attributes->maxWidth) && !is_null($attributes->maxWidth)) {
            $meta->setMaxWidth($attributes->maxWidth);
            $modified = true;
        }
        if(!empty($attributes->minWidth) && !is_null($attributes->minWidth)) {
            $meta->setMinWidth($attributes->minWidth);
            $modified = true;
        }
        if($modified) {
            $meta->save();
        }
    }
    
    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     * @param editor_Models_Import_FileParser $parser
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser) {
    	$skel = ZfExtended_Factory::get('editor_Models_Skeletonfile');
    	$skel->setfileName($this->fileName);
    	$skel->setfileId($this->fileId);
    	$skel->setFile($parser->getSkeletonFile()); // wird in Sdlxliff.php befüllt
    	$skel->save();
        $this->saveFieldWidth($parser);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId) {
        $this->calculateFieldWidth($parser);
    }
}
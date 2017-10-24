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
 * Segment TermTag Recreator
 */
class editor_Models_Segment_MaterializedView {
    const VIEW_PREFIX = 'LEK_segment_view_';
    
    /**
     * @var string
     */
    protected $taskGuid;
    
    /**
     * @var string
     */
    protected $viewName;
    
    /**
     */
    public function __construct($taskGuid = null) {
        $this->config = Zend_Registry::get('config');
        $this->db = Zend_Registry::get('db');
        if(!empty($taskGuid)) {
            $this->setTaskGuid($taskGuid);
        }
    }
    
    /**
     * sets the taskguid to be used internally
     * @param string $taskGuid
     */
    public function setTaskGuid($taskGuid) {
        $this->taskGuid = $taskGuid;
        $this->viewName = $this->makeViewName($taskGuid);
    }
    
    /**
     * generates the view name out of the taskGuid
     * @param string $taskGuid
     */
    protected function makeViewName($taskGuid) {
        return self::VIEW_PREFIX.md5($taskGuid);
    }
    
    /**
     * returns the name of the data view
     * @param string $taskGuid
     * @return string
     */
    public function getName() {
        $this->checkTaskGuid();
        return $this->viewName;
    }
    
    /**
     * creates a temporary table used as materialized view
     */
    public function create() {
        $this->checkTaskGuid();
        //$start = microtime(true);
        if($this->createMutexed()) {
            $this->addFields();
            $this->fillWithData();
            return;
        }
        //the following check call is to avoid using a not completly filled MV in a second request accessing this task
        $this->checkMvFillState();
    }

    /**
     * ensure that a taskGuid is set
     * @throws LogicException
     */
    protected function checkTaskGuid() {
        if(empty($this->taskGuid)) {
            throw new LogicException('You have to provide a taskGuid!');
        }
    }
    
    /**
     * created the MV table mutexed, if it already exists return false, if created return true.
     * @return boolean true if table was created, false if it already exists
     */
    protected function createMutexed() {
        $createSql = 'CREATE TABLE `'.$this->viewName.'` LIKE `LEK_segments`; ALTER TABLE `'.$this->viewName.'` ENGINE=MyISAM;';
        $db = Zend_Db_Table::getDefaultAdapter();
        try {
            $db->query($createSql);
            return true;
        }
        catch(Zend_Db_Statement_Exception $e) {
            $m = $e->getMessage();
            //the second string check must be case insensitive for windows usage
            if(strpos($m,'SQLSTATE') !== 0 || stripos($m,'Base table or view already exists: 1050 Table \''.$this->viewName.'\' already exists') === false) {
                throw $e;
            }
            return false;
        }
    }

    /**
     * Adds the fluent field names to the materialized view
     */
    protected function addFields() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $data = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $data editor_Models_Db_SegmentData */
        $md = $data->info($data::METADATA);
        
        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($this->taskGuid);
        $baseCols = $sfm->getBaseColumns();
        
        //define the add column states based on the field type stored in the DB
        $addColTpl = array();
        foreach($baseCols as $v) {
            if(empty($md[$v])) {
                throw new Zend_Exception('Missing Column '.$v.' in LEK_segment_data on creating the materialized view!');
            }
            $sql = 'ADD COLUMN `%s%s%s` '.strtoupper($md[$v]['DATA_TYPE']);
            if(!empty($md[$v]['LENGTH'])) {
                $sql .= '('.$md[$v]['LENGTH'].')';
            }
            if(empty($md[$v]['NULLABLE'])) {
                $sql .= ' NOT NULL';
            }
            
            //searching in our text fields should be searched binary, see TRANSLATE-646 
            if($v == 'original' || $v == 'edited') {
                $sql .= ' COLLATE utf8_bin';
            }
            
            $addColTpl[$v] = $sql;
        }
        
        //loop over all available segment fields for this task and create the SQL for
        $walker = function($prefix,$name, $suffix, $realCol) use ($addColTpl) {
            return sprintf($addColTpl[$realCol],$prefix,$name, $suffix);
        };
        
        $addColSql = $sfm->walkFields($walker);
        
        $sql = 'ALTER TABLE `'.$this->viewName.'` '.join(', ', $addColSql).';';
        $db->query($sql);
    }
    
    /**
     * checks if the MV is already filled up, if not, wait a maximum of 28 seconds.
     * @throws Zend_Exception
     */
    protected function checkMvFillState() {
        $fillQuery = 'select mv.cnt mvCnt, tab.cnt tabCnt from (select count(*) cnt from LEK_segments where taskGuid = ?) mv, ';
        $fillQuery .= '(select count(*) cnt from '.$this->viewName.' where taskGuid = ?) tab;';
        $db = Zend_Db_Table::getDefaultAdapter();
        //we assume a maximum of 28 seconds to wait on the MV
        for($i=1;$i<8;$i++) {
            //if the MV was already created, wait until it is already completly filled 
            $res = $db->fetchRow($fillQuery, array($this->taskGuid,$this->taskGuid));
            if($res && $res['mvCnt'] == $res['tabCnt']) {
                return;
            }
            sleep($i);
        }
        //here throw exception
        throw new Zend_Exception('TimeOut on waiting for the following materialized view to be filled (Task '.$this->taskGuid.'): '.$this->viewName);
    }
    
    /**
     * prefills the materialized view
     */
    protected function fillWithData() {
        $selectSql = array('INSERT INTO '.$this->viewName.' SELECT s.*');

        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($this->taskGuid);
        $walker = function($prefix,$name, $suffix, $realCol) use (&$selectSql) {
            //if($prefix){
            //    $selectSql[] = sprintf('MAX(IF(d.name = \'%s\', PREG_REPLACE("#<[^>]+>#","",d.%s), NULL)) AS %s%s', $name, $realCol, $prefix,$name, $suffix);
            //}else{
            //    $selectSql[] = sprintf('MAX(IF(d.name = \'%s\', d.%s, NULL)) AS %s%s', $name, $realCol, $prefix,$name, $suffix);
            //}
            //TODO create function which will remove the html tags, so the new colum have content with no html in it
            $selectSql[] = sprintf('MAX(IF(d.name = \'%s\', d.%s, NULL)) AS %s%s', $name, $realCol, $prefix,$name, $suffix);
        };
        //loop over all available segment fields for this task and create SQL for
        $sfm->walkFields($walker);
        $selectSql = join(',', $selectSql);
        $selectSql .= ' FROM LEK_segment_data d, LEK_segments s';
        $selectSql .= ' WHERE d.taskGuid = ? and s.taskGuid = d.taskGuid and d.segmentId = s.id';
        $selectSql .= ' GROUP BY d.segmentId';
        
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query($selectSql, $this->taskGuid);
    }
    
    /**
     * Updates the Materialized View Data Object with the saved data.
     * @param editor_Models_Segment $segment
     */
    public function updateSegment(editor_Models_Segment $segment) {
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments', array(array(), $this->viewName));
        /* @var $db editor_Models_Db_Segments */
        $data = $segment->getDataObject();
        $id = $data->id;
        unset($data->id);
        unset($data->isWatched);
        unset($data->segmentUserAssocId);
        $db->update((array) $data, array('id = ?' => $id));
    }
    
    /**
     * drops the segment data view to the given taskguid
     * @param string $taskGuid
     */
    public function drop() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query("DROP TABLE IF EXISTS " . $this->viewName);
    }
    
    /**
     * drops unused materialized views. Unused means not exisiting in LEK_task_log since X days, 
     * where X can be configured in app.ini (resources.db.matViewLifetime)
     */
    public function cleanUp() {
        $config = Zend_Registry::get('config');
        $lifeTime = (int) $config->resources->db->matViewLifetime;
        $db = Zend_Db_Table::getDefaultAdapter();
        
        //since unused tasks are not listed in LEK_task_log we have to check against the creation date. 
        //If this is older than lifetime, and mat view was not used, then drop it.
        $viewLike = self::VIEW_PREFIX.'%';
        $sql = 'select table_name from INFORMATION_SCHEMA.TABLES t where t.TABLE_SCHEMA = database() and t.TABLE_NAME like ? and t.create_time < (CURRENT_TIMESTAMP - INTERVAL ? DAY);';
        $viewToDelete = $db->fetchAll($sql, array($viewLike, $lifeTime), Zend_Db::FETCH_COLUMN);
        
        $sql = 'select t.taskGuid from LEK_task t WHERE t.taskGuid in (select distinct taskGuid from LEK_task_log where created > (CURRENT_TIMESTAMP - INTERVAL ? DAY));';
        $tasksInUse = $db->fetchAll($sql, array($lifeTime), Zend_Db::FETCH_COLUMN);
        $viewsInUse = array_map(array($this, 'makeViewName'),$tasksInUse);
        
        foreach($viewToDelete as $view) {
            if(in_array($view, $viewsInUse)) {
                continue;
            }
            $sql = 'DROP TABLE IF EXISTS `'.$view.'`';
            $db->query($sql);
        }
    }
    
    public function fieldExist($fieldName){
        $db = Zend_Db_Table::getDefaultAdapter();
        $sql='SHOW COLUMNS FROM `'.$this->getName().'` LIKE "'.$fieldName.'"';
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }
}
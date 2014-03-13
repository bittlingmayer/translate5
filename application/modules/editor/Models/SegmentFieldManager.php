<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
/**
 * Intelligent Container for the segment fields of a Task
 */
class editor_Models_SegmentFieldManager {
    /**
     * This are the default labels, they are translated on sending the output (in SegmentfieldController)
     */
    const LABEL_SOURCE = 'Ausgangstext';
    const LABEL_TARGET = 'Zieltext'; 
    const LABEL_RELAIS = 'Relaissprache';
    
    const _MAP_DELIM = '#';
    const _EDIT_PREFIX = 'Edit';
    
    /**
     * @var array
     */
    protected $segmentfields = array();
    
    /**
     * contains a map between Segment get and set keys (targetEditToSort) and the field and db col name (target#editedToSort)
     * @var array
     */
    protected $segmentDataMap = array();
    
    /**
     * @var array
     */
    protected $firstNameOfType = array();
    
    /**
     * affected taskGuid
     * @var string
     */
    protected $taskGuid;
    
    /**
     * base definition of our cols in the segment data table
     * @var array
     */
    protected $baseFieldColMap = array(
        '' => 'original',
        'Md5' => 'originalMd5',
        'ToSort' => 'originalToSort',
        'Edit' => 'edited',
        'EditToSort' => 'editedToSort',
    );
    
    /**
     * key: taskGuid, value: the segmentFieldManager instance for the taskGuid
     * @var array
     */
    protected static $instances = array();
    
    /**
     * Since SegmentFieldManager is used frequently at different places, 
     * we provide an internal cache of already initialized Instances for specific taskGuids
     * @param string $taskGuid
     * @return editor_Models_SegmentFieldManager
     */
    public static function getForTaskGuid($taskGuid) {
        if(empty(self::$instances[$taskGuid])) {
            $sfm = ZfExtended_Factory::get(__CLASS__);
            $sfm->initFields($taskGuid);
            self::$instances[$taskGuid] = $sfm;
        }
        return self::$instances[$taskGuid];
    }
    
    /**
     * returns the loaded fields as array of Zend_Db_Table_Row
     * @return [Zend_Db_Table_Row]
     */
    public function getFieldList() {
        return $this->segmentfields;
    }
    
    /**
     * initiates the task specific segment fields
     * @param string $taskGuid
     * @param boolean $reload optional, if true overwrite the internal stored fields for the task (if they were already loaded)
     */
    public function initFields($taskGuid, $reload = false) {
        if($this->taskGuid == $taskGuid && !$reload) {
            return; //already loaded for this guid
        }
        $this->taskGuid = $taskGuid;
        if(isset(self::$instances[$taskGuid]) && !$reload) {
            $inst = self::$instances[$taskGuid];
            $this->segmentfields = $inst->segmentfields;
            $this->segmentDataMap = $inst->segmentDataMap;
            $this->firstNameOfType = $inst->firstNameOfType;
            return; //recycle already loaded fields
        }
        $segmentfield = ZfExtended_Factory::get('editor_Models_SegmentField');
        /* @var $segmentfield editor_Models_SegmentField */
        $fields = $segmentfield->loadByTaskGuidAsRowset($taskGuid);

        $this->segmentfields = array();
        $this->segmentDataMap = array();
        foreach($fields as $field) {
            if(empty($this->firstNameOfType[$field->type])){
                $this->firstNameOfType[$field->type] = $field->name;
            }
            $this->segmentfields[$field->name] = $field;
            $this->addFieldToDataMap($field->name);
        }
        self::$instances[$taskGuid] = $this;
    }
    
    /**
     * fills the data col to field map
     */
    protected function addFieldToDataMap($fieldname) {
        foreach($this->baseFieldColMap as $k => $v) {
            $this->segmentDataMap[$fieldname.$k] = $fieldname.self::_MAP_DELIM.$v;
        }
    }
    
    /**
     * returns the sortColMap for the loaded fields
     */
    public function getSortColMap() {
        $result = array();
        foreach($this->segmentDataMap as $key => $val) {
            $pos = strrpos($key, 'ToSort');
            if($pos !== false) {
                $result[substr($key, 0, $pos)] = $key;
            }
        }
        return $result;
    }
    
    /**
     * returns true if we have exactly one source and one target field. A relais field can be optional.
     * If we have alternatives return false.
     * Is used to determine if we can use alikes or not.
     * @param array $fields optional, if omitted the internal loaded fields are used
     */
    public function isDefaultLayout(array $fields = null) {
        if(empty($fields)) {
            $fields = array_keys($this->segmentfields);
        }
        $defaultFields = array(
            editor_Models_SegmentField::TYPE_RELAIS,
            editor_Models_SegmentField::TYPE_SOURCE,
            editor_Models_SegmentField::TYPE_TARGET,
        );
        $diff = array_diff($fields, $defaultFields);
        return empty($diff);
    }
    
    /**
     * returns a field instance by given name or false if not found
     * @param string $name
     * @return Zend_Db_Table_Row_Abstract | false; if not false, then RowAbstract is of type SegmentField
     */
    public function getByName($name) {
        if(! array_key_exists($name, $this->segmentfields)){
            return false;
        }
        return $this->segmentfields[$name];
    }
    
    /**
     * returns an array with the segment field and the DB Col name to the given get / set key (dataindex)
     * the array looks for "targetEditMd5" like: ['field' => 'target', 'column' => 'editedMd5']
     * @param string $dataindex
     * @return array | false if no matching field was found
     */
    public function getDataLocationByKey($key) {
        if(array_key_exists($key, $this->segmentDataMap)) {
            return array_combine(array('field', 'column'), explode(self::_MAP_DELIM, $this->segmentDataMap[$key]));
        }
        return false;
    }
    
    /**
     * Add the given segment field (for the internally stored taskGuid)
     * FIXME Sortierung der Felder!!!
     * @param string $label string any string as label
     * @param string $type one of the editor_Models_SegmentField::TYPE_... consts
     * @param boolean $editable optional, default null means that editable is calculated. if boolean use the given value for editable
     * @return string returns the fieldname to be used by the segment data instances for this field
     */
    public function addField($label, $type, $editable = null) {
        $fieldCnt = array();
        foreach($this->segmentfields as $field) {
            //label already exists, so we take this fields name
            if($label === $field->label) {
                return $field->name;
            }
            if($field->type === $type) {
                $fieldCnt[] = (int) substr($field->name, strlen($field->type));
            }
        }
        $name = $type;
        if(!empty($fieldCnt)) {
            $name .= max($fieldCnt)+1;
        }
        $this->addFieldToDataMap($name);
        $field = ZfExtended_Factory::get('editor_Models_SegmentField');
        /* @var $field editor_Models_SegmentField */
        
        $isTarget = ($type === $field::TYPE_TARGET);
        if(is_null($editable)) {
            $editable = $isTarget;
        }
        $field->setLabel($label);
        $field->setName($name);
        $field->setTaskGuid($this->taskGuid);
        $field->setType($type);
        $field->setEditable($editable);
        $field->setRankable($isTarget);
        $field->save();
        if(empty($this->firstNameOfType[$type])){
            $this->firstNameOfType[$type] = $name;
        }
        $this->segmentfields[$name] = $field->getRowObject();
        return $name;
    }
    
    /**
     * returns the first field name of the desired type
     * @param string $type
     * @return string
     */
    protected function getFirstName($type) {
        if(empty($this->firstNameOfType[$type])) {
            throw new Zend_Exception('Desired Segment Field Type '.$type.' not set!');
        }
        return $this->firstNameOfType[$type];
    }
    
    /**
     * returns the first field name of the desired type
     * @return string
     */
    public function getFirstSourceName() {
        return $this->getFirstName(editor_Models_SegmentField::TYPE_SOURCE);
    }
    
    /**
     * returns the first field name of the desired type
     * @return string
     */
    public function getFirstTargetName() {
        return $this->getFirstName(editor_Models_SegmentField::TYPE_TARGET);
    }
    
    /**
     * returns the first field name of the desired type
     * @return string
     */
    public function getFirstRelaisName() {
        return $this->getFirstName(editor_Models_SegmentField::TYPE_RELAIS);
    }
    
    /**
     * creates the nam of the data view
     * @param string $taskGuid
     * @return string
     */
    public function getDataViewName($taskGuid) {
        return "LEK_segment_view_" . md5($taskGuid);
    }
    
    /**
     * returns a list of editable field dataindizes
     * @return array
     */
    public function getEditableDataIndexList() {
        return $this->_getDataIndexList(true);
    }
    
    /**
     * returns a list of all field dataindizes
     * @return array
     */
    public function getDataIndexList() {
        return $this->_getDataIndexList(false);
    }
    
    /**
     * returns a list of all or editable field dataindizes, depending on param
     * @param boolean $editableOnly if true return editable field data indizes only
     * @return array
     */
    protected function _getDataIndexList($editableOnly) {
        $result = array();
        foreach($this->segmentfields as $field) {
            if(!$editableOnly) {
                $result[] = $field->name;
            }
            if($field->editable) {
                $result[] = $this->getEditIndex($field->name);
            }
        }
        return $result;
    }
    
    /**
     * returns the editable Data Index to a given field name, false if field does not exists or is not editable!
     * @param string $name
     * @return string|false
     */
    public function getEditIndex($name) {
        if($this->getByName($name) === false){
            return false;
        }
        return $name.self::_EDIT_PREFIX;
    }
    
    /**
     * creates / updates the View of the internal stored taskGuid
     * @todo adapt this method for history tables when needed!
     */
    public function updateView() {
        if(empty($this->taskGuid)) {
            throw new LogicException('You have to call initFields before!');
        }
        $viewName         = $this->getDataViewName($this->taskGuid);
        $createViewSql = array('CREATE VIEW '.$viewName.' as SELECT s.*');

        //loop over all available segment fields for this task
        foreach($this->segmentfields as $field) {
            $name = $field->name;
            //loop over our available base data columns and generate them
            foreach($this->baseFieldColMap as $k => $v) {
                if(!$field->editable && strpos($k, self::_EDIT_PREFIX) === 0) {
                    continue;
                }
                $createViewSql[] = sprintf('MAX(IF(d.name = \'%s\', d.%s, NULL)) AS %s%s', $name, $v, $name, $k);
            }
        }
        $createViewSql = join(',', $createViewSql);
        $createViewSql .= ' FROM LEK_segment_data d, LEK_segments s';
        $createViewSql .= ' WHERE d.taskGuid = ? and s.taskGuid = d.taskGuid and d.segmentId = s.id';
        $createViewSql .= ' GROUP BY d.segmentId';

        $db = Zend_Db_Table::getDefaultAdapter();
        $this->_dropView($viewName, $db);
        $db->query($createViewSql, $this->taskGuid);
    }
    
    /**
     * drops the segment data view to the given taskguid
     * @param string $taskGuid
     */
    public function dropView($taskGuid) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $this->_dropView($this->getDataViewName($taskGuid), $db);
    }
    
    /**
     * returns the SQL to add one segment field to an existing segment identified by mid, taskGuid and fileId
     * placeholders in SQL are: fieldName, 
     * sql for encapsulating purposes in this class
     */
    public function getAddOneFieldSql() {
        return 'INSERT INTO LEK_segment_data (taskGuid, name, segmentId, mid, 
                original, originalMd5, originalToSort, edited, editedToSort) 
                SELECT taskGuid, ?, id, mid, ?, ?, ?, ?, ?, ?, ';
    }

    /**
     * generates all needed data attributes for the segment fields and fills them up with the given data. 
     * Attributes are merged into the given resultObject. Since the object is given by reference no return value is used.
     * @param array $segmentData
     * @param stdClass $resultObj
     */
    public function mergeData(array $segmentData, stdClass $resultObj) {
        //loop over all available segment fields for this task
        foreach($this->segmentfields as $field) {
            $name = $field->name;
            $data = $segmentData[$name];
            //loop over our available base data columns and generate them
            foreach($this->baseFieldColMap as $k => $v) {
                if(!$field->editable && strpos($k, self::_EDIT_PREFIX) === 0) {
                    continue;
                }
                $resultObj->{$name.$k} = $data->{$v};
            }
        }
    }
    
    /**
     * reusable internal delete view function
     * @param string $viewname
     * @param Zend_Db_Adapter_Abstract $db
     */
    protected function _dropView($viewname, Zend_Db_Adapter_Abstract $db) {
        $db->query("DROP VIEW IF EXISTS " . $viewname);
    }
}
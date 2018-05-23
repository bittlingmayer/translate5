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
 * Abstract Base Connector
 */
abstract class editor_Plugins_MatchResource_Services_Connector_Abstract {
    
    const STATUS_ERROR = 'error';
    const STATUS_AVAILABLE = 'available';
    const STATUS_UNKNOWN = 'unknown';
    const STATUS_NOCONNECTION = 'noconnection';
    
    /**
     * @var editor_Plugins_MatchResource_Models_TmMt
     */
    protected $tmmt;
    
    /**
     * Container for the connector results
     * @var editor_Plugins_MatchResource_Services_ServiceResult
     */
    protected $resultList;

    /**
     * initialises the internal result list
     */
    public function __construct() {
        $this->resultList = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_ServiceResult');
    }
    
    /**
     * Just for logging the called methods
     * @param string $msg
     */
    protected function log($method, $msg = '') {
        error_log($method." Tmmt ".$this->tmmt->getName().' - '.$this->tmmt->getServiceName().$msg);
    }
    
    /**
     * Link this Connector Instance to the given Tmmt and its resource
     * @param editor_Plugins_MatchResource_Models_TmMt $tmmt
     */
    public function connectTo(editor_Plugins_MatchResource_Models_TmMt $tmmt) {
        $this->tmmt = $tmmt;
        $this->resultList->setTmmt($tmmt);
    }

    /**
     * Updates translations in the connected service
     * for returning error messages to the GUI use rest_messages
     * @param editor_Models_Segment $segment
     */
    public function update(editor_Models_Segment $segment) {
        //to be implemented if needed
        $this->log(__METHOD__, ' segment '.$segment->getId());
    }
    
    /***
     * Reset the tm result list data
     */
    public function resetResultList(){
        $this->resultList->resetResult();
    }

    /**
     * makes a tm / mt / file query to find a match / translation
     * returns an array with stdObjects, each stdObject contains the fields: 
     * 
     * @param editor_Models_Segment $segment
     * @return editor_Plugins_MatchResource_Services_ServiceResult
     */
    abstract public function query(editor_Models_Segment $segment);

    /**
     * returns the original or edited source content to be queried, depending on source edit
     * @param editor_Models_Segment $segment
     * @return string
     */
    protected function getQueryString(editor_Models_Segment $segment) {
        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($segment->getTaskGuid());
        $source = editor_Models_SegmentField::TYPE_SOURCE;
        $sourceMeta = $sfm->getByName($source);
        $isSourceEdit = ($sourceMeta !== false && $sourceMeta->editable == 1);
        return $isSourceEdit ? $segment->getFieldEdited($source) : $segment->getFieldOriginal($source);
    }
    
    /**
     * makes a tm / mt / file concordance search
     * @param string $queryString
     * @param string $field
     * @return editor_Plugins_MatchResource_Services_ServiceResult
     */
    abstract public function search(string $searchString, $field = 'source', $offset = null);
    
    /**
     * @return the status of the connected resource and additional information if there is some
     */
    abstract public function getStatus(& $moreInfo);
    
    /**
     * Opens the with connectTo given TM on the configured Resource (on task open, not on each request)
     * @param editor_Plugins_MatchResource_Models_TmMt $tmmt
     */
    public function open() {
        //to be implemented if needed
        $this->log(__METHOD__);
    }
    
    /**
     * Closes the connected TM on the configured Resource (on task close, not after each request)
     */
    public function close() {
        //to be implemented if needed
        $this->log(__METHOD__);
    }
}
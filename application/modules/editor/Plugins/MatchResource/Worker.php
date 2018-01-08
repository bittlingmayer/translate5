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

class editor_Plugins_MatchResource_Worker extends editor_Models_Import_Worker_Abstract {
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(empty($parameters['tmmtId'])) {
            return false;
        }
        return true;
    } 
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $assoc = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_Taskassoc');
        /* @var $assoc editor_Plugins_MatchResource_Models_Taskassoc */
        
        $params = $this->workerModel->getParameters();
        
        $task = $this->task;
        if(!$task->lock(NOW_ISO, true)) {
            $this->log->logError('The following task is in use and cannot be reimported: '.$task->getTaskName().' ('.$task->getTaskGuid().')');
            continue;
        }
        $oldState = $task->getState();
        $task->setState('reimporttm');
        $task->save();
        $task->createMaterializedView();
        
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$task->getTaskGuid()]);
        /* @var $segments editor_Models_Segment_Iterator */
        $assoc->loadByTaskGuidAndTm($task->getTaskGuid(), $params['tmmtId']);
        
        $tmmt = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
        /* @var $tmmt editor_Plugins_MatchResource_Models_TmMt */
        $tmmt->load($params['tmmtId']);
        
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $connector = $manager->getConnector($tmmt);
        
        foreach($segments as $segment) {
            if(empty($segment->getTargetEdit()) || mb_strpos($segment->getTargetEdit(), "\n") !== false){
                continue;
            }
            //TaskAssoc laden! daher die segmentsUpdateable info
            if(!empty($assoc->getSegmentsUpdateable())) {
                $connector->update($segment);
            }
        }
        $task->setState($oldState);
        $task->save();
        if($oldState == $task::STATE_END) {
            $task->dropMaterializedView();
        }
        $task->unlock();
        $this->log->logError('Task reimported successfully into the desired TM: '.$task->getTaskName().' ('.$task->getTaskGuid().')');
        return true;
    }
}

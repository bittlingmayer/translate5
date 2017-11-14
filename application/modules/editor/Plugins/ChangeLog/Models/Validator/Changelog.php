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

class editor_Plugins_ChangeLog_Models_Validator_Changelog extends ZfExtended_Models_Validator_Abstract {

    /**
     * Validators for change log Entity
     * 
     */
    protected function defineValidators() {
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive();
        /* @var $workflow editor_Workflow_Abstract */
        //comment = string, without length contrain. No validator needed / possible
        //$this->addValidator('taskGuid', 'guid');
        $this->addValidator('id', 'int');
        $this->addValidator('dateOfChange', 'stringLength', array('min' => 0, 'max' => 11));
        $this->addValidator('jiraNumber', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('title', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('description', 'stringLength', array('min' => 0, 'max' => 1000000));
        $this->addValidator('userGroup', 'stringLength', array('min' => 0, 'max' => 255));
        
    }
}

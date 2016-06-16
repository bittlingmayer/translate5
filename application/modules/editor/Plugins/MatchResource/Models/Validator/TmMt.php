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

class editor_Plugins_MatchResource_Models_Validator_TmMt extends ZfExtended_Models_Validator_Abstract {

    /**
     * Validators for Task User Assoc Entity
     * 
     */
    protected function defineValidators() {
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive();
        /* @var $workflow editor_Workflow_Abstract */
        //comment = string, without length contrain. No validator needed / possible
        //$this->addValidator('taskGuid', 'guid');
        $this->addValidator('id', 'int');
        $this->addValidator('name', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('sourceLang', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('targetLang', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('color', 'stringLength', array('min' => 0, 'max' => 8));
        $this->addValidator('resourceId', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('serviceName', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('serviceType', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('fileName', 'stringLength', array('min' => 0, 'max' => 1024));
        
    }
}

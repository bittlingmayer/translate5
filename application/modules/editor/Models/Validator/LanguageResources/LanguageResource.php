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

class editor_Models_Validator_LanguageResources_LanguageResource extends ZfExtended_Models_Validator_Abstract {

    /**
     * Validators for LanguageResource Entity
     * 
     */
    protected function defineValidators() {
        $this->addValidator('id', 'int');
        $this->addValidator('name', 'stringLength', array('min' => 0, 'max' => 1024));
        $this->addValidator('sourceLang', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('targetLang', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('sourceLangRfc5646', 'stringLength', array('min' => 0, 'max' => 7));
        $this->addValidator('targetLangRfc5646', 'stringLength', array('min' => 0, 'max' => 7));
        $this->addValidator('color', 'stringLength', array('min' => 0, 'max' => 8));
        $this->addValidator('resourceId', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('serviceName', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('serviceType', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('specificData', 'stringLength', array('min' => 0, 'max' => 1024));
    }
}

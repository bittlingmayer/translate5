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
 * @package translate5
 * @version 1.0
 *
 */
require_once'ControllerMixIns.php';
/**
 * Klasse der Nutzermethoden
 */
class LoginController extends ZfExtended_Controllers_Login {
    use ControllerMixIns;
    public function init(){
        parent::init();
        $this->view->languageSelector();
        $this->_form   = new ZfExtended_Zendoverwrites_Form('loginIndex.ini');
    }
    
    public function indexAction() {
        $lock = ZfExtended_Factory::get('ZfExtended_Models_Db_SessionUserLock');
        /* @var $lock ZfExtended_Models_Db_SessionUserLock */
        $this->view->lockedUsers = $lock->getLocked();
        return parent::indexAction();
    }

    protected function initDataAndRedirect() {
        //@todo do this with events
        if(class_exists('editor_Models_Segment_MaterializedView')) {
            $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
            /* @var $mv editor_Models_Segment_MaterializedView */
            $mv->cleanUp();
        }
        
        $this->localeSetup();
        header ('HTTP/1.1 302 Moved Temporarily');
        header ('Location: '.APPLICATION_RUNDIR.'/editor');
        exit;
    }
}
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
 * Cron Controller 
 * This controller provides methods to be called regularly.
 * Each action should be called through wget by a crontjob  
 */ 
class Editor_CronController extends ZfExtended_Controllers_Action {
    /**
     * @var Zend_Session_Namespace
     */
    protected $session;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * simple method to secure that controller is only called by the same server (wget)
     * @see ZfExtended_Controllers_Action::init()
     */
    public function init() {
        $config = Zend_Registry::get('config');
        if($config->runtimeOptions->cronIP !== $_SERVER['REMOTE_ADDR']) {
            exit;
        }
    }
    
    /**
     * Empty index, does nothing
     */
    public function indexAction() {}
    
    /**
     * triggers daily actions
     */
    public function dailyAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $workflows = $wfm->getWorkflows();
        foreach($workflows as $wfId => $cls) {
            $workflow = $wfm->get($wfId);
            /* @var $workflow editor_Workflow_Abstract */
            $workflow->doCronDaily();
        }
        echo "OK";
    }
}


<?php
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/***
 * Enable deletion of user associations not in the user hierarchy.
 * @author aleksandar
 *
 */
class editor_Plugins_DeleteUserAssociations_Init extends ZfExtended_Plugin_Abstract {
    /**
     * Initialize the plugn "DeleteUserAssociations"
     * {@inheritDoc}
     * @see ZfExtended_Plugin_Abstract::init()
     */
    public function init() {
        if(ZfExtended_Debug::hasLevel('plugin', 'DeleteUserAssociations')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
        $this->initEvents();
    }
    
    /**
     * define all event listener
     */
    protected function initEvents() {
        $this->eventManager->attach('Editor_TaskuserassocController', 'afterIndexAction', array($this, 'handleEditableDeletable'));
        $this->eventManager->attach('Editor_TaskuserassocController', 'afterPutAction', array($this, 'handleEditableDeletable'));
        $this->eventManager->attach('Editor_TaskuserassocController', 'afterPostAction', array($this, 'handleEditableDeletable'));
        $this->eventManager->attach('Editor_TaskuserassocController', 'beforeDeleteAction', array($this, 'handleTaskUserAssocBeforeDelete'));
    }
    
    /***
     * Add deletable flag to the assoc record, so in the frontend the user is able to delete and other assoc users
     * 
     * @param Zend_EventManager_Event $event
     */
    public function handleEditableDeletable(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        //enable the deletable flag
        if(is_array($view->rows)) {
            foreach ($view->rows as &$row){
                $row['deletable']=true;
            }
        }
        elseif(is_object($view->rows)) {
            $view->rows->deletable=true;
        }
    }

    /***
     * Before user assoc delete action handler
     * @param Zend_EventManager_Event $event
     */
    public function handleTaskUserAssocBeforeDelete(Zend_EventManager_Event $event){
        //add the backend right seeAllUsers to the current logged user, so the user is able to delete any assoc users
        $userSession = new Zend_Session_Namespace('user');
        $userData = $userSession->data;
        $acl = ZfExtended_Acl::getInstance();
        $acl->allow($userData->roles, 'backend', 'seeAllUsers');
    }
}

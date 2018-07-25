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
 * Resources are no valid Models/Entitys, we support only a generated Resource listing
 * One Resource is one available configured connector, Languages and Title can be customized in the TM Overview List
 */
class editor_ResourceController extends ZfExtended_RestController  {
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::init()
     * 
     * copied the init method, parent can not be used, since no real entity is used here
     */
    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
        
        $this->restMessages = ZfExtended_Factory::get('ZfExtended_Models_Messages');
        Zend_Registry::set('rest_messages', $this->restMessages);
    }
    
    public function indexAction() {
        $serviceManager = ZfExtended_Factory::get('editor_Services_LanguageResources_Manager');
        /* @var $serviceManager editor_Services_LanguageResources_Manager */
        $resources = $serviceManager->getAllResources();
        $result = array();
        
        $userSession = new Zend_Session_Namespace('user');
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        
        $isAllowedFilebased = $acl->isInAllowedRoles($userSession->data->roles, 'frontend', 'languageResourcesAddFilebased');
        $isAllowedNonFilebased = $acl->isInAllowedRoles($userSession->data->roles, 'frontend', 'languageResourcesAddNonFilebased');
        foreach($resources as $resource) {
            /* @var $resource editor_Models_Resource */
            $isFilebased = $resource->getFilebased();
            if($isFilebased && $isAllowedFilebased || !$isFilebased && $isAllowedNonFilebased) {
                $result[] = $resource->getDataObject();
            }
        }
        $this->view->rows = $result;
        $this->view->total = count($result);
    }
    public function getAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->get');
    }
    
    public function putAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }

    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
    }

    public function postAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}


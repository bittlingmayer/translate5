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

/***
 * Imports the language resource file into the language resource.
 */
class editor_Services_ImportWorker extends ZfExtended_Worker_Abstract {
    
    /***
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(empty($parameters['fileinfo']) || empty($parameters['languageResourceId'])){
            return false;
        }
        return true;
    } 
    
    public function init($taskGuid,$params) {
        $this->handleUploadFile($params['fileinfo']);
        return parent::init($taskGuid,$params);
    }
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        return $this->doImport();
    }
    
    /***
     * Move the upload file to the tem directory so it can be used by the worker
     * @param array $fileinfo
     */
    protected function handleUploadFile(&$fileinfo){
        $newFileLocation=APPLICATION_PATH.'/../data'.$fileinfo['tmp_name'];
        move_uploaded_file($fileinfo['tmp_name'],$newFileLocation);
        $fileinfo['tmp_name']=$newFileLocation;
    }

    /**
     * Uploads one file to Okapi to convert it to an XLIFF file importable by translate5
     */
    protected function doImport() {
        $params = $this->workerModel->getParameters();
        
        $this->languageResource=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResource editor_Models_LanguageResources_LanguageResource */
        $this->languageResource->load($params['languageResourceId']);
        
        $connector=$this->getConnector($this->languageResource);
        
        if(isset($params['addnew']) && $params['addnew']){
            $return=$connector->addTm($params['fileinfo'],$params);
        }else{
            $return=$connector->addAdditionalTm($params['fileinfo'],$params);
        }
        
        $this->updateLanguageResourceStatus($connector);
        
        //remove the file from the temp dir
        unlink($params['fileinfo']['tmp_name']);
        
        return $return;
    }
    
    /***
     * Update language reources status so the resource is available again
     */
    protected function updateLanguageResourceStatus() {
        $this->languageResource->addSpecificData('status', editor_Services_Connector_FilebasedAbstract::STATUS_AVAILABLE);
        $this->languageResource->save();
    }
    
    /***
     * Get the language resource connector
     * 
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     * @return editor_Services_Connector
     */
    protected function getConnector($languageResource) {
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        
        return $serviceManager->getConnector($languageResource);
    }
    
}

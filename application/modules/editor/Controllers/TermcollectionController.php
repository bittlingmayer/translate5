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
 * 
 */
class editor_TermcollectionController extends ZfExtended_RestController  {
    
    const FILE_UPLOAD_NAME = 'tbxUpload';
    
    protected $entityClass = 'editor_Models_TermCollection_TermCollection';
    
    /**
     * @var editor_Models_TermCollection_TermCollection
     */
    protected $entity;
    
    /**
     * @var array
     */
    protected $uploadErrors = array();

    /***
     * Info: function incomplete! 
     * Only used in a test at the moment! 
     */
    public function exportAction() {
        $this->decodePutData();
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        
        $export = ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx');
        /* @var $export editor_Models_Export_Terminology_Tbx */
        
        $data=$term->loadSortedByCollectionAndLanguages([$this->data->collectionId]);
        $export->setData($data);
        $exportData=$export->export();
        
        $this->view->filedata=$exportData;
    }
    
    /***
     * Import the terms into the term collection
     * 
     * @throws ZfExtended_Exception
     */
    public function importAction(){
        $params=$this->getRequest()->getParams();
        
        if(!isset($params['collectionId']) || !isset($params['customerId'])){
            //TODO: missing api parameter exception ?
            throw new ZfExtended_Exception();
        }
        
        $filePath=$this->getUploadedTbxFilePaths();
        if(!$this->validateUpload()){
            $this->view->success=false;
        }else{
            //the return is needed for the tests
            $this->view->success=$this->entity->importTbx($filePath,$params);
        }
        
    }
    
    /***
     * Search terms 
     */
    public function searchAction(){
        $params=$this->getRequest()->getParams();
        $responseArray=array();
        
        $model=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $model editor_Models_Term */
        
        $config = Zend_Registry::get('config');
        $termCount=$config->runtimeOptions->termportal->searchTermsCount;
        
        if(isset($params['term'])){
            $languages=isset($params['language']) ? $params['language'] : null;
            
            //if the limit is disabled, do not use it
            if(isset($params['disableLimit']) && $params['disableLimit']=="true"){
                $termCount=null;
            }
            
            $responseArray['term']=$model->searchTermByLanguage($params['term'],$languages,$params['collectionIds'],$termCount);
        }
        
        $this->view->rows=$responseArray;
    }
    
    /***
     * Search term entry and term attributes in group
     */
    public function searchattributeAction(){
        $params=$this->getRequest()->getParams();
        $responseArray=array();
        
        $model=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $model editor_Models_Term */
        
        if(isset($params['groupId'])){
            $responseArray['termAttributes']=$model->searchTermAttributesInTermentry($params['groupId']);
            
            $entryAttr=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntryAttributes');
            /* @var $entryAttr editor_Models_TermCollection_TermEntryAttributes */
            $responseArray['termEntryAttributes']=$entryAttr->getAttributesForTermEntry($params['groupId']);
            
        }
        
        $this->view->rows=$responseArray;
    }
    
    /***
     * This action is only used in a test at the moment! 
     */
    public function testgetattributesAction(){
        $this->view->rows=$this->entity->getAttributesCountForCollection($this->getParam('collectionId'));
    }
    
    /***
     * Return the uploaded tbx files paths
     * 
     * @throws ZfExtended_FileUploadException
     * @return array
     */
    private function getUploadedTbxFilePaths(){
        $upload = new Zend_File_Transfer();
        $upload->addValidator('Extension', false, 'tbx');
        // Returns all known internal file information
        $files = $upload->getFileInfo();
        $filePath=[];
        foreach ($files as $file => $info) {
            // file uploaded ?
            if (!$upload->isUploaded($file)) {
                $this->uploadErrors[]="The file is not uploaded";
                continue;
            }
            
            // validators are ok ?
            if (!$upload->isValid($file)) {
                $this->uploadErrors[]="The file:".$file." is with invalid file extension";
                continue;
            }
            $filePath[]=$info['tmp_name'];
        }
        return $filePath;
    }
    
    
    /**
     * translates and transport upload errors to the frontend
     * @return boolean if there are upload errors false, true otherwise
     */
    protected function validateUpload() {
        if(empty($this->uploadErrors)){
            return true;
        }
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        $errors = array(self::FILE_UPLOAD_NAME => array());
        
        foreach($this->uploadErrors as $error) {
            $errors[self::FILE_UPLOAD_NAME][] = $translate->_($error);
        }
        
        $e = new ZfExtended_ValidateException(print_r($errors, 1));
        $e->setErrors($errors);
        $this->handleValidateException($e);
        return false;
    }
}


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

/**
 * TODO: most of the following code is the same for each language-resource...
 */
class editor_Plugins_PangeaMt_Connector extends editor_Services_Connector_Abstract {

    /**
     * @var editor_Plugins_PangeaMt_HttpApi
     */
    protected $api;

    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::__construct()
     */
    public function __construct() {
        parent::__construct();
        $this->api = ZfExtended_Factory::get('editor_Plugins_PangeaMt_HttpApi');
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->defaultMatchRate = $config->runtimeOptions->plugins->PangeaMt->matchrate;
    }
    
    /***
     * 
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryString($segment);
        $this->resultList->setDefaultSource($queryString);
        
        $queryString = $this->restoreWhitespaceForQuery($queryString);
        
        //TODO: tag mapper class, something similar as TmTagManager from commit-> 7465209ac82597d88de70850b093d750a1964922
        //$map is set by reference
        $map = [];
        $queryString = $this->internalTag->toXliffPaired($queryString, true, $map);
        $mapCount = count($map);
        
        //we have to use the XML parser to restore whitespace, otherwise protectWhitespace would destroy the tags
        $xmlParser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlParser editor_Models_Import_FileParser_XmlParser */
        
        $this->shortTagIdent = $mapCount + 1;
        $xmlParser->registerOther(function($textNode, $key) use ($xmlParser){
            $textNode = $this->whitespaceHelper->protectWhitespace($textNode, true);
            $textNode = $this->whitespaceTagReplacer($textNode);
            $xmlParser->replaceChunk($key, $textNode);
        });
            
        $results = null;
        $sourceLang = $this->languageResource->getSourceLangRfc5646(); // = e.g. "de", TODO: validate against $this->sourceLang (e.g. 4)
        $targetLang = $this->languageResource->getTargetLangRfc5646();// = e.g. "en"; TODO: validate against $this->targetLang (e.g. 5)
        $engineId = $this->languageResource->getSpecificData('engineId');
        
        if($this->api->search($queryString, $sourceLang, $targetLang, $engineId)){
            $results = $this->api->getResult();
            if(empty($results)) {
                return $this->resultList;
            }
            
            foreach ($results as $result) {
                $result = $result[0];
                $target = $result->tgt ?? "";
                $source=$result->src ?? "";

                $target = $xmlParser->parse($target);
                $target = $this->internalTag->reapply2dMap($target, $map);
                $this->resultList->addResult($target, $this->defaultMatchRate);
                
                $source = $xmlParser->parse($source);
                $source = $this->internalTag->reapply2dMap($source, $map);
                $this->resultList->setSource($source);
            }
        }
        return $this->resultList;
    }
    
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        throw new BadMethodCallException("The PangeaMT Translation Connector does not support search requests");
    }
    
    /***
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        return $this->queryPangeaMtApi($searchString);
    }
    
    /***
     * Query the PangeaMT cloud api for the search string
     * @param string $searchString
     * @param bool $reimportWhitespace optional, if true converts whitespace into translate5 capable internal tag
     * @return boolean
     */
    protected function queryPangeaMtApi($searchString, $reimportWhitespace = false){
        if(empty($searchString)) {
            return $this->resultList;
        }
        $allResults = null;
        $sourceLang = $this->languageResource->getSourceLangRfc5646(); // = e.g. "de", TODO: validate against $this->sourceLang (e.g. 4)
        $targetLang = $this->languageResource->getTargetLangRfc5646();// = e.g. "en"; TODO: validate against $this->targetLang (e.g. 5)
        $engineId = $this->languageResource->getSpecificData('engineId');
        if($this->api->search($searchString, $sourceLang, $targetLang, $engineId)){
            $allResults = $this->api->getResult();
        }
        
        if(empty($allResults)) {
            return $this->resultList;
        }
        
        foreach ($allResults as $result) {
            $result = $result[0];
            $translation = $result->tgt ?? "";
            if($reimportWhitespace) {
                $translation = $this->importWhitespaceFromTagLessQuery($translation);
            }
            $this->resultList->addResult($translation,$this->defaultMatchRate);
        }
        return $this->resultList;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        
        try {
            if($this->api->getStatus()){
                return self::STATUS_AVAILABLE;
            }
            return self::STATUS_NOCONNECTION;
        }catch (ZfExtended_ErrorCodeException $e){
            $moreInfo = $e->getMessage();
            $logger = Zend_Registry::get('logger')->cloneMe('editor.languageresource.service.connector');
            /* @var $logger ZfExtended_Logger */
            $logger->warn('E1282','Language resource communication error.',
            array_merge($e->getErrors(),[
                'languageResource'=>$this->languageResource,
                'message'=>$e->getMessage()
            ]));
            return self::STATUS_NOCONNECTION;
        }
    }
}

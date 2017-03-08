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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * OpenTM2 HTTP Connection API
 */
class editor_Plugins_MatchResource_Services_OpenTM2_HttpApi {
    /**
     * @var editor_Plugins_MatchResource_Models_TmMt
     */
    protected $tmmt;
    
    /**
     * @var Zend_Http_Response
     */
    protected $response;
    
    /**
     * @var stdClass
     */
    protected $result;
    
    protected $error = array();
    
    /**
     * For logging purposes
     * @var Zend_Http_Client
     */
    protected $http;
    
    /**
     * For logging purposes
     * @var string
     */
    protected $httpMethod;
    
    public function __construct(editor_Plugins_MatchResource_Models_TmMt $tmmt) {
        $this->tmmt = $tmmt;
    }
    
    /**
     * This method creates a new memory.
     */
    public function createEmptyMemory($memory, $sourceLanguage) {
        $data = new stdClass();
        $data->name = $memory;
        $data->sourceLang = $sourceLanguage;
        
        $http = $this->getHttp('POST');
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        $res = $this->request($http);
        return $this->processResponse($res);
    }
    
    /**
     * This method creates a new memory with TM file
     */
    public function createMemory($memory, $sourceLanguage, $tmData) {
        $data = new stdClass();
        $data->name = $memory;
        $data->sourceLang = $sourceLanguage;
        $data->data = base64_encode($tmData);
        
        $http = $this->getHttp('POST');
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        $res = $this->request($http);
        return $this->processResponse($res);
    }
    
    /**
     * This method imports a memory from a TMX file.
     */
    public function importMemory($tmData) {
        //In:{ "Method":"import", "Memory":"MyTestMemory", "TMXFile":"C:/FileArea/MyTstMemory.TMX" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
        
        $data = new stdClass();
        $data->tmxData = base64_encode($tmData);

        $http = $this->getHttpWithMemory('POST', '/import');
        $http->setConfig(['timeout' => 1200]);
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        
        $res = $this->request($http);
        return $this->processResponse($res);
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts (ID + verbs)
     * @param string $httpMethod
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttp($method, $urlSuffix = '') {
        $url = rtrim($this->tmmt->getResource()->getUrl(), '/');
        $urlSuffix = ltrim($urlSuffix, '/');
        $this->http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $this->http->setUri($url.'/'.$urlSuffix);
        $this->http->setMethod($method);
        $this->httpMethod = $method;
        $this->http->setHeaders('Accept-charset', 'UTF-8');
        $this->http->setHeaders('Accept', 'application/json; charset=utf-8');
        return $this->http;
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the Memory Name + additional URL parts
     * @param string $httpMethod
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttpWithMemory($method, $urlSuffix = '') {
        return $this->getHttp($method, urlencode($this->tmmt->getFileName()).'/'.ltrim($urlSuffix, '/'));
    }
    
    /**
     * retrieves the TM as TM file
     * @return boolean
     */
    public function get($mime) {
        $http = $this->getHttpWithMemory('GET');
        $http->setConfig(['timeout' => 1200]);
        $http->setHeaders('Accept', $mime);
        $response = $this->request($http);
        if($response->getStatus() === 200) {
            $this->result = $response->getBody();
            return true;
        }
        
        return $this->processResponse($this->request($http));
    }

/**
     * This method deletes a memory.
     */
    public function delete() {
        $http = $this->getHttpWithMemory('DELETE');
        return $this->processResponse($this->request($http));
    }
    
    /**
     * searches for matches in the TM
     * @param editor_Models_Segment $segment
     * @param string $queryString
     * @param string $filename
     * @return boolean
     */
    public function lookup(editor_Models_Segment $segment, string $queryString, string $filename) {
        $json = new stdClass();
        $json->sourceLang = $this->tmmt->getSourceLangRfc5646();
        $json->targetLang = $this->tmmt->getTargetLangRfc5646();
        $json->source = $queryString;
        //In general OpenTM2 can deal with whole paths, not only with filenames.
        // But we hold the filepaths in the FileTree JSON, so this value is not easily accessible, 
        // so we take only the single filename at the moment
        $json->documentName = $filename; 
        
        $json->segmentNumber = ''; //TODO can be used after implementing TRANSLATE-793
        $json->markupTable = 'OTMXUXLF';
        $json->context = $segment->getMid(); // here MID (context was designed for dialog keys/numbers on translateable strings software)
        
        $http = $this->getHttpWithMemory('POST', 'fuzzysearch');
        $http->setRawData(json_encode($json), 'application/json; charset=utf-8');
        return $this->processResponse($this->request($http));
    }
    
    /**
     * This method searches the given search string in the proposals contained in a memory (concordance search). 
     * The function returns one proposal per request. 
     * The caller has to provide the search position returned by a previous call or an empty search position to start the search at the begin of the memory.
     * Note: Provide the returned search position NewSearchPosition as SearchPosition on subsequenet calls to do a sequential search of the memory.
     */
    public function search($queryString, $field, $searchPosition = null) {
        $data = new stdClass();
        $data->searchString = $queryString;
        $data->searchType = $field;
        $data->searchPosition = $searchPosition;
        $data->numResults = 5;
        $data->msSearchAfterNumResults = 100;
        $http = $this->getHttpWithMemory('POST', 'concordancesearch');
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        return $this->processResponse($this->request($http));
    }

    /**
     * This method updates (or adds) a memory proposal in the memory.
     * Note: This method updates an existing proposal when a proposal with the same key information (source text, language, segment number, and document name) exists.
     * 
     * @param editor_Models_Segment $segment
     * @return boolean
     */
    public function update(string $source, string $target, editor_Models_Segment $segment, $filename) {
        /* 
         * In:{ "Method":"update", "Memory": "TestMemory", "Proposal": {
         *  "Source": "This is the source text", 
         *  "Target": "This is the translated text", 
         *  "Segment":231,
         *  "DocumentName":"Anothertest.txt", 
         *  "SourceLanguage":"en-US", 
         *  "TargetLanguage":"de-de", 
         *  "Type":"Manual", 
         *  "Author":"A.Nonymous", 
         *  "DateTime":"20161013T152948Z", 
         *  "Markup":"EQFHTML3", 
         *  "Context":"", 
         *  "AddInfo":"" }  } 
         */
        //Out: { "ReturnValue":0, "ErrorMsg":"" }
        $json = $this->json(__FUNCTION__);
        
        $json->source = $source;
        $json->target = $target;

        //$json->segmentNumber = $segment->getSegmentNrInTask(); FIXME TRANSLATE-793 must be implemented first, since this is not segment in task, but segment in file
        $json->documentName = $filename;
        $json->author = $segment->getUserName();
        $json->timeStamp = $this->nowDate();
        $json->context = $segment->getMid();
        
        $json->type = "Manual";
        $json->markupTable = "OTMXUXLF"; //fixed markup table for our XLIFF subset
        
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        
        $lang->load($this->tmmt->getSourceLang());
        $json->sourceLang = $lang->getRfc5646();
        
        $lang->load($this->tmmt->getTargetLang());
        $json->targetLang = $lang->getRfc5646();
        
        $http = $this->getHttpWithMemory('POST', 'entry');
        $http->setRawData(json_encode($json), 'application/json; charset=utf-8');
        return $this->processResponse($this->request($http));
    }

    /**
     * returns the found errors
     */
    public function getErrors() {
        return $this->error;
    }
    
    /**
     * returns the raw response
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     * returns the decoded JSON result
     */
    public function getResult() {
        return $this->result;
    }
    
    /**
     * Creates a stdClass Object which is later converted to JSON for communication
     * @param string $method a method is always needed in the request JSON
     * @param string $memory optional, if given this is added as memory to the JSON
     * @return stdClass;
     */
    protected function json(string $method) {
        $result = new stdClass();
        $result->Method = $method;
        return $result;
    }
    
    /**
     * wraps the http request call to catch connection exceptions
     * @param Zend_Http_Client $http
     * @return Zend_Http_Response
     */
    protected function request(Zend_Http_Client $http) {
        try {
            return $http->request();
        }
        catch (Zend_Exception $e) {
            if(strpos($e->getMessage(), 'stream_socket_client(): unable to connect to tcp') !== 0) {
                throw $e;
            }
        }
        $badGateway = new ZfExtended_BadGateway('Die angefragte OpenTM2 Instanz ist nicht erreichbar', 0, $e);
        $badGateway->setOrigin('MatchResource OpenTM2');
        
        $error = new stdClass();
        $error->type = 'HTTP';
        $error->error = $e->getMessage();
        $error->url = $http->getUri(true);
        $error->method = $this->httpMethod;
        
        $badGateway->setErrors([$error]);
        throw $badGateway;
    }
    
    /**
     * parses and processes the response of OpenTM2, and handles the errors
     * @param Zend_Http_Response $response
     * @return boolean
     */
    protected function processResponse(Zend_Http_Response $response) {
        $this->response = $response;
        $validStates = [200, 201];
        
        $url = $this->http->getUri(true);
        
        //check for HTTP State (REST errors)
        if(!in_array($response->getStatus(), $validStates)) {
            $error = new stdClass();
            $error->type = 'HTTP';
            $error->error = $response->getStatus();
            $error->url = $url;
            $error->method = $this->httpMethod;
            $this->error[] = $error;
        }
        $result = json_decode(trim($response->getBody()));
        
        //check for JSON errors
        if(json_last_error() > 0){
            $error = new stdClass();
            $error->type = 'JSON';
            $error->error = json_last_error_msg();
            $error->url = $url;
            $error->method = $this->httpMethod;
            $this->error[] = $error;
            return false;
        }
        
        $this->result = $result;
        
        //check for error messages from body
        if(!empty($result->ReturnValue) && $result->ReturnValue > 0) {
            $error = new stdClass();
            $error->type = 'Error Nr. '.$result->ReturnValue;
            $error->error = $result->ErrorMsg;
            $error->url = $url;
            $error->method = $this->httpMethod;
            $this->error[] = $error;
        }
        
        return empty($this->error);
    }
    
    /**
     * returns the current time stamp in the expected format for OpenTM2
     */
    protected function nowDate() {
        return gmdate('Ymd\THis\Z');
    }
}
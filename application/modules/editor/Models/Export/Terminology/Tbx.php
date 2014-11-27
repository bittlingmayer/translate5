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
 * exports term data stored in translate5 to valid TBX files
 * implements editor_Models_Export_Terminology_Interface
 */
class editor_Models_Export_Terminology_Tbx implements editor_Models_Export_Terminology_Interface {
    /**
     * @var Iterator
     */
    protected $data;
    
    /**
     * Holds the XML Tree
     * @var SimpleXMLElement
     */
    protected $tbx;
    
    /**
     * @var string
     */
    protected $target;
    
    /**
     * @var array
     */
    protected $languageCache = array();
    
    /**
     * @var array
     */
    protected $statusMap;
    
    public function __construct() {
        $tbxImport = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $tbxImport editor_Models_Import_TermListParser_Tbx */
        $this->statusMap = array_flip($tbxImport->getStatusMap());
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Export_Terminology_Interface::setData()
     */
    public function setData(Iterator $data) {
        $this->data = $data;
    }
    
    /**
     * expects a TBX filename 
     * (non-PHPdoc)
     * @see editor_Models_Export_Terminology_Interface::setTarget()
     */
    public function setTarget($target) {
        $this->target = $target;
    }
    
    /**
     * creates the TBX Element and returns the body node to add data
     * @return SimpleXMLElement
     */
    protected function createTbx() {
        $this->tbx = new SimpleXMLElement('<martif/>');
        $this->tbx->addAttribute('noNamespaceSchemaLocation', 'TBXcsV02.xsd');
        $this->tbx->addAttribute('type', 'TBX');
        $this->tbx->addAttribute('TBX', 'en');
        $head = $this->tbx->addChild('martifHeader');
        $fileDesc = $head->addChild('fileDesc');
        $sourceDesc = $fileDesc->addChild('sourceDesc');
        $sourceDesc->addChild('p', 'TBX recovered from Translate5 DB '.date('Y-m-d H:i:s'));
        $text = $this->tbx->addChild('text');
        return $text->addChild('body');
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Export_Terminology_Interface::export()
     */
    public function export() {
        $body = $this->createTbx();
        
        //we assume that we got the data already sorted from DB
        $oldTermEntry = '';
        $oldLanguage = 0;
        foreach($this->data as $row) {
            if($oldTermEntry != $row->groupId) {
                $termEntry = $body->addChild('termEntry');
                $termEntry->addAttribute('id', $row->groupId);
                $oldTermEntry = $row->groupId;
            }
            if($oldLanguage != $row->language) {
                $langSet = $termEntry->addChild('langSet');
                $langSet->addAttribute('lang', $this->getLanguage($row->language));
                $oldLanguage = $row->language;
            }
            $tig = $langSet->addChild('tig');
            $tig->addAttribute('id', $this->convertToTigId($row->mid));
            
            $term = $tig->addChild('term', $row->term);
            $term->addAttribute('id', $row->mid);
            
            $termNote = $tig->addChild('termNote', $row->status); //FIXME Status gemapped???
            $termNote->addAttribute('type', 'normativeAuthorization');
        }
        $this->tbx->asXML($this->target);
    }
    
    /**
     * returns the Rfc5646 language code to the given language id
     * @param integer $langId
     * @return string
     */
    protected function getLanguage($langId) {
        if(empty($this->languageCache[$langId])) {
            $lang = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $lang editor_Models_Languages */
            $lang->load($langId);
            $this->languageCache[$langId] = $lang->getRfc5646();
        }
        return $this->languageCache[$langId];
    }
    
    /**
     * reverts the status mapping of the TBX Import
     * @param string $status
     */
    protected function getStatus($status) {
        if(empty($this->statusMap[$status])) {
            $default = $this->statusMap[editor_Models_Term::STAT_STANDARDIZED];
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError('Error on TBX creation, missing term status "'.$status.'", set to "'.$default.'" in file '.$this->target);
            return $default;
        }
        return $this->statusMap[$status];
    }
    
    /**
     * converts the given mid to a tig id
     * @param string $mid
     */
    protected function convertToTigId($mid) {
        if(strpos($mid, 'term') === false) {
            return 'tig_'.$mid;
        }
        return str_replace('term', 'tig', $mid);
    }
}

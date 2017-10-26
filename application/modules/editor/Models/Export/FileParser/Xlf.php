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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * 
 */
class editor_Models_Export_FileParser_Xlf extends editor_Models_Export_FileParser {

    /**
     * @var string Klassenname des Difftaggers
     */
    protected $_classNameDifftagger = 'editor_Models_Export_DiffTagger_Sdlxliff';
    
    /**
     * Helper to call namespace specfic parsing stuff 
     * @var editor_Models_Export_FileParser_Xlf_Namespaces
     */
    protected $namespaces;
    
    /**
     * übernimmt das eigentliche FileParsing
     *
     * - setzt an Stelle von <lekTargetSeg... wieder das überarbeitete Targetsegment ein
     * - befüllt $this->_exportFile
     */
    protected function parse() {
        $xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlparser editor_Models_Import_FileParser_XmlParser */
        
        //namespaces are not available until the xliff start tag was parsed!
        $xmlparser->registerElement('xliff', function($tag, $attributes, $key) use ($xmlparser){
            $this->namespaces = ZfExtended_Factory::get("editor_Models_Export_FileParser_Xlf_Namespaces",[$xmlparser->getChunk($key)]);
            $this->namespaces->registerParserHandler($xmlparser, $this->_task);
        });
        
        $xmlparser->registerElement('lekTargetSeg', null, function($tag, $key, $opener) use ($xmlparser){
            $attributes = $opener['attributes'];
            if(empty($attributes['id'])) {
                throw new Zend_Exception('Missing id attribute in '.$xmlparser->getChunk($key));
            }
            
            $id = $attributes['id'];
            //alternate field is optional, use target as default
            if(isset($attributes['field'])) {
                $field = $attributes['field'];
            }
            else {
                $field = editor_Models_SegmentField::TYPE_TARGET;
            }
            //$this->writeMatchRate(); refactor reapplyment of matchratio with XMLParser and namespace specific!
            $xmlparser->replaceChunk($key, $this->getSegmentContent($id, $field));
        });
        $this->_exportFile = $xmlparser->parse($this->_skeletonFile);
        
    }
    
    /**
     * dedicated to write the match-Rate to the right position in the target format
     * @param array $file that contains file as array as splitted by parse function
     * @param integer $i position of current segment in the file array
     * @return string
     * 
     */
    protected function writeMatchRate(array $file, integer $i) {
        // FIXME This code is disabled, because: 
        //  - the mid is not unique (due multiple files in the XLF) this code is buggy
        //  - the tmgr:matchratio should only be exported for OpenTM2 XLF and not in general
        //  - the preg_match is leading to above problems, it would be better to use the XMLParser here to, 
        //    and paste the new attributes on the parent trans-unit to one <lekSegmentPlaceholder>
        //
        //  SEE ALSO TRANSLATE-956
        //  must be implemented in editor_Models_Export_FileParser_Xlf_TmgrNamespace
        //
        return $file;
        
        $matchRate = $this->_segmentEntity->getMatchRate();
        $midArr = explode('_', $this->_segmentEntity->getMid());
        $mid = $midArr[0];
        $segPart =& $file[$i-1];
        //example string
        //<trans-unit id="3" translate="yes" tmgr:segstatus="XLATED" tmgr:matchinfo="AUTOSUBST" tmgr:matchratio="100">
        if(preg_match('#<trans-unit[^>]* id="'.$mid.'"[^>]*tmgr:matchratio="\d+"#', $segPart)===1){
            //if percent attribute is already defined
            $segPart = preg_replace('#(<trans-unit[^>]* id="'.$mid.'"[^>]*tmgr:matchratio=)"\d+"#', '\\1"'.$matchRate.'"', $segPart);
            return $file;
        }
        $segPart = preg_replace('#(<trans-unit[^>]* id="'.$mid.'" *)#', '\\1 tmgr:matchratio="'.$matchRate.'" ', $segPart);
        return $file;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Export_FileParser::getSegmentContent()
     */
    protected function getSegmentContent($segmentId, $field) {
        $content = parent::getSegmentContent($segmentId, $field);
        //without sub tags, no sub tags must be restored
        if(stripos($content, '<sub') === false) {
            return $content;
        }
        
        //get the transunit part of the root segment
        $transunitMid = $this->_segmentEntity->getMid();
        $transunitMid = explode('_', $transunitMid)[0]; 
        
        $xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlparser editor_Models_Import_FileParser_XmlParser */
        
        $xmlparser->registerElement('sub', function($tag, $attributes, $key) use($xmlparser){
            //disable handling of tags if we reach a sub, this is done recursivly in the loaded content of the found sub
            $xmlparser->disableHandlersUntilEndtag();
        }, function($tag, $key, $opener) use ($xmlparser, $transunitMid, $field){
            $tagId = $this->getParentTagId($xmlparser);
            if(empty($tagId)) {
                error_log("Could not restore sub tag content since there is no id in the surrounding <ph>,<bpt>,<ept>,<it> tag!"); //FIXME better logging
                return;
            }
            //now we need the segmentId to the found MID:
            // since the MID of a <sub> segment is defined as:
            // SEGTRANSUNITID _ SEGNR -sub- TAGID
            // and we have only the first and the last part, we have to use like to get the segmentId
            $s = $this->_segmentEntity->db->select('id')
                ->where('taskGuid = ?', $this->_taskGuid)
                ->where('mid like ?', $transunitMid.'_%-sub-'.$tagId);
            $segmentRow = $this->_segmentEntity->db->fetchRow($s);
            
            //if we got a segment we have to get its segmentContent and set it as the new content in our resulting XML
            // since we are calling getSegmentContent recursivly, the <sub> segments are replaced from innerst one out
            if($segmentRow) {
                //remove all chunks between the sub tag
                $xmlparser->replaceChunk($opener['openerKey']+1,'', $key-$opener['openerKey']-1);
                //fill one chunk with the segment content
                $xmlparser->replaceChunk($opener['openerKey']+1,$this->getSegmentContent($segmentRow->id, $field));
            }
        });
        return $xmlparser->parse($content);
    }
    
    /**
     * returns the parent tag id of the current SUB element, 
     *  since this ID is part of the Segment MID of the created segment for the sub element
     * @param editor_Models_Import_FileParser_XmlParser $xmlparser
     * @return string|NULL
     */
    protected function getParentTagId(editor_Models_Import_FileParser_XmlParser $xmlparser) {
        //loop through all valid parent tags 
        $validParents = ['ph[id]','it[id]','bpt[id]','ept[id]'];
        $parent = false;
        while(!$parent && !empty($validParents)) {
            $parent = $xmlparser->getParent(array_shift($validParents));
            if($parent) {
                //if we have found a valid parent (ID must be given) 
                // we create the same string as it was partly used for the segments MID
                return $parent['tag'].'-'.$parent['attributes']['id'];
            }
        }
        //without the parent id no further processing is possible for that segment 
        return null;
    }
}

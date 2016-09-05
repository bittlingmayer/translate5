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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * Enthält Methoden zum Fileparsing für den Export
 */
abstract class editor_Models_Export_FileParser {
    use editor_Models_Export_FileParser_MQMTrait;
    
    const REGEX_INTERNAL_TAGS = '#<div\s*class="([a-z]*)\s+([gxA-Fa-f0-9]*)"\s*.*?(?!</div>)<span[^>]*id="([^-]*)-.*?(?!</div>).</div>#s';
    
    /**
     * @var string
     */
    protected $_exportFile = NULL;
    /**
     * @var string
     */
    protected $_skeletonFile = NULL;
    /**
     * @var integer
     */
    protected $_fileId = NULL;
    /**
     * @var editor_Models_Segment aktuell bearbeitetes Segment
     */
    protected $_segmentEntity = NULL;
    /**
     * contains a limited amount of loaded segments
     * @var array
     */
    protected $segmentCache = array();
    /**
     * @var string Klassenname des Difftaggers
     */
    protected $_classNameDifftagger = NULL;
    /**
     * @var object 
     */
    protected $_difftagger = NULL;
    /**
     * @var boolean wether or not to include a diff about the changes in the exported segments
     *
     */
    protected $_diff= false;
    /**
     * @var editor_Models_Task current task
     */
    protected $_task;
    /**
     * @var string
     */
    protected $_taskGuid;
    /**
     * @var Zend_Config
     */
    protected $config;
    /**
     *
     * @var string path including filename, on which the exported file will be saved
     */
    protected $path;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * Disables the MQM Export if needed
     * @var boolean
     */
    protected $disableMqmExport = false;
    
    /**
     * Container for content tag protection
     * @var array
     */
    protected $originalTags;
    
    /**
     * each array element contains the comments for one segment
     * the array-index is set to an ID for the comments
     * @var array
     */
    protected $comments;
    
    /**
     * 
     * @param integer $fileId
     * @param boolean $diff
     * @param editor_Models_Task $task
     * @param string $path 
     * @throws Zend_Exception
     */
    public function __construct(integer $fileId,boolean $diff,editor_Models_Task $task, string $path) {
        if(is_null($this->_classNameDifftagger)){
            throw new Zend_Exception('$this->_classNameDifftagger muss in der Child-Klasse definiert sein.');
        }
        $this->_fileId = $fileId;
        $this->_diffTagger = ZfExtended_Factory::get($this->_classNameDifftagger);
        $this->_diff = $diff;
        $this->_task = $task;
        $this->_taskGuid = $task->getTaskGuid();
        $this->path = $path;
        $this->config = Zend_Registry::get('config');
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }

    /**
     * Gibt eine zu exportierende Datei bereits korrekt für den Export geparsed zurück
     * 
     * @return string file
     */
    public function getFile() {
        $this->getSkeleton();
        $this->parse();
        $this->convertEncoding();
        return $this->_exportFile;
    }
    
    public function saveFile() {
        file_put_contents($this->path, $this->getFile());
    }
    /**
     * übernimmt das eigentliche FileParsing
     *
     * - setzt an Stelle von <lekTargetSeg... wieder das überarbeitete Targetsegment ein
     * - befüllt $this->_exportFile
     */
    protected function parse() {
        $file = preg_split('#<lekTargetSeg([^>]+)/>#', $this->_skeletonFile, null, PREG_SPLIT_DELIM_CAPTURE);

        //reusable exception creation
        $exception = function($val) {
            $e  = 'Error in Export-Fileparsing. instead of a id="INT" and a optional ';
            $e .= 'field="STRING" attribute the following content was extracted: ' . $val;
            return new Zend_Exception($e);
        };
        
        $count = count($file) - 1;
        for ($i = 1; $i < $count;) {
            $file[$i] = $this->preProcessReplacement($file[$i]);
            if (!preg_match('#^\s*id="([^"]+)"\s*(field="([^"]+)"\s*)?$#', $file[$i], $matches)) {
                throw $exception($file[$i]);
            }
          
            //check $matches[1] for integer (segmentId) if empty throw an exception
            settype($matches[1], 'int');
            if(empty($matches[1])) {
                throw $exception($file[$i]);
            }
          
            //alternate column is optional, use target as default
            if(isset($matches[3])) {
                $field = $matches[3];
            }
            else {
              $field = editor_Models_SegmentField::TYPE_TARGET;
            }
          
            $file[$i] = $this->getSegmentContent($matches[1], $field);
            
            $file = $this->writeMatchRate($file,$i);
            
            if($this->config->runtimeOptions->editor->export->exportComments) {
                $commentsId = $this->getSegmentComments($matches[1]);
                
                if(!empty($commentsId)){
                    $file = $this->writeCommentGuidToSegment($file, $i, $commentsId);
                }
            }

            $i = $i + 2;
        }
        $this->_exportFile = implode('', $file);
    }
    
    /**
     * for overwriting purposes only
     * @param array $file
     * @param integer $i
     * @param type $id
     */
    protected function writeCommentGuidToSegment(array $file, integer $i, $id) {
    }
    
    /**
     * for setting $this-comments, if needed by the child class
     * for overwriting purposes only
     * @param integer $segmentId
     * @return string $id of comments index in $this->comments | null if no comments exist
     */
    protected function getSegmentComments(integer $segmentId) {
        return null;
    }
    
    /**
     * pre processor for the extracted lekTargetSeg attributes
     * for overwriting purposes only
     * @param string $attributes
     * @return string
     */
    protected function preProcessReplacement($attributes) {
        return $attributes;
    }
    
    /**
     * dedicated to write the match-Rate to the right position in the target format
     * for overwriting purposes only
     * @param array $file that contains file as array as splitted by parse function
     * @param integer $i position of current segment in the file array
     * @return string
     */
    protected function writeMatchRate(array $file, integer $i) {
        return $file;
    }
    
    /**
     * the browser adds non-breaking-spaces instead of normal spaces, if the user
     * types more than one space directly after eachother. For the GUI this
     * makes sense, because this way the whitespace can be presented in the 
     * correct visual form to the user (normal spaces would be shown as one
     * space in HTML). For the export they have to be reconverted to normal 
     * spaces
     * 
     * @param integer $segmentId
     * @param string $segment
     * @return string $segment
     */
    protected function revertNonBreakingSpaces($segment){
        //replacing nbsp introduced by browser back to multiple spaces
        return preg_replace('#\x{00a0}#u',' ',$segment);
    }
    /**
     * returns the segment content for the given segmentId and field. Adds optional diff markup, and handles tags.
     * @param integer $segmentId
     * @param string $field fieldname to get the content from
     * @return string
     */
    protected function getSegmentContent($segmentId, $field) {
        $this->_segmentEntity = $segment = $this->getSegment($segmentId);
        
        $edited = (string) $segment->getFieldEdited($field);
        
        $before = $edited;
        $edited = $this->protectContentTags($edited);
        $edited = $this->recreateTermTags($edited, $this->shouldTermTaggingBeRemoved());
        $edited = $this->unprotectContentTags($edited);
        
        $edited = $this->parseSegment($edited);
        $edited = $this->revertNonBreakingSpaces($edited);
        if(!$this->_diff){
            return $this->unprotectWhitespace($edited);
        }
        
        $original = (string) $segment->getFieldOriginal($field);
        $original = $this->protectContentTags($original);
        $original = $this->recreateTermTags($original);
        $original = $this->unprotectContentTags($original);
        $original = $this->parseSegment($original);
        
        $diffed = $this->_diffTagger->diffSegment(
                $original,
                $edited,
                $segment->getTimestamp(),
                $segment->getUserName());
        // unprotectWhitespace must be done after diffing!
        return $this->unprotectWhitespace($diffed);
    }
    
    /**
     * @return boolean
     */
    protected function shouldTermTaggingBeRemoved() {
        $exportTermTags = $this->config->runtimeOptions->termTagger->exportTermTags;
        $exportTermTags = $this->_diff ? $exportTermTags->diffExport : $exportTermTags->normalExport;
        return !$exportTermTags;
    }
    /**
     * loads the segment to the given Id, caches a limited count of segments internally 
     * to prevent loading again while switching between fields
     * @param integer $segmentId
     * @return editor_Models_Segment
     */
    protected function getSegment($segmentId){
        if(isset($this->segmentCache[$segmentId])) {
            return $this->segmentCache[$segmentId];
        }
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        $this->segmentCache[$segmentId] = $segment;
        //we keep a max of 50 segments, this should be enough
        if(count($this->segmentCache) > 50) {
            reset($this->segmentCache);
            $firstKey = key($this->segmentCache);
            unset($this->segmentCache[$firstKey]);
        }
        return $segment;
    }
    
    /**
     * creates termMarkup according to xliff-Syntax (<mrk ...) 
     * 
     * converts from:
     * <div class="term admittedTerm transNotFound" id="term_05_1_de_1_00010-0" title="">Hause</div>
     * to:
     * <mrk mtype="x-term-admittedTerm" mid="term_05_1_de_1_00010">Hause</mrk>
     * and removes the information about trans[Not]Found
     * 
     * @param string $segment
     * @param boolean $removeTermTags, default = true
     * @return string $segment
     */
    protected function recreateTermTags($segment, $removeTermTags=true) {
        $toRemove = array('transFound', 'transNotFound', 'transNotDefined');
        
        //replace or remove closing tags
        $closingTag =  $removeTermTags ? '' : '</mrk>';
        $segment = str_ireplace('</div>', $closingTag, $segment);
        
        $termRegex = '/<div[^>]+class="term([^"]+)"\s+data-tbxid="([^"]+)"[^>]*>/s';
        return preg_replace_callback($termRegex, function($match) use ($removeTermTags, $toRemove) {
            if($removeTermTags) {
                return '';
            }
            
            $mid = $match[2];
            $classes = explode(' ', trim($match[1]));
            $classes = join('-', array_diff($classes, $toRemove));
            return '<mrk mtype="x-term-' . $classes . '" mid="' . $mid . '">';
        }, $segment);
    }
    
    /**
     * protects the internal tags of one segment, stores the original values in $this->originalTags
     * @param unknown $segment
     * @return string
     */
    protected function protectContentTags(string $segment) {
        $id = 1;
        $this->originalTags = array();
        return preg_replace_callback(self::REGEX_INTERNAL_TAGS, function($match) use (&$id) {
            $placeholder = '<translate5:escaped id="'.$id++.'" />';
            $this->originalTags[$placeholder] = $match[0];
            return $placeholder;
        }, $segment);
    }
    
    /**
     * unprotects / restores the content tags
     * @param string $segment
     * @return string
     */
    protected function unProtectContentTags(string $segment) {
        return preg_replace_callback('#<translate5:escaped id="[0-9]+" />#s', function($match) {
            return $this->originalTags[$match[0]];
        }, $segment);
    }
    
    /**
     * sets $this->_skeletonFile
     */
    protected function getSkeleton() {
        $skel = ZfExtended_Factory::get('editor_Models_Skeletonfile');
        $skel->loadRow('fileId = ?', $this->_fileId);
        $this->_skeletonFile = $skel->getFile();
    }

    /**
     * Rekonstruiert in einem Segment die ursprüngliche Form der enthaltenen Tags
     *
     * @param string $segment
     * @return string $segment 
     */
    protected function parseSegment($segment) {
        $segmentArr = preg_split(self::REGEX_INTERNAL_TAGS, $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($segmentArr);
        for ($i = 1; $i < $count;) {
            $j = $i + 2;
            // detect if single-tag is regex-tag, if not capsule result with brackets (= normal behavior)
            $isRegexTag = $segmentArr[$i+2] == "regex";
            $segmentArr[$i] = pack('H*', $segmentArr[$i + 1]);
            if (!$isRegexTag) {
                //the following search and replace is needed for TRANSLATE-464
                //backwards compatibility of already imported tasks
                $search = array('hardReturn /','softReturn /','macReturn /');
                $replace = array('hardReturn/','softReturn/','macReturn/');
                $segmentArr[$i] = str_replace($search, $replace, $segmentArr[$i]);
                $segmentArr[$i] = '<' . $segmentArr[$i] .'>';
            }
            
            unset($segmentArr[$j]);
            unset($segmentArr[$i + 1]);
            $i = $i + 4;
        }
        return implode('', $segmentArr);
    }
    
    /**
     * - converts $this->_exportFile back to the original encoding registered in the LEK_files
     */
    protected function convertEncoding(){
        $file = ZfExtended_Factory::get('editor_Models_File');
        $file->load($this->_fileId);
        $enc = $file->getEncoding();
        if(is_null($enc) || $enc === '' || strtolower($enc) === 'utf-8')return;
        $this->_exportFile = iconv('utf-8', $enc, $this->_exportFile);
    }
    
    /**
     * Exports a single segment content, without MQM support!
     * @param string $segment
     * @return string
     */
    public function exportSingleSegmentContent($segment) {
        $this->disableMqmExport = true;
        
        //protect content tags to do nasty things with the content and prevent our html like content tags to be damaged
        $segment = $this->protectContentTags($segment);
        
        //do here segment things where contentTags are needed to be protected
        $segment = $this->recreateTermTags($segment);
        
        //unprotect / restore content tags
        $segment = $this->unprotectContentTags($segment);
        //FIXME: unprotect tags and final tag replacement in parseSegment can and should be merged
        
        $segment = $this->parseSegment($segment);
        $segment = $this->revertNonBreakingSpaces($segment);
        return $this->unprotectWhitespace($segment);
    }
    
    /**
     * unprotects tag protected whitespace inside the given segment content
     * keep attention to the different invocation points for this method!
     * @param string $content
     * @return string
     */
    protected function unprotectWhitespace($content) {
        $search = array(
          '<hardReturn/>',
          '<softReturn/>',
          '<macReturn/>',
          '<hardReturn />',
          '<softReturn />',
          '<macReturn />',
        );
        $replace = array(
          "\r\n",  
          "\n",  
          "\r",
          "\r\n",  
          "\n",  
          "\r",
        );
        $content = str_replace($search, $replace, $content);
        return preg_replace_callback('"<space ts=\"([A-Fa-f0-9]*)\"/>"', function ($match) {
                    return pack('H*', $match[1]);
                }, $content);
    }
}

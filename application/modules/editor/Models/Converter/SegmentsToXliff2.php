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

class editor_Models_Converter_SegmentsToXliff2 extends editor_Models_Converter_SegmentsToXliffAbstract {
    
    
    protected $itsPerson=null;
    protected $itsPersonRef=null;
    protected $itsRevPerson=null;
    protected $itsRevPersonRef=null;
    
    
    /***
     * Unsupported configs:
     * 
     *      CONFIG_ADD_RELAIS_LANGUAGE,
     *      CONFIG_INCLUDE_DIFF,
     *      CONFIG_ADD_ALTERNATIVES
     * 
     */
    protected $unsupportedConfigs=[
    ];
    
    /***
     * Finished workflow step
     * 
     * @var string
     */
    public $workflowStep=null;
    
    /***
     * xlif2 segment state
     * @var string
     */
    const XLIFF2_SEGMENT_STATE_INITIAL='initial';
    
    /***
     * xlif2 segment state
     * @var string
     */
    const XLIFF2_SEGMENT_STATE_TRANSLATED='translated';
    
    /***
     * xlif2 segment state
     * @var string
     */
    const XLIFF2_SEGMENT_STATE_REVIEWED='reviewed';
    
    /***
     * xlif2 segment state
     * @var string
     */
    const XLIFF2_SEGMENT_STATE_FINAL='final';
    
    /***
      Mapping of translate5 autostates to xliff 2.x default segment state is as follows:
			translate5 autostatus			->	mapped xliff status
			--------------------------------------------------------
			untranslated		 			-> initial
			blocked							-> initial
			
			translated						-> translated
			auto-translated					-> translated
			
			auto-reviewed					-> reviewed
			reviewed, untouched, auto-set	-> reviewed
			reviewed, untouched				-> reviewed
			auto-reviewed, untouched		-> reviewed
			
			translator reviewed				-> final
			translator autoreviewed			-> final
			PM reviewed						-> final
			PM auto-reviewed				-> final
			PM reviewed, unchanged			-> final
			PM auto-reviewed, unchanged 	-> final
       @var array
     */
    protected $segmentStateMap=[
            editor_Models_Segment_AutoStates::REVIEWED=>self::XLIFF2_SEGMENT_STATE_REVIEWED,
            editor_Models_Segment_AutoStates::NOT_TRANSLATED=>self::XLIFF2_SEGMENT_STATE_INITIAL,
            editor_Models_Segment_AutoStates::BLOCKED=>self::XLIFF2_SEGMENT_STATE_INITIAL,
            
            editor_Models_Segment_AutoStates::TRANSLATED=>self::XLIFF2_SEGMENT_STATE_TRANSLATED,
            editor_Models_Segment_AutoStates::TRANSLATED_AUTO=>self::XLIFF2_SEGMENT_STATE_TRANSLATED,
            
            editor_Models_Segment_AutoStates::REVIEWED_AUTO=>self::XLIFF2_SEGMENT_STATE_REVIEWED,
            editor_Models_Segment_AutoStates::REVIEWED_UNTOUCHED=>self::XLIFF2_SEGMENT_STATE_REVIEWED,
            editor_Models_Segment_AutoStates::REVIEWED_UNCHANGED=>self::XLIFF2_SEGMENT_STATE_REVIEWED,
            editor_Models_Segment_AutoStates::REVIEWED_UNCHANGED_AUTO=>self::XLIFF2_SEGMENT_STATE_REVIEWED,
            
            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR_AUTO=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_PM=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_PM_AUTO=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_PM_UNCHANGED=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_PM_UNCHANGED_AUTO=>self::XLIFF2_SEGMENT_STATE_FINAL,
    ];
    
    /**
     * @var Zend_Config
     */
    protected $config;
    
    
    
    
    public function __construct(array $config = []){
        $this->initUnsupportedConfig();
        parent::__construct($config);
    }
    
    /**
     * For the options see the constructor
     * @see self::__construct
     * @param array $config
     */
    public function setOptions(array $config) {
        parent::setOptions($config);
        
        if(!empty($this->options)){
            foreach ($this->options as $op=>$value){
                if(in_array($op, $this->unsupportedConfigs)){
                    error_log("The config variable ".$op." is unsupported in xliff v 2.1 and it will take no effect over generated xliff");
                }
            }
        }
        //flags defaulting to false
        $defaultsToFalse = [
                self::CONFIG_INCLUDE_DIFF,//TODO: unsupported in 2.1 ? -> replaced with track changes export
                //if this is active, track changes are exported as mrk tag, if not we remove the track changes content
                
                self::CONFIG_ADD_TERMINOLOGY,//TODO: supported
        ];
        foreach($defaultsToFalse as $key){
            settype($this->options[$key], 'bool');
        }
        
        //flags defaulting to true; if nothing given, empty is the falsy check
        $defaultsToTrue = [
                self::CONFIG_ADD_COMMENTS,
                self::CONFIG_ADD_STATE_QM,//TODO: valid ?
                self::CONFIG_ADD_DISCLAIMER,//TODO: Ask Marc about additional disclamer in the new xliff 
        ];
        foreach($defaultsToTrue as $key){
            $this->options[$key] = !(array_key_exists($key, $config) && empty($config[$key]));
        }
        
        if($this->options[self::CONFIG_ADD_TERMINOLOGY]) {
            $this->initTagHelper();
        }
    }
    
    
    /**
     * converts a list with segment data to xml (xliff2)
     *
     *
     * @param editor_Models_Task $task
     * @param array $segments
     */
    public function convert(editor_Models_Task $task, array $segments) {
        $this->result = array('<?xml version="1.0" encoding="UTF-8"?>');
        $this->task = $task;
        $allSegmentsByFile = $this->reorderByFilename($segments);
        
        $this->initConvertionData();
        
        $this->createXmlHeader();
        
        foreach($allSegmentsByFile as $filename => $segmentsOfFile) {
            $this->processAllSegments($filename, $segmentsOfFile);
        }
        
        //XML Footer, no extra method
        $this->result[] = '</xliff>';
        
        $xml = join("\n", $this->result);
        
        return $xml;
    }
    
    protected function initConvertionData(){
        parent::initConvertionData();
        $assocModel=ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $assocModel editor_Models_TaskUserAssoc */
        $assocUsers=$assocModel->loadByTaskGuidList([$this->task->getTaskGuid()]);
        $this->data['assocUsers']=[];
        
        $tmpUser=[];
        foreach($assocUsers as $user){
            if(!$user['role'] || $user['role']===editor_Workflow_Abstract::ROLE_VISITOR || $user['role']===''){
                continue;
            }
            $this->data['assocUsers'][$user['role']][]=$user;
        }
        
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $allUsers=$userModel->loadAll();
        
        array_map(function($usr){
            $this->data['users'][$usr['userGuid']]=$usr;
            
        },$allUsers);
        
        $this->config = Zend_Registry::get('config');
        $rop = $this->config->runtimeOptions;
        $manualStates = $rop->segments->stateFlags->toArray();
        $this->data['manualStatus']=$manualStates;
    }
    /**
     * Helper function to create the XML Header
     */
    public function createXmlHeader() {
        $headParams = array('xliff', 'version="2.0"');
        
        $headParams[] = 'xmlns="urn:oasis:names:tc:xliff:document:2.0"';
        
        $headParams[] = 'xmlns:its="https://www.w3.org/2005/11/its/"';
        $this->enabledNamespaces['its'] = 'its';
        
        $headParams[] = 'xmlns:translate5="http://www.translate5.net/"';
        $this->enabledNamespaces['translate5'] = 'translate5';
        
        $headParams[] = 'srcLang="'.htmlspecialchars($this->task->getSourceLang()).'"';
        $headParams[] = 'trgLang="'.htmlspecialchars($this->task->getTargetLang()).'"';
        
        $headParams[] = 'translate5:taskguid="'.htmlspecialchars($this->task->getTaskGuid()).'"';
        $headParams[] = 'translate5:taskname="'.htmlspecialchars($this->task->getTaskName()).'"';
        $this->result[] = '<'.join(' ', $headParams).'>';
        
        $this->result[] = '<!-- For attributes or elements in translate5 that have no matching xliff 2 representation are the translate5 namespace is used -->';
        $this->result[] = '<!-- the file id reflects the fileid in LEK_segments table of translate5 -->';
    }
    
    
    /**
     * process and convert all segments to xliff
     * @param string $filename
     * @param array $segmentsOfFile
     */
    protected function processAllSegments($filename, array $segmentsOfFile) {
        if(empty($segmentsOfFile)) {
            return;
        }
        
        $this->exportParser = $this->getExportFileparser($segmentsOfFile[0]['fileId'], $filename);
        $file = '<file id="%s">';
        $this->result[] = sprintf($file,$segmentsOfFile[0]['fileId']);
        
        $this->addUnitComment();
        
        foreach($segmentsOfFile as $segment) {
            $this->processSegmentsOfFile($segment);
        }
        
        $this->result[] = '</file>';
    }
    
    /**
     * process and convert the segments of one file to xliff
     * @param array $segment
     */
    protected function processSegmentsOfFile($segment) {
        
        if(!empty($this->data['assocUsers'])){
            $this->initItsPersonRef($segment);
            $this->initItsRefPersonRef($segment);
        }
        
        $unitTag[]='unit';
        $unitTag[]='id="'.$segment['segmentNrInTask'].'"';
        
        if($this->itsPerson){
            $unitTag[]='person="'.$this->itsPerson.'"';
        }
        
        if($this->itsPersonRef){
            $unitTag[]='personRef="'.$this->itsPersonRef.'"';
        }
        
        if($this->itsRevPerson){
            $unitTag[]='revPerson="'.$this->itsRevPerson.'"';
        }
        
        if($this->itsRevPersonRef){
            $unitTag[]='revPersonRef="'.$this->itsRevPersonRef.'"';
        }
        
        //state start
        if(isset($this->data['manualStatus'][$segment['stateId']])) {
            $stateText =  $this->data['manualStatus'][$segment['stateId']];
            $unitTag[]='translate5:manualStatus="'.$stateText.'" '.'translate5:manualStatusId="'.$segment['stateId'].'"';
        }
        
        /*
         * <!-- attention: regarding internal tags the source and the target-content are in the same format as the contents of the original source formats would have been. For SDLXLIFF this means: No mqm-Tags; Terms marked with <mrk type="x-term-...">-Tags; Internal Tags marked with g- and x-tags; For CSV this means: No internal tags except mqm-tags -->
         */

        $this->result[] = '<'.join(' ', $unitTag).'>';
        
        if(!empty($segment['comments']) && $this->options[self::CONFIG_ADD_COMMENTS]) {
            $this->processComment($segment);
        }
        
        //according to the spec, the prefix "translate5Autostate:" must always be shown in the subState value.
        $stateText="";
        if(isset($this->data['autostates'][$segment['autoStateId']])) {
            $stateText =  $this->data['autostates'][$segment['autoStateId']];
        }
        
        $this->result[] = '<segment id="'.$segment['segmentNrInTask'].'" state="'.$this->segmentStateMap[$segment['autoStateId']].'" subState="translate5Autostate:'.$stateText.'">';
        
        $this->result[] = '<source>'.$this->prepareText($segment[$this->data['firstSource']]).'</source>';//FIXME: talk with Thomas
        
        $fields = $this->sfm->getFieldList();
        foreach($fields as $field) {
            $this->processSegmentField($field, $segment);
        }
        
        //FIXME see the example comment about qm, i think this needs to be inside the target text
        //TODO: i waith a response from Marc how the qm should be handled
        if($this->options[self::CONFIG_ADD_STATE_QM]) {
            $this->processQm($segment);
        }
        
        $this->result[] = '</segment>';
        $this->result[] = '</unit>';
    }
    
    /**
     * process and convert the segment comments
     * @param array $segment
     */
    protected function processComment(array $segment) {
        $comments = $this->comment->loadBySegmentAndTaskPlain((integer)$segment['id'], $this->task->getTaskGuid());
        $this->result[] = '<!-- the note id reflects the id in LEK_comments table of translate5 -->';
        $note = '<note id="%1$s" translate5:userGuid="%2$s" translate5:username="%3$s" translate5:created="%4$s" translate5:modified="%5$s">%6$s</note>';
        $this->result[] = '<notes>';
        foreach($comments as $comment) {
            $modified = new DateTime($comment['modified']);
            $created  = new DateTime($comment['created']);
            //if the +0200 at the end makes trouble use the following
            //gmdate('Y-m-d\TH:i:s\Z', $modified->getTimestamp());
            $modified = $modified->format($modified::ATOM);
            $created= $created->format($created::ATOM);
            $this->result[] = sprintf($note,
                                      htmlspecialchars($comment['id']),
                                      htmlspecialchars($comment['userGuid']),
                                      htmlspecialchars($comment['userName']),
                                      $created,
                                      $modified, 
                                      htmlspecialchars($comment['comment']));
        }
        $this->result[] = '</notes>';
    }
    
    /**
     * process and convert the segments of one file to xliff
     * @param Zend_Db_Table_Row $field
     * @param array $segment
     */
    protected function processSegmentField(Zend_Db_Table_Row $field, array $segment) {
        if($field->type == editor_Models_SegmentField::TYPE_SOURCE) {
            return; //handled before
        }
        if($field->type == editor_Models_SegmentField::TYPE_RELAIS && $this->data['relaisLang'] !== false) {
            return;
        }
        if($field->type != editor_Models_SegmentField::TYPE_TARGET) {
            return;
        }
        
        
        $lang = $this->data['targetLang'];
        if($this->data['firstTarget'] == $field->name) {
            $altTransName = $field->name;
            $targetEdit = $this->prepareText($segment[$this->sfm->getEditIndex($this->data['firstTarget'])]);

            $this->result[] = '<target>';
            $this->result[] = $targetEdit;
            $this->result[] = '</target>';
        }
    }
    
    protected function initItsPersonRef($segment){
        $assocUsers=$this->data['assocUsers'];
        
        if(!isset($assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATOR]) || !isset($assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATORCHECK])){
            return;
        }
        
        $tmpTranslatorArray=[];
        $this->itsPerson=null;
        $this->itsPersonRef=null;
        
        //proofreader==lector
        
        //if only one translator
        if(count($assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATOR])==1){
            $this->itsPersonRef=$assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATOR][0]['userGuid'];
        }else if(count($assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATOR])==0){//if the there is no translator, check for translatorCheck
            
            //if only one translatorCheck
            if(count($assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATORCHECK])==1){
                $this->itsPersonRef=$assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATORCHECK][0]['userGuid'];
            }else if(count($assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATORCHECK])>1){//more than one tanslator check
                $tmpTranslatorArray=$assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATORCHECK];
            }
        }else{//more than one translator
            $tmpTranslatorArray=$assocUsers[editor_Workflow_Abstract::ROLE_TRANSLATOR];
        }
        
        if($this->itsPersonRef){
            $usr=$this->data['users'][$this->itsPersonRef];
            $this->itsPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        
        if(empty($tmpTranslatorArray)){
            return;
        }
        
        //check last editor
        foreach ($tmpTranslatorArray as $translator){
            if($translator['userGuid']===$segment['userGuid']){
                $this->itsPersonRef=$segment['userGuid'];
                break;
            }
        }
        
        if($this->itsPersonRef){
            $usr=$this->data['users'][$this->itsPersonRef];
            $this->itsPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        //it is no user that is assigned to the task, it can be pm
        if($segment['userGuid'] === $this->task->getPmGuid()){
            //FIXME
            //If the workflow step that is currently finishd is translation or translator-check, the PM is used for its:person. If the current workflow step is proofreading, than the project manager is used for its:revPerson
            if($this->workflowStep===editor_Workflow_Abstract::STEP_TRANSLATION || $this->workflowStep===editor_Workflow_Abstract::STEP_TRANSLATORCHECK){
                $this->itsPersonRef=$this->task->getPmGuid();
            }
            
            if($segment['workflowStep']===editor_Workflow_Abstract::STEP_LECTORING){
                $this->revPersonRef=$this->task->getPmGuid();
            }
        }
        
        if($this->itsPersonRef){
            $usr=$this->data['users'][$this->itsPersonRef];
            $this->itsPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        
        $this->itsPersonRef='undefined';
        $this->itsPerson='undefined';
    }
    
    protected function initItsRefPersonRef($segment){
        $assocUsers=$this->data['assocUsers'];
        $tmpProofreaderArray=[];
        $this->itsRevPerson=null;
        $this->itsRevPersonRef=null;
        
        //proofreader==lector
        if(!isset($assocUsers[editor_Workflow_Abstract::ROLE_LECTOR])){
            return;
        }
        
        if(count($assocUsers[editor_Workflow_Abstract::ROLE_LECTOR])==0){
            return;
        }
        
        //if only one proofreader
        if(count($assocUsers[editor_Workflow_Abstract::ROLE_LECTOR])==1){
            $this->itsRevPersonRef=$assocUsers[editor_Workflow_Abstract::ROLE_LECTOR][0]['userGuid'];
            
            $usr=$this->data['users'][$this->itsRevPersonRef];
            $this->itsRevPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        
        $tmpProofreaderArray=$assocUsers[editor_Workflow_Abstract::ROLE_LECTOR];
        
        //check last editor
        foreach ($tmpProofreaderArray as $proofreader){
            if($proofreader['userGuid']===$segment['userGuid']){
                $this->itsRevPersonRef=$segment['userGuid'];
                break;
            }
        }
        
        if($this->itsRevPersonRef){
            $usr=$this->data['users'][$this->itsRevPersonRef];
            $this->itsRevPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        
        //it is no user that is assigned to the task, it can be pm
        if($segment['userGuid'] === $this->task->getPmGuid()){
            if($this->task->getWorkflowStepName()===editor_Workflow_Abstract::STEP_LECTORING){
                $this->itsRevPersonRef=$this->task->getPmGuid();
            }
        }
        
        if($this->itsRevPersonRef){
            $usr=$this->data['users'][$this->itsRevPersonRef];
            $this->itsRevPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        $this->itsRevPersonRef='undefined';
        $this->itsRevPerson='undefined';
    }
    
    /**
     * prepares segment text parts for xml
     * @param string $text
     * @return string
     */
    protected function prepareText($text) {
        //if active, track changes are exported as mrk tag, if not we remove the track changes content
        if($this->options[self::CONFIG_INCLUDE_DIFF]){
            $text=$this->cleanTrackChanges($text);
        }else{
            //TODO: track changes as mrk tags ? Talk with Thomas
            $text=$this->trackChangesAsMrk($text);
        }
        
        //if plain internal tags are disabled:
        // 1. toXliff converts the internal tags to xliff g,bx,ex and x tags
        // 2. remove MQM tags
        //TODO MQM tags are just removed and not supported by our XLIFF exporter so far!
        $text = $this->taghelperInternal->toXliffPaired($text, true, $this->tagMap, $this->tagId);
        $text = $this->handleTerminology($text, false); //internaltag replacment not needed, since already converted
        $text = $this->taghelperMqm->remove($text);
        return $text;
    }
    
    /**
     */
    protected function handleTerminology($text, $protectInternalTags) {
        if(!$this->options[self::CONFIG_ADD_TERMINOLOGY]){
            return $this->taghelperTerm->remove($text);
        }
        $termStatus = editor_Models_Term::getAllStatus();
        $transStatus = [
                editor_Models_Term::TRANSSTAT_FOUND => 'found',
                editor_Models_Term::TRANSSTAT_NOT_FOUND => 'notfound',
                editor_Models_Term::TRANSSTAT_NOT_DEFINED => 'undefined',
        ];
        //to get the definition value we need the title:
        //     TERMTAGGER-33: term-definition is not passed in tagged return
        //
        return $this->taghelperTerm->replace($text, function($wholeMatch, $tbxId, $classes) use ($termStatus, $transStatus) {
            //in the whole match there is a title, use regex to get the value =value="termDefinitionInLEK_Term__OmitAttributeIfEmpty"
            //the mid_in_LEK_termsis  $tbxId
            //if no title or the title is empty, do not add the value field
            $status = '';
            $translation = '';
            foreach($classes as $class) {
                if($class == editor_Models_Term::CSS_TERM_IDENTIFIER) {
                    continue;
                }
                if(in_array($class, $termStatus)) {
                    $status = $class;
                    continue;
                }
                
                if(!empty($transStatus[$class])) {
                    $translation = ' translate5:translated="'.$transStatus[$class].'"';
                }
            }
            //<mrk id="mid_in_LEK_terms" value="termDefinitionInLEK_Term__OmitAttributeIfEmpty">
            //type,status and translated are the same as in 1.2
            return '<mrk id="'.$tbxId.'" type="term" value="termDefinitionInLEK_Term__OmitAttributeIfEmpty" translate5:status="'.$status.'"'.$translation.'>';
        }, '</mrk>', $protectInternalTags);
    }
    
    
    protected function initUnsupportedConfig(){
        $this->unsupportedConfigs=[
            self::CONFIG_ADD_RELAIS_LANGUAGE,
            self::CONFIG_ADD_ALTERNATIVES,
            self::CONFIG_PLAIN_INTERNAL_TAGS,
            self::CONFIG_ADD_PREVIOUS_VERSION
        ];
    }
    
    protected function cleanTrackChanges($text){
        $text= preg_replace(editor_Models_Segment_TermTagTrackChange::REGEX_DEL, '', $text);
        $text= preg_replace(editor_Models_Segment_TermTagTrackChange::REGEX_INS, '', $text);
        return $text;
    }
    
    protected function trackChangesAsMrk($text){
        return $text;
    }
    
    
    /**
     * process and convert the segment states and QM states
     * @param array $segment
     */
    protected function processQm(array $segment) {
        $qms = $this->segmentUtility->convertQmIds($segment['qmId']);
        if(!empty($qms)) {
            $this->result[] = '<dx:qa-hits>';
            $qmXml = '<dx:qa-hit dx:qa-origin="target" dx:qa-code="%1$s" dx:qa-shorttext="%2$s" />';
            foreach ($qms as $qmid => $qm) {
                $this->result[] = sprintf($qmXml, $qmid, $qm);
            }
            $this->result[] = '</dx:qa-hits>';
        }
    }
    
    private function addUnitComment(){
        
        $unitComment[]='<!-- unit id is the segmentNrInTask of LEK_segment in translate5;';
        
        $unitComment[]='its:person is the translator name, if assigned in translate5; if no translator is assigned, it is the user of the translator-check;';
        $unitComment[]='its:personRef is the corresponding userGuid of the person-attribute';
        $unitComment[]='its:revPerson is the proofreader, if assigned in translate5;';
        $unitComment[]='its:revPersonRef is the userGuid of the proofreader;';
        
        $unitComment[]='- If more than one proofreader or translator is assigned, the above attributes refer to the translator or proofreader that edited the segment the last time / set the autostatus flag the last time;';
        $unitComment[]='- If the last editor is no person, that is assigned to the task, it may be the project manager.';
        $unitComment[]='If it is the project manager, the project manager of the task is used in the following way:';
        $unitComment[]='If the workflow step that is currently finishd is translation or translator-check, the PM is used for its:person.';
        $unitComment[]='If the current workflow step is proofreading, than the project manager is used for its:revPerson';
        
        $unitComment[]='- If the last editor of a segment is no assigned user and not the PM, but we have more than one user assigned for a role, than we use the value "undefined".';
        
        $unitComment[]='if no user is assigned for a role, we omit the attribute (be it its:person or its:revPerson).';
        
        $unitComment[]='translate5 autostates show the segment state more in detail, than xliff 2 is able to. Autostates are mapped to xliff 2 segment state as best as possible and also shown in translate5:autostate attribute';
        $unitComment[]='In xliff 2.1 generated by translate5 a unit will always only contain one segment, since xliff 2 does not allow custom attributes on segment level, yet translate5 needs autostates there';
        
        $unitComment[]='translate5:manualStatus is omitted, if empty';
        $unitComment[]='-->';
        
        $this->result[] =join("\n", $unitComment);
    }
}
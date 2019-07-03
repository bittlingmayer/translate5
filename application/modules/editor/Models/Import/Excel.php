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
 * Import the whole task from an earlier exported Excel-file
 */
class editor_Models_Import_Excel extends editor_Models_Excel_AbstractExImport {
    /**
     * @var editor_Models_Excel_ExImport
     */
    protected $excel;
    
    /**
     * A list of segment-numbers and notices about the segment (e.g. invalid tag-structure in segment).
     * This list is shown after the reimport with the hint that the user has to check the here notet segments.
     * 
     * @var array
     */
    protected $segmentError = [];
    
    /**
     * reimport $filename xls into $task.
     * the fiel $filename is located inside the /data/importedTasks/<taskGuid>/excelReimport/ folder
     * returns TRUE if everything is OK, FALSE on (fatal) error
     * @param editor_Models_Task $task
     * @param string $filename
     * @return bool
     */
    public function run(editor_Models_Task $task, $filename) : bool {
        // task data must be actualized
        $task->createMaterializedView();
        
        // load the excel
        $this->excel = editor_Models_Excel_ExImport::loadFromExcel($task->getAbsoluteTaskDataPath().'/excelReimport/'.$filename);
        
        // do formal checkings of the loaded excel data aginst the task
        // on error an editor_Models_Excel_ExImportException is thrown
        $this->formalCheck($task);
        
        // load required ressources:
        // - load the model that handles the t5 segments
        $t5Segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $t5Segment editor_Models_Segment */
        
        // - load segment tagger to extract pure text from t5Segment
        $segmentTagger = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        /* @var $segmentTagger editor_Models_Segment_InternalTag */
        
        // - load diffTagger for markup changes with TrackChanges Markup
        $diffTagger = ZfExtended_Factory::get('editor_Models_Export_DiffTagger_TrackChanges', [$task]);
        /* @var $diffTagger editor_Models_Export_DiffTagger_TrackChanges */
        
        // - load tag structure checker
        $tagStructureChecker = ZfExtended_Factory::get('editor_Models_Excel_TagStructureChecker');
        /* @var $tagStructureChecker editor_Models_Excel_TagStructureChecker */
        
        
        // now handle each segment from the excel
        foreach ($this->excel->getSegments() as $segment) {
            // new segement is the one from excel
            $newSegment = $segment->target;
            
            // detect $orgSegmentAsExcel as content of the t5 target segment
            $t5Segment->loadBySegmentNrInTask($segment->nr, $task->getTaskGuid());
            $orgSegmentAsExcel = $segmentTagger->toExcel($t5Segment->getTargetEdit());
            
            
            // do nothing if segment has not changed
            if ($newSegment == $orgSegmentAsExcel) {
                if (!empty($segment->comment)) {
                    $this->addComment($segment->comment, $t5Segment->getId(), $task);
                }
                continue;
            }
            
            // check structure of the new segment (from excel)
            if (!$tagStructureChecker->check($newSegment)) {
                $this->addSegmentError($segment->nr, 'tags in segment are not well-structured. '.$tagStructureChecker->getError());
            }
            $countNewSegmentTags = $tagStructureChecker->getCount();
            
            // check count tags of the new segment (from excel) against the org. segement from t5
            $tagStructureChecker->check($orgSegmentAsExcel);
            if ($tagStructureChecker->getCount() != $countNewSegmentTags) {
                $this->addSegmentError($segment->nr, 'count of tags in segment changed in excel');
            }
            
            // add TrackChanges informations comparing the new segment (from excel) with the t5 segment (converted to excel tagging)
            // but only if task is not in workflowStep 'translation'
            // @FIXME: ADD check Plugin.TrackChanges active, or something similar.
            if ($task->getWorkflowStepName() !== editor_Workflow_Abstract::STEP_TRANSLATION) {
                $newSegment = $diffTagger->diffSegment($orgSegmentAsExcel, $newSegment, date('Y-m-d H:i:s'), $task->getPmName());
            }
            
            // restore org. tags; detect tag-map from t5 SOURCE segment. Only there all original tags are present.
            $tempMap = [];
            $segmentTagger->toExcel($t5Segment->getSource(), $tempMap);
            $newSegment = $segmentTagger->reapply2dMap($newSegment, $tempMap);
            
            // @FIXME: terminology markup is readded by sending the segment again to the termTagger.
            // ?? is it always neded??? or only if TermTagger Plugin is active.. what about the workflow..
            // maybe its better to do it for the complete task, so not every single segment must be tagged.
            // must be somehow like on task creation
            // or maybe this is automaticaly done by $t5Segment->save(); a bit later on this function.
            
            // save edited segment target
            $t5Segment->setTargetEdit($newSegment);
            $t5Segment->save();
            
            
            // on every changed segment, add a comment that it was edited
            $this->addComment("Changed in external Excel editing.", $t5Segment->getId(), $task, TRUE);
            // save (new) comment for the segment (if not empty in excel)
            if (!empty($segment->comment)) {
                $this->addComment($segment->comment, $t5Segment->getId(), $task);
            }
        }
        
        // task data must be actualized
        $task->createMaterializedView();
        
        
        return TRUE;
    }
    
    /**
     * Add a comment to a segment in t5.
     * @param string $comment
     * @param int $segmentId
     * @param editor_Models_Task $task
     * @param bool $noIntro
     */
    protected function addComment(string $commentText, int $segmentId, editor_Models_Task$task, $noIntro = FALSE) : void {
        try {
            $comment = ZfExtended_Factory::get('editor_Models_Comment');
            /* @var $comment editor_Models_Comment */
            $now = date('Y-m-d H:i:s');
            $comment->init();
            
            $comment->setModified($now);
            $comment->setCreated($now);
            
            $comment->setTaskGuid($task->getTaskGuid());
            $comment->setSegmentId($segmentId);
            
            $comment->setUserGuid($task->getPmGuid());
            $comment->setUserName($task->getPmName());
            
            $tempComment = ($noIntro) ? $commentText : 'Comment from external editing in Excel:'."\n".$commentText;
            $comment->setComment($tempComment);
            
            $comment->validate();
            $comment->save();
            
            $comment->updateSegment($segmentId, $task->getTaskGuid());
        }
        catch (ZfExtended_UnprocessableEntity | Zend_Db_Statement_Exception $e) {
            // @TODO what to do if en error occures on validating/saving the comment
        }
    }
    
    /**
     * Do some formal checks, by comparing the informations in the excel with the informations of the task<br/>
     * - compare the task-guid<br/>
     * - compare the number of segments<br/>
     * - compare all segments if an empty segment in excel was not-empty in task<br/>
     * 
     * @param editor_Models_Task $task
     * @throws editor_Models_Excel_ExImportException
     */
    protected function formalCheck(editor_Models_Task $task) {
        // compare task-guid
        if ($task->getTaskGuid() != $this->excel->getTaskGuid()) {
            // throw exception 'E1138' => 'Excel Reimport: Formal check failed: task-guid differs in task compared to the excel.'
            throw new editor_Models_Excel_ExImportException('E1138',['task' => $task]);
        }
        
        // compare number of segments.
        $t5Segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $tempCountTaskSegments = $t5Segment->count($task->getTaskGuid());
        
        $tempExcelSegments = $this->excel->getSegments();
        if ($tempCountTaskSegments != count($tempExcelSegments)) {
            // throw exception 'E1139' => 'Excel Reimport: Formal check failed: number of segments differ in task compared to the excel.'
            throw new editor_Models_Excel_ExImportException('E1139',['task' => $task]);
        }
        
        // compare all segments if an empty segment in excel is not-empty in task
        $emptySegments = [];
        foreach ($tempExcelSegments as $excelSegment) {
            if (empty($excelSegment->target)) {
                $t5Segment->loadBySegmentNrInTask($excelSegment->nr, $task->getTaskGuid());
                if (!empty($t5Segment->getTargetEdit())) {
                    $emptySegments[] = $excelSegment->nr;
                }
            }
        }
        if(!empty($emptySegments)) {
            // throw exception 'E1140' => 'Excel Reimport: Formal check failed: segment #{segmentNr} is empty in excel while there was content in the the original task.'
            throw new editor_Models_Excel_ExImportException('E1140',['task' => $task, 'segmentNr' => join(',', $emptySegments)]);
        }
    }
    
    /**
     * add an segment error to the internal segment-error-list.
     * @param int $segmentNr
     * @param string $hint
     */
    protected function addSegmentError(int $segmentNr, string $hint) : void {
        //we abuse the segment container for transporting the error messages
        $error = new excelExImportSegmentContainer();
        $error->nr = $segmentNr;
        $error->comment = $hint;
        $this->segmentError[] = $error;
    }
    
    /**
     * get the list of internal segment errors (as formatet string).
     * if there where no error FALSE will be returned
     * @return excelExImportSegmentContainer[]
     */
    public function getSegmentErrors() : array {
        return $this->segmentError;
    }
}
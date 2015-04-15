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

/**
 * extends and modifies the original TBX importer, so that only missing Term entries ares imported
 */
class editor_Models_Import_TermListParser_TbxReimportMissing extends editor_Models_Import_TermListParser_Tbx {
    
    /**
     * Set all auto ID Generations to false, so that the ids are used from the already existing TBX
     * @var boolean
     */
    protected $autoIds = false;
    protected $addTermEntryIds = false;
    protected $addTigIds = false;
    protected $addTermIds = false;
    
    /**
     * @var array
     */
    protected $insertedMissing = array();
    
    /**
     * @var integer
     */
    protected $alreadyExistingTerms = 0;
    
    /**
     * returns the inserted missing terms
     * @return array
     */
    public function getInsertedMissing(){
        return $this->insertedMissing;
    }
    
    /**
     * returns the count of already existing terms
     * @return integer
     */
    public function getAlreadyExistingTerms(){
        return $this->alreadyExistingTerms;
    }
    
    public function importMissing(editor_Models_Task $task){
        $this->term = ZfExtended_Factory::get('editor_Models_Term');
        $sourceLang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $sourceLang editor_Models_Languages */
        $sourceLang->load($task->getSourceLang());
        
        $targetLang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $sourceLang editor_Models_Languages */
        $targetLang->load($task->getTargetLang());
        
        $tbx = new SplFileInfo(self::getTbxPath($task));
        if(!$tbx->isReadable()) {
            return false;
        }
        $this->importOneTbx($tbx, $task, $sourceLang, $targetLang);
        return true;
    }
    
    protected function saveTermEntityToDb() {
        if(empty($this->termInsertBuffer)) {
            return null;
        }
        $mids = array_map(function($term){
            return $term['mid'];
        },$this->termInsertBuffer);
        $termDb = $this->term->db;
        $sql = $termDb->select()
        ->where('taskGuid = ?', $this->task->getTaskGuid())
        ->where('mid in (?)', $mids);
        
        $foundMids = $termDb->fetchAll($sql)->toArray();
        $foundMids = array_map(function($term){
            return $term['mid'];
        },$foundMids);
        
        $toInsert = array();
        foreach($this->termInsertBuffer as $termToInsert) {
            if(in_array($termToInsert['mid'], $foundMids)) {
                $this->alreadyExistingTerms++;
            }
            else {
                $this->insertedMissing[] = $termToInsert;
                $toInsert[] = $termToInsert;
            }
        }
        $this->termInsertBuffer = $toInsert;
        return parent::saveTermEntityToDb();
    }
}

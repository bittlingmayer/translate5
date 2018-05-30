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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */
/**
 * Term Instanz
 * 
 * TODO refactor this class, so that code to deal with the term mark up will be moved in editor_Models_Segment_TermTag
 * 
 */
class editor_Models_Term extends ZfExtended_Models_Entity_Abstract {
    const STAT_PREFERRED = 'preferredTerm';
    const STAT_ADMITTED = 'admittedTerm';
    const STAT_LEGAL = 'legalTerm';
    const STAT_REGULATED = 'regulatedTerm';
    const STAT_STANDARDIZED = 'standardizedTerm';
    const STAT_DEPRECATED = 'deprecatedTerm';
    const STAT_SUPERSEDED = 'supersededTerm';
    const STAT_NOT_FOUND = 'STAT_NOT_FOUND'; //Dieser Status ist nicht im Konzept definiert, sondern wird nur intern verwendet!

    const TRANSSTAT_FOUND = 'transFound';
    const TRANSSTAT_NOT_FOUND = 'transNotFound';
    const TRANSSTAT_NOT_DEFINED ='transNotDefined';
    
    const CSS_TERM_IDENTIFIER = 'term';
    
    /**
     * The above constants are needed in the application as list, since reflection usage is expensive we cache them here:
     * @var array
     */
    protected static $statusCache = [];
    
    protected $statOrder = array(
        self::STAT_PREFERRED => 1,
        self::STAT_ADMITTED => 2,
        self::STAT_LEGAL => 2,
        self::STAT_REGULATED => 2,
        self::STAT_STANDARDIZED => 2,
        self::STAT_DEPRECATED => 3,
        self::STAT_SUPERSEDED => 3,
        self::STAT_NOT_FOUND => 99,
    );

    protected $dbInstanceClass = 'editor_Models_Db_Terms';
    
    protected static $groupIdCache = array();

    /**
     * @var editor_Models_Segment_TermTag
     */
    protected $tagHelper;
    
    public function __construct() {
        parent::__construct();
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
    }
    
    /**
     * returns for a termId the associated termentries by group 
     * @param array $collectionIds associated collections to the task
     * @param string $termId
     * @param int $langId
     * @return array
     */
    public function getTermGroupEntries(array $collectionIds, $termId,$langId) {
        $s1 = $this->db->getAdapter()->select()
        ->from(array('t1' => 'LEK_terms'),
                array('t1.groupId'))
        ->where('t1.id = ?', $termId)
        ->where('t1.collectionId IN(?)', $collectionIds);
        $s2 = $this->db->getAdapter()->select()
        ->from(array('t2' => 'LEK_terms'))
        ->where('t2.collectionId IN(?)', $collectionIds)
        ->where('t2.language = ? and t2.groupId = ('.$s1->assemble().')', $langId);
        return $this->db->getAdapter()->fetchAll($s2);
    }
    
    /**
     * returns an array with groupId and term to a given mid
     * @param string $mid
     * @param array $collectionIds
     * @return array
     */
    public function getTermAndGroupIdToMid($mid, $collectionIds) {
        if(!empty(self::$groupIdCache[$mid])) {
            return self::$groupIdCache[$mid];
        }
        $select = $this->db->select()
        ->from($this->db, array('groupId', 'term'))
        ->where('collectionId IN(?)', $collectionIds)
        ->where('mid = ?', $mid);
        $res = $this->db->fetchRow($select);
        if(empty($res)) {
            return $res;
        }
        self::$groupIdCache[$mid] = $res;
        return $res->toArray();
    }
    
    
    /**
     * Returns term-informations for $segmentId in $taskGuid.
     * Includes assoziated terms corresponding to the tagged terms
     * 
     * @param string $taskGuid
     * @param int $segmentId
     * @return array
     */
    public function getByTaskGuidAndSegment(string $taskGuid, integer $segmentId) {
        if(empty($taskGuid) || empty($segmentId)) {
            return array();
        }
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        if (!$task->getTerminologie()) {
            return array();
        }
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        
        $termIds = $this->getTermMidsFromTaskSegment($task, $segment);
        
        if(empty($termIds)) {
            return array();
        }
        
        $assoc=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $assoc editor_Models_TermCollection_TermCollection */
        $collections=$assoc->getCollectionsForTask($task->getTaskGuid());
        $result = $this->getSortedTermGroups($collections, $termIds, $task->getSourceLang());
        
        if(empty($result)) {
            return array();
        }
        return $this->sortTerms($result);
    }
    
    /**
     * Returns term-informations for $segmentId in termCollection.
     * Includes assoziated terms corresponding to the tagged terms
     * 
     * @param array $collectionIds
     * @param string $mid
     * @param array $languageIds 1-dim array with languageIds|default empty array; 
     *          if passed only terms with the passed languageIds are returned
     * @return 2-dim array (get term of first row like return[0]['term'])
     */
    public function getAllTermsOfGroupByMid(array $collectionIds, string $mid, $languageIds = array()) {
        $db = $this->db;
        $s = $db->select()
            ->from(array('t1' => $db->info($db::NAME)))
            ->join(array('t2' => $db->info($db::NAME)), 't1.groupId = t2.groupId', '')
            ->where('t1.collectionId IN(?)', $collectionIds)
            ->where('t2.collectionId IN(?)', $collectionIds)
            ->where('t2.mid = ?', $mid);
        $s->setIntegrityCheck(false);
        if(!empty($languageIds)) {
            $s->where('t1.language in (?)', $languageIds);
        }
        return $db->fetchAll($s)->toArray();
    }
    
    /**
     * Returns term-informations for a given group id
     * 
     * @param array $collectionIds
     * @param string $groupid
     * @param array $languageIds 1-dim array with languageIds|default empty array; 
     *          if passed only terms with the passed languageIds are returned
     * @return 2-dim array (get term of first row like return[0]['term'])
     */
    public function getAllTermsOfGroup(array $collectionIds, string $groupid, $languageIds = array()) {
        $db = $this->db;
        $s = $db->select()
            ->where('collectionId IN(?)', $collectionIds)
            ->where('groupId = ?', $groupid);
        if(!empty($languageIds)) {
            $s->where('language in (?)', $languageIds);
        }
        return $db->fetchAll($s)->toArray();
    }
    
    /***
     * check if the term with the same termEntry,collection but different termId exist
     * 
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRestTermsOfGroup($groupId, $mid, $collectionId){
        $s = $this->db->select()
        ->where('groupId = ?', $groupId)
        ->where('mid != ?', $mid)
        ->where('collectionId = ?',$collectionId);
        return $this->db->fetchAll($s);
    }
    
    /**
     * returns all term mids of the given segment in a multidimensional array.
     * First level contains source or target (the fieldname)
     * Second level contains a list of arrays with the found mids and div tags,
     *   the div tag is needed for transfound check 
     * @param editor_Models_Task $task
     * @param editor_Models_Segment $segment
     * @return array
     */
    protected function getTermMidsFromTaskSegment(editor_Models_Task $task, editor_Models_Segment $segment) {
        
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($task->getTaskGuid());
        
        //Currently only terminology is shown in the first fields see also TRANSLATE-461
        if ($task->getEnableSourceEditing()) {
            $sourceFieldName = $fieldManager->getEditIndex($fieldManager->getFirstSourceName());
            $sourceText = $segment->get($sourceFieldName);
        }
        else {
            $sourceFieldName = $fieldManager->getFirstSourceName();
            $sourceText = $segment->get($sourceFieldName);
        }
        
        $targetFieldName = $fieldManager->getEditIndex($fieldManager->getFirstTargetName());
        $targetText = $segment->get($targetFieldName);
        
        //tbxid should be sufficient as distinct identifier of term tags
        $getTermIdRegEx = '/<div[^>]+data-tbxid="([^"]*)"[^>]*>/';
        preg_match_all($getTermIdRegEx, $sourceText, $sourceMatches, PREG_SET_ORDER);
        preg_match_all($getTermIdRegEx, $targetText, $targetMatches, PREG_SET_ORDER);
        
        if (empty($sourceMatches) && empty($targetMatches)) {
            return array();
        }
        
        return array('source' => $sourceMatches, 'target' => $targetMatches);
    }
    
    /**
     * returns all term mids from given segment content (allows and returns also duplicated mids)
     * @param string $seg
     * @return array values are the mids of the terms in the string
     */
    public function getTermMidsFromSegment(string $seg) {
        return array_map(function($item) {
            return $item['mid'];
        }, $this->getTermInfosFromSegment($seg));
    }
    
    /**
     * returns mids and term flags (css classes) found in a string
     * @param string $seg
     * @return array 2D Array, first level are found terms, second level has key mid and key classes
     */
    public function getTermInfosFromSegment(string $seg) {
        return $this->tagHelper->getInfos($seg);
    }
    
    /**
     * Returns a multidimensional array.
     * 1. level: keys: groupId, values: array of terms grouped by groupId
     * 2. level: terms of group groupId
     * 
     * !! TODO: Sortierung der Gruppen in der Reihenfolge wie sie im Segment auftauchen (order by seg2term.id sollte hinreichend sein)
     * 
     * @param array $collectionIds term collections associated to the task
     * @param array $termIds as 2-dimensional array('source' => array(), 'target' => array())
     * @param $sourceLang
     * 
     * @return array
     */
    protected function getSortedTermGroups(array $collectionIds, array $termIds, $sourceLang) {
        $sourceIds = array();
        $targetIds = array();
        $transFoundSearch = array();
        foreach ($termIds['source'] as $termId) {
            $sourceIds[] = $termId[1];
            $transFoundSearch[$termId[1]] = $termId[0];
        }
        foreach ($termIds['target'] as $termId) {
            $targetIds[] = $termId[1];
            $transFoundSearch[$termId[1]] = $termId[0];
        }
        
        $allIds = array_merge($sourceIds, $targetIds);
        $serialIds = '"'.implode('", "', $allIds).'"';
        
        $sql = $this->db->getAdapter()->select()
                ->from(array('t1' =>'LEK_terms'), array('t2.*'))
                ->distinct()
                ->joinLeft(array('t2' =>'LEK_terms'), 't1.groupId = t2.groupId', null)
                ->join(array('l' =>'LEK_languages'), 't2.language = l.id', 'rtl')
                ->where('t1.collectionId IN(?)', $collectionIds)
                ->where('t2.collectionId IN(?)', $collectionIds)
                ->where('t1.mid IN('.$serialIds.')');
       
        $terms = $this->db->getAdapter()->fetchAll($sql);
        
        $termGroups = array();
        foreach($terms as $term) {
            $term = (object) $term;
            
            settype($termGroups[$term->groupId], 'array');
            
            $term->used = in_array($term->mid, $allIds);
            $term->isSource = in_array($term->language, array($sourceLang));
            $term->transFound = false;
            if ($term->used) {
                $term->transFound = preg_match('/class="[^"]*transFound[^"]*"/', $transFoundSearch[$term->mid]);
            }
            
            $termGroups[$term->groupId][] = $term;
        }
        
        return $termGroups;
    }
    
    /**
     * 
     * @param string $mid
     * @param array $collectionIds
     * @return Zend_Db_Table_Row_Abstract | null
     */
    public function loadByMid(string $mid,array $collectionIds) {
        $s = $this->db->select(false);
        $db = $this->db;
        $s->from($this->db);
        $s->where('collectionId IN(?)', $collectionIds)->where('mid = ?', $mid);
        
        
        $this->row = $this->db->fetchRow($s);
        if(empty($this->row)){
            $this->notFound('#select', $s->assemble());
        }
        return $this->row;
    }
    
    /**
     * Sortiert die Terme innerhalb der Termgruppen:
     * @param array $termGroups
     * @return array
     */
    protected function sortTerms(array $termGroups) {
        foreach($termGroups as $groupId => $group) {
            usort($group, array($this, 'compareTerms'));
            $termGroups[$groupId] = $group;
        }
        return $termGroups;
    }

    /**
     * Bewertet die Terme nach den folgenden Kriterien (siehe auch http://php.net/usort/)
     *  -- 1. Kriterium: Vorzugsbenennung vor erlaubter Benennung vor verbotener Benennung
     *  -- 2. Kriterium: In Quelle vorhanden
     *  -- 3. Kriterium: In Ziel vorhanden (damit ist die Original-Übersetzung gemeint, nicht die editierte Variante)
     *  -- 4. Kriterium: Alphanumerische Sortierung
     *  Zusammenhang Parameter und Return Values siehe usort $cmp_function
     *
     *  @param array $term1
     *  @param array $term2
     *  @return integer
     */
    protected function compareTerms($term1, $term2) {
        // return > 0 => t1 > t2
        // return = 0 => t1 = t2
        // return < 0 => t1 < t2
        $status = $this->compareTermStatus($term1->status, $term2->status);
        if($status !== 0) {
            return $status;
        }

        $isSource = $this->compareTermLangUsage($term1->isSource, $term2->isSource);
        if($isSource !== 0) {
            return $isSource;
        }

        //Kriterium 4 - alphanumerische Sortierung:
        return strcmp(mb_strtolower($term1->term), mb_strtolower($term2->term));
    }

    /**
     * Vergleicht die Term Status
     * @param string $status1
     * @param string $status2
     * @return integer
     */
    protected function compareTermStatus($status1, $status2) {
        //wenn beide stati gleich, dann wird kein weiterer Vergleich benötigt
        if($status1 === $status2) {
            return 0;
        }
        if(empty($this->statOrder[$status1])){
            $status1 = self::STAT_NOT_FOUND;
        }
        if(empty($this->statOrder[$status2])){
            $status2 = self::STAT_NOT_FOUND;
        }

        //je kleiner der statOrder, desto höherwertiger ist der Status!
        //Da Höherwertig aber bedeutet, dass es in der Sortierung weiter oben erscheinen soll,
        //ist der Höherwertige Status im numerischen Wert kleiner!
        if($this->statOrder[$status1] < $this->statOrder[$status2]) {
            return -1; //status1 ist höherwertiger, da der statOrdner kleiner ist
        }
        return 1; //status2 ist höherwertiger
    }

    /**
     * Vergleicht die Term auf Verwendung in Quell oder Zielspalte
     * @param string $isSource1
     * @param string $isSource2
     * @return integer
     */
    protected function compareTermLangUsage($isSource1, $isSource2) {
        //Verwendung in Quelle ist höherwertiger als in Ziel (Kriterium 2 und 3)
        if($isSource1 === $isSource2) {
            return 0;
        }
        if($isSource1) {
            return 1;
        }
        return -1;
    }
    
    /**
     * @param editor_Models_Task $task
    //FIXME editor_Models_Export_Tbx durch entsprechendes Interface ersetzen
     * @param editor_Models_Export_Tbx $exporteur
     */
    public function export(editor_Models_Task $task, editor_Models_Export_Terminology_Tbx $exporteur) {
        $langs = array($task->getSourceLang(), $task->getTargetLang());
        if($task->getRelaisLang() > 0) {
            $langs[] = $task->getRelaisLang();
        }
        
        $assoc=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $assoc editor_Models_TermCollection_TermCollection */
        $collectionIds=$assoc->getCollectionsForTask($task->getTaskGuid());
        
        $data=$this->loadSortedByCollectionAndLanguages($collectionIds, $langs);
        if(!$data) {
            return null;
        }
        $exporteur->setData($data);
        return $exporteur->export();
    }
    
    /***
     * Load terms in given collection and languages. The returned data will be sorted by groupId,language and id
     * 
     * @param array $collectionIds
     * @param array $langs
     * @return NULL|Zend_Db_Table_Rowset_Abstract
     */
    public function loadSortedByCollectionAndLanguages(array $collectionIds,$langs=array()){
        $s = $this->db->select()
        ->where('collectionId IN(?)', $collectionIds);
        if(!empty($langs)){
            $s->where('language in (?)', $langs);
        }
        $s->order('groupId ASC')
        ->order('language ASC')
        ->order('id ASC');
        $data = $this->db->fetchAll($s);
        if($data->count() == 0) {
            return null;
        }
        return $data;
    }
    
    /***
     * Get term by collection, language and term
     * 
     * @param mixed $collectionId
     * @param mixed $languageId
     * @param mixed $termValue
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function loadByCollectionLanguageAndTermValue($collectionId,$languageId,$termValue){
        $s = $this->db->select()
            ->where('collectionId = ?', $collectionId)
            ->where('language = ?', $languageId)
            ->where('term = ?', $termValue);
        return $this->db->fetchAll($s);
    }
    
    /***
     * Check if the given term entry exist in the collection
     * @param mixed $termEntry
     * @param integer $collectionId
     * @return boolean
     */
    public function isTermEntryInCollection($termEntry,$collectionId){
        $s = $this->db->select()
        ->where('groupId = ?', $termEntry)
        ->where('collectionId = ?', $collectionId);
        $terms=$this->db->fetchAll($s);
        return $terms->count()>0;
    }
    
    /***
     * Check if the term should be updated for the term collection
     * 
     * @param mixed $termEntry
     * @param mixed $termId
     * @param mixed $collectionId
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function isUpdateTermForCollection($termEntry,$termId,$collectionId){
        $s = $this->db->select()
        ->where('groupId = ?', $termEntry)
        ->where('mid = ?', $termId)
        ->where('collectionId = ?', $collectionId);
        return $this->db->fetchAll($s);
    }
    
    /***
     * Find term in collection by given language and term value
     */
    public function findTermInCollection($termText,$languageId,$termCollection){
        $s = $this->db->select()
        ->where('language = ?', $languageId)
        ->where('term = ?', $termText)
        ->where('collectionId = ?',$termCollection);
        return $this->db->fetchAll($s);
    }
    
    /**
     * Search terms in the term collection with the given search string and languages.
     * 
     * @param string $queryString
     * @param array $languages
     * @param array $collectionIds
     * @param mixed $limit
     * 
     * @return array|NULL
     */
    public function searchTermByLanguage($queryString,$languages,$collectionIds,$limit=null){
        
        //if wildcards are used, adopt them to the mysql needs
        $queryString=str_replace("*","%",$queryString);
        $queryString=str_replace("?","_",$queryString);
        
        $adapter=$this->db->getAdapter();
        
        //when limit is provided -> autocomplete search
        if($limit){
            $queryString=$queryString.'%';
        }
        
        $s=$this->db->select()
        ->from($this->db, array('definition','groupId', 'term as label','id as value','term as desc'))
        ->where('lower(term) like lower(?) COLLATE utf8_bin',$queryString)
        ->where('language IN(?)',explode(',', $languages))
        ->where('collectionId IN(?)',$collectionIds)
        ->order('term asc');
        if($limit){
            $s->limit($limit);
        }
        $rows=$this->db->fetchAll($s)->toArray();
        if(!empty($rows)){
            return $rows;
        }
        return null;
    }
    
    /***
     * Find term attributes in the given term entry (lek_terms groupId)
     * 
     * @param string $groupId
     * @param array $collectionIds
     * @return array|NULL
     */
    public function searchTermAttributesInTermentry($groupId,$collectionIds){
        $attCols=array(
                'LEK_term_attributes.labelId as labelId',
                'LEK_term_attributes.id AS attributeId',
                'LEK_term_attributes.parentId AS parentId',
                'LEK_term_attributes.internalCount AS internalCount',
                'LEK_term_attributes.name AS name',
                'LEK_term_attributes.attrType AS attrType',
                'LEK_term_attributes.attrTarget AS attrTarget',
                'LEK_term_attributes.attrId AS attrId',
                'LEK_term_attributes.attrLang AS attrLang',
                'LEK_term_attributes.value AS attrValue',
                'LEK_term_attributes.created AS attrCreated',
                'LEK_term_attributes.updated AS attrUpdated',
                'LEK_term_attributes.attrDataType AS attrDataType',
        );
        
        $cols=array(
                'definition',
                'groupId', 
                'term as label',
                'id as value',
                'term as desc',
                'id as termId',
                'collectionId',
                'language as languageId'
        );
        
        $s=$this->db->select()
        ->from($this->db,$cols)
        ->joinLeft('LEK_term_attributes', 'LEK_term_attributes.termId = LEK_terms.id',$attCols)
        ->join('LEK_languages', 'LEK_terms.language=LEK_languages.id',array('LEK_languages.rfc5646 AS language'))
        ->where('groupId=?',$groupId)
        ->where('LEK_term_attributes.collectionId IN(?)',$collectionIds)
        ->order('label');
        $s->setIntegrityCheck(false);
        $rows=$this->db->fetchAll($s)->toArray();
        if(!empty($rows)){
            return $rows;
        }
        return null;
    }
    
    /***
     * Find term entry attributes in the given term entry (lek_terms groupId)
     * 
     * @param string $groupId
     * @return array|NULL
     */
    public function searchTermEntryAttributesInTermentry($groupId){
        $s=$this->db->select()
        ->from($this->db, array('definition','groupId', 'term','id as termId'))
        ->join('LEK_term_entry_attributes', 'LEK_term_entry_attributes.termEntryId = LEK_terms.termEntryId')
        ->where('LEK_terms.groupId=?',$groupId)
        ->group('LEK_terms.groupId');
        $s->setIntegrityCheck(false);
        $rows=$this->db->fetchAll($s)->toArray();
        if(!empty($rows)){
            return $rows;
        }
        return null;
    }
    
    /***
     * Remove old terms by given date.
     * The term attributes also will be removed.
     * 
     * @param string $olderThan
     * @return boolean
     */
    public function removeOldTerms($olderThan){
       return $this->db->delete(['updated < ?'=>$olderThan])>0;
    }

    /**
     * returns a map CONSTNAME => value of all term status
     * @return array
     */
    static public function getAllStatus() {
        self::initConstStatus();
        return self::$statusCache['status'];
    }
    
    /**
     * returns a map CONSTNAME => value of all translation status
     * @return array
     */
    static public function getAllTransStatus() {
        self::initConstStatus();
        return self::$statusCache['translation'];
    }
    
    /**
     * creates a internal list of the status constants
     */
    static protected function initConstStatus() {
        if(!empty(self::$statusCache)) {
            return;
        }
        self::$statusCache = [
            'status' => [],
            'translation' => [],
        ];
        $refl = new ReflectionClass(__CLASS__);
        $constants = $refl->getConstants();
        foreach($constants as $key => $val) {
            if(strpos($key, 'STAT_') === 0) {
                self::$statusCache['status'][$key] = $val;
            }
            if(strpos($key, 'TRANSSTAT_') === 0) {
                self::$statusCache['translation'][$key] = $val;
            }
        }
    }
}
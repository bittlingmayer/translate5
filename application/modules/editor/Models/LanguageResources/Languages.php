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
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getSourceLang() getSourceLang()
 * @method void setSourceLang() setSourceLang(integer $id)
 * @method string getSourceLangRfc5646() getSourceLangRfc5646()
 * @method void setSourceLangRfc5646() setSourceLangRfc5646(string $lang)
 * @method string getTargetLang() getTargetLang()
 * @method void setTargetLang() setTargetLang(integer $id)
 * @method string getTargetLangRfc5646() getTargetLangRfc5646()
 * @method void setTargetLangRfc5646() setTargetLangRfc5646(string $lang)
 * @method integer getLanguageResourceId getLanguageResourceId()
 * @method void setLanguageResourceId() setLanguageResourceId(integer $languageResourceId)
 * 
 */
class editor_Models_LanguageResources_Languages extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_Languages';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_Languages';
    
    
    /***
     * Save the languages for the resource id
     * @param int $source
     * @param int $target
     * @param int $languageResourceId
     */
    public function saveLanguages($source,$target,$languageResourceId){
        $sourceLang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $sourceLang editor_Models_Languages */
        $sourceLang->load($source);
        
        $targetLang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $targetLang editor_Models_Languages */
        $targetLang->load($target);
        
        $this->setSourceLang($sourceLang->getId());
        $this->setTargetLang($targetLang->getId());
        $this->setSourceLangRfc5646($sourceLang->getRfc5646());
        $this->setTargetLangRfc5646($targetLang->getRfc5646());
        $this->setLanguageResourceId($languageResourceId);
        $this->save();
    }
    
    /***
     * @param integer $languageResourceId
     * @return array
     */
    public function loadByLanguageResourceId($languageResourceId=null){
        $s=$this->db->select();
        if($languageResourceId){
            $s->where('languageResourceId=?',$languageResourceId);
        }
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Load assocs by source language ids
     * @param array $sourceLangs
     * @return array
     */
    public function loadBySourceLangIds($sourceLangs=array()) {
        return $this->loadByFieldAndValue('sourceLang', $sourceLangs);
    }
    
    /***
     * Load assocs by target language ids
     * @param array $targetLangs
     * @return array
     */
    public function loadByTargetLangIds($targetLangs=array()) {
        return $this->loadByFieldAndValue('targetLang', $targetLangs);
    }
    
    /***
     * Load assocs by given assoc field and values
     * @param string $field
     * @param array $value
     * @return array
     */
    public function loadByFieldAndValue($field,array $value){
        $s=$this->db->select()
        ->where($field.' IN(?)',$value);
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * @return array[]
     */
    public function loadResourceIdsGrouped() {
        $langs=$this->loadByLanguageResourceId();
        $retval=[];
        foreach ($langs as $lang){
            if(!isset($retval[$lang['languageResourceId']])){
                $retval[$lang['languageResourceId']]['sourceLang']=[];
                $retval[$lang['languageResourceId']]['targetLang']=[];
            }
            array_push($retval[$lang['languageResourceId']]['sourceLang'],$lang['sourceLang']);
            array_push($retval[$lang['languageResourceId']]['targetLang'],$lang['targetLang']);
        }
        return $retval;
    }
}


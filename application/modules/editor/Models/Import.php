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
 * Starts an import by gathering all needed data, check and store it, and start an Import Worker
 */
class editor_Models_Import {
    use editor_Models_Import_HandleExceptionTrait;
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events;
    
    /**
     * 
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;
    
    /**
     * Konstruktor
     */
    public function __construct(){
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $this->importConfig = ZfExtended_Factory::get('editor_Models_Import_Configuration');
    }
    
    /**
     * sets the Importer to check mode: additional debug output on import
     * does not effect pre import checks
     * @param boolean $check optional, per default true 
     */
    public function setCheck($check = true){
        $this->importConfig->isCheckRun = $check;
    }
    
    /**
     * führt den Import aller Dateien eines Task durch
     * @param string $importFolderPath
     */
    public function import(editor_Models_Import_DataProvider_Abstract $dataProvider) {
        if(empty($this->task)){
            throw new Zend_Exception('taskGuid not set - please set using $this->setTask/$this->createTask');
        }
        Zend_Registry::set('affected_taskGuid', $this->task->getTaskGuid()); //for TRANSLATE-600 only
        
        //pre import methods:
        $dataProvider->setTask($this->task);
        $dataProvider->checkAndPrepare();
        $this->importConfig->importFolder = $dataProvider->getAbsImportPath();
        
        $this->importConfig->isValid($this->task->getTaskGuid());
        
        if(! $this->importConfig->hasRelaisLanguage()) {
            //@todo in new rest api and / or new importwizard show ereror, if no relaislang is set, but relais data is given or viceversa (see translate5 featurelist)
            
            //reset given relais language value if no relais data is provided / feature is off
            $this->task->setRelaisLang(0); 
        }
        
        $this->task->save(); //Task erst Speichern wenn die obigen validates und checks durch sind.
        $this->task->lock(NOW_ISO, true); //locks the task

        /*
         * Queue Import Worker
         */
        $importWorker = ZfExtended_Factory::get('editor_Models_Import_Worker');
        /* @var $worker editor_Models_Import_Worker */
        $importWorker->init($this->task->getTaskGuid(), array(
                'config' => $this->importConfig,
                'dataProvider' => $dataProvider
        ));
        $importWorker->queue();
        
        $worker = ZfExtended_Factory::get('editor_Models_Import_Worker_SetTaskToOpen');
        /* @var $worker editor_Models_Import_Worker_SetTaskToOpen */
        
        //queuing this worker when task has errors make no sense, init checks this.
        if($worker->init($this->task->getTaskGuid())) {
            $worker->queue(); 
        }
    }
    
    /**
     * Using this proxy method for triggering the event to keep the legacy code bound to this class instead to the new worker class
     * @param editor_Models_Task $task
     */
    public function triggerAfterImport(editor_Models_Task $task) {
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        /* @var $eventManager ZfExtended_EventManager */
        $eventManager->trigger('afterImport', $this, array('task' => $task));
    }
    
    /**
     * sets the info/data to the user
     * @param string $userguid
     * @param string $username
     */
    public function setUserInfos(string $userguid, string $username) {
        $this->importConfig->userName = $username;
        $this->importConfig->userGuid = $userguid;
    }

    /**
     * sets a optional taskname and options of the imported task
     * returns the created task
     * Current Options: 
     *   enableSourceEditing => boolean
     * @param stdClass $params
     * @return editor_Models_Task
     */
    public function createTask(stdClass $params) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->setTaskName($params->taskName);
        $task->setTaskGuid($params->taskGuid);
        $task->setPmGuid($params->pmGuid);
        $task->setEdit100PercentMatch((int)$params->editFullMatch);
        $task->setLockLocked((int)$params->lockLocked);
        
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        try {
            $pm->loadByGuid($params->pmGuid);
            $task->setPmName($pm->getUsernameLong());
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e){
            $task->setPmName('- not found -');
        }
        
        $task->setTaskNr($params->taskNr);
        
        $sourceId = empty($this->importConfig->sourceLang) ? 0 : $this->importConfig->sourceLang->getId();
        $task->setSourceLang($sourceId);
        $targetId = empty($this->importConfig->targetLang) ? 0 : $this->importConfig->targetLang->getId();
        $task->setTargetLang($targetId);
        $relaisId = empty($this->importConfig->relaisLang) ? 0 : $this->importConfig->relaisLang->getId();
        $task->setRelaisLang($relaisId);
        
        $task->setWorkflow($params->workflow);
        $task->setWordCount($params->wordCount);
        $task->setTargetDeliveryDate($params->targetDeliveryDate);
        $task->setOrderdate($params->orderDate);
        $config = Zend_Registry::get('config');
        //Task based Source Editing can only be enabled if its allowed in the whole editor instance 
        $enableSourceEditing = (bool) $config->runtimeOptions->import->enableSourceEditing;
        $task->setEnableSourceEditing(! empty($params->enableSourceEditing) && $enableSourceEditing);
        $task->validate();
        $this->setTask($task);
        return $task;
    }
    
    /**
     * sets the internal needed Task, inits the Task Directory
     * @param editor_Models_Task $task
     */
    public function setTask(editor_Models_Task $task) {
        $this->task = $task;
        $this->task->initTaskDataDirectory();
    }

    /**
     * Setzt die zu importierende Quell und Zielsprache, das Format der Sprach IDs wird über den Parameter $type festgelegt
     * @param mixed $source
     * @param mixed $target
     * @param mixed $relais Relaissprache, kann null/leer sein wenn es keine Relaissprache gibt
     * @param string $type
     */
    public function setLanguages($source, $target, $relais, $type = editor_Models_Languages::LANG_TYPE_RFC5646) {
        $this->importConfig->setLanguages($source, $target, $relais, $type);
    }
}

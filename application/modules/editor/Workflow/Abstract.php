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
 * Abstract Workflow Class
 * 
 * Warning: When adding new workflows, a alter script must be provided to add 
 *   a default userpref entry for that workflow for each task 
 */
abstract class editor_Workflow_Abstract {
    /*
     * STATES: states describe on the one side the actual state between a user and a task
     *         on the other side changing a state can trigger specific actions on the server
     * currently we have 3 places to define userStates: IndexController
     * for translation, JS Task Model and workflow for programmatic usage
     */
    //the user cant access the task yet
    const STATE_WAITING = 'waiting'; 
    //the user has finished his work on this task, and cant access it anymore
    const STATE_FINISH = 'finished'; 
    //the user can access the task editable and writeable, 
    //setting this state releases the lock if the user had locked the task
    const STATE_OPEN = 'open'; 
    //this state must be set on editing a task, it locks the task for the user
    const STATE_EDIT = 'edit'; 
    //setting this state opens the task readonly 
    const STATE_VIEW = 'view'; 
    
    const ROLE_TRANSLATOR = 'translator';
    const ROLE_LECTOR = 'lector';
    const ROLE_TRANSLATORCHECK = 'translatorCheck';
    const ROLE_VISITOR = 'visitor';
    
    const STEP_TRANSLATION = 'translation';
    const STEP_LECTORING = 'lectoring';
    const STEP_TRANSLATORCHECK = 'translatorCheck';
    const STEP_PM_CHECK = 'pmCheck';
    
    //const WORKFLOW_ID = ''; this is the internal used name for this workflow, it has to be defined in each subclass!
    
    /**
     * labels of the states, roles and steps. Can be changed / added in constructor
     * @var array
     */
    protected $labels = array(
        'WORKFLOW_ID' => 'Standard Ablaufplan', 
        'STATE_IMPORT' => 'import', 
        'STATE_WAITING' => 'wartend', 
        'STATE_FINISH' => 'abgeschlossen', 
        'STATE_OPEN' => 'offen', 
        'STATE_EDIT' => 'selbst in Arbeit', 
        'STATE_VIEW' => 'selbst geöffnet', 
        'ROLE_TRANSLATOR' => 'Übersetzer',
        'ROLE_LECTOR' => 'Lektor',
        'ROLE_TRANSLATORCHECK' => 'Übersetzer (Überprüfung)',
        'ROLE_VISITOR' => 'Besucher',
        'STEP_TRANSLATION' => 'Übersetzung',
        'STEP_LECTORING' => 'Lektorat',
        'STEP_TRANSLATORCHECK' => 'Übersetzer Prüfung',
        'STEP_PM_CHECK' => 'PM Prüfung',
    );
    
    /**
     * This part is very ugly: in the frontend we are working only with all states expect the ones listed here.
     * The states listed here are only used in the frontend grid for rendering purposes, 
     * they are not used to be activly set to a user, or to be filtered etc. pp.
     * So we define them as "pending" states, which have to be delivered in a separate matter to the frontend
     * The values are a subset of the above STATE_CONSTANTs
     * @var array
     */
    protected $pendingStates = array(self::STATE_EDIT, self::STATE_VIEW);
    
    /**
     * Container for the old Task Model provided by doWithTask
     * (task as loaded from DB)
     * @var editor_Models_Task
     */
    protected $oldTask;

    /**
     * Container for the new Task Model provided by doWithTask
     * (task as going into DB, means not saved yet!)
     * @var editor_Models_Task
     */
    protected $newTask;

    /**
     * Container for the old User Task Assoc Model provided by doWithUserAssoc
     * @var editor_Models_TaskUserAssoc
     */
    protected $oldTaskUserAssoc;
    
    /**
     * Container for the new Task User Assoc Model provided by doWithUserAssoc
     * @var editor_Models_TaskUserAssoc
     */
    protected $newTaskUserAssoc;

    /**
     * Container for new User Task State provided by doWithUserAssoc
     * @var string
     */
    protected $newUtaState;

    /**
     * enables / disables debugging (logging), can be enabled by setting runtimeOptions.debug.core.workflow = 1 in installation.ini
     * 0 => disabled
     * 1 => log called handler methods (logging must be manually implemented in the handler methods by usage of $this->doDebug)
     * 2 => log also $this
     * @var integer
     */
    protected $debug = 0;
    
    /**
     * @var stdClass
     */
    protected $authenticatedUser;
    
    /**
     * @var ZfExtended_Models_User
     */
    protected $authenticatedUserModel;
    
    /**
     * Import config, only available on workflow stuff triggerd in the context of an import 
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig = null;
    
    /**
     * lists all roles with read access to tasks
     * @var array 
     */
    protected $readableRoles = array(
        self::ROLE_VISITOR,
        self::ROLE_LECTOR,
        self::ROLE_TRANSLATOR,
        self::ROLE_TRANSLATORCHECK,
    );
    /**
     * lists all roles with write access to tasks
     * @var array 
     */
    protected $writeableRoles = array(
        self::ROLE_LECTOR,
        self::ROLE_TRANSLATOR,
        self::ROLE_TRANSLATORCHECK,
    );
    /**
     * lists all states which allow read access to tasks
     * @todo readableStates and writeableStates have to be changed/extended to a modelling of state transitions
     * @var array 
     */
    protected $readableStates = array(
        self::STATE_WAITING,
        self::STATE_FINISH,
        self::STATE_OPEN,
        self::STATE_EDIT,
        self::STATE_VIEW
    );
    /**
     * lists all states which allow write access to tasks
     * @var array 
     */
    protected $writeableStates = array(
        self::STATE_OPEN,
        self::STATE_EDIT,
        self::STATE_VIEW,
    );
    /**
     * roles which are part of the workflow chain (in this order)
     * @todo currently only used in notification. For extending of workflow system 
     *      or use of a workflow engine extend the use of roleChain to whereever applicable
     * @var array 
     */
    protected $stepChain = array(
        self::STEP_TRANSLATION,
        self::STEP_LECTORING,
        self::STEP_TRANSLATORCHECK,
    );
    
    /**
     * Mapping between roles and workflowSteps. 
     * @var array
     */
    protected $steps2Roles = array(
        self::STEP_TRANSLATION=>self::ROLE_TRANSLATOR,
        self::STEP_LECTORING=>self::ROLE_LECTOR,
        self::STEP_TRANSLATORCHECK=>self::ROLE_TRANSLATORCHECK
    );
    
    /**
     * Valid state / role combination for each step
     * the first state of the states array is also the default state for that step and role
     * @var array
     */
    protected $validStates = [
        self::STEP_TRANSLATION => [
            self::ROLE_TRANSLATOR => [self::STATE_OPEN, self::STATE_EDIT, self::STATE_VIEW],
            self::ROLE_LECTOR => [self::STATE_WAITING],
            self::ROLE_TRANSLATORCHECK => [self::STATE_WAITING],
        ],
        self::STEP_LECTORING => [
            self::ROLE_TRANSLATOR => [self::STATE_FINISH],
            self::ROLE_LECTOR => [self::STATE_OPEN, self::STATE_EDIT, self::STATE_VIEW],
            self::ROLE_TRANSLATORCHECK => [self::STATE_WAITING],
        ],
        self::STEP_TRANSLATORCHECK => [
            self::ROLE_TRANSLATOR => [self::STATE_FINISH],
            self::ROLE_LECTOR => [self::STATE_FINISH],
            self::ROLE_TRANSLATORCHECK => [self::STATE_OPEN, self::STATE_EDIT, self::STATE_VIEW],
        ],
    ];
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    /**
     * determines if calls were done by cronjob
     * @var boolean
     */
    protected $isCron = false;
    
    protected $validDirectTrigger = [
            'notifyAllUsersAboutTaskAssociation',
    ]; 
    
    public function __construct() {
        $this->debug = ZfExtended_Debug::getLevel('core', 'workflow');
        $this->loadAuthenticatedUser();
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $events = Zend_EventManager_StaticEventManager::getInstance();
        $events->attach('Editor_TaskuserassocController', 'afterPostAction', function(Zend_EventManager_Event $event){
            $tua = $event->getParam('entity');
            $this->recalculateWorkflowStep($tua);
            $this->doUserAssociationAdd($tua);
        });
        
        $events->attach('Editor_TaskuserassocController', 'afterDeleteAction', function(Zend_EventManager_Event $event){
            $this->recalculateWorkflowStep($event->getParam('entity'));
        });
        
        $events->attach('editor_Models_Import', 'beforeImport', function(Zend_EventManager_Event $event){
            $this->newTask = $event->getParam('task');
            $this->handleBeforeImport();
        });
    }
    
    /**
     * returns the workflow ID used in translate5
     * if parameter $className is given return the ID of the given classname,
     * if no $className is given, the current class is used
     * @param string $className optional
     */
    public static function getId($className = null) {
        if(empty($className)) {
            return static::WORKFLOW_ID;
        }
        return call_user_func(array($className, __METHOD__));
    }
    
    /**
     * returns a recursive list of workflow IDs used by this workflows instances class hierarchy
     * @return array
     */
    public function getIdList() {
        $parents = class_parents($this);
        $result = [static::WORKFLOW_ID];
        foreach($parents as $parent) {
            if (defined($parent.'::WORKFLOW_ID')) {
                $result[] = constant($parent.'::WORKFLOW_ID');
            }
        }
        return $result;
    }
    
    /**
     * returns true if the workflow methods were triggered by a cron job and no direct user/API interaction
     * @return boolean
     */
    public function isCalledByCron() {
        return $this->isCron;
    }
    
    /**
     * returns the step to roles mapping
     * @return array
     */
    public function getSteps2Roles() {
        return $this->steps2Roles;
    }
    
    /**
     * returns the workflow steps which should have initially an activated segment filter
     * @return string[]
     */
    public function getStepsWithFilter() {
        return [self::STEP_TRANSLATORCHECK];
    }
    
    /**
     * returns the initial states of the different roles in the different steps
     * @return string[][]
     */
    public function getInitialStates() {
        $result = [];
        foreach($this->validStates as $step => $statesToRoles) {
            $result[$step] = [];
            foreach($statesToRoles as $role => $states) {
                //the initial state per role is just the first defined state per role
                $result[$step][$role] = reset($states);
            }
        }
        return $result;
    }
    
    /**
     * @param mixed $step string or null
     * @return string $role OR false if step does not exist
     */
    public function getNextStep(string $step) {
        $stepChain = $this->getStepChain();
        $position = array_search($step, $stepChain);
        if (isset($stepChain[$position + 1])) {
            return $stepChain[$position + 1];
        }
        return false;
    }
    /**
     * @param mixed $step string
     * @return string $role OR false if step does not exist
     */
    public function getRoleOfStep(string $step) {
        $steps2Roles = $this->getSteps2Roles();
        if(isset($steps2Roles[$step]))
            return $steps2Roles[$step];
        return false;
    }
    
    /**
     * returns the step of a role (the first configured one, if there are multiple steps for a role)
     * @param string $role
     * @return string|false
     */
    public function getStepOfRole(string $role) {
        return array_search($role, $this->steps2Roles, true);
    }
    
    /**
     * returns the available step values
     * @return array
     */
    public function getStepChain() {
        return $this->stepChain;
    }
    
    /**
     * return the states defined as pending (is a subset of the getStates result)
     * @return array
     */
    public function getPendingStates() {
        return array_intersect($this->getStates(), $this->pendingStates);
    }

    /**
     * loads the system user as authenticatedUser, if no user is logged in
     */
    protected function loadAuthenticatedUser(){
        $userSession = new Zend_Session_Namespace('user');
        if(isset($userSession->data) && isset($userSession->data->userGuid)) {
            $userGuid = $userSession->data->userGuid;
        }
        else {
            $userGuid = false;
        }
        $config = Zend_Registry::get('config');
        $isCron = $config->runtimeOptions->cronIP === $_SERVER['REMOTE_ADDR'];
        $isWorker = defined('ZFEXTENDED_IS_WORKER_THREAD');
        $this->authenticatedUserModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        
        if($userGuid === false){
            if(!$isCron && !$isWorker) {
                throw new ZfExtended_NotAuthenticatedException("Cannot authenticate the system user!");
            }
            //set session user data with system user
            $this->authenticatedUserModel->setUserSessionNamespaceWithoutPwCheck(ZfExtended_Models_User::SYSTEM_LOGIN);
        }
        $this->authenticatedUserModel->loadByGuid($userSession->data->userGuid);
        $this->authenticatedUser = $userSession->data;
        
    }
    /**
     * 
     * @return array of available step constants (keys are constants, valus are constant-values)
     */
    public function getSteps(){
        return $this->getFilteredConstants('STEP_');
    }
    
    /**
     * 
     * @return array of available role constants (keys are constants, valus are constant-values)
     */
    public function getRoles(){
        return $this->getFilteredConstants('ROLE_');
    }
    
    /**
     * returns an array of wf roles which are allowed by the current user to be used in task user associations
     * @return array of for the authenticated user usable role constants (keys are constants, valus are constant-values)
     */
    public function getAddableRoles(){
        $roles = $this->getRoles();
        //FIXME instead of checking the roles a user have, 
        //this must come from ACL table analogous to setaclrole, use a setwfrole then
        // check sub classes on refactoring too!
        $user = new Zend_Session_Namespace('user');
        if(in_array(ACL_ROLE_PM, $user->data->roles)) {
            return $roles;
        }
        return [];
    }
    
    /**
     * returns the already translated labels as assoc array
     * @var boolean $translated optional, defaults to true
     * @return array
     */
    public function getLabels($translated = true) {
        if(!$translated) {
            return $this->labels;
        }
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        return array_map(function($label) use ($t) {
            return $t->_($label);
        }, $this->labels);
    }
    
    /**
     * 
     * @param string $role
     * @return boolean
     */
    public function isRole(string $role){
        $roles = $this->getRoles();
        return in_array($role, $roles);
    }
    /**
     * 
     * @param string $state
     * @return boolean
     */
    public function isState(string $state){
        $states = $this->getStates();
        return in_array($state, $states);
    }
    /**
     * 
     * @return array of available state constants (keys are constants, valus are constant-values)
     */
    public function getStates(){
        return $this->getFilteredConstants('STATE_');
    }
    /**
     * 
     * @param string $filter
     * @return array values are all constant values which names match filter
     */
    public function getFilteredConstants(string $filter){
        $refl = new ReflectionClass($this);
        $consts = $refl->getConstants();
        $filtered = array();
        foreach ($consts as $const => $val) {
            if(strpos($const, $filter)!==FALSE){
                $filtered[$const] = $val;
            }
        }
        return $filtered;
    }
    /**
     * FIXME auf sinnvolle Weise umsetzen, dass workflowrechte ins frontend kommen
     * FIXME WorkflowRollen-Rechte-Mapping auf verallgemeinerte Weise umsetzen
     * @return array of role constants (keys are constants, valus are constant-values)
     */
    public function getReadableRoles() {
        return $this->readableRoles;
    }
    /**
     * 
     * @return array of state constants (keys are constants, valus are constant-values)
     */
    public function getReadableStates() {
        return $this->readableStates;
    }
    /**
     * 
     * @return array of role constants (keys are constants, valus are constant-values)
     */
    public function getWriteableRoles() {
        return $this->writeableRoles;
    }
    /**
     * 
     * @return array of state constants (keys are constants, valus are constant-values)
     */
    public function getWriteableStates() {
        return $this->writeableStates;
    }

    /**
     * returns the TaskUserAssoc Entity to the given combination of $taskGuid and $userGuid, 
     * returns null if nothing found
     * @param string $taskGuid
     * @param string $userGuid
     * @return editor_Models_TaskUserAssoc returns null if nothing found
     */
    public function getTaskUserAssoc(string $taskGuid, string $userGuid) {
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        try {
            $tua->loadByParams($userGuid, $taskGuid);
            return $tua;
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            return null;
        }
    }
    
    /**
     * checks if the given TaskUserAssoc Instance allows reading of the task according to the Workflow Definitions
     * @param editor_Models_TaskUserAssoc $tua (default null is only to allow null as value)
     * @param boolean $useUsedState optional, per default false means using TaskUserAssoc field state, otherwise TaskUserAssoc field usedState
     * @return boolean
     */
    public function isReadable(editor_Models_TaskUserAssoc $tua = null, $useUsedState = false) {
        return $this->isTuaAllowed($this->getReadableRoles(), $this->getReadableStates(), $tua, $useUsedState);
    }
    
    /**
     * checks if the given TaskUserAssoc Instance allows writing to the task according to the Workflow Definitions
     * @param editor_Models_TaskUserAssoc $tua (default null is only to allow null as value)
     * @param boolean $useUsedState optional, per default false means using TaskUserAssoc field state, otherwise TaskUserAssoc field usedState
     * @return boolean
     */
    public function isWriteable(editor_Models_TaskUserAssoc $tua = null, $useUsedState = false) {
        return $this->isTuaAllowed($this->getWriteableRoles(), $this->getWriteableStates(), $tua, $useUsedState);
    }
    
    /**
     * FIXME this is a small ugly workaround for that fact that we do not differ 
     * between state transitions and "whats allowed" in a state. 
     * The isWriteable and isReadable methods are only used in conjunction with state 
     * transitions, and so cannot be used with the desired behaviour here. 
     * Here we want to know if a task can be written in the given state (which 
     * is currently only edit). See TRANSLATE-7 and TRANSLATE-18.
     * @param string $userState
     * @return boolean
     */
    public function isWritingAllowedForState($userState) {
        return $userState == self::STATE_EDIT;
    }
    
    /**
     * helper function for isReadable and isWriteable
     * @param array $roles
     * @param array $states
     * @param editor_Models_TaskUserAssoc $tua (default null is only to allow null as value)
     * @param boolean $useUsedState
     * @return boolean
     */
    protected function isTuaAllowed(array $roles, array $states, editor_Models_TaskUserAssoc $tua = null, $useUsedState = false) {
        if(empty($tua)) {
            return false;
        }
        $state = $useUsedState ? $tua->getUsedState() : $tua->getState();
        return in_array($tua->getRole(), $roles) && in_array($state, $states);
    }
    
    /**
     * returns true if a normal user can change the state of this assoc, false otherwise. 
     * false means that the user has finished this task already or the user is still waiting.
     * 
     * - does not look for the state of a task, only for state of taskUserAssoc
     * 
     * @param editor_Models_TaskUserAssoc $taskUserAssoc 
     * @return boolean
     */
    public function isStateChangeable(editor_Models_TaskUserAssoc $taskUserAssoc) {
        $state = $taskUserAssoc->getState();
        return !($state == self::STATE_FINISH || $state == self::STATE_WAITING);
    }

    /**
     * returns the possible start states for a transition to the target state
     * @param string $targetState
     * @return array
     */
    public function getAllowedTransitionStates($targetState) {
        if($targetState == self::STATE_OPEN){
            return array(self::STATE_EDIT, self::STATE_VIEW);
        }
        return array();
    }
    
    /**
     * simple debugging
     * @param string $name
     */
    protected function doDebug($name) {
        if(empty($this->debug)) {
            return;
        }
        if($this->debug == 1) {
            error_log(get_class($this).'::'.$name);
            return;
        }
        if($this->debug == 2) {
            error_log($name);
            error_log(print_r($this, 1));
        }
    }
    
    /**
     * manipulates the segment as needed by workflow after updated by user
     * @param editor_Models_Segment $segmentToSave
     */
    public function beforeSegmentSave(editor_Models_Segment $segmentToSave) {
        $updateAutoStates = function($autostates, $segment, $tua) {
            //sets the calculated autoStateId
            $segment->setAutoStateId($autostates->calculateSegmentState($segment, $tua));
        };
        $this->commonBeforeSegmentSave($segmentToSave, $updateAutoStates);
    }
    
    /**
     * manipulates the segment as needed by workflow after user has add or edit a comment of the segment
     */
    public function beforeCommentedSegmentSave(editor_Models_Segment $segmentToSave) {
        $updateAutoStates = function($autostates, $segment, $tua) {
            $autostates->updateAfterCommented($segment, $tua);
        };
        $this->commonBeforeSegmentSave($segmentToSave, $updateAutoStates);
    }
    
    /**
     * internal used method containing all common logic happend on a segment before saving it
     * @param editor_Models_Segment $segmentToSave
     * @param Closure $updateStates
     */
    protected function commonBeforeSegmentSave(editor_Models_Segment $segmentToSave, Closure $updateStates) {
        $session = new Zend_Session_Namespace();
        $sessionUser = new Zend_Session_Namespace('user');
        
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        
        //we assume that on editing a segment, every user (also not associated pms) have a assoc, so no notFound must be handled
        $tua->loadByParams($sessionUser->data->userGuid,$session->taskGuid);
        if($tua->getIsPmOverride() == 1){
            $segmentToSave->setWorkflowStepNr($session->taskWorkflowStepNr); //set also the number to identify in which phase the changes were done
            $segmentToSave->setWorkflowStep(self::STEP_PM_CHECK);
        }
        else {
            //sets the actual workflow step
            $segmentToSave->setWorkflowStepNr($session->taskWorkflowStepNr);
            
            //sets the actual workflow step name, does currently depend only on the userTaskRole!
            $step = $this->getStepOfRole($tua->getRole());
            $step && $segmentToSave->setWorkflowStep($step);
        }

        $autostates = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        
        //set the autostate as defined in the given Closure
        /* @var $autostates editor_Models_Segment_AutoStates */
        $updateStates($autostates, $segmentToSave, $tua);
    }
    
    /**
    - Methode wird beim PUT vom Task aufgerufen
    - bekommt den alten und den neuen Task, sowie den Benutzer übergeben
    - setzt die übergebenen Task und User Objekte zur weiteren Verwendung als Objekt Attribute
    - Anhand von der Statusänderung ergibt sich welche ""do"" Methode aufgerufen wird
    - Anhand der Statusänderung kann auch der TaskLog Eintrag erzeugt werden
    - Hier lässt sich zukünftig auch eine Zend_Acl basierte Rechteüberprüfung integrieren, ob der Benutzer die ermittelte Aktion überhaupt durchführen darf.
    - Hier lassen sich zukünftig auch andere Änderungen am Task abfangen"	1.6		x
     * 
     * @param editor_Models_Task $oldTask task as loaded from DB
     * @param editor_Models_Task $newTask task as going into DB (means not saved yet!)
     */
    public function doWithTask(editor_Models_Task $oldTask, editor_Models_Task $newTask) {
        $this->oldTask = $oldTask;
        $this->newTask = $newTask;
        $newState = $newTask->getState();
        $oldState = $oldTask->getState();
        //a segment mv creation is currently not needed, since doEnd deletes it, and doReopen creates it implicitly!

        if($newState == $oldState) {
            $this->doTaskChange();
            $this->events->trigger("doTaskChange", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
            return; //saved some other attributes, do nothing
        }
        switch($newState) {
            case $newTask::STATE_OPEN:
                if($oldState == $newTask::STATE_END) {
                    $this->doReopen();
                    $this->events->trigger("doReopen", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
                }
                if($oldState == $newTask::STATE_UNCONFIRMED) {
                    $this->doConfirm();
                    $this->events->trigger("doConfirm", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
                }
                break;
            case $newTask::STATE_END:
                $this->doEnd();
                $this->events->trigger("doEnd", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
                break;
            case $newTask::STATE_UNCONFIRMED:
                //doing currently nothing 
                break;
        }
    }
    
    /**
     * Method should be called every time a TaskUserAssoc is updated. Must be called after doWithTask if both methods are called.
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     */
    public function doWithUserAssoc(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua) {
        $this->doDebug(__FUNCTION__);
        $this->oldTaskUserAssoc = $oldTua;
        $this->newTaskUserAssoc = $newTua;
        
        if(empty($this->newTask)) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($newTua->getTaskGuid());
            $this->newTask = $task;
        }
        else {
            $task = $this->newTask;
        }
        //ensure that segment MV is createad
        $task->createMaterializedView();
        $this->recalculateWorkflowStep($newTua);
        $state = $this->getTriggeredState($oldTua, $newTua);
        if(!empty($state)) {
            if(method_exists($this, $state)) {
                $this->{$state}();
            } 
            $this->events->trigger($state, __CLASS__, array('oldTua' => $oldTua, 'newTua' => $newTua));
        }
    }
    
    /**
     * calls the actions configured to the trigger with given role and state
     * @param string $trigger
     * @param string $step can be empty
     * @param string $role can be empty
     * @param string $state can be empty
     */
    protected function callActions($trigger, $step = null, $role = null, $state = null) {
        $actions = ZfExtended_Factory::get('editor_Models_Workflow_Action');
        /* @var $actions editor_Models_Workflow_Action */
        $workflows = $this->getIdList();
        $actions = $actions->loadByTrigger($workflows, $trigger, $step, $role, $state);
        $this->doDebug($this->actionDebugMessage($workflows, $trigger, $step, $role, $state));
        $instances = [];
        foreach($actions as $action) {
            $class = $action['actionClass'];
            $method = $action['action'];
            if(empty($instances[$class])) {
                $instance = ZfExtended_Factory::get($class);
                /* @var $instance editor_Workflow_Actions_Abstract */
                $instance->init($this->getActionConfig());
                $instances[$class] = $instance;
            }
            else {
                $instance = $instances[$class];
            }
            
            $msg = $this->actionDebugMessage($action, $trigger, $step, $role, $state);
            $this->doDebug($msg);
            if(empty($action['parameters'])) {
                call_user_func([$instance, $method]);
                continue;
            }
            call_user_func([$instance, $method], json_decode($action['parameters']));
            if(json_last_error() != JSON_ERROR_NONE) {
                $this->doDebug('Last Workflow called action: JSON Parameters for last call could not be parsed with message: '.json_last_error_msg());
            }
        }
    }
    
    /**
     * generates a debug message for called actions
     * @param array $action
     * @param string $trigger
     * @param string $step
     * @param string $role
     * @param string $state
     * @return string
     */
    protected function actionDebugMessage(array $action, $trigger, $step, $role, $state) {
        if(!empty($action) && empty($action['actionClass'])) {
            //called in context before action load
            $msg = ' Try to load actions for workflow(s) "'.join(', ', $action).'" through trigger '.$trigger;
        }
        else {
            //called in context after action loaded
            $msg = ' Workflow called action '.$action['actionClass'].'::'.$action['action'].'() through trigger '.$trigger;
        }
        if(!empty($step)) {
            $msg .= "\n".' with step '.$step;
        }
        if(!empty($role)) {
            $msg .= "\n".' with role '.$role;
        }
        if(!empty($state)) {
            $msg .= "\n".' and state '.$state;
        }
        if(!empty($action['parameters'])) {
            $msg .= "\n".' and parameters '.$action['parameters'];
        }
        return $msg;
    }
    
    /**
     * prepares a config object for workflow actions
     * @return editor_Workflow_Actions_Config
     */
    protected function getActionConfig() {
        $config = ZfExtended_Factory::get('editor_Workflow_Actions_Config');
        /* @var $config editor_Workflow_Actions_Config */
        $config->workflow = $this;
        $config->newTua = $this->newTaskUserAssoc;
        $config->oldTask = $this->oldTask;
        $config->task = $this->newTask;
        $config->importConfig = $this->importConfig;
        $config->authenticatedUser = $this->authenticatedUserModel;
        return $config;
    }
    
    /**
     * recalculates the workflow step by the given task user assoc combinations
     * If the combination of roles and states are pointing to an specific workflow step, this step is used
     * If the states and roles does not match any valid combination, no step is changed. 
     * @param editor_Models_TaskUserAssoc $tua
     */
    protected function recalculateWorkflowStep(editor_Models_TaskUserAssoc $tua) {
        $tuas = $tua->loadByTaskGuidList([$tua->getTaskGuid()]);
        
        $areTuasSubset = function($toCompare) use ($tuas){
            foreach($tuas as $tua) {
                if(empty($toCompare[$tua['role']])) {
                    return false;
                }
                if(!in_array($tua['state'], $toCompare[$tua['role']])) {
                    return false;
                }
            }
            return true;
        };
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($tua->getTaskGuid());
        
        $msg = ZfExtended_Factory::get('ZfExtended_Models_Messages');
        /* @var $msg ZfExtended_Models_Messages */
        
        $matchingSteps = [];
        foreach($this->validStates as $step => $roleStates) {
            if(!$areTuasSubset($roleStates)) {
                continue;
            }
            $matchingSteps[] = $step;
        }
        
        //if the current step is one of the possible steps for the tua configuration
        // then everything is OK, 
        // or if no valid configuration is found, then we also could not change the step
        if(empty($matchingSteps) || in_array($task->getWorkflowStepName(), $matchingSteps)) {
            return;
        }
        //set the first found valid step to the current workflow step
        $step = reset($matchingSteps);
        $task->updateWorkflowStep($step, false);
        $log = ZfExtended_Factory::get('editor_Workflow_Log');
        /* @var $log editor_Workflow_Log */
        $log->log($task, $this->authenticatedUser->userGuid);
        //set $step as new workflow step if different to before!
        $labels = $this->getLabels();
        $steps = $this->getSteps();
        $step = $labels[array_search($step, $steps)];
        $msg->addNotice('Der Workflow Schritt der Aufgabe wurde zu "{0}" geändert!', 'core', null, $step);
        return;
    }
    
    /**
     * triggers a beforeSTATE event
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     */
    public function triggerBeforeEvents(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua) {
        $state = $this->getTriggeredState($oldTua, $newTua, 'before');
        $this->events->trigger($state, __CLASS__, array('oldTua' => $oldTua, 'newTua' => $newTua));
    }
    
    /**
     * method returns the triggered state as string ready to use in events, these are mainly:
     * doUnfinish, doView, doEdit, doFinish, doWait
     * beforeUnfinish, beforeView, beforeEdit, beforeFinish, beforeWait
     * 
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     * @param $prefix optional, defaults to "do"
     * @return string
     */
    public function getTriggeredState(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua, $prefix = 'do') {
        $oldState = $oldTua->getState();
        $newState = $newTua->getState();
        if($oldState == $newState) {
            return null;
        }
        
        if($oldState == self::STATE_FINISH && $newState != self::STATE_FINISH) {
            return $prefix.'Unfinish';
        }
        
        switch($newState) {
            case $this::STATE_OPEN:
                return $prefix.'Open';
            case $this::STATE_VIEW:
                return $prefix.'View';
            case $this::STATE_EDIT:
                return $prefix.'Edit';
            case self::STATE_FINISH:
                return $prefix.'Finish';
            case self::STATE_WAITING:
                return $prefix.'Wait';
        }
        return null;
    }

    /**
     * Sets the new workflow step in the given task and increases by default the workflow step nr
     * @param editor_Models_Task $task
     * @param string $stepName
     */
    protected function setNextStep(editor_Models_Task $task, $stepName) {
        $this->doDebug(__FUNCTION__);
        $task->updateWorkflowStep($stepName, true);
        $log = ZfExtended_Factory::get('editor_Workflow_Log');
        /* @var $log editor_Workflow_Log */
        $log->log($task, $this->authenticatedUser->userGuid);
        //call action directly without separate handler method
        $newTua = $this->newTaskUserAssoc;
        $this->callActions('handleSetNextStep', $stepName, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * Inits the workflow step in the given task
     * @param editor_Models_Task $task
     * @param string $stepName
     */
    protected function initWorkflowStep(editor_Models_Task $task, $stepName) {
        $task->updateWorkflowStep($stepName, false);
        $log = ZfExtended_Factory::get('editor_Workflow_Log');
        /* @var $log editor_Workflow_Log */
        //since we are in the import, we don't have the current user, so we use the pmGuid user of the task:
        $log->log($task, $task->getPmGuid()); 
    }
    
    /*
    //DO Methods.
     the do.. methods 
    - are called by doWithTask and doWithTaskUserAssoc, according to the changed states
    - can contain further logic to call different "handle" Methods, can also been overwritten
     */
    
    /**
     * is called directly after import
     * @param editor_Models_Task $importedTask
     */
    public function doImport(editor_Models_Task $importedTask, editor_Models_Import_Configuration $importConfig) {
        $this->newTask = $importedTask;
        $this->importConfig = $importConfig;
        $this->handleImport();
    }
    
    /**
     * is called after a user association is added
     * @param editor_Models_TaskUserAssoc $tua
     */
    public function doUserAssociationAdd(editor_Models_TaskUserAssoc $tua) {
        $this->newTaskUserAssoc = $tua;
        $this->handleUserAssociationAdded();
    }
    
    /**
     * can be triggered via API, valid triggers are currently
     * @param editor_Models_Task $task
     * @param unknown $trigger
     */
    public function doDirectTrigger(editor_Models_Task $task, $trigger) {
        if(!in_array($trigger, $this->validDirectTrigger)) {
            return false;
        }
        $this->newTask = $task;
        
        //try to load an user assoc between current user and task 
        $this->newTaskUserAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        try {
            $this->newTaskUserAssoc->loadByParams($this->authenticatedUser->userGuid, $task->getTaskGuid());
            $role = $this->newTaskUserAssoc->getRole();
            $state = $this->newTaskUserAssoc->getState();
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->newTaskUserAssoc = null;
            $role = null;
            $state = null;
        }
        $this->callActions('handleDirect::'.$trigger, $task->getWorkflowStepName());
        return true;
    }
    
    /**
     * returns the valid direct trigger
     * @return string[]
     */
    public function getDirectTrigger() {
        return $this->validDirectTrigger;
    }
    
    /**
     * is called on ending
     */
    protected function doEnd() {
        $this->handleEnd();
    }

    /**
     * is called on re opening a task
     */
    protected function doReopen() {
        $this->handleReopen();
    }
    
    /**
     * is called when a task assoc state gets OPEN
     */
    protected function doOpen() {
    }
    
    /**
     * is called when a task is opened coming from state unconfirmed
     */
    protected function doConfirm() {
    }
    
    /**
     * is called when a task changes via API
     */
    protected function doTaskChange() {
        $function = 'handleTaskChange';
        $this->doDebug($function);
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        try {
            $tua->loadByParams($this->authenticatedUser->userGuid, $this->newTask->getTaskGuid());
            $this->callActions($function, $this->newTask->getWorkflowStepName(), $tua->getRole(), $tua->getState());
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->callActions($function, $this->newTask->getWorkflowStepName());
        }
    }
    
    /**
     * is called on finishin a task
     * evaluates the role and states of the User Task Association and calls the matching handlers:
     */
    protected function doFinish() {
        $this->doDebug(__FUNCTION__);
        $userTaskAssoc = $this->newTaskUserAssoc;
        $stat = $userTaskAssoc->getUsageStat();
        $allFinished = true;
        $roleAllFinished = true;
        $sum = 0;
        foreach($stat as $entry) {
            $isRole = $entry['role'] === $userTaskAssoc->getRole();
            $isFinish = $entry['state'] === self::STATE_FINISH;
            if($isRole && $roleAllFinished && ! $isFinish) {
                $roleAllFinished = false;
            }
            if($allFinished && ! $isFinish) {
                $allFinished = false;
            }
            if($isRole && $isFinish && (int)$entry['cnt'] === 1) {
                $this->handleFirstFinishOfARole(); 
            }
            if($isFinish) {
                $sum += (int)$entry['cnt'];
            }
        }
        if($sum === 1) {
            $this->handleFirstFinish();
        }
        if($roleAllFinished) {
            $this->handleAllFinishOfARole(); 
        }
        if($allFinished) {
            $this->handleAllFinish(); 
        }
        $this->handleFinish();
    }
    
    /**
     * is called on wait for a task
     */
    protected function doWait() {
        
    }
    
    /**
     * is called on reopening / unfinishing a task
     */
    protected function doUnfinish() {
        $this->handleUnfinish();
    }
    
    /**
     * will be called after task import, the imported task is available in $this->newTask
     * @abstract
     */
    abstract protected function handleImport();
    
    /**
     * will be called directly before import is started, task is already created and available
     */
    abstract protected function handleBeforeImport();
    
    /**
     * will be called after a user has finished a task
     * @abstract
     */
    abstract protected function handleFinish();
    
    /**
     * will be called after first user of a role has finished a task
     * @abstract
     */
    abstract protected function handleFirstFinishOfARole();
    
    /**
     * will be called after all users of a role has finished a task
     * @abstract
     */
    abstract protected function handleAllFinishOfARole();
    
    /**
     * will be called after a user has finished a task
     * @abstract
     */
    abstract protected function handleFirstFinish();
    
    /**
     * will be called after all associated users of a task has finished a task
     * @abstract
     */
    abstract protected function handleAllFinish();
    
    /**
     * will be called after a task has been ended
     * @abstract
     */
    abstract protected function handleEnd();
    
    /**
     * will be called after a task has been reopened (after was ended - task-specific)
     * @abstract
     */
    abstract protected function handleReopen();
    
    /**
     * will be called after a task has been unfinished (after was finished - taskassoc-specific)
     * @abstract
     */
    abstract protected function handleUnfinish();
    
    /**
     * will be called daily
     */
    abstract public function doCronDaily();
    
    /**
     * will be called when a new task user association is created
     */
    abstract protected function handleUserAssociationAdded();
}
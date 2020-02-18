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
 * Encapsulates the Default Notifications triggered by the Workflow
 * Basicly the Notifications are E-Mail based. But this class can be overwritten
 * to redirect the generated mailer texts to over notification channels
 */
class editor_Workflow_Notification extends editor_Workflow_Actions_Abstract {
    /**
     * @var ZfExtended_TemplateBasedMail
     */
    protected $mailer;
    
    /**
     * @var array
     */
    protected $xmlCache = array();
    
    /**
     * reusable $tua instance, instanced if needed, must be set explictly by the called notify method
     * @var editor_Models_TaskUserAssoc
     */
    protected $tua;
    
    /**
     * generates and returns the template path.
     * @param string $role the affected workflow role string
     * @param string $template the template name
     */
    protected function getMailTemplate(string $role, string $template) {
        return 'workflow/'.$role.'/'.$template.'.phtml';
    }
    
    /**
     * returns a list with PM Users (currently only one)
     * @return [array] array with Pm User Data Arrays
     */
    protected function getTaskPmUsers(){
        $task = $this->config->task;
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($task->getPmGuid());
        return array((array)$user->getDataObject());
    }
    
    /**
     * perhaps this method should be moved to another location (into the workflow?)
     */
    protected function getStepSegments(string $step) {
        $task = $this->config->task;
        //attention, in context of increasing the stepNr, the current task from config always contains the old stepNr! 
        // The new one must be loaded from DB!
        $stepNr = $task->getWorkflowStep(); 
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        return $segment->loadByWorkflowStep($task->getTaskGuid(), $step, $stepNr);
    }
    
    /**
     * creates the Notification and stores it internally
     * @param string $role
     * @param string $template
     * @param array $parameters
     */
    protected function createNotification(string $role, string $template, array $parameters) {
        $this->mailer = ZfExtended_Factory::get('ZfExtended_TemplateBasedMail');
        $this->mailer->setParameters($parameters);
        $this->mailer->setTemplate($this->getMailTemplate($role, $template));
    }
    
    /**
     * send the latest created notification to the list of users
     * @param array $userData
     */
    protected function notify(array $userData) {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->init($userData);
        $this->mailer->sendToUser($user);
    }
    
    /**
     * send the latest created notification to the list of users
     * @param ZfExtended_Models_User $user
     */
    protected function notifyUser(ZfExtended_Models_User $user) {
        $this->mailer->sendToUser($user);
    }
    
    /**
     * Adds the users of the given cc/bcc role config to the email - if receiverRole is configured in config
     * @param stdClass $triggerConfig the config object given in action matrix
     * @param string $receiverRole the original receiver role of the notification to be sended
     */
    protected function addCopyReceivers(stdClass $triggerConfig, $receiverRole) {
        $task = $this->config->task;
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */

        $tua = empty($this->tua) ? ZfExtended_Factory::get('editor_Models_TaskUserAssoc') : $this->tua;
        
        $addReceivers = function($receiverRoleMap, $bcc = false) use ($receiverRole, $task, $user, $tua) {
            $users = [];
            foreach($receiverRoleMap as $recRole => $roles) {
                if($recRole == '*' || $recRole == $receiverRole) {
                    foreach($roles as $role) {
                        $users = array_merge($users,$tua->loadUsersOfTaskWithRole($task->getTaskGuid(), $role));
                    }
                }
                if($recRole == 'byUserLogin') {
                    $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
                    /* @var $userModel ZfExtended_Models_User */
                    foreach($roles as $singleUser) {
                        try {
                            $userModel->loadByLogin($singleUser);
                            $users[] = (array) $userModel->getDataObject();
                        }
                        catch (ZfExtended_Models_Entity_NotFoundException $e) {
                            // do nothing if the user was not found
                        }
                    }
                }
            }
            foreach($users as $userData) {
                $user->init($userData);
                if($bcc) {
                    $this->mailer->addBcc($user->getEmail());
                }
                else {
                    $this->mailer->addCc($user->getEmail(), $user->getUserName());
                }
            }
        };
        
        $addReceivers($triggerConfig->cc);
        $addReceivers($triggerConfig->bcc, true);
    }
    
    /**
     * Initiales the internal trigger configuration through the given parameters and returns it
     * currently the following configuration parameters exist:
     * pmBcc boolean, true if the pm of the task should also receive the notification
     * rolesBcc array, list of workflow roles which also should receive the notification
     * @param $config
     * @return stdClass
     */
    protected function initTriggerConfig(array $config) {
        $defaultConfig = new stdClass();
        $defaultConfig->cc = [];
        $defaultConfig->bcc = [];
        if(empty($config)) {
            return $defaultConfig;
        }
        $config = reset($config);
        if(empty($config)) {
            return $defaultConfig;
        }
        foreach($config as $key => $v) {
            $defaultConfig->{$key} = $v;
        }
        return $defaultConfig;
    }
    
    /**
     * Feel free to define additional Notifications
     */
    /**
     * Workflow specific Notification after all users of a role have finished a task
     * @param string $triggeringRole
     * @param bool $isCron
     */
    public function notifyAllFinishOfARole() {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $task = $this->config->task;
        $workflow = $this->config->workflow;
        $isCron = $workflow->isCalledByCron();
        $triggeringRole = $this->config->newTua->getRole();
        $this->tua = clone $this->config->newTua; //we just reuse the already used entity
        $currentStep = $workflow->getStepOfRole($triggeringRole);
        if($currentStep === false){
            $this->log->warn('E1013', 'No workflow step to Role {role} found! This is actually a workflow config error!', [
                'task' => $task,
                'role' => $triggeringRole,
            ]);
        }
        $segments = $this->getStepSegments($currentStep);
        
        $segmentHash = md5(print_r($segments,1)); //hash to identify the given segments (for internal caching)
        
        $nextRole = $workflow->getRoleOfStep((string)$workflow->getNextStep($currentStep));
        
        
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $users = $tua->loadUsersOfTaskWithRole($task->getTaskGuid(), $nextRole);
        $previousUsers = $tua->loadUsersOfTaskWithRole($task->getTaskGuid(), $triggeringRole);
        $params = array(
            'triggeringRole' => $triggeringRole,
            'nextRole' => $nextRole,
            'segmentsHash' => $segmentHash,
            'segments' => $segments,
            'isCron' => $isCron,
            'users' => $users,
            'previousUsers' => $previousUsers,
            'task' => $task,
            'workflow' => $workflow
        );
        //send to the PM
        $pms = $this->getTaskPmUsers();
        foreach($pms as $pm) {
            $this->createNotification(ACL_ROLE_PM, __FUNCTION__, $params); //@todo PM currently not defined as WORKFLOW_ROLE, so hardcoded here
            $this->attachXliffSegmentList($segmentHash, $segments,$currentStep);
            $this->addCopyReceivers($triggerConfig, ACL_ROLE_PM);
            $this->notify($pm);
        }
        
        if(!$nextRole){
            return;
        }
        
        //send to each user of the targetRole
        foreach($users as $user) {
            $params['user'] = $user;
            $this->createNotification($nextRole, __FUNCTION__, $params);
            $this->attachXliffSegmentList($segmentHash, $segments,$currentStep);
            $this->addCopyReceivers($triggerConfig, $nextRole);
            $this->notify($user);
        }
    }
    
    /**
     * Workflow specific PM Notification after one users of a role have finished a task
     * @param string $triggeringRole
     * @param bool $isCron
     */
    public function notifyOneFinishOfARole() {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $task = $this->config->task;
        $workflow = $this->config->workflow;
        $isCron = $workflow->isCalledByCron();
        if($isCron) {
            //currently we do not trigger the notifyOne on cron actions (since currently there are all users set to finish)
            return;
        }
        $triggeringRole = $this->config->newTua->getRole();
        $this->tua = clone $this->config->newTua; //we just reuse the already used entity
        $currentStep = $workflow->getStepOfRole($triggeringRole);
        if($currentStep === false){
            $this->log->warn('E1013', 'No workflow step to Role {role} found! This is actually a workflow config error!', [
                'task' => $task,
                'role' => $triggeringRole,
            ]);
        }
        
        $currentUsers = $this->tua->loadUsersOfTaskWithRole($task->getTaskGuid(), $triggeringRole, ['state']);
        $params = array(
            'triggeringRole' => $triggeringRole,
            'currentUsers' => $currentUsers,
            'task' => $task,
            'workflow' => $workflow
        );
        
        //set the triggering user
        $params['currentUser'] = [];
        foreach($currentUsers as $user) {
            if($user['userGuid'] == $this->tua->getUserGuid()) {
                $params['currentUser'] = $user;
            }
        }
        
        //send to the PM
        $pms = $this->getTaskPmUsers();
        foreach($pms as $pm) {
            $this->createNotification(ACL_ROLE_PM, __FUNCTION__, $params); //@todo PM currently not defined as WORKFLOW_ROLE, so hardcoded here
            $this->addCopyReceivers($triggerConfig, ACL_ROLE_PM);
            $this->notify($pm);
        }
    }
    
    /**
     * Sends a notification to users which are removed automatically from the task
     * The Users to be notified must be given in the parameter array key 'deleted' 
     */
    public function notifyCompetitiveDeleted(array $parameter) {
        $triggerConfig = $this->initTriggerConfig([$parameter]);
        $this->tua = $this->config->newTua;
        settype($triggerConfig->deleted, 'array');
        
        if ($this->config->task->anonymizeUsers(false)) {
            $params = [];
        }
        else {
            $params = [
                //we do not pass the whole userObject to keep data private 
                'responsibleUser' => [
                    'surName' => $triggerConfig->currentUser->surName,
                    'firstName' => $triggerConfig->currentUser->firstName,
                    'login' => $triggerConfig->currentUser->login,
                    'email' => $triggerConfig->currentUser->email,
                ]
            ];
        }
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        foreach($triggerConfig->deleted as $deleted) {
            $user->loadByGuid($deleted['userGuid']);
            $workflow = $this->config->workflow;
            $labels = $workflow->getLabels(false);
            $roles = $workflow->getRoles();
            $params['task'] = $this->config->task;
            $params['role'] = $labels[array_search($deleted['role'], $roles)];
            
            $this->createNotification($deleted['role'], __FUNCTION__, $params);
            $this->addCopyReceivers($triggerConfig, $deleted['role']);
            $this->notifyUser($user);
        }
    }
    
    /**
     * Sends a notification to users which are attached newly to a task with status open
     * The User to be notified is gathered from the current active TaskUserAssociation
     */
    public function notifyNewTaskAssigned() {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $this->tua = $tua = $this->config->newTua;
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $pm = clone $user;
        $pm->loadByGuid($this->config->task->getPmGuid());
        $user->loadByGuid($tua->getUserGuid());
        $workflow = $this->config->workflow;
        $labels = $workflow->getLabels(false);
        $roles = $workflow->getRoles();
        $params = [
            'pm' => $pm,
            'task' => $this->config->task,
            'role' => $labels[array_search($tua->getRole(), $roles)],
        ];
        
        $this->createNotification($tua->getRole(), __FUNCTION__, $params);
        $this->addCopyReceivers($triggerConfig, $tua->getRole());
        $this->notifyUser($user);
    }
    
    /**
     * Notifies all associated users about the task association
     * Main difference to notifyNewTaskAssigned to a single user:
     *  This notification contains a list of all assigned users.
     */
    public function notifyAllAssociatedUsers() {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $task = $this->config->task;
        $this->tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        
        $workflow = $this->config->workflow;
        $labels = $workflow->getLabels(false);
        $tuas = $this->tua->loadUsersOfTaskWithRole($task->getTaskGuid(), $triggerConfig->role ?? null, ['state', 'role']);
        
        $roles = array_column($tuas, 'role');
        //sort first $roles, then sort $tuas to the same order (via the indexes)
        array_multisort($roles, SORT_ASC, SORT_STRING, $tuas);
        $aRoleOccursMultipleTimes = count($roles) !== count(array_flip($roles));
        foreach($tuas as &$tua) {
            $tua['originalRole'] = $tua['role'];
            $tua['role'] = $labels[array_search($tua['role'], $workflow->getRoles())];
        }
        unset ($tua);
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $pm = clone $user;
        $pm->loadByGuid($task->getPmGuid());
        
        $params = [
            'pm' => $pm,
            'task' => $this->config->task,
            'associatedUsers' => $tuas,
            //the disclaimer is needed only, if from one role multiple users are assigned. If it is only one reviewer then no dislaimer is needed
            'addCompetitiveDisclaimer' => $aRoleOccursMultipleTimes && $task->getUsageMode() == $task::USAGE_MODE_COMPETITIVE
        ];
        
        foreach($tuas as $tua) {
            $params['role'] = $tua['role'];
            //we assume the PM user for all roles, since it is always the same template
            $this->createNotification(ACL_ROLE_PM, 'notifyNewTaskAssigned', $params);
            $user->loadByGuid($tua['userGuid']);
            $this->addCopyReceivers($triggerConfig, $tua['originalRole']);
            $this->notifyUser($user);
        }
    }
    
    /**
     * Notifies the tasks PM over the new task, but only if PM != the user who has uploaded the task
     */
    public function notifyNewTaskForPm() {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $task = $this->config->task;
        $pmGuid = $task->getPmGuid();
        $importConf = $this->config->importConfig;
        
        //if the user who imports the task is the same as the PM, we don't send the mail
        // also this mail is not possible at all, if no import config is given
        if(empty($importConf) || $importConf->userGuid == $pmGuid) {
            return;
        }
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($pmGuid);
        
        $params = [
            'task' => $task,
            'user' => (array) $user->getDataObject(),
            'sourceLanguage' => $importConf->sourceLang->getLangName(),
            'targetLanguage' => $importConf->targetLang->getLangName(),
            'relaisLanguage' => (empty($importConf->relaisLang) ? '' : $importConf->relaisLang->getLangName())
        ];
        
        $this->createNotification(ACL_ROLE_PM, __FUNCTION__, $params);
        $this->addCopyReceivers($triggerConfig, ACL_ROLE_PM);
        $this->notifyUser($user);
    }
    
    
    /***
     * Notify the lectors when the delivery date is over the defined days in the config
     */
    public function notifyOverdueTasks(){
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $daysOffset=isset($triggerConfig->daysOffset) ? $triggerConfig->daysOffset : 1;
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        
        //load all tasks where the delivery date overdue the current date - days offset
        $db = Zend_Registry::get('db');
        /* @var $db Zend_Db_Table */
        $s = $db->select()
        ->from(array('tua' => 'LEK_taskUserAssoc'), ['taskGuid'])
        ->distinct()
        ->join(array('t' => 'LEK_task'), 'tua.taskGuid = t.taskGuid', array())
        ->where('tua.role = ?', editor_Workflow_Abstract::ROLE_REVIEWER)
        ->where('tua.state != ?', editor_Workflow_Abstract::STATE_FINISH)
        ->where('t.state = ?', $task::STATE_OPEN)
        ->where('targetDeliveryDate = CURRENT_DATE - INTERVAL ? DAY', $daysOffset);
        $tasks = $db->fetchAll($s);
        
        $this->tua = $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        
        $wf = $this->config->workflow;
        
        $notifyRole=$wf::ROLE_REVIEWER;
        if(isset($triggerConfig->receiverRole)){
            $notifyRole=$triggerConfig->receiverRole;
        }
        $template=__FUNCTION__;
        if(isset($triggerConfig->template)){
            $template=$triggerConfig->template;
        }
        
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        if(isset($triggerConfig->receiverUser)){
            $user->loadByLogin($triggerConfig->receiverUser);
        }
        
        foreach($tasks as $oneTask) {
            $task->loadByTaskGuid($oneTask['taskGuid']);
            $this->config->task = clone $task;
            
            //if the receiverUser user is configured, send mail only to receiverUser
            if(isset($triggerConfig->receiverUser)){
                $reviewers=[(array)$user->getDataObject()];
            }else{
                //load only users with state open
                $reviewers = $tua->loadUsersOfTaskWithRole($oneTask['taskGuid'], $notifyRole, ['state'], editor_Models_Task::STATE_OPEN);
            }
            
            $params = [
                'task' => $this->config->task,
                'reviewers' => $reviewers,
            ];
            
            foreach($reviewers as $reviewer) {
                $this->createNotification($notifyRole, $template, $params);
                $this->addCopyReceivers($triggerConfig, $notifyRole);
                $this->notify($reviewer);
            }
        }
    }
    
    /**
     * Notify the configured user with term and term attribute proposals of the configured or all termcollections
     * The attached export data in the mail will be in excel format.
     */
    public function notifyTermProposals(){
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        if(!isset($triggerConfig->receiverUser) || empty($triggerConfig->receiverUser)){
            return;
        }
        
        //use all collections if no collections are given in workflow action config
        if(empty($triggerConfig->collections)) {
            $service=ZfExtended_Factory::get('editor_Services_TermCollection_Service');
            /* @var $service editor_Services_TermCollection_Service */
            $lr=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $lr editor_Models_LanguageResources_LanguageResource */
            
            //load all existing term collections
            $collections=$lr->loadByResourceId($service->getServiceNamespace());
            $collections=array_column($collections,'id');
        }
        else {
            $collections = $triggerConfig->collections;
            if(!is_array($collections)) {
                $collections = [$collections];
            }
        }
        
        if(empty($collections)){
            return;
        }
        
        $proposals=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $proposals editor_Models_Term */
        
        //load the term and term entry proposals data for all term collections and younger as $exportDate
        $rows = $proposals->loadProposalExportData($collections);
        if(empty($rows)){
            return;
        }
        
        $file=tempnam(APPLICATION_PATH.'/../data/tmp/','').'xlsx';

        //create tmp file in the tmp directory of translate5
        $proposals->exportProposals($rows,$file);
        
        //create the notification with the xlsx file
        $attachment = array(
            'body' => file_get_contents($file),
            'mimeType' => Zend_Mime::TYPE_OCTETSTREAM,
            'disposition' => Zend_Mime::DISPOSITION_ATTACHMENT,
            'encoding' => Zend_Mime::ENCODING_BASE64,
            'filename' => 'Proposals.xlsx',
        );
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByLogin($triggerConfig->receiverUser);
        
        $this->createNotification('visitor', __FUNCTION__, []);
        $this->mailer->setAttachment([$attachment]);
        $this->notifyUser($user);
        
        //remove the tmp file from the disc
        unlink($file);
    }
    
    
    /***
     * Notify the the associated users when the deadlineDate is over the defined days in the config
     */
    public function notifyOverdueDeadline(){
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $daysOffset=isset($triggerConfig->daysOffset) ? $triggerConfig->daysOffset : 1;
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        
        //load all task assocks where the deadlineDate date overdue the current date - days offset
        $db = Zend_Registry::get('db');
        /* @var $db Zend_Db_Table */
        $s = $db->select()
        ->from(array('tua' => 'LEK_taskUserAssoc'), ['userGuid','role','deadlineDate'])
        ->join(array('t' => 'LEK_task'), 'tua.taskGuid = t.taskGuid',['taskName'])
        ->where('t.state NOT IN(?)', [$task::STATE_ERROR,$task::STATE_END])
        ->where('tua.state NOT IN(?)', [editor_Workflow_Abstract::STATE_FINISH])
        ->where('tua.deadlineDate = CURRENT_DATE - INTERVAL ? DAY', $daysOffset);
        $userAssocs = $db->fetchAll($s);
        
        foreach($userAssocs as $assoc) {
            $user = ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $user ZfExtended_Models_User */
            $user->loadByGuid($assoc['userGuid']);
            $this->createNotification($assoc['role'],__FUNCTION__,[
                'taskName' => $assoc['taskName'],
                'deadlineDate' =>$assoc['deadlineDate']
            ]);
            $this->notifyUser($user);
        }
    }
    
    
    /**
     * attaches the segmentList as attachment to the internal mailer object
     * @param string $segmentHash
     * @param array $segments
     * @param string $currentStep
     */
    protected function attachXliffSegmentList($segmentHash, array $segments,$currentStep) {
        $config = Zend_Registry::get('config');
        $notifyConfig = $config->runtimeOptions->editor->notification;
        $xlfAttachment = (boolean) $notifyConfig->enableSegmentXlfAttachment;
        $xlfFile =       (boolean) $notifyConfig->saveXmlToFile;
        
        if(empty($segments) || (!$xlfAttachment && !$xlfFile)) {
            return;
        }
        if(empty($this->xmlCache[$segmentHash])) {
            $xliffConverter=$this->getXliffConverter($currentStep,$config);
            
            if(!$xliffConverter){
                $this->log->warn('E1013', 'Error on xliff converter initialization', [
                    'task' => $this->config->task
                ]);
                return;
            }
            
            try {
                $this->xmlCache[$segmentHash] = $xliff = $xliffConverter->convert($this->config->task, $segments);
            }
            catch(Exception $e) {
                $msg = 'changes.xliff could not be created';
                $this->log->warn('E1013', $msg, [
                    'task' => $this->config->task
                ]);
                $this->log->exception($e, ['level' => $this->log::LEVEL_WARN]);
                //if file saving is enabled we save the file with the debug content 
                $this->xmlCache[$segmentHash] = $xliff = $msg;
                //but we disable attaching it to the mail:
                $xlfAttachment = false;
            }
            
            if($xlfFile) {
                $this->saveXmlToFile($xliff);
            }
        }
        
        if(!$xlfAttachment) {
            return;
        }
        $attachment = array(
            'body' => $this->xmlCache[$segmentHash],
            'mimeType' => Zend_Mime::TYPE_OCTETSTREAM,
            'disposition' => Zend_Mime::DISPOSITION_ATTACHMENT,
            'encoding' => Zend_Mime::ENCODING_BASE64,
            'filename' => 'changes.xliff',
        );
        $this->mailer->setAttachment(array($attachment));
    }
    
    protected function saveXmlToFile($xml) {
        $path = $this->config->task->getAbsoluteTaskDataPath();
        if(!is_dir($path) || !is_writeable($path)) {
            $this->log->warn('E1013', 'cant write changes.xliff file to path: {path}', [
                'task' => $this->config->task,
                'path' => $path
            ]);
            return;
        }
        $suffix = '.xliff';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //windows can not deal with the : in the filename
            $filename = 'changes-'.date('Y-m-d\TH-i-s');
        } else {
            //for linux we leave it for compatibility reasons
            $filename = 'changes-'.date('Y-m-d\TH:i:s');
        }
        $i = 0;
        $outFile = $path.DIRECTORY_SEPARATOR.$filename.$suffix;
        while(file_exists($outFile)) {
            $outFile = $path.DIRECTORY_SEPARATOR.$filename.'-'.($i++).$suffix;
        }
        if(file_put_contents($outFile, $xml) == 0) {
            $this->log->warn('E1013', 'Error on writing XML File: {path}', [
                'task' => $this->config->task,
                'path' => $outFile
            ]);
        }
    }
    
    /***
     * Return the xliff or xliff2 converted depending on the xliff2Active config
     * @param string $currentStep
     * @param Zend_Config $config
     * @return editor_Models_Converter_SegmentsToXliff
     */
    private function getXliffConverter($currentStep,$config){
        $xliff2Active =       (boolean) $config->runtimeOptions->editor->notification->xliff2Active;
        
        //if the config is active, convert segments to xliff2 format
        if($xliff2Active){
            $xliffConf = [
                    editor_Models_Converter_SegmentsToXliff2::CONFIG_ADD_TERMINOLOGY=>true,
                    editor_Models_Converter_SegmentsToXliff2::CONFIG_INCLUDE_DIFF=>false,
                    editor_Models_Converter_SegmentsToXliff2::CONFIG_ADD_QM=>true,
            ];
            return ZfExtended_Factory::get('editor_Models_Converter_SegmentsToXliff2', [$xliffConf, $currentStep]);
        }
        
        $xliffConf = [
                editor_Models_Converter_SegmentsToXliff::CONFIG_INCLUDE_DIFF => (boolean) $config->runtimeOptions->editor->notification->includeDiff,
                editor_Models_Converter_SegmentsToXliff::CONFIG_PLAIN_INTERNAL_TAGS => true,
                editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_ALTERNATIVES => true,
                editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_TERMINOLOGY => true,
        ];
        return ZfExtended_Factory::get('editor_Models_Converter_SegmentsToXliff', [$xliffConf]);
    }
    
    public function testNotifications() {
        $user = [
            'firstName' => 'Thomas',
            'surName' => 'Lauria',
            'login' => 'fakeuser',
            'email' => 'thomas@mittagqi.com',
            'locale' => 'en',
        ];
        $users = [
            ['firstName' => 'Thomas', 'surName' => 'Lauria','login' => 'fakeuser','email' => 'thomas@mittagqi.com','role' => 'Testrole','state' => 'Teststatus'],
            ['firstName' => 'XXX', 'surName' => 'YYY','login' => 'fakeuser2','email' => 'thomas@mittagqi.com','role' => 'Testrole2','state' => 'Teststatus2'],
        ];
        $params = [
            'task' => $this->config->task,
            'associatedUsers' => $users,
        ];
        
        $roles = $this->config->workflow->getRoles();
        
        foreach($roles as $role){
            $this->createNotification($role, 'notifyNewTaskAssigned', $params);
            //$this->addCopyReceivers($triggerConfig, $tua['role']);
            $this->notify($user);
        }
        
        $params = [
            'task' => $this->config->task,
            'user' => $user,
            'sourceLanguage' => 'German',
            'targetLanguage' => 'English',
            'relaisLanguage' => 'French Relais'
        ];
        
        $this->createNotification(ACL_ROLE_PM, 'notifyNewTaskForPm', $params);
        //$this->addCopyReceivers($triggerConfig, ACL_ROLE_PM);
        $this->notify($user);
        
        $segmentHash = "123";
        $segments = json_decode(file_get_contents(APPLICATION_PATH.'/modules/editor/testcases/editorAPI/XlfImportTest/expectedSegments.json'),true);
        $segments = array_map(function($item){
            $item['fileId'] = 1;
            return $item;
        }, $segments);
        $segments = [];
        $currentStep = 'reviewing';
        $params = array(
            'triggeringRole' => 'triggerROLE',
            'nextRole' => 'nextROLE',
            'segmentsHash' => $segmentHash,
            'segments' => $segments,
            'isCron' => true,
            'users' => $users,
            'previousUsers' => $users,
            'task' => $this->config->task,
            'workflow' => $this->config->workflow
        );
        
        $this->createNotification(ACL_ROLE_PM, 'notifyAllFinishOfARole', $params); //@todo PM currently not defined as WORKFLOW_ROLE, so hardcoded here
        $this->attachXliffSegmentList($segmentHash, $segments,$currentStep);
        //$this->addCopyReceivers($triggerConfig, ACL_ROLE_PM);
        $this->notify($user);
        
        $params['user'] = $user;
        $this->createNotification('translatorCheck', 'notifyAllFinishOfARole', $params);
        $this->attachXliffSegmentList($segmentHash, $segments,$currentStep);
        //$this->addCopyReceivers($triggerConfig, $nextRole);
        $this->notify($user);
        $this->createNotification('reviewer', 'notifyAllFinishOfARole', $params);
        $this->attachXliffSegmentList($segmentHash, $segments,$currentStep);
        //$this->addCopyReceivers($triggerConfig, $nextRole);
        $this->notify($user);
    }
}
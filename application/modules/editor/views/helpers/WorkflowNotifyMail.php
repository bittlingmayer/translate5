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
 * @package portal
 * @version 2.0
 *
 */
/**
 * Utility functions usable in workflow notification E-Mails. 
 */
class View_Helper_WorkflowNotifyMail extends Zend_View_Helper_Abstract {
    public function workflowNotifyMail() {
        return $this;
    }
    
    /**
     * render the HTML user list table
     * @param array $users
     * @return string
     */
    public function renderUserList(array $users) {
        $firstUser = reset($users);
        $hasState = !empty($firstUser) && array_key_exists('state', $firstUser);
        $t = $this->view->translate;
        /* @var $task editor_Models_Task */
        $result = array('<table cellpadding="4">');
        $th = '<th align="left">';
        $result[] = '<tr>';
        $result[] = $th.$t->_('Nachname').'</th>';
        $result[] = $th.$t->_('Vorname').'</th>';
        $result[] = $th.$t->_('Login').'</th>';
        $result[] = $th.$t->_('E-Mail Adresse').'</th>';
        if($hasState) {
            $result[] = $th.$t->_('Status').'</th>';
        }
        $result[] = '</tr>';
        
        foreach($users as $user) {
            $result[] = "\n".'<tr>';
            $result[] = '<td>'.$user['surName'].'</td>';
            $result[] = '<td>'.$user['firstName'].'</td>';
            $result[] = '<td>'.$user['login'].'</td>';
            $result[] = '<td>'.$user['email'].'</td>';
            if($hasState) {
                $result[] = '<td>'.$t->_($user['state']).'</td>';
            }
            $result[] = '</tr>';
        }
        $result[] = '</table>';
        return join('', $result);
    }
    
    /**
     * returns an array with translated language names used in the given task
     * The result is ready to be used in mail templates
     * 
     * @param editor_Models_Task $task
     * @return array
     */
    public function getTaskLanguages(editor_Models_Task $task) {
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */

        $params = [];
        
        try {
            $lang->load($task->getSourceLang());
            $params['sourceLanguageTranslated'] = $this->view->translate->_($lang->getLangName());
        }
        catch (Exception $e) {
            $params['sourceLanguageTranslated'] = 'unknown';
        }
        
        try {
            $lang->load($task->getTargetLang());
            $params['targetLanguageTranslated'] = $this->view->translate->_($lang->getLangName());
        }
        catch (Exception $e) {
            $params['targetLanguageTranslated'] = 'unknown';
        }

        $relais = $task->getRelaisLang();
        if(!empty($relais)) {
            try {
                $lang->load($task->getRelaisLang());
                $params['relaisLanguageTranslated'] = $this->view->translate->_($lang->getLangName());
            }
            catch (Exception $e) {
                $params['relaisLanguageTranslated'] = 'unknown';
            }
            $params['relaisLanguageFragment'] = $this->view->translate->_('<b>Relaissprache:</b> {relaisLanguageTranslated}<br />');
        }
        else {
            $params['relaisLanguageFragment'] = '';
        }
        
        return $params;
    }
    
    /**
     * returns a date in the locale of the receiver
     * 
     * @param string/integer $date
     * @return string
     */
    public function dateFormat($date) {
        if(empty($this->view->receiver->locale)) {
            $locale = $this->view->config->runtimeOptions->translation->fallbackLocale;
        }
        else {
            $locale = $this->view->receiver->locale;
        }
        $format = Zend_Locale_Format::getDateFormat($locale);
        $date = new Zend_Date($date);
        return $date->toString($format);
    }
}
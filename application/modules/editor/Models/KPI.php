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
 * KPI (Key Point Indicators) are handled in this class.
 */
class editor_Models_KPI {
    
    /**
     * Tasks the KPI are to be calculated for.
     * @var array
     */
    protected $tasks = [];
    
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    public function __construct() {
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }
    
    /**
     * Set the tasks the KPI are to be calculated for.
     * @param array $rows
     */
    public function setTasks(array $rows) {
        $this->tasks = $rows;
    }
    
    /**
     * Can KPI-statistics be calculated at all?
     * @return bool
     */
    protected function hasStatistics() : bool {
        // no tasks? no statistics!
        return count($this->tasks) > 0;
    }
    
    /**
     * Get the KPI-statistics.
     * @return array
     */
    public function getStatistics() {
        $statistics = [];
        $statistics['averageProcessingTime'] = $this->getAverageProcessingTime();
        $statistics['excelExportUsage'] = $this->getExcelExportUsage();
        return $statistics;
    }
    
    /**
     * Calculate and return the average processing time for the tasks.
     */
    protected function getAverageProcessingTime() {
        if (!$this->hasStatistics()) {
            return '';
        }
        return 'APT ' . date("h:i:sa"); // TODO :)
    }
    
    /**
     * Calculate and return the Excel-export-usage of the tasks
     * (= percent of the tasks exported at least once).
     * @return string
     */
    protected function getExcelExportUsage() {
        if (!$this->hasStatistics()) {
            return '';
        }
        $nrExported = 0;
        $allTaskGuids = array_column($this->tasks, 'taskGuid');
        $excelExport = ZfExtended_Factory::get('editor_Models_Task_ExcelExport');
        /* @var $excelExport editor_Models_Task_ExcelExport */
        foreach ($allTaskGuids as $taskGuid) {
            if ($excelExport->isExported($taskGuid)) {
                $nrExported++;
            }
        }
        $percentage = ($nrExported / count($allTaskGuids)) * 100;
        return sprintf($this->translate->_('%0.2f%% Excel-Export Nutzung'), $percentage);
    }
    
}

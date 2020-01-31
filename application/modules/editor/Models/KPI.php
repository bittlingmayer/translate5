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
        $statistics['averageProcessingTimeTranslator'] = 10;//TODO: collect all in one query
        $statistics['averageProcessingTimeSecondTranslator'] = 10;
        $statistics['excelExportUsage'] = $this->getExcelExportUsage();
        return $statistics;
    }
    
    /**
     * Calculate and return the average processing time for the tasks.
     * Current implementation:
     * - startDate: order date
     * - endDate: delivery date (real)
     * TODO: With TRANSLATE-1455, change these to:
     * - startDate: assigned
     * - endDate: review delivered
     * @return string '123 days' or '-' if statistics can't be calculated
     */
    protected function getAverageProcessingTime() {
        if (!$this->hasStatistics()) {
            return '-';
        }
        $average = '-';
        $allProcessingTimes = [];
        
        $taskGuids=array_column($this->tasks,'taskGuid');
        
        $assoc=ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $assoc editor_Models_TaskUserAssoc  */
        $assoc->loadByTaskGuidList($taskGuids);
        //TODO: use the assoc to do calculations: see the notes in TODOS-1455
        
        // If this is will ever be needed for showing the taskGrid, we should not
        // iterate through all filtered tasks, but change to pure SQL such as:
        //    SELECT ROUND(AVG(TIMESTAMPDIFF(DAY,orderDate, realDeliveryDate)),0)
        //    FROM LEK_task
        //    WHERE not realDeliveryDate is null and not orderDate is null;
        foreach ($this->tasks as $task) {
            if ($task['realDeliveryDate'] == null) {
                // Only tasks that already do have an end-date are to be included.
                continue;
            }
            // TODO: would it be better to retrieve these dates from the task-model?
            $startDate = new DateTime($task['orderdate']);
            $endDate = new DateTime($task['realDeliveryDate']);
            $processingTime = $endDate->diff($startDate);
            $allProcessingTimes[] = $processingTime->format('%a');
        }
        if (count($allProcessingTimes) > 0) {
            $average = array_sum($allProcessingTimes) / count($allProcessingTimes);
            $average = round($average, 0) . ' ' . $this->translate->_('Tage');
        }
        return $average;
    }
    
    /**
     * Calculate and return the Excel-export-usage of the tasks
     * (= percent of the tasks exported at least once).
     * @return string Percentage (0-100%) or '-' if statistics can't be calculated
     */
    protected function getExcelExportUsage() {
        if (!$this->hasStatistics()) {
            return '-';
        }
        $nrExported = 0;
        
        // If this is will ever be needed for showing the taskGrid, we should not
        // iterate through all filtered tasks, but change to pure SQL.
        $allTaskGuids = array_column($this->tasks, 'taskGuid');
        $excelExport = ZfExtended_Factory::get('editor_Models_Task_ExcelExport');
        /* @var $excelExport editor_Models_Task_ExcelExport */
        foreach ($allTaskGuids as $taskGuid) {
            if ($excelExport->isExported($taskGuid)) {
                $nrExported++;
            }
        }
        
        $percentage = ($nrExported / count($allTaskGuids)) * 100; // after $this->hasStatistics(), count($allTaskGuids) will always be > 0
        return round($percentage,2) . '%';
    }
    
}

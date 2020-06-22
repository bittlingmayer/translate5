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
 * This model collects some internal handling of the segmentrange-column in the
 * taskUserAssoc-table (= for all DB-related methods see editor_Models_TaskUserAssoc).
 */
class editor_Models_TaskUserAssoc_Segmentrange {
    
    /**
     * Return an array with the numbers of the segments that are set in the given 
     * array of tua-rows.
     * @param array $tuaRows
     * @return array
     */
    public static function getSegmentNumbersFromRows(array $tuaRows) : array {
        // Example for:
        // - translator {94ff4a53-dae0-4793-beae-1f09968c3c93}: "1-3;5"
        // - translator {c77edcf5-3c55-4c29-a73d-da80d4dcfb36}: "7-8"
        // $segmentNumbers = [1,2,3,5,7,8]
        $segmentNumbers = [];
        foreach ($tuaRows as $row) {
            $segmentsNumbersInRange = self::getNumbers($row['segmentrange']);
            $segmentNumbers  = array_merge($segmentNumbers, $segmentsNumbersInRange);
        }
        return $segmentNumbers;
    }
    
    /**
     * Return an array with the numbers of the segments that are set in the given
     * single range.
     * @param string $segmentRanges
     * @return array
     */
    private static function getNumbers(string $segmentRanges) : array {
        // Example for "1-3;5, 8-9 ":
        // $segmentNumbers = [1,2,3,5,8,9]
        $segmentNumbers = [];
        $segmentRanges = preg_replace('/;/', ',', $segmentRanges);
        $segmentRanges = trim(preg_replace('/\s+/','', $segmentRanges));
        $allSegmentGroups = explode(",", $segmentRanges);
        foreach ($allSegmentGroups as $segmentGroup) {
            $segmentGroupLimits = explode("-", $segmentGroup);
            for ($i = reset($segmentGroupLimits); $i <= end($segmentGroupLimits); $i++) {
                $segmentNumbers[] = (int)$i;
            }
        }
        return $segmentNumbers;
    }
    
    /**
     * Return ranges from numbers.
     * @param string $segmentNumbers
     * @return string
     */
    public static function getRanges(array $segmentNumbers) : string {
        // Example for [1,2,3,5,8,9]:
        // $segmentRanges = "1-3,5,8-9"
        // from https://codereview.stackexchange.com/a/103767
        $segmentNumbers = array_unique( $segmentNumbers);
        sort($segmentNumbers);
        $allSegmentGroups = array();
        for( $i = 0; $i < count($segmentNumbers); $i++ ) {
            if( $i > 0 && ($segmentNumbers[$i-1] == $segmentNumbers[$i] - 1)) {
                array_push($allSegmentGroups[count($allSegmentGroups)-1], $segmentNumbers[$i]);
            } else {
                array_push($allSegmentGroups, array( $segmentNumbers[$i]));
            }
        }
        $segmentRanges = array();
        foreach( $allSegmentGroups as $segmentGroup) {
            if( count($segmentGroup) == 1 ) {
                $segmentRanges[] = $segmentGroup[0];
            } else {
                $segmentRanges[] = $segmentGroup[0] . '-' . $segmentGroup[count($segmentGroup)-1];
            }
        }
        return implode( ',', $segmentRanges);
    }
}
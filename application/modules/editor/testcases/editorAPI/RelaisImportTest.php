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
 * Tests if Relais Files are imported correctly, inclusive our alignment checks 
 */
class RelaisImportTest extends \ZfExtended_Test_ApiTestcase {
    
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'relaisLang' => 'it',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $appState = $api->requestJson('editor/index/applicationstate');
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig may not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology may not be activated for this test case!');
        
        $api->addImportFile($api->getFile('RelaisImportTest.zip'));
        $api->import($task);
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     * Test if relais columns are containing the expected content
     */
    public function testRelaisContent() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $segments = array_map(function($segment){
            //TODO remove array cast with PHP7
            return (array) $segment;
        }, $segments);
        $relais = array_column($segments, 'relais', 'segmentNrInTask');
        
        $expected = [
            '1' => 'Questo e un casa roso.',
            '2' => 'RELAIS - Here the alignment is OK.',
            '3' => '',
            '4' => 'RELAIS – Here the alignment is OK again.',
            '5' => '',
            '6' => 'RELAIS – Here the alignment is OK again 2.',
            '7' => '',
            '8' => 'RELAIS – Here the alignment is OK again 3.',
            
            '9' => 'RELAIS - Segment with ignored and different tags',
            '10' => 'RELAIS – Segment with ignored and equal tags',
            '11' => '',
            '12' => 'RELAIS – Segment with equal entity encoding',
            '13' => 'RELAIS – Segment with equal entity encoding',
                
            '14' => 'This is a red house',
            '15' => 'Here the alignment is OK.',
            '16' => '',
            '17' => 'Here the alignment is OK again.',
            '18' => 'Here the alignment is OK again 2.',
            '19' => 'Here the alignment is OK again 3.',
                
            '19' => 'Here the alignment is OK again 3.',
            '20' => 'Diese Datei ist Teil der php-online-Dokumentation. Ihre Übersetzung ist durch eine Vorübersetzung entstanden, die auf einem sehr schnell durchgeführten winalign-Project basiert und in keiner Art und Weise dem State of the Art eines Übersetzungsprojekts entspricht. Sein einziger Zweck ist die Erzeugung von Demo-Daten für translate5. ',
            '21' => 'Apache 2.0 auf Unixsystemen - Manual',
            '22' => 'PHP Manual',
            '23' => 'Installation und Konfiguration',
            '24' => 'Installation auf Unix-Systemen',
            '25' => 'RELAIS - Apache 1.3.x auf Unix-Systemen',
            '26' => '',
        ];
        
        $this->assertEquals($expected, $relais, 'Relais columns not filled as expected!');
    }
    
    public static function tearDownAfterClass() {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
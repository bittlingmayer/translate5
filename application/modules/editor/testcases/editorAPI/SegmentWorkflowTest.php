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

/**
 * SegmentWorkflowTest imports a test task, adds workflow users, edits segments and finishes then the task.
 * The produced changes.xml and the workflow steps of the segments are checked. 
 */
class SegmentWorkflowTest extends \ZfExtended_Test_ApiTestcase {
    /**
     * Setting up the test task by fresh import, adds the lector and translator users
     */
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        $appState = self::assertTermTagger();
        self::assertNotContains('editor_Plugins_ManualStatusCheck_Bootstrap', $appState->pluginsLoaded, 'Plugin ManualStatusCheck may not be activated for this test case!');
        $api->addImportFile('editorAPI/SegmentWorkflowTest/simple-en-de.zip');
        $api->import($task);
        
        //FIXME improve this test by using two lector users to test after all finish with multiple users
        
        $api->addUser('testlector');
        $api->reloadTask();
        $api->addUser('testtranslator', 'waiting', 'translator');
    }
    
    /**
     * tests if config is correct for testing changes.xliff 
     */
    public function testSaveXmlToFile() {
        $config = $this->api()->requestJson('editor/config', 'GET', array(
            'filter' => '[{"type":"string","value":"runtimeOptions.editor.notification.saveXmlToFile","field":"name"}]',
        ));
        $this->assertCount(1, $config);
        $this->assertEquals(1, $config[0]->value);
    }
    
    /**
     * edits some segments as lector, finish then the task
     * - checks for correct changes.xliff
     * - checks if task is open for translator and finished for lector
     * - modifies also segments with special characters to test encoding in changes.xml
     */
    public function testWorkflowFinishAsLector() {
        //check that testtranslator is waiting
        $this->api()->login('testtranslator');
        $this->assertEquals('waiting', $this->api()->reloadTask()->userState);
        
        //check that testlector is open
        $this->api()->login('testlector');
        $this->assertEquals('open', $this->api()->reloadTask()->userState);
        
        $task = $this->api()->getTask();
        //open task for whole testcase
        $this->api()->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
        
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');

        //edit two segments
        $segToTest = $segments[2];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'PHP Handbuch', $segToTest->id);
        $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        $segToTest = $segments[6];
        $nbsp = json_decode('"\u00a0"');
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'Apache 2.x'.$nbsp.' auf Unix-Systemen', $segToTest->id);
        $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //edit a segment with special characters
        $segToTest = $segments[4];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', "Installation auf Unix-Systemen &amp; Umlaut Test äöü &lt; &lt;ichbinkeintag&gt; - bearbeitet durch den Testcode", $segToTest->id);
        $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');

        //bulk check of all workflowStepNr fields
        $workflowStepNr = array_map(function($item){
            return $item->workflowStepNr;
        }, $segments);
        $this->assertEquals(array('0','0','1','0','1','0','1'), $workflowStepNr);
        
        //bulk check of all autoStateId fields
        $workflowStep = array_map(function($item){
            return $item->workflowStep;
        }, $segments);
        $this->assertEquals(array('','','lectoring','','lectoring','','lectoring'), $workflowStep);
        
        //finishing the task
        $res = $this->api()->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'finished', 'id' => $task->id));
        $this->assertEquals('finished', $this->api()->reloadTask()->userState);
        
        //get the changes file
        $path = $this->api()->getTaskDataDirectory();
        $foundChangeFiles = glob($path.'changes*.xliff');
        $this->assertNotEmpty($foundChangeFiles, 'No changes*.xliff file was written for taskGuid: '.$task->taskGuid);
        $foundChangeFile = end($foundChangeFiles);
        $this->assertFileExists($foundChangeFile);
        
        //no direct file assert equals possible here, since our diff format contains random sdl:revids
        //this revids has to be replaced before assertEqual
        $approvalFileContent = $this->api()->replaceChangesXmlContent($this->api()->getFileContent('testWorkflowFinishAsLector-assert-equal.xliff'));
        $toCheck = $this->api()->replaceChangesXmlContent(file_get_contents($foundChangeFile));
        $this->assertXmlStringEqualsXmlString($approvalFileContent, $toCheck);
        
        //check that task is finished for lector now
        $this->assertEquals('finished', $this->api()->reloadTask()->userState);
        
        //check that task is open for translator now
        $this->api()->login('testtranslator');
        $this->assertEquals('open', $this->api()->reloadTask()->userState);
    }
    
    public static function tearDownAfterClass() {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
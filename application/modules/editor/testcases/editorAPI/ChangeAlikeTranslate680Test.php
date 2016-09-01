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
 * ChangeAlikeTranslate683Test imports a simple task, checks and checks the ChangeAlike Behaviour in combination 
 * with Source Editing and trans[Not]Found mark up.
 * See therefore: 
 * TRANSLATE-683 source original will be overwritten even if Source is not editable, 
 *   and contents are no repetition of each other
 * TRANSLATE-549 fixing Source Editing and Change Alike behaviour
 * TRANSLATE-543 fixing red terms go blue on using change alikes, causing 683
 * 
 * This test also covers:
 * TRANSLATE-686 by testing the autostates
 * 
 * So in conclusion the desired behaviuor is: 
 * Without Source Editing: 
 *   - Segments to be changed with the repetition editor are getting the edited target and the autostates of the master segment
 *   - In Source Original the transFound states are recalculated
 *   
 * With Source Editing: 
 *   - Segments to be changed with the repetition editor are getting the edited target and the edited source and the autostates of the master segment
 *   - In Source Original the transFound states are recalculated
 */
class ChangeAlikeTranslate680Test extends \ZfExtended_Test_ApiTestcase {
    protected static $useSourceEditing = false;
    
    /**
     * the strings to be compared on testing change alike source matching 
     * [0] = source
     * [1] = target
     * @var array
     */
    static protected $dummyData = array(
        //1. master segment, we assume that tag positioning was the same before editing
        //what is edited does not matter, since md5 hash is created before editing at all
        // no matching here, since this segment won't be in alikes result set
        ['This <b><br>is a</b> red house.','Dies <b>ist<br> ein</b> rotes Haus.'],
        
        //2.
        //full source match
        //target text different
        ['This <b><br>is a</b> red house.','Dies <b>ist</b> ein<br> grünes Haus.'],
            
        //3.
        //no full source match, since target tag count differs
        //target text different
        ['This <b><br>is a</b> red house.','Dies <b>ist</b> ein grünes Haus.'],
            
        //4.
        //full source match
        //target text different
        ['This <b><br>is a</b> red house.','Dies <b>ist<br> ein</b> grünes Haus.'],
            
        //5.
        //no source match
        //full target match
        ['This <b><br>is a</b> green house.','Dies <b>ist<br> ein</b> rotes Haus.'],
            
        //6.
        //no source match
        //full target match, with different source tags
        ['This is a<br> green house.','Dies <b>ist<br> ein</b> rotes Haus.'],
         
        //7.
        //source repetition since source tags are at the same place and target tag count equals
        //no target repetition since tags are at a different place and text is different
        ['This <br><b>is a</b> red house.','Dies <br><b>war ein</b> grünes Haus.'],
            
        //8.
        //source repetition, see above
        //no target repetition since tags are at a different place
        ['This <br><b>is a</b> red house.','Dies <br><b>war ein</b> rotes Haus.'],
            
        //9.
        //no source repetition since target tag count differs, although source tags are at the same place
        //no target repetition since tags and text is different
        ['This <br><b>is a</b> red house.', 'Dies <br><b>war ein grünes Haus.'],
            
        //10.
        //no source repetition, text differs
        //target repetition since tag structure and text is the same
        ['This <b><br>is a</b> green house.','Dies <br>ist<b> ein</b> rotes Haus.'],
            
        //11.
        //no source repetition, text differs, tags missing
        //target repetition since tag structure and text is the same, regardless of the different source tag structure
        ['This <br><br>is a green house.','Dies <br>ist<b> ein</b> rotes Haus.'],
            
        //12 no match at all, just to test tag less segments
        ['This is a green house.','Dies ist ein rotes Haus.'],
    );
    
    /**
     * This are the expected segmentNrInTask for targetMatches with Source Editing
     * @var array
     */
    static protected $sourceMatch = [2, 4, 7, 8];
    
    /**
     * This are the expected values for targetMatches with Source Editing
     * key segmentNrInTask
     * value isMatch
     * @var array
     */
    static protected $targetMatch = [5, 6, 10, 11];
    
    /**
     * This are the expected values for targetMatches with Source Editing
     * key segmentNrInTask
     * value isMatch
     * @var array
     */
    static protected $targetMatchSE = [5,10];
    
    /*
     Idee war die Anzahl der Tags des Targets auch in den Hash des sources mit aufzunehmen,
        so dass keine WDHs gefunden werden wenn die Tag Anzahl unterschiedlich ist.
        
        Logisch bedeutet das für Source Wiederholungen: 
            Eine Wiederholung ist eine Wiederholung wenn, 
                - der Source Text gleich ist
                - wenn die Source Tags an der gleichen Stelle stehen
                - Was es für Source Tags sind ist egal
                    → md5 hash auf segment mit neutralen tag placeholdern
                - Wenn die Anzahl der Target Tags ebenfalls gleich sind
                    → tag count des targets in den md5 hash des source mit rein
                    → Bei Projekten mit alternativen Targets den count weglassen, so dass md5 Spalte befüllt, auch wenn alikes nicht nutzbar
        
        Wie sieht das mit Target Wiederholungen aus?
            Eine Wiederholung ist eine Wiederholung wenn,
                - der Target Text gleich ist
                - wenn die Target Tags an der gleichen Stelle stehen (impliziert gleiche Anzahl im Target)
                - Die Anzahl / Struktur der Source Tags interessiert nicht 
                - Was es für Tags sind ist egal
                    → md5 hash auf segment mit neutralen tag placeholdern
                    → Tags im Source interessieren hier nicht, da Inhalt komplett unterschiedlich sein kann und Source nicht modifiziert wird!
                
        Wie sieht es mit Source Editing aus: 
            - Theoretisch dürften mit aktiviertem Source Editing ebenfalls keine Wiederholungen gefunden werden,
                in denen die Tag Anzahl im Source unterschiedlich ist, da die Source ebenfalls modifiziert wird.  

                
                
        Für die relais md5 Spalte ist es prinzipiell ebenfalls Egal, da auch hier keine Wiederholungen genutzt werden könne, dennoch nehmen wir der Konsistenz wegen den gleichen Algorithmus als für die Source Splate
                
        Fragen: 
        - Wieso hatten wir definiert, dass die Tags einer Wiederholung an der gleichen Stelle stehen müssen?
            → Immerhin ist ja der Tag Inhalt unerheblich, ist dann die Position soviel wichtiger?
            Antwort: Position spielt explizit eine Rolle, 

     */
    
    
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'enableSourceEditing' => static::$useSourceEditing,
            'lockLocked' => 1,
        );
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $appState = $api->requestJson('editor/index/applicationstate');
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig may not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology may not be activated for this test case!');
        
        $api->addImportTaskTemplate('editorAPI/TaskTemplateTestCaseCSVTagProtect.xml');
        
        $api->addImportArray(self::$dummyData);
        $api->import($task);
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     * Test using changealikes by source match
     */
    public function testAlikeCalculation() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $this->assertCount(count(self::$dummyData), $segments);
        
        //test source editing 
        $isSE = $this->api()->getTask()->enableSourceEditing;
        
        //test editing a prefilled segment
        $segToTest = $segments[0];
        //$this->assertEquals($this->dummyData[0]['sourceBeforeEdit'], $segToTest->source);
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        $alikes = array_map(function($item){return (array) $item;}, $alikes);
        
        $targetMatch = array_keys(array_filter(array_column($alikes, 'targetMatch', 'segmentNrInTask')));
        $sourceMatch = array_keys(array_filter(array_column($alikes, 'sourceMatch', 'segmentNrInTask')));
        
        $this->assertEquals(static::$sourceMatch, $sourceMatch, 'The Source Matches are not as expected');
        $this->assertEquals(static::$targetMatch, $targetMatch, 'The Target Matches are not as expected');
    }
    
    /**
     * @depends testAlikeCalculation
     */
    public function testEditing() {
        $isSE = static::$useSourceEditing;
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $segToTest = $segments[0];
        $edit = str_replace('Haus', 'Haus - edited', $segToTest->targetEdit);
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $edit, $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
            //edit source also, currently our test helper cant make this in one API call
        if($isSE) {
            $edit = str_replace('house', 'house - edited', $segToTest->sourceEdit);
            $segmentData = $this->api()->prepareSegmentPut('sourceEdit', $edit, $segToTest->id);
            $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        }
        
        //fetch alikes and assert correct segments found by segmentNrInTask
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        
        //save alikes
        $alikeIds = [];
        $alikePutData = array('duration' => 777 ); //faked duration value
        foreach($alikes as $k => $v){
            $alikeIds[] = $v->id;
            $alikePutData['alikes['.$k.']'] = $v->id;
        }
        //Alike Data is sent as plain HTTP request parameters not as JSON in data parameter!
        $resp = $this->api()->request('editor/alikesegment/'.$segToTest->id, 'PUT', $alikePutData);
        
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        foreach($segments as $segment) {
            $nr = $segment->segmentNrInTask;
            if(!in_array($segment->id, $alikeIds)) {
                continue;
            }
            preg_match_all('#<div.+?</div>#', $segment->target, $originalTags);
            preg_match_all('#<div.+?</div>#', $segment->targetEdit, $editedTags);
            $this->assertEquals($originalTags, $editedTags, 'Target segment (Nr. '.$nr.') tags were changed, that must not be!');
            $this->assertStringEndsWith('Haus - edited.', $segment->targetEdit, 'Target of segment Nr. '.$nr.' was not edited');
            
            if(!$isSE) {
                continue;
            }
            preg_match_all('#<div.+?</div>#', $segment->source, $originalTags);
            preg_match_all('#<div.+?</div>#', $segment->sourceEdit, $editedTags);
            $this->assertEquals($originalTags, $editedTags, 'Source segment (Nr. '.$nr.') tags were changed, that must not be!');
            $this->assertStringEndsWith('house - edited.', $segment->sourceEdit, 'Source of segment Nr. '.$nr.' was not edited');
        }
    }
    
    public static function tearDownAfterClass() {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
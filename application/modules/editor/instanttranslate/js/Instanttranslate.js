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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/* --------------- selecting languages and MT-engines ----------------------- */
$('#mtEngineSelector input[name="mtEngines"]:radio').change(function() {
    var mtEngine = machineTranslationEngines[this.id];
    $("#sourceLocale").val(mtEngine.source).selectmenu("refresh");
    $("#targetLocale").val(mtEngine.target).selectmenu("refresh");
});
function setMtEnginesAccordingToLanguages() {
    var sourceLocale = $("#sourceLocale").val(),
        targetLocale = $("#targetLocale").val(),
        mtIdsFound = getMtEnginesAccordingToLanguages(sourceLocale,targetLocale),
        mtEngine;
    $("#mtEngineSelector input:radio[name='mtEngines']").prop( "disabled", true );
    $("#mtEngineSelector input:radio[name='mtEngines']").prop("checked",false);
    for (var i in mtIdsFound) {
        var mtId = mtIdsFound[i];
        console.log(mtId);
        $("#"+mtId).button("enable").button("refresh");
      }
    $("#mtEngineSelector input:radio[name='mtEngines']").checkboxradio("refresh");
    if (mtIdsFound.length === 0) {
        $('#errorNoMtFound').show();
        $('#messageMultipleMtFound').hide();
        return;
    }
    if (mtIdsFound.length === 1) {
        mtEngine = machineTranslationEngines[mtIdsFound[0]];
        $("#"+mtIdsFound[0]).prop("checked",true).button("refresh");
        $("#mtEngineSelector input:radio[name='mtEngines']").prop("checked",false);
        $("#sourceLocale").val(mtEngine.source).selectmenu("refresh");
        $("#targetLocale").val(mtEngine.target).selectmenu("refresh");
        $('#errorNoMtFound').hide();
        $('#messageMultipleMtFound').hide();
        return;
    }
    if (mtIdsFound.length > 1) {
        $('#errorNoMtFound').hide();
        $('#messageMultipleMtFound').show();
        return;
    }
}
function getMtEnginesAccordingToLanguages(sourceLocale,targetLocale) {
    var mtIdsFound = [],
        mtId,
        mtEngineToCheck,
        langIsOK = function(langMT,langSet){
            if (langMT === langSet) {
                return true;
            }
            if (langSet === '-') {
                return true;
            }
            return false;
        };
    for (mtId in machineTranslationEngines) {
        if (machineTranslationEngines.hasOwnProperty(mtId)) {
            mtEngineToCheck = machineTranslationEngines[mtId];
            if (langIsOK(mtEngineToCheck.source,sourceLocale) && langIsOK(mtEngineToCheck.target,targetLocale)) {
                mtIdsFound.push(mtId); 
            }
        }
    }
    return mtIdsFound;
}

/* --------------- toggle instant translation ------------------------------- */
$('.instant-translation-toggle').click(function(){
    $('.instant-translation-toggle').toggle();
});

/* --------------- clear source --------------------------------------------- */
$(".clearable").each(function() {
    // idea fom https://stackoverflow.com/a/6258628
    var elInp = $(this).find("#sourceText"),
        elCle = $(this).find(".clearable-clear");
    elInp.on("input", function(){
        elCle.toggle(!!this.value);
    });
    elCle.on("touchstart click", function(e) {
        e.preventDefault();
        elInp.val("").trigger("input");
    });
});

/* --------------- count characters ----------------------------------------- */
$('#sourceText').on("input", function(){
    $('#countedCharacters').html($(this).val().length);
});

/* --------------- copy translation ----------------------------------------- */
$(".copyable").each(function() {
    var elCopy = $(this).find(".copyable-copy");
    elCopy.on("touchstart click", function(e) {
        var content = $(this).closest('.copyable').find('.translation-result').text();
        alert("TODO: copy '" + content + "'"); // TODO
    });
});
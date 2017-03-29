
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**
 * @class Editor.view.segments.GridViewModel
 * @extends Ext.app.ViewModel
 */
Ext.define('Editor.view.ViewPortEditorViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.viewportEditor',
    data: {
        taskIsReadonly: false,   // indicates if the task is readonly (can not be changed by the user in the editor)
        editorIsReadonly: false, // indicates if the task is readonly, triggered by the user and changeable by the user
        editorViewmode: null
    },
    formulas: {
        viewmodeIsErgonomic: function(get){
            return get('editorViewmode') == Editor.controller.ViewModes.MODE_ERGONOMIC;
        },
        viewmodeIsEdit: function(get){
            return get('editorViewmode') == Editor.controller.ViewModes.MODE_EDIT;
        }
    }
});
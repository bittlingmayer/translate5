
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.qmsubsegments.AddFlagFieldset
 * @extends Ext.form.FieldSet
 */
Ext.define('Editor.view.qmsubsegments.AddFlagFieldset', {
	extend : 'Ext.form.FieldSet',
	alias : 'widget.qmSubsegmentsFlagFieldset',
	title : "QM Subsegmente",
	collapsible: true,
	strings: {
		severityLabel: '##UT##Gewichtung',
		commentLabel: '##UT##Kommentar',
		qmAddBtn: '##UT##QM Subsegment hinzufügen'
	},
	initComponent : function() {
		var me = this;
		Ext.applyIf(me, {
			items : [{
				xtype : 'button',
				text : me.strings.qmAddBtn,
				margin: '0 0 6 0',
				menu : {
					bodyCls: 'qmflag-menu',
					listeners: {
					    beforerender: function(component) {
					        component.add(me.controller.menuConfig);
					    },
	                    afterrender: function(component) {
	                    	if(component.keyNav) {
	                    		component.keyNav.disable();
	                    	}
	                    }
                	}
				}
			},{
				xtype: 'combobox',
				anchor: '100%',
				name: 'qmsubseverity',
				queryMode: 'local',
				autoSelect: true,
				fieldLabel: me.strings.severityLabel,
				forceSelection: true,
				editable: false,
			    displayField: 'text',
			    valueField: 'id'
			},{
				xtype: 'textfield',
				anchor: '100%',
				fieldLabel: me.strings.commentLabel,
				name: 'qmsubcomment'
			}]
		});
		me.callParent(arguments);
	}
});
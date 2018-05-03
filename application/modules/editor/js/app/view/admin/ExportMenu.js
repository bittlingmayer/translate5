
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
 * contains the Admin Task Export Menu
 * @class Editor.view.admin.ExportMenu
 */
Ext.define('Editor.view.admin.ExportMenu', {
  extend: 'Ext.menu.Menu',
  itemId: 'exportMenu',
  messages: {
      exportDef: '#UT#exportieren (Orginalformat)',
      export2Def: '#UT#exportieren (XLIFF 2.2)',
      exportDiff: '#UT#exportieren mit Änderungshistorie',
      exportQmField: '#UT#Export QM-Statistik (XML) für Feld: {0}'
  },
  alias: 'widget.adminExportMenu',
  makePath: function(path, field) {
      var task = this.initialConfig.task;
      return Editor.data.restpath+Ext.String.format(path, task.get('id'), task.get('taskGuid'), field);
  },
  initComponent: function() {
    var me = this,
        fields = this.initialConfig.fields;
    
    me.items = [{
        itemId: 'exportItem',
        hrefTarget: '_blank',
        href: me.makePath('task/export/id/{0}'),
        text: me.messages.exportDef
    },{
        itemId: 'exportItemXliff2',
        hrefTarget: '_blank',
        href: me.makePath('task/export/id/{0}?format=xliff2'),
        text: me.messages.export2Def
    },{
        itemId: 'exportDiffItem',
        hrefTarget: '_blank',
        href: me.makePath('task/export/id/{0}/diff/1'),
        text : me.messages.exportDiff
    }];
    
    if(fields === false) {
        me.callParent(arguments);
        return;
    }
    
    fields.each(function(field){
        if(!field.get('editable')) {
            return;
        }
        me.items.push({
            hrefTarget: '_blank',
            href: me.makePath('qmstatistics/index/taskGuid/{1}/?type={2}', field.get('name')),
            text : Ext.String.format(me.messages.exportQmField, field.get('label'))
        });
    });
    me.callParent(arguments);
  }
});
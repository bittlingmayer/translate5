
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

Ext.define('Editor.LanguageResources.view.AddTmWindow', {
    extend: 'Ext.window.Window',
    requires: [
        'Ext.ux.colorpick.Button',
        'Ext.ux.colorpick.Field'
    ],
    alias: 'widget.addTmWindow',
    itemId: 'addTmWindow',
    strings: {
        add: '#UT#Matchressource hinzufügen',
        resource: '#UT#Ressource',
        name: '#UT#Name',
        source: '#UT#Quellsprache',
        target: '#UT#Zielsprache',
        file: '#UT#TM/TMX-Datei (optional)',
        importTmxType: '#UT#Bitte verwenden Sie eine TM oder TMX Datei!',
        color: '#UT#Farbe',
        colorTooltip: '#UT#Farbe dieser Matchressource',
        save: '#UT#Speichern',
        cancel: '#UT#Abbrechen'
    },
    height : 360,
    width : 500,
    modal : true,
    layout:'fit',
    initConfig : function(instanceConfig) {
        var me = this,
        langCombo = {
                xtype: 'combo',
                typeAhead: true,
                displayField: 'label',
                forceSelection: true,
                queryMode: 'local',
                valueField: 'id'
            },
            config = {},
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
        config = {
            title: me.strings.add,
            items : [{
                xtype: 'form',
                padding: 5,
                ui: 'default-frame',
                defaults: defaults,
                items: [{
                    xtype: 'combo',
                    name:'resourceId',
                    allowBlank: false,
                    typeAhead: true,
                    forceSelection: true,
                    queryMode: 'local',
                    valueField: 'id',
                    displayField: 'name',
                    store:'Editor.LanguageResources.store.Resources',
                    fieldLabel: me.strings.resource
                },{
                    xtype: 'textfield',
                    name: 'name',
                    maxLength: 255,
                    allowBlank: false,
                    toolTip:'Name',
                    fieldLabel: me.strings.name
                },Ext.applyIf({
                    name: 'sourceLang',
                    allowBlank: false,
                    toolTip: me.strings.source,
                    //each combo needs its own store instance, see EXT6UPD-8
                    store: Ext.create(Editor.store.admin.Languages),
                    fieldLabel: me.strings.source
                }, langCombo),Ext.applyIf({
                    name: 'targetLang',
                    allowBlank: false,
                    toolTip: me.strings.target,
                    //each combo needs its own store instance, see EXT6UPD-8
                    store:Ext.create(Editor.store.admin.Languages),
                    fieldLabel: me.strings.target
                }, langCombo),{
                    xtype: 'hiddenfield',
                    name: 'serviceType',
                    dataIndex: 'serviceType',
                    maxLength: 255,
                    allowBlank: false
                },{
                    xtype: 'hiddenfield',
                    name: 'serviceName',
                    dataIndex: 'serviceName',
                    maxLength: 255,
                    allowBlank: false
                },{
                    xtype: 'colorfield',
                    fieldLabel: me.strings.color,
                    toolTip: me.strings.colorTooltip, 
                    labelWidth: 160,
                    anchor: '100%',
                    name: 'color'
                },{
                    xtype: 'filefield',
                    name: 'tmUpload',
                    allowBlank: true,
                    toolTip: me.strings.file,
                    regex: /\.(tm|tmx)$/i,
                    regexText: me.strings.importTmxType,
                    disabled:true,
                    fieldLabel: me.strings.file
                }]
            }],
            dockedItems : [{
                xtype : 'toolbar',
                dock : 'bottom',
                ui: 'footer',
                layout: {
                    type: 'hbox',
                    pack: 'start'
                },
                items : [{
                    xtype: 'tbfill'
                },{
                    xtype: 'button',
                    iconCls:'ico-save',
                    itemId: 'save-tm-btn',
                    text: me.strings.save
                }, {
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'cancel-tm-btn',
                    text : me.strings.cancel
                }]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
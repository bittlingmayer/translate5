
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

Ext.define('Editor.view.admin.task.TaskAttributes', {
    extend: 'Ext.form.Panel',
    //requires: ['Editor.view.admin.task.TaskAttributesViewController'],
    requires: [
        'Editor.view.admin.task.TaskAttributesViewController',
    ],
    alias: 'widget.taskattributes',
    strings: {
        taskName:'#UT#Aufgabenname',
        customerName:'#UT#Kunde',
        deliveryDate:'#UT#Lieferdatum (soll)',
        realDeliveryDate:'#UT#Lieferdatum (ist)',
        orderDate:'#UT#Bestelldatum',
        pmGuid:'#UT#Projektmanager',
        btnSave: '#UT#Speichern',
        successUpdate:'#UT#Die Aufgabe wurde erfolgreich aktualisiert',
        btnCancel:'#UT#Abbrechen',
        btnReload: '#UT#Aktualisieren',
        loadingMask:'#UT#Aktualisieren',
        fullMatchLabel: '#UT#100% Matches sind editierbar',
        editTrue:'#UT#Ja',
        editFalse:'#UT#Nein',
        usageModeTitle: "#UT#Mehrere Benutzer",
        usageModeInfo: "#UT#Wenn mehrere Benutzer demselben Workflow-Schritt zugewiesen werden",
        usageModeDisabled: "#UT#Diese Option kann nur verändert werden, wenn kein Benutzer der Aufgabe zugewiesen ist.",
        usageModeCoop: "#UT#Mehrere Benutzer bearbeiten abwechselnd dieselbe Aufgabe",
        usageModeCompetitive: "#UT#Konkurrierende Benutzer: Der erste Benutzer, der einen Job annimmt, führt einen Job aus. Allen anderen wird automatisch die Aufgabe entzogen.",
        usageModeSimultaneous: "#UT#Mehrere Benutzer bearbeiten gleichzeitig dieselbe Aufgabe"
    },
    itemId:'taskAttributesPanel',
    controller:'taskattributesviewcontroller',
    title: '#UT#Eigenschaften',
    initConfig: function(instanceConfig) {
        var me = this,
            config,
            allowedItems=me.getAllowedFields();
    
        if(!allowedItems){
            return;
        }
        config = {
            title: me.title, //see EXT6UPD-9
            bodyPadding: 10,
            frame: true,
            defaults: {
                labelWidth: 200,
                anchor: '60%'
            },
            items:allowedItems,
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    items: [
                        {
                            xtype: 'tbfill'
                        },
                        {
                            xtype: 'button',
                            itemId: 'cancelTaskAttributes',
                            iconCls : 'ico-cancel',
                            listeners:{
                                click:'onCancelTaskAttributesClick'
                            },
                            text: me.strings.btnCancel
                        },
                        {
                            xtype: 'button',
                            itemId: 'reloadTaskAttributes',
                            iconCls: 'ico-refresh',
                            listeners:{
                                click:'onReloadTaskAttributesClick'
                            },
                            text: me.strings.btnReload
                        },{
                            xtype: 'button',
                            itemId: 'saveTaskAttributes',
                            iconCls : 'ico-save',
                            listeners:{
                                click:'onSaveTaskAttributesClick'
                            },
                            text: me.strings.btnSave
                        },
                    ]
                }
            ]
        };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Return the allowed fields in the task attributes tab. If the field is not allowed for the current logged user,
     * the component type will be displayfield(the value will be noneditable)
     * @returns Array
     */
    getAllowedFields:function(){
        var me=this,
            auth = Editor.app.authenticatedUser,
            items=[], dateRenderer = function(value, displayField) {
                return Ext.Date.format(value, Ext.Date.defaultFormat);
            };

        items.push({
            xtype: 'displayfield',
            fieldLabel: me.strings.customerName,
            name: 'customerId',
            renderer: me.customerRenderer
        });
        
        //TODO: the displayfield value is not selectable
        //EXTJSBUG: https://www.sencha.com/forum/showthread.php?330319-Value-not-selectable-in-display-and-text-fields-under-6-2-0
        //is the user allowed to edit the task name
        items.push({
            xtype: auth.isAllowed('editorEditTaskTaskName') ? 'textfield' : 'displayfield',
            fieldLabel: me.strings.taskName,
            name:'taskName',
            itemId:'taskName'
        });

        items.push(me.getPmFieldConfig());

        items.push(me.applyIfNotAllowed({
            xtype: 'datefield',
            fieldLabel: me.strings.deliveryDate,
            name:'targetDeliveryDate',
            itemId:'targetDeliveryDate'
        },'editorEditTaskDeliveryDate',{
            xtype: 'displayfield',
            renderer: dateRenderer
        }));
        
        items.push(me.applyIfNotAllowed({
            xtype: 'datefield',
            fieldLabel: me.strings.realDeliveryDate,
            name:'realDeliveryDate',
            itemId:'realDeliveryDate'
        },'editorEditTaskRealDeliveryDate',{
            xtype: 'displayfield',
            renderer: dateRenderer
        }));
        
        items.push(me.applyIfNotAllowed({
            xtype: 'datefield',
            fieldLabel: me.strings.orderDate,
            name:'orderdate',
            itemId:'orderdate'
        },'editorEditTaskOrderDate',{
            xtype: 'displayfield',
            renderer: dateRenderer
        }));
        
        
        //is the user allowed to edit the Edit100PercentMatch
        items.push(me.applyIfNotAllowed({
            xtype: 'checkbox',
            fieldLabel:me.strings.fullMatchLabel,
            name:'edit100PercentMatch',
            itemId:'edit100PercentMatch',
            listeners:{
            	change:function(cb, newValue, oldValue, eOpts ){
            		 me.lookupViewModel().get('currentTask').set('edit100PercentMatch',newValue);
            	} 
            }
        },'editorEditTaskEdit100PercentMatch',{
            xtype: 'displayfield',
            renderer: function(value, displayField) {
                return value ? me.strings.editTrue : me.strings.editFalse;
            }
        }));
        
        me.setUsageModeConfig(items);
        
        return items;
    },
    applyIfNotAllowed: function(baseItem, right, overwrite) {
        if(Editor.app.authenticatedUser.isAllowed(right)) {
            return baseItem;
        }
        return Ext.apply(baseItem, overwrite);
    },
    customerRenderer : function(val) {
        if (val == undefined) {
            return val;
        }
        var customersStore = Ext.StoreManager.get('customersStore'),
            customer = customersStore && customersStore.getById(val);
        return customer ? customer.get('name') : '';
    },
    /**
     * Adds the usage mode radio box to the items list if allowed
     */
    setUsageModeConfig: function(items) {
        var me=this,
            auth = Editor.app.authenticatedUser;
        //without task user assoc view, this setting may also not be visible 
        if(!auth.isAllowed('editorChangeUserAssocTask')) {
            return;
        }
        items.push({
            xtype      : 'radiogroup',
            fieldLabel : me.strings.usageModeTitle,
            columns: 1,
            anchor: '100%',
            items: [
                {
                    xtype: 'component', 
                    html: me.strings.usageModeInfo, 
                    cls:'x-form-check-group-label'
                },{
                    xtype: 'component', 
                    html: me.strings.usageModeDisabled,
                    bind: {
                        hidden:'{!disableUsageMode}'
                    }
                },{
                    //readonly bind: admin.TaskUserAssocs mit Inhalt
                    boxLabel  : me.strings.usageModeCoop,
                    name      : 'usageMode',
                    inputValue: 'cooperative',
                    bind: {
                        disabled:'{disableUsageMode}'
                    }
                }, {
                    //readonly bind: admin.TaskUserAssocs mit Inhalt
                    boxLabel  : me.strings.usageModeCompetitive,
                    name      : 'usageMode',
                    inputValue: 'competitive',
                    bind: {
                        disabled:'{disableUsageMode}'
                    }
                }, {
                    //readonly bind: admin.TaskUserAssocs mit Inhalt
                    boxLabel  : me.strings.usageModeSimultaneous,
                    name      : 'usageMode',
                    inputValue: 'simultaneous',
                    bind: {
                        disabled:'{disableUsageMode}'
                    }
                }
            ]
        });
    },
    getPmFieldConfig: function() {
        var me=this,
            auth = Editor.app.authenticatedUser;
        if(!auth.isAllowed('editorEditTaskPm') || !auth.isAllowed('editorEditAllTasks')) {
            return {
                xtype: 'displayfield',
                name: 'pmGuid',
                fieldLabel: me.strings.pmGuid
            };
        }
        return {
            xtype: 'combo',
            fieldLabel: me.strings.pmGuid,
            allowBlank: false,
            typeAhead: false,
            forceSelection: true,
            anyMatch: true,
            queryMode: 'local',
            name: 'pmGuid',
            itemId: 'pmGuid',
            displayField: 'longUserName',
            valueField: 'userGuid',
            listConfig: {
                loadMask: false
            },
            store: Ext.create('Ext.data.Store',{
                autoLoad: true,
                model: 'Editor.model.admin.User',
                pageSize: 0,
                proxy : {
                    type : 'rest',
                    url: Editor.data.restpath+'user/pm',
                    extraParams: {
                        sort: '[{"property":"surName","direction":"ASC"},{"property":"firstName","direction":"ASC"}]'
                    },
                    reader : {
                        rootProperty: 'rows',
                        type : 'json'
                    }
                }
            })
        };
    }
  });
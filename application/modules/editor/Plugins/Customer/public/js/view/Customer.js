
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

Ext.define('Editor.plugins.Customer.view.Customer', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.customerPanel',
    itemId:'customerPanel',
    requires: [
        'Editor.plugins.Customer.view.CustomerViewModel',
        'Editor.plugins.Customer.view.CustomerViewController',
    ],

    stores:['Editor.plugins.Customer.store.Customer'],

    controller: 'customerPanel',
    viewModel: {
        type: 'customerPanel'
    },
    listeners: {
        activate: {
            fn: 'reloadCustomerStore',//when customers panel is displayed,this function is executed in the ViewController
            scope: 'controller'
        },
        render:{
            fn:'onCustomerPanelRender',
            scope:'controller'
        }
    },
    strings:{
        addCustomer:'#UT#Hinzufügen',
        refreshCustomer:'Aktualisieren',
        customerName:'#UT#Kundenname',
        customerNumber:'#UT#Kundennummer',
        save:'#UT#Speichern',
        cancel:'#UT#Abbrechen',
        remove:'#UT#Löschen',
        editCustomerTitle:'#UT#Kunde bearbeiten',
        addCustomerTitle:'#UT#Kunde hinzufügen',
        saveCustomerMsg:'#UT#Kunde wird gespeichert...',
        customerSavedMsg:'#UT#Kunde gespeichert!',
        customerDeleteMsg:'#UT#Diesen Kunden löschen?',
        customerDeleteTitle:'#UT#Kunden löschen',
        customerDeletedMsg:'#UT#Kunde gelöscht'
    },
    shrinkWrap: 0,
    layout: 'border',
    collapsed: false,
    title: '#UT#Kundenübersicht',
    defaultListenerScope: true,
    defaultButton: 'saveButton',
    referenceHolder: true,

    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                items: [
                    {
                        xtype: 'gridpanel',
                        flex: 0.7,
                        region: 'center',
                        split: true,
                        reference: 'list',
                        resizable: false,
                        title: '',
                        forceFit: true,
                        bind: {
                            store: 'customersStore'
                        },
                        columns: [
                            {
                                xtype: 'gridcolumn',
                                dataIndex: 'name',
                                text: me.strings.customerName,
                                filter: {
                                    type: 'string'
                                }
                            },
                            {
                                xtype: 'gridcolumn',
                                dataIndex: 'number',
                                text:  me.strings.customerNumber,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ],
                        listeners: {
                            itemdblclick: {
                                fn: 'dblclick',
                                scope: 'controller'
                            }
                        },
                        selModel: {
                            selType: 'rowmodel'
                        },
                        plugins: [
                            {
                                ptype: 'gridfilters'
                            }
                        ],
                        viewConfig: {
                            listeners: {
                                beforerefresh: 'onViewBeforeRefresh'
                            }
                        }
                    },
                    {
                        xtype: 'panel',
                        flex: 0.3,
                        region: 'east',
                        split: true,
                        reference: 'display',
                        width: 150,
                        layout: 'card',
                        bodyBorder: true,
                        items: [
                            {
                                xtype: 'form',
                                reference: 'form',
                                bodyPadding: 10,
                                fieldDefaults: {
                                    anchor: '1'
                                },
                                title:me.strings.editCustomerTitle,
                                bind: {
                                    disabled: '{!record}',
                                    title: '{title}'
                                },
                                items: [
                                    {
                                        xtype: 'textfield',
                                        fieldLabel: me.strings.customerName,
                                        name: 'name',
                                        allowBlank: false,
                                        maxLength: 255,
                                        minLength: 3
                                    },
                                    {
                                        xtype: 'textfield',
                                        fieldLabel: me.strings.customerNumber,
                                        name: 'number',
                                        allowBlank: false,
                                        maxLength: 255
                                    },
                                    {
                                        xtype: 'container',
                                        padding: 10,
                                        layout: {
                                            type: 'hbox',
                                            align: 'middle',
                                            pack: 'center'
                                        },
                                        items: [
                                            {
                                                xtype: 'button',
                                                flex: 1,
                                                formBind: true,
                                                itemId: 'saveButton',
                                                reference: 'saveButton',
                                                margin: 5,
                                                text: me.strings.save,
                                                listeners: {
                                                    click: {
                                                        fn: 'save',
                                                        scope: 'controller'
                                                    }
                                                }
                                            },
                                            {
                                                xtype: 'button',
                                                flex: 1,
                                                itemId: 'cancelButton',
                                                margin: 5,
                                                text: me.strings.cancel,
                                                listeners: {
                                                    click: {
                                                        fn: 'cancelEdit',
                                                        scope: 'controller'
                                                    }
                                                }
                                            },
                                            {
                                                xtype: 'button',
                                                flex: 1,
                                                itemId: 'removeButton',
                                                margin: 5,
                                                text: me.strings.remove,
                                                disabled:true,
                                                reference: 'removeButton',
                                                listeners: {
                                                    click: {
                                                        fn: 'remove',
                                                        scope: 'controller'
                                                    }
                                                }
                                                
                                            }
                                        ]
                                    }
                                ]
                            }
                        ]
                    }
                ],
                dockedItems: [
                    {
                        xtype: 'toolbar',
                        dock: 'top',
                        items: [
                            {
                                xtype: 'button',
                                iconCls: 'icon-add',
                                text: me.strings.addCustomer,
                                listeners: {
                                    click: {
                                        fn: 'add',
                                        scope: 'controller'
                                    }
                                }
                            },
                            {
                                xtype: 'button',
                                iconCls: 'icon-refresh',
                                text: me.strings.refreshCustomer,
                                listeners: {
                                    click: {
                                        fn: 'refresh',
                                        scope: 'controller'
                                    }
                                }
                            }
                        ]
                    }
                ]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    initComponent: function() {
        var me = this;
        me.callParent(arguments);
    },
    
    onViewBeforeRefresh: function(dataview, eOpts) {
        //workaround / fix for TMUE-11
        dataview.getSelectionModel().deselectAll();
    }

});

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

/**
 * @class Editor.controller.Termportal
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Termportal', {
    extend : 'Ext.app.Controller',
    
    views: [
        'Editor.plugins.Customer.view.Customer',
        'Editor.plugins.Customer.model.Customer',
        'Editor.plugins.Customer.view.CustomerTagField'
    ],

    refs:[{
        ref: 'headToolBar',
        selector: 'headPanel toolbar#top-menu'
    }],

    listen: {
        component: {
            'headPanel toolbar#top-menu': {
                afterrender: 'onHeadPanelAfterRender'
            },
            'viewport container[region="center"] panel':{
                hide:'onCentarPanelComponentAfterLayout'
            }
        },
        controller:{
            '#Editor.$application': {
                editorViewportOpen: 'onEditorViewportOpen'
            }
        }
    },

    strings:{
        termPortal:'#UT#Terminologieportal'
    },
    
    /***
     * hide the customers button when editor is opened
     */
    onEditorViewportOpen:function(){
        this.getHeadToolBar().down('#btnTermPortal').setHidden(true);
    },

    /**
     * On head panel after render handler
     */
    onHeadPanelAfterRender: function(toolbar) {
        //if we are in edit task mode, do not add the portal button
        if(Ext.ComponentQuery.query('#segmentgrid')[0]){
            return;
        }
        var me=this;
        
        if(!me.isTermportalAllowed()){
            return;
        }
        var pos = toolbar.items.length - 2;
        toolbar.insert(pos, {
            xtype: 'button',
            itemId: 'btnTermPortal',
            text:me.strings.termPortal,
            listeners:{
                click:{
                    fn:'onTermPortalButtonClick',
                    scope:me
                }
            }
        });
    },

    /***
     * Term portal button handler
     */
    onTermPortalButtonClick:function(){
        if(this.isTermportalAllowed()){
            window.open(Editor.data.restpath+"termportal", '_blank');
        }  
    },
    
    /***
     * Fires when the components in this container are arranged by the associated layout manager.
     */
    onCentarPanelComponentAfterLayout:function(component){
        if(!this.isTermportalAllowed()){
            return;
        }
        //set the component to visible on each centar panel element hide
        this.setCustomerOverviewButtonHidden(false);
    },

    /**
     * Set the term portal button hidden property
     */
    setCustomerOverviewButtonHidden:function(isHidden){
        if(!this.getHeadToolBar()){
            return;
        }
        this.getHeadToolBar().down('#btnTermPortal').setHidden(isHidden);
    },

    /**
     * Check if the user has right to use the term portal
     */
    isTermportalAllowed:function(){
        var userRoles=Editor.data.app.user.roles.split(",");
        return (Ext.Array.indexOf(userRoles, "termCustomerSearch") >= 0);
    }
});
    
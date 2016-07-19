
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
 * @class Editor.plugins.MatchResource.view.SearchGrid
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.plugins.MatchResource.view.SearchGrid', {
    extend : 'Ext.grid.Panel',
    requires: [
               'Editor.plugins.MatchResource.view.SearchGridViewController',
               'Editor.plugins.MatchResource.view.SearchGridViewModel'
               ],
    alias : 'widget.matchResourceSearchGrid',
    controller: 'matchResourceSearchGrid',
    viewModel: {
        type: 'matchResourceSearchGrid'
    },
    bind: {
        store: '{editorsearch}'
    },
    itemId:'searchGridPanel',
    assocStore : [],
    border: false,
    layout: 'fit',
    strings: {
        source: '#UT#Quelltext',
        target: '#UT#Zieltext',
        match: '#UT#Matchrate',
        sourceEmptyText:'#UT#Quelltextsuche',
        targetEmptyText:'#UT#Zieltextsuche',
        tmresource:'#UT#TM-Ressource'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                //height:'100%',
                columns: [{
                    xtype: 'gridcolumn',
                    flex: 33/100,
                    dataIndex: 'source',
                    text: me.strings.source
                },{
                    xtype: 'gridcolumn',
                    flex: 33/100,
                    dataIndex: 'target',
                    text: me.strings.target
                },{
                    xtype: 'gridcolumn',
                    flex: 33/100,
                    dataIndex: 'service',
                    renderer: function(val, meta, record) {
                        var str =me.assocStore.findRecord('id',record.get('tmmtid'));
                        meta.style="background-color:#"+str.get('color')+" !important;";
                        return str.get('name')+'-'+str.get('serviceName');
                    },
                    text: me.strings.tmresource
                },{
                    xtype: 'actioncolumn',
                    width: 60,
                    renderer: function(val, meta, record) {
                        if(record.get('showMoreIcon')){
                            meta.tdAttr = 'data-qtip="Show more..."';
                            meta.tdCls  = meta.tdCls  + 'ico-tm-information';
                        }
                    }
                }],
                dockedItems: [{
                    xtype: 'panel',
                    //height: '50px',
                    layout: {
                        type: 'hbox',
                        align: 'stretch'
                    },
                    border: false,
                    items:[{
                        xtype:'textfield',
                        id:'sourceSearch',
                        //width: '31%',
                        name:'source',
                        enableKeyEvents: true,
                        emptyText:me.strings.sourceEmptyText,
                        padding:'10 10 10 10',
                    },{
                        xtype:'textfield',
                        id:'targetSearch',
                        //width: '31%',
                        name:'target',
                        enableKeyEvents: true,
                        emptyText:me.strings.targetEmptyText,
                        padding:'10 10 10 10',
                    }]
                }]
            };
        me.assocStore = instanceConfig.assocStore;
        if (instanceConfig) {
            me.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
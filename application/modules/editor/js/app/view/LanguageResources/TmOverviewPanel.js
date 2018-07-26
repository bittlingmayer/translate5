
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.LanguageResources.TmOverviewPanel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.LanguageResources.TmOverviewPanel', {
    extend : 'Ext.grid.Panel',
    alias: 'widget.tmOverviewPanel',
    itemId: 'tmOverviewPanel',
    title:'#UT#Sprachressourcen',
    strings: {
        name: '#UT#Name',
        edit: '#UT#Bearbeiten',
        erase: '#UT#Löschen',
        tasks: '#UT#Zugewiesene Aufgaben',
        download: '#UT#Dateibasiertes TM herunterladen und lokal speichern',
        resource: '#UT#Ressource',
        color: '#UT#Farbe',
        refresh: '#UT#Aktualisieren',
        add: '#UT#Hinzufügen',
        import: '#UT#Weitere TM Daten in Form einer TMX Datei importieren und dem TM hinzufügen',
        noTaskAssigned:'#UT#Keine Aufgaben zugewiesen.',
        sourceLang: '#UT#Quellsprache',
        targetLang: '#UT#Zielsprache',
        tmmtStatusColumn: '#UT#Status',
        tmmtStatus: {
            loading: '#UT#Statusinformationen werden geladen',
            error: '#UT#Fehler',
            available: '#UT#verfügbar',
            unknown: '#UT#unbekannt',
            noconnection: '#UT#Keine Verbindung!',
            import: '#UT#importiert',
            notloaded: '#UT#verfügbar'
        },
        taskassocgridcell:'#UT#Zugewiesene Aufgaben'
    },
    cls:'tmOverviewPanel',
    height: '100%',
    layout: {
        type: 'fit'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                store : 'Editor.store.LanguageResources.TmMts',
                viewConfig: {
                    getRowClass: function(record) {
                        var cls = record.get('filebased') ? 'match-ressource-filebased' : 'match-ressource-non-filebased';
                        return cls + ' tmmt-status-'+record.get('status');
                    }
                },
                columns: [{
                    xtype: 'gridcolumn',
                    width: 150,
                    dataIndex: 'name',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.name
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'sourceLang',
                    renderer : me.langRenderer,
                    cls: 'source-lang',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.sourceLang
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'targetLang',
                    renderer : me.langRenderer,
                    cls: 'target-lang',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.targetLang
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'color',
                    renderer: function(value, metaData, record) {
                        return '<div style="float: left; width: 15px; height: 15px;margin-right:5px; border: 1px solid rgba(0, 0, 0, .2);background: #'+record.data.color+';"></div>';
                    },
                    text: me.strings.color
                },{
                    xtype: 'gridcolumn',
                    width: 160,
                    text: me.strings.tmmtStatusColumn,
                    dataIndex: 'status',
                    tdCls: 'status',
                    renderer: function(value, meta, record) {
                        var str = me.strings.tmmtStatus,
                            info = record.get('statusInfo');
                        if(value === "loading") {
                            record.load();
                            meta.tdCls = 'loading';
                            meta.tdAttr = 'data-qtip="'+str.loading+'"';
                            return ''; //no string since icon set
                        }
                        if(str[value]){
                            value = str[value];
                        }
                        else {
                            value = str.unknown;
                        }
                        if(info) {
                            meta.tdAttr = 'data-qtip="'+info+'"';
                            meta.tdCls = 'infoIcon';
                        }
                        else {
                            meta.tdAttr = 'data-qtip=""';
                        }
                        return value;

                    }
                },{
                    xtype: 'actioncolumn',
                    width: 98,
                    items: [{
                        tooltip: me.strings.edit,
                        action: 'edit',
                        iconCls: 'ico-tm-edit'
                    },{
                        tooltip: me.strings.erase,
                        action: 'delete',
                        iconCls: 'ico-tm-delete'
                    },{
                        tooltip: me.strings.tasks,
                        action: 'tasks',
                        iconCls: 'ico-tm-tasks'
                    },{
                        tooltip: me.strings.import,
                        action: 'import',
                        iconCls: 'ico-tm-import'
                    },{
                        tooltip: me.strings.download,
                        action: 'download',
                        iconCls: 'ico-tm-download'
                    }]
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    text: me.strings.resource,
                    dataIndex: 'serviceName',
                    tdCls: 'serviceName',
                    renderer: function(v, meta, rec){
                        var store = Ext.getStore('Editor.store.LanguageResources.Resources'),
                            resource = store.getById(rec.get('resourceId'));
                        if(resource) {
                            meta.tdAttr = 'data-qtip="'+resource.get('name')+'"';
                        }
                        return v;
                    },
                    filter: {
                        type: 'string'
                    }
                },{
                    xtype:'gridcolumn',
                    width: 40,
                    dataIndex:'taskList',
                    tdCls: 'taskList',
                    cls: 'taskList',
                    text: me.strings.taskassocgridcell,
                    renderer: function(v, meta, rec){
                        var tasks = [], i;
                        
                        if(!v || v.length == 0){
                            tasks.push(this.strings.noTaskAssigned);
                        }
                        else {
                            for(i = 0;i<v.length;i++){
                                tasks.push(v[i]);
                            }
                        }
                        meta.tdAttr = 'data-qtip="'+tasks.join('<br />')+'"';
                        return v.length;
                    }
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [{
                        xtype: 'button',
                        iconCls: 'ico-tm-add',
                        itemId: 'btnAddTm',
                        text: me.strings.add,
                        tooltip: me.strings.add
                    },{
                        xtype: 'button',
                        iconCls: 'ico-refresh',
                        itemId: 'btnRefresh',
                        text: me.strings.refresh,
                        tooltip: me.strings.refresh
                    }]
                },{
                    xtype: 'pagingtoolbar',
                    store: 'Editor.store.LanguageResources.TmMts',
                    dock: 'bottom',
                    displayInfo: true
            }]
      };

      if (instanceConfig) {
          me.self.getConfigurator().merge(me, config, instanceConfig);
      }
      return me.callParent([config]);
    },
    langRenderer : function(val, md) {
        var lang = Ext.StoreMgr.get('admin.Languages').getById(val), label;
        if (lang) {
            label = lang.get('label');
            md.tdAttr = 'data-qtip="' + label + '"';
            return label;
        }
        return '';
    },
});
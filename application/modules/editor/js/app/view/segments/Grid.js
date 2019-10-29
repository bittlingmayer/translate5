
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
 * @class Editor.view.segments.Grid
 * @extends Editor.view.ui.segments.Grid
 * @initalGenerated
 */
Ext.define('Editor.view.segments.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.segments.grid.Toolbar',
        'Editor.view.segments.RowEditing',
        'Editor.view.segments.column.Content',
        'Editor.view.segments.column.ContentEditable',
        'Editor.view.segments.column.SegmentNrInTask',
        'Editor.view.segments.column.State',
        'Editor.view.segments.column.Quality',
        'Editor.view.segments.column.MatchrateType',
        'Editor.view.segments.column.Matchrate',
        'Editor.view.segments.column.AutoState',
        'Editor.view.segments.column.UserName',
        'Editor.view.segments.column.Comments',
        'Editor.view.segments.column.WorkflowStep',
        'Editor.view.segments.column.Editable',
        'Editor.view.segments.column.IsWatched',
        'Editor.util.SegmentContent',
        'Editor.view.segments.GridViewModel'
    ],
    viewModel: {
        type: "segmentsGrid"
    },
    plugins: ['gridfilters'],
    alias: 'widget.segments.grid',
    id: 'segment-grid',
    stateful: false,
    store: 'Segments',
    title: '#UT#Segmentliste und Editor',
    title_readonly: '#UT#Segmentliste und Editor - [LESEMODUS]',
    title_addition_unconfirmed: '#UT# - [AUFGABE UNBESTÄTIGT]',
    column_edited: '#UT#bearbeibar',    
    target_original: '#UT# (zur Importzeit)',    
    column_edited_icon: '{0} <img src="{1}" class="icon-editable" alt="{2}" title="{3}">',
    item_logoutheaderbtn:'#UT#Ausloggen',
    item_leavetaskheaderbtn:'#UT#Aufgabe verlassen',
    columnMap:{},
    hasRelaisColumn: false,
    stateData: {},
    qualityData: {},
    userSellectedSegments:[],
    userSellectedSegmentsCss:'userSellectedSegments',
    constructor: function() {
        this.plugins = [
            'gridfilters',
            Ext.create('Editor.view.segments.RowEditing')
        ];
        this.callParent(arguments);
    },
    bind:{
    	userSellectedSegments:'{addUserSellectedSegments}'
    },
    initComponent: function() {
        var me = this,
            columns = [],
            firstTargetFound = false,
            fields = Editor.data.task.segmentFields(),
            userPref = Editor.data.task.userPrefs().first(),
            fieldList = [],
            fieldClsList;

        if(Editor.app.authenticatedUser.isAllowed('editorCommentsForLockedSegments')) {
            me.addCls('comments-for-locked-segments');
        } 
        
        this.store = Ext.create('Editor.store.Segments',{
            storeId: 'Segments'
        });
        
        //befülle interne Hash Map mit QM und Status Werten:
        Ext.each(Editor.data.segments.stateFlags, function(item){
            me.stateData[item.id] = item.label;
        });
        Ext.each(Editor.data.segments.qualityFlags, function(item){
            me.qualityData[item.id] = item.label;
        });

        columns.push.apply(columns, [{
            xtype: 'segmentNrInTaskColumn',
            itemId: 'segmentNrInTaskColumn',
            width: 50
        },{
            xtype: 'workflowStepColumn',
            itemId: 'workflowStepColumn',
            renderer: function(v) {
                var steps = Editor.data.task.getWorkflowMetaData().steps;
                return steps[v] ? steps[v] : v;
            },
            width: 140
        },{
            xtype: 'autoStateColumn',
            itemId: 'autoStateColumn',
            width: 82
        },{
            xtype: 'matchrateColumn'
        },{
            xtype: 'matchrateTypeColumn',
            hidden: true
        }]);
        
        //conv store data to an array
        fields.each(function(rec){
            fieldList.push(rec);
        });
        
        fieldList = Editor.model.segment.Field.listSort(fieldList);
        
        Ext.Array.each(fieldList, function(rec){
            var name = rec.get('name'),
                type = rec.get('type'),
                editable = rec.get('editable'),
                label = rec.get('label'),
                width = rec.get('width'),
                widthFactorHeader = Editor.data.columns.widthFactorHeader,
                widthOffsetEditable = Editor.data.columns.widthOffsetEditable,
                ergoWidth = width * Editor.data.columns.widthFactorErgonomic,
                labelWidth = (label.length) * widthFactorHeader,
                maxWidth = Editor.data.columns.maxWidth,
                isEditableTarget = type == rec.TYPE_TARGET && editable,
                isErgoVisible = !firstTargetFound && isEditableTarget || type == rec.TYPE_SOURCE || type == rec.TYPE_RELAIS;
            
            if(!me.hasRelaisColumn && type == rec.TYPE_RELAIS) {
                me.hasRelaisColumn = true;
            }
            
            //stored outside of function and must be set after isErgoVisible!
            firstTargetFound = firstTargetFound || isEditableTarget; 

            if(!rec.isTarget() || ! userPref.isNonEditableColumnDisabled()) {
                width = Math.min(Math.max(width, labelWidth), maxWidth);
                if(isEditableTarget) {
                    label = label + me.target_original;
                }
                var col2push = {
                    xtype: 'contentColumn',
                    grid: me,
                    segmentField: rec,
                    fieldName: name,
                    hidden: !userPref.isNonEditableColumnVisible() && rec.isTarget(),
                    isErgonomicVisible: isErgoVisible && !editable,
                    isErgonomicSetWidth: true, //currently true for all our affected default fields
                    isContentColumn: true,//TODO this propertie is missing
                    text: label,
                    tooltip: label,
                    width: width
                };
                if(width < maxWidth){
                //the following line would be an alternative to only adjust the columnWidth of 
                //hidden cols in ergoMode. This would ensure, that the horizontal scrollbar
                //keeps working, which it does not if it is not initialized at the first place
                //(due to an ExtJs-bug)    
                //if(width !== maxWidth && (isErgoVisible && !editable)=== false){
                    col2push.ergonomicWidth = Math.max(labelWidth, ergoWidth);
                }
                columns.push(col2push);
            }
            
            if(editable){
                labelWidth = (label.length) * widthFactorHeader + widthOffsetEditable;
                label = Ext.String.format(me.column_edited_icon, rec.get('label'), Ext.BLANK_IMAGE_URL, me.column_edited, me.column_edited);
                width = Math.min(Math.max(width, labelWidth), maxWidth);
                var col2push = {
                    xtype: 'contentEditableColumn',
                    grid: me,
                    segmentField: rec,
                    fieldName: name,
                    isErgonomicVisible: isErgoVisible,
                    isErgonomicSetWidth: true, //currently true for all our affected default fields
                    isContentColumn: true,//TODO those properties are missing 
                    isEditableContentColumn: true,//TODO those properties are missing
                    tooltip: rec.get('label'),
                    text: label,
                    width: width
                };
                if(width < maxWidth){
                //the following line would be an alternative to only adjust the columnWidth of 
                //hidden cols in ergoMode. This would ensure, that the horizontal scrollbar
                //keeps working, which it does not if it is not initialized at the first place
                //(due to an ExtJs-bug)
                //if(width !== maxWidth && !isErgoVisible){
                    col2push.ergonomicWidth = Math.max(labelWidth, ergoWidth);
                }
                columns.push(col2push);
            }
        });
        
    
        columns.push.apply(columns, [{
            xtype: 'commentsColumn',
            itemId: 'commentsColumn',
            width: 200
        }]);
    
        if(Editor.data.segments.showStatus){
            columns.push({
                xtype: 'stateColumn',
                itemId: 'stateColumn'
            });
        }
        
        if(Editor.data.segments.showQM){
            columns.push({
                xtype: 'qualityColumn',
                itemId: 'qualityColumn'
            });
        }
    
        columns.push.apply(columns, [{
            xtype: 'usernameColumn',
            itemId: 'usernameColumn',
            width: 122
        },{
            xtype: 'editableColumn',
            itemId: 'editableColumn'
        },{
            xtype: 'iswatchedColumn',
            itemId: 'iswatchedColumn'
        }]);
    
        Ext.applyIf(me, {
       /**
        * for information se below onReconfigure
        *
        * listeners: {'reconfigure': Ext.bind(this.onReconfigure, this)},
        */
        	listeners:{
    		  beforerender:function(grid){
    			  //Disable the sorting on column click in editor : TRANSLATE-1295
    			  grid.down('headercontainer').sortOnClick=false;
    		  }
    		},
    		header: {
    			padding:'8 8 8 8',//align the buttons with the toolbar buttons
    	        items: [{
                	xtype: 'button',
                    itemId:'toolbarInfoButton',
                    icon: Editor.data.moduleFolder+'images/information-white.png',
                    tooltip: me.addToolbarInfoButtonTpl()
    	        },{
    	        	xtype: 'button',
    	            itemId: 'leaveTaskHeaderBtn',
    	            icon: Editor.data.moduleFolder+'images/table_back.png',
    	            text:me.item_leavetaskheaderbtn,
    	            hidden: !Editor.controller.HeadPanel || Editor.data.editor.toolbar.hideLeaveTaskButton
    	        },{
    	        	xtype: 'button',
    	        	itemId:'logoutHeaderBtn',
    	        	icon: Editor.data.moduleFolder+'images/door_out.png',
    	        	text:me.item_logoutheaderbtn,
    	        	hidden: !Editor.controller.HeadPanel || Editor.data.editor.toolbar.hideLogoutButton
    	        }]
    	    },
            columns: columns
        });

        me.callParent(arguments);

        fieldClsList = me.query('contentEditableColumn').concat(me.query('contentColumn'));
        Ext.Array.each(fieldClsList, function(item, idx){
            fieldClsList[idx] = '#segment-grid .x-grid-row .x-grid-cell-'+item.itemId+' .x-grid-cell-inner { width: '+item.width+'px; }';
        });
        Ext.util.CSS.removeStyleSheet('segment-content-width-definition');
        Ext.util.CSS.createStyleSheet(fieldClsList.join("\n"),'segment-content-width-definition');
    },
    
    initConfig: function(instanceConfig) {
            var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                viewConfig: {
                    getRowClass: function(record, rowIndex, rowParams, store){
                        var newClass = 'segment-font-sizable',
                            // only on non sorted list we mark last file segments
                            isDefaultSort = (store.sorters.length == 0),
                            tracking = Editor.data.task.userTracking(),
                            isFirstInFile = false,
                            nextRec = null;
                        
                        if (isDefaultSort && record.get('isFirstofFile')){
                            newClass += ' first-in-file';
                        }
                        me.lastRowIdx = rowIndex;
                        if (!record.get('editable')) {
                            newClass += ' editing-disabled';
                        }
                        if(record.get('usedByUserGuid')) {
                            tracking = tracking.getAt(tracking.findExact('userGuid', record.get('usedByUserGuid')));
                            newClass += ' other-user-select usernr-'+tracking.get('taskOpenerNumber');
                        }
                        return newClass;
                    }
                },
                dockedItems: [{
                    xtype: 'segmentsToolbar',
                    dock: 'top',
                    }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /**
     * Scrolls the view to the segment on the given rowindex,
     * positions the desired segment in the view as defined in targetregion
     * editor means under the roweditor, if no editor is opened the default is used.
     * 
     * @param {Integer} rowindex
     * @param {Object} config, for config see positionRowAfterScroll, its completly given to that method
     */
    scrollTo: function(rowindex, config) {
        var me = this,
            view = me.getView(),
            options = {
                focus: true,
                animate: false //may not be animated, to place the callback at the correct place 
            };
        
        options.callback = function(idx, model, row) {
            if(model.get('editable')){
                me.selectOrFocus(rowindex);
            }
            me.positionRowAfterScroll(rowindex, row, config);
        };

        view.bufferedRenderer.scrollTo(rowindex, options);
    },
    /**
     * positions the given row to the given target, for valid targets see scrollTo
     * @private
     * @param {Integer} rowindex
     * @param {HTMLElement} row
     * @param {Object} config
     * config parameters: 
     *   target: {String} one of "editor", "top", "bottom", "center" (default), 
     *   notScrollCallback: {Function} callback which will be called, if we are not possible to scroll to the desired position (top/bottom reached)
     *   callback: {Function} callback which will be called in every case after final scroll animation

     */
    positionRowAfterScroll: function(rowindex, row, config) {
        var me = this,
            view = me.getView(),
            editor = me.editingPlugin.editor,
            rowFly = Ext.fly(row),
            rowHeight = rowFly.getHeight(),
            rowTop = rowFly.getOffsetsTo(view)[1],
            topMargin = 20,
            viewHeight = view.getHeight(),
            bottomMargin = 20,
            config = Ext.applyIf(config || {}, {
                target: 'editor',
                notScrollCallback: Ext.emptyFn,
                callback: Ext.emptyFn
            }),
            target = config.target,
            deltaY;
                    
        //if no editor exists scroll to center
        if(target == 'editor' && !editor) {
            target = 'center';
        }

        switch (target) {
            case 'editor':
                deltaY = editor.editorLocalTop - rowTop;
                break;
            case 'top':
                deltaY = topMargin - rowTop;
                break;
            case 'bottom':
                deltaY = (viewHeight - bottomMargin) - (rowTop + rowHeight);
                break;
            case 'center':
            default:
                deltaY = viewHeight/2 - (rowTop + rowHeight/2);
                break;
        }
        if(!view.el.scroll('t', deltaY, {callback: config.callback})) {
            config.notScrollCallback();
        }
    },
    /**
     * selects and give the focus to the segment on the given rowindex
     */
    selectOrFocus: function(rowIdx) {
        var me = this,
            sm = me.getSelectionModel();
        if(sm.isSelected(rowIdx)){
            me.getView().focusRow(rowIdx);
        }
        else {
            sm.select(rowIdx);
        }
    },
    
    /***
     * Return visible row indexes in segment grid
     * TODO if needed move this as overide so it can be used for all grids
     * @returns {Object} { top:topIndex, bottom:bottomIndex }
     */
    getVisibleRowIndexBoundaries:function(){
        var view=this.getView(),
            vTop = view.el.getTop(),
            vBottom = view.el.getBottom(),
            top=-1, bottom=-1;


        Ext.each(view.getNodes(), function (node) {
            if (top<0 && Ext.fly(node).getBottom() > vTop) {
                top=view.indexOf(node);
            }
            if (Ext.fly(node).getTop() < vBottom) {
                bottom = view.indexOf(node);
            }
        });

        return {
            top:top,
            bottom:bottom,
        };
    },
    
    /***
     * Add the toolbarinfo button tooltip
     */
    addToolbarInfoButtonTpl:function(){
        var tpl = new Ext.XTemplate(Editor.util.Constants.appInfoTpl),
        	infoPanel=Ext.create('Editor.view.ApplicationInfoPanel');
        return tpl.applyTemplate(infoPanel.getEditorTplData());
    },
    
    /***
     * Set the user selected segmetns. In the collection array
     * the kew is the user guid an the value is the sellected segment
     */
    setUserSellectedSegments:function(data){
    	if(!data){
    		return;
    	}
        var me=this;
        //with this alway the last selected for the user will be in the array
        me.userSellectedSegments[data.user]=data.segment;
    	console.log(this.userSellectedSegments);
    	me.undateUserSelectedSegments();
    },
    
    getUserSellectedSegments:function(){
    	return this.userSellectedSegments;
    },
    
    /***
     * Update the grid rows with the selected segments from the users
     */
    undateUserSelectedSegments:function(){
        var me=this;
        me.clearUserSelectedSegments();
    	var grid=Ext.getCmp('segment-grid'),
    		view=grid.getView(),
    		segmentStore=Ext.StoreManager.get('Segments'),
    		row=null;
        

        for (var key in me.userSellectedSegments) {
            // skip loop if the property is from prototype
            if (!me.userSellectedSegments.hasOwnProperty(key)){
                continue;
            }
            var segment= me.userSellectedSegments[key];
            segment=segmentStore.findRecord('id',segment);
    		if(!segment){
    			return;
    		}
    		row=view.getRow(segment);
    		if(!row){
    			return;
    		}
    		Ext.get(row).addCls(me.userSellectedSegmentsCss)
        }
    },

    clearUserSelectedSegments:function(){
        Ext.select('tr.'+this.userSellectedSegmentsCss).removeCls(this.userSellectedSegmentsCss);
    }
});

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
 * Editor.controller.admin.TaskOverview encapsulates the Task Overview functionality
 * @class Editor.controller.admin.TaskOverview
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskOverview', {
  extend : 'Ext.app.Controller',
  requires: ['Editor.view.admin.ExportMenu'],
  models: ['admin.Task'],
  stores: ['admin.Users', 'admin.Tasks','admin.Languages'],
  views: ['admin.TaskGrid', 'admin.TaskAddWindow'],
  refs : [{
      ref: 'headToolBar',
      selector: 'headPanel toolbar#top-menu'
  },{
      ref: 'logoutButton',
      selector: 'headPanel toolbar#top-menu #logoutSingle'
  },{
      ref: 'taskAddForm',
      selector: '#adminTaskAddWindow form'
  },{
      ref: 'tbxField',
      selector: '#adminTaskAddWindow form filefield[name="importTbx"]'
  },{
      ref: 'centerRegion',
      selector: 'viewport container[region="center"]'
  },{
      ref: 'taskGrid',
      selector: '#adminTaskGrid'
  },{
      ref: 'taskAddWindow',
      selector: '#adminTaskAddWindow'
  }],
  alias: 'controller.taskOverviewController',
  
  isCardFinished:false,
  /**
   * Container for translated task handler confirmation strings
   * Deletion of an entry means to disable confirmation.
   */
  confirmStrings: {
      "finish":       {title: "#UT#Aufgabe abschließen?", msg: "#UT#Wollen Sie die Aufgabe wirklich abschließen?"},
      "unfinish":     {title: "#UT#Aufgabe wieder öffnen?", msg: "#UT#Wollen Sie die Aufgabe wirklich wieder öffnen?"},
      "finish-all":   {title: "#UT#Aufgabe für alle Nutzer abschließen?", msg: "#UT#Wollen Sie die Aufgabe wirklich für alle Benutzer abschließen?"},
      "unfinish-all": {title: "#UT#Aufgabe für alle Nutzer wieder öffnen?", msg: "#UT#Wollen Sie die Aufgabe wirklich für alle Benutzer wieder öffnen?"},
      "end":          {title: "#UT#Aufgabe endgültig beenden?", msg: "#UT#Wollen Sie die Aufgabe wirklich für alle Benutzer endgültig beenden?"},
      "reopen":       {title: "#UT#Beendete Aufgabe wieder öffnen?", msg: "#UT#Wollen Sie die beendete Aufgabe wirklich wieder öffnen?"},
      "delete":       {title: "#UT#Aufgabe komplett löschen?", msg: "#UT#Wollen Sie die Aufgabe wirklich komplett und unwiderruflich löschen?"}
  },
  strings: {
      taskImported: '#UT#Aufgabe "{0}" vollständig importiert',
      taskError: '#UT#Die Aufgabe konnte aufgrund von Fehlern nicht importiert werden!',
      taskOpening: '#UT#Aufgabe wird im Editor geöffnet...',
      taskFinishing: '#UT#Aufgabe wird abgeschlossen...',
      taskUnFinishing: '#UT#Aufgabe wird abgeschlossen...',
      taskReopen: '#UT#Aufgabe wird wieder eröffnet...',
      taskEnding: '#UT#Aufgabe wird beendet...',
      taskDestroy: '#UT#Aufgabe wird gelöscht...',
      taskNotDestroyed : '#UT#Aufgabe wird noch verwendet und kann daher nicht gelöscht werden!',
      forcedReadOnly: '#UT#Aufgabe wird durch Benutzer "{0}" bearbeitet und ist daher schreibgeschützt!',
      openTaskAdminBtn: "#UT#Aufgabenübersicht",
  },
  init : function() {
      var me = this;
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('adminViewportClosed', me.clearTasks, me);
      Editor.app.on('editorViewportOpened', me.handleInitEditor, me);
      
      me.getAdminTasksStore().on('load', me.startCheckImportStates, me);
      
      me.control({
          'headPanel toolbar#top-menu' : {
              beforerender: me.initMainMenu
          },
          'button#task-admin-btn': {
              click: me.openTaskGrid
          },
          '#adminTaskGrid': {
              hide: me.handleAfterHide,
              show: me.handleAfterShow,
              celldblclick: me.handleGridClick, 
              cellclick: me.handleGridClick 
          },
          '#adminTaskGrid #reload-task-btn': {
              click: me.handleTaskReload
          },
          '#adminTaskGrid taskActionColumn': {
              click: me.taskActionDispatcher
          },
          '#adminTaskGrid #add-task-btn': {
              click: me.handleTaskAddShow
          },
          '#adminTaskAddWindow #add-task-btn': {
              click: me.handleTaskAdd
          },
          '#adminTaskAddWindow #cancel-task-btn': {
              click: me.handleTaskCancel
          },
          '#adminTaskAddWindow #continue-wizard-btn': {
              click: me.handleContinueWizardClick
          },
          '#adminTaskAddWindow #skip-wizard-btn': {
              click: me.handleSkipWizardClick
          },
          '#adminTaskAddWindow filefield[name=importUpload]': {
              change: me.handleChangeImportFile
          },
          '#segmentgrid': {
              afterrender: me.initTaskReadMode
          },
          'adminTaskAddWindow panel:not([hidden])': {
              wizardCardFinished:me.onWizardCardFinished,
              wizardCardSkiped:me.onWizardCardSkiped
          }
      });
  },
    //***********************************************************************************
    //Begin Events
    //***********************************************************************************
    /**
     * @event taskCreated
     * @param {Ext.form.Panel} form
     * @param {Ext.action.Submit} submit
     * Fires after a task has successfully created
     */
    /**
     * @event handleTaskPreferences
     * @param {Editor.model.admin.Task} task
     * Fires after the User has clicked on the icon to edit the Task Preferences
     */
    /**
     * @event handleTaskChangeUserAssoc
     * @param {Editor.model.admin.Task} task
     * Fires after the User has clicked on the button / cell to edit the Task User Assoc
     */
    //***********************************************************************************
    //End Events
    //***********************************************************************************
  /**
   * injects the task menu into the main menu
   */
  initMainMenu: function() {
      var toolbar = this.getHeadToolBar(),
          insertIdx = 1,
          logout = this.getLogoutButton(),
          grid = this.getTaskGrid();
      if(logout) {
          insertIdx = toolbar.items.indexOf(logout) + 1;
      }
      if(Editor.data.helpUrl){
    	  insertIdx=insertIdx+1;
      }
      toolbar.insert(insertIdx, {
          itemId: 'task-admin-btn',
          xtype: 'button',
          hidden: true,
          text: this.strings.openTaskAdminBtn
      });
  },
  /**
   * handle after show of taskgrid
   */
  handleAfterShow: function(grid) {
      this.getHeadToolBar().down('#task-admin-btn').hide();
      Editor.data.helpSection = 'taskoverview';
      Editor.data.helpSectionTitle = grid.getTitle();
  },
  /**
   * handle after hide of taskgrid
   */
  handleAfterHide: function() {
      this.getHeadToolBar().down('#task-admin-btn').show();
  },
  /**
   * opens the task grid, hides all other
   */
  openTaskGrid: function() {
      var me = this, 
          grid = me.getTaskGrid();

      me.getCenterRegion().items.each(function(item){
          item.hide();
      });
      
      if(grid) {
          //set the value used for displaying the help pages
          grid.show();
      }
      else {
          grid = me.getCenterRegion().add({
              xtype: 'adminTaskGrid',
              height: '100%'
          });
          me.handleAfterShow(grid);
      }
  },
  handleInitEditor: function() {
      this.getHeadToolBar().down('#task-admin-btn').hide();
  },
  clearTasks: function() {
      this.getAdminTasksStore().removeAll();
  },
  loadTasks: function() {
      this.getAdminTasksStore().load();
  },
  startCheckImportStates: function(store) {
      if(!this.checkImportStateTask) {
          this.checkImportStateTask = {
                  run: this.checkImportState,
                  scope: this,
                  interval: 10000
          };
      }
      Ext.TaskManager.start(this.checkImportStateTask);
  },
  /**
   * Checks if all actually loaded tasks are imported completly
   */
  checkImportState: function() {
      var me = this, 
          tasks = me.getAdminTasksStore(),
          foundImporting = 0,
          taskReloaded = function(rec) {
              if(rec.isErroneous()) {
                  Editor.MessageBox.addSuccess(Ext.String.format(me.strings.taskError, rec.get('taskName')));
                  return;
              }
              if(!rec.isImporting()) {
                  Editor.MessageBox.addSuccess(Ext.String.format(me.strings.taskImported, rec.get('taskName')));
              }
          };
      tasks.each(function(task){
          if(!task.isImporting()){
              return;
          }
          task.load({
              success: taskReloaded
          });
          foundImporting++;
      });
      if(foundImporting == 0) {
          Ext.TaskManager.stop(this.checkImportStateTask);
      }
  },
  handleChangeImportFile: function(field, val){
      var name = this.getTaskAddForm().down('textfield[name=taskName]'),
          srcLang = this.getTaskAddForm().down('combo[name=sourceLang]'),
          targetLang = this.getTaskAddForm().down('combo[name=targetLang]'),
          langs = val.match(/-([a-zA-Z]{2,3})-([a-zA-Z]{2,3})\.[^.]+$/);
      if(name.getValue() == '') {
          name.setValue(val.replace(/\.[^.]+$/, ''));
      }
      //simple algorithmus to get the language from the filename
      if(langs && langs.length == 3) {
          var srcStore = srcLang.store,
              targetStore = targetLang.store,
              srcIdx = srcStore.find('label', '('+langs[1]+')', 0, true, true),
              targetIdx = targetStore.find('label', '('+langs[2]+')', 0, true, true);
          
          if(srcIdx >= 0) {
              srcLang.setValue(srcStore.getAt(srcIdx).get('id'));
          }
          if(targetIdx >= 0) {
              targetLang.setValue(targetStore.getAt(targetIdx).get('id'));
          }
      }
  },
  /**
   * Inits the loaded task and the segment grid read only if necessary
   */
  initTaskReadMode: function(grid) {
      var vm = this.application.getController('ViewModes'),
          task = Editor.data.task,
          readonly = task.isReadOnly() || ! this.isAllowed('editorEditTask', task);
      grid.lookupViewModel().set('taskIsReadonly', readonly);
      vm && vm.editMode(readonly);
  },
  /**
   * Method Shortcut for convenience
   * @param {String} right
   * @param {Editor.model.admin.Task} task [optional]
   * @return {Boolean}
   */
  isAllowed: function(right, task) {
      return Editor.app.authenticatedUser.isAllowed(right, task);
  },
  
  /**
   * opens the editor by click or dbl click
   * @param {Ext.grid.View} view
   * @param {Element} colEl
   * @param {Integer} colIdx
   * @param {Editor.model.admin.Task} rec
   * @param {Element} rowEl
   * @param {Integer} rowIdxindex
   * @param {Event} e
   * @param {Object} eOpts
   */
  handleGridClick: function(view, colEl, colIdx, rec, rowEl, rowIdxindex, e, eOpts) {
      //logic for handling single clicks on column taskNr and dblclick on other cols
      var isTaskNr = (view.up('grid').columns[colIdx].dataIndex == 'taskNr'),
          dbl = e.type == 'dblclick'; 
      
      if(!rec.isLocked() && (isTaskNr || dbl)) {
          this.openTaskRequest(rec);
      }
  },
  /**
   * general method to open a task, starting in readonly mode is calculated
   * @param {Editor.model.admin.Task} task
   * @param {Boolean} readonly (optional)
   */
  openTaskRequest: function(task, readonly) {
      var me = this,
          initialState,
          app = Editor.app;
      if(!me.isAllowed('editorOpenTask', task) && !me.isAllowed('editorEditTask', task)){
          return;
      }
      readonly = (readonly === true || task.isReadOnly());
      initialState = readonly ? task.USER_STATE_VIEW : task.USER_STATE_EDIT;
      task.set('userState', initialState);
      app.mask(me.strings.taskOpening, task.get('taskName'));
      task.save({
          success: function(rec, op) {
              if(rec && initialState == task.USER_STATE_EDIT && rec.get('userState') == task.USER_STATE_VIEW) {
                  Editor.MessageBox.addInfo(Ext.String.format(me.strings.forcedReadOnly, rec.get('lockingUsername')));
              }
              app.unmask();
              Editor.app.openEditor(rec, readonly);
          },
          failure: app.unmask
      });
  },
  handleTaskCancel: function() {
      this.getTaskAddForm().getForm().reset();
      this.getTaskAddWindow().close();
  },

  handleTaskAdd: function(button) {
      var me=this,
          win=me.getTaskAddWindow(),
          vm=win.getViewModel();
          winLayout=win.getLayout(),
          nextStep=win.down('#taskUploadCard');
      
      if(me.getTaskAddForm().isValid()){
          if(nextStep.strings && nextStep.strings.wizardTitle){
              win.setTitle(nextStep.strings.wizardTitle);
          }
          
          vm.set('activeItem',nextStep);
          winLayout.setActiveItem(nextStep);
      }
  },
  
  
  /**
   * is called after clicking continue, if there are wizard panels, 
   * then the next available wizard panel is set as active 
   */
  handleContinueWizardClick:function(){
      var me = this,
          win = me.getTaskAddWindow(),
          winLayout=win.getLayout(),
          activeItem=winLayout.getActiveItem();
      
      activeItem.triggerNextCard(activeItem);
  },
  
  /***
   * Called when skip button in wizard window is clicked.
   * The function of this button is to skup the next card group (in the current wizard group, sure if there is one)
   * 
   */
  handleSkipWizardClick:function(){
      var me = this,
          win = me.getTaskAddWindow(),
          winLayout=win.getLayout(),
          activeItem=winLayout.getActiveItem();
      
      activeItem.triggerSkipCard(activeItem);
  },
  
  onWizardCardFinished:function(skipCards){
      var me = this,
          win = me.getTaskAddWindow(),
          winLayout=win.getLayout(),
          nextStep=winLayout.getNext(),
          vm=win.getViewModel();
      
      if(skipCards){
          for(var i=1;i < skipCards;i++){
              winLayout.setActiveItem(nextStep);
              nextStep=winLayout.getNext();
          }
      }

      if(!nextStep){
          //me.handleTaskCancel();
          me.saveTask();
          return;
      }
      if(nextStep.strings && nextStep.strings.wizardTitle){
          win.setTitle(nextStep.strings.wizardTitle);
      }
      
      vm.set('activeItem',nextStep);
      winLayout.setActiveItem(nextStep);
  },
  
  onWizardCardSkiped:function(){
      this.saveTask();
  },
  
  handleTaskAddShow: function() {
      if(!this.isAllowed('editorAddTask')){
          return;
      }
      Ext.widget('adminTaskAddWindow').show();
  },
  /**
   * reloads the Task Grid, will also be called from other controllers
   */
  handleTaskReload: function () {
      this.getAdminTasksStore().load();
  },
  /**
   * calls local task handler, dispatching is done by the icon CSS class of the clicked img
   * the css class ico-task-foo-bar is transformed to the method handleTaskFooBar
   * if this controller contains this method, it'll be called. 
   * First Parameter is the task record.
   * 
   * @param {Ext.grid.View} view
   * @param {DOMElement} cell
   * @param {Integer} row
   * @param {Integer} col
   * @param {Ext.Event} ev
   * @param {Object} evObj
   */
  taskActionDispatcher: function(view, cell, row, col, ev, evObj) {
      var me = this,
          t = ev.getTarget(),
          f = t.className.match(/ico-task-([^ ]+)/),
          camelRe = /(-[a-z])/gi,
          camelFn = function(m, a) {
              return a.charAt(1).toUpperCase();
          },
          actionIdx = ((f && f[1]) ? f[1] : "not-existing"),
          //build camelized action out of icon css class:
          action = ('handleTask-'+actionIdx).replace(camelRe, camelFn),
          right = action.replace(/^handleTask/, 'editor')+'Task',
          task = view.getStore().getAt(row),
          confirm;

      if(! me.isAllowed(right) || ! me[action] || ! Ext.isFunction(me[action])){
          return;
      }

      //if NO confirmation string exists, we call the action unconfirmed. 
      if(! me.confirmStrings[actionIdx]) {
          me[action](task, ev);
          return; 
      }

      confirm = me.confirmStrings[actionIdx];
      Ext.Msg.confirm(confirm.title, confirm.msg, function(btn){
          if(btn == 'yes') {
              me[action](task, ev);
          }
      });
  },
  /**
   * Shorthand method to get the default task save handlers
   * @return {Object}
   */
  getTaskMaskBindings: function(){
      var app = Editor.app;
      return {
          success: app.unmask,
          failure: function(rec, op){
              var recs = op.getRecords(),
                  task = recs && recs[0] || false;
              task && task.reject();
              app.unmask();
          }
      };
  },
  
  //
  //Task Handler:
  //
  
  /**
   * Opens the task readonly
   * @param {Editor.model.admin.Task} task
   */
  handleTaskOpen: function(task) {
      this.openTaskRequest(task, true);
  },
  
  /**
   * Opens the task in normal (edit) mode (does internal a readonly check by task)
   * @param {Editor.model.admin.Task} task
   */
  handleTaskEdit: function(task) {
      this.openTaskRequest(task);
  },
  
  /**
   * Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  handleTaskFinish: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskFinishing, task.get('taskName'));
      task.set('userState', task.USER_STATE_FINISH);
      task.save(me.getTaskMaskBindings());
  },
  
  /**
   * Un Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  handleTaskUnfinish: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskUnFinishing, task.get('taskName'));
      task.set('userState', task.USER_STATE_OPEN);
      task.save(me.getTaskMaskBindings());
  },
  
  /**
   * Un Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  handleTaskEnd: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskEnding, task.get('taskName'));
      task.set('state', 'end');
      task.save(me.getTaskMaskBindings());
  },
  
  /**
   * Un Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  handleTaskReopen: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskReopen, task.get('taskName'));
      task.set('state', 'open');
      task.save(me.getTaskMaskBindings());
  },
  /**
   * delete the task
   * @param {Editor.model.admin.Task} task
   */
  handleTaskDelete: function(task) {
      var me = this,
          store = task.store,
          app = Editor.app;
      app.mask(me.strings.taskDestroy, task.get('taskName'));
      task.dropped = true; //doing the drop / erase manually
      task.save({
          //prevent default ServerException handling
          preventDefaultHandler: true,
          success: function() {
              store.load();
              app.unmask();
          },
          failure: function(records, op){
              task.reject();
              app.unmask();
              if(op.getError().status == '405') {
                  Editor.MessageBox.addError(me.strings.taskNotDestroyed);
              }
              else {
                  Editor.app.getController('ServerException').handleException(op.error.response);
              }
          }
      });
  },
  /**
   * displays the export menu
   * @param {Editor.model.admin.Task} task
   * @param {Ext.EventObjectImpl} event
   */
  handleTaskShowexportmenu: function(task, event) {
      var me = this,
          hasQm = task.hasQmSub(),
          exportAllowed = me.isAllowed('editorExportTask'),
          menu;
      
      menu = Ext.widget('adminExportMenu', {
          task: task,
          fields: hasQm ? task.segmentFields() : false
      });
      menu.down('#exportItem').setVisible(exportAllowed);
      menu.down('#exportDiffItem').setVisible(exportAllowed);
      menu.showAt(event.getXY());
  },
  
  /**
   * triggerd by click on the Task Preferences Icon
   * fires only an event to allow flexible handling of this click
   * @param {Editor.model.admin.Task} task
   */
  handleTaskPreferences: function(task) {
      this.fireEvent('handleTaskPreferences', task);
  },
  
  /**
   * triggerd by click on the Change Task User Assoc Button / (Cell also => @todo)
   * fires only an event to allow flexible handling of this click
   * @param {Editor.model.admin.Task} task
   */
  handleTaskChangeUserAssoc: function(task) {
      this.fireEvent('handleTaskChangeUserAssoc', task);
  },
  /***
   * starts the upload / form submit
   */
  saveTask:function(){
      var me = this,
          win = me.getTaskAddWindow(),
          error = win.down('#feedbackBtn');
      error.hide();
      win.setLoading(true);
      this.getTaskAddForm().submit({
          //Accept Header of submitted file uploads could not be changed:
          //http://stackoverflow.com/questions/13344082/fileupload-accept-header
          //so use format parameter jsontext here, for jsontext see REST_Controller_Action_Helper_ContextSwitch
          
          params: {
              format: 'jsontext'
          },
          
          url: Editor.data.restpath+'task',
          scope: this,
          success: function(form, submit) {
              var task = me.getModel('admin.Task').create(submit.result.rows);
              me.fireEvent('taskCreated', task);
              win.setLoading(false);
              me.getAdminTasksStore().load();
              me.handleTaskCancel();
              Editor.MessageBox.addSuccess(win.importTaskMessage,2);
          },
          failure: function(form, submit) {
              win.setLoading(false);
              if(submit.failureType == 'server' && submit.result && submit.result.errors && !Ext.isDefined(submit.result.success)) {
                  //all other failures should mark a field invalid
                  error.show();
              }
          }
      });
  }
});
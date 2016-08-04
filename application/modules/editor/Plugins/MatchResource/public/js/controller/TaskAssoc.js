
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
 * @class Editor.plugins.MatchResource.controller.TaskAssoc
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.MatchResource.controller.TaskAssoc', {
  extend : 'Ext.app.Controller',
  views: ['Editor.plugins.MatchResource.view.TaskAssocPanel'],
  models: ['Editor.plugins.MatchResource.model.TaskAssoc'],
  stores:['Editor.plugins.MatchResource.store.TaskAssocStore'],
  strings: {
      assocSave: '#UT#Eintrag gespeichert!',
      assocDeleted: '#UT#Eintrag gelöscht!',
      assocSaveError: '#UT#Fehler beim Speichern der Änderungen!'
  },
  refs: [{
      ref: 'taskTabs',
      selector: 'adminTaskPreferencesWindow > tabpanel'
  },{
      ref: 'grid',
      selector: 'adminTaskPreferencesWindow > tabpanel #tmTaskAssocGrid'
  },{
      ref: 'adminTaskWindow',
      selector: 'adminTaskPreferencesWindow'
  }],
  listen: {
      controller: {
          '#taskpreferences': {
              'loadPreferences': 'handleLoadPreferences'
          }
      },
      component: {
          'adminTaskPreferencesWindow': {
              render: 'onParentRender',
              beforeclose:function(panel,eOpts){
                  var me=this;
                  if(me.requestsCount>0){
                      return false;
                  }
                  panel.setLoading(false);
              }
          },
          'matchResourceTaskAssocPanel #btnSaveChanges': {
              click: 'handleOnSaveButtonClick'
          },
          'matchResourceTaskAssocPanel #btnReload': {
              click: 'handleOnReload'
          }
      }
  },
  requestsCount:0,
  /**
   * inject the plugin tab and load the task meta data set
   */
  onParentRender: function(window) {
      var me = this;
      me.actualTask = window.actualTask;
      me.getTaskTabs().insert(1,{xtype: 'matchResourceTaskAssocPanel', actualTask: me.actualTask});
  },
  handleLoadPreferences: function(controller,task){
      var me = this,
          tmmtparams = {
              params: {
                  filter: '[{"operator":"like","value":"'+task.get('taskGuid')+'","property":"taskGuid"}]'
              }
          };
      me.getGrid().store.removeAll();
      me.getGrid().store.load(tmmtparams);
  },
  handleOnSaveButtonClick: function(window) {
      var me = this,
          tmpStore = me.getGrid().store;
      me.getAdminTaskWindow().setLoading(true);
      me.requestsCount = tmpStore.getCount();
      tmpStore.each(me.saveOneAssocRecord, me);
  },
  handleOnReload: function(window) {
      var me = this;
      me.getGrid().store.reload();
  },
  /**
   * currently no easy "subentity" versioning is possible here, because of the bulk (store each) like saving / deleting.
   * on the other hand no versioning is needed, master entity tmmt does not contain changeable values which affects the taskassoc entity
   * The taskassocs itself can be handled by plain 404 already deleted and duplicate entry messages.
   */
  saveOneAssocRecord: function(record){
      var me = this;
      if(!record.dirty){
          me.requestsCount--;
          me.hideLoadingMask();
          return;
      }
      var str = me.strings,
          checkedData = {
          data: Ext.JSON.encode({
              tmmtId: record.get('id'),
              taskGuid: me.actualTask.get('taskGuid')
          })
      };
      Ext.Ajax.request({
          url:Editor.data.restpath+'plugins_matchresource_taskassoc' + (!record.data.checked ? '/'+record.get('taskassocid'):''),
          method: record.data.checked ? "POST" : "DELETE",
          params: record.data.checked ? checkedData : {} ,
          success: function(response){
              me.requestsCount--;
              if(record.data.checked){
                  var resp = Ext.util.JSON.decode(response.responseText),
                      newId = resp.rows['id'];
                  record.set('taskassocid', newId);
                  Editor.MessageBox.addSuccess(str.assocSave);
              }
              else {
                  Editor.MessageBox.addSuccess(str.assocDeleted);
              }
              record.commit();
              me.hideLoadingMask();
          },
          failure: function(response){
              me.requestsCount--;
              Editor.app.getController('ServerException').handleException(response);
              me.hideLoadingMask();
          } 
      });
  },
  hideLoadingMask:function(){
      var me=this;
      if(me.requestsCount <= 0){
          var task = me.getAdminTaskWindow().actualTask;
          me.getAdminTaskWindow().setLoading(false);
          task.load();
      }
  }
});

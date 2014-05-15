/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
/**
 * Editor.controller.admin.User encapsulates the User Administration functionality
 * @class Editor.controller.admin.User
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.User', {
  extend : 'Ext.app.Controller',
  models: ['admin.User'],
  stores: ['admin.Users'],
  views: ['admin.UserGrid', 'admin.UserAddWindow'],
  refs : [{
      ref: 'headToolBar',
      selector: 'headPanel toolbar#top-menu'
  },{
      ref: 'logoutButton',
      selector: 'headPanel toolbar#top-menu #logoutSingle'
  },{
      ref: 'centerRegion',
      selector: 'viewport container[region="center"]'
  },{
      ref: 'UserForm',
      selector: '#adminUserAddWindow form'
  },{
      ref: 'UserWindow',
      selector: '#adminUserAddWindow'
  },{
      ref: 'userGrid',
      selector: 'adminUserGrid'
  }],
  strings: {
      confirmDeleteTitle: '#UT#Benutzer endgültig löschen?',
      confirmDeleteMsg: '#UT#Soll der gewählte Benutzer "{0}" wirklich endgültig gelöscht werden?',
      confirmResetPwTitle: '#UT#Passwort zurücksetzen?',
      confirmResetPwMsg: '#UT#Soll das Passwort des Benutzers "{0}" wirklick zurückgesetzt werden?<br /> Der Benutzer wird per E-Mail benachrichtigt, dass er ein neues Passwort anfordern muss.',
      userSaved: '#UT#Der Änderungen an Benutzer "{0}" wurden erfolgreich gespeichert.',
      openUserAdminBtn: "#UT#Benutzerverwaltung",
      userAdded: '#UT#Der Benutzer "{0}" wurde erfolgreich erstellt.'
  },
  init : function() {
      var me = this;
      this.addEvents(
              /**
               * @event userCreated
               * @param {Ext.form.Panel} form
               * Fires after a user has successfully created
               */
              'userCreated'
      );
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('adminViewportClosed', me.clearUsers, me);
      Editor.app.on('adminViewportOpened', me.loadUsers, me);
      Editor.app.on('editorViewportOpened', me.handleInitEditor, me);
      
      me.control({
          'headPanel toolbar#top-menu' : {
              beforerender: me.initMainMenu
          },
          'button#user-admin-btn': {
              click: me.openUserGrid
          },
          '#adminUserGrid #reload-user-btn': {
              click: me.handleUserReload
          },
          '#adminUserGrid #add-user-btn': {
              click: me.handleUserAddShow
          },
          '#adminUserAddWindow #save-user-btn': {
              click: me.handleUserSave
          },
          '#adminUserAddWindow #cancel-user-btn': {
              click: me.handleUserCancel
          },
          '#adminUserGrid': {
              hide: me.handleAfterHide,
              show: me.handleAfterShow,
              celldblclick: me.handleUserEdit 
          },
          '#adminUserGrid actioncolumn': {
              click: me.userActionDispatcher
          }
      });
  },
  /**
   * injects the user menu into the main menu
   */
  initMainMenu: function() {
      var toolbar = this.getHeadToolBar(),
          insertIdx = 1,
          logout = this.getLogoutButton();
      if(logout) {
          insertIdx = toolbar.items.indexOf(logout) + 1;
      }
      toolbar.insert(insertIdx, {
          itemId: 'user-admin-btn',
          xtype: 'button',
          text: this.strings.openUserAdminBtn
      });
  },
  /**
   * handle after show of usergrid
   */
  handleAfterShow: function() {
      this.getHeadToolBar().down('#user-admin-btn').hide();
  },
  /**
   * handle after hide of usergrid
   */
  handleAfterHide: function() {
      this.getHeadToolBar().down('#user-admin-btn').show();
  },
  /**
   * opens the task grid, hides all other
   */
  openUserGrid: function() {
      var me = this, 
          grid = me.getUserGrid();
      
      me.getCenterRegion().items.each(function(item){
          item.hide();
      });
      
      if(grid) {
          grid.show();
      }
      else {
          me.getCenterRegion().add({
              xtype: 'adminUserGrid'
          });
          me.handleAfterShow();
      }
  },
  handleInitEditor: function() {
      this.getHeadToolBar().down('#user-admin-btn').hide();
  },
  /**
   * Handles the different user action on the action column
   * @param {Ext.grid.View} view
   * @param {DOMElement} cell
   * @param {Integer} row
   * @param {Integer} col
   * @param {Ext.Event} ev
   * @param {Object} evObj
   */
  userActionDispatcher: function(view, cell, row, col, ev, evObj) {
      var me = this,
          store = view.getStore(),
          user = store.getAt(row),
          t = ev.getTarget(),
          msg = me.strings,
          info,
          taskStore = Ext.StoreMgr.get('admin.Tasks'),
          f = t.className.match(/ico-user-([^ ]+)/);
      
      switch(f && f[1] || '') {
          case 'edit':
              me.handleUserEdit(view,cell,col,user);
              break;
          case 'delete':
              if(!me.isAllowed('editorDeleteUser')) {
                  return;
              }
              info = Ext.String.format(msg.confirmDeleteMsg,user.get('firstName')+' '+user.get('surName'));
              Ext.Msg.confirm(msg.confirmDeleteTitle, info, function(btn){
                  if(btn == 'yes') {
                      user.destroy({
                          success: function() {
                              taskStore && taskStore.load();
                              store.remove(user);
                          }
                      });
                  }
              });
              break;
          case 'reset-pw':
              if(!me.isAllowed('editorResetPwUser')) {
                  return;
              }
              info = Ext.String.format(msg.confirmResetPwMsg,user.get('firstName')+' '+user.get('surName'));
              Ext.Msg.confirm(msg.confirmResetPwTitle, info, function(btn){
                  if(btn == 'yes') {
                     user.set('passwd',null);
                     user.setDirty();//necessary, cause sometimes ext does not transfer the null-value in save
                     user.save();
                  }
              });
              break;
      }
  },
  clearUsers: function() {
      this.getAdminUsersStore().removeAll();
  },
  loadUsers: function() {
      this.getAdminUsersStore().load();
  },
  handleUserCancel: function() {
      this.getUserForm().getForm().reset();
      this.getUserWindow().close();
  },
  /**
   * setting a loading mask for the window / grid is not possible, using savingShow / savingHide instead.
   * perhaps because of bug for ext-4.0.7 (see http://www.sencha.com/forum/showthread.php?157954)
   * This Fix is better as in {Editor.view.changealike.Window} because of useing body as LoadMask el.
   */
  savingShow: function() {
      var win = this.getUserWindow();
      if(!win.loadingMask) {
          win.loadingMask = new Ext.LoadMask(Ext.getBody(), {store: false});
          win.on('destroy', function(){
              win.loadingMask.destroy();
          });
      }
      win.loadingMask.show();
  },
  savingHide: function() {
      var win = this.getUserWindow();
      win.loadingMask.hide();
  },
  /**
   * is called after clicking save user
   */
  handleUserSave: function() {
      var me = this,
          form = me.getUserForm(),
          basic = form.getForm(),
          win = me.getUserWindow(),
          rec = form.getRecord();
      if(!basic.isValid()) {
          return;
      }
      //if in first save attempt we got an error from server, 
      //and we then disable the password in the second save, 
      //the password will be kept in the model, so reject it here
      rec.reject();
      basic.updateRecord(rec);
      
      me.savingShow();
      rec.save({
          //using the callback method here (instead failure & success) disables the default server exception
          callback: function(rec, op) {
              var error,
                  errorHandler = Editor.app.getController('ServerException');
              if(op.success) {
                  return;
              }
              me.savingHide();
              if(op.response && op.response.responseText) {
                  error = Ext.decode(op.response.responseText);
                  if(error.errors && op.error && op.error.status == '400') {
                      basic.markInvalid(error.errors);
                      return;
                  }
              }
              errorHandler.handleCallback.apply(errorHandler, arguments); 
          },
          success: function() {
              var user = rec.get('surName')+', '+rec.get('firstName')+' ('+rec.get('login')+')',
                  msg = win.editMode ? me.strings.userSaved : me.strings.userAdded;
              me.savingHide();
              win.close();
              me.getAdminUsersStore().load();
              Editor.MessageBox.addSuccess(Ext.String.format(msg, user));
          }
      });
  },
  /**
   * shows the form to edit a user
   */
  handleUserEdit: function(view, cell, cellIdx, rec){
      if(!this.isAllowed('editorEditUser')){
          return;
      }
      var win = Ext.widget('adminUserAddWindow',{editMode: true}),
          noEdit = ! rec.get('editable');
      win.loadRecord(rec);
      win.down('form').setDisabled(noEdit);
      win.down('#save-user-btn').setDisabled(noEdit);
      win.show();
  },
  /**
   * shows the form to add a user
   */
  handleUserAddShow: function() {
      if(!this.isAllowed('editorAddUser')){
          return;
      }
      var win = Ext.widget('adminUserAddWindow');
      win.loadRecord(this.getNewUser());
      win.show();
  },
  /**
   * creates a new User Record
   * @returns {Editor.model.admin.User}
   */
  getNewUser: function() {
      return Ext.create('Editor.model.admin.User',{
          surName: '',
          firstName: '',
          email: '',
          login: '',
          gender: 'f',
          roles: 'editor'
      });
  },
  /**
   * Method Shortcut for convenience
   * @param {String} right
   * @return {Boolean}
   */
  isAllowed: function(right) {
      return Editor.app.authenticatedUser.isAllowed(right);
  },
  /**
   * reloads the User Grid, will also be called from other controllers
   */
  handleUserReload: function () {
      this.getAdminUsersStore().load();
  }
});
/*
 * File: app/view/customer/PanelViewModel.js
 *
 * This file was generated by Sencha Architect
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 5.1.x library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 5.1.x. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('Editor.plugins.Customer.view.CustomerViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.customerPanel',

    data: {
        title: '',
        record: false
    },

    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                stores: {
                    customers: {
                        pageSize: 200,
                        autoLoad: true,
                        model: 'Editor.plugins.Customer.model.Customer'
                    }
                }
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
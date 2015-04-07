
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

/**
 * @class Editor.model.ModelOverride
 * @overrides Ext.data.Model
 * extends the default model with the methods: 
 * Editor.model.ModelOverride::reload(config)
 * Editor.model.ModelOverride::destroyVersioned(version, config)
 */
Ext.define('Editor.model.ModelOverride', {
    override: 'Ext.data.Model',
    /**
     * reloads a single model instance
     * @param {Object} config object, same as for load
     * @return {Ext.data.Model}
     */
    reload: function(config) {
        config = Ext.apply({}, config);
        var me = this,
            scope = config.scope || this,
            success = config.success || Ext.emptyFn;
        
        config.success = function(rec) {
            Ext.callback(success, scope, arguments);
            me.set(rec.data);
            me.suspendEvents();
            me.commit();
            me.resumeEvents();
        };
        return me.self.load(me.get(me.idProperty), config);
    },
    /**
     * save method with version check: give the entity or version to be compared against
     * @see TRANSLATE-206 for more information
     * @param {Integer}|{Ext.data.Model} version version number or model with version to be compared against on the server
     * @param {Object} config object, same as for normal destroy
     * @return {Ext.data.Model}
     */
    saveVersioned: function(version, config) {
        var me = this;
        me.set('entityVersion', me.parseVersion(version));
        return me.save(config);
    },
    /**
     * destroy method with version check: give the entity or version to be compared against
     * @see TRANSLATE-206 for more information
     * @param {Integer}|{Ext.data.Model} version version number or model with version to be compared against on the server
     * @param {Object} config object, same as for normal destroy
     * @return {Ext.data.Model}
     */
    destroyVersioned: function(version, config) {
        var me = this,
            p = me.getProxy(),
            result;
        if(! p.headers) {
            p.headers = {};
        }
        version = me.parseVersion(version);
        p.headers['Mqi-Entity-Version'] = version;
        result = me.destroy(config);
        delete p.headers['Mqi-Entity-Version'];
        return result;
    },
    /**
     * returns the version of the given mixed value
     * @param mixed version can be a model or an integer
     * @return {Integer}
     */
    parseVersion: function(version){
        if(Ext.isNumeric(version)) {
            return version;
        }
        if(Ext.isObject(version) && version.isModel && version.get('entityVersion') !== undefined) {
            return version.get('entityVersion');
        }
        Ext.Error.raise('Given version is no integer and no Model, or Model has no entityVersion field!');
    }
});
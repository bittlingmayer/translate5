
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
 * @class Editor.view.segments.column.Comments
 */
Ext.define('Editor.view.segments.column.Comments', {
    extend: 'Ext.grid.column.Column',
    alias: 'widget.commentsColumn',
    mixins: ['Editor.view.segments.column.BaseMixin'],
    dataIndex: 'comments',
    text: '#UT#Kommentare',
    text_morecomments: '#UT#({0} weitere Kommentare)',
    text_morecomment: '#UT#({0} weiterer Kommentar)',
    tdCls: 'comments-field',
    statics: {
        /**
         * Extract the first <div class="comment"> from comments string
         */
        getFirstComment: function(comments) {
            var self = Editor.view.segments.column.Comments.prototype,
                more, text,
                split = comments.split(/<\/div>[^<]*<div class="comment">/);
            if(split.length > 1) {
                text = split.length == 2 ? self.text_morecomment : self.text_morecomments;
                more = Ext.String.format(text, (split.length - 1));
                return split[0] + '<span class="ellipsis"> ... '+more+'</span></div>'; //add stripped end tag and ...
            }
            return split[0];
        }
    },
    renderer: function(val, meta, record) {
        if(!val || val.length == 0) {
            return '<img class="add" src="'+Ext.BLANK_IMAGE_URL+'">';
        }
        var value = Ext.String.htmlEncode(val);
        meta.tdAttr = 'data-qtip="'+value+'"';
        return Editor.view.segments.column.Comments.getFirstComment(val) + '<img class="edit" src="'+Ext.BLANK_IMAGE_URL+'">';
    },
    editor: {
        xtype: 'displayfield',
        getModelData: function() {
            return null;
        },
        listeners: {
            'afterrender': function(field) {
                var initTT = null;
                Ext.widget('button', {
                    iconCls: 'ico-comment-edit',
                    itemId: 'editorCommentBtn', 
                    renderTo: field.getEl()
                });
                if(field.tooltip && Ext.isString(field.tooltip)) {
                    initTT = field.tooltip;
                }
                field.tooltip = Ext.create('Ext.tip.ToolTip', {
                    target: field.getEl(),
                    html: initTT,
                    disabled: !initTT
                });
            }
        }
    },
    initComponent: function() {
        var me = this;
        me.initBaseMixin();
        me.callParent(arguments);
    }
});

/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Mixin with Helpers regarding the (content in the) Segment-Editor.
 * @class Editor.util.SegmentEditor
 */
Ext.define('Editor.util.SegmentEditor', {
    mixins: ['Editor.util.DevelopmentTools'],
    
    editor: null, // = the segment's Editor (Editor.view.segments.HtmlEditor)
    
    // =========================================================================
    // Avoid problems due to not initialized Editor.
    // =========================================================================
    
    /***
     * Use this function to get the editor body.
     * @returns {HTMLBodyElement}
     */
    getEditorBody:function(){
        var me = this;
        if(!me.editor){
            return false;
        }
        if(me.editor.editorBody){
            return me.editor.editorBody;
        }
        return me.editor.getEditorBody();
    },
    /***
     * Use this function to get the editor ext document element.
     * @returns {Ext.dom.Element}
     */
    getEditorBodyExtDomElement:function(){
        var me = this;
        return Ext.get(me.getEditorBody());
    },
    /***
     * Use this function to get the editor HTML-document.
     * @returns {HTMLDocument (#document)}
     */
    getEditorDoc:function(){
        var me = this;
        if(!me.editor){
            return false;
        }
        return me.editor.getDoc();
    },
    
    // =========================================================================
    // Helpers regarding the (content in the) Segment-Editor
    // =========================================================================
    
    /**
     * Returns true if the Segment-Editor is "empty" (= includes nothing but isContainerToIgnore or Selection-Boundary), e.g.:
     * - true: <body><img class="duplicatesavecheck"></body>
     * - true: <body><ins><span id="selectionBoundary"></span></ins><img class="duplicatesavecheck"></body>
     * @returns {Boolean}
     */
    isEmptyEditor: function() {
        var me = this,
            rangeForEditor = rangy.createRange(),
            relevantNodesInEditor;
        rangeForEditor.selectNodeContents(me.getEditorBody());
        relevantNodesInEditor = rangeForEditor.getNodes([1,3], function(node) {
            if (node.nodeType == 1) {
                if (node.nodeValue == null) {
                    return false;
                }
                if (!me.isContainerToIgnore(node)) {
                    return false;
                }
                return true;
            } else {
                // selection-boundary-spans do include #text (data: \ufeff)
                if (node.parentNode != null && me.isContainerToIgnore(node.parentNode)) {
                    return false;
                }
                return node.data != "";
            }
        });
        if (relevantNodesInEditor.length == 0) {
            return true;
        }
        return false;
    },
    /**
     * Adds a placeholder in the Editor if necessary (TRANSLATE-1042).
     */
    addPlaceholderIfEditorIsEmpty: function() {
        var me = this;
        if ( (Ext.isGecko  || Ext.isWebKit) && me.isEmptyEditor()) {
            me.getEditorBody().innerHTML = me.placeholder_empty_INS + me.getEditorBody().innerHTML;
            me.consoleLog("Firefox: Editor must never be empty => Placeholder added.");
        }
    },
    /**
     * E.g. invisible containers in the Editor.
     * @param {Object} node
     * @returns {Boolean} 
     */
    isContainerToIgnore: function(node) {
        if (node.nodeName.toLowerCase() == 'img' && /duplicatesavecheck/.test(node.className)) {
            return true;
        }
        if (node.nodeName.toLowerCase() == 'span' && /rangySelectionBoundary/.test(node.className)) {
            return true;
        }
        return false;
    },
    /**
     * Returns the first/last node in the editor that is not of the kind to be ignored.
     * @param {String} direction
     * @returns {Boolean} 
     */
    getLastRelevantNodeInEditor: function(direction) {
        var me = this,
            node = (direction == 'fromEnd') ? me.getEditorBody().lastChild : me.getEditorBody().firstChild;
        while (node) {
            if (!me.isContainerToIgnore(node)) {
                return node;
            }
            node = (direction == 'fromEnd') ? node.previousSibling : node.nextSibling;
        }
        return null;
    },
    
    /**
     * Get the TermTag-Node for a node (= the node itself or loop its parents until we find it).
     * @param {Object} node
     * @returns {?Object} termTag-node
     */
    getTermTagNodeOfNode: function(node){
        var me = this;
        while (node) {
            if (/term/.test(node.className)) {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    },
    /**
     * Collects all kinds of partner-Tags we need to check against.
     * @param {Object} node
     * @returns {?Object} node
     */ 
    getPartnerTag: function(node) {
        var me = this,
            partnerTag = null;
        if (node.nodeType == 3) {
            return null;
        }
        // (can only match one type of partner-Tag)
        partnerTag = me.getMQMPartnerTag(node);            // MQM-Tags
        if (partnerTag == null) {
            partnerTag = me.getContentPartnerTag(node);    // Content-Tags
        }
        return partnerTag;
    },
    /**
     * Checks if an img is an MQM-tag and returns its partner-tag (if there is one).
     * @param {Object} mqmImgNode
     * @returns {?Object} imgNode
     */ 
    getMQMPartnerTag: function(mqmImgNode) {
        var me = this,
            imgInEditorTotal,
            imgOnCheck,
            i,
            arrLength;
        if (Ext.fly(mqmImgNode).hasCls('qmflag') && mqmImgNode.hasAttribute('data-seq')) {
            imgInEditorTotal = me.getEditorDoc().images;
            arrLength = imgInEditorTotal.length;
            for(i = 0; i < arrLength; i++){
                imgOnCheck = imgInEditorTotal[i];
                if (Ext.fly(imgOnCheck).hasCls('qmflag')
                        && imgOnCheck.hasAttribute('data-seq')
                        && (imgOnCheck.getAttribute('data-seq') == mqmImgNode.getAttribute('data-seq') )
                        && (imgOnCheck.id != mqmImgNode.id ) ) {
                    return imgOnCheck;
                }
            }
        }
        return null;
    },
    /**
     * Checks if an img is a Content-tag (<i>, <b>, ...) and returns its partner-tag (if there is one).
     * @param {Object} contentTagImgNode
     * @returns {?Object} imgNode
     */ 
    getContentPartnerTag: function(contentTagImgNode) {
        var me = this,
            idPrefix = me.editor.idPrefix, // s. Editor.view.segments.HtmlEditor
            contentTagImgClass = contentTagImgNode.className,
            contentTagImgId = contentTagImgNode.id,
            partnerTagImgId = null;
        if (Ext.String.startsWith(contentTagImgId, idPrefix)) {
            // "Toggle" id
            switch(true){
              case /open/.test(contentTagImgClass):
                  partnerTagImgId = contentTagImgId.replace('open', 'close');
                break;
              case /close/.test(contentTagImgClass):
                  partnerTagImgId = contentTagImgId.replace('close', 'open');
                break;
            }
            if(partnerTagImgId != null) {
                return me.getEditorDoc().getElementById(partnerTagImgId); // getElementById() returns null if it doesn't exist
            }
        };
        return null;
    },
    /***
     * Remove SpellCheck-Tags "live" in the Editor but keep their content.
     */
    cleanSpellCheckTagsInEditor:function(){
        var me = this,
            el = me.getEditorBodyExtDomElement(),
            allSpellCheckElements,
            spellCheckElementParentNode;
        allSpellCheckElements = el.query('.spellcheck');
        Ext.Array.each(allSpellCheckElements, function(spellCheckEl, index) {
            spellCheckElementParentNode = spellCheckEl.parentNode;
            while(spellCheckEl.firstChild) {
                spellCheckElementParentNode.insertBefore(spellCheckEl.firstChild, spellCheckEl);
            }
            spellCheckElementParentNode.removeChild(spellCheckEl);
            spellCheckElementParentNode.normalize();
        });
    }
});
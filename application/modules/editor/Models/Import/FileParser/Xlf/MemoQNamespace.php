<?php
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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * XLF Fileparser Add On to parse MemoQ XLF specific stuff
 */
class editor_Models_Import_FileParser_Xlf_MemoQNamespace extends editor_Models_Import_FileParser_Xlf_AbstractNamespace{
    const MEMOQ_XLIFF_NAMESPACE = 'xmlns:mq="MQXliff"';
    const USERGUID = 'memoq-imported';

    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_AbstractNamespace::registerParserHandler()
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser) {
        $memoqMqmTag = 'trans-unit > target > mrk[mtype=x-mq-range], ';
        $memoqMqmTag .= 'trans-unit > source > mrk[mtype=x-mq-range], ';
        $memoqMqmTag .= 'trans-unit > seg-source > mrk[mtype=x-mq-range]';
        $xmlparser->registerElement($memoqMqmTag, function($tag, $attributes, $key) use ($xmlparser) {
            $xmlparser->replaceChunk($key, '');
        }, function($tag, $key, $opener) use ($xmlparser){
            $xmlparser->replaceChunk($key, '');
        });

        $xmlparser->registerElement('trans-unit mq:comments mq:comment', null, function($tag, $key, $opener) use ($xmlparser) {
            $attr = $opener['attributes'];

            //if the comment is marked as deleted, we just do not import it
            if(!empty($attr['deleted']) && strtolower($attr['deleted']) == 'true') {
                return;
            }

            $comment = ZfExtended_Factory::get('editor_Models_Comment');
            /* @var $comment editor_Models_Comment */

            $startText = $opener['openerKey'] + 1;
            $length = $key - $startText;
            $commentText = htmlspecialchars(join($xmlparser->getChunks($startText, $length)));

            $suffix = [];
            if(!empty($attr['appliesto']) && $attr['appliesto'] != 'Target') {
                $suffix[] = 'annotates '.$attr['appliesto'].' column';
            }
            if(!empty($attr['origin']) && $attr['origin'] != 'User') {
                $suffix[] = 'from '.$attr['origin'];
            }
            if(!empty($suffix)) {
                $commentText .= "\n (".join('; ',$suffix).')';
            }

            $comment->setComment($commentText);

            $comment->setUserName($attr['creatoruser'] ?? 'no user');
            $comment->setUserGuid(self::USERGUID);

            $date = date(DATE_ISO8601, strtotime($attr['creatoruser'] ?? 'now'));
            $comment->setCreated($date);
            $comment->setModified($date);
            $this->comments[] = $comment;
        });
    }

    /**
     * Translate5 uses x,g and bx ex tags only. So the whole content of the tags incl. the tags must be used.
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_AbstractNamespace::useTagContentOnly()
     */
    public function useTagContentOnly() {
        return false;
    }

    /**
     * After fetching the comments, the internal comments fetcher is resetted (if comments are inside MRKs and not the whole segment)
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_AbstractNamespace::getComments()
     */
    public function getComments() {
        $comments = $this->comments;
        $this->comments = [];
        return $comments;
    }
}

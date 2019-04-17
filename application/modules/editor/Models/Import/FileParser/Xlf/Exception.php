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
/**
 */
class editor_Models_Import_FileParser_Xlf_Exception extends editor_Models_Import_FileParser_Exception {
    /**
     * @var string
     */
    protected $domain = 'editor.import.fileparser.xlf';
    
    static protected $localErrorCodes = [
        'E1067' => 'MRK/SUB tag of source not found in target with Mid: "{mid}"',
        'E1068' => 'MRK/SUB tag of target not found in source with Mid(s): "{mids}"',
        'E1069' => 'There is other content as whitespace outside of the mrk tags. Found content: {content}',
        'E1070' => 'SUB tag of {field} is not unique due missing ID in the parent node and is ignored as separate segment therefore.',
        'E1071' => 'MRK tag of {field} has no MID attribute.',
    ];
}

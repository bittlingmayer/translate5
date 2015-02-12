--  /*
--  START LICENSE AND COPYRIGHT
--  
--  This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
--  
--  Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU General Public License version 3.0
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU General Public License version 3.0 requirements will be met:
--  http://www.gnu.org/copyleft/gpl.html.
-- 
--  For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
--  General Public License version 3.0 as specified by Sencha for Ext Js. 
--  Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
--  that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
--  For further information regarding this topic please see the attached license.txt
--  of this software package.
--  
--  MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
--  brought in accordance with the ExtJs license scheme. You are welcome to support us
--  with legal support, if you are interested in this.
--  
--  
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
--              with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
--  
--  END LICENSE AND COPYRIGHT 
--  */
-- 

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES 
('runtimeOptions.termTagger.url.default', 1, 'plugin', 'termtagger', '', '', '', 'list', 'List of available TermTagger-URLs. At least one available URL must be defined. Example: ["http://localhost:9000"]'),
('runtimeOptions.termTagger.url.import', 1, 'plugin', 'termtagger', '', '', '', 'list', 'Optional list of TermTagger-URL to use for task-import processing. Fallback is list runtimeOptions.termTagger.url.default. Example: ["http://localhost:9000"]'),
('runtimeOptions.termTagger.url.gui', 1, 'plugin', 'termtagger', '', '', '', 'list', 'Optional list of TermTagger-URL to use for gui-response processing. Fallback is list runtimeOptions.termTagger.url.default. Example: ["http://localhost:9000"]'),
('runtimeOptions.termTagger.segmentsPerCall', 1, 'plugin', 'termtagger', '20', '20', '', 'integer', 'Maximal number of segments the TermTagger will process in one step'),
('runtimeOptions.termTagger.timeOut.tbxParsing', 1, 'plugin', 'termtagger', '120', '120', '', 'integer', 'connection timeout when parsing tbx'),
('runtimeOptions.termTagger.timeOut.segmentTagging', 1, 'plugin', 'termtagger', '60', '60', '', 'integer', 'connection timeout when tagging segments');
UPDATE  `Zf_configuration` SET  `default` =  '500',`value` =  '500' WHERE  `Zf_configuration`.`name` ='runtimeOptions.termTagger.timeOut.tbxParsing';
UPDATE  `Zf_configuration` SET  `default` =  '500',`value` =  '500' WHERE  `Zf_configuration`.`name` ='runtimeOptions.termTagger.timeOut.segmentTagging';
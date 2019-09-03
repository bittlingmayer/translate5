-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--   
--  There is a plugin exception available for use with this release of translate5 for
--  translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */


INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
VALUES ('editor', 'editor', 'frontend', 'pluginNecTm'),
('editor', 'admin', 'frontend', 'pluginNecTm'),
('editor', 'pm', 'frontend', 'pluginNecTm');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.NecTm.server', 1, 'editor', 'plugins', '[]', '[]', '', 'list', 'NEC-TM Api Server; format: ["SCHEME://HOST:PORT"]'),
('runtimeOptions.plugins.NecTm.credentials', 1, 'editor', 'editor', '[]', '[]', '', 'list', 'Credentials (licenses) to the NEC-TM API; format: ["username:password"]'),
('runtimeOptions.plugins.NecTm.topLevelTags', 1, 'editor', 'editor', '[]', '[]', '', 'list', 'Only TM data below the top-level tags can be accessed (plus all public data). Example: ["Health", "Automotive", "Madrid"]');

CREATE TABLE `LEK_languageresources_tag_assoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) DEFAULT NULL,
  `tagId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_LEK_languageresources_tag_assoc_1`
    FOREIGN KEY (`languageResourceId`)
    REFERENCES `LEK_languageresources` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_LEK_languageresources_tag_assoc_2`
    FOREIGN KEY (`tagId`)
    REFERENCES `LEK_tags` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

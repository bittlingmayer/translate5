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
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or 
--  plugin-exception.txt in the root folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

INSERT INTO Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES 
('editor', 'termProposer', 'termCustomerSearch', 'all'),
('editor', 'termProposer', 'editor_termportal', 'all'),
('editor', 'termProposer', 'editor_apps', 'all'),
('editor', 'termCustomerSearch', 'editor_term', 'get'),
('editor', 'termProposer', 'editor_term', 'post'),
('editor', 'termProposer', 'editor_term', 'proposeOperation'),
('editor', 'termProposer', 'editor_term', 'removeproposalOperation'),
('editor', 'termCustomerSearch', 'editor_termattribute', 'get'),
('editor', 'termProposer', 'editor_termattribute', 'post'),
('editor', 'termProposer', 'editor_termattribute', 'proposeOperation'),
('editor', 'termProposer', 'editor_termattribute', 'removeproposalOperation');

-- insert other rules for already existing operations.
INSERT INTO Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES 
('editor', 'pm', 'editor_task', 'analysisOperation'),
('editor', 'pm', 'editor_task', 'pretranslationOperation'),
('editor', 'pm', 'setaclrole', 'termProposer'),
('editor', 'admin', 'setaclrole', 'termProposer'),
('editor', 'admin', 'setaclrole', 'instantTranslate'),
('editor', 'admin', 'setaclrole', 'termCustomerSearch');
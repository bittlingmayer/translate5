# Change Log
All notable changes to translate5 will be documented here.
For a reference to the issue keys see http://jira.translate5.net
Missing Versions are merged into in the next upper versions, so no extra section is needed.

## [2.6.20] - 2018-01-18
###Bugfixes
TRANSLATE-1097: Current release produces SQL error on installation

## [2.6.18] - 2018-01-17
###Added
TRANSLATE-950: Implement a user hierarchy for user listing and editing
TRANSLATE-1089: Create segment history entry when set autostatus untouched, auto-set and reset username on unfinish
TRANSLATE-1099: Exclude framing internal tags from xliff import
TRANSLATE-941: New front-end rights
TRANSLATE-942: New task attributes tab in task properties window
TRANSLATE-1090: A user without setaclrole for a specific role can revoke such already granted roles

###Changed
Integrate segmentation rules for EN in Okapi default bconf-file
TRANSLATE-1091: Rename "language" field/column in user grid / user add window

###Bugfixes
TRANSLATE-1101: Using Translate5 in internet explorer leads sometimes to logouts while application load
TRANSLATE-1086: Leave visualReview task leads to error in IE 11
T5DEV-219: Subsegment img found on saving some segments with tags and enabled track changes

## [2.6.16] - 2017-12-14
###Changed
TRANSLATE-1084: refactor internal translation mechanism

###Bugfixes
several smaller issues

## [2.6.14] - 2017-12-11
###Added
TRANSLATE-1061: Add user locale dropdown to user add and edit window

###Bugfixes
TRANSLATE-1081: Using a taskGuid filter on /editor/task does not work for non PM users
TRANSLATE-1077: Segment editing in IE 11 does not work

## [2.6.13] - 2017-12-07
###Added
TRANSLATE-822: segment min and max length - activated in Frontend
TRANSLATE-869: Okapi integration - improved tikal logging

## [2.6.12] - 2017-12-06
###Added
TRANSLATE-1074: Editor-only mode: On opening finished task: Open in read-only mode

###Changed
TRANSLATE-1055: Disable therootcause feedback button
TRANSLATE-1073: Update configured languages.
TRANSLATE-1072: Set default GUI language for users to EN

###Bugfixes
visualReview: fixes for translate5 embedded editor usage and RTL fixes

## [2.6.11] - 2017-11-30
###Added
TRANSLATE-935: Configure columns of task overview on system level

###Changed
TRANSLATE-905: Improve formatting of the maintenance mode message and add timezone to the timestamp.

###Bugfixes
T5DEV-198: Fixes for the non public VisualReview Plug-In
TRANSLATE-1063: VisualReview Plug-In: missing CSS for internal tags and to much line breaks
TRANSLATE-1053: Repetition editor starts over tag check dialog on overtaking segments from MatchResource

## [2.6.10] - 2017-11-14
###Added
TRANSLATE-931: Tag check can NOT be skipped in case of error
TRANSLATE-822: segment min and max length
TRANSLATE-1027: Add translation step in workflow

###Changed
Bundled OpenTM2 Installer 1.4.1.2 

###Bugfixes
TRANSLATE-1001: Tag check does not work for translation tasks
TRANSLATE-1037: VisualReview and feedback button are overlaying each other
TRANSLATE-763: SDLXLIFF imports no segments with empty target tags
TRANSLATE-1051: Internal XLIFF reader for internal application translation can not deal with single tags

## [2.6.4] - 2017-10-19
###Added
TRANSLATE-944: Import and Export comments from Across Xliff
TRANSLATE-1013: Improve embedded translate5 usage by a static link
T5DEV-161: Non public VisualReview Plug-In

###Changed
TRANSLATE-1028: Correct wrong or misleading language shortcuts

## [2.6.2] - 2017-10-16
###Added
TRANSLATE-869: Okapi integration for source file format conversion
TRANSLATE-995: Import files with generic XML suffix with auto type detection
TRANSLATE-994: Support RTL languages in the editor

###Changed
TRANSLATE-1012: Improve REST API on task creation
TRANSLATE-1004: Enhance text description for task grid column to show task type

###Bugfixes
TRANSLATE-1011: XLIFF Import can not deal with internal unicodePrivateUseArea tags
TRANSLATE-1015: Reference Files are not attached to tasks
TRANSLATE-983: More tags in OpenTM2 answer than in translate5 segment lead to error
TRANSLATE-972: translate5 does not check, if there are relevant files in the import zip

## [2.6.1] - 2017-09-14
###Added
TRANSLATE-994: Support RTL languages in the editor (must be set in LEK_languages)
TRANSLATE-974: Save all segments of a task to a TM

###Changed
TRANSLATE-925: support xliff 1.2 as import format - improve fileparser to file extension mapping
TRANSLATE-926: ExtJS 6.2 update
TRANSLATE-972: translate5 does not check, if there are relevant files in the import zip
TRANSLATE-981: User inserts content copied from rich text wordprocessing tool

###Bugfixes
TRANSLATE-984: The editor converts single quotes to the corresponding HTML entity
TRANSLATE-997: Reset password works only once without reloading the user data
TRANSLATE-915: JS Error: response is undefined

## [2.5.35] - 2017-08-17
###Changed
TRANSLATE-957: XLF Import: Different tag numbering on tags swapped position from source to target
TRANSLATE-955: XLF Import: Whitespace import in XLF documents

###Bugfixes
TRANSLATE-937: translate untranslated GUI elements
TRANSLATE-925: XLF Import: support xliff 1.2 as import format - several smaller fixes
TRANSLATE-971: XLF Import: Importing an XLF with comments produces an error
TRANSLATE-968: XLF Import: Ignore CDATA blocks in the Import XMLParser
TRANSLATE-967: SDLXLIFF segment attributes could not be parsed
MITTAGQI-42: Changes.xliff filename was invalid under windows and minor issue in error logging
TRANSLATE-960: Trying to delete a task user assoc entry produces an exception

## [2.5.34] - 2017-08-07
###Added
TRANSLATE-925: support xliff 1.2 as import format

###Changed
T5DEV-172: (Ext 6.2 update prework) Quicktip manager instances have problems if configured targets does not exist anymore
T5DEV-171: (Ext 6.2 update prework) Get Controller instance getController works only with full classname

###Bugfixes
TRANSLATE-953: Direct Workers (like GUI TermTagging) are using the wrong worker state

## [2.5.33] - 2017-07-11
###Changed
TRANSLATE-628: Log changed terminology in changes xliff

###Bugfixes
TRANSLATE-921: Saving ChangeAlikes reaches PHP max_input_vars limit with a very high repetition count
TRANSLATE-922: Segment timestamp updates only on the first save of a segment

## [2.5.32] - 2017-07-04
###Changed
TRANSLATE-911: Workflow Notification mails could be too large for underlying mail system
TRANSLATE-906: translation bug: "Mehr Info" in EN
TRANSLATE-909: Editor window - change column title "Target text(zur Importzeit)"
TRANSLATE-894: Copy source to target – FIX
TRANSLATE-907: Rename QM-Subsegments to MQM in the GUI
TRANSLATE-818: internal tag replace id usage with data-origid and data-filename - additional migration script
TRANSLATE-895: Copy individual tags from source to target - ToolTip
TRANSLATE-885: fill non-editable target for translation tasks - compare targetHash to history
small fix for empty match rate tooltips showing "null"

## [2.5.31] - 2017-06-23
###Changed
TRANSLATE-882: Switch default match resource color from red to a nice green

###Bugfixes
TRANSLATE-845: Calling task export on task without segment view produces an error (with enabled SegmentStatistics Plugin)
TRANSLATE-904: json syntax error in match resource plugin
Multiple minor changes/fixes (code comment changes, missing tooltip) 

## [2.5.30] - 2017-06-13
###Added
TRANSLATE-885: fill non-editable target for translation tasks
TRANSLATE-894: Copy source to target
TRANSLATE-895: Copy individual tags from source to target
TRANSLATE-901: GUI task creation wizard
TRANSLATE-902: Pretranslation with Globalese Machine Translation

###Changed
TRANSLATE-296: Harmonize whitespace and unicode special chars protection throughout the import file formats
TRANSLATE-896: Restructure editor menu

## [2.5.27] - 2017-05-29
###Added
TRANSLATE-871: New Tooltip shows segment meta data over segmentNrInTask column
TRANSLATE-878: Enable GUI JS logger TheRootCause
TRANSLATE-877: Make Worker URL separately configurable

###Changed
TRANSLATE-823: ignore sdlxliff bookmarks for relais import check
TRANSLATE-870: Enable MatchRate and Relays column per default in ergonomic mode
TRANSLATE-857: change target column names in the segment grid
TRANSLATE-880: XLF import: Copy source to target, if target is empty or does not exist
TRANSLATE-897: changes.xliff generation: alt-trans shorttext for target columns must be changed

###Bugfixes
TRANSLATE-875: Width of relays column is too small
TRANSLATE-891: OpenTM2 answer with Unicode characters and internal tags produces invalid HTML in answer
TRANSLATE-888: Mask tab character in source files with internal tag
TRANSLATE-879: SDLXliff and XLF import does not work with missing target tags

## [2.5.26] - 2017-04-24
###Added
TRANSLATE-871: New Tooltip should show segment meta data over segmentNrInTask column

###Changed
TRANSLATE-823: ignore sdlxliff bookmarks for relais import check
TRANSLATE-870: Enable MatchRate and Relais column per default in ergonomic mode

###Bugfixes
TRANSLATE-875: Width of relais column is too small

## [2.5.25] - 2017-04-06
###Changed
MITTAGQI-36: Add new license plug-in exception

## [2.5.24] - 2017-04-05
###Bugfixes
TRANSLATE-850: Task can not be closed when user was logged out in the meantime

## [2.5.23] - 2017-04-05
###Changed
Included OpenTM2 Community Edition updated to Version 1.3.4.2

## [2.5.22] - 2017-04-05
###Changed
TRANSLATE-854: Change font-size in ergo-mode to 13pt

###Bugfixes
TRANSLATE-849: wrong usage of findRecord in frontend leads to wired errors
TRANSLATE-853: installer fails with "-" in database name

## [2.5.14] - 2017-03-30
###Added
TRANSLATE-807: Change default editor mode to ergonomic mode
TRANSLATE-796: Enhance concordance search
TRANSLATE-826: Show only a maximum of MessageBox messages
TRANSLATE-821: Switch translate5 to Triton theme
TRANSLATE-502: OpenTM2-Integration into MatchResource Plug-In

###Changed
TRANSLATE-820: Generalization of Languages model
TRANSLATE-818: internal tag replace id usage with data-origid and data-filename
MITTAGQI-30: Update license informations

###Bugfixes
TRANSLATE-833: Add application locale to the configurable Help URL
TRANSLATE-839: Ensure right character set of DB import with importer
TRANSLATE-844: roweditor minimizes its height
TRANSLATE-758: DbUpdater under Windows can not deal with DB Passwords with special characters
TRANSLATE-805: show match type tooltip also in row editor

## [2.5.9] - 2017-01-23
###Bugfixes
fixing an installer issue with already existing tables while installation
TRANSLATE-783: Indentation of fields

## [2.5.7] - 2017-01-19
###Bugfixes
TRANSLATE-767: Changealike Window title was always in german
TRANSLATE-787: Translate5 editor does not start anymore - on all installed instances
TRANSLATE-782: Change text in task creation pop-up
TRANSLATE-781: different white space inside of internal tags leads to failures in relais import
TRANSLATE-780: id column of LEK_browser_log must not be NULL
TRANSLATE-768: Db Updater complains about Zf_worker_dependencies is missing

## [2.5.6] - 2016-11-04
###Changed
Content changes in the pages surround the editor

###Bugfixes
TRANSLATE-758: DbUpdater under Windows can not deal with DB Passwords with special characters
TRANSLATE-761: Task must be reloaded when switching from state import to open

## [2.5.2] - 2016-10-26
###Added
TRANSLATE-726: New Column "type" in ChangeLog Plugin
TRANSLATE-743: Implement filters in change-log grid

###Changed
improved worker exception logging
TRANSLATE-759: Introduce config switch to set application language instead of browser recognition
TRANSLATE-751: Updater must check for invalid DB settings
TRANSLATE-612: User-Authentication via API - enable session deletion, login counter
TRANSLATE-644: enable editor-only usage in translate5 - enable direct task association
TRANSLATE-750: Make API auth default locale configurable

###Bugfixes
TRANSLATE-760: The source and target columns are missing sometimes after import for non PM users
TRANSNET-10: Login and passwd reset page must be also in english
TRANSLATE-684: Introduce match-type column - fixing tests
TRANSLATE-745: double tooltip on columns with icon in taskoverview
TRANSLATE-749: session->locale sollte an dieser Stelle bereits durch LoginController gesetzt sein
TRANSLATE-753: change-log-window is not translated on initial show

## [2.5.1] - 2016-09-27
###Added
TRANSLATE-637: Inform users about new features
TRANSLATE-137: Maintenance Mode
TRANSLATE-680: Automatic substituations of tags for repetitions
TRANSLATE-612: User-Authentication via API
TRANSLATE-664: Integrate separate help area in translate5 editor
TRANSLATE-684: Introduce match-type column
TRANSLATE-644: enable editor-only usage in translate5
TRANSLATE-718: Introduce a config switch to disable comment export (default is to enable export)
TRANSLATE-625: Switch Task-Import and -export to worker-architecture
TRANSLATE-621: Implement task status "error"

###Changed
TRANSLATE-646: search for "füll" is finding the attribute-value "full", that is contained in every internal tag
TRANSLATE-750: Make API auth default locale configurable

###Bugfixes
TRANSLATE-725: Filtering status column in task overview throws error
TRANSLATE-727: Filtering source language column in task overview throws an error
TRANSLATE-728: Missing column title for match resource column
several
TRANSLATE-715: Fix MQM short cut labels
TRANSLATE-749: session locale fix


## [2.4.16] - 2016-08-04
### Added
TRANSLATE-711: Check-Script for translate-683
enable application zip override on commandline

### Changed
TRANSLATE-710: change generated dates in changes.xliff to DateTime::ATOM format
TRANSLATE-705: Single click leads to opening of segment
TRANSLATE-712: remote sorting not working in task and user grid
TRANSLATE-713: JS Error when opening segments with terminology with unknown term status

### Changed - for supporters only
TRANSLATE-421: Display TM-Assoc in task-Overview panel & Task-Assoc in TM-Overview panel
               Only available for supporters of the crowdfunding until the crowdfunding is fully financed,
               see https://www.startnext.com/joined-os-translation-system


## [2.4.14] - 2016-07-27
### Added
TRANSLATE-707: Export comments to sdlxliff
TRANSLATE-684: adding a matchRateType column
translate5 Plugins: added support for translations, public files and php controllers

### Added - for supporters only
TRANSLATE-421: translate5 connects and uses results from third party TM (openTM2) and MT (Moses MT) resources
               Only available for supporters of the crowdfunding until the crowdfunding is fully financed,
               see https://www.startnext.com/joined-os-translation-system

### Changed
TRANSLATE-706: Check during relais import, if source of relais file is identical to source of to be translated file
TRANSLATE-689: Files containing empty segments cannot be imported in Transit
TRANSLATE-701: remove legacy content tag export code
TRANSLATE-700: Move regexInternalTags from config to class constant 


## [2.4.9] - 2016-06-02
### Changed
TRANSLATE-678: Diff Export is destroying entities in certain rare cases
TRANSLATE-670: Keyboard short cut collision under windows: 
                CTRL + ALT + DIGIT changed to ALT + S and then DIGIT
                CTRL + ALT + C changed ALT + C
TRANSLATE-631: ExtJS6 Update, fixes (related to keyboard shortcuts)
TRANSLATE-682: translate5 export contains closing div tags from termtagger: PORTAL-88
TRANSLATE-683: repetition editor changes the source, even if it is non-editable
TRANSLATE-686: Autostate calculation in ChangeAlike handling is wrong in some circumstances


## [2.4.8] - 2016-05-06
### Added
integrate crowdfunding success in frontpage

### Changed
TRANSLATE-631: ExtJS6 Update, fixes (IE-warnmessage, errors related to shortcut CTRL-G)


## [2.4.7] - 2016-04-25
### Added
TRANSLATE-679: Notify User about outdated browser

### Changed
TRANSLATE-631: ExtJS6 Update, fixes
TRANSLATE-668: Termtagger config GUI repaired
TRANSLATE-671: Improve filemap performance (memory peak on huge tasks)


## [2.4.6] - 2016-04-06
### Changed
TRANSLATE-631: ExtJs 6 Update, several fixes

## [2.4.5] - 2016-03-17
### Added
TRANSLATE-586: Allow user only to add MQM-tags, but not to edit the content
TRANSLATE-631: ExtJs 6 Update, including new features:
  - decoupling segment editor from the grid
  - segment editor can be moved vertically
  - the opened segment stays open, regardless where the other segments are scrolled
  - Navi Button to scroll back to the opened segment
TRANSLATE-598: Show count of filtered segments in GUI

### Changed
TRANSLATE-659: Multiple tags of the same type are producing DomQuery warnings
TRANSLATE-218: enable MQM for empty strings / missing content
TRANSLATE-578: Change MQM-syntax in exported CSV
TRANSLATE-622: Change order of the save and cancel button in the meta panel
TRANSLATE-654: Improve tag protection and regex-based protection in CSV files
TRANSLATE-653: Stop import, if TBX-file is given but does not contain entries for one of the selected languages

## [2.3.103] - 2016-02-04
### Added
TRANSLATE-576: Added Keyboard shortcuts for most common actions
TRANSLATE-216: Introduced a user specific segment watch-list
TRANSLATE-641: Revert segment to initial version
TRANSLATE-653: Stopping import, if given TBX-file does not contain entries for one of the selected languages
TRANSLATE-635: lock segments in translate5 that are locked in original bilingual system
TRANSLATE-640: make maxParallelProcesses for all other worker types configurable
TRANSLATE-627: Make configurable, if unfiltered statistic file is generated or not
TRANSLATE-620: add columns for number of chars and lines per file to statistics

### Changed
TRANSLATE-652: transNotDefined in XliffTermTagger-Responses leads to duplicate CSS-class definitions
TRANSLATE-655: Fixed sql-error in Installer on sql-import of new installation from the scratch
TRANSLATE-650: switch XliffTermTagger version checking to new version output
TRANSLATE-648: MQM-Shortcut-Hint does not show correct shortcuts
TRANSLATE-594: Fixed entity encode on import and decode on export of CSV files
TRANSLATE-624: don't copy icons in terminology portlet of editor

## [2.3.102] - 2015-12-09
### Added
TRANSLATE-614: JS-based serverside Log of Browser-Version of the user
TRANSLATE-619: Import statistics: configurable value for generating statistic tables for single language pairs

### Changed
TRANSLATE-611: Fixed Error-Message "Terme"
TRANSLATE-610: Enhance Error-Message on tag error in editor
TRANSLATE-615: Repetition editor sets wrong autostate for unchanged source match with different target content
TRANSLATE-609: Improve error message on receiving a termtagger error while loading TBX
TRANSLATE-608: Internal space tag is not reconverted in changes.xml
TRANSLATE-607: DB Deadlock on taskUserAssoc clean up
TRANSLATE-604: Termtagger errors when importing already imported taskGuid
improve striptermtags error output
TRANSLATE-623: Change segment grid column order
TRANSLATE-622: Change order of the save and cancel button in the meta panel
TRANSLATE-598: Show count of filtered segments in GUI



For formatting of this file see http://keepachangelog.com/

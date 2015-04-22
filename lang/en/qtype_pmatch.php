<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * OU pmatch question type language strings.
 *
 * @package   qtype_pmatch
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['addmoreanswerblanks'] = 'Blanks for {no} More Answers';
$string['addmoresynonymblanks'] = 'Blanks for {no} more synonyms';
$string['allowsubscript'] = 'Allow use of subscript';
$string['allowsuperscript'] = 'Allow use of superscript';
$string['answeringoptions'] = 'Options For Entering Answers';
$string['answeroptions'] = 'Answer options';
$string['anyotheranswer'] = 'Any other answer';
$string['applydictionarycheck'] = 'Check spelling of student';
$string['answer'] = 'Answer: {$a}';
$string['answermustbegiven'] = 'You must enter an answer if there is a grade or feedback.';
$string['answerno'] = 'Answer {$a}';
$string['caseno'] = 'No, case is unimportant';
$string['casesensitive'] = 'Case sensitivity';
$string['caseyes'] = 'Yes, case must match';
$string['combinedcontrolnamepmatch'] = 'text input';
$string['converttospace'] = 'Convert the following characters to a space';
$string['correctanswers'] = 'Correct answers';
$string['errors'] = 'Please fix the following problems : {$a}';
$string['env_dictmissing'] = 'Missing spell check dictionary {$a->langforspellchecker} for installed language {$a->humanfriendlylang} is installed.';
$string['env_dictmissing2'] = 'Student attempted a spell check in language \'{$a}\'. But aspell dictionary for this language is not installed.';
$string['env_dictok'] = 'Spell check dictionary {$a->langforspellchecker} for installed language {$a->humanfriendlylang} is installed.';
$string['env_peclnormalisationmissing'] = 'PECL Package for Unicode Normalizer appears not to be correctly installed';
$string['env_peclnormalisationok'] = 'PECL Package for Unicode Normalizer appears to be correctly installed';
$string['env_pspellmissing'] = 'Pspell library appears not to be correctly installed';
$string['env_pspellok'] = 'Pspell library appears to be correctly installed';
$string['environmentcheck'] = 'Environment checks for the pmatch question type';
$string['err_providepmatchexpression'] = 'You must provide a pmatch expression here.';
$string['extenddictionary'] = 'Add these words to dictionary';
$string['filloutoneanswer'] = 'Use Pattern match syntax to describe correct answers. You must provide at least one possible answer. Answers left blank will not be used. The first matching answer will be used to determine the score and feedback.';
$string['forcelength'] = 'If answer is more than 20 words';
$string['forcelengthno'] = 'do not issue warning';
$string['forcelengthyes'] = 'warn that answer is too long and invite respondee to shorten it';
$string['ie_nomatchfound'] = 'Error in pattern match code.';
$string['ie_nofullstop'] = 'Full stop characters are not allowed in pmatch expressions. (Decimal points in the middle of nubmers are OK.)';
$string['ie_unrecognisedsubcontents'] = 'Non recognized sub content in code fragment "{$a}".';
$string['ie_missingclosingbracket'] = 'Missing closing bracket in code fragment "{$a}".';
$string['ie_lastsubcontenttypeorcharacter'] = 'Or character must not end subcontent in "{$a}".';
$string['ie_lastsubcontenttypeworddelimiter'] = 'Word delimiter character must not end sub content "{$a}".';
$string['ie_illegaloptions'] = 'Illegal options in expression "match<strong><em>{$a}</em></strong>()".';
$string['ie_unrecognisedexpression'] = 'Unrecognised expression.';
$string['inputareatoobig'] = 'The input area defined by "{$a}" is too big. Input area size is limited to a width of 150 characters and a height of 100 characters.';
$string['nomatchingsynonymforword'] = 'No synonyms entered for word. Delete the word or enter synonym(s) for it.';
$string['notenoughanswers'] = 'This type of question requires at least {$a} answers';
$string['nomatchingwordforsynonym'] = 'You have not entered a word that the synonym is equivalent too. Delete the synonym(s) or enter an equivalent word for it.';
$string['pleaseenterananswer'] = 'Please enter an answer.';
$string['pluginname'] = 'Pattern match';
$string['pluginname_help'] = 'In response to a question (that may include a image) the respondent types a short phrase. There may be several possible correct answers, each with a different grade. If the "Case sensitive" option is selected, then you can have different scores for "Word" or "word".';
$string['pluginname_link'] = 'question/type/pmatch';
$string['pluginnameadding'] = 'Adding a Pattern match question';
$string['pluginnameediting'] = 'Editing a Pattern match question';
$string['pluginnamesummary'] = 'Allows a short response of one or a few sentences that is graded by comparing against various model answers, which are described using the OU\'s pattern match syntax.';
$string['repeatedword'] = 'This word appears more than once in synonym list.';
$string['spellcheckerenchant'] = 'Enchant spell-checking library';
$string['spellcheckernull'] = 'No spell checking available';
$string['spellcheckerpspell'] = 'Pspell spell-checking library';
$string['spellcheckertype'] = 'Spell checking library';
$string['spellcheckertype_desc'] = 'Which spell checking library to use. This should automatically be set to the correct value on install.';
$string['spellingmistakes'] = 'The following words are not in our dictionary : {$a}. Please correct your spelling.';
$string['subsuponelineonly'] = 'The sub / super script editor can only be used with an input box one line high.';
$string['synonym'] = 'Synonyms';
$string['synonymsno'] = 'Synonyms {$a}';
$string['synonymcontainsillegalcharacters'] = 'Synonym contains illegal characters.';
$string['synonymsheader'] = 'Define Synonyms For Words in Answers';
$string['toomanywords'] = 'Your answer is too long. Please edit it to be no longer than 20 words.';
$string['unparseable'] = 'We do not understand the characters or punctuation here "{$a}"';
$string['wordwithsynonym'] = 'Word';
$string['wordcontainsillegalcharacters'] = 'Word contains illegal characters.';

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


$string['add'] = 'Add';
$string['addmoreanswerblanks'] = 'Blanks for {no} More Answers';
$string['addmoresynonymblanks'] = 'Blanks for {no} more synonyms';
$string['addtoanswer'] = 'Add to answer';
$string['allowsubscript'] = 'Allow use of subscript';
$string['allowsuperscript'] = 'Allow use of superscript';
$string['answeringoptions'] = 'Options For Entering Answers';
$string['answeroptions'] = 'Answer options';
$string['anyotheranswer'] = 'Any other answer';
$string['applydictionarycheck'] = 'Spell checking';
$string['amatiwsurl'] = 'Amati webservice URL';
$string['amatiwsurl_desc'] = 'URL for the Amati webservice';
$string['answer'] = 'Answer: {$a}';
$string['answermustbegiven'] = 'You must enter an answer if there is a grade or feedback.';
$string['answerno'] = 'Answer {$a}';
$string['caseno'] = 'No, case is unimportant';
$string['casesensitive'] = 'Case sensitivity';
$string['caseyes'] = 'Yes, case must match';
$string['choosetoken'] = 'Choose token';
$string['combinedcontrolnamepmatch'] = 'text input';
$string['converttospace'] = 'Convert to space';
$string['converttospace_help'] = 'Specify characters that you want the system to convert to spaces before running the pattern-matching process. This is great for simplifying the input if punctuation does not matter.';
$string['correctanswers'] = 'Answer matching';
$string['errors'] = 'Please fix the following problems : {$a}';
$string['error:title'] = 'Error';
$string['error:blank'] = 'The response cannot be blank';
$string['env_dictmissing'] = 'Missing spell check dictionary {$a->langforspellchecker} for installed language {$a->humanfriendlylang} is installed.';
$string['env_dictmissing2'] = 'Student attempted a spell check in language \'{$a}\'. But aspell dictionary for this language is not installed.';
$string['env_dictok'] = 'Spell check dictionary {$a->langforspellchecker} for installed language {$a->humanfriendlylang} is installed.';
$string['env_peclnormalisationmissing'] = 'PECL Package for Unicode Normalizer appears not to be correctly installed';
$string['env_peclnormalisationok'] = 'PECL Package for Unicode Normalizer appears to be correctly installed';
$string['env_pspellmissing'] = 'Pspell library appears not to be correctly installed';
$string['env_pspellok'] = 'Pspell library appears to be correctly installed';
$string['environmentcheck'] = 'Environment checks for the pmatch question type';
$string['err_ousupsubnotsupportedonmobile'] = 'This question requires superscripts or subscripts and so does not yet work in this app. Please answer this question in the web browser.';
$string['err_providepmatchexpression'] = 'You must provide a pmatch expression here.';
$string['exclude'] = 'Exclude';
$string['extenddictionary'] = 'Add these words to dictionary';
$string['filloutoneanswer'] = 'Use pattern match to syntax to describe possible answers. The student\'s response will be compared to these in order, and the first matching pattern will determine the score and the feedback. You must give at least one pattern. <br> Overall grading accuracy:';
$string['forcelength'] = 'If answer is more than 20 words';
$string['forcelengthno'] = 'do not issue warning';
$string['forcelengthyes'] = 'warn that answer is too long and invite respondee to shorten it';
$string['ie_nomatchfound'] = 'Error in pattern match code.';
$string['ie_nofullstop'] = 'Full stop characters are not allowed in pmatch expressions. (Decimal points in the middle of numbers are OK.)';
$string['ie_unrecognisedsubcontents'] = 'Non recognized sub content in code fragment "{$a}".';
$string['ie_missingclosingbracket'] = 'Missing closing bracket in code fragment "{$a}".';
$string['ie_lastsubcontenttypeorcharacter'] = 'Or character must not end subcontent in "{$a}".';
$string['ie_lastsubcontenttypeworddelimiter'] = 'Word delimiter character must not end sub content "{$a}".';
$string['ie_illegaloptions'] = 'Illegal options in expression "match<strong><em>{$a}</em></strong>()".';
$string['ie_unrecognisedexpression'] = 'Unrecognised expression.';
$string['inputareatoobig'] = 'The input area defined by "{$a}" is too big. Input area size is limited to a width of 150 characters and a height of 100 characters.';
$string['minresponses'] = 'Minimum number of responses';
$string['minresponses_desc'] = 'Minimum number of marked responses that should be uploaded in order for the Amati system to generate rules.';
$string['nolanguagesfound'] = '<i>No languages found</i>';
$string['minresponses_desc'] = 'Minimum number of marked responses that should be uploaded in order for the Amati system to generate rules';
$string['modelanswer'] = 'Model answer';
$string['modelanswer_help'] = 'Give one possible answer to this question that would be graded correct.';
$string['modelanswererror'] = '\'<strong>{$a}</strong>\' is not a correct answer to this question.';
$string['nomatchingsynonymforword'] = 'No synonyms entered for word. Delete the word or enter synonym(s) for it.';
$string['notenoughanswers'] = 'This type of question requires at least {$a} answers';
$string['nomatchingwordforsynonym'] = 'You have not entered a word that the synonym is equivalent too. Delete the synonym(s) or enter an equivalent word for it.';
$string['or'] = 'Or';
$string['pleaseenterananswer'] = 'Please enter an answer.';
$string['pluginname'] = 'Pattern match';
$string['pluginname_help'] = 'In response to a question (that may include a image) the respondent types a short phrase. There may be several possible correct answers, each with a different grade. If the "Case sensitive" option is selected, then you can have different scores for "Word" or "word".';
$string['pluginname_link'] = 'question/type/pmatch';
$string['pluginnameadding'] = 'Adding a Pattern match question';
$string['pluginnameediting'] = 'Editing a Pattern match question';
$string['pluginnamesummary'] = 'Allows a short response of one or a few sentences that is graded by comparing against various model answers, which are described using the OU\'s pattern match syntax.';
$string['precedes'] = 'Precedes';
$string['precedesclosely'] = 'Closely precedes';
$string['privacy:metadata:preference:pagesize'] = 'Number of pattern match question attempts to show per page.';
$string['processingxofy'] = 'Processing response {$a->row} of {$a->total}: {$a->response}.';
$string['repeatedword'] = 'This word appears more than once in synonym list.';
$string['resetrule'] = 'Reset rule';
$string['rule'] = 'Rule';
$string['ruleaccuracylabel'] = 'Effect on sample responses';
$string['ruleaccuracy'] = 'Responses not matched above: {$a->responseneedmatch} <br> Correctly matched by this rule: {$a->correctlymatched}, <span class="{$a->class}">Incorrectly matched: {$a->incorrectlymatched}</span> <br> Responses still to be processed below: {$a->responsestillprocess}';
$string['rulecreationasst'] = 'Show/hide rule creation assistant';
$string['rulecreationtoomanyors'] = 'Sorry too many or\'s.';
$string['rulecreationtoomanyterms'] = 'Sorry too many terms.';
$string['rulesuggestionlabel'] = 'Rule suggestion';
$string['rulesuggestionbutton'] = 'Auto generate matching rules';
$string['rulesuggestiondescriptionnoresponses'] = 'Automatically generate matching rules by uploading a set of existing marked responses';
$string['row'] = 'Row';
$string['savedxresponses'] = 'Saved {$a} responses';
$string['sentencedividers'] = 'Sentence end points';
$string['sentencedividers_help'] = 'Specify characters for the system to treat as sentence end points. By default, ‘?’ is a sentence end point, so if you wanted to match “Hello?”, you would remove ‘?’ from this field and use “match (hello\?)”. Note that ‘?’ needs escaping ( \\ ) in the match expression because it is a special character, but ‘.’ and ‘!’ do not.';
$string['sentencedividers_noconvert'] = '\'<strong>{$a}</strong>\' is used as a <strong>sentence end point</strong> and cannot be converted to space.';
$string['showcoverage'] = 'Show coverage';
$string['showingresponsesforquestion'] = 'Showing the responses for the selected question: {$a}';
$string['spellcheckerenchant'] = 'Enchant spell-checking library';
$string['spellcheckernull'] = 'No spell checking available';
$string['spellcheckerpspell'] = 'Pspell spell-checking library';
$string['spellcheckertype'] = 'Spell checking library';
$string['spellcheckertype_desc'] = 'Which spell checking library to use. This should automatically be set to the correct value on install.';
$string['setting_installed_spell_check_dictionaries'] = 'Spell check dictionaries';
$string['setting_installed_spell_check_dictionaries_des'] = 'This setting controls which spell-check language options are displayed to question authors when they create or edit a question.';
$string['apply_spellchecker_label'] = 'Do not check spelling of student';
$string['apply_spellchecker_select'] = '{$a->name} ({$a->code})';
$string['apply_spellchecker_missing_language_attempt'] = 'This question is set to use {$a} spell-check, but that language is not available on this server.';
$string['apply_spellchecker_missing_language_select'] = '{$a} (Warning! Dictionary not installed on this server)';
$string['spellingmistakes'] = 'The following words are not in our dictionary: {$a}. Please correct your spelling.';
$string['subsuponelineonly'] = 'The sub / super script editor can only be used with an input box one line high.';
$string['synonym'] = 'Synonyms';
$string['synonymsno'] = 'Synonyms {$a}';
$string['synonymcontainsillegalcharacters'] = 'Synonym contains illegal characters.';
$string['synonymsheader'] = 'Define Synonyms For Words in Answers';
$string['template'] = 'Template';
$string['term'] = 'Term';
$string['test'] = 'Test';
$string['testquestionidlabel'] = '#';
$string['testquestionactualmark'] = 'Computed mark';
$string['testquestionchangescore'] = 'Change score';
$string['testquestioncorrect'] = 'Correct';
$string['testquestionbacklink'] = 'Back to Test question';
$string['testquestiondeletedresponses'] = 'The responses were successfully deleted.';
$string['testquestioneditresponse'] = 'Edit response';
$string['testquestionexpectedfraction'] = 'Human mark';
$string['testquestionformheader'] = 'Marked responses to upload';
$string['testquestionforminfo'] = 'You should upload a spreadsheet file (.csv or .xlsx) with two columns. The first column contains the expected mark for that response, and the second column should contain that response. The first row in the file is assumed to contain column headings, and is ignored.';
$string['testquestionformuploadlabel'] = 'Marked responses';
$string['testquestionformdeletesubmit'] = 'Delete';
$string['testquestionformdeletecheck'] = 'Are you absolutely sure you want to completely delete these responses?';
$string['testquestionformtestsubmit'] = 'Test selected responses';
$string['testquestionformtitle'] = 'Pattern-match question testing tool';
$string['testquestionformnewresponsebutton'] = 'Add new response';
$string['testquestionformsaveresponsebutton'] = 'Save';
$string['testquestionformcancelresponsebutton'] = 'Cancel';
$string['testquestionformduplicateresponse'] = 'Duplicate responses are not allowed.';
$string['testquestionformerror_incorrectquestionid'] = 'Incorrect question id, or not a pattern match question.';
$string['testquestionheader'] = 'Testing question: {$a}';
$string['testquestionmatches'] = 'matches';
$string['testquestionincorrectlymarkedwrong'] = 'missed positive';
$string['testquestionincorrectlymarkedrights'] = 'missed negative';
$string['testthisquestionnoresponsesfound'] = 'No responses were found.';
$string['testthisquestionnoresmoreponsesrequired'] = 'More responses are required for auto-suggestion to work. There are {$a->existing} responses and you need {$a->required}';
$string['testthisquestionnorulesreturned'] = 'No rules were suggested.';
$string['testquestiontool'] = 'Pattern-match testing tool';
$string['testquestionresponse'] = 'Response';
$string['testquestionresponsesthatare'] = 'Responses that are';
$string['testquestionresultsheader'] = 'Test results: {$a}';
$string['testquestionresultssummary'] = 'Sample responses: {$a->total} <br>
     Marked correctly: {$a->correct} ({$a->accuracy}%)<br>
     <span>Computed mark greater than human mark: {$a->misspositive} (missed positive)</span><br>
     <span>Computed mark less than human mark: {$a->missnegative} (missed negative)</span>';
$string['testquestionruleslabel'] = 'Rules';
$string['testquestionungraded'] = 'ungraded';
$string['testquestionuploadtheseresponses'] = 'Upload these responses';
$string['testquestionuploadresponses'] = 'Upload responses';
$string['testquestionuploadanother'] = 'Upload another file';
$string['testquestionuploadrowhastwoitems'] = 'Each row should contain exactly two items, a numerical mark and a response. Row <b>{$a->row}</b> contains <b>{$a->items}</b> item(s).';
$string['testquestionuploadrownotvalidutf8'] = 'The response in row <b>{$a}</b> contains unrecognised special characters. The input must be valid UTF-8.';
$string['testthisquestion'] = 'Test this question';
$string['testsubquestionx'] = 'Test sub question {$a}';
$string['toomanywords'] = 'Your answer is too long. Please edit it to be no longer than 20 words.';
$string['tryrule'] = 'Try rule';
$string['tryrulecoverage'] = 'Coverage';
$string['tryrulenogradedresponses'] = 'There are no graded responses, please grade your response set.';
$string['tryrulenomatch'] = 'This rule does not match any graded responses.';
$string['tryrulenovalidrule'] = 'This rule is not a valid pmatch expression.';
$string['tryrulegradeerror'] = 'Sorry, try rule only works if the grade is set to 100% or None.';
$string['unparseable'] = 'We do not understand the characters or punctuation here "{$a}"';
$string['wordwithsynonym'] = 'Word';
$string['wordcontainsillegalcharacters'] = 'Word contains illegal characters.';
$string['xresponsesduplicated'] = 'The following {$a} responses were duplicated';
$string['xresponsesproblems'] = 'The following {$a} responses were not saved';
$string['xrulesuggested'] = '{$a} new answer(s) were suggested and added to the end of the existing answers';
$string['errorfileformat'] = 'The file must be in .csv/.xlsx/.html/.json/.ods format.';
$string['errorfilecell'] = 'The file requires at least two rows (the first row is the header row, the second row onwards for responses).';
$string['errorfilecolumnbigger'] = 'The file has more than two columns. Please only include the expected mark and response.';
$string['errorfilecolumnless'] = 'The file requires at least two columns (the first column for expected marks, the second column for responses).';

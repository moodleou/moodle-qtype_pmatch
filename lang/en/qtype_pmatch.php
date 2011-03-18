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
 * @package qtype_pmatch
 * @copyright 2008 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['addingpmatch'] = 'Adding a PMatch question';
$string['editingpmatch'] = 'Editing a PMatch question';
$string['pmatch'] = 'PMatch';
//intrepreter errors
$string['ie_nomatchfound'] = 'Error in pmatch code.';
$string['ie_unrecognisedsubcontents'] = 'Non recognized sub content in code fragment "{$a}".';
$string['ie_missingclosingbracket'] = 'Missing closing bracket in code fragment "{$a}".';
$string['ie_lastsubcontenttypeorcharacter'] = 'Or character must not end subcontent in "{$a}".';
$string['ie_lastsubcontenttypeworddelimiter'] = 'Word delimiter character must not end sub content "{$a}".';
$string['ie_illegaloptions'] = 'Illegal options in match{options}() expression "{$a}".';
$string['ie_unrecognisedexpression'] = 'Unrecognised expression.';


$string['addmoreanswerblanks'] = 'Blanks for {no} More Answers';
$string['addmoresynonymblanks'] = 'Blanks for {no} more synonyms';
$string['allowsubscript'] = 'Allow use of subscript';
$string['allowsuperscript'] = 'Allow use of super script';
$string['answeringoptions'] = 'Options For Entering Answers';
$string['applydictionarycheck'] = 'Check spelling of student';
$string['answer'] = 'Answer: {$a}';
$string['answermustbegiven'] = 'You must enter an answer if there is a grade or feedback.';
$string['answerno'] = 'Answer {$a}';
$string['caseno'] = 'No, case is unimportant';
$string['casesensitive'] = 'Case sensitivity';
$string['caseyes'] = 'Yes, case must match';
$string['converttospace'] = 'Convert the following characters to a space';
$string['correctanswers'] = 'Correct answers';
$string['extenddictionary'] = 'Add these words to dictionary';
$string['filloutoneanswer'] = 'Use PMatch syntax to describe correct answers. You must provide at least one possible answer. Answers left blank will not be used. The first matching answer will be used to determine the score and feedback.';
$string['forcelength'] = 'If answer is more than 20 words';
$string['forcelengthno'] = 'do not issue warning';
$string['forcelengthyes'] = 'warn that answer is too long and invite respondee to shorten it';
$string['forcelengthyes'] = 'warn that answer is too long and invite respondee to shorten it';
$string['inputareatoobig'] = 'The input area defined by "{$a}" is too big. Input area size is limited to a width of 150 characters and a height of 100 characters.';
$string['nomatchingsynonymforword'] = 'No synonyms entered for word. Delete the word or enter synonym(s) for it.';
$string['nomatchingwordforsynonym'] = 'You have not entered a word that the synonym is equivalent too. Delete the synonym(s) or enter an equivalent word for it.';
$string['pleaseenterananswer'] = 'Please enter an answer.';
$string['pmatch_help'] = 'In response to a question (that may include a image) the respondent types a short phrase. There may be several possible correct answers, each with a different grade. If the "Case sensitive" option is selected, then you can have different scores for "Word" or "word".';
$string['pmatch_link'] = 'question/type/pmatch';
$string['pmatchsummary'] = 'Allows a short response of one or a few sentences that is graded by comparing against various model answers, which are described using the OU\'s pmatch syntax.';
$string['synonym'] = 'Synonyms';
$string['synonymcontainsillegalcharacters'] = 'Synonym contains illegal characters.';
$string['synonymsheader'] = 'Define Synonyms For Words in Answers';
$string['wordwithsynonym'] = 'Word';
$string['wordcontainsillegalcharacters'] = 'Word contains illegal characters.';
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
 * Contains the helper class for the pmatch question type tests.
 *
 * @package   qtype_pmatch
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_pmatch\local\spell\qtype_pmatch_null_spell_checker;
use qtype_pmatch\local\spell\qtype_pmatch_spell_checker;

defined('MOODLE_INTERNAL') || die();

/**
 * Question maker for unit tests for the pmatch question definition class.
 *
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_test_helper extends question_test_helper {

    public function get_test_questions() {
        return ['listen', 'test0', 'frogtoad', 'test1'];
    }

    /**
     * Makes a pmatch question with correct answer 'Tom' or 'Harry', partially
     * correct answer 'Dick' and defaultmark 1.
     *
     * @param bool|PHPUnit\Framework\TestCase $applydictionarycheck false not to check.
     *      basic_testcase ($this in the test code) to check.
     * @return qtype_pmatch_question
     */
    public static function make_a_pmatch_question($applydictionarycheck = false) {
        if ($applydictionarycheck) {
            self::skip_test_if_no_spellcheck($applydictionarycheck, 'en');
        }
        question_bank::load_question_definition_classes('pmatch');
        $pm = new qtype_pmatch_question();
        test_question_maker::initialise_a_question($pm);
        $pm->name = 'Short answer question';
        $pm->questiontext = 'Who was Jane\'s companion : __________';
        $pm->generalfeedback = 'Generalfeedback: Tom, Dick or Harry are all possible answers.';
        $pm->pmatchoptions = new pmatch_options();
        $pm->modelanswer = 'Tom';
        $pm->answers = array(
            13 => new question_answer(13, 'match_w(Tom|Harry)', 1.0,
                'Either Tom or Harry is a very good answer.', FORMAT_HTML),
            14 => new question_answer(14,
                                      'match_w(Dick)', 0.8, 'Dick is an OK good answer.', FORMAT_HTML),
            15 => new question_answer(15,
                                      'match_w(Felicity)', 0.0, 'No, no, no! That is a bad answer.', FORMAT_HTML),
        );
        $pm->qtype = question_bank::get_qtype('pmatch');
        $pm->applydictionarycheck = $applydictionarycheck ? 'en_GB' :
                qtype_pmatch_spell_checker::DO_NOT_CHECK_OPTION;
        if ($pm->applydictionarycheck) {
            // These tests are in English,
            // no matter what the current language of the user running the tests.
            $pm->pmatchoptions->lang = 'en';
        }
        return $pm;
    }

    /**
     * @return stdClass data to create a pattern match question.
     */
    public function get_pmatch_question_form_data_listen() {
        global $CFG, $USER;

        $fromform = new stdClass();

        $fromform->name = 'My first pattern match question';
        $fromform->questiontext = array('text' => 'Listen, translate and write.', 'format' => FORMAT_HTML);
        $fromform->defaultmark = 1.0;
        $fromform->generalfeedback = array('text' => 'This is the simplest aromatic molecule.', 'format' => FORMAT_HTML);
        $fromform->allowsubscript = 0;
        $fromform->allowsuperscript = 0;
         $fromform->synonymsdata = array(
            array(
                'word' => 'any',
                'synonyms' => 'testing\|one\|two\|three\|four',
            ),
        );

        $fromform->extenddictionary = '';
        $fromform->sentencedividers = '.?!';
        $fromform->converttospace = ',;:';
        $fromform->modelanswer = 'testing one two three four';
        $fromform->answer = array('match (testing one two three four)');
        $fromform->fraction = array('1');
        $fromform->feedback = array(
                array('text' => 'Well done!', 'format' => FORMAT_HTML),
        );

        $fromform->otherfeedback = array('text' => 'Sorry, no.', 'format' => FORMAT_HTML);
        $fromform->penalty = 0.3333333;

        $fromform->hint = array(
            array(
                'text' => 'Please try again.',
                'format' => FORMAT_HTML,
            ),
            array(
                'text' => 'Use a calculator if necessary.',
                'format' => FORMAT_HTML,
            ),
        );

        $fromform->hintshownumcorrect = array(1, 1);
        $fromform->hintclearwrong = array(0, 1);
        $fromform->hintoptions = array(0, 1);

        return $fromform;
    }

    /**
     * @return stdClass data to create a pattern match question.
     */
    public function get_pmatch_question_form_data_frogtoad() {
        global $CFG, $USER;

        $fromform = new stdClass();

        $fromform->name = 'Frog but not toad';
        $fromform->questiontext = ['text' => 'Type a sentence with the word frog but not toad.', 'format' => FORMAT_HTML];
        $fromform->defaultmark = 1.0;
        $fromform->generalfeedback = ['text' => 'The word frog can appear within the sentence but not the word toad.',
                'format' => FORMAT_HTML];
        $fromform->allowsubscript = 0;
        $fromform->allowsuperscript = 0;
        $fromform->synonymsdata = [
            [
                'word' => '',
                'synonyms' => '',
            ]
        ];
        $fromform->extenddictionary = '';
        $fromform->sentencedividers = '.?!';
        $fromform->converttospace = ',;:';
        $fromform->modelanswer = 'I saw a tiny yellow frog in the amazon forest';
        $fromform->answer = ['match_w (toad)', 'match_w (frog)'];
        $fromform->fraction = ['0', '1'];
        $fromform->feedback =
            [
                ['text' => 'The word toad should not apear in your response.', 'format' => FORMAT_HTML],
                ['text' => 'Well done! The word frog apears in your response.', 'format' => FORMAT_HTML],
            ];
        $fromform->otherfeedback = ['text' => 'Sorry, no.', 'format' => FORMAT_HTML];
        $fromform->penalty = 0.3333333;

        $fromform->hint = [
            [
                'text' => 'Please try again.',
                'format' => FORMAT_HTML,
            ],
            [
                'text' => 'Use the word frog with any other words but not the word toad.',
                'format' => FORMAT_HTML,
            ]
        ];

        $fromform->hintshownumcorrect = [1, 1];
        $fromform->hintclearwrong = [0, 1];
        $fromform->hintoptions = [0, 1];

        return $fromform;
    }


    /**
     * Get test data for test question 0
     *
     * @return stdClass the question data.
     */
    public static function get_pmatch_question_data_test0() {
        question_bank::load_question_definition_classes('pmatch');
        $qdata = new stdClass();
        test_question_maker::initialise_question_data($qdata);
        $qdata->id = 1;
        $qdata->qtype = 'pmatch';
        $qdata->name = 'test-0';
        $qdata->questiontext = 'Listen, translate and write';
        $qdata->generalfeedback = '';

        $qdata->options = new stdClass();
        $qdata->options->usecase = 0;
        $qdata->options->allowsubscript = 0;
        $qdata->options->allowsuperscript = 0;
        $qdata->options->forcelength = 1;
        $qdata->options->applydictionarycheck = 'en_GB';
        $qdata->options->extenddictionary = '';
        $qdata->options->sentencedividers = '.?!';
        $qdata->options->converttospace = ',;:';
        $qdata->options->modelanswer = 'testing one two three four';

        $qdata->options->answers = [
                13 => new question_answer(13, 'match (testing one two three four)', 1.0,
                        'Well done!', FORMAT_MOODLE),
                14 => new question_answer(14,
                        '*', 0.0, 'Sorry, no.', FORMAT_MOODLE)
        ];

        $synonyms = [];
        $synonym = new stdClass();
        $synonym->word = 'any';
        $synonym->synonyms = 'testing|one|two|three|four';
        $synonyms[] = $synonym;
        $qdata->options->synonyms = $synonyms;

        $qdata->hints = [
                1 => new question_hint(1, 'Hint 1', FORMAT_HTML),
                2 => new question_hint(2, 'Hint 2', FORMAT_HTML),
        ];
        return $qdata;
    }

    /**
     * Cause a test to be skipped if we cannot spell-check in the given language.
     *
     * @param PHPUnit\Framework\TestCase $testcase the test to skip if necessary.
     * @param string $lang the language required.
     */
    public static function skip_test_if_no_spellcheck(PHPUnit\Framework\TestCase $testcase, string $lang) {
        $spellchecker = qtype_pmatch_spell_checker::make($lang, false);
        if ($spellchecker instanceof qtype_pmatch_null_spell_checker) {
            $testcase->markTestSkipped(
                    'Spell-checking not installed on your server. Skipping test.');
        }
    }
}

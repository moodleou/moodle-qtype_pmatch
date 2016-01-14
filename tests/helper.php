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


defined('MOODLE_INTERNAL') || die();

/**
 * Question maker for unit tests for the pmatch question definition class.
 *
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_test_helper extends question_test_helper {

    public function get_test_questions() {
        return array('listen');
    }

    /**
     * Makes a pmatch question with correct answer 'Tom' or 'Harry', partially
     * correct answer 'Dick' and defaultmark 1.
     * @param bool $applydictionarycheck false not to check. basic_testcase ($this in the test code) to check.
     * @return qtype_pmatch_question
     */
    public static function make_a_pmatch_question($applydictionarycheck = false) {
        if ($applydictionarycheck && !function_exists('pspell_new')) {
            $applydictionarycheck->markTestSkipped(
                'pspell not installed on your server. Spell checking will not work.');
        }
        question_bank::load_question_definition_classes('pmatch');
        $pm = new qtype_pmatch_question();
        test_question_maker::initialise_a_question($pm);
        $pm->name = 'Short answer question';
        $pm->questiontext = 'Who was Jane\'s companion : __________';
        $pm->generalfeedback = 'Generalfeedback: Tom, Dick or Harry are all possible answers.';
        $pm->pmatchoptions = new pmatch_options();
        $pm->answers = array(
            13 => new question_answer(13, 'match_w(Tom|Harry)', 1.0,
                                      'Either Tom or Harry is a very good answer.', FORMAT_HTML),
            14 => new question_answer(14,
                                      'match_w(Dick)', 0.8, 'Dick is an OK good answer.', FORMAT_HTML),
            15 => new question_answer(15,
                                      'match_w(Felicity)', 0.0, 'No, no, no! That is a bad answer.', FORMAT_HTML),
        );
        $pm->qtype = question_bank::get_qtype('pmatch');
        $pm->applydictionarycheck = $applydictionarycheck;
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
        $fromform->converttospace = ',;:';
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
}

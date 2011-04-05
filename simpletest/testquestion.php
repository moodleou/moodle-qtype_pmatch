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
 * Unit tests for the pmatch question definition class.
 *
 * @package    qtype
 * @subpackage pmatch
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/pmatch/question.php');
require_once($CFG->dirroot . '/question/engine/simpletest/helpers.php');


class test_pmatch_question_maker extends test_question_maker {
    /**
     * Makes a pmatch question with correct answer 'Tom' or 'Harry', partially
     * correct answer 'Dick' and defaultmark 1.
     * @return qtype_shortanswer_question
     */
    public static function make_a_pmatch_question($applydictionarycheck = false) {
        if ($applydictionarycheck && !function_exists('pspell_new')){
            throw new coding_exception('pspell not installed on your server. Spell checking will not work.');
        }
        question_bank::load_question_definition_classes('pmatch');
        $pm = new qtype_pmatch_question();
        self::initialise_a_question($pm);
        $pm->name = 'Short answer question';
        $pm->questiontext = 'Who was Jane\'s companion : __________';
        $pm->generalfeedback = 'Generalfeedback: Tom, Dick or Harry are all possible answers.';
        $pm->pmatchoptions = new pmatch_options();
        $pm->answers = array(
            13 => new question_answer(13, 'match_w(Tom|Harry)', 1.0, 'Either Tom or Harry is a very good answer.', FORMAT_HTML),
            14 => new question_answer(14, 'match_w(Dick)', 0.8, 'Dick is an OK good answer.', FORMAT_HTML),
            15 => new question_answer(15, '*', 0.0, 'No, no, no! That is a bad answer.', FORMAT_HTML),
        );
        $pm->qtype = question_bank::get_qtype('pmatch');
        $pm->applydictionarycheck = $applydictionarycheck;
        if ($pm->applydictionarycheck){
            //these tests are in English, no matter what the current laguage of the user running the tests
            $pm->pmatchoptions->lang = 'en';
        }
        return $pm;
    }


}

/**
 * Unit tests for the short answer question definition class.
 *
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_question_test extends UnitTestCase {
    public function test_compare_string_with_wildcard() {
        // Test case sensitive literal matches.
        $options = new pmatch_options();
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('mop', 'match_c(m)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('bomb', 'match_c(m)', $options));
        $this->assertFalse(qtype_pmatch_question::compare_string_with_pmatch_expression('car', 'match_c(m)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('car', 'match_c(*)', $options));
        $this->assertFalse(qtype_pmatch_question::compare_string_with_pmatch_expression('Car', 'match_c(c)', $options));

        $options = new pmatch_options();
        $options->ignorecase = true;
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('Mop', 'match_c(m)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('bomb', 'match_c(m)', $options));
        $this->assertFalse(qtype_pmatch_question::compare_string_with_pmatch_expression('car', 'match_c(m)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('car', 'match_c(*)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('Car', 'match_c(c)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('car', 'match_c(C)', $options));

    }

    public function test_is_complete_response() {
        $question = test_pmatch_question_maker::make_a_pmatch_question();

        $this->assertFalse($question->is_complete_response(array()));
        $this->assertFalse($question->is_complete_response(array('answer' => '')));
        $this->assertTrue($question->is_complete_response(array('answer' => '0')));
        $this->assertTrue($question->is_complete_response(array('answer' => '0.0')));
        $this->assertTrue($question->is_complete_response(array('answer' => 'x')));

        $question = test_pmatch_question_maker::make_a_pmatch_question(true);

        $this->assertTrue($question->is_complete_response(array('answer' => 'The Queen is dead.')));
        $this->assertFalse($question->is_complete_response(array('answer' => 'Long kive the Kin.')));
    }

    public function test_is_complete_response_with_spelling() {
    }

    public function test_is_gradable_response() {
        $question = test_pmatch_question_maker::make_a_pmatch_question();

        $this->assertFalse($question->is_gradable_response(array()));
        $this->assertFalse($question->is_gradable_response(array('answer' => '')));
        $this->assertTrue($question->is_gradable_response(array('answer' => '0')));
        $this->assertTrue($question->is_gradable_response(array('answer' => '0.0')));
        $this->assertTrue($question->is_gradable_response(array('answer' => 'x')));

        $question = test_pmatch_question_maker::make_a_pmatch_question(true);

        $this->assertTrue($question->is_gradable_response(array('answer' => 'The Queen is dead.')));
        $this->assertTrue($question->is_gradable_response(array('answer' => 'Long kive the Kin.')));
    }

    public function test_grading() {
        $question = test_pmatch_question_maker::make_a_pmatch_question();

        $this->assertEqual(array(0, question_state::$gradedwrong),
                $question->grade_response(array('answer' => 'x')));
        $this->assertEqual(array(1, question_state::$gradedright),
                $question->grade_response(array('answer' => 'Tom')));
        $this->assertEqual(array(1, question_state::$gradedright),
                $question->grade_response(array('answer' => 'Harry')));
                $this->assertEqual(array(0.8, question_state::$gradedpartial),
                $question->grade_response(array('answer' => 'Dick')));
    }

    public function test_get_correct_response() {
        $question = test_pmatch_question_maker::make_a_pmatch_question();

        $this->assertEqual(array('answer' => 'match_w(Tom|Harry)'),
                $question->get_correct_response());
    }

    public function test_get_question_summary() {
        $sa = test_pmatch_question_maker::make_a_pmatch_question();
        $qsummary = $sa->get_question_summary();
        $this->assertEqual('Who was Jane\'s companion : __________', $qsummary);
    }

    public function test_summarise_response() {
        $sa = test_pmatch_question_maker::make_a_pmatch_question();
        $summary = $sa->summarise_response(array('answer' => 'dog'));
        $this->assertEqual('dog', $summary);
    }

    public function test_classify_response() {
        $sa = test_pmatch_question_maker::make_a_pmatch_question();
        $sa->start_attempt(new question_attempt_step());

        $this->assertEqual(array(
                new question_classified_response(13, 'Tom', 1.0)),
                $sa->classify_response(array('answer' => 'Tom')));
        $this->assertEqual(array(
                new question_classified_response(13, 'Harry', 1.0)),
                $sa->classify_response(array('answer' => 'Harry')));
        $this->assertEqual(array(
                new question_classified_response(14, 'Dick', 0.8)),
                $sa->classify_response(array('answer' => 'Dick')));
        $this->assertEqual(array(
                new question_classified_response(15, 'cat', 0.0)),
                $sa->classify_response(array('answer' => 'cat')));
        $this->assertEqual(array(
                question_classified_response::no_response()),
                $sa->classify_response(array('answer' => '')));
    }


}

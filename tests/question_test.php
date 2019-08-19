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
 * @package   qtype_pmatch
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/pmatch/tests/helper.php');
require_once($CFG->dirroot . '/question/type/pmatch/question.php');

/**
 * Unit tests for the pattern-match question definition class.
 *
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      qtype_pmatch
 */
class qtype_pmatch_question_test extends basic_testcase {
    public function test_compare_string_with_wildcard() {
        // Test case sensitive literal matches.
        $options = new pmatch_options();
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('mop',
                                                                    'match_c(m)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('bomb',
                                                                    'match_c(m)', $options));
        $this->assertFalse(qtype_pmatch_question::compare_string_with_pmatch_expression('car',
                                                                    'match_c(m)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('car',
                                                                    'match_c(*)', $options));
        $this->assertFalse(qtype_pmatch_question::compare_string_with_pmatch_expression('Car',
                                                                    'match_c(c)', $options));

        $options = new pmatch_options();
        $options->ignorecase = true;
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('Mop',
                                                                    'match_c(m)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('bomb',
                                                                    'match_c(m)', $options));
        $this->assertFalse(qtype_pmatch_question::compare_string_with_pmatch_expression('car',
                                                                    'match_c(m)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('car',
                                                                    'match_c(*)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('Car',
                                                                    'match_c(c)', $options));
        $this->assertTrue(qtype_pmatch_question::compare_string_with_pmatch_expression('car',
                                                                    'match_c(C)', $options));
    }

    public function test_is_complete_response() {
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();

        $this->assertFalse($question->is_complete_response(array()));
        $this->assertFalse($question->is_complete_response(array('answer' => '')));
        $this->assertTrue($question->is_complete_response(array('answer' => '0')));
        $this->assertTrue($question->is_complete_response(array('answer' => '0.0')));
        $this->assertTrue($question->is_complete_response(array('answer' => 'x')));

        $question = qtype_pmatch_test_helper::make_a_pmatch_question($this);

        $this->assertTrue($question->is_complete_response(array('answer' => 'The Queen is dead.')));
        $this->assertFalse($question->is_complete_response(
                                                        array('answer' => 'Long kive the Kin.')));
    }

    public function test_is_complete_response_with_spelling() {
    }

    public function test_is_gradable_response() {
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();

        $this->assertFalse($question->is_gradable_response(array()));
        $this->assertFalse($question->is_gradable_response(array('answer' => '')));
        $this->assertTrue($question->is_gradable_response(array('answer' => '0')));
        $this->assertTrue($question->is_gradable_response(array('answer' => '0.0')));
        $this->assertTrue($question->is_gradable_response(array('answer' => 'x')));

        $question = qtype_pmatch_test_helper::make_a_pmatch_question($this);

        $this->assertTrue($question->is_gradable_response(array('answer' => 'The Queen is dead.')));
        $this->assertTrue($question->is_gradable_response(array('answer' => 'Long kive the Kin.')));
    }

    public function test_grading() {
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();

        $this->assertEquals(array(0, question_state::$gradedwrong),
                $question->grade_response(array('answer' => 'x')));
        $this->assertEquals(array(1, question_state::$gradedright),
                $question->grade_response(array('answer' => 'Tom')));
        $this->assertEquals(array(1, question_state::$gradedright),
                $question->grade_response(array('answer' => 'Harry')));
                $this->assertEquals(array(0.8, question_state::$gradedpartial),
                $question->grade_response(array('answer' => 'Dick')));
    }

    public function test_get_correct_response() {
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();
        $this->assertEquals(array('answer' => 'Tom'), $question->get_correct_response());

        $question->modelanswer = '';
        $this->assertNull($question->get_correct_response());

        $question->modelanswer = '0';
        $this->assertEquals(array('answer' => '0'), $question->get_correct_response());
    }

    public function test_get_question_summary() {
        $sa = qtype_pmatch_test_helper::make_a_pmatch_question();
        $qsummary = $sa->get_question_summary();
        $this->assertEquals('Who was Jane\'s companion : __________', $qsummary);
    }

    public function test_summarise_response() {
        $sa = qtype_pmatch_test_helper::make_a_pmatch_question();
        $summary = $sa->summarise_response(array('answer' => 'dog'));
        $this->assertEquals('dog', $summary);
    }

    public function test_classify_response() {
        $sa = qtype_pmatch_test_helper::make_a_pmatch_question();
        $sa->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(array(
                new question_classified_response(13, 'Tom', 1.0)),
                $sa->classify_response(array('answer' => 'Tom')));
        $this->assertEquals(array(
                new question_classified_response(13, 'Harry', 1.0)),
                $sa->classify_response(array('answer' => 'Harry')));
        $this->assertEquals(array(
                new question_classified_response(14, 'Dick', 0.8)),
                $sa->classify_response(array('answer' => 'Dick')));
        $this->assertEquals(array(
                new question_classified_response(15, 'Felicity', 0.0)),
                $sa->classify_response(array('answer' => 'Felicity')));
        $this->assertEquals(array(
                question_classified_response::no_response()),
                $sa->classify_response(array('answer' => '')));
    }
}

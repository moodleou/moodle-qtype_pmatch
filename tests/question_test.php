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

namespace qtype_pmatch;

use pmatch_options;
use qtype_pmatch_question;
use qtype_pmatch_test_helper;
use question_classified_response;
use question_state;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/pmatch/tests/helper.php');
require_once($CFG->dirroot . '/question/type/pmatch/question.php');

/**
 * Unit tests for the pattern-match question definition class.
 *
 * @package   qtype_pmatch
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qtype_pmatch_question
 */
final class question_test extends \basic_testcase {

    /**
     * Test that the pmatch expression is correctly compared with a string.
     */
    public function test_compare_string_with_wildcard(): void {
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

    /**
     * An expression that is valid on its own can become invalid once synonyms are applied,
     * because synonym substitution happens during parsing. In that case
     * compare_string_with_pmatch_expression() must return false rather than throwing a
     * coding_exception from pmatch_expression::matches().
     */
    public function test_compare_string_with_expression_invalidated_by_synonyms(): void {
        $options = new pmatch_options();
        // The synonym value carries a trailing space, so substituting it into the match content
        // leaves a word delimiter at the end of the sub content, making the expression invalid.
        $options->set_synonyms([(object) ['word' => 'Israel', 'synonyms' => 'Jacob ']]);

        // Sanity check: the expression really is invalid under these options.
        $expression = new \pmatch_expression('match_mw (Yahweh|delivered|salvation Israel)', $options);
        $this->assertFalse($expression->is_valid());

        // The comparison must not throw; it should simply report no match.
        $this->assertFalse(qtype_pmatch_question::compare_string_with_pmatch_expression(
                'salvation Israel', 'match_mw (Yahweh|delivered|salvation Israel)', $options));
    }

    /**
     * validate_pmatch_expression() must validate against the given matching options so that an
     * expression invalidated by synonyms is reported as an answer-field error, rather than being
     * accepted and then failing fatally during model answer matching.
     *
     * @covers \qtype_pmatch\form_utils::validate_pmatch_expression
     */
    public function test_validate_pmatch_expression_uses_options(): void {
        global $CFG;
        require_once($CFG->dirroot . '/question/type/pmatch/classes/form_utils.php');

        $expression = 'match_mw (Yahweh|delivered|salvation Israel)';

        // Without the synonym option the expression is valid: no error message.
        $this->assertSame('', form_utils::validate_pmatch_expression($expression));

        // With the synonym that appends a trailing delimiter, validation must report the error.
        $options = new pmatch_options();
        $options->set_synonyms([(object) ['word' => 'Israel', 'synonyms' => 'Jacob ']]);
        $this->assertNotSame('', form_utils::validate_pmatch_expression($expression, $options));
    }

    public function test_is_complete_response(): void {
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();

        $this->assertFalse($question->is_complete_response([]));
        $this->assertFalse($question->is_complete_response(['answer' => '']));
        $this->assertTrue($question->is_complete_response(['answer' => '0']));
        $this->assertTrue($question->is_complete_response(['answer' => '0.0']));
        $this->assertTrue($question->is_complete_response(['answer' => 'x']));

        $question = qtype_pmatch_test_helper::make_a_pmatch_question($this);

        $this->assertTrue($question->is_complete_response(['answer' => 'The Queen is dead.']));
        $this->assertFalse($question->is_complete_response(
                                                        ['answer' => 'Long kive the Kin.']));
    }

    public function test_is_gradable_response(): void {
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();

        $this->assertFalse($question->is_gradable_response([]));
        $this->assertFalse($question->is_gradable_response(['answer' => '']));
        $this->assertTrue($question->is_gradable_response(['answer' => '0']));
        $this->assertTrue($question->is_gradable_response(['answer' => '0.0']));
        $this->assertTrue($question->is_gradable_response(['answer' => 'x']));

        $question = qtype_pmatch_test_helper::make_a_pmatch_question($this);

        $this->assertTrue($question->is_gradable_response(['answer' => 'The Queen is dead.']));
        $this->assertTrue($question->is_gradable_response(['answer' => 'Long kive the Kin.']));
    }

    public function test_grading(): void {
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();

        $this->assertEquals([0, question_state::$gradedwrong],
                $question->grade_response(['answer' => 'x']));
        $this->assertEquals([1, question_state::$gradedright],
                $question->grade_response(['answer' => 'Tom']));
        $this->assertEquals([1, question_state::$gradedright],
                $question->grade_response(['answer' => 'Harry']));
                $this->assertEquals([0.8, question_state::$gradedpartial],
                $question->grade_response(['answer' => 'Dick']));

        // Pmatch question with quotematching = 0.
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();
        $question->answers = [
            16 => new \question_answer(16, 'match_w(D\'Angelo)', 1.0,
                'D\'Angelo a very good answer.', FORMAT_HTML),
        ];
        $this->assertEquals([1, question_state::$gradedright],
            $question->grade_response(['answer' => 'D’Angelo']));
        // Pmatch question with quotematching = 1.
        $question->quotematching = 1;
        $this->assertEquals([0, question_state::$gradedwrong],
            $question->grade_response(['answer' => 'D’Angelo']));
    }

    public function test_get_correct_response(): void {
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();
        $this->assertEquals(['answer' => 'Tom'], $question->get_correct_response());

        $question->modelanswer = '';
        $this->assertNull($question->get_correct_response());

        $question->modelanswer = '0';
        $this->assertEquals(['answer' => '0'], $question->get_correct_response());
    }

    public function test_get_question_summary(): void {
        $sa = qtype_pmatch_test_helper::make_a_pmatch_question();
        $qsummary = $sa->get_question_summary();
        $this->assertEquals('Who was Jane\'s companion : __________', $qsummary);
    }

    public function test_summarise_response(): void {
        $sa = qtype_pmatch_test_helper::make_a_pmatch_question();
        $summary = $sa->summarise_response(['answer' => 'dog']);
        $this->assertEquals('dog', $summary);
    }

    public function test_classify_response(): void {
        $sa = qtype_pmatch_test_helper::make_a_pmatch_question();
        $sa->start_attempt(new \question_attempt_step(), 1);

        $this->assertEquals([
                new question_classified_response(13, 'Tom', 1.0)],
                $sa->classify_response(['answer' => 'Tom']));
        $this->assertEquals([
                new question_classified_response(13, 'Harry', 1.0)],
                $sa->classify_response(['answer' => 'Harry']));
        $this->assertEquals([
                new question_classified_response(14, 'Dick', 0.8)],
                $sa->classify_response(['answer' => 'Dick']));
        $this->assertEquals([
                new question_classified_response(15, 'Felicity', 0.0)],
                $sa->classify_response(['answer' => 'Felicity']));
        $this->assertEquals([
                question_classified_response::no_response()],
                $sa->classify_response(['answer' => '']));
    }
}

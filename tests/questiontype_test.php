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

use question_possible_response;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/pmatch/questiontype.php');

/**
 * Unit tests for the pmatch question type class.
 *
 * @package   qtype_pmatch
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qtype_pmatch
 */
final class questiontype_test extends \basic_testcase {

    /** @var string[] List of files to include for code coverage */
    public static $includecoverage = ['question/type/questiontype.php',
                                        'question/type/pmatch/questiontype.php'];
    /** @var \qtype_pmatch The question type instance being tested */
    protected $qtype;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->qtype = new \qtype_pmatch();
    }

    /**
     * Returns a test question data object with predefined answers.
     *
     * @return \stdClass
     */
    protected function get_test_question_data(): \stdClass {
        $q = new \stdClass();
        $q->id = 1;
        $q->options = new \stdClass();
        $q->options->answers[1] = (object) ['answer' => 'match(frog)', 'fraction' => 1];
        $q->options->answers[2] = (object) ['answer' => '*', 'fraction' => 0];

        return $q;
    }

    /**
     * Tests the name of the question type.
     */
    public function test_name(): void {
        $this->assertEquals('pmatch', $this->qtype->name());
    }

    /**
     * Tests the can_analyse_responses method of the question type.
     */
    public function test_can_analyse_responses(): void {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    /**
     * Tests the get_random_guess_score method of the question type.
     */
    public function test_get_random_guess_score(): void {
        $q = $this->get_test_question_data();
        $this->assertEquals(0, $this->qtype->get_random_guess_score($q));
    }

    /**
     * Tests the get_possible_responses method of the question type.
     */
    public function test_get_possible_responses(): void {
        $q = $this->get_test_question_data();

        $this->assertEquals([
            $q->id => [
                1 => new question_possible_response('match(frog)', 1),
                2 => new question_possible_response('*', 0),
                null => question_possible_response::no_response()],
        ], $this->qtype->get_possible_responses($q));
    }
}

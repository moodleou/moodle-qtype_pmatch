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
 * Unit tests for the pmatch question type class.
 *
 * @package    qtype
 * @subpackage pmatch
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/pmatch/questiontype.php');


/**
 * Unit tests for the pmatch question type class.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_test extends UnitTestCase {
    public static $includecoverage = array('question/type/questiontype.php', 'question/type/pmatch/questiontype.php');
    var $qtype;

    public function setUp() {
        $this->qtype = new qtype_pmatch();
    }

    public function tearDown() {
        $this->qtype = null;
    }

    protected function get_test_question_data() {
        $q = new stdClass();
        $q->id = 1;
        $q->options->answers[1] = (object) array('answer' => 'match(frog)', 'fraction' => 1);
        $q->options->answers[2] = (object) array('answer' => '*', 'fraction' => 0);

        return $q;
    }

    public function test_name() {
        $this->assertEqual($this->qtype->name(), 'pmatch');
    }

    public function test_can_analyse_responses() {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    public function test_get_random_guess_score() {
        $q = $this->get_test_question_data();
        $this->assertEqual(0, $this->qtype->get_random_guess_score($q));
    }

    public function test_get_possible_responses() {
        $q = $this->get_test_question_data();

        $this->assertEqual(array(
            $q->id => array(
                1 => new question_possible_response('match(frog)', 1),
                2 => new question_possible_response('*', 0),
                null => question_possible_response::no_response()),
        ), $this->qtype->get_possible_responses($q));
    }
}

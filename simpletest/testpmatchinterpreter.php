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
 * This file contains tests that walks a question through the interactive
 * behaviour.
 *
 * @package qtype
 * @subpackage gapselect
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot . '/question/type/pmatch/pmatchinterpreter.php');


class qtype_pmatch_interpreter extends UnitTestCase {
    public function test_qtype_pmatch_all() {
        $matchall = new qtype_pmatch_match_all();
        $this->assertEqual(array(true, 5), $matchall->match_not(' not pmatch_all()', 0));
        $this->assertEqual(array(true, 6), $matchall->match_not(' not  pmatch_all()', 0));
        $this->assertEqual(array(false, 0), $matchall->match_not(' notpmatch_all()', 0));
        $this->assertEqual(array(false, 2), $matchall->match_not(' notpmatch_all()', 2));
        $this->assertEqual(array(true, 16), $matchall->match(' not pmatch_all()', 0));
        $this->assertEqual(array(true, 17), $matchall->match(' not  pmatch_all()', 0));
        $this->assertEqual(array(false, 0), $matchall->match(' notpmatch_all()', 0));
        $this->assertEqual(array(false, 2), $matchall->match(' notpmatch_all()', 2));
    }
}

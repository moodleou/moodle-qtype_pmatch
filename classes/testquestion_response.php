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
 * Defines the \qtype_pmatch\test response class.
 *
 * @package   qtype_pmatch
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch;
defined('MOODLE_INTERNAL') || die();

/**
 * Question type: Pattern match: Test response class.
 *
 * A simple object representing one test response.
 *
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testquestion_response {

    /** @var string to identify the matched state. */
    const MATCHED = 'matched';
    /** @var string to identify the missed positive state. */
    const MISSED_POSITIVE     = 'missedpositive';
    /** @var string to identify the missed negative state. */
    const MISSED_NEGATIVE    = 'missednegative';
    /** @var string to identify the ungraded state. */
    const UNGRADED   = 'ungraded';

    /** @var id. */
    public $id = null;

    /** @var questionid. */
    public $questionid = null;

    /** @var response. */
    public $response = null;

    /** @var expectedfraction. */
    public $expectedfraction = null;

    /** @var gradedfraction. */
    public $gradedfraction = null;

    /** @var ruleids. */
    public $ruleids = array();

    /**
     * Create an instance of this class representing an empty test response.
     * @param $response stdClass data object to translate into a test_response class
     * @return test_response
     */
    public static function create($response = null) {
        $testresponse = new self();
        $fields = array('id', 'questionid', 'response', 'expectedfraction', 'gradedfraction');

        if ($response === null) {
            return null;
        }
        foreach ($fields as $field) {
            if (!isset($response->$field)) {
                continue;
            }
            $testresponse->$field = $response->$field;

            // Marks are stored as fractions but considered ints in general.
            if (strpos($field, 'fraction')) {
                $testresponse->$field = round($testresponse->$field);
            }
        }

        return $testresponse;
    }

    public function set_gradedfraction($value) {
        $this->gradedfraction = round($value);
    }
}

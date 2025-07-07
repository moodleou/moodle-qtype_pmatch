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

/**
 * A simple object representing one question-testing response.
 *
 * @package   qtype_pmatch
 * @copyright 2013 The Open University
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

    /** @var int id. */
    public $id = null;

    /** @var int questionid. */
    public $questionid = null;

    /** @var string response. */
    public $response = null;

    /** @var float expectedfraction. */
    public $expectedfraction = null;

    /** @var float gradedfraction. */
    public $gradedfraction = null;

    /** @var array ruleids. */
    public $ruleids = [];

    /**
     * Create an instance of this class representing an empty test response.
     * @param $response \stdClass data object to translate into a test_response class
     * @return testquestion_response
     */
    public static function create($response = null) {
        $testresponse = new self();
        $fields = ['id', 'questionid', 'response', 'expectedfraction', 'gradedfraction'];

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

    /**
     * Set the graded fraction value.
     *
     * @param float $value The value to set.
     */
    public function set_gradedfraction($value) {
        $this->gradedfraction = round($value);
    }
}

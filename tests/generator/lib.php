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

defined('MOODLE_INTERNAL') || die();

/**
 * Pattern match question type test question test data generator class
 *
 * @package   qtype_pmatch
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_generator extends component_generator_base {

    /**
     * @var number of created instances
     */
    protected $responsecount = 0;

    public function reset() {
        $this->responsecount = 0;
    }

    /**
     * Create a new test response.
     * @param array|stdClass $record
     * @return stdClass qtype_pmatch_test_responses record.
     */
    public function create_test_response($record = null, $question = null) {
        global $DB;

        $this->responsecount++;

        $defaults = array(
            'response' => 'Test response ' . $this->responsecount,
            'questionid' => $question ? $question->id : 0,
            'expectedfraction' => null,
            'gradedfraction' => null
        );

        $record = $this->datagenerator->combine_defaults_and_record($defaults, $record);
        $record['id'] = $DB->insert_record('qtype_pmatch_test_responses', $record);
        return (object) $record;
    }
}

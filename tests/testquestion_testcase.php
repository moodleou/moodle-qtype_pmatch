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
 * This file contains of the pmatch library using files of examples.
 *
 * @package   qtype_pmatch
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/pmatch/tests/helper.php');
require_once($CFG->dirroot . '/question/type/pmatch/pmatchlib.php');


/**
 * Test driver class that tests the pmatch library by loading examples from
 * text files in the examples folder.
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     qtype_pmatch
 */
class qtype_pmatch_testquestion_testcase extends advanced_testcase {

    public static $responsesfilepath = "fixtures/shortanswerquestion_gradedresponses.csv";
    protected $currentquestion = null; // Holder for the current question object.

    /**
     * Load a csv file into an array of response objects reporting feedback
     * @param qtype_pmatch_question $question (optional) question to associate responses with.
     * @return array $responses, $problems
     */
    protected function load_responses($question = null, $pathtoresponses = null) {
        $pathtoresponses = $pathtoresponses ? $pathtoresponses : self::$responsesfilepath;
        $responsesfile = dirname(__FILE__) . '/' . $pathtoresponses;
        if (!$question) {
            $question = $this->create_default_question();
        }
        return qtype_pmatch\test_responses::load_responses_from_file($responsesfile, $question);
    }

    /**
     * Create a default pmatch question object
     * @return qtype_pmatch_question
     */
    protected function create_default_question() {
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();
        $question->id = 1;
        return $question;
    }

    /**
     * Load the default result set and store in the database.
     * Note the array returned is in a random order, so do not rely on an array_shift to
     * give you the first item you might expect from the list of responses you provide.
     * @return array \qtype_pmatch\test_response
     */
    protected function load_default_responses($pathtoresponses = null) {
        global $DB;
        $this->currentquestion = $this->create_default_question();

        list($responses, $problems) = $this->load_responses($this->currentquestion, $pathtoresponses);

        //  Add responses.
        \qtype_pmatch\test_responses::add_responses($responses);
        $dbresponses = $DB->get_records('qtype_pmatch_test_responses');
        return \qtype_pmatch\test_responses::data_to_responses($dbresponses);
    }

    /**
     * Load graded data for responses.
     *
     * To match up with a set of responses.
     */
    protected function load_graded_data($pathtoresponses) {
        $absolutepath = dirname(__FILE__) . '/' . $pathtoresponses;

        $handle = fopen($absolutepath, 'r');
        if (!$handle) {
            throw new coding_exception('Could not open testquestionresponses CSV file.');
        }
        $gradeddata = array();
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) == 2) {
                $data[2] = null;
            }
            $gradeddata[$data[1]] = $data[2];
        }
        return $gradeddata;
    }

    /**
     * Update the computer grade for each response using the grades in the given file.
     */
    protected function update_response_grades_from_file($responses, $pathtoresponses) {
        $gradeddata = $this->load_graded_data($pathtoresponses);

        // Update computer marked grade.
        foreach ($responses as $response) {
            if (!array_key_exists($response->response, $gradeddata)) {
                continue;
            }

            $response->gradedfraction = $gradeddata[$response->response];

            if ($response->gradedfraction == null) {
                continue;
            }

            \qtype_pmatch\test_responses::update_response($response);
        }
    }

    /**
     * Check this class is working.
     */
    public function test_currentquestion() {
        $this->resetAfterTest();
        $this->load_default_responses();
        $this->assertEquals($this->currentquestion->id, 1);
    }

}

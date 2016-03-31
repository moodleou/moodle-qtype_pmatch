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
 * Library for the testquestion test suite.
 *
 * @package   qtype_pmatch
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/pmatch/tests/helper.php');
require_once($CFG->dirroot . '/question/type/pmatch/pmatchlib.php');


/**
 * Base test class providing defaults for the testquestion suite.
 *
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     qtype_pmatch
 */
class qtype_pmatch_testquestion_testcase extends advanced_testcase {

    /**
     * File path to default responses csv file.
     */
    public static $responsesfilepath = "fixtures/testresponses.csv";

    /**
     * File path to default graded responses csv file.
     */
    public static $gradedresponses = "fixtures/testresponsesgraded.csv";

    /**
     * Place holder for the current question object.
     */
    protected $currentquestion = null;

    /**
     * File path to default rules json file representing rules returned from the AMATI web service.
     */
    public static $rulesfilepath = "fixtures/testquestion_rules.json";

    /**
     * Is the the current question initialised correctly?
     * @param return void
     */
    public function test_currentquestion() {
        $this->resetAfterTest();
        $this->load_default_responses();
        $this->assertEquals($this->currentquestion->id, 1);
    }

    /**
     * Load a csv file into an array of response objects reporting feedback.
     * @param qtype_pmatch_question $question (optional) question to associate responses with.
     * @return array $responses, $problems
     */
    protected function load_responses($question = null, $pathtoresponses = null, $count=0) {
        $pathtoresponses = $pathtoresponses ? $pathtoresponses : self::$responsesfilepath;
        $responsesfile = dirname(__FILE__) . '/' . $pathtoresponses;
        if (!$question) {
            $question = $this->create_default_question();
        }
        return \qtype_pmatch\testquestion_responses::load_responses_from_file($responsesfile, $question, $count);
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
     * Load the default responses and related question
     * Create a question if none exists. Load response set from file then save them to the database
     * for the given question and return the responses ready for use.
     * Note the array returned is in a random order, so do not rely on an array_shift to
     * give you the first item you might expect from the list of responses you provide.
     * @param $pathtoresponses = string file path to the required responses
     * @param $count int Number of responses to load. 0 = load all responses in file
     * @return array \qtype_pmatch\test_response responses for the question
     */
    protected function load_default_responses($pathtoresponses = null, $count=0) {
        global $DB;
        $this->currentquestion = $this->create_default_question();

        list($responses, $problems) = $this->load_responses($this->currentquestion, $pathtoresponses, $count);

        //  Add responses to the db.
        \qtype_pmatch\testquestion_responses::add_responses($responses);
        $dbresponses = $DB->get_records('qtype_pmatch_test_responses');
        return \qtype_pmatch\testquestion_responses::data_to_responses($dbresponses);
    }

    /**
     * Load graded response data from the given csv file path .
     * Format the graded response data ready to convert to responses.
     * @param $pathtoresponses = string file path to the required responses
     * @return array rows of graded response data
     */
    protected function load_graded_data($pathtoresponses = null) {
        if (empty($pathtoresponses)) {
            $pathtoresponses = self::$gradedresponses;
        }
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
     * Update the computer grade for each response using the grades in the given graded response file.
     * @param $responses array \qtype_pmatch\test_response responses
     * @param $pathtoresponses = string file path to the required responses
     * @return void
     */
    protected function update_response_grades_from_file($responses, $pathtoresponses = null) {
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

            \qtype_pmatch\testquestion_responses::update_response($response);
        }
    }

    /**
     * Convert the rule match look up table into the related responses and answers
     * so it can be tested.
     */
    protected function get_rule_matches_as_responses_and_rules($rulematches, $rules, $responses) {
        global $DB;

        $responseids = array();
        $ruleids = array();
        $matchedresponsesandrules = array();
        $matchedrulesandresponses = array();
        foreach ($rulematches['responseidstoruleids'] as $responseid => $responseruleids) {
            array_push($responseids, $responseid);
            $response = $responses[$responseid];
            if (!array_key_exists($response->response, $matchedresponsesandrules)) {
                $matchedresponsesandrules[$response->response] = array();
            }
            $matchedresponse = $matchedresponsesandrules[$response->response];
            foreach ($responseruleids as $ruleid) {
                $rule = $rules[$ruleid];
                if (in_array($rule->answer, $matchedresponse)) {
                    continue;
                }
                $matchedresponse[] = $rule->answer;
            }
            $matchedresponsesandrules[$response->response] = $matchedresponse;
        }

        foreach ($rulematches['ruleidstoresponseids'] as $ruleid => $ruleresponseids) {
            array_push($ruleids, $ruleid);
            $rule = $rules[$ruleid];
            if (!array_key_exists($rule->answer, $matchedrulesandresponses)) {
                $matchedrulesandresponses[$rule->answer] = array();
            }
            $matchedrule = $matchedrulesandresponses[$rule->answer];
            foreach ($ruleresponseids as $responseid) {
                $response = $responses[$responseid];
                if (in_array($response->response, $matchedrule)) {
                    continue;
                }
                $matchedrule[] = $response->response;
            }
            $matchedrulesandresponses[$rule->answer] = $matchedrule;
        }

        $responseandrulematches = array('responseidstoruleids' => $matchedresponsesandrules,
                                    'ruleidstoresponseids' => $matchedrulesandresponses);
        return $responseandrulematches;
    }

    protected function get_rule_matches($responses, $rules) {
        // Determine which rules match which response given the responses.
        $rulematches = \qtype_pmatch\testquestion_responses::get_rule_matches_from_responses($responses);

        // Translate the rule and response ids into responses and rules to test.
        $responseandrulematches = $this->get_rule_matches_as_responses_and_rules($rulematches, $rules, $responses);

        return $responseandrulematches['ruleidstoresponseids'];
    }

}

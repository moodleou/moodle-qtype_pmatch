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

use qtype_pmatch_question;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/pmatch/tests/helper.php');
require_once($CFG->dirroot . '/question/type/pmatch/pmatchlib.php');

/**
 * Base test class providing defaults for the testquestion suite.
 *
 * @package   qtype_pmatch
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testquestion_test_base extends \question_testcase {

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
     * Load a csv file into an array of response objects reporting feedback.
     *
     * @param qtype_pmatch_question|null $question (optional) question to associate responses with.
     * @param string|null fixture filename to use
     * @param int $count number of responses to import (defaults to all).
     * @return array as for testquestion_responses::load_responses_from_file.
     */
    protected function load_responses(
            qtype_pmatch_question $question = null,
            string $pathtoresponses = null,
            int $count = 0): array {
        $pathtoresponses = $pathtoresponses ?? self::$responsesfilepath;
        $responsesfile = dirname(__FILE__) . '/' . $pathtoresponses;
        if (!$question) {
            $question = $this->create_default_question();
        }
        return testquestion_responses::load_responses_from_file($responsesfile, $question, $count);
    }

    /**
     * Create a default pmatch question object.
     *
     * @return qtype_pmatch_question
     */
    protected function create_default_question(): qtype_pmatch_question {
        $question = \qtype_pmatch_test_helper::make_a_pmatch_question();
        $question->id = 1;
        return $question;
    }

    /**
     * Load the default responses and related question
     * Create a question if none exists. Load response set from file then save them to the database
     * for the given question and return the responses ready for use.
     * Note the array returned is in a random order, so do not rely on an array_shift to
     * give you the first item you might expect from the list of responses you provide.
     *
     * @param string|null $pathtoresponses file path to the required responses
     * @param int $count Number of responses to load. 0 = load all responses in file
     * @return testquestion_response[] responses for the question
     */
    protected function load_default_responses(string $pathtoresponses = null, int $count = 0): array {
        global $DB;
        $this->currentquestion = $this->create_default_question();

        [$responses] = $this->load_responses($this->currentquestion, $pathtoresponses, $count);

        // Add responses to the db.
        testquestion_responses::add_responses($responses);
        $dbresponses = $DB->get_records('qtype_pmatch_test_responses');
        return testquestion_responses::data_to_responses($dbresponses);
    }

    /**
     * Load graded response data from the given csv file path.
     * Format the graded response data ready to convert to responses.
     *
     * @param string|null $pathtoresponses file path to the required responses
     * @return array rows of graded response data
     */
    protected function load_graded_data(?string $pathtoresponses): array {
        $pathtoresponses = $pathtoresponses ?? self::$gradedresponses;
        $absolutepath = dirname(__FILE__) . '/' . $pathtoresponses;

        $handle = fopen($absolutepath, 'r');
        if (!$handle) {
            throw new \coding_exception('Could not open testquestionresponses CSV file.');
        }
        $gradeddata = [];
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
     * @param testquestion_response[] $responses
     * @param string|null $pathtoresponses file path to the required responses
     */
    protected function update_response_grades_from_file(array $responses, string $pathtoresponses = null): void {
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

            testquestion_responses::update_response($response);
        }
    }

    /**
     * Convert the rule match look up table into the related responses and answers
     * so it can be tested.
     *
     * @param array $rulematches
     * @param array $rules
     * @param array $responses
     * @return array[]
     */
    protected function get_rule_matches_as_responses_and_rules(
            array $rulematches, array $rules, array $responses): array {

        $matchedresponsesandrules = [];
        $matchedrulesandresponses = [];
        foreach ($rulematches['responseidstoruleids'] as $responseid => $responseruleids) {
            $response = $responses[$responseid];
            if (!array_key_exists($response->response, $matchedresponsesandrules)) {
                $matchedresponsesandrules[$response->response] = [];
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
            $rule = $rules[$ruleid];
            if (!array_key_exists($rule->answer, $matchedrulesandresponses)) {
                $matchedrulesandresponses[$rule->answer] = [];
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

        return [
            'responseidstoruleids' => $matchedresponsesandrules,
            'ruleidstoresponseids' => $matchedrulesandresponses,
        ];
    }

    /**
     * Get which rulse match which responses.
     *
     * @param testquestion_response[] $responses
     * @param array $rules
     * @return array
     */
    protected function get_rule_matches(array $responses, array $rules): array {
        // Determine which rules match which response given the responses.
        $rulematches = testquestion_responses::get_rule_matches_from_responses($responses);

        // Translate the rule and response ids into responses and rules to test.
        $responseandrulematches = $this->get_rule_matches_as_responses_and_rules($rulematches, $rules, $responses);

        return $responseandrulematches['ruleidstoresponseids'];
    }
}

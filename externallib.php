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

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/questionlib.php');

/**
 * Pattern-match question type upgrade code.
 *
 * @package   qtype_pmatch
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_external extends external_api {

    /**
     * Describes the parameters for check_response webservice.
     *
     * @return external_function_parameters
     */
    public static function check_response_parameters() {
        return new external_function_parameters([
                'questionid' => new external_value(PARAM_INT, 'The question id'),
                'response' => new external_value(PARAM_TEXT, 'The response'),
        ]);
    }

    /**
     * Describes the return value for check_response webservice.
     *
     * @return external_single_structure
     */
    public static function check_response_returns() {
        return new external_single_structure([
                'status' => new external_value(PARAM_TEXT, 'Status when check'),
                'message' => new external_value(PARAM_TEXT, 'The error message', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Check response for create pattern match test response.
     *
     * @param int $questionid The question id
     * @param int $response The response to check
     * @return array The status and message after checked the response.
     */
    public static function check_response($questionid, $response) {
        $result = [];
        $result['status'] = 'success';
        $params = self::validate_parameters(self::check_response_parameters(), [
                'questionid' => $questionid,
                'response' => $response]);
        $duplicated = \qtype_pmatch\testquestion_responses::check_duplicate_response($params['questionid'], $params['response']);
        if ($duplicated) {
            $result['status'] = 'error';
            $result['message'] = get_string('testquestionformduplicateresponse', 'qtype_pmatch');
        }
        return $result;
    }

    /**
     * Describes the parameters for create_response webservice.
     *
     * @return external_function_parameters
     */
    public static function create_response_parameters() {
        return new external_function_parameters([
                'questionid' => new external_value(PARAM_INT, 'The question id'),
                'expectedfraction' => new external_value(PARAM_FLOAT, 'The expectedfraction'),
                'response' => new external_value(PARAM_TEXT, 'The response'),
                'curentrow' => new external_value(PARAM_INT, 'The index of curent row editing'),
        ]);
    }

    /**
     * Describes the return value for create_response
     *
     * @return external_single_structure
     */
    public static function create_response_returns() {
        return new external_single_structure([
                'status' => new external_value(PARAM_TEXT, 'Status when create'),
                'message' => new external_value(PARAM_TEXT, 'The error message', VALUE_OPTIONAL),
                'html' => new external_value(PARAM_RAW, 'A row html for append to response table'),
                'counts' => new external_single_structure([
                        'graded' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                        'correctlymarkedright' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                        'correctlymarkedwrong' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                        'humanmarkedwrong' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                        'humanmarkedright' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                        'ungraded' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                        'accuracy' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                ])
        ]);
    }

    /**
     * Create pattern match test response
     *
     * @param int $questionid The question id
     * @param int $expectedfraction The expectedfraction for qtype_pmatch_test_responses table
     * @param string $response The response for qtype_pmatch_test_responses table
     * @param int $curentrow The index of curent row editing.
     *
     * @return array The status and data after created the response. If not success return the error message.
     */
    public static function create_response($questionid, $expectedfraction, $response, $curentrow) {
        global $DB;
        $params = self::validate_parameters(self::create_response_parameters(), [
                'questionid' => $questionid,
                'expectedfraction' => $expectedfraction,
                'response' => $response,
                'curentrow' => $curentrow]);
        $question = question_bank::load_question($params['questionid']);
        try {
            $response = new stdClass();
            $response->expectedfraction = $params['expectedfraction'];
            $response->response = $params['response'];
            $response->questionid = $params['questionid'];
            $duplicated = \qtype_pmatch\testquestion_responses::check_duplicate_response($params['questionid'],
                    $params['response']);

            if (!$duplicated) {
                $rid = $DB->insert_record('qtype_pmatch_test_responses', $response);
            } else {
                throw new Exception(get_string('testquestionformduplicateresponse', 'qtype_pmatch'));
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            $result['status'] = 'error';
            $result['counts'] = [];
            $result['html'] = '';
            return $result;
        }
        // Now update the computed mark (though this will never change), as it allows us
        // to get the correct row class. It also means that if you change the human mark
        // of a response that has not been computer marked yet, the computed mark will be inserted.
        $responses = \qtype_pmatch\testquestion_responses::get_responses_by_ids([$rid]);
        $response = $responses[$rid];
        \qtype_pmatch\testquestion_responses::grade_response($response, $question);
        \qtype_pmatch\testquestion_responses::save_rule_matches($question, [$rid]);
        // We need the table to get a row response.
        $options = new \qtype_pmatch\testquestion_options($question);
        $testresponsesobj = \qtype_pmatch\testquestion_responses::create_for_question($question);
        $table = new \qtype_pmatch\testquestion_table($question, $testresponsesobj, $options);
        // Counts could be returned as the lang string 'testquestionresultssummary', and that
        // would mean any changes in the string would not need to be replicated in updater.js, creator.js,
        // but it was felt that just passing an object of integers is better.
        $result['counts'] = \qtype_pmatch\testquestion_responses::get_question_grade_summary_counts($question);
        $result['html'] = $table->get_row_html_for_response_table($response, $curentrow);
        $result['status'] = 'success';

        return $result;
    }
}

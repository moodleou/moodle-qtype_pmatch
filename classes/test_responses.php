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
 * Defines the \qtype_pmatch\test responses class.
 *
 * @package   qtype_pmatch
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch;
defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/type/pmatch/question.php');

/**
 * Question type: Pattern match: Test responses class.
 *
 * Manages the test responses associated with a given question.
 *
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_responses {

    /** @var \question the question the test responses relate to. */
    protected $questionobj = null;

    /**
     * Create an instance of this class representing a question with no saved test responses.
     * @return test_responses
     */
    public static function create() {
        return new self();
    }

    /**
     * Create an instance of this class representing the saved test responses of a given question.
     * @param \question $questionobj the quiz.
     * @return test_responses
     */
    public static function create_for_question($questionobj) {
        $responses = self::create();
        $responses->questionobj = $questionobj;
        return $responses;
    }

    /**
     * Set up this class with the saved test responses for the given question.
     */
    public static function get_responses_by_ids($responseids) {
        global $DB;
        $responses = $DB->get_records_list('qtype_pmatch_test_responses', 'id', $responseids, 'id ASC');
        return self::data_to_responses($responses);
    }

    /**
     * Convert the passed data
     * @param array $data data records to convert
     * @return test_response[] array of convert records as test_response objects
     */
    public static function data_to_responses($data) {
        $responses = array();
        foreach ($data as $datarow) {
            $response = \qtype_pmatch\test_response::create($datarow);
            $responses[$response->id] = $response;
        }
        return $responses;
    }

    /**
     * Save given responses to the database and return feedback on number saved and duplicates.
     *
     * Ugly to combine saving to the database with validating data but works for now.
     */
    public static function add_responses ($responses) {
        global $DB;

        $feedback = new \stdClass();
        $feedback->duplicates = array();
        $feedback->saved = 0;
        $count = 0;
        // Loop the responses.
        foreach ($responses as $response) {
            $count++;
            // There could be matching responses in the DB. Ugly to have a DB call in a for loop.
            // but seemed best compromise since this is a rare function.
            $id = $DB->get_field_select('qtype_pmatch_test_responses', 'id', 'response=? AND questionid=?',
                    array($response->response, $response->questionid));
            // Check for duplicates.
            if ($id) {
                // Record duplicate response against it's number in the saved array.
                $feedback->duplicates[$count] = $response->response;
                continue;
            }

            // Save unique response.
            $DB->insert_record('qtype_pmatch_test_responses', $response);
            $feedback->saved++;
        }
        return $feedback;
    }

    /**
     * Update the database with the given test_response data
     * @param \test_response $response updated response object
     */
    public static function update_response($response) {
        global $DB;
        return $DB->update_record('qtype_pmatch_test_responses', $response);
    }

    /**
     * Delete test_responses from the database with the given ids
     * @param array $responseids ids of responses to delete
     */
    public static function delete_responses_by_ids ($responseids) {
        global $DB;
        return $DB->delete_records_list('qtype_pmatch_test_responses', 'id', $responseids);
    }

    /**
     * Return test response grade summary counts for the given question.
     * @return $counts \stdClass
     */
    public static function get_grade_summary_counts($question) {
        global $DB;
        $counts = new \stdClass();

        // Get graded count.
        $sqlgraded = "SELECT COUNT(1) FROM {qtype_pmatch_test_responses}
                WHERE questionid = ? AND expectedfraction IS NOT NULL AND gradedfraction IS NOT NULL";
        $params = array('questionid' => $question->id);
        $counts->graded = $DB->count_records_sql($sqlgraded, $params);

        // Get total responses.
        $counts->total = $DB->count_records('qtype_pmatch_test_responses', $params);
        $counts->ungraded = $counts->total - $counts->graded;

        $params['expectedfraction'] = 1;
        $params['gradedfraction'] = 1;
        $counts->correctlymarkedright = $DB->count_records('qtype_pmatch_test_responses', $params);

        $params['expectedfraction'] = 0;
        $params['gradedfraction'] = 0;
        $counts->correctlymarkedwrong = $DB->count_records('qtype_pmatch_test_responses', $params);

        $counts->correct = $counts->correctlymarkedright + $counts->correctlymarkedwrong;

        // Get human marks v computer marks.
        // Remove expectedfraction and gradedfraction as we are using count_records_sql and excluding null values.
        unset($params['expectedfraction']);
        unset($params['gradedfraction']);
        $sqlhumanmarkedwrong = $sqlgraded . " AND expectedfraction = 0 AND gradedfraction IS NOT NULL";
        $counts->humanmarkedwrong = $DB->count_records_sql($sqlhumanmarkedwrong, $params);

        $sqlhumanmarkedright = $sqlgraded . " AND expectedfraction = 1 AND gradedfraction IS NOT NULL";
        $counts->humanmarkedright = $DB->count_records_sql($sqlhumanmarkedright, $params);

        $counts->accuracy = 0;
        if ($counts->correct && $counts->graded) {
            $counts->accuracy = round($counts->correct / $counts->graded * 100);
        }
        return $counts;
    }

    /**
     * Does the given question have linked test responses?
     * @return bool
     */
    public static function has_responses($question) {
        global $DB;
        if (!isset($question->id)) {
            return false;
        }

        // Get correct count.
        $params = array('questionid' => $question->id);

        // Get total responses.
        return $DB->record_exists('qtype_pmatch_test_responses', $params);
    }

    /**
     * Grade the given response with the given question.
     * @param test_response $response response object to grade
     * @param qtype_pmatch_question question to do the grading
     */
    public static function grade_response($response, $question) {
        list($actualmark, $notused) = $question->grade_response(array('answer' => $response->response));
        $response->set_gradedfraction($actualmark);
        self::update_response($response);
    }

    /**
     * Grade the responses with the given rule and question.
     * @param array[] test_response $responses response objects to grade
     * @param \question_answer $rule Answer oobject containing the rule to grade with
     * @param qtype_pmatch_question question to do the grading
     */
    public static function grade_responses_by_rule($responses, $rule, $question) {
        foreach ($responses as $response) {
            $match = $question->compare_response_with_answer(array('answer' => $response->response), $rule);
            if ($match && !in_array($rule->id, $response->ruleids)) {
                $response->ruleids[] = $rule->id;
            }
        }
    }

    /**
     * Grade the responses with the given rule and question.
     * @param array[] test_response $responses response objects to grade
     * @param \question_answer $rule Answer oobject containing the rule to grade with
     * @param qtype_pmatch_question question to do the grading
     */
    public static function get_individual_grade_accuracy($responses, $ruleid) {
        $accuracy = array('positive' => 0, 'negative' => 0);
        foreach ($responses as $response) {
            if (!in_array($ruleid, $response->ruleids)) {
                continue;
            }

            if ($response->expectedfraction) {
                $accuracy['positive']++;
            } else {
                $accuracy['negative']++;
            }
        }

        return $accuracy;
    }

    /**
     * Return a look up array linking the id of each respone with the ids of the rules
     * that match it, and an opposite version linking each rule with the response it matches.
     *
     * @param array[] test_response $responses response objects to grade
     * @param \question_answer $rule Answer oobject containing the rule to grade with
     * @param qtype_pmatch_question question to do the grading
     */
    public static function get_rule_matches_for_responses($responseids) {
        global $DB;

        // Get the response ids for the question.
        $sql = "SELECT id, testresponseid, answerid FROM {qtype_pmatch_rule_matches}
                    WHERE testresponseid IN(". implode(',', $responseids) . ")";
        $data = $DB->get_records_sql($sql);
        $matchresponseidstoruleids = array();
        $matchruleidstoresponseids = array();
        foreach ($data as $record) {
            // Match responses to rules.
            // if the matching array hasn't be created, create it.
            if (!array_key_exists($record->testresponseid, $matchresponseidstoruleids)) {
                $matchresponseidstoruleids[$record->testresponseid] = array();
            }
            $matchresponseidtoruleid = $matchresponseidstoruleids[$record->testresponseid];
            if (!in_array($record->answerid, $matchresponseidtoruleid)) {
                $matchresponseidtoruleid[] = $record->answerid;
            }
            $matchresponseidstoruleids[$record->testresponseid] = $matchresponseidtoruleid;

            // Match rules to responses.

            // If the matching array hasn't be created, create it.
            if (!array_key_exists($record->answerid, $matchruleidstoresponseids)) {
                $matchruleidstoresponseids[$record->answerid] = array();
            }
            $matchruleidtoresponseid = $matchruleidstoresponseids[$record->answerid];
            if (!in_array($record->testresponseid, $matchruleidtoresponseid)) {
                $matchruleidtoresponseid[] = $record->testresponseid;
            }
            $matchruleidstoresponseids[$record->answerid] = $matchruleidtoresponseid;

        }

        $matches = array('responseids to ruleids' => $matchresponseidstoruleids,
                'ruleids to responseids' => $matchruleidstoresponseids);
        return $matches;
    }

    /**
     * Update the ruleids field of the given responses using the matches look up
     * array
     *
     * @param array[] test_response $responses response objects to grade
     * @param \question_answer $rule Answer oobject containing the rule to grade with
     * @param qtype_pmatch_question question to do the grading
     */
    public static function update_responses_with_ruleids($responses, $matches) {
        $matchresponseidstoruleids = $matches['responseids to ruleids'];
        foreach ($matchresponseidstoruleids as $responseid => $ruleids) {
            if (!array_key_exists($responseid, $responses)) {
                continue;
            }
            $responses[$responseid]->ruleids = $ruleids;
        }
    }

    public static function load_responses_from_file($filepath, $question) {
        global $CFG;

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            throw new coding_exception('Could not open testquestionresponses CSV file.');
        }

        $alldata = array();
        $responses = array();
        $problems = array();
        $row = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $row += 1;
            $problem = false;
            if ($row == 1) {
                continue; // Skipping header row or comment.
            }

            if (!is_numeric($data[0])) {
                $problems[] = get_string('testquestionuploadexpectedfractionnull', 'qtype_pmatch',
                                    $row);
                $problem = true;
            }
            if (count($data) !== 2) {
                $problems[] = get_string('testquestionuploadrowhastwoitems', 'qtype_pmatch',
                                    array('row' => $row, 'items' => count($data)));
            }

            // Remove special characters.
            $data[1] = fix_utf8($data[1]);
            if (count($data) >= 2 && fix_utf8($data[1]) !== $data[1]) {
                $problems[] = get_string('testquestionuploadrownotvalidutf8', 'qtype_pmatch',
                                    $row);
                $problem = true;
            }

            $alldata[$row] = $data;
            if (!$problem) {
                $response = new \qtype_pmatch\test_response();
                $response->questionid = $question->id;
                $response->response = $data[1];
                $response->expectedfraction = $data[0];
                $responses[] = $response;
            }
        }
        fclose($handle);

        return array($responses, $problems);
    }
}

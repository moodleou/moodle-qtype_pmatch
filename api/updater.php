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
 * Ajax endpoint for updating the human mark for a response.
 *
 * @package question
 * @subpackage qtype_pmatch/updater
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/pmatch/classes/output/testquestion_renderer.php');

require_login();
require_sesskey();

$qid = optional_param('qid', 0, PARAM_INT);
$rid = optional_param('rid', 0, PARAM_INT);
$ef = optional_param('expectedfraction', 0, PARAM_INT);
$question = question_bank::load_question($qid);
$return = array();

header('Content-type: application/json');

if (!$question || !is_a($question->qtype, 'qtype_pmatch')) {
    $return['status'] = 'error';
    $return['data'] = 'Incorrect question id, or not a pattern match question.';
    echo json_encode($return);
    die;
}
if (!question_has_capability_on($question, 'edit')) {
    $return['status'] = 'error';
    $return['data'] = 'You do not have permission to edit this record.';
    echo json_encode($return);
    die;
}
$response = $DB->get_record('qtype_pmatch_test_responses', array('id' => $rid), 'id, expectedfraction');
if (!$response) {
    $return['status'] = 'error';
    $return['data'] = 'The response id:' . $rid . ' does not match a record.';
    echo json_encode($return);
    die;
}
$response->expectedfraction = $ef;
try {
    $DB->update_record('qtype_pmatch_test_responses', $response);
} catch (Exception $e) {
    $return['status'] = 'error';
    $return['data'] = 'Cannot update response id:' . $rid;
    echo json_encode($return);
    die;
}

// Now update the computed mark (though this will never change), as it allows us
// to get the correct row class. It also means that if you change the human mark
// of a response that has not been computer marked yet, the computed mark will be inserted.
$responses = \qtype_pmatch\testquestion_responses::get_responses_by_ids(array($rid));
$response = $responses[$rid];
\qtype_pmatch\testquestion_responses::grade_response($response, $question);
\qtype_pmatch\testquestion_responses::save_rule_matches($question, array($rid));
$options = new \qtype_pmatch\testquestion_options($question);
$table = new \qtype_pmatch\testquestion_table($question, $responses, $options);
// Counts could be returned as the lang string 'testquestionresultssummary', and that
// would mean any changes in the string would not need to be replicated in updater.js,
// but it was felt that just passing an object of integers is better.
$counts = \qtype_pmatch\testquestion_responses::get_question_grade_summary_counts($question);

$return['status'] = 'success';
$return['ef'] = $response->expectedfraction;
$return['gf'] = $response->gradedfraction;
$return['rowclass'] = $table->get_row_class($response);
$return['counts'] = $counts;
echo json_encode($return);

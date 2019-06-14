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
 * Serve question type files
 *
 * @since      2.0
 * @package   qtype_pmatch
 * @copyright  2012 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Checks file access for pattern-match questions.
 *
 * Standard callback for question types.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 */
function qtype_pmatch_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_pmatch', $filearea, $args, $forcedownload, $options);
}

/**
 * Used by the testquestion.php and uploadresponse.php scripts to do some initialisation
 * that is needed on all of them.
 *
 * @param qtype_pmatch_question $question the question.
 * @return array page context, and URL parameters.
 */
function qtype_pmatch_setup_question_test_page($question) {
    global $PAGE;

    $urlparams = array('questionid' => $question->id);

    // Were we given a particular context to run the question in?
    // This affects things like filter settings, or forced theme or language.
    $qcontext = $question->get_context();
    if ($cmid = optional_param('cmid', 0, PARAM_INT)) {
        $cm = get_coursemodule_from_id(false, $cmid);
        require_login($cm->course, false, $cm);
        $context = context_module::instance($cmid);
        $urlparams['cmid'] = $cmid;

    } else if ($courseid = optional_param('courseid', 0, PARAM_INT)) {
        require_login($courseid);
        $context = context_course::instance($courseid);
        $urlparams['courseid'] = $courseid;

    } else if ($qcontext->contextlevel == CONTEXT_MODULE) {
        $cm = get_coursemodule_from_id(false, $qcontext->instanceid);
        require_login($cm->course, false, $cm);
        $context = $qcontext;
        $urlparams['cmid'] = $cm->id;

    } else if ($qcontext->contextlevel == CONTEXT_COURSE) {
        require_login($qcontext->instanceid);
        $context = $qcontext;
        $urlparams['courseid'] = $courseid;

    } else {
        require_login();
        $context = $question->get_context();
        $PAGE->set_context($context);
        // Note that in the other cases, require_login will set the correct page context.
    }

    return array($context, $urlparams);
}

/**
 * Renders element for inline editing.
 *
 * @param string $itemtype type of response item.
 * @param int $itemid an item id.
 * @param mixed $newvalue new given response.
 * @return string the inplace editable response.
 */
function qtype_pmatch_inplace_editable($itemtype, $itemid, $newvalue) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot . '/question/type/pmatch/externallib.php');

    if ($itemtype === 'responsetable') {
        $responses = \qtype_pmatch\testquestion_responses::get_responses_by_ids([$itemid]);
        $response = $responses[$itemid];
        $question = \question_bank::load_question($response->questionid);
        $context = $question->get_context();
        \external_api::validate_context($context);
        require_capability('moodle/question:editall', $context);
        // Clean input and update the record.
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);
        $newvalue = trim($newvalue);

        if ($newvalue !== $response->response) {
            if (!strlen($newvalue) > 0) {
                throw new moodle_exception('error:blank', 'qtype_pmatch');
            } else {
                $duplicated = \qtype_pmatch\testquestion_responses::check_duplicate_response(
                        $response->questionid, $newvalue);
                if ($duplicated) {
                    throw new moodle_exception('testquestionformduplicateresponse', 'qtype_pmatch');
                }
            }
            $response->response = $newvalue;
            $DB->update_record('qtype_pmatch_test_responses',
                    (object) ['id' => $itemid, 'response' => $newvalue]);
            $result = qtype_pmatch_external::update_computed_mark_and_get_row_response($response->id, $question, null);
            // An json string pass value to updater.js file.
            $responsevalue = json_encode(['html' => $result['html'],
                    'summary' => get_string('testquestionresultssummary', 'qtype_pmatch', $result['counts'])]);
        } else {
            $responsevalue = $response->response;
        }

        // Prepare the element for the output.
        $editresponse = get_string('testquestioneditresponse', 'qtype_pmatch');
        return new \core\output\inplace_editable('qtype_pmatch', 'responsetable', $response->id,
                true, $response->response, $responsevalue, $editresponse, $editresponse);
    }

    throw new coding_exception('Unexpected item type in qtype_pmatch_inplace_editable.');
}

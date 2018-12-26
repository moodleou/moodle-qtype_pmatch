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
 * This page allows responses to be tested against the rules contained in the question.
 * The user can select one or more responses, and have the computer mark these, or
 * delete them (so long as the user has edit capability).
 *
 * The grading statistics show how accurately the question has marked the currently
 * graded responses.
 *
 * The human mark is the bench mark by which the computer is judged and we assume
 * that the human mark is the correct mark to give.
 *
 * The table of responses can be sorted, paged, and manipulated with the options in the top
 * section of the page.
 *
 * @package   qtype_pmatch
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_OUTPUT_BUFFERING', true);

// Login is checked in qtype_pmatch_setup_question_test_page but CodeChecker can't see that.
// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/pmatch/lib.php');
require_once($CFG->dirroot . '/question/type/pmatch/classes/output/testquestion_renderer.php');

$questionid = required_param('id', PARAM_INT);
$download = optional_param('download', '', PARAM_RAW);
$questiondata = $DB->get_record('question', array('id' => $questionid), '*', MUST_EXIST);
if ($questiondata->qtype != 'pmatch') {
    throw new coding_exception('That is not a pattern-match question.');
}
$question = question_bank::load_question($questionid);

// Process any other URL parameters, and do require_login.
list($context, $urlparams) = qtype_pmatch_setup_question_test_page($question);

$url = new moodle_url('/question/type/pmatch/testquestion.php', array('id' => $questionid));
$PAGE->set_pagelayout('popup');
$PAGE->set_url('/question/type/pmatch/testquestion.php', array('id' => $questionid));

// Check permissions after initialising $PAGE so messages (not exceptions) can be rendered.
$canview = question_has_capability_on($questiondata, 'view');
try {
    question_require_capability_on($questiondata, 'view');
} catch (moodle_exception $e) {
    if (defined('BEHAT_SITE_RUNNING')) {
            echo $OUTPUT->header();
            echo get_string('nopermissions', 'error', 'view');
            echo $OUTPUT->footer();
            exit;
    } else {
        throw $e;
    }
}

$PAGE->set_title(get_string('testquestionformtitle', 'qtype_pmatch'));
$PAGE->set_heading(get_string('testquestionformtitle', 'qtype_pmatch'));

$output = $PAGE->get_renderer('qtype_pmatch', 'testquestion');
$controller = new \qtype_pmatch\testquestion_controller($question, $context);

if ($download) {
    $controller->download_data($download, format_string($questiondata->name));
    die();
}

echo $output->header();
echo $output->heading(get_string('testquestionformtitle', 'qtype_pmatch') . ': ' .
        get_string('testquestionheader', 'qtype_pmatch', format_string($questiondata->name)));

echo $output->get_display_options_form($controller);

echo $output->get_responses_heading($question);
echo $output->get_grade_summary($question);

echo $output->get_responses_table_form($controller);

echo $output->footer();

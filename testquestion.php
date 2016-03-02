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
 * This is a quick and dirty script to test a question against a list of
 * responses in a .csv file.
 *
 * The CSV file should contain two columns, the first contains 0 or 1 (or
 * any number between) for whether that response should be considered correct.
 * The second column contains the response. The first row in the file is ignored
 * (on the assumption that it contains the column headers "mark","response".)
 *
 * @package   qtype_pmatch
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/question/type/pmatch/classes/output/testquestion_renderer.php');

$questionid = required_param('id', PARAM_INT);
$questiondata = $DB->get_record('question', array('id' => $questionid), '*', MUST_EXIST);
if ($questiondata->qtype != 'pmatch') {
    throw new coding_exception('That is not a pattern-match question.');
}
require_login();
$question = question_bank::load_question($questionid);
$context = context::instance_by_id($question->contextid);

$url = new moodle_url('/question/type/pmatch/testquestion.php', array('id' => $questionid));
$PAGE->set_pagelayout('popup');
$PAGE->set_url('/question/type/pmatch/testquestion.php', array('id' => $questionid));
$PAGE->set_context($context);

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

// Display headers.
echo $output->header();
echo $output->heading(get_string('testquestionformtitle', 'qtype_pmatch') . ': ' .
        get_string('testquestionheader', 'qtype_pmatch', format_string($questiondata->name)));

$output->init($question);
echo $output->render_display_options();

// Display link to upload  responses.
echo html_writer::tag('p', html_writer::link(new moodle_url('/question/type/pmatch/uploadresponses.php',
                        array('id' => $questionid)), 'Upload responses'));

echo html_writer::tag('p', get_string('showingresponsesforquestion', 'qtype_pmatch', $question->name));
echo $output->render_grade_summary();
echo $output->render_table();

echo $output->footer();

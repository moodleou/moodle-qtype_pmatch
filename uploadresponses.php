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

/**
 * The upload form.
 *
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_test_form extends moodleform {
    protected function definition() {
        $this->_form->addElement('header', 'header', get_string('testquestionformheader', 'qtype_pmatch'));

        $this->_form->addElement('static', 'help', '', get_string('testquestionforminfo', 'qtype_pmatch'));

        $this->_form->addElement('filepicker', 'responsesfile', get_string('testquestionformuploadlabel', 'qtype_pmatch'));
        $this->_form->addRule('responsesfile', null, 'required', null, 'client');

        $this->_form->addElement('hidden', 'id', 0);
        $this->_form->setType('id', PARAM_INT);

        $this->_form->addElement('submit', 'submitbutton', get_string('testquestionuploadresponses', 'qtype_pmatch'));
    }
}

$questionid = required_param('id', PARAM_INT);

$questiondata = $DB->get_record('question', array('id' => $questionid), '*', MUST_EXIST);
if ($questiondata->qtype != 'pmatch') {
    throw new coding_exception('That is not a pattern-match question.');
}

require_login();
question_require_capability_on($questiondata, 'view');
$canedit = question_has_capability_on($questiondata, 'edit');

$question = question_bank::load_question($questionid);
$context = context::instance_by_id($question->contextid);

$PAGE->set_url('/question/type/pmatch/uploadresponses.php', array('id' => $questionid));
$PAGE->set_context($context);
$PAGE->set_title(get_string('testquestionformtitle', 'qtype_pmatch'));
$PAGE->set_heading(get_string('testquestionformtitle', 'qtype_pmatch'));

if ($context->contextlevel == CONTEXT_MODULE) {
    // Calling $PAGE->set_context should be enough, but it seems that it is not.
    // Therefore, we get the right $cm and $course, and set things up ourselves.
    $cm = get_coursemodule_from_id(false, $context->instanceid, 0, false, MUST_EXIST);
    $PAGE->set_cm($cm, $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST));
}

$form = new qtype_pmatch_test_form($PAGE->url);
$form->set_data(array('id' => $questionid));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('testquestionformtitle', 'qtype_pmatch') . ': ' .
        get_string('testquestionheader', 'qtype_pmatch', format_string($questiondata->name)));

// Display link back to test question.
echo html_writer::tag('p', html_writer::link(new moodle_url('/question/type/pmatch/testquestion.php',
        array('id' => $questionid)), 'Back to Test question'));

if ($fromform = $form->get_data()) {
    $filename = $form->get_new_filename('responsesfile');

    make_temp_directory('questionimport');
    $responsefile = "{$CFG->tempdir}/questionimport/{$filename}";
    if (!$result = $form->save_file('responsesfile', $responsefile, true)) {
        throw new moodle_exception('uploadproblem');
    }

    list($responses, $problems) = qtype_pmatch\test_responses::load_responses_from_file($responsefile, $question);

    // Save responses to the database.
    $feedback = \qtype_pmatch\test_responses::add_responses($responses);
    $feedback->problems = $problems;

    // Display feedback.
    echo html_writer::div(get_string('savedxresponses', 'qtype_pmatch', ($feedback->saved)));
    if (count($feedback->duplicates)) {
        echo html_writer::div(get_string('xresponsesduplicated', 'qtype_pmatch', (count($feedback->duplicates))));
        echo html_writer::alist($feedback->duplicates);
    }

    if (count($feedback->problems)) {
        echo html_writer::div(get_string('xresponsesproblems', 'qtype_pmatch', (count($feedback->problems))));
        echo html_writer::alist($feedback->problems);
    }

    echo $OUTPUT->heading(get_string('testquestionuploadanother', 'qtype_pmatch'));
}

$form->display();

// Display link back to test question.
echo html_writer::tag('p', html_writer::link(new moodle_url('/question/type/pmatch/testquestion.php',
        array('id' => $questionid)), 'Back to Test question'));

echo $OUTPUT->footer();

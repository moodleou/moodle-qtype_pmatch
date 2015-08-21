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

        $this->_form->addElement('submit', 'submitbutton', get_string('testquestionformsubmit', 'qtype_pmatch'));
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

$PAGE->set_url('/question/type/pmatch/testquestion.php', array('id' => $questionid));
$PAGE->set_context($context);
$PAGE->set_title(get_string('testquestionformtitle', 'qtype_pmatch'));
$PAGE->set_heading(get_string('testquestionformtitle', 'qtype_pmatch'));

$form = new qtype_pmatch_test_form($PAGE->url);
$form->set_data(array('id' => $questionid));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('testquestionheader', 'qtype_pmatch', format_string($questiondata->name)));
echo '<p>' . $PAGE->get_renderer('core_question')->question_preview_link(
        $question->id, $context, true) . '</p>';

if ($fromform = $form->get_data()) {
    $filename = $form->get_new_filename('responsesfile');

    make_temp_directory('questionimport');
    $responsefile = "{$CFG->tempdir}/questionimport/{$filename}";
    if (!$result = $form->save_file('responsesfile', $responsefile, true)) {
        throw new moodle_exception('uploadproblem');
    }

    $handle = fopen($responsefile, 'r');
    if (!$handle) {
        throw new coding_exception('Could not open CSV file.');
    }

    $alldata = array();
    $problems = array();
    $row = 0;
    while (($data = fgetcsv($handle)) !== false) {
        $row += 1;
        if ($row == 1) {
            continue; // Skipping header row or comment.
        }

        if (count($data) != 2 || !is_numeric($data[0])) {
            $problems[] = 'Each row should contain exactly two items, ' .
                    'a numerical mark and a response. Row ' . $row . ' contains ' .
                    count($data) . ' items.';
        }

        if (count($data) >= 2 && fix_utf8($data[1]) !== $data[1]) {
            $problems[] = 'The response in row ' . $row .
                    ' contains unrecognised special characters. The input must be valid UTF-8.';
        }

        $alldata[$row] = $data;
    }
    fclose($handle);
    $rowcount = $row;

    if ($problems) {
        throw new coding_exception(html_writer::alist($problems));
    }

    $table = new html_table();
    $table->head = array(
            get_string('row', 'qtype_pmatch'),
            get_string('testquestionexpectedmark', 'qtype_pmatch'),
            get_string('testquestionactualmark', 'qtype_pmatch'),
            get_string('testquestionresponse', 'qtype_pmatch'));
    $counts = new stdClass();
    $counts->correct = 0;
    $counts->incorrectlymarkedright = 0;
    $counts->incorrectlymarkedwrong = 0;

    $pbar = new progress_bar('testingquestion', 500, true);
    foreach ($alldata as $row => $data) {
        \core_php_time_limit::raise(60);

        list($expectedmark, $response) = $data;
        list($actualmark, $notused) = $question->grade_response(array('answer' => $response));

        $table->data[] = array($row, $expectedmark, 0 + $actualmark, s($response));
        $table->rowclasses[] = 'qtype_pmatch-selftest-' . ($expectedmark == $actualmark ? 'ok' : 'bad');

        if ($expectedmark == $actualmark) {
            $counts->correct += 1;
        } else if ($expectedmark < $actualmark) {
            $counts->incorrectlymarkedright += 1;
        } else {
            $counts->incorrectlymarkedwrong += 1;
        }

        $pbar->update($row, $rowcount, get_string('processingxofy', 'qtype_pmatch',
                array('row' => $row, 'total' => $rowcount)));
    }

    echo $OUTPUT->heading(get_string('testquestionheader', 'qtype_pmatch', s($filename)));
    echo html_writer::table($table);
    echo '<p>' . get_string('testquestionresultssummary','qtype_pmatch', $counts) . '</p>';

    echo $OUTPUT->heading(get_string('testquestionuploadanother', 'qtype_pmatch'));
}

$form->display();
echo $OUTPUT->footer();

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
 * This script allows an author to upload a .csv file listing marked test responses to a question.
 * The responses are graded using the current rules and rule matches are recorded.
 * This allows calculation and display of the accuracy of the question and each rule.
 *
 * The CSV file should contain two columns, the first contains 0 or 1 (or
 * any number between) for whether that response should be considered correct.
 * The second column contains the response. The first row in the file is ignored
 * (on the assumption that it contains the column headers "mark","response".)
 *
 * @package   qtype_pmatch
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Login is checked in qtype_pmatch_setup_question_test_page but CodeChecker can't see that.
// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/question/type/pmatch/lib.php');

/**
 * The upload form.
 */
class upload_form extends moodleform {
    protected function definition() {
        $this->_form->addElement('header', 'header',
                get_string('testquestionformheader', 'qtype_pmatch'));
        $this->_form->addElement('static', 'help', '',
                get_string('testquestionforminfo', 'qtype_pmatch'));
        $this->_form->addElement('filepicker', 'responsesfile',
                get_string('testquestionformuploadlabel', 'qtype_pmatch'), null,
                ['accepted_types' => 'csv']);
        $this->_form->addRule('responsesfile', null, 'required', null, 'client');
        $this->_form->addElement('hidden', 'id', 0);
        $this->_form->setType('id', PARAM_INT);
        $this->_form->addElement('submit', 'submitbutton',
                get_string('testquestionuploadresponses', 'qtype_pmatch'));
    }

    /**
     *
     * Override function validation for custom form validation of moodle form
     *
     * @param array $data
     * @param array $files
     * @return array
     * @throws coding_exception
     */
    public function validation($data, $files) {
        global $USER;

        // Initialize.
        $errors = parent::validation($data, $files);
        $errcase = [];
        $fileoption = ['mimetype' => ['text/csv'], 'mincell' => 2, 'column' => 2];
        $context = context_user::instance($USER->id);
        $fs = get_file_storage();
        $draftid = file_get_submitted_draft_itemid('responsesfile');
        $attachfiles = $fs->get_area_files($context->id, 'user', 'draft', $draftid);

        // Handle.
        foreach ($attachfiles as $file) {
            if (!in_array($file->get_filename(), ['.', '..'])) {
                if (!in_array($file->get_mimetype(), $fileoption['mimetype'])) {
                    array_push($errcase, 'format');
                } else {
                    $contents = str_getcsv($file->get_content(), "\n");
                    if (count($contents) < $fileoption['mincell']) {
                        array_push($errcase, 'cell');
                    }
                    foreach ($contents as $content) {
                        $columns = explode(',', $content);
                        $counter = count($columns);
                        if ($counter === $fileoption['column']) {
                            continue;
                        }
                        if ($counter > $fileoption['column']) {
                            array_push($errcase, 'columnbigger');
                            break;
                        } else {
                            array_push($errcase, 'columnless');
                            break;
                        }
                    }
                }
            }
        }

        // Get error case and return.
        if (in_array('format', $errcase)) {
            $errortext = get_string('errorfileformat', 'qtype_pmatch');
        } else {
            $errortextarr = [];
            if (in_array('cell', $errcase)) {
                $errortextarr[] = get_string('errorfilecell', 'qtype_pmatch');
            }
            if (in_array('columnbigger', $errcase)) {
                $errortextarr[] = get_string('errorfilecolumnbigger', 'qtype_pmatch');
            }
            if (in_array('columnless', $errcase)) {
                $errortextarr[] = get_string('errorfilecolumnless', 'qtype_pmatch');
            }
            $errortext = implode('<br>', $errortextarr);
        }
        if ($errortext) {
            $errors['responsesfile'] = $errortext;
        }
        return $errors;
    }
}

$questionid = required_param('id', PARAM_INT);

$questiondata = $DB->get_record('question', array('id' => $questionid), '*', MUST_EXIST);
if ($questiondata->qtype != 'pmatch') {
    throw new coding_exception('That is not a pattern-match question.');
}
$question = question_bank::load_question($questionid);

// Process any other URL parameters, and do require_login.
list($context, $urlparams) = qtype_pmatch_setup_question_test_page($question);
question_require_capability_on($questiondata, 'edit');

$url = new moodle_url('/question/type/pmatch/uploadresponses.php', array('id' => $questionid));
$title = get_string('testquestionformtitle', 'qtype_pmatch');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$form = new upload_form();
$form->set_data(array('id' => $questionid));

$renderer = $PAGE->get_renderer('qtype_pmatch');
$link = $renderer->back_to_test_question_link($questionid);

echo $OUTPUT->header();
echo $OUTPUT->heading($title . ': ' .
        get_string('testquestionheader', 'qtype_pmatch', format_string($questiondata->name)));

// Display link back to test question.
echo $link;

if ($fromform = $form->get_data()) {
    $filename = $form->get_new_filename('responsesfile');

    $path = make_temp_directory('questionimport');
    $responsefile = $path . '/' . $filename;
    if (!$result = $form->save_file('responsesfile', $responsefile, true)) {
        throw new moodle_exception('uploadproblem');
    }

    list($responses, $problems) = \qtype_pmatch\testquestion_responses::load_responses_from_file(
            $responsefile, $question);

    // Save responses to the database.
    $feedback = \qtype_pmatch\testquestion_responses::add_responses($responses);
    $feedback->problems = $problems;
    // Because this process could take a long time if there are a large number of responses
    // and a large number of rules, we could add a spinner or other indicator of progress here.
    // The best rule of thumb is to keep the number of responses under 100 if the number of
    // rules is greater than maybe 10. More responses are OK if there are fewer rules.
    \qtype_pmatch\testquestion_responses::grade_responses_and_save_matches($question);

    echo $renderer->display_feedback($feedback);

    echo $OUTPUT->heading(get_string('testquestionuploadanother', 'qtype_pmatch'));
}

$form->display();

echo $link;

echo $OUTPUT->footer();

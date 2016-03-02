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
 * This file defines the setting form for a {@link qtype_pmatch_testquestion_report}.
 *
 * @package   qtype_pmatch
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Pmatch question type test question report settings form.
 *
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_testquestion_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'preferencespage',
                get_string('reportwhattoinclude', 'quiz'));

        $this->standard_attempt_fields($mform);
        $this->other_attempt_fields($mform);

        $mform->addElement('header', 'preferencesuser',
                get_string('reportdisplayoptions', 'quiz'));

        $this->standard_preference_fields($mform);

        $mform->addElement('submit', 'submitbutton',
                get_string('updatedisplayoptions', 'core_question'));
    }

    protected function standard_attempt_fields(MoodleQuickForm $mform) {

        $stategroup = array(
            $mform->createElement('advcheckbox', 'statematches', '',
                    get_string('testquestionmatches', 'qtype_pmatch')),
            $mform->createElement('advcheckbox', 'statemissedpositive', '',
                    get_string('testquestionincorrectlymarkedwrong', 'qtype_pmatch')),
            $mform->createElement('advcheckbox', 'statemissednegative', '',
                    get_string('testquestionincorrectlymarkedrights', 'qtype_pmatch')),
            $mform->createElement('advcheckbox', 'stateungraded', '',
                    get_string('testquestionungraded', 'qtype_pmatch')),
        );
        $mform->addGroup($stategroup, 'stateoptions',
                get_string('testquestionresponsesthatare', 'qtype_pmatch'), array(' '), false);
        $mform->setDefault('statematches', 1);
        $mform->setDefault('statemissedpositive',    1);
        $mform->setDefault('statemissednegative',   1);
        $mform->setDefault('stateungraded',  1);
    }

    protected function other_attempt_fields(MoodleQuickForm $mform) {
    }

    protected function standard_preference_fields(MoodleQuickForm $mform) {
        $mform->addElement('text', 'pagesize', get_string('pagesize', 'quiz'));
        $mform->setType('pagesize', PARAM_INT);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!(
                $data['statematches'] || $data['statemissedpositive'] || $data['statemissednegative'] || $data['stateungraded'])) {
            $errors['stateoptions'] = get_string('reportmustselectstate', 'quiz');
        }

        return $errors;
    }

}

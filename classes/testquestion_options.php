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

namespace qtype_pmatch;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to store options for {@link \qtype_pmatch\testquestion_controller}.
 * Design references are:
 * mod_quiz_attempts_report_options in mod/quiz/report/attemptsreport_options.php
 * quiz_overview_options in mod/quiz/report/overview/overview_options.php
 *
 * @package   qtype_pmatch
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testquestion_options {

    /** @var object the settings for the question we are reporting on. */
    public $question;

    /**
     * @var array form field name => corresponding type_pmatch_testresponses:: state constant.
     */
    protected static $statefields = array(
            'statematches' => \qtype_pmatch\testquestion_response::MATCHED,
            'statemissedpositive' => \qtype_pmatch\testquestion_response::MISSED_POSITIVE,
            'statemissednegative' => \qtype_pmatch\testquestion_response::MISSED_NEGATIVE,
            'stateungraded' => \qtype_pmatch\testquestion_response::UNGRADED
    );

    /**
     * @var array|null of quiz_attempt::IN_PROGRESS, etc. constants. null means
     *      no restriction.
     */
    public $states = array(\qtype_pmatch\testquestion_response::MATCHED, \qtype_pmatch\testquestion_response::MISSED_POSITIVE,
            \qtype_pmatch\testquestion_response::MISSED_NEGATIVE, \qtype_pmatch\testquestion_response::UNGRADED);

    /** @var int Number of attempts to show per page. */
    public $pagesize = \qtype_pmatch\testquestion_controller::DEFAULT_PAGE_SIZE;

    /** @var string whether the data should be downloaded in some format, or '' to display it. */
    public $download = '';

    /** @var bool whether the report table should have a column of checkboxes. */
    public $checkboxcolumn = true;

    /**
     * Constructor.
     * @param object $question the settings for the question being reported on.
     * @param object $context the context object for the question being reported on.
     */
    public function __construct($question) {
        $this->question = $question;
    }

    protected function get_url_params() {
        $params = array();
        $params['id'] = $this->question->id;
        if ($this->states) {
            $params['states'] = implode('-', $this->states);
        }
        return $params;
    }

    public function get_initial_form_data() {
        $toform = new \stdClass();
        $toform->pagesize   = $this->pagesize;
        if ($this->states) {
            foreach (self::$statefields as $field => $state) {
                $toform->$field = in_array($state, $this->states);
            }
        }
        return $toform;
    }

    public function setup_from_form_data($fromform) {
        $this->pagesize   = $fromform->pagesize;
        $this->states = array();
        foreach (self::$statefields as $field => $state) {
            if (!empty($fromform->$field)) {
                $this->states[] = $state;
            }
        }
    }

    public function setup_from_params() {
        $this->pagesize = optional_param('pagesize', $this->pagesize, PARAM_INT);
        $states = optional_param('states', '', PARAM_ALPHAEXT);
        if (!empty($states)) {
            $this->states = explode('-', $states);
        }
    }

    /**
     * Get the URL to show the report with these options.
     * @return moodle_url the URL.
     */
    public function get_url() {
        return new \moodle_url('/question/type/pmatch/testquestion.php', $this->get_url_params());
    }

    /**
     * Process the data we get when the settings form is submitted. This includes
     * updating the fields of this class, and updating the user preferences
     * where appropriate.
     * @param object $fromform The data from $mform->get_data() from the settings form.
     */
    public function process_settings_from_form($fromform) {
        $this->setup_from_form_data($fromform);
        $this->resolve_dependencies();
        $this->update_user_preferences();
    }

    /**
     * Set up this preferences object using optional_param (using user_preferences
     * to set anything not specified by the params.
     */
    public function process_settings_from_params() {
        $this->setup_from_user_preferences();
        $this->setup_from_params();
        $this->resolve_dependencies();
    }

    /**
     * Set the fields of this object from the user's preferences.
     * (For those settings that are backed by user-preferences).
     */
    public function setup_from_user_preferences() {
        $this->pagesize = get_user_preferences('qtype_pmatch_testquestion_pagesize', $this->pagesize);
    }

    /**
     * Update the user preferences so they match the settings in this object.
     * (For those settings that are backed by user-preferences).
     */
    public function update_user_preferences() {
        set_user_preference('qtype_pmatch_testquestion_pagesize', $this->pagesize);
    }

    /**
     * Check the settings, and remove any 'impossible' combinations.
     */
    public function resolve_dependencies() {
        $cleanstates = array();
        foreach (self::$statefields as $state) {
            if (in_array($state, $this->states)) {
                $cleanstates[] = $state;
            }
        }
        $this->states = $cleanstates;
        if (count($this->states) == count(self::$statefields)) {
            // If all states have been selected, then there is no constraint
            // required in the SQL, so clear the array.
            $this->states = null;
        }
        if ($this->pagesize < 1) {
            $this->pagesize = \qtype_pmatch\testquestion_controller::DEFAULT_PAGE_SIZE;
        }
        // We only want to show the checkbox to delete attempts
        // if the user has permissions and if the report mode is showing attempts.
        $this->checkboxcolumn = question_require_capability_on($this->question, 'edit');
    }
}

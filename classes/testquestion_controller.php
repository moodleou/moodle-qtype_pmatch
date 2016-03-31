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

require_once($CFG->dirroot . '/lib/questionlib.php');

/**
 * Defines the \qtype_pmatch\testquestion_controller class.
 * Manages the testquestion page - particularly the forms actions.
 *
 * @package    qtype_pmatch
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testquestion_controller {

    /** @var int default page size (number of responses to display on a page). */
    const DEFAULT_PAGE_SIZE = 50;

    /** @var object the question. */
    public $question;

    /** @var object the testresponses. */
    protected $testresponses;

    /** @var object the page context. */
    protected $context;

    /** @var object The options form. */
    protected $optionsform;

    /** @var object The responses table form. */
    protected $responsestableform;

    /** @var object The options. */
    protected $options;

    public function __construct($question, $context) {
        $this->question = $question;
        $this->testresponses = \qtype_pmatch\testquestion_responses::create_for_question($question);
        $this->context = $context;
        $pagesize = optional_param('pagesize', self::DEFAULT_PAGE_SIZE, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $this->options = new \qtype_pmatch\testquestion_options($question);
        $this->optionsform = new \qtype_pmatch\testquestion_options_form($this->get_base_url());
        $this->responsestableform = new \qtype_pmatch\testquestion_table($question,
                $this->testresponses, $this->options);
    }

    public function handle_display_options_form () {
        // Handle any options form submission.
        if ($fromform = $this->optionsform->get_data()) {
            $this->options->process_settings_from_form($fromform);
        } else {
            $this->options->process_settings_from_params();
        }
        $this->optionsform->set_data($this->options->get_initial_form_data());
        // Print the options form.
        print $this->optionsform->display();
    }

    public function handle_responses_table_form() {
        // Handle any attempts form submission.
        $this->process_response_table_actions($this->options->get_url());
        // Note the attempts form is wrapped around this responses table - see wrap_html_start().
        $this->responsestableform->out(0, false);
    }

    /**
     * Get the base URL for this report.
     * @return moodle_url the URL.
     */
    protected function get_base_url() {
        return new \moodle_url('/question/type/pmatch/testquestion.php',
                array('id' => $this->question->id));
    }

    /**
     * Process the results of the form.
     * @return void.
     */
    protected function process_response_table_actions($redirecturl) {
        global $OUTPUT;
        if (optional_param('test', 0, PARAM_BOOL) && confirm_sesskey()) {
            if ($responseids = optional_param_array('responseid', array(), PARAM_INT)) {
                $this->print_grading_responses_progressbar($responseids);
                \qtype_pmatch\testquestion_responses::save_rule_matches($this->question, $responseids);
                echo $OUTPUT->continue_button($redirecturl);
                echo $OUTPUT->footer();
                exit;
            }
        }

        if (optional_param('delete', 0, PARAM_BOOL) && confirm_sesskey()) {
            if ($responseids = optional_param_array('responseid', array(), PARAM_INT)) {
                question_require_capability_on($this->question, 'edit');
                \qtype_pmatch\testquestion_responses::delete_responses_by_ids($responseids);
                echo get_string('testquestiondeletedresponses', 'qtype_pmatch');
                echo $OUTPUT->continue_button($redirecturl);
                echo $OUTPUT->footer();
                exit;
            }
        }
    }

    protected function print_grading_responses_progressbar($responseids) {
        $responses = \qtype_pmatch\testquestion_responses::get_responses_by_ids($responseids);
        $pbar = new \progress_bar('testingquestion', 500, true);
        $row = 0;
        $rowcount = count($responseids);
        // Release the session, so the user can do other things while this runs.
        \core\session\manager::write_close();

        foreach ($responses as $response) {
            \core_php_time_limit::raise(60);
            $row++;
            \qtype_pmatch\testquestion_responses::grade_response($response, $this->question);
            $pbar->update($row, $rowcount, get_string('processingxofy', 'qtype_pmatch',
                    array('row' => $row, 'total' => $rowcount, 'response' => $response->response)));
        }
    }
}

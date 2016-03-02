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
 * Render methods.
 *
 * @package    qtype_pmatch
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/questionlib.php');
require_once($CFG->dirroot . '/question/type/pmatch/classes/testquestion_options.php');
require_once($CFG->dirroot . '/question/type/pmatch/classes/testquestion_form.php');
require_once($CFG->dirroot . '/question/type/pmatch/classes/testquestion_table.php');

class qtype_pmatch_testquestion_renderer extends plugin_renderer_base {

    /** @var int default page size for reports. */
    const DEFAULT_PAGE_SIZE = 50;

    /** @var object the settings for the question we are reporting on. */
    public $question;

    /** @var object the testresponses handler. */
    protected $testresponses;

    /** @var object the page context . */
    protected $context;

    /** @var qtype_pmatch_testquestion_form The form to use. */
    protected $form;

    /** @var qtype_pmatch_testresponses_options The options to use. */
    protected $options;

    public function init($question) {
        $this->question = $question;
        $this->testresponses = \qtype_pmatch\test_responses::create_for_question($question);
        $this->context = $this->page->context;
        $pagesize = optional_param('pagesize', self::DEFAULT_PAGE_SIZE, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);

        // Assemble the options requried to reload this page.
        $optparams = array('page');
        foreach ($optparams as $param) {
            if ($$param) {
                $this->viewoptions[$param] = $$param;
            }
        }
        if ($pagesize != self::DEFAULT_PAGE_SIZE) {
            $this->viewoptions['pagesize'] = $pagesize;
        }

        $this->form = new qtype_pmatch_testquestion_form($this->get_base_url(),
                array('question' => $question, 'context' => $this->context));

        $this->options = new qtype_pmatch_testresponses_options($this->question, $this->context);

        if ($fromform = $this->form->get_data()) {
            $this->options->process_settings_from_form($fromform);

        } else {
            $this->options->process_settings_from_params();
        }

        $this->form->set_data($this->options->get_initial_form_data());

        $this->process_actions($this->options->get_url());
    }

    public function render_display_options () {
        // Print the display options.
        $this->form->display();
    }

    public function render_table () {
        $table = new qtype_pmatch_testquestion_table($this->question, $this->context,
                $this->testresponses, $this->options, $this->get_base_url());

        // Start output.

        // Print information on the number of existing responses.
        // Use mod/quiz/report/overview/report.php::display() as reference.

        // Print the display options.
        // Use mod/quiz/report/overview/report.php::display() as reference.

        // Construct the SQL.
        list($fields, $from, $where, $params) = $table->base_sql();

        $table->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);
        $table->set_sql($fields, $from, $where, $params);

        // Output the regrade buttons.
        // Use mod/quiz/report/overview/report.php::display() as reference.

        // Define table columns.
        $columns = array();
        $headers = array();

        if (!$table->is_downloading() && $this->options->checkboxcolumn) {
            $columns[] = 'checkbox';
            $headers[] = null;
        }

        $this->add_columns($table, $columns, $headers);

        $this->set_up_table_columns($table, $columns, $headers, $this->get_base_url(), $this->options, false);
        $table->set_attribute('class', 'generaltable generalbox grades');

        $table->out($this->options->pagesize, false);
    }

    /**
     * Add all the user-related columns to the $columns and $headers arrays.
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_columns($table, &$columns, &$headers) {
        if (!$table->is_downloading()) {
            $columns[] = 'gradedfraction';
            $headers[] = get_string('testquestionactualmark', 'qtype_pmatch');
        }

        if (!$table->is_downloading()) {
            $columns[] = 'expectedfraction';
            $headers[] = get_string('testquestionexpectedfraction', 'qtype_pmatch');

            $columns[] = 'response';
            $headers[] = get_string('testquestionresponse', 'qtype_pmatch');
        }
    }

    /**
     * Set the display options for the user-related columns in the table.
     * @param table_sql $table the table being constructed.
     */
    protected function configure_columns($table) {
        $table->column_suppress('id');

        $table->column_class('gradedfraction', 'bold');
        $table->column_class('expectedfraction', 'bold');
        $table->column_class('response', 'bold');
    }

    /**
     * Set up the table.
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns.
     * @param array $headers the columns headings.
     * @param moodle_url $reporturl the URL of this report.
     * @param mod_quiz_attempts_report_options $options the display options.
     * @param bool $collapsible whether to allow columns in the report to be collapsed.
     */
    protected function set_up_table_columns($table, $columns, $headers, $reporturl,
            qtype_pmatch_testresponses_options $options, $collapsible) {
        $table->define_columns($columns);
        $table->define_headers($headers);
        $table->sortable(true, 'uniqueid');

        $table->define_baseurl($options->get_url());

        $this->configure_columns($table);

        $table->column_class('response', 'bold');

        $table->set_attribute('id', 'responses');

        $table->collapsible($collapsible);
    }

    /**
     * Get the base URL for this report.
     * @return moodle_url the URL.
     */
    protected function get_base_url() {
        return new moodle_url('/question/type/pmatch/testquestion.php',
                array('id' => $this->question->id));
    }

    /**
     * Process the results of the form.
     * @return void.
     */
    protected function process_actions($redirecturl) {
        if (optional_param('test', 0, PARAM_BOOL) && confirm_sesskey()) {
            if ($responseids = optional_param_array('responseid', array(), PARAM_INT)) {
                $this->render_grading_responses($responseids);
                echo $this->output->continue_button($redirecturl);
                echo $this->footer();
                exit;
            }
        }

        if (optional_param('delete', 0, PARAM_BOOL) && confirm_sesskey()) {
            if ($responseids = optional_param_array('responseid', array(), PARAM_INT)) {
                question_require_capability_on($this->question, 'edit');
                \qtype_pmatch\test_responses::delete_responses_by_ids($responseids);
                echo get_string('testquestiondeletedresponses', 'qtype_pmatch');
                echo $this->output->continue_button($redirecturl);
                echo $this->footer();
                exit;
            }
        }
    }

    protected function render_grading_responses($responseids) {
        $responses = \qtype_pmatch\test_responses::get_responses_by_ids($responseids);
        $pbar = new progress_bar('testingquestion', 500, true);
        $row = 0;
        $rowcount = count($responseids);
        // Release the session, so the user can do other things while this runs.
        \core\session\manager::write_close();

        foreach ($responses as $response) {
            \core_php_time_limit::raise(60);
            $row++;
            \qtype_pmatch\test_responses::grade_response($response, $this->question);
            $pbar->update($row, $rowcount, get_string('processingxofy', 'qtype_pmatch',
                    array('row' => $row, 'total' => $rowcount, 'response' => $response->response)));
        }
    }

    public function render_grade_summary($responses = array()) {
        $counts = \qtype_pmatch\test_responses::get_grade_summary_counts($this->question);
        return html_writer::tag('p', get_string('testquestionresultssummary', 'qtype_pmatch', $counts));
    }
}

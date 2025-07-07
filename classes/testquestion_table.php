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

require_once($CFG->libdir . '/tablelib.php');

/**
 * class for the table used by the test question feature.
 *
 * @package   qtype_pmatch
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testquestion_table extends \table_sql {

    /** @var object the settings for the question we are reporting on. */
    protected $question;

    /** @var object the testresponses handler. */
    protected $testresponses;

    /** @var object mod_quiz_attempts_report_options the options affecting this report. */
    protected $options;

    /** @var bool whether to include the column with checkboxes to select each attempt. */
    protected $includecheckboxes;

    /**
     * Constructor
     * @param object $question
     * @param testquestion_responses $testresponses
     * @param \qtype_pmatch\testquestion_options $options
     */
    public function __construct($question, $testresponses, \qtype_pmatch\testquestion_options $options) {
        $this->uniqueid = 'qtype-pmatch-testquestion';
        parent::__construct($this->uniqueid);
        $this->question = $question;
        $this->testresponses = $testresponses;
        $this->options = $options;
        $this->includecheckboxes = $options->checkboxcolumn;
    }

    /**
     * Render input checkbox for header.
     *
     * @return string The checkbox for header.
     */
    protected function get_checkbox_header() {
        global $OUTPUT;
        return $OUTPUT->render(new \core\output\checkbox_toggleall(
                'responses', true, [
                    'id' => 'tqheadercheckbox',
                    'value' => 1,
                    'label' => get_string('selectall'),
                    'labelclasses' => 'accesshide',
                ]));
    }

    /**
     * Generate the display of the checkbox column.
     * @param object $response the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_checkbox($response) {
        global $OUTPUT;
        if ($response->id) {
            return $OUTPUT->render(new \core\output\checkbox_toggleall(
                'responses', false, [
                    'name' => 'responseid[]',
                    'value' => $response->id,
                    'label' => get_string('testquestionseletresponsex', 'qtype_pmatch', $response->id),
                    'labelclasses' => 'accesshide',
                ]));
        } else {
            return '';
        }
    }

    /**
     * Generate the display of the expectedfraction column.
     * @param object $response the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_expectedfraction($response) {
        // If expectedfraction is null, then display it with a '-'.
        $expectedfraction = $response->expectedfraction ?? '-';

        if ($this->is_downloading()) {
            return $expectedfraction;
        }

        return \html_writer::tag('a',
                $expectedfraction,
                ['class' => 'updater-ef', 'data-id' => $response->id, 'id' => 'updater-ef_' . $response->id, 'href' => '#',
                        'title' => get_string('testquestionchangescore', 'qtype_pmatch') ]);

    }

    /**
     * Generate the display of the response id colunn
     * @param object $response the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_id($response) {
        return $response->id;
    }

    /**
     * Generate the display of the rules column.
     * @param object $response the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_rules($response) {
        if (testquestion_responses::has_rule_match_for_response(
                    $this->testresponses->rulematches, $response->id)) {
            return implode(',',
                    testquestion_responses::get_matching_rule_indexes_for_response(
                            $this->testresponses, $response->id));
        } else {
            return '';
        }
    }

    #[\Override]
    public function get_row_class($response) {
        $class = 'qtype_pmatch-selftest-';
        if ($response->expectedfraction === $response->gradedfraction) {
            $class .= 'ok';
        } else if (is_null($response->gradedfraction)) {
            $class .= 'null';
        } else if ($response->expectedfraction == 1 && $response->gradedfraction == 0) {
            $class .= 'missed-negative';
        } else if ($response->expectedfraction == 0 && $response->gradedfraction == 1) {
            $class .= 'missed-positive';
        }
        return $class;
    }

    /**
     * Construct all the parts of the main database query.
     * @return array with 4 elements ($fields, $from, $where, $params) that can be used to
     *      build the actual database query.
     */
    public function base_sql() {
        global $DB;

        $from = '{qtype_pmatch_test_responses}';
        $fields = 'id, expectedfraction, gradedfraction, response';
        $params = ['questionid' => $this->question->id];
        $where = 'questionid = '.$this->question->id;

        if ($this->options->states) {
            $statesqllist = [
                   testquestion_response::MATCHED => '(expectedfraction = gradedfraction)',
                   testquestion_response::MISSED_POSITIVE => '(gradedfraction = 0 AND expectedfraction = 1)',
                   testquestion_response::MISSED_NEGATIVE => '(gradedfraction = 1 AND expectedfraction = 0)',
                   testquestion_response::UNGRADED => '(expectedfraction IS NULL)',
            ];
            $statesql = ' AND (';
            $count = 0;
            foreach ($this->options->states as $state) {
                if (!array_key_exists($state, $statesqllist)) {
                    continue;
                }
                if ($count) {
                    $statesql .= ' OR ';
                }
                $statesql .= $statesqllist[$state];
                $count++;
            }
            $statesql .= ')';
            $where .= $statesql;
        }

        return [$fields, $from, $where, $params];
    }

    #[\Override]
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        $this->set_up_table_form();
        $this->setup();
        $this->query_db($this->options->pagesize, false);
        $this->format_data();
        $this->build_table();
        $this->finish_output();
    }

    /**
     * Format the response column.
     *
     * @param object $response the response data.
     * @return string format new column response.
     */
    public function col_response($response) {
        global $OUTPUT;
        if ($this->is_downloading()) {
            return $response->response;
        }
        $editresponse = get_string('testquestioneditresponse', 'qtype_pmatch');
        $tmpl = new \core\output\inplace_editable('qtype_pmatch', 'responsetable', $response->id,
                true, s($response->response), $response->response, $editresponse, $editresponse);
        $out = $OUTPUT->render($tmpl);

        return $out;
    }

    /**
     * Format the data into test responses classes.
     */
    protected function format_data() {
        $this->rawdata = testquestion_responses::data_to_responses($this->rawdata);
    }

    #[\Override]
    public function get_sort_columns() {
        $sortcolumns = parent::get_sort_columns();
        return $sortcolumns;
    }

    #[\Override]
    public function wrap_html_start() {
        if ($this->is_downloading() || !$this->includecheckboxes) {
            return;
        }
        $url = $this->options->get_url();
        $url->param('sesskey', sesskey());
        echo '<div id="tablecontainer">';
        // The table is wrapped inside the attempts form.
        echo '<form id="attemptsform" method="post" action="' . $url->out_omit_querystring() . '">';
        echo \html_writer::input_hidden_params($url);
    }

    #[\Override]
    public function wrap_html_finish() {
        global $PAGE;
        if ($this->is_downloading() || !$this->includecheckboxes) {
            return;
        }
        $output = $PAGE->get_renderer('qtype_pmatch', 'testquestion');
        echo $output->get_table_bottom_buttons($this->question);
        // Close the form.
        echo '</form></div>';
    }

    /**
     * Add all the user-related columns to the $columns and $headers arrays.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_columns(&$columns, &$headers) {
        if (!$this->is_downloading()) {
            // Only export Human mark and Response columns for download file.
            if ($this->options->checkboxcolumn) {
                $columns[] = 'checkbox';
                $headers[] = $this->get_checkbox_header();
                $this->column_nosort[] = 'checkbox';
            }
            $columns[] = 'id';
            $headers[] = get_string('testquestionidlabel', 'qtype_pmatch');
            $columns[] = 'rules';
            $headers[] = get_string('testquestionruleslabel', 'qtype_pmatch');
            $columns[] = 'gradedfraction';
            $headers[] = get_string('testquestionactualmark', 'qtype_pmatch');
        }
        $columns[] = 'expectedfraction';
        $headers[] = get_string('testquestionexpectedfraction', 'qtype_pmatch');
        $columns[] = 'response';
        $headers[] = get_string('testquestionresponse', 'qtype_pmatch');
    }

    /**
     * Local set up for the table (called before parent setup).
     */
    protected function set_up_table_form() {
        // Set up the table's SQL.
        list($fields, $from, $where, $params) = $this->base_sql();
        $this->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);
        $this->set_sql($fields, $from, $where, $params);
        // Define table columns and headers.
        $columns = [];
        $headers = [];
        $this->add_columns($columns, $headers);
        $this->define_columns($columns);
        // Add a column class to help distinguish updatable human marks.
        $this->column_class('expectedfraction', 'updater-expectedfraction');
        $this->define_headers($headers);
        // Set up other table parameters.
        $this->define_baseurl($this->options->get_url());
        $this->sortable(true, 'id');
        $this->no_sorting('rules');
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable generalbox grades');
        $this->set_attribute('id', 'responses');
    }

    /**
     * Return row as html for response table.
     *
     * @param $row \stdClass the response to display.
     * @param $curentrow int the index of current editing row.
     * @return string row html to append response table.
     */
    public function get_row_html_for_response_table($row, $curentrow) {
        $columns = [];
        $headers = [];
        $this->currentrow = $curentrow;
        $this->add_columns($columns, $headers);
        $this->define_columns($columns);
        $formattedrow = $this->format_row($row);

        return $this->get_row_html($this->get_row_from_keyed($formattedrow), $this->get_row_class($row));
    }

    #[\Override]
    public function finish_html() {
        if (!$this->started_output) {
            // No data has been added to the table.
            $this->start_output();
        }
        parent::finish_html();
    }
}

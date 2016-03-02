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
 * class for the table used by the test question feature.
 *
 * @package   qtype_pmatch
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * class for the table used by the test question feature.
 *
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_testquestion_table extends table_sql {
    /** @var moodle_url the URL of this report. */
    protected $reporturl;

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
     * @param context $context
     * @param $options
     * @param moodle_url $reporturl
     */
    public function __construct($question, $context, $testresponses, qtype_pmatch_testresponses_options $options, $reporturl) {
        $this->uniqueid = 'qtype-pmatch-testquestion';
        parent::__construct($this->uniqueid);
        $this->question = $question;
        $this->context = $context;
        $this->testresponses = $testresponses;
        $this->reporturl = $reporturl;
        $this->options = $options;
        $this->includecheckboxes = $options->checkboxcolumn;
    }

    /**
     * Generate the display of the checkbox column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_checkbox($response) {
        if ($response->id) {
            return '<input type="checkbox" name="responseid[]" value="'.$response->id.'" />';
        } else {
            return '';
        }
    }

    /**
     * Get any extra classes names to add to this row in the HTML.
     * @param $row array the data for this row.
     * @return string added to the class="" attribute of the tr.
     */
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
        $params = array('questionid' => $this->question->id);
        $where = 'questionid = '.$this->question->id;

        if ($this->options->states) {
            $statesqllist = array(
                   \qtype_pmatch\test_response::MATCHED => '(expectedfraction = gradedfraction)',
                   \qtype_pmatch\test_response::MISSED_POSITIVE => '(gradedfraction = 0 AND expectedfraction = 1)',
                   \qtype_pmatch\test_response::MISSED_NEGATIVE => '(gradedfraction = 1 AND expectedfraction = 0)',
                   \qtype_pmatch\test_response::UNGRADED => '(gradedfraction IS NULL)'
            );
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

        return array($fields, $from, $where, $params);
    }

    /**
     * Convenience method to call a number of methods for you to display the
     * table.
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton='') {
        $this->setup();
        $this->query_db($pagesize, $useinitialsbar);
        $this->format_data();
        $this->build_table();
        $this->finish_output();
    }

    /**
     * Format the data into test question classes.
     */
    protected function format_data() {
        $this->rawdata = \qtype_pmatch\test_responses::data_to_responses($this->rawdata);;

    }

    /**
     * Default by sorting on test response id.
     * @see flexible_table::get_sort_columns()
     */
    public function get_sort_columns() {
        $sortcolumns = parent::get_sort_columns();
        $sortcolumns['id'] = SORT_ASC;
        return $sortcolumns;
    }

    public function wrap_html_start() {
        if ($this->is_downloading() || !$this->includecheckboxes) {
            return;
        }

        $url = $this->options->get_url();
        $url->param('sesskey', sesskey());

        echo '<div id="tablecontainer">';
        echo '<form id="attemptsform" method="post" action="' . $url->out_omit_querystring() . '">';

        echo html_writer::input_hidden_params($url);
        echo '<div>';
    }

    public function wrap_html_finish() {
        if ($this->is_downloading() || !$this->includecheckboxes) {
            return;
        }

        echo '<div id="commands">';
        echo '<a href="javascript:select_all_in(\'DIV\', null, \'tablecontainer\');">' .
                get_string('selectall', 'quiz') . '</a> / ';
        echo '<a href="javascript:deselect_all_in(\'DIV\', null, \'tablecontainer\');">' .
                get_string('selectnone', 'quiz') . '</a> ';
        echo '&nbsp;&nbsp;';
        $this->submit_buttons();
        echo '</div>';

        // Close the form.
        echo '</div>';
        echo '</form></div>';
    }

    /**
     * Output any submit buttons required by the $this->includecheckboxes form.
     */
    protected function submit_buttons() {
        global $PAGE;
        // Test responses.
        if (question_has_capability_on($this->question, 'edit')) {
            echo '<input type="submit" id="testresponsesbutton" name="test" value="' .
                    get_string('testquestionformtestsubmit', 'qtype_pmatch') . '"/>';
        }

        // Delete responses.
        if (question_has_capability_on($this->question, 'edit')) {
            echo '<input type="submit" id="deleteresponsesbutton" name="delete" value="' .
                    get_string('testquestionformdeletesubmit', 'qtype_pmatch') . '"/>';
            $PAGE->requires->event_handler('#deleteresponsesbutton', 'click', 'M.util.show_confirm_dialog',
                    array('message' => get_string('testquestionformdeletecheck', 'qtype_pmatch')));
        }
    }
}

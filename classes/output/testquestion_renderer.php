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
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_pmatch_testquestion_renderer extends plugin_renderer_base {

    public function get_display_options_form($controller) {
        return $controller->handle_display_options_form();
    }

    public function get_responses_table_form($controller) {
        return $controller->handle_responses_table_form();
    }

    public function get_uploadresponses_link($question) {
        return html_writer::tag('p', html_writer::link(new moodle_url('/question/type/pmatch/uploadresponses.php',
                        array('id' => $question->id)), 'Upload responses'));
    }

    public function get_responses_heading($question) {
        return html_writer::tag('h3', get_string('showingresponsesforquestion', 'qtype_pmatch', $question->name));
    }

    /**
     * This looks like (Pos=12/12 Neg=1/1 Unm=180 Acc=100%).
     * @param object $question
     */
    public function get_grade_summary($question) {
        $counts = \qtype_pmatch\testquestion_responses::get_question_grade_summary_counts($question);
        return html_writer::tag('p', get_string('testquestionresultssummary', 'qtype_pmatch', $counts),
                array('id' => 'testquestion_gradesummary'));
    }

    /**
     * Output any submit buttons required by the attempts (responses table) form.
     * @param object $question
     */
    public function get_table_bottom_buttons($question) {
        $html = '';
        if (question_has_capability_on($question, 'edit')) {
            $html .= html_writer::start_div('', array('id' => 'commands'));
            $html = \html_writer::start_tag('p');
            $html .= \html_writer::tag('button',
                    get_string('testquestionformnewresponsebutton', 'qtype_pmatch'),
                    ['id' => 'newresponsebutton']);
            $html .= \html_writer::end_tag('p');
            $html .= html_writer::tag('strong', get_string('withselected', 'question') . ':');
            $html .= html_writer::empty_tag('br');
            // Delete responses.
            $html .= '<input type="submit" id="deleteresponsesbutton" name="delete" value="' .
                get_string('testquestionformdeletesubmit', 'qtype_pmatch') . '"/> ';
            // Test responses.
            $html .= '<input type="submit" id="testresponsesbutton" name="test" value="' .
                    get_string('testquestionformtestsubmit', 'qtype_pmatch') . '"/> ';
            $this->page->requires->event_handler('#deleteresponsesbutton', 'click', 'M.util.show_confirm_dialog',
                    array('message' => get_string('testquestionformdeletecheck', 'qtype_pmatch')));
            $html .= html_writer::end_div();
            // Add ajax updater.
            $this->page->requires->js_call_amd('qtype_pmatch/updater', 'init');
            // Add ajax create response.
            $this->page->requires->js_call_amd('qtype_pmatch/creator', 'init');

            $this->page->requires->strings_for_js(['selectall', 'deselectall'], 'moodle');
            $this->page->requires->strings_for_js(['testquestionresultssummary', 'testquestionformsaveresponsebutton',
                    'testquestionformcancelresponsebutton'], 'qtype_pmatch');
        }
        return $html;
    }
}

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
        $link = new moodle_url('/question/type/pmatch/uploadresponses.php', ['id' => $question->id]);

        return html_writer::tag('input', '', array('value' => get_string('testquestionuploadresponses', 'qtype_pmatch'),
            'type' => 'button', "onclick" => "window.location.href = '" . $link->out(false) . "'", 'class' => 'btn btn-secondary'));
    }

    public function get_responses_heading($question) {
        return html_writer::tag('h3', get_string('showingresponsesforquestion', 'qtype_pmatch', $question->name));
    }

    /**
     * Get the grade summary
     *
     * @param object $question
     * @return string the html grade summary
     * @throws coding_exception\
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
            $html = \html_writer::start_tag('p', ['id' => 'wrapperactionresponse']);
            $html .= html_writer::tag('input', '', array('type' => 'button',
                    'value' => get_string('testquestionformnewresponsebutton', 'qtype_pmatch'),
                    'id' => 'newresponsebutton', 'class' => 'btn btn-secondary m-t-0'));
            $html .= ' ' . $this->get_uploadresponses_link($question);
            $html .= \html_writer::end_tag('p');
            $html .= html_writer::tag('strong', get_string('withselected', 'question') . ':');
            $html .= html_writer::empty_tag('br');
            // Delete responses.
            $html .= '<input type="submit" id="deleteresponsesbutton" class="btn btn-secondary" name="delete" value="' .
                get_string('testquestionformdeletesubmit', 'qtype_pmatch') . '"/> ';
            // Test responses.
            $html .= '<input type="submit" id="testresponsesbutton" class="btn btn-secondary" name="test" value="' .
                    get_string('testquestionformtestsubmit', 'qtype_pmatch') . '"/> ';
            $this->page->requires->event_handler('#deleteresponsesbutton', 'click', 'M.util.show_confirm_dialog',
                    array('message' => get_string('testquestionformdeletecheck', 'qtype_pmatch')));
            $html .= html_writer::end_div();
            // Add ajax updater.
            $this->page->requires->js_call_amd('qtype_pmatch/updater', 'init');
            // Add ajax create response.
            $this->page->requires->js_call_amd('qtype_pmatch/creator', 'init');

            $this->page->requires->strings_for_js(['ok', 'selectall', 'deselectall'], 'moodle');
            $this->page->requires->strings_for_js(['error:title', 'testquestionresultssummary',
                    'testquestionformsaveresponsebutton',
                    'testquestionformcancelresponsebutton'], 'qtype_pmatch');
        }
        return $html;
    }
}

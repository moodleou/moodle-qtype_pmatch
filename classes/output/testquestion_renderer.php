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

use qtype_pmatch\testquestion_controller;

/**
 * Render methods for the question testing tool.
 *
 * @package    qtype_pmatch
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_testquestion_renderer extends plugin_renderer_base {

    /**
     * Render the question testing tool options form.
     *
     * @param testquestion_controller $controller
     * @return string HTML to display.
     */
    public function get_display_options_form(testquestion_controller $controller): string {
        return $controller->handle_display_options_form();
    }

    /**
     * Display the the question testing tool response table.
     *
     * Although this method is in a renderer, it incorrectly directly outputs,
     * rather than returning a string. TODO fix.
     *
     * @param testquestion_controller $controller
     */
    public function get_responses_table_form(testquestion_controller $controller): void {
        $controller->handle_responses_table_form();
    }

    /**
     * Render the link upload test responses for a question.
     *
     * @param qtype_pmatch_question $question the question to render the link for.
     * @return string HTML to display.
     */
    public function get_uploadresponses_link(qtype_pmatch_question $question): string {
        $link = new moodle_url('/question/type/pmatch/uploadresponses.php', ['id' => $question->id]);

        return html_writer::tag('input', '', ['value' => get_string('testquestionuploadresponses', 'qtype_pmatch'),
            'type' => 'button', "onclick" => "window.location.href = '" . $link->out(false) . "'", 'class' => 'btn btn-secondary']);
    }

    /**
     * Render the question testing tool heading a question.
     *
     * @param qtype_pmatch_question $question the question to render the heading for.
     * @return string HTML to display.
     */
    public function get_responses_heading(qtype_pmatch_question $question): string {
        return html_writer::tag('h3', get_string('showingresponsesforquestion', 'qtype_pmatch', $question->name));
    }

    /**
     * Render the summary of testing results for a question.
     *
     * @param qtype_pmatch_question $question the question.
     * @return string HTML to display.
     */
    public function get_grade_summary(qtype_pmatch_question $question) {
        $counts = \qtype_pmatch\testquestion_responses::get_question_grade_summary_counts($question);
        return html_writer::tag('p', get_string('testquestionresultssummary', 'qtype_pmatch', $counts),
                ['id' => 'testquestion_gradesummary']);
    }

    /**
     * Render any submit buttons required by the attempts (responses table) form.
     *
     * @param qtype_pmatch_question $question
     * @return string HTML to display.
     */
    public function get_table_bottom_buttons(qtype_pmatch_question $question): string {
        $html = '';
        if (question_has_capability_on($question, 'edit')) {
            $html .= html_writer::start_div('', ['id' => 'commands']);

            // Add and upload actions.
            $html .= html_writer::start_tag('p', ['id' => 'wrapperactionresponse']);
            $html .= html_writer::tag('input', '', ['type' => 'button',
                    'value' => get_string('testquestionformnewresponsebutton', 'qtype_pmatch'),
                    'id' => 'newresponsebutton', 'class' => 'btn btn-secondary m-t-0']);
            $html .= ' ' . $this->get_uploadresponses_link($question);
            $html .= \html_writer::end_tag('p');

            // Bulk actions.
            $html .= html_writer::tag('strong', get_string('withselected', 'question') . ':');
            $html .= html_writer::empty_tag('br');

            // Delete responses.
            $html .= html_writer::empty_tag('input', [
                    'type' => 'submit',
                    'id' => 'deleteresponsesbutton',
                    'class' => 'btn btn-secondary',
                    'name' => 'delete',
                    'value' => get_string('testquestionformdeletesubmit', 'qtype_pmatch'),
                    'data-action' => 'toggle',
                    'data-togglegroup' => 'responses',
                    'data-toggle' => 'action',
                    // This data-confirmation stuff does not currently work, but in future ...
                    // 'data-confirmation' => 'modal',
                    // 'data-confirmation-title-str' => '["confirmation", "admin"]',
                    // 'data-confirmation-question-str' => '["testquestionformdeletecheck", "qtype_pmatch"]',
                    // 'data-confirmation-yes-button-str' => '["delete", "core"]',
                    // ... will be a nice way to do it.
                ]);

            // Test responses.
            $html .= html_writer::empty_tag('input', [
                    'type' => 'submit',
                    'id' => 'testresponsesbutton',
                    'class' => 'btn btn-secondary',
                    'name' => 'test',
                    'value' => get_string('testquestionformtestsubmit', 'qtype_pmatch'),
                    'data-action' => 'toggle',
                    'data-togglegroup' => 'responses',
                    'data-toggle' => 'action',
                ]);
            $html .= html_writer::end_div();

            // Initialise JavaScript.
            $this->page->requires->event_handler('#deleteresponsesbutton', 'click', 'M.util.show_confirm_dialog',
                    ['message' => get_string('testquestionformdeletecheck', 'qtype_pmatch')]);
            $this->page->requires->js_call_amd('qtype_pmatch/updater', 'init');
            $this->page->requires->js_call_amd('qtype_pmatch/creator', 'init');
            $this->page->requires->strings_for_js(['ok', 'selectall', 'deselectall'], 'moodle');
            $this->page->requires->strings_for_js(['error:title', 'testquestionresultssummary',
                    'testquestionformsaveresponsebutton',
                    'testquestionformcancelresponsebutton'], 'qtype_pmatch');
        }

        return $html;
    }
}

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
 * Combined question embedded sub question renderer class.
 *
 * @package   qtype_pmatch
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


class qtype_pmatch_embedded_renderer extends qtype_combined_text_entry_renderer_base {

    /**
     * @param question_attempt $qa
     * @param question_display_options $options
     * @param qtype_combined_combinable_text_entry $subq
     * @param integer $placeno
     * @return string
     */
    public function subquestion(question_attempt $qa, question_display_options $options, qtype_combined_combinable_base $subq,
            $placeno) {

        $result = parent::subquestion($qa, $options, $subq, $placeno);
        $link = '';
        if ($subq->question->user_can_view()) {
            $link = html_writer::link(new moodle_url(
                    '/question/type/pmatch/testquestion.php', ['id' => $subq->question->id]),
                    get_string('test', 'qtype_pmatch'), ['title' => get_string('testsubquestionx', 'qtype_pmatch',
                            $subq->get_identifier())]);
        }
        return html_writer::tag('span', $result . $link, ['class' => 'combined-pmatch-input']);
    }
}

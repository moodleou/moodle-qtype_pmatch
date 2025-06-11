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

use qtype_pmatch\local\spell\qtype_pmatch_spell_checker;

/**
 * Pattern-match question renderer class.
 *
 * @package    qtype_pmatch
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_renderer extends qtype_renderer {

    #[\Override]
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {

        /** @var qtype_pmatch_question $question */
        $question = $qa->get_question();
        $currentanswer = $question->modify_current_answer($qa->get_last_qt_var('answer'), $options);

        $inputname = $qa->get_qt_field_name('answer');
        $attributes = [
            'class' => 'answerinputfield',
            'name' => $inputname,
            'id' => $inputname,
            'aria-labelledby' => $inputname . '-label',
        ];

        $attributes = array_merge($attributes, $this->display_spellcheck($question));

        if ($options->readonly) {
            $attributes['readonly'] = 'readonly';
        }
        $feedbackimg = '';
        if ($options->correctness) {
            $answer = $question->get_matching_answer(['answer' => $currentanswer]);
            if ($answer) {
                $fraction = $answer->fraction;
            } else {
                $fraction = 0;
            }
            $attributes['class'] .= ' '.$this->feedback_class($fraction);
            $feedbackimg = $this->feedback_image($fraction);
        }

        $htmlresponse = $question->allowsubscript || $question->allowsuperscript;
        if ($htmlresponse) {
            $editor = get_texteditor('ousupsub');
            if ($editor === false) {
                $htmlresponse = false;
            }
        }

        // Distinguish between answer input with or without supsub editor (used for mobileApp).
        if ($htmlresponse) {
            $attributes['class'] .= " answer-supsub";
        }

        $questiontext = $question->format_questiontext($qa);
        $rows = 2;
        $cols = 50;
        $placeholder = false;
        if (preg_match('/__(\d+)x(\d+)__/i', $questiontext, $matches)) {
            $placeholder = $matches[0];
            $cols = $matches[1];
            $rows = $matches[2];
        } else {
            if (preg_match('/__([0-9]+)__/', $questiontext, $matches)) {
                $placeholder = $matches[0];
                $cols = $matches[1];
            } else if (preg_match('/_____+/', $questiontext, $matches)) {
                $placeholder = $matches[0];
                $cols = strlen($placeholder);
            }
        }
        $rows = round($rows * 1.1);
        $cols = round($cols * 1.1);
        if ($htmlresponse && $options->readonly) {
            $input = html_writer::tag('span', $currentanswer, $attributes) . $feedbackimg;
        } else if ($htmlresponse) {
            $attributes['rows'] = 2;
            $attributes['cols'] = $cols;
            $input = html_writer::tag('textarea', $currentanswer, $attributes) . $feedbackimg;
        } else if ($rows > 1) {
            $attributes['rows'] = $rows;
            $attributes['cols'] = $cols;
            $input = html_writer::tag('textarea', $currentanswer, $attributes) . $feedbackimg;
        } else {
            $inputattributes = [
                'type' => 'text',
                'value' => $currentanswer,
            ];
            $inputattributes['size'] = $cols;
            $input = html_writer::empty_tag('input', $inputattributes + $attributes) . $feedbackimg;
        }

        $resetbutton = $this->reset_button($question, $options, $qa->get_qt_field_name('resetbutton'), $inputname);

        if ($placeholder) {
            $inputinplace = html_writer::tag('label', get_string('answer'),
                    ['for' => $attributes['id'], 'class' => 'accesshide']);
            $inputinplace .= $input . $resetbutton;
            $questiontext = substr_replace($questiontext, $inputinplace,
                     strpos($questiontext, $placeholder), strlen($placeholder));
        }

        $result = $this->question_tests_link($question, $options);
        $result .= html_writer::tag('div', $questiontext, ['class' => 'qtext']);

        if (!$placeholder) {
            $result .= html_writer::start_tag('div', ['class' => 'ablock', 'id' => $inputname . '-label']);
            $result .= html_writer::tag('label', get_string('answercolon', 'qtype_numerical'), ['for' => $attributes['id']]);
            $result .= html_writer::tag('div', $input, ['class' => 'answer']) . $resetbutton;
            $result .= html_writer::end_tag('div');
        }

        if ($htmlresponse && !$options->readonly) {
            if ($question->allowsubscript && $question->allowsuperscript) {
                $supsub = 'both';
            } else if ($question->allowsuperscript) {
                $supsub = 'sup';
            } else if ($question->allowsubscript) {
                $supsub = 'sub';
            }
            $editor->use_editor($attributes['id'], ['supsub' => $supsub]);
        }

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(['answer' => $currentanswer]),
                    ['class' => 'validationerror']);
        }

        // Show the error if the question is using a language that does not available on the server.
        if ($question->user_can_see_missing_dict_warning() && $question->is_spell_check_laguage_available()) {
            $missinglangname = qtype_pmatch_spell_checker::get_display_name_for_language_code($question->applydictionarycheck);
            $result .= html_writer::nonempty_tag('div',
                    get_string('apply_spellchecker_missing_language_attempt', 'qtype_pmatch', $missinglangname),
                    ['class' => 'validationerror']);
        }
        return $result;
    }

    /**
     * Render a button to reset the response input to the template.
     *
     * @param qtype_pmatch_question $question the pmatch question
     * @param question_display_options $options display options.
     * @param string $resetbuttonid id to use for this reset button.
     * @param string $inputname id for the input the button should affect.
     * @return string HTML to output.
     */
    public function reset_button(
        qtype_pmatch_question $question,
        question_display_options $options,
        string $resetbuttonid,
        string $inputname,
    ): string {
        if ($options->readonly || !$question->responsetemplate) {
            return '';
        }

        $this->page->requires->js_call_amd('qtype_pmatch/reset_button', 'initResetButton', [
            $resetbuttonid,
            $inputname,
            $question->responsetemplate,
        ]);

        return html_writer::tag(
            'button',
            $options->add_question_identifier_to_label(get_string('reset', 'core'), true),
            [
                'id' => $resetbuttonid,
                'class' => 'submit btn btn-secondary align-middle ml-1',
                'type' => 'button',
            ],
        );
    }

    #[\Override]
    public function specific_feedback(question_attempt $qa) {
        /** @var qtype_pmatch_question $question */
        $question = $qa->get_question();

        $answer = $question->get_matching_answer(['answer' => $qa->get_last_qt_var('answer')]);
        if (!$answer || !$answer->feedback) {
            return '';
        }

        return $question->format_text($answer->feedback, $answer->feedbackformat,
                $qa, 'question', 'answerfeedback', $answer->id);
    }

    #[\Override]
    public function correct_response(question_attempt $qa) {
        return '';
    }

    /**
     * Displays a link to run the question tests, if applicable.
     * @param qtype_pmatch_question $question
     * @param question_display_options $options
     * @return string HTML fragment.
     */
    public function question_tests_link(qtype_pmatch_question $question, question_display_options $options) {
        if (!empty($options->suppressruntestslink)) {
            return '';
        }
        if (!$question->user_can_view()) {
            return '';
        }

        $link = html_writer::link(new moodle_url(
                '/question/type/pmatch/testquestion.php', ['id' => $question->id]),
                get_string('testthisquestion', 'qtype_pmatch'));

        return html_writer::tag('div', $link, ['class' => 'questiontestslink']);
    }

    /**
     * Displays a link to go back to the test question page.
     *
     * @param int $qid question id.
     * @return string HTML fragment.
     */
    public function back_to_test_question_link(int $qid): string {
        return html_writer::tag('p', html_writer::link(
                new moodle_url('/question/type/pmatch/testquestion.php', ['id' => $qid]),
                get_string('testquestionbacklink', 'qtype_pmatch')));
    }

    /**
     * Displays the feedback
     *
     * @param object $feedback object containing feedback data.
     * @return string HTML fragment.
     */
    public function display_feedback($feedback) {
        $html = html_writer::tag('p', html_writer::div(
                    get_string('savedxresponses', 'qtype_pmatch', ($feedback->saved))));
        $total = count($feedback->duplicates) + count($feedback->problems);
        if ($total) {
            $html .= html_writer::div(get_string('xresponsesproblems', 'qtype_pmatch',
                $total));

            $feebacklist = array_merge($feedback->duplicates, $feedback->problems);
            $html .= html_writer::alist($feebacklist);
        }
        return $html;
    }

    /**
     * Check whether we want to display spell check
     *
     * @param qtype_pmatch_question $question object that contain question properties.
     * @return array spell-check attribute.
     */
    public function display_spellcheck(qtype_pmatch_question $question): array {
        $attribute['spellcheck'] = 'false';
        if ($question->applydictionarycheck !== qtype_pmatch_spell_checker::DO_NOT_CHECK_OPTION) {
            $attribute['spellcheck'] = 'true';
            $attribute['lang'] = str_replace('_', '-', $question->applydictionarycheck);
        }
        return $attribute;
    }
}

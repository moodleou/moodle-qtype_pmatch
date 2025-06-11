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
 * Defines the hooks necessary to make the pmatch question type combinable
 *
 * @package   qtype_pmatch
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_pmatch\local\spell\qtype_pmatch_spell_checker;

defined('MOODLE_INTERNAL') || die();
define('QTYPE_PMATCH_DEFAULT_PLACEHOLDER_SIZE', '__6__');

use qtype_pmatch\form_utils;
use qtype_pmatch\utils;

require_once($CFG->dirroot.'/question/type/pmatch/pmatchlib.php');

class qtype_combined_combinable_type_pmatch extends qtype_combined_combinable_type_base {

    protected $identifier = 'pmatch';

    protected function extra_question_properties() {
        return ['forcelength' => '0'];
    }

    protected function extra_answer_properties() {
        return ['fraction' => '1', 'feedback' => ['text' => '', 'format' => FORMAT_PLAIN]];
    }

    public function subq_form_fragment_question_option_fields() {
        return [
            'allowsubscript' => null,
            'allowsuperscript' => null,
            'usecase' => null,
            'quotematching' => null,
            'applydictionarycheck' => null,
            'extenddictionary' => '',
            'sentencedividers' => '.?!',
            'converttospace' => ',;:',
            'modelanswer' => '',
            'responsetemplate' => '',
            'synonymsdata' => []
        ];
    }

    protected function third_param_for_default_question_text() {
        return QTYPE_PMATCH_DEFAULT_PLACEHOLDER_SIZE;
    }
}


class qtype_combined_combinable_pmatch extends qtype_combined_combinable_text_entry {

    /**
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param                 $repeatenabled
     * @return mixed
     */
    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        $mform->addElement('select', $this->form_field_name('usecase'), get_string('casesensitive', 'qtype_pmatch'), [
            get_string('caseno', 'qtype_pmatch'),
            get_string('caseyes', 'qtype_pmatch'),
        ]);

        $mform->addElement('select', $this->form_field_name('quotematching'),
            get_string('smart_straight_quote_matching', 'qtype_pmatch'), [
                get_string('smart_straight_quote_matching_relaxed', 'qtype_pmatch'),
                get_string('smart_straight_quote_matching_strict', 'qtype_pmatch'),
            ]);
        $mform->addHelpButton($this->form_field_name('quotematching'), 'smart_straight_quote_matching', 'qtype_pmatch');

        $supsubels = [];
        $supsubels[] = $mform->createElement('selectyesno', $this->form_field_name('allowsubscript'),
                get_string('allowsubscript', 'qtype_pmatch'));
        $supsubels[] = $mform->createElement('selectyesno', $this->form_field_name('allowsuperscript'),
                get_string('allowsuperscript', 'qtype_pmatch'));
        $mform->addGroup($supsubels, $this->form_field_name('supsubels'),
                get_string('allowsubscript', 'qtype_pmatch'), '', false);

        // Add hidden sup field so that we can retain the value when the field is disabled.
        $mform->addElement('hidden', $this->form_field_name('allowsuperscriptselectedvalue'), '');
        $mform->setType($this->form_field_name('allowsuperscriptselectedvalue'), PARAM_BOOL);
        // Add hidden sub field so that we can retain the value when the field is disabled.
        $mform->addElement('hidden', $this->form_field_name('allowsubscriptselectedvalue'), '');
        $mform->setType($this->form_field_name('allowsubscriptselectedvalue'), PARAM_BOOL);

        $mform->addElement('static', 'spellcheckdescription', '', get_string('spellcheckdisabled', 'qtype_pmatch'));

        [$options, $disable] = qtype_pmatch_spell_checker::get_spell_checker_language_options($this->questionrec);
        if ($disable) {
            $mform->addElement('select', $this->form_field_name('applydictionarycheck'),
                    get_string('applydictionarycheck', 'qtype_pmatch'), $options, ['disabled' => 'disabled']);
        } else {
            $mform->addElement('select', $this->form_field_name('applydictionarycheck'),
                    get_string('applydictionarycheck', 'qtype_pmatch'), $options);
            $mform->setDefault($this->form_field_name('applydictionarycheck'), get_string('iso6391', 'langconfig'));
            $mform->addElement('hidden', $this->form_field_name('applydictionarycheckselectedvalue'), '');
            $mform->setType($this->form_field_name('applydictionarycheckselectedvalue'), PARAM_ALPHAEXT);

            $mform->disabledIf($this->form_field_name('applydictionarycheck'), $this->form_field_name('allowsubscript'),
                'eq', true);
            $mform->disabledIf($this->form_field_name('applydictionarycheck'), $this->form_field_name('allowsuperscript'),
                'eq', true);
            $mform->disabledIf($this->form_field_name('allowsuperscript'),
                $this->form_field_name('applydictionarycheck'), 'neq', qtype_pmatch_spell_checker::DO_NOT_CHECK_OPTION);
            $mform->disabledIf($this->form_field_name('allowsubscript'),
                $this->form_field_name('applydictionarycheck'), 'neq', qtype_pmatch_spell_checker::DO_NOT_CHECK_OPTION);
        }

        $mform->addElement('textarea', $this->form_field_name('extenddictionary'), get_string('extenddictionary', 'qtype_pmatch'),
            ['rows' => '3', 'cols' => '57']);
        $mform->disabledIf($this->form_field_name('extenddictionary'),
                $this->form_field_name('applydictionarycheck'),
                'eq', qtype_pmatch_spell_checker::DO_NOT_CHECK_OPTION);
        $mform->disabledIf($this->form_field_name('extenddictionary'), $this->form_field_name('allowsubscript'),
            'eq', true);
        $mform->disabledIf($this->form_field_name('extenddictionary'), $this->form_field_name('allowsuperscript'),
            'eq', true);

        $mform->addElement('text', $this->form_field_name('sentencedividers'), get_string('sentencedividers', 'qtype_pmatch'));
        $mform->setDefault($this->form_field_name('sentencedividers'), '.?!');
        $mform->setType($this->form_field_name('sentencedividers'), PARAM_RAW_TRIMMED);

        $mform->addElement('text', $this->form_field_name('converttospace'), get_string('converttospace', 'qtype_pmatch'));
        $mform->setDefault($this->form_field_name('converttospace'), ',;:');
        $mform->setType($this->form_field_name('converttospace'), PARAM_RAW_TRIMMED);

        $mform->addElement('text', $this->form_field_name('responsetemplate'), get_string('prefillanswertext', 'qtype_pmatch'));
        $mform->addHelpButton($this->form_field_name('responsetemplate'), 'prefillanswertext', 'qtype_pmatch');
        $mform->setType($this->form_field_name('responsetemplate'), PARAM_RAW_TRIMMED);

        form_utils::add_synonyms($combinedform, $mform, $this->questionrec, false,
                $this->form_field_name('synonymsdata'), 1, 0);
        $mform->setType($this->form_field_name('synonymsdata'), PARAM_RAW_TRIMMED);

        $modalanswer = [];
        $modalanswer[] = $mform->createElement('text', $this->form_field_name('modelanswer'), null);
        $modalanswer[] = $mform->createElement('static', 'appropriately-size-placeholder', '',
            get_string('modelanswer_appropriateinputsize', 'qtype_pmatch'));
        $htmlplaceholder = html_writer::empty_tag('input', [
            'type' => 'text',
            'readonly' => 'readonly',
            'size' => '22',
            'name' => $this->form_field_name('placeholder'),
            'id' => $this->form_field_name('placeholder'),
            'value' => QTYPE_PMATCH_DEFAULT_PLACEHOLDER_SIZE,
            'onfocus' => 'this.select()',
            'class' => 'form-control-plaintext d-inline-block w-auto mr-3',
        ]);
        $modalanswer[] = $mform->createElement('static', 'possible-answer-placeholder',
            get_string('modelanswer_possibleanswerplaceholders', 'qtype_pmatch'), $htmlplaceholder);
        $mform->addGroup($modalanswer, $this->form_field_name('modelanswer'),
            get_string('modelanswer', 'qtype_pmatch'), '', false);
        $mform->setType($this->form_field_name('modelanswer'), PARAM_RAW_TRIMMED);
        $mform->addRule($this->form_field_name('modelanswer'), get_string('modelanswermissing', 'qtype_pmatch'),
            'required');
        $mform->addHelpButton($this->form_field_name('modelanswer'), 'modelanswer', 'qtype_pmatch');

        $mform->addElement('textarea', $this->form_field_name('answer[0]'), get_string('answermustmatch', 'qtype_pmatch'),
                                                             ['rows' => '6', 'cols' => '57', 'class' => 'textareamonospace']);
        $mform->addHelpButton($this->form_field_name('answer[0]'), 'correctanswers', 'qtype_pmatch');
        $mform->setDefault($this->form_field_name('answer'), [0 => 'match ()']);
        $mform->setType($this->form_field_name('answer'), PARAM_RAW_TRIMMED);

        $this->js_call();
    }

    public function data_to_form($context, $fileoptions) {
        $answers = ['answer' => []];
        if ($this->questionrec !== null) {
            $answer = array_pop($this->questionrec->options->answers);
            $answers['answer'][] = $answer->answer;
        }

        $data = parent::data_to_form($context, $fileoptions) + $answers;
        // These options are incompatible, so of sup or sub is set, unset applydictionarycheck before showing the form.
        if (!empty($data['allowsubscript']) || !empty($data['allowsuperscript'])) {
            $data['applydictionarycheck'] = qtype_pmatch_spell_checker::DO_NOT_CHECK_OPTION;
        }
        if (isset($this->questionrec)) {
            // Convert synonyms from record into synonymsdata for form fields.
            $data['synonymsdata'] = array_values($this->questionrec->options->synonyms);
            foreach ($data['synonymsdata'] as $key => $item) {
                $data['synonymsdata'][$key] = (array)$item;
            }
        }

        return $data;
    }


    public function validate() {
        $errors = [];

        // Convert smart quotes to straight quotes in the form data before validating.
        if (isset($this->formdata->quotematching) && !$this->formdata->quotematching) {
            $this->formdata = utils::convert_quote_to_straight_quote($this->formdata);
        }
        $trimmedanswer = $this->formdata->answer[0];
        if ('' !== $trimmedanswer) {
            if ($message = form_utils::validate_pmatch_expression($trimmedanswer)) {
                $errors[$this->form_field_name('answer[0]')] = $message;
            }
        } else {
            $errors[$this->form_field_name('answer[0]')] = get_string('err_providepmatchexpression', 'qtype_pmatch');
        }

        // Check whether any chars of sentencedividers field exists in converttospace field.
        if (!empty($this->formdata->sentencedividers)) {
            if ($charfound = form_utils::find_char_in_both_strings(
                    $this->formdata->sentencedividers, $this->formdata->converttospace)) {
                $errors[$this->form_field_name('converttospace')] =
                        get_string('sentencedividers_noconvert', 'qtype_pmatch', $charfound);
            }
        }

        $errors += form_utils::validate_synonyms((array) $this->formdata, $this->form_field_name('synonymsdata'));

        // Validate the model answer (if everything else is OK).
        if (empty($errors)) {
            $modelanswer = trim($this->formdata->modelanswer);
            $pmatchoptions = form_utils::options_from_form_data((array) $this->formdata);
            if (!form_utils::validate_modelanswer($this->formdata->answer,
                    ['0' => 1.0], $modelanswer, $pmatchoptions)) {
                $errors[$this->form_field_name('modelanswer')] = get_string('modelanswererror', 'qtype_pmatch', $modelanswer);
            }
        }

        return $errors;
    }

    public function get_sup_sub_editor_option() {
        if ($this->question->allowsubscript && $this->question->allowsuperscript) {
            return 'both';
        } else if ($this->question->allowsuperscript) {
            return 'sup';
        } else if ($this->question->allowsubscript) {
            return 'sub';
        } else {
            return null;
        }
    }

    public function has_submitted_data() {
        return $this->submitted_data_array_not_empty('answer') || parent::has_submitted_data();
    }

    /**
     * Perform the needed JS setup for this question type.
     */
    public function js_call(): void {
        global $PAGE;
        $PAGE->requires->js_call_amd('qtype_pmatch/check_valid_expression', 'init');
        $PAGE->requires->js_call_amd('qtype_pmatch/formchanged', 'init', [$this->form_field_name_prefix()]);
        $PAGE->requires->js_call_amd('qtype_pmatch/populate_placeholder', 'init' , [$this->form_field_name_prefix()]);
    }
}

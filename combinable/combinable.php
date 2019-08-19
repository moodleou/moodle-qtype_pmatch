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

require_once($CFG->dirroot.'/question/type/pmatch/pmatchlib.php');

class qtype_combined_combinable_type_pmatch extends qtype_combined_combinable_type_base {

    protected $identifier = 'pmatch';

    protected function extra_question_properties() {
        return array('forcelength' => '0');
    }

    protected function extra_answer_properties() {
        return array('fraction' => '1', 'feedback' => array('text' => '', 'format' => FORMAT_PLAIN));
    }

    public function subq_form_fragment_question_option_fields() {
        return array('allowsubscript' => null,
                     'allowsuperscript' => null,
                     'usecase' => null,
                     'applydictionarycheck' => null,
                     'extenddictionary' => '',
                     'sentencedividers' => '.?!',
                     'converttospace' => ',;:',
                     'modelanswer' => '',
                     'synonymsdata' => array());
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
        $susubels = array();
        $susubels[] = $mform->createElement('selectyesno', $this->form_field_name('allowsubscript'),
                                            get_string('allowsubscript', 'qtype_pmatch'));
        $susubels[] = $mform->createElement('selectyesno', $this->form_field_name('allowsuperscript'),
                                            get_string('allowsuperscript', 'qtype_pmatch'));
        $mform->addGroup($susubels, $this->form_field_name('susubels'), get_string('allowsubscript', 'qtype_pmatch'),
                                                                    '',
                                                                    false);
        $menu = array(
            get_string('caseno', 'qtype_pmatch'),
            get_string('caseyes', 'qtype_pmatch')
        );
        $casedictels = array();
        $casedictels[] = $mform->createElement('select', $this->form_field_name('usecase'),
                                               get_string('casesensitive', 'qtype_pmatch'), $menu);
        list ($options, $disable) = qtype_pmatch_spell_checker::get_spell_checker_language_options($this->questionrec);
        if ($disable) {
            $casedictels[] = $mform->createElement('select', $this->form_field_name('applydictionarycheck'),
                    get_string('applydictionarycheck', 'qtype_pmatch'), $options, ['disabled' => 'disabled']);
        } else {
            $casedictels[] = $mform->createElement('select', $this->form_field_name('applydictionarycheck'),
                    get_string('applydictionarycheck', 'qtype_pmatch'), $options);
            $mform->setDefault('applydictionarycheck', get_string('iso6391', 'langconfig'));
        }
        $mform->addGroup($casedictels, $this->form_field_name('casedictels'),
                                                                        get_string('casesensitive', 'qtype_pmatch'), '', false);

        $mform->addElement('textarea', $this->form_field_name('extenddictionary'), get_string('extenddictionary', 'qtype_pmatch'),
            array('rows' => '3', 'cols' => '57'));
        $mform->disabledIf($this->form_field_name('extenddictionary'),
                $this->form_field_name('applydictionarycheck'),
                'eq', qtype_pmatch_spell_checker::DO_NOT_CHECK_OPTION);

        $mform->addElement('text', $this->form_field_name('sentencedividers'), get_string('sentencedividers', 'qtype_pmatch'));
        $mform->setDefault($this->form_field_name('sentencedividers'), '.?!');
        $mform->addElement('text', $this->form_field_name('converttospace'), get_string('converttospace', 'qtype_pmatch'));
        $mform->setDefault($this->form_field_name('converttospace'), ',;:');
        $mform->addElement('text', $this->form_field_name('modelanswer'), get_string('modelanswer', 'qtype_pmatch'));
        \qtype_pmatch\form_utils::add_synonyms($combinedform, $mform, $this->questionrec, false,
                $this->form_field_name('synonymsdata'), 1, 0);

        $mform->addElement('textarea', $this->form_field_name('answer[0]'), get_string('answer', 'question'),
                                                             array('rows' => '6', 'cols' => '57', 'class' => 'textareamonospace'));
        $mform->setType($this->form_field_name('answer'), PARAM_RAW_TRIMMED);
        $mform->setType($this->form_field_name('sentencedividers'), PARAM_RAW_TRIMMED);
        $mform->setType($this->form_field_name('converttospace'), PARAM_RAW_TRIMMED);
        $mform->setType($this->form_field_name('modelanswer'), PARAM_RAW_TRIMMED);
        $mform->setType($this->form_field_name('synonymsdata'), PARAM_RAW_TRIMMED);
    }

    public function data_to_form($context, $fileoptions) {
        $answers = array('answer' => array());
        if ($this->questionrec !== null) {
            $answer = array_pop($this->questionrec->options->answers);
            $answers['answer'][] = $answer->answer;
        }

        $data = parent::data_to_form($context, $fileoptions) + $answers;

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
        $errors = array();

        $trimmedanswer = $this->formdata->answer[0];
        $answerok = true;
        if ('' !== $trimmedanswer) {
            $expression = new pmatch_expression($trimmedanswer);
            if (!$expression->is_valid()) {
                $errors[$this->form_field_name('answer[0]')] = $expression->get_parse_error();
                $answerok = false;
            }
        } else {
            $errors[$this->form_field_name('answer[0]')] = get_string('err_providepmatchexpression', 'qtype_pmatch');
            $answerok = false;
        }

        // Check whether any chars of sentencedividers field exists in converttospace field.
        if (!empty($this->formdata->sentencedividers)) {
            if ($charfound = \qtype_pmatch\form_utils::find_char_in_both_strings($this->formdata->sentencedividers, $this->formdata->converttospace)) {
                $errors[$this->form_field_name('converttospace')] = get_string('sentencedividers_noconvert', 'qtype_pmatch', $charfound);
            }
        }
        // Check whether the modelanswer is a correct match (For pmatch as subquestion of a combined question, we pass ['0' => 1.0] as $grades).
        if ($answerok && !\qtype_pmatch\form_utils::validate_modelanswer($this->formdata->answer, ['0' => 1.0], $this->formdata->modelanswer)) {
            $errors[$this->form_field_name('modelanswer')] = get_string('modelanswererror', 'qtype_pmatch', $this->formdata->modelanswer);
        }

        $errors += \qtype_pmatch\form_utils::validate_synonyms((array)$this->formdata, $this->form_field_name('synonymsdata'));

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
}

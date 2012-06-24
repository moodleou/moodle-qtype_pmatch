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
 * Defines the editing form for the pmatch question type.
 *
 * @package    qtype
 * @subpackage pmatch
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/pmatch/pmatchlib.php');

/**
 * Short answer question editing form definition.
 *
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_edit_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function definition_inner($mform) {
        $this->general_answer_fields($mform);

        $this->add_synonyms($mform);

        $mform->addElement('static', 'answersinstruct',
                                                get_string('correctanswers', 'qtype_pmatch'),
                                                get_string('filloutoneanswer', 'qtype_pmatch'));
        $mform->closeHeaderBefore('answersinstruct');

        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_pmatch', '{no}'),
                question_bank::fraction_options());

        $this->add_other_answer_fields($mform);
        $this->add_interactive_settings();
    }

    /**
     * Add answer options for any other (wrong) answer.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function add_other_answer_fields($mform) {
        $mform->addElement('header', 'otheranswerhdr',
                                                get_string('anyotheranswer', 'qtype_pmatch'));
        $mform->addElement('static', 'otherfraction', get_string('grade'), '0%');
        $mform->addElement('editor', 'otherfeedback', get_string('feedback', 'question'),
                                                        array('rows' => 5), $this->editoroptions);
    }

    /**
     * Add answer options that are common to all answers.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function general_answer_fields($mform) {
        $mform->addElement('header', 'generalheader',
                                                get_string('answeringoptions', 'qtype_pmatch'));
        $menu = array(
            get_string('caseno', 'qtype_pmatch'),
            get_string('caseyes', 'qtype_pmatch')
        );
        $mform->addElement('select', 'usecase', get_string('casesensitive', 'qtype_pmatch'), $menu);
        $mform->addElement('selectyesno', 'allowsubscript',
                                                    get_string('allowsubscript', 'qtype_pmatch'));
        $mform->addElement('selectyesno', 'allowsuperscript',
                                                    get_string('allowsuperscript', 'qtype_pmatch'));
        $menu = array(
            get_string('forcelengthno', 'qtype_pmatch'),
            get_string('forcelengthyes', 'qtype_pmatch')
        );
        $mform->addElement('select', 'forcelength',
                                                get_string('forcelength', 'qtype_pmatch'), $menu);
        $mform->setDefault('forcelength', 1);
        $mform->addElement('selectyesno', 'applydictionarycheck',
                                            get_string('applydictionarycheck', 'qtype_pmatch'));
        $mform->setDefault('applydictionarycheck', 1);
        $mform->addElement('textarea', 'extenddictionary',
                        get_string('extenddictionary', 'qtype_pmatch'),
                        array('rows' => '5', 'cols' => '80'));
        $mform->disabledIf('extenddictionary', 'applydictionarycheck', 'eq', 0);
        $mform->addElement('text', 'converttospace',
                        get_string('converttospace', 'qtype_pmatch'),
                        array('size' => 60));
        $mform->setDefault('converttospace', ',;:');
    }

    /**
     * Get the list of form elements to repeat, one for each answer.
     * @param object $mform the form being built.
     * @param $label the label to use for each option.
     * @param $gradeoptions the possible grades for each answer.
     * @param $repeatedoptions reference to array of repeated options to fill
     * @param $answersoption reference to return the name of $question->options
     *                       field holding an array of answers
     * @return array of form fields.
     */
    protected function get_per_answer_fields($mform, $label, $gradeoptions,
                                                            &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = $mform->createElement('header', 'answerhdr', $label);
        $repeated[] = $mform->createElement('textarea', 'answer', get_string('answer', 'question'),
                            array('rows' => '8', 'cols' => '60', 'class' => 'textareamonospace'));
        $repeated[] = $mform->createElement('select', 'fraction',
                                                                get_string('grade'), $gradeoptions);
        $repeated[] = $mform->createElement('editor', 'feedback',
                                get_string('feedback', 'question'),
                                array('rows' => 5), $this->editoroptions);
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answers';
        return $repeated;
    }

    protected function data_preprocessing_other_answer($question) {
        //special handling of otheranswer
        if (!empty($question->options->answers)) {
            foreach ($question->options->answers as $key => $answer) {
                if ($answer->answer == '*') {
                    $question->otherfeedback = array();
                    // Prepare the feedback editor to display files in draft area
                    $draftitemid = file_get_submitted_draft_itemid('otherfeedback');
                    $question->otherfeedback['text'] = file_prepare_draft_area(
                        $draftitemid,          // draftid
                        $this->context->id,    // context
                        'question',            // component
                        'answerfeedback',      // filarea
                        !empty($answer->id) ? (int) $answer->id : null, // itemid
                        $this->fileoptions,    // options
                        $answer->feedback      // text
                    );
                    $question->otherfeedback['itemid'] = $draftitemid;
                    $question->otherfeedback['format'] = $answer->feedbackformat;
                    unset($question->options->answers[$key]);
                }
            }
        }
        return $question;
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_other_answer($question);
        $question = $this->data_preprocessing_answers($question);

        $question = $this->data_preprocessing_hints($question);
        if (isset($question->options)) {
            $question->usecase = $question->options->usecase;
            $question->allowsubscript = $question->options->allowsubscript;
            $question->allowsuperscript = $question->options->allowsuperscript;
            $question->forcelength = $question->options->forcelength;
            $question->applydictionarycheck = $question->options->applydictionarycheck;
            $question->extenddictionary = $question->options->extenddictionary;
            $question->converttospace = $question->options->converttospace;
        }
        if (isset($question->options->synonyms)) {
            $synonyms = $question->options->synonyms;
            $question->synonymsdata = array();
            $key = 0;
            foreach ($synonyms as $synonym) {
                $question->synonymsdata[$key]['word'] = $synonym->word;
                $question->synonymsdata[$key]['synonyms'] = $synonym->synonyms;
                $key++;
            }
        }
        return $question;
    }


    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;
        $maxgrade = false;
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if ($trimmedanswer !== '') {
                $expression = new pmatch_expression($trimmedanswer);
                if (!$expression->is_valid()) {
                    $errors["answer[$key]"] = $expression->get_parse_error();
                }
                $answercount++;
                if ($data['fraction'][$key] == 1) {
                    $maxgrade = true;
                }
            } else if ($data['fraction'][$key] != 0 ||
                                            !html_is_blank($data['feedback'][$key]['text'])) {
                $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_pmatch');
                $answercount++;
            }
        }
        if ($answercount==0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_pmatch', 1);
        }
        if ($maxgrade == false) {
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
        }

        $errors += $this->place_holder_errors($data['questiontext']['text'],
                                              $data['allowsubscript'] || $data['allowsuperscript']);

        $wordssofar = array();
        foreach ($data['synonymsdata'] as $key => $synonym) {
            $trimmedword = trim($synonym['word']);
            $trimmedsynonyms = trim($synonym['synonyms']);
            if ($trimmedword == '' && $trimmedsynonyms == '') {
                continue;
            }

            if ($trimmedword != '' && $trimmedsynonyms == '') {
                $errors['synonymsdata['.$key.']'] =
                                            get_string('nomatchingsynonymforword', 'qtype_pmatch');
                continue;
            } else if ($trimmedword == '' && $trimmedsynonyms != '') {
                $errors['synonymsdata['.$key.']'] =
                                            get_string('nomatchingwordforsynonym', 'qtype_pmatch');
                continue;
            }

            $wordinterpreter = new pmatch_interpreter_word();
            list($wordmatched, $endofmatch) = $wordinterpreter->interpret($trimmedword);
            if ((!$wordmatched) || !($endofmatch == (strlen($trimmedword)))) {
                $errors['synonymsdata['.$key.']'] =
                                        get_string('wordcontainsillegalcharacters', 'qtype_pmatch');
                continue;
            } else if ($wordinterpreter->get_error_message()) {
                $errors['synonymsdata['.$key.']'] = $wordinterpreter->get_error_message();
                continue;
            }

            $synonyminterpreter = new pmatch_interpreter_synonym();
            list($synonymmatched, $endofmatch) = $synonyminterpreter->interpret($trimmedsynonyms);
            if ((!$synonymmatched) || !($endofmatch == (strlen($trimmedsynonyms)))) {
                $errors['synonymsdata['.$key.']'] =
                                get_string('synonymcontainsillegalcharacters', 'qtype_pmatch');
                continue;
            } else if ($synonyminterpreter->get_error_message()) {
                $errors['synonymsdata['.$key.']'] = $synonyminterpreter->get_error_message();
                continue;
            }

            if (in_array($trimmedword, $wordssofar)) {
                $errors['synonymsdata['.$key.']'] = get_string('repeatedword', 'qtype_pmatch');
            }
            $wordssofar[] = $trimmedword;
        }

        return $errors;
    }

    protected function place_holder_errors($questiontext, $usesubsup) {
        //check sizes of answer box within a reasonable range
        $errors = array();
        $placeholder = false;
        if (preg_match('/__([0-9]+)x([0-9]+)__/i', $questiontext, $matches)) {
            $cols = $matches[1];
            $rows = $matches[2];
            $placeholder = $matches[0];
        } else if (preg_match('/__([0-9]+)__/', $questiontext, $matches)) {
            $rows = 1;
            $cols = round($matches[1] * 1.1);
            $placeholder = $matches[0];
        }
        if ($placeholder && ($rows > 100 || $cols > 150)) {
            $errors['questiontext'] = get_string('inputareatoobig', 'qtype_pmatch', $placeholder);
        }
        if ($placeholder && ($rows > 1) && $usesubsup) {
            $errors['questiontext'] = get_string('subsuponelineonly', 'qtype_pmatch');
        }
        return $errors;
    }


    protected function add_synonyms($mform) {
        $mform->addElement('header', 'synonymshdr', get_string('synonymsheader', 'qtype_pmatch'));

        $textboxgroup = array();
        $textboxgroup[] = $mform->createElement('group', 'synonymsdata',
                get_string('wordwithsynonym', 'qtype_pmatch'), $this->add_synonym($mform));

        if (isset($this->question->options)) {
            $countsynonyms = count($this->question->options->synonyms);
        } else {
            $countsynonyms = 0;
        }

        if ($this->question->formoptions->repeatelements) {
            $repeatsatstart = max(3, $countsynonyms + 2);
        } else {
            $repeatsatstart = $countsynonyms;
        }

        $this->repeat_elements($textboxgroup, $repeatsatstart, array(), 'nosynonyms',
                        'addsynonyms', 2, get_string('addmoresynonymblanks', 'qtype_pmatch'));
    }

    protected function add_synonym($mform) {
        $grouparray = array();
        $grouparray[] = $mform->createElement('text', 'word',
                            get_string('wordwithsynonym', 'qtype_pmatch'), array('size'=>15));
        $grouparray[] = $mform->createElement('static', '', '',
                            ' '.get_string('synonym', 'qtype_pmatch').' ');
        $grouparray[] = $mform->createElement('text', 'synonyms',
                            get_string('synonym', 'qtype_pmatch'), array('size'=>50));
        return $grouparray;
    }

    public function qtype() {
        return 'pmatch';
    }
}

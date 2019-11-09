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
 * @package   qtype_pmatch
 * @copyright 2007 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/pmatch/pmatchlib.php');

use qtype_pmatch\local\spell\qtype_pmatch_spell_checker;

/**
 * Short answer question editing form definition.
 *
 * @copyright 2007 Jamie Pratt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_edit_form extends question_edit_form {
    /**
     * @var stdClass the 'Any other' answer.
     */
    protected $otheranswer = null;

    /**
     * @var string[] place holder for suggested rules.
     */
    protected $suggestedrules = null;

    public function __construct($submiturl, $question, $category, $contexts, $formeditable = true) {
        // Separate the Any other' answer from the list of normal answers.
        if (!empty($question->options->answers)) {
            foreach ($question->options->answers as $key => $answer) {
                if ($answer->answer == '*') {
                    $this->otheranswer = $answer;
                    unset($question->options->answers[$key]);
                    break;
                }
            }
        }
        parent::__construct($submiturl, $question, $category, $contexts, $formeditable = true);
    }

    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function definition_inner($mform) {
        $this->general_answer_fields($mform);
        \qtype_pmatch\form_utils::add_synonyms($this, $mform, $this->question, true, 'synonymsdata', 3, 2);

        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_pmatch', '{no}'),
                question_bank::fraction_options());

        $this->add_interactive_settings();
    }

    protected function add_per_answer_fields(&$mform, $label, $gradeoptions,
            $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {

        // Nasty hack. The auto suggest answers button is a no submit button so it doesn't
        // appear in the normal form flow. Though it is in the $_FORM object so we access it
        // there to see if rules need to be suggested.
        $suggestrules = optional_param('answersuggestbutton', '', PARAM_TEXT);
        if ($suggestrules && $suggestrules !== '') {
            $this->add_suggested_answers($mform);
        }

        parent::add_per_answer_fields($mform, $label, $gradeoptions);
        $results = '';

        if (\qtype_pmatch\testquestion_responses::has_responses($this->question)) {
            $counts = \qtype_pmatch\testquestion_responses::get_question_grade_summary_counts($this->question);
            $results = html_writer::tag('p',
                    get_string('testquestionresultssummary', 'qtype_pmatch', $counts),
                    ["id" => 'testquestion_gradesummary']);
        }
        $answersinstruct = $mform->createElement('static', 'answersinstruct',
                                                get_string('correctanswers', 'qtype_pmatch'),
                                                get_string('filloutoneanswer', 'qtype_pmatch') .
                                                $results);
        $mform->insertElementBefore($answersinstruct, 'topborder[0]');

        if (\qtype_pmatch\testquestion_responses::has_responses($this->question)) {
            // Add rule suggestion button.
            $answerssuggest = $this->add_rule_suggestion_fields($mform);
        }

        $this->add_answer_accuracy_fields($mform);
        $this->add_other_answer_fields($mform);
    }

    /**
     * Add answer options for any other (wrong) answer.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function add_answer_accuracy_fields($mform) {
        if (!$this->question || !property_exists ($this->question, 'id')) {
            return;
        }
        $questionobj = question_bank::load_question($this->question->id);
        if (!$hasrepsonses = \qtype_pmatch\testquestion_responses::has_responses($questionobj)) {
            return;
        }

        $rules = $questionobj->get_answers();
        $responses = \qtype_pmatch\testquestion_responses::get_graded_responses_by_questionid($questionobj->id);

        $responseids = array_keys($responses);
        $matches = \qtype_pmatch\testquestion_responses::get_rule_matches_for_responses($responseids, $this->question->id);

        // If there are no matches.
        if (!$matches) {
            return;
        }
        $count = 0;
        $responsestmp = $responses;
        foreach ($rules as $aid => $rule) {
            // Avoid adding anything to the 'Any other answer' section.
            if (!$mform->elementExists('fraction[' . $count . ']')) {
                continue;
            }

            // Add the Rule accuracy section.
            $accuracy = \qtype_pmatch\testquestion_responses::get_rule_accuracy_counts($responsestmp, $rule, $matches);
            $labelhtml = html_writer::div(
                    html_writer::label(get_string('ruleaccuracylabel', 'qtype_pmatch'), 'fitem_accuracy_' . $count),
                    'fitemtitle');
            $elementhtml = html_writer::div(get_string('ruleaccuracy', 'qtype_pmatch', $accuracy),
                    'felement fselect', array('id' => 'fitem_accuracy_' . $count));
            $html = html_writer::div(html_writer::div($labelhtml. $elementhtml, 'col-md-12'), 'fitem fitem_accuracy form-group row');
            $answersaccuracy = $mform->createElement('html', $html);
            $cloneanswersaccuracy = clone $answersaccuracy;
            $mform->insertElementBefore($cloneanswersaccuracy, 'accuracyborder[' . $count . ']');
            unset($cloneanswersaccuracy);
            // Add the Show coverage section - for rules that have been marked.
            if (array_key_exists($rule->id, $matches['ruleidstoresponseids'])) {
                $items = array();
                foreach ($matches['ruleidstoresponseids'][$rule->id] as $responseid) {
                    if ($responses[$responseid]->expectedfraction == $responses[$responseid]->gradedfraction) {
                        if ($responses[$responseid]->expectedfraction) {
                            $items[] = '<span>' .
                                    $responses[$responseid]->id . ': ' . $responses[$responseid]->response .
                                    '</span>';
                        } else {
                            $items[] = '<span>' .
                                    $responses[$responseid]->id . ': ' . $responses[$responseid]->response .
                                    '</span>';
                        }
                    } else {
                        if ($responses[$responseid]->expectedfraction) {
                            $items[] = '<span class="qtype_pmatch-selftest-missed-negative">' .
                                    $responses[$responseid]->id . ': ' . $responses[$responseid]->response .
                                    '</span>';
                        } else {
                            $items[] = '<span class="qtype_pmatch-selftest-missed-positive">' .
                                    $responses[$responseid]->id . ': ' . $responses[$responseid]->response .
                                    '</span>';
                        }
                    }
                }
                $reponseslist = print_collapsible_region_start('', 'matchedresponses_' . $count,
                        get_string('showcoverage', 'qtype_pmatch'), '', true, true);
                $reponseslist .= html_writer::alist($items);
                $reponseslist .= print_collapsible_region_end(true);
                $html = html_writer::div($reponseslist, 'fitem fitem_matchedresponses');
                $mform->insertElementBefore($mform->createElement('html', $html), 'fraction[' . $count . ']');
            }
            $count++;
        }
    }

    /**
     * Language string to use for 'Add {no} more {whatever we call answers}'.
     */
    protected function get_more_choices_string() {
        return get_string('addmoreanswerblanks', 'qtype_pmatch');
    }
    /**
     * Add answer options for any other (wrong) answer.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function add_other_answer_fields($mform) {
        $otheranswerhdr = $mform->addElement('static', 'otheranswerhdr',
                                                get_string('anyotheranswer', 'qtype_pmatch'));
        $otheranswerhdr->setAttributes(array('class' => 'otheranswerhdr'));
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
        $mform->addElement('header', 'answeroptionsheader',
                                                get_string('answeroptions', 'qtype_pmatch'));
        $mform->addElement('static', 'generaldescription', '',
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
        list ($options, $disable) = qtype_pmatch_spell_checker::get_spell_checker_language_options($this->question);
        if ($disable) {
            $mform->addElement('select', 'applydictionarycheck',
                    get_string('applydictionarycheck', 'qtype_pmatch'), $options, ['disabled' => 'disabled']);
        } else {
            $mform->addElement('select', 'applydictionarycheck',
                    get_string('applydictionarycheck', 'qtype_pmatch'), $options);
            $mform->setDefault('applydictionarycheck', get_string('iso6391', 'langconfig'));
        }
        $mform->addElement('textarea', 'extenddictionary',
                        get_string('extenddictionary', 'qtype_pmatch'),
                        array('rows' => '5', 'cols' => '80'));
        $mform->disabledIf('extenddictionary', 'applydictionarycheck', 'eq', qtype_pmatch_spell_checker::DO_NOT_CHECK_OPTION);
        $mform->addElement('text', 'sentencedividers',
                get_string('sentencedividers', 'qtype_pmatch'), array('size' => 50));
        $mform->addHelpButton('sentencedividers', 'sentencedividers', 'qtype_pmatch');
        $mform->setDefault('sentencedividers', '.?!');
        $mform->setType('sentencedividers', PARAM_RAW_TRIMMED);
        $mform->addElement('text', 'converttospace',
                get_string('converttospace', 'qtype_pmatch'), array('size' => 50));
        $mform->addHelpButton('converttospace', 'converttospace', 'qtype_pmatch');
        $mform->setDefault('converttospace', ',;:');
        $mform->setType('converttospace', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'modelanswer',
                get_string('modelanswer', 'qtype_pmatch'), array('size' => 50));
        $mform->addHelpButton('modelanswer', 'modelanswer', 'qtype_pmatch');
        $mform->setType('modelanswer', PARAM_RAW_TRIMMED);
    }

    /**
     * Get the list of form elements to repeat, one for each answer.
     * @param object $mform the form being built.
     * @param string $label the label to use for each option.
     * @param array $gradeoptions the possible grades for each answer.
     * @param array $repeatedoptions reference to array of repeated options to fill
     * @param string $answersoption reference to return the name of $question->options
     *                       field holding an array of answers
     * @return array of form fields.
     */
    protected function get_per_answer_fields($mform, $label, $gradeoptions,
                                                            &$repeatedoptions, &$answersoption) {
        $repeated = array();
        // Add an empty label to provide the top border for an answer (rule).
        // It would be nice to add a class to this element for styling, but it does not work.
        $repeated[] = $mform->createElement('static', 'topborder', '', ' ');
        $repeated[] = $mform->createElement('textarea', 'answer', $label,
                ['rows' => '8', 'cols' => '60', 'class' => 'answer-rule textareamonospace']);
        if ($this->question->qtype == 'pmatch') {
            $title = $this->get_rc_title();
            $content = $this->get_rc_content();
            $repeated[] = $mform->createElement('static', 'rule-creator-wrapper', $title, $content);
            $repeated[] = $mform->createElement('static', 'accuracyborder', '', ' ');
            if ($html = $this->get_try_button()) {
                $repeated[] = $mform->createElement('html', $html);
            }
        }
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

    protected function get_try_button() {
        $html = '';
        if (!\qtype_pmatch\testquestion_responses::has_responses($this->question)) {
            return $html;
        }
        $button = '<input type="button" name="tryrule" value="' .
                get_string('tryrule', 'qtype_pmatch') . '" class="btn btn-secondary">';
        $result = html_writer::div('', 'try-rule-result');
        $html .= html_writer::div(html_writer::div($button . $result, 'col-md-12'), 'fitem try-rule form-group row');
        return $html;
    }

    /**
     * Gets the rule creation assistant link.
     * @return string
     */
    protected function get_rc_title() {
        global $OUTPUT;
        return html_writer::link('#', get_string('rulecreationasst', 'qtype_pmatch') . ' ' .
                $OUTPUT->pix_icon('t/collapsed', ''), array('class' => 'rule-creator-btn'));
    }

    /**
     * Gets the rule creation assistant content.
     * This could be added as a template at a later stage.
     * @return string
     */
    protected function get_rc_content() {
        $html = html_writer::start_div('rule-creator rc-hidden');
        $add = get_string('add', 'qtype_pmatch');
        $addtoanswer = get_string('addtoanswer', 'qtype_pmatch');
        $choosetoken = get_string('choosetoken', 'qtype_pmatch');
        $exclude = get_string('exclude', 'qtype_pmatch');
        $or = get_string('or', 'qtype_pmatch');
        $precedes = get_string('precedes', 'qtype_pmatch');
        $precedesclosely = get_string('precedesclosely', 'qtype_pmatch');
        $resetrule = get_string('resetrule', 'qtype_pmatch');
        $rule = get_string('rule', 'qtype_pmatch');
        $template = get_string('template', 'qtype_pmatch');
        $term = get_string('term', 'qtype_pmatch');
        $html .= <<<EOT
<div>
    <div class="rc-notice"></div>
</div>
<div>
    <label for="term">$term</label>
    <input type="text" name="term" value="">
    <input type="submit" name="termadd" class="btn btn-secondary m-b-0" value="$add">
    <input type="submit" name="termexclude" class="btn btn-secondary m-b-0" value="$exclude">
    <input type="submit" name="termor" class="btn btn-secondary m-b-0" value="$or">
</div>
<div>
    <label for="template">$template</label>
    <input type="text" name="template" class="form-control" value="">
    <input type="submit" name="templateadd" class="btn btn-secondary m-b-0" value="$add">
    <input type="submit" name="templateexclude" class="btn btn-secondary m-b-0" value="$exclude">
</div>
<div>
    <label for="precedesadd">$precedes</label>
    <select name="precedes1" class="custom-select m-l-0">
        <option value="0">$choosetoken</option>
    </select>
    <select name="precedes2" class="custom-select">
        <option value="0">$choosetoken</option>
    </select>
    <input type="submit" name="precedesadd" value="$add">
</div>
<div>
    <label for="cprecedesadd">$precedesclosely</label>
    <select name="cprecedes1">
        <option value="0">$choosetoken</option>
    </select>
    <select name="cprecedes2">
        <option value="0">$choosetoken</option>
    </select>
    <input type="submit" name="cprecedesadd" class="btn btn-secondary m-b-0" value="$add">
</div>
<div>
    <div>$rule</div>
    <div class="rc-result"></div>
    <input type="submit" name="add" class="btn btn-secondary m-l-0" value="$addtoanswer">
    <input type="submit" name="clear" class="btn btn-secondary" value="$resetrule">
</div>
EOT;
        $html .= html_writer::end_div();
        return $html;
    }

    /*
     * Adds the rule suggestion fields to the form
     * @param object $mform
     */
    protected function add_rule_suggestion_fields($mform) {
        $feedback = '';

        // If the rule suggestion button has been pressed feedback how many rules were
        // suggested.
        if ($this->suggestedrules !== null) {
            $rulecount = $this->suggestedrules ? count($this->suggestedrules) : 0;
            $feedback = get_string('xrulesuggested', 'qtype_pmatch', $rulecount);
        }

        $textelement = $mform->createElement('static', 'answersuggesttext',
                                                get_string('rulesuggestionlabel', 'qtype_pmatch'), $feedback);
        $mform->insertElementBefore($textelement, 'topborder[0]');
        $buttonelement = $mform->createElement('submit', 'answersuggestbutton',
                                                get_string('rulesuggestionbutton', 'qtype_pmatch'));
        $mform->insertElementBefore($buttonelement, 'topborder[0]');
        $mform->registerNoSubmitButton('answersuggestbutton');
    }

    /**
     * Retrieves suggested answers processes them and appends to the existing question answers.
     * @param object $fromform form contents
     */
    protected function add_suggested_answers($mform) {
        try {
            $suggestedrules = \qtype_pmatch\amati_rule_suggestion::suggest_rules($mform, $this->question);
            // Now we have removed duplicate and invalid rules we can store them for use later.
            $this->suggestedrules = $suggestedrules;

            // Formslib.php::repeat_elements checks the submitted form to
            // establish the number of answer fields required. To accomdate the suggested
            // rules we just added we must override this form parameter with the new
            // number of answers.
            $_POST['noanswers'] = count($this->question->options->answers);
        } catch (moodle_exception $e) {
            switch ($e->getMessage()) {
                case 'No rules were suggested.':
                    $this->suggestedrules = array();
                    break;
                default:
                    $this->_form->setElementError('answersuggesttext', $e->getMessage());
            }
        }
    }

    protected function data_preprocessing_other_answer($question) {
        // Special handling of otheranswer.
        if ($this->otheranswer) {
            $question->otherfeedback = array();
            // Prepare the feedback editor to display files in draft area.
            $draftitemid = file_get_submitted_draft_itemid('otherfeedback');
            $question->otherfeedback['text'] = file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'question',
                'answerfeedback',
                !empty($answer->id) ? (int) $answer->id : null,
                $this->fileoptions,
                $this->otheranswer->feedback
            );
            $question->otherfeedback['itemid'] = $draftitemid;
            $question->otherfeedback['format'] = $this->otheranswer->feedbackformat;
            unset($question->options->answers[$this->otheranswer->id]);
        }
        return $question;
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_other_answer($question); // Must come first.
        $question = $this->data_preprocessing_answers($question);

        $question = $this->data_preprocessing_hints($question);
        if (isset($question->options)) {
            $question->usecase = $question->options->usecase;
            $question->allowsubscript = $question->options->allowsubscript;
            $question->allowsuperscript = $question->options->allowsuperscript;
            $question->forcelength = $question->options->forcelength;
            $question->applydictionarycheck = $question->options->applydictionarycheck;
            $question->extenddictionary = $question->options->extenddictionary;
            $question->sentencedividers = $question->options->sentencedividers;
            $question->converttospace = $question->options->converttospace;
            $question->modelanswer = $question->options->modelanswer;
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
        $this->js_call();
        return $question;
    }


    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;
        $maxgrade = false;

        // Check whether any chars of sentencedividers field exists in converttospace field.
        if (isset($data['sentencedividers'])) {
            if ($charfound = \qtype_pmatch\form_utils::find_char_in_both_strings($data['sentencedividers'], $data['converttospace'])) {
                $errors['converttospace'] = get_string('sentencedividers_noconvert', 'qtype_pmatch', $charfound);
            }
        }

        $allanswersok = true;
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if ($trimmedanswer !== '') {
                $expression = new pmatch_expression($trimmedanswer);
                if (!$expression->is_valid()) {
                    $errors["answer[$key]"] = $expression->get_parse_error();
                    $allanswersok = false;
                }
                $answercount++;
                if ($data['fraction'][$key] == 1) {
                    $maxgrade = true;
                }
            } else if ($data['fraction'][$key] != 0 ||
                                            !html_is_blank($data['feedback'][$key]['text'])) {
                $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_pmatch');
                $allanswersok = false;
                $answercount++;
            }
        }
        if ($answercount == 0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_pmatch', 1);
        }
        if ($maxgrade == false) {
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
        }

        if (isset($data['modelanswer'])) {
            $modelanswer = trim($data['modelanswer']);
            if ($allanswersok && !\qtype_pmatch\form_utils::validate_modelanswer($answers, $data['fraction'], $modelanswer)) {
                $errors['modelanswer'] = get_string('modelanswererror', 'qtype_pmatch', $modelanswer);
            }
        }

        $errors += \qtype_pmatch\form_utils::validate_synonyms($data);

        $errors += $this->place_holder_errors($data['questiontext']['text'],
                                              $data['allowsubscript'] || $data['allowsuperscript']);
        return $errors;
    }

    protected function place_holder_errors($questiontext, $usesubsup) {
        // Check sizes of answer box within a reasonable range.
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

    public function qtype() {
        return 'pmatch';
    }

    public function js_call() {
        global $PAGE;
        $PAGE->requires->js_call_amd('qtype_pmatch/rulecreator', 'init');
        $PAGE->requires->string_for_js('rulecreationtoomanyterms', 'qtype_pmatch');
        $PAGE->requires->string_for_js('rulecreationtoomanyors', 'qtype_pmatch');
        $PAGE->requires->js_call_amd('qtype_pmatch/tryrule', 'init');
    }
}

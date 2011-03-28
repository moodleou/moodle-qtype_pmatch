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
 * Short answer question definition class.
 *
 * @package    qtype
 * @subpackage pmatch
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/pmatch/pmatchlib.php');

/**
 * Represents a short answer question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_question extends question_graded_by_strategy
        implements question_response_answer_comparer {
    /** @var boolean whether to allow students to use subscript. */
    public $allowsubscript;
    /** @var boolean whether to allow students to use super script. */
    public $allowsuperscript;
    /** @var boolean whether to warn student if their response is longer than 20 words. */
    public $forcelength;
    /** @var boolean whether to spell check students response. */
    public $applydictionarycheck;
    /** @var pmatch_options options for pmatch expression matching. */
    public $pmatchoptions;
    /** @var array of question_answer. */
    public $answers = array();

    
    
    public function __construct() {
        parent::__construct(new question_first_matching_answer_grading_strategy($this));
    }

    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    public function is_complete_response(array $response) {
        print_object(compact('response'));
        $this->validate($response);
        return (!count($this->errors) > 0);
    }

    private $errors = null;

    protected function validate(array $response){
        if (is_null($this->errors)){
            $this->errors = array();
            $parsestring = new pmatch_parsed_string($response['answer'], $this->pmatchoptions);
            if  (!array_key_exists('answer', $response) ||
                    ((!$response['answer']) && $response['answer'] !== '0')){
                $this->errors[] = get_string('pleaseenterananswer', 'qtype_pmatch');
                return;
            }
            if ($this->applydictionarycheck && !$parsestring->is_spelt_correctly()){
                $misspelledwords = $parsestring->get_spelling_errors();
                $a = join(' ', $misspelledwords);
                $this->errors[] = get_string('spellingmistakes', 'qtype_pmatch', $a);
            }
            if ($this->forcelength){
                if ($parsestring->get_word_count() > 20){
                    $this->errors[] = get_string('toomanywords', 'qtype_pmatch');
                }
            }

        }
        error_log(print_r(compact('response')+array('$this->errors'=> $this->errors), true));
    }

    public function get_validation_error(array $response) {
        $this->validate($response);
        return join('<br />', $this->errors);
    }


    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function get_answers() {
        return $this->answers;
    }

    public function compare_response_with_answer(array $response, question_answer $answer) {
        return self::compare_string_with_pmatch_expression($response['answer'], $answer->answer, $this->pmatchoptions);
    }

    public static function compare_string_with_pmatch_expression($string, $expression, $options) {
        $string = new pmatch_parsed_string($string, $options);
        $expression = new pmatch_expression($expression, $options);
        return $expression->matches($string);
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'answerfeedback') {
            $currentanswer = $qa->get_last_qt_var('answer');
            $answer = $qa->get_question()->get_matching_answer(array('answer' => $currentanswer));
            $answerid = reset($args); // itemid is answer id.
            return $options->feedback && $answerid == $answer->id;

        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);

        } else {
            return parent::check_file_access($qa, $options, $component, $filearea, $args, $forcedownload);
        }
    }
}

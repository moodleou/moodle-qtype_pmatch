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
 * Pattern match form utils.
 *
 * @package qtype_pmatch
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../question.php');
require_once(__DIR__ . '/../pmatchlib.php');

/**
 * Pattern match form utils.
 *
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_utils {

    /**
     * Validate synonyms field.
     *
     * @param array $data Form data.
     * @param string $fieldname Field name.
     * @return array
     */
    public static function validate_synonyms($data, $fieldname = 'synonymsdata') {
        $errors = [];
        $wordssofar = [];
        foreach ($data['synonymsdata'] as $key => $synonym) {
            $trimmedword = trim($synonym['word']);
            $trimmedsynonyms = trim($synonym['synonyms']);
            if ($trimmedword == '' && $trimmedsynonyms == '') {
                continue;
            }

            if ($trimmedword != '' && $trimmedsynonyms == '') {
                $errors[$fieldname . '[' . $key . ']'] = get_string('nomatchingsynonymforword', 'qtype_pmatch');
                continue;
            } else if ($trimmedword == '' && $trimmedsynonyms != '') {
                $errors[$fieldname . '[' . $key . ']'] = get_string('nomatchingwordforsynonym', 'qtype_pmatch');
                continue;
            }

            $wordinterpreter = new \pmatch_interpreter_word();
            list($wordmatched, $endofmatch) = $wordinterpreter->interpret($trimmedword);
            if ((!$wordmatched) || !($endofmatch == (\core_text::strlen($trimmedword)))) {
                $errors[$fieldname . '[' . $key . ']'] = get_string('wordcontainsillegalcharacters', 'qtype_pmatch');
                continue;
            } else if ($wordinterpreter->get_error_message()) {
                $errors[$fieldname . '[' . $key . ']'] = $wordinterpreter->get_error_message();
                continue;
            }

            $synonyminterpreter = new \pmatch_interpreter_synonym();
            list($synonymmatched, $endofmatch) = $synonyminterpreter->interpret($trimmedsynonyms);
            if ((!$synonymmatched) || !($endofmatch == (\core_text::strlen($trimmedsynonyms)))) {
                $errors[$fieldname . '[' . $key . ']'] = get_string('synonymcontainsillegalcharacters', 'qtype_pmatch');
                continue;
            } else if ($synonyminterpreter->get_error_message()) {
                $errors[$fieldname . '[' . $key . ']'] = $synonyminterpreter->get_error_message();
                continue;
            }

            if (in_array($trimmedword, $wordssofar)) {
                $errors[$fieldname . '[' . $key . ']'] = get_string('repeatedword', 'qtype_pmatch');
            }
            $wordssofar[] = $trimmedword;
        }

        return $errors;
    }

    /**
     * Check whether any char of the first string appear in the second string.
     *
     * @param string $fiststring
     * @param string $secondstring
     * @return string
     */
    public static function find_char_in_both_strings($firststring, $secondstring) {
        if (empty($firststring)) {
            return null;
        }
        $firstchars = str_split($firststring);
        $secondchars = str_split($secondstring);
        foreach ($firstchars as $char) {
            if (in_array($char, $secondchars)) {
                return $char;
            }
        }
        return null;
    }

    /**
     * Make a pmatch_options object from raw form data.
     *
     * Used when validating forms.
     *
     * @param array $data the form data.
     * @return \pmatch_options the options set in the form.
     */
    public static function options_from_form_data(array $data): \pmatch_options {
        // Get the relevant options from the rest of the form.
        $pmatchoptions = new \pmatch_options();
        $pmatchoptions->ignorecase = empty($data['usecase']);
        if (isset($data['sentencedividers'])) {
            $pmatchoptions->sentencedividers = $data['sentencedividers'];
        }
        if (isset($data['sentencedividers'])) {
            $pmatchoptions->converttospace = $data['converttospace'];
        }

        // Including the synonyms.
        $synonyms = [];
        foreach ($data['synonymsdata'] as $key => $synonym) {
            $trimmedword = trim($synonym['word']);
            $trimmedsynonyms = trim($synonym['synonyms']);
            if ($trimmedword == '' && $trimmedsynonyms == '') {
                continue;
            }

            $synonym = new \stdClass();
            $synonym->word = $trimmedword;
            $synonym->synonyms = $trimmedsynonyms;
            $synonyms[] = $synonym;
        }
        $pmatchoptions->set_synonyms($synonyms);

        return $pmatchoptions;
    }

    /**
     * Check validity of the modelanswer if it is given and return the string, otherwise retun null
     *
     * @param array $answers
     * @param array $grades
     * @param string $modelanswer
     * @param \pmatch_options|null $options
     * @return bool
     */
    public static function validate_modelanswer($answers, $grades, $modelanswer, $options = null) {
        // If there is no modelanswer there is no need for validation.
        if ($modelanswer === '') {
            return true;
        }
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if ($trimmedanswer === '') {
                // Not an answer, just part of the form that was left blank.
                continue;
            }
            $expression = new \pmatch_expression($trimmedanswer, $options);
            if (\qtype_pmatch_question::compare_string_with_pmatch_expression(
                    $modelanswer, $trimmedanswer, $expression->get_options())) {
                // This answer matches. Is the grade right?
                return $grades[$key] == 1.0;
            }
        }
        // No matching answers.
        return false;
    }

    /**
     * Add synonyms field to form.
     *
     * @param \moodleform $editform
     * @param \MoodleQuickForm $mform
     * @param \stdClass $question Question's information.
     * @param boolean $showheader Show/hide synonyms header.
     * @param string $elementname Element name.
     * @param int $repeatwhenempty Number of synonyms field will be shown when no synonyms inserted.
     * @param int $repeatwhenexist Number of synonyms field will be shown when synonyms existed.
     */
    public static function add_synonyms($editform, $mform, $question, $showheader, $elementname, $repeatwhenempty,
                                        $repeatwhenexist) {
        if ($showheader) {
            $mform->addElement('header', 'synonymshdr', get_string('synonym', 'qtype_pmatch'));
        }

        $mform->addElement('static', 'synonymsdescription', '',
                get_string('synonymsheader', 'qtype_pmatch'));

        $textboxgroup = [];
        $textboxgroup[] = $mform->createElement('group', $elementname,
                get_string('synonymsno', 'qtype_pmatch', '{no}'), self::add_synonym($mform));
        $repeatedoptions = ['synonymsdata[word]' => ['type' => PARAM_RAW],
                'synonymsdata[synonyms]' => ['type' => PARAM_RAW]];

        if (isset($question->options)) {
            $countsynonyms = count($question->options->synonyms);
        }

        if (empty($countsynonyms)) {
            $repeatsatstart = $repeatwhenempty;
        } else {
            $repeatsatstart = $countsynonyms + $repeatwhenexist;
        }

        $editform->repeat_elements($textboxgroup, $repeatsatstart, $repeatedoptions, 'nosynonyms' . $elementname,
                'addsynonyms' . $elementname, 2, get_string('addmoresynonymblanks', 'qtype_pmatch'), true);
    }

    /**
     * Data_preprocessing implementation for common pmatch options
     *
     * @param stdClass $question
     */
    public static function data_preprocessing_pmatch_options(stdClass $question): void {
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
            $question->synonymsdata = [];
            $key = 0;
            foreach ($synonyms as $synonym) {
                $question->synonymsdata[$key]['word'] = $synonym->word;
                $question->synonymsdata[$key]['synonyms'] = $synonym->synonyms;
                $key++;
            }
        }
    }

    /**
     * Add symnonym field: word and synonyms.
     *
     * @param \MoodleQuickForm $mquickform
     * @return array
     */
    public static function add_synonym($mquickform) {
        $grouparray = [];
        $grouparray[] = $mquickform->createElement('text', 'word',
                get_string('wordwithsynonym', 'qtype_pmatch'), ['size' => 15]);
        $grouparray[] = $mquickform->createElement('text', 'synonyms',
                get_string('synonym', 'qtype_pmatch'), ['size' => 50]);
        return $grouparray;
    }

    /**
     * Check valid or invalid of expression.
     *
     * @param string $expressionstring Inputted expression data. E.g: match(example).
     * @return string Error message when the expression is invalid or empty string if expression is valid.
     */
    public static function validate_pmatch_expression(string $expressionstring): string {
        $expression = new \pmatch_expression($expressionstring);
        return $expression->get_parse_error();
    }

    public static function initialise_pmatch_form_js() {
        global $PAGE;
        $PAGE->requires->js_call_amd('qtype_pmatch/check_valid_expression', 'init');
        $PAGE->requires->js_call_amd('qtype_pmatch/rulecreator', 'init');
        $PAGE->requires->string_for_js('rulecreationtoomanyterms', 'qtype_pmatch');
        $PAGE->requires->string_for_js('rulecreationtoomanyors', 'qtype_pmatch');
        $PAGE->requires->js_call_amd('qtype_pmatch/tryrule', 'init');
    }
}

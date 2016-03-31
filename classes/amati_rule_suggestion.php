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
 * Defines the \qtype_pmatch\test rules class.
 *
 * @package   qtype_pmatch
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/pmatch/question.php');
require_once($CFG->dirroot . '/question/type/pmatch/pmatchlib.php');

/**
 * Question type: Pattern match: Test rules class.
 *
 * Manages the test rules associated with a given question.
 *
 * The key part of translating AMATI rules into pmatch rules is to break the AMATI rules
 * into their constituent subrules and these sub rules into consitutent paramenters. I
 * found command, operator and word to be good (not perfect) parameters to use.
 *
 * Commands denote the general approach to matching such as:
 * * Term expects exact word matches
 * * Template Expects an exact for the first few letters then any characters for the rest of the word
 * * Precedes expects the first word to appear before the second. In AMATI the match can be across
 * sentences, in pmatch it is only with a sentence.
 * * Closely precedes expects the first word to be a maximum of two words before the second word
 *
 * Operators add or exclude a phrase from a match or provide an alternate and include add, esclude and or
 *
 * Words are the target words themselves. For each term and template only one word is allowed but for precedes
 * 2 words are required.
 *
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class amati_rule_suggestion {

    const AMATI_RULE_PREFIX = 'correct_response(A) :-';
    const AMATI_EMPTY_RULE = 'correct_response(A).';
    /**
     * Operators in AMATI rules add or exclude a phrase from a match or provide an alternate and
     * include add, esclude and or
     */
    const RULE_OPERATOR_AND = 'AND';
    const RULE_OPERATOR_NOT = 'NOT';
    const RULE_OPERATOR_OR = 'OR';

    /**
     * Retrieve suggested rules from the amati web service using the given marked responses.
     * @param string $url url to call
     * @param \testquestion_responses[] $responses marked responses to send
     * @return array(string[] $rules, string[] $errors) $rules returned from amati, $errors from web service call.
     */
    public static function load_suggested_rules_from_amati_webservice($url, $responses) {
        \core_php_time_limit::raise(300);// 300 seconds = 5 minutes.
        $responses = \qtype_pmatch\testquestion_responses::responses_to_data($responses);
        $post = array('service' => 'generate_rules', 'responses' => json_encode($responses), '');
        $header[] = "Content-Type: multipart/form-data";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $errors = array();
        $rules = array();
        $data = curl_exec ($ch);
        if ($errno = curl_errno($ch)) {
            $errors[] = curl_error($ch);
        }

        curl_close($ch);

        // If there are no errors then the response must contain rules.
        if (!$errors) {
            $rules = self::load_rules_from_json($data);
        }
        return array($rules, $errors);
    }

    /**
     * Retrieve sample amati rules from the given file.
     * @param string $filepath path to the file containing responses
     * @return string[] array of rules.
     */
    public static function load_rules_from_file($filepath) {
        $content = file_get_contents($filepath);
        return self::load_rules_from_json($content);
    }

    /**
     * Retrieve sample amati rules from the given json string.
     * @param string $json json encoded string from AMATI web service.
     * @return string decoded string
     */
    public static function load_rules_from_json($json) {
        return json_decode($json);
    }

    /**
     * Break an amati rule into separate subrules and parameters to make translation so
     * we can make a matching pmatch rule from the parameters.
     * @param string $rule amati rule
     * @return array Array of subrules broken down into parameters
     */
    public static function get_parameters_from_amati_rule($rule) {
        $subrulesasparameters = array();

        // Return early if there are no rules.
        if ($rule === self::AMATI_EMPTY_RULE) {
            return $subrulesasparameters;
        }
        // Remove prefix correct_response(A) :-.
        $rule = str_replace(self::AMATI_RULE_PREFIX, '', $rule);

        // Add a separator character between sub rules to simplify the separation.
        $rule = str_replace(');', ');|', $rule);
        $rule = str_replace('),', '),|', $rule);

        // Split the AMATI rule into it's sub rules.
        $subrules = explode('|', $rule);
        foreach ($subrules as $subrule) {
              $subrulesasparameters[] = self::get_parameters_from_amati_subrule($subrule, $subrulesasparameters);
        }

        return $subrulesasparameters;
    }

    /**
     * Break an individual sub rule into its constituent parameters of command, operator
     * and word
     *
     * @param string $subrule a rule segment of the parent AMATI rule
     * @param array $subrulesasparameters the parameters already determined for the parent rule
     * @return stdClass[] Array of subrules converted to parameters.
     */
    public static function get_parameters_from_amati_subrule($subrule, $subrulesasparameters) {
        $parameters = new \stdClass();
        // Set defaults.
        $parameters->command = '';
        $parameters->operator = self::RULE_OPERATOR_AND;
        $parameters->word = '';

        // Retrieve the command.
        $command = substr($subrule, 0, strpos($subrule, '('));
        $command = str_replace('_in_response', '', $command);

        // Detect a NOT operator in the command.
        if (strpos($command, 'not')) {
            $parameters->operator = self::RULE_OPERATOR_NOT;
            // Remove the not from the command.
            $command = str_replace('not ', '', $command);
        }

        $parameters->command = trim($command);

        // Remove command from remaining rule.
        $subrule = substr($subrule, strpos($subrule, '('), strlen($subrule));

        // Determine the word. There is just on Word in a template or term.
        if ($parameters->command == 'template' || $parameters->command == 'term') {
            // Word is after first comma.
            $wordend = strpos($subrule, ')');
            $wordtemp = substr($subrule, 0, $wordend);
            $wordstart = strrpos($wordtemp, ',') + 1;

            $parameters->word = substr($wordtemp, $wordstart, strlen($wordtemp));
        } else { // There are two Words in precedes and closely precedes.
            $wordsstart = 1;
            $wordend = strpos($subrule, ')');
            $letters = explode(', ', substr($subrule, $wordsstart, $wordend - $wordsstart));
            $words = array();

            // The letters used in AMATI precedes correspond to words in the previous sub rules.
            $characterstoindex = 'BCDEFGHI';
            foreach ($letters as $letter) {
                $index = strpos($characterstoindex, $letter);
                $word = $subrulesasparameters[$index]->word;
                // Templates need *.
                if ($subrulesasparameters[$index]->command == 'template') {
                    $word .= '*';
                }
                $words[] = $word;
            }
            $parameters->word = $words;
        }

        // Remove word and related symbols from remaining rule.
        $subrule = substr($subrule, $wordend + 1, strlen($subrule));

        // Single semicolons at the start of a word are not found. Add a word before so they are.
        $subrule = str_replace(';', 'semi;', $subrule);
        $or = strpos($subrule, ';');
        // Semicolon is the OR operator..
        if (strpos($subrule, ';')) {
            $parameters->operator = self::RULE_OPERATOR_OR;
        }

        return $parameters;
    }

    /**
     * Return a valid pattern match rule combining from the rules craeted from the
     * parameters of the give subrules
     *
     * AMATI rules are broken down into commands, operators and words. We determine
     * the commands and words to use in self:get_pmatch_sub_rule_from_parameters and
     * also the operator NOT.
     *
     * Here we wrap each sub rule in the operator correct operator using match_all for
     * ADD  and match_any for OR.
     *
     * @param \stdClass[] $subrules Sub rules converted to consyituent parameteres
     * @return string valid pmatch rule
     */
    public static function get_pmatch_rule_from_subrules($subrulesasparameters) {

        // If there are no subrules return early.
        if (!count($subrulesasparameters)) {
            return '';
        }

        // In pmatch The OR and ADD operator wrap the sub rule so we must add them now.
        // Start with default match operator (ADD).
        $rule = 'match_all';
        //  Should the match operator be an OR? Check the operator of the first subrule.
        if ($subrulesasparameters[0]->operator == self::RULE_OPERATOR_OR) {
            $rule = 'match_any';
        }
        $rule .= '(';
        $count = 0;
        // Looop through the array of subrule parameters creating a pmatch rule
        // from each parameter object.
        foreach ($subrulesasparameters as $parameters) {
            $rule = $count ? $rule . ' ' : $rule;
            $rule .= self::get_pmatch_sub_rule_from_parameters($parameters);
            $count++;
        }

        $rule .= ')';
        return $rule;
    }

    /**
     * Return a valid pmatch rule from the given parameters of an AMATI sub rule.
     *
     * AMATI rules are forumlated from commands, operators and words as explained at the root
     * of this class. The equivalent pmatch rules were determined through unit tests in
     * qtype_pmatch_testquestion_amati_rule_suggestion::test_find_pmatch_equivalents_to_amati_commands()
     *
     * Commands are the most complicated rule to translate from amati to pmatch. The four commands are achieved:
     * * Term: An exact match on a word
     * * Template: an exact for the given letters then any characters for the rest of the word
     * * Precedes expects the first word to appear before the second. In AMATI the match can be across
     * sentences, in pmatch it is only with a sentence and uses space ' ' to achieve the match.
     * * Closely precedes expects the first word to be a maximum of two words before the second word
     * and uses an underscore '_' to achieve the match.
     *
     * Operators are achieved by using the pmatch rules match_all for ADD, match_any for OR and not when
     * required.
     * Words are the sequence of alphanumeric characters being matched. Spaces are not allowed so each is an
     * invidiual word, not a collection of words.
     *
     * @param \stdClass $parameters parameters of an AMATI sub rule
     * @return string the new subrule
     */
    public static function get_pmatch_sub_rule_from_parameters($parameters) {
        $rule = '';

        // Make the basic rule based on the command and word(s).
        switch ($parameters->command) {
            case 'term':
                $rule .= 'match_w(' . $parameters->word . ')';
                break;
            case 'template':
                $rule .= 'match_wm(' . $parameters->word . '*)';
                break;
            case 'precedes':
            case 'closely_precedes':
                $rule .= 'match_w(';
                $count = 0;
                // Space ' ' matches within sentence for precedes, underscore '_' matches within 2 words
                // for closely precedes.
                $separator = $parameters->command == 'precedes' ? ' ' : '_';
                foreach ($parameters->word as $word) {
                    $rule .= $word;
                    $rule .= $count ? '' : $separator;
                    $count++;
                }
                $rule .= ')';
                break;
        }

        // Is a not operator required?
        if ($parameters->operator == self::RULE_OPERATOR_NOT) {
            $rule = 'not( ' . $rule . ')';
        }

        return $rule;
    }

    /**
     * Translate an AMATI rule into an equivalent pmatch rule.
     * @param string $rule amati rule to translate to pmatch
     * @return string translated pmatch rule
     */
    public static function get_pmatch_rule_from_amati_rule ($rule) {
        // Translate each rule into an array of parameter objects that describe
        // the AMATI rule.
        $subrulesasparameters = self::get_parameters_from_amati_rule($rule);

        // Convert the parameter objects into equivalent pmatch rules.
        return self::get_pmatch_rule_from_subrules ($subrulesasparameters);
    }

    /**
     * Translate an array of AMATI rules into an equivalent pmatch rules.
     * @param string[] $rule amati rules to translate to pmatch
     * @return string[] translated pmatch rules
     */
    public static function get_pmatch_rules_from_amati_rules ($amatirules) {
        $rules = array();
        foreach ($amatirules as $rule) {
            $pmatchrule = self::get_pmatch_rule_from_amati_rule($rule->rule);
            $rules[$rule->id] = $pmatchrule;
        }

        return $rules;
    }

    /**
     * Suggest pmatch rules using the AMATI rule suggestion service.
     *
     * Submits marked responses to the AMATI web service and translates the received rules
     * into equivalent pmatch rules.
     * @param object $mform question object from a moodle form.
     * @param object $question
     * @return string[]
     */
    public static function suggest_rules($mform, $question) {
        $config = get_config('qtype_pmatch');
        $responses = null;

        if ((!$responses || !count($responses)) && $question->id) {
            $responses = \qtype_pmatch\testquestion_responses::get_responses_by_questionid($question->id);
        }

        // Are there any responses?
        if (!$responses || !count($responses)) {
            throw new \moodle_exception('testthisquestionnoresponsesfound', 'qtype_pmatch');
        }

        // Are there enough responses?
        if (count($responses) < $config->minresponses) {
            $counts = new \stdClass();
            $counts->required = $config->minresponses;
            $counts->existing = count($responses);
            throw new \moodle_exception('testthisquestionnoresmoreponsesrequired', 'qtype_pmatch', '', $counts);
        }

        // Get suggested rules from AMATI web service.
        list($amatirules, $amatierrors) = self::load_suggested_rules_from_amati_webservice($config->amatiwsurl,
                $responses);

        // If there were no rules returned no translation is needed so return early.
        if (!$amatirules || !count($amatirules) || $amatirules[0]->rule == self::AMATI_EMPTY_RULE) {
            return array();
        }

        // Translate the amati rules into pmatch equivalents.
        $suggestedrules = self::get_pmatch_rules_from_amati_rules($amatirules);
        // Ensure the translated rules:
        //  are formatted correctly
        //  are valid
        //  don't duplicate the existing rules.
        $suggestedrules = self::prepare_suggested_rules($question, $suggestedrules);

        // Add the suggested rules to the question object so they are presented correctly
        // as rules in the question editing form.
        self::add_suggested_rules_to_question($question, $suggestedrules);

        return $suggestedrules;
    }

    /**
     * Prepared suggested pmatch rules from the AMATI rule suggestion service for use in pmatch
     * questions.
     *
     * Removes invalid rules, duplicate rules that already exist in the supplied question, and
     * formats the remaining rules in pmatch style.
     * @param object $question question object from a moodle form.
     * @param array $suggestedrules rules returned from the amati suggestion service.
     * @return string[]
     */
    public static function prepare_suggested_rules($question, $suggestedrules) {

        // Format valid rules correctly and remove invalid rules.
        foreach ($suggestedrules as $key => $suggestedrule) {
            $suggestedexpression = new \pmatch_expression($suggestedrule);
            if (!$suggestedexpression->is_valid()) {
                unset($suggestedrules[$key]);
                continue;
            }
            $suggestedrules[$key] = $suggestedexpression->get_formatted_expression_string();
        }

        // Remove rule suggestions that match existing rules.
        foreach ($question->options->answers as $key => $answerdata) {
            $answer = trim($answerdata->answer);
            // Check for, and ignore, completely blank answer from the form.
            if ($answer == '' && $answerdata->fraction == 0) {
                continue;
            }

            $expression = new \pmatch_expression($answer);
            if ($expression->is_valid()) {
                $answer = $expression->get_formatted_expression_string();
            }

            foreach ($suggestedrules as $key => $suggestedrule) {
                if (strtolower($suggestedrule) !== strtolower($answer)) {
                    continue;
                }
                unset($suggestedrules[$key]);
            }
        }

        // Reorder the array removing empty indexes.
        $suggestedrules = array_merge(array(), $suggestedrules);
        return $suggestedrules;
    }

    /**
     * Add the suggested rules to the question object.
     *
     * To ensure the right number of answer fields are created and that they are correctly populated
     * we add the suggested rules to the end of the existing answers array to the question->options
     * property.
     *
     * This must be done in qtype_pmatch_edit_form:: add_per_answer_fields before the edit question
     * form answer fields are created.
     * @param object $question
     * @param string[]
     * @return void
     */
    public static function add_suggested_rules_to_question($question, $suggestedrules) {
        foreach ($suggestedrules as $suggestedrule) {
            $newrule = (object) array(
                'id' => '',
                'question' => $question->id,
                'answer' => $suggestedrule,

                'answerformat' => 0,
                'fraction' => 1.0000000,
                'feedback' => '',
                'feedbackformat' => 1,
            );

            // Add the new rules/answer to the $question->options->answers object so that
            // pmatch can add them to the form ready to be saved if needed.
            $question->options->answers[] = $newrule;
        }
    }
}

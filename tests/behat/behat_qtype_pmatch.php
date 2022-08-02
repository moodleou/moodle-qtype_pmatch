<?php
// This file is part of Stack - http://stack.bham.ac.uk/
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Behat steps definitions for pattern match questions.
 *
 * @package   qtype_pmatch
 * @category  test
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use qtype_pmatch\local\spell\qtype_pmatch_spell_checker;

/**
 * Steps definitions related with the pattern match question type.
 *
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_qtype_pmatch extends behat_base {

    public static $responsesfilepath = "fixtures/myfirstquestion_responses.csv";

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype              | name meaning  | description                                |
     * | test responses        | Question name | The response-testing screen for a question |
     * | test responses upload | Question name | Upload test responses screen               |
     *
     * @param string $type identifies which type of page this is, e.g. 'Preview'.
     * @param string $identifier identifies the particular page, e.g. 'My question'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'test responses':
                return new moodle_url('/question/type/pmatch/testquestion.php',
                        ['id' => $this->find_question_by_name($identifier)]);

            case 'test responses upload':
                return new moodle_url('/question/type/pmatch/uploadresponses.php',
                        ['id' => $this->find_question_by_name($identifier)]);

            default:
                throw new Exception('Unrecognised qtype_pmatch page type "' . $type . '."');
        }
    }

    /**
     * Find a question, and where it is, from the question name.
     *
     * This is a helper used by resolve_page_instance_url.
     *
     * @param string $questionname
     * @return int question id.
     */
    protected function find_question_by_name(string $questionname): int {
        global $DB;
        return (int) $DB->get_field('question', 'id', ['name' => $questionname], MUST_EXIST);
    }

    /**
     * Initialise the default responses for pattern match questions.
     *
     * @param string $questionname name od the question being tested, 'x,y'.
     *
     * @Given /^the default question test responses exist for question "(?P<question_name_string>(?:[^"]|\\")*)"$/
     */
    public function default_question_test_responses_exist_for_question($questionname) {
        $this->intialise_default_responses($questionname);
    }

    /**
     * Load a csv file into an array of response objects reporting feedback
     *
     * @param qtype_pmatch_question $question (optional) question to associate responses with.
     * @param string|null $pathtoresponses responses file to load. Defaults to self::$responsesfilepath.
     * @return array [$responses, $problems].
     */
    protected function load_responses(qtype_pmatch_question $question, string $pathtoresponses = null): array {
        $pathtoresponses = $pathtoresponses ?? self::$responsesfilepath;
        $responsesfile = dirname(__FILE__) . '/../' . $pathtoresponses;

        return qtype_pmatch\testquestion_responses::load_responses_from_file($responsesfile, $question);
    }

    /**
     * Create a default pmatch question object
     *
     * @param string $name the question name to use
     * @return qtype_pmatch_question
     */
    protected function get_question_by_name(string $name): qtype_pmatch_question {
        global $DB;
        $questionid = $DB->get_field('question', 'id', ['name' => $name]);
        /** @var qtype_pmatch_question $question */
        $question = question_bank::load_question($questionid);
        return $question;
    }

    /**
     * load the default result set and store in the database.
     *
     * @param string $questionname the question name to use.
     * @param string|null $pathtoresponses the file of responses to load.
     */
    protected function intialise_default_responses(string $questionname, string $pathtoresponses = null): void {
        $question = $this->get_question_by_name($questionname);

        [$responses] = $this->load_responses($question, $pathtoresponses);

        // Add responses.
        \qtype_pmatch\testquestion_responses::add_responses($responses);
    }

    /**
     * Check that the given Spell checking library already installed.
     *
     * @Given /^I check the "(?P<spell_check_engine_string>(?:[^"]|\\")*)" spell checking library already installed$/
     */
    public function is_spell_checking_library_install($enginename) {
        if ($enginename == 'pspell') {
            if (!function_exists('pspell_new')) {
                throw new \Moodle\BehatExtension\Exception\SkippedException();
            }
        } else if ($enginename == 'enchant') {
            if (!function_exists('enchant_broker_init')) {
                throw new \Moodle\BehatExtension\Exception\SkippedException();
            }
        } else {
            // Not supported checking library.
            throw new \Moodle\BehatExtension\Exception\SkippedException();
        }
    }

    /**
     * Check that dictionary already installed and set the default dictionary.
     *
     * @Given /^I setup the available dictionaries for the pattern-match question type$/
     */
    public function set_default_spell_check_dictionary() {
        $defaultlanguage = get_string('iso6391', 'langconfig');
        $availablelangs = qtype_pmatch_spell_checker::get_available_languages();
        $matched = qtype_pmatch_spell_checker::get_default_spell_check_dictionary($defaultlanguage, $availablelangs);
        set_config('spellcheck_languages', $matched, 'qtype_pmatch');
    }

    /**
     * Check that the given Question type already installed.
     *
     * @Given /^I check the "(?P<question_type_string>(?:[^"]|\\")*)" question type already installed$/
     */
    public function check_question_type_installed($questiontype) {
        $qtypes = question_bank::get_creatable_qtypes();
        if (!array_key_exists($questiontype, $qtypes)) {
            // Question type not available.
            throw new \Moodle\BehatExtension\Exception\SkippedException();
        }
    }
}

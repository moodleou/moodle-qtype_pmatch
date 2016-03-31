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

use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Steps definitions related with the pattern match question type.
 *
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_qtype_pmatch extends behat_base {

    public static $responsesfilepath = "fixtures/myfirstquestion_responses.csv";

    /**
     * Opens a test response home page.
     *
     * @Given /^I am on the pattern match test responses page for question "(?P<question_name_string>(?:[^"]|\\")*)"$/
     */
    public function i_am_on_pattern_match_test_responses_page($questionname) {
        $question = $this->get_question_by_name($questionname);
        $this->getSession()->visit($this->locate_path(
                '/question/type/pmatch/testquestion.php?id=' . $question->id));
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
     * @param qtype_pmatch_question $question (optional) question to associate responses with.
     * @return array $responses, $problems
     */
    protected function load_responses($question, $pathtoresponses = null) {
        if (!$question) {
            throw new ExpectationException('Question not provided', $this->getSession());
        }

        $pathtoresponses = $pathtoresponses ? $pathtoresponses : self::$responsesfilepath;
        $responsesfile = dirname(__FILE__) . '/../' . $pathtoresponses;

        return qtype_pmatch\testquestion_responses::load_responses_from_file($responsesfile, $question);
    }

    /**
     * Create a default pmatch question object
     * @return qtype_pmatch_question
     */
    protected function get_question_by_name($name) {
        global $DB;
        $questionid = $DB->get_field('question', 'id', array('name' => $name));
        $question = question_bank::load_question($questionid);
        return $question;
    }

    /**
     * load the default result set and store in the database.
     * @return array \qtype_pmatch\test_response
     */
    protected function intialise_default_responses($questionname, $pathtoresponses = null) {
        global $DB;
        $question = $this->get_question_by_name($questionname);

        list($responses, $problems) = $this->load_responses($question, $pathtoresponses);

        //  Add responses.
        \qtype_pmatch\testquestion_responses::add_responses($responses);
    }

}

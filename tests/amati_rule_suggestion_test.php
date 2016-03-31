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
 * Test the amati rule suggestion facility.
 *
 * @package   qtype_pmatch
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/question/type/pmatch/tests/testquestion_testcase.php');


/**
 * Establish a test approach for the amati rule suggestion facility using existing fixtures.
 *
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     qtype_pmatch
 */
class qtype_pmatch_testquestion_amati_rule_suggestion extends qtype_pmatch_testquestion_testcase {

    /**
     * At first we didn't know how to write pmatch rules that were the equivalent on AMATI rules so
     * we needed
     * 1) To prove pmatch had equivalents for each AMATI rule
     * 2) To establish a practical workflow to compare AMATI rule matches with Pmatch
     *
     * So these 4 tests don't test a specific method, they prove that it is possible to write
     * Pattern match rules that are equivalent to the many variations of each AMATI rule.
     * They establish equivalent pmatch commands for each amati command including TERM, TEMPLATE,
     * PRECEDES and CLOSELY PRECEDES.
     *
     * We couldn't use amati data because that is private so we used the existing unit test data
     * and added responses and other fixtures. Using this approach::
     * 1) We could Compare amati rules and the responses that match with a pattern match rule/rules that amatch
     * the same responses.
     * 2) We documented which responses weren't matched because each rule was applied to the same list of responses.
     * 3) Where possible we could provide multiple pmatch equivalents to an AMATI rule
     *
     * The results are displayed with the AMATI rule used first and an array of the responses matched as found in
     * the show coverage feature.
     * Then follow the best equivalent pmatch rules with an array of the responses they return.
     *
     *  In most cases exact pmatch equivlaents were found. Occasionaliy we only got close but not exact equivalents,
     *
     *  Differences
     *  AMATI and Pmatch also handle their responses differently. During our tests we discovered these differences
     *  in how ANATI and Pmatch store responses:
     *  1) AMATI stores only alphanumeric data (no punctuation or syumbols) pmatch stores the raw data
     *  2) Amati stores lower case text, pmatch stores either text in the case it is supplied
     */
    public function test_find_pmatch_equivalents_to_amati_commands() {
        $this->find_pmatch_equivalents_to_amati_term_command();
        $this->find_pmatch_equivalents_to_amati_template_command();
        $this->find_pmatch_equivalents_to_amati_precedes_command();
        $this->find_pmatch_equivalents_to_amati_closely_precedes_command();
    }

    /**
     * Find pmatch commands to match the AMATI term command and related operators.
     */
    public function find_pmatch_equivalents_to_amati_term_command() {
        $this->resetAfterTest();

        // First we test with 10 responses against basic  AMATI term rules usiong the Add, Not and
        // Or operators.
        // Set correct expectation.
        $comparerulematches = array(
                // Add.
                // The AMATI rule and responses returned.
                //   'term_in_response(A,tom)' => array(
                //        1 => 'tom dick or harry',
                //        2 => 'tom',
                //        7 => 'tom was janes companion'
                //    )
                // The best match in Pmatch.
                'match_w(tom)' => array(
                        0 => 'Tom Dick or Harry',
                        1 => 'Tom',
                        2 => 'Tom was janes companion'),
                // Try Harry
                // The amati rule with responses.
                //    term_in_response(A,harry)' => array(
                //        1 => 'tom dick or harry',
                //        6 => 'harry'
                //    )
                // The best match in Pmatch.
                'match_w(harry)' => array(
                        0 => 'Tom Dick or Harry',
                        1 => 'Harry'
                    ),
                // Not.
                // The Amati rule used with matched responses.
                // 'not term_in_response(A,tom)' => array(
                //        3 => 'dick',
                //        4 => 'john',
                //        5 => 'tomato',
                //        6 => 'harry',
                //        8 => 'adam',
                //        9 => 'felicity',
                //        10 => '',
                //    ),
                // The pmatch equivalent with matches.
                // Notes:AMATI only stores alphanumeric responses '€£¥©®™±≠≤≥÷×∞µαβπΩ∑' became '' so
                // these are equivlanet matches.
                'not(match_w(tom))' => array(
                        0 => 'Dick',
                        1 => 'John',
                        2 => 'Tomato',
                        3 => 'Harry',
                        4 => 'Adam',
                        5 => 'Felicity',
                        6 => '€£¥©®™±≠≤≥÷×∞µαβπΩ∑',
                    ),
                // Or.
                // term_in_response(A,B,felicity); term_in_response(A,C,dick)' => array(
                //        1 => 'tom dick or harry',
                //        3 => 'dick',
                //        9 => 'felicity'
                //    ),
                // The pmatch equivalent with matches.
                'match_w(dick|felicity)' => array(
                        0 => 'Tom Dick or Harry',
                        1 => 'Dick',
                        2 => 'Felicity'
                    ),
                // Another equivalent rule.
                'match_any(match_w(dick) match_w(felicity))' => array(
                        0 => 'Tom Dick or Harry',
                        1 => 'Dick',
                        2 => 'Felicity'
                    )
            );

        // Get the responses which match the rules and test them.
        $responseandrulematches = $this->grade_responses($comparerulematches, 10);
        $this->assertEquals($comparerulematches, $responseandrulematches);

        // Next, test with 30 responses against basic versions of Add, Not and Or.See if
        // The pmatch commands still work.
        // Set correct expectation.
        $comparerulematches = array(
                // A Single term.
                // 'term_in_response(A,tom)' => array(
                //        1 => 'tom dick or harry',
                //        2 => 'tom',
                //        7 => 'tom was janes companion',
                //        27 => 'tom is janes companion'
                //    ),.
            'match_w(tom)' => array(
                    0 => 'Tom Dick or Harry',
                    1 => 'Tom',
                    2 => 'Tom was janes companion',
                    3 => 'tom is jane\'s companion'),
                // Another single term.
                // 'term_in_response(A,harry)' => array(
                //        1 => 'tom dick or harry',
                //        6 => 'harry',
                //        28 => 'harry is janes buddy'
                //    ).
            'match_w(harry)' => array(
                    0 => 'Tom Dick or Harry',
                    1 => 'Harry',
                    2 => 'harry is jane\'s buddy'
                ),
                // A rule combining And, or and Not.
                // 'term_in_response(A,B,mate); term_in_response(A,C,friend), not term_in_response(A,D,harrriet)' => array(
                //        25 => 'richard is janes friend',
                //        26 => 'thomas is janes mate'
                //    ).
            'match_all(match_any(match_w(friend) match_w(mate)) not(match_w(harrriet)))' => array(
                    0 => 'Richard is jane\'s friend',
                    1 => 'Thomas is jane\'s mate',
                ),
            );
        // Get the responses which match the rules and test them.
        $responseandrulematches = $this->grade_responses($comparerulematches, 30);
        $this->assertEquals($comparerulematches, $responseandrulematches);
    }

    /**
     * Find pmatch commands to match the AMATI template command and related operators.
     */
    public function find_pmatch_equivalents_to_amati_template_command() {
        $this->resetAfterTest();

         // Set correct expectation.
        $comparerulematches = array(
                // A single template command.
                // 'template_in_response(A,tom)' => array(
                //        1 => 'tom dick or harry',
                //        2 => 'tom',
                //        5 => 'tomato',
                //        7 => 'tom was janes companion',
                //        13 => 'tomcat',
                //        27 => 'tom is janes companion'
                //    ).
                    'match_wmr(tom*)' => array(
                            0 => 'Tom Dick or Harry',
                            1 => 'Tom',
                            2 => 'Tomato',
                            3 => 'Tom was janes companion',
                            4 => 'Tomcat',
                            5 => 'tom is jane\'s companion'),
                // Another single template command.
                // 'template_in_response(A,harry)' => array(
                //        1 => 'tom dick or harry',
                //        6 => 'harry',
                //        15 => 'harriet',
                //        28 => 'harry is janes buddy',
                //        29 => 'harriet is janes companion',
                //        30 => 'harrriet is janes most treasured friend and companion'
                //    ),.
                    'match_wm(harry*)' => array(
                            0 => 'Tom Dick or Harry',
                            1 => 'Harry',
                            2 => 'Harriet',
                            3 => 'harry is jane\'s buddy',
                            4 => 'harriet is jane\'s companion',
                            5 => 'harrriet is jane\'s most treasured friend and companion'
                        ),
                // A template rule using not(exclude).
                // 'template_in_response(A,B,tom), not template_in_response(A,C,companion)' => array(
                //        1 => 'tom dick or harry',
                //        2 => 'tom',
                //        5 => 'tomato',
                //        13 => 'tomcat'
                //    ).
                    'match_all(match_wm(tom*) not(match_wm(companion*)))' => array(
                            0 => 'Tom Dick or Harry',
                            1 => 'Tom',
                            2 => 'Tomato',
                            3 => 'Tomcat',
                            // Amati doesn't return this.
                            4 => 'Thomas is jane\'s mate'
                        )
                );

        // Get the responses which match the rules and test them.
        $responseandrulematches = $this->grade_responses($comparerulematches, 30);
        $this->assertEquals($comparerulematches, $responseandrulematches);
    }

    /**
     * Test grading responses by a precedes rule.
     *
     *  Pmatch proximity option does not allow the matching words to be more than 4 words
     * apart or to span sentences. So the results we expect do not match exactly those AMATI would give.
     */
    public function find_pmatch_equivalents_to_amati_precedes_command() {
        $this->resetAfterTest();

        // Set the correct expectation.
        $comparerulematches = array(
                // A single AMATI precedes rule.
                // 'term_in_response(A,B,is), term_in_response(A,C,companion), precedes(B, C).' => array(
                //        24 => 'frederick is janes companion',
                //        27 => 'tom is janes companion',
                //        29 => 'harriet is janes companion',
                //        30 => 'harrriet is janes most treasured friend and companion',
                //        32 => 'tim is not janes favourite close companion',
                //        33 => 'tim is janes closest companion',
                //        35 -> 'dick is janes most trusted confidante best friend and closest companion'
                //    ).

                // First attempt using pmatch wp4 parameters and _ between target words to match words
                // with a max of 4 words apart.
                // Pmatch misses these 2 responses because it cannot match words more than 4 words apart.
                //  'harrriet is jane\'s most treasured friend and companion'
                //  'Dick is jane\'s most trusted confidante, best friend and closest companion'.
                'match_wp4(is_companion)' => array(
                        0 => 'Frederick is jane\'s companion',
                        1 => 'tom is jane\'s companion',
                        2 => 'harriet is jane\'s companion',
                        3 => 'tim is not jane\'s favourite close companion',
                        4 => 'tim is jane\'s closest companion'),
                // Second attempt matches perfectly. This time  using a space ( ) between target words to
                // match is precedes companion any where in a sentence.
                'match_w(is companion)' => array(
                        0 => 'Frederick is jane\'s companion',
                        1 => 'tom is jane\'s companion',
                        2 => 'harriet is jane\'s companion',
                        3 => 'harrriet is jane\'s most treasured friend and companion',
                        4 => 'tim is not jane\'s favourite close companion',
                        5 => 'tim is jane\'s closest companion',
                        6 => 'Dick is jane\'s most trusted confidante, best friend and closest companion'),
                // An extra test using syntax from translating parameters to pmatch rules.
                // It's laid out in the format AMATI precedes rules are laid out with each target word
                // in its own term or template match first then the precedes check.
                // So I just tested this format would work correctly.
                'match_all(match_wm(is) match_w(companion) match_w(is companion))' => array(
                         0 => 'Frederick is jane\'s companion',
                        1 => 'tom is jane\'s companion',
                        2 => 'harriet is jane\'s companion',
                        3 => 'harrriet is jane\'s most treasured friend and companion',
                        4 => 'tim is not jane\'s favourite close companion',
                        5 => 'tim is jane\'s closest companion',
                        6 => 'Dick is jane\'s most trusted confidante, best friend and closest companion'
                    ),
                // I noticed templates hadn't been using the test so I added a quick test here to
                // see what would happen. Though I didn't have the time to run the matching AMATI test at
                // the same time.
                'match_all(match_wm(tom*) match_wm(harry*) match_w(tom* harry*))' => array(
                        0 => 'Tom Dick or Harry'
                    )
            );

        // Get the responses which match the rules and test them.
        $responseandrulematches = $this->grade_responses($comparerulematches, 35);
        $this->assertEquals($comparerulematches, $responseandrulematches);
    }

    /**
     * Test grading responses by a closely precedes rule.
     */
    public function find_pmatch_equivalents_to_amati_closely_precedes_command() {
        $this->resetAfterTest();

        // The rule should match these responses.
        $comparerulematches = array(
                // An AMATI rules using closely_precedes and the responses it matches.
                // We needed to confirm what the difference was between precedes and closely precedes. Is it just
                // the number of words allowed between the target words?
                // 'term_in_response(A,B,is), term_in_response(A,C,companion), closely_precedes(B, C).' => array(
                //        24 => 'frederick is janes companion',
                //        27 => 'tom is janes companion',
                //        29 => 'harriet is janes companion',
                //        33 => 'tim is janes closest companion',
                //    )
                // An equivalent pmatch rule using _ to which only allows 2 words between matching words.
                // This proved that closely_precedes matches target words not more than 2 words apart.
                'match_w(is_companion)' => array(
                        0 => 'Frederick is jane\'s companion',
                        1 => 'tom is jane\'s companion',
                        2 => 'harriet is jane\'s companion',
                        3 => 'tim is jane\'s closest companion'),
                // An equivalent pmatch rule using the p2 parameter to achieve the same result.
                'match_wp2(is_companion)' => array(
                        0 => 'Frederick is jane\'s companion',
                        1 => 'tom is jane\'s companion',
                        2 => 'harriet is jane\'s companion',
                        3 => 'tim is jane\'s closest companion'),
                // Testing an equivalent template rule which also gives the same result.
                'match_w(is*_companion*)' => array(
                        0 => 'Frederick is jane\'s companion',
                        1 => 'tom is jane\'s companion',
                        2 => 'harriet is jane\'s companion',
                        3 => 'tim is jane\'s closest companion'),
            );

        // Get the responses which match the rules and test them.
        $responseandrulematches = $this->grade_responses($comparerulematches, 35);
        $this->assertEquals($comparerulematches, $responseandrulematches);
    }

    /**
     * Test separating amati term rules from the web service into their constituent parameters.
     *
     * With the previous tests we have determined which pmatch parameters are equivalent to each type of
     * AMATI rule and therefore what each AMATI rule and parameter actually does. Next we start translating
     * AMATI rules into Pmatch equivalents.
     *
     * The first part of translating AMATI rules into pmatch rules is to break the AMATI rules
     * into their constituent parts. I found command, operator and word to be good (not perfect)
     * parameters to use.
     * Commands denote the general approach to matching such as:
     * * Term expects exact word matches
     * * Template Expects an exact for the first few letters then any characters for the rest of the word
     * * Precedes expects the first word to appear before the second. In AMATI the match can be across
     * sentences, in pmatch it is only with a sentence.
     * * Closely precedes expects the first word to be a maximum of two words before the second word
     *
     * Operators add or exclude a phrase from a match or provide an alternate and include add, esclude and or
     * words are the target words themselves. For each term and template only one word is allowed but for precedes
     * 2 words are required.
     *
     * The amati rules we use are taken from the previous tests and we assert that the given rule is broken down
     * into the parameters in the following array.
     */
    public function test_get_parameters_from_amati_rules() {
        $this->get_parameters_from_amati_term_rules();
        $this->get_parameters_from_amati_template_rules();
        $this->get_parameters_from_amati_precedes_rules();
        $this->get_parameters_from_amati_closely_precedes_rules();
        $this->get_parameters_from_amati_complex_rules();
    }

    /**
     * Test separating amati term rules from the web service into their constituent parameters.
     */
    public function get_parameters_from_amati_term_rules() {

        // Set the expectation.
        $comparerulesandparameters = array (
            // These AMATI rules should be broken down into their associated array of parameters.
            "correct_response(A) :- term_in_response(A,tom)." => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'tom'
                        )
                ),
            "correct_response(A) :- term_in_response(A,B,tom), term_in_response(A,C,harry)." => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'harry'
                        )
                ),
            "correct_response(A) :- not term_in_response(A,B,harry)." => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'NOT',
                            'word' => 'harry'
                        )
                ),
            "correct_response(A) :- term_in_response(A,B,tom), not term_in_response(A,C,harry)." => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'term',
                            'operator' => 'NOT',
                            'word' => 'harry'
                        )
                ),
            "correct_response(A) :- term_in_response(A,B,tom); term_in_response(A,C,harry)." => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'OR',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'harry'
                        )
                )
        );

        $rulesandparameters = $this->get_parameters_from_amati_rules($comparerulesandparameters);

        // Check the result matches the expected result.
        $this->assertEquals($comparerulesandparameters, $rulesandparameters);
    }

    /**
     * Test separating amati template rules from the web service into their constituent parameters.
     */
    public function get_parameters_from_amati_template_rules() {

        // Set the expectation.
        $comparerulesandparameters = array (
            // These AMATI rules should be broken down into their associated array of parameters.
            "correct_response(A) :- template_in_response(A,tom)." => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'tom'
                        )
                ),
            "correct_response(A) :- template_in_response(A,B,tom), template_in_response(A,C,harry)." => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'harry'
                        )
                ),
            "correct_response(A) :- not template_in_response(A,B,harry)." => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'NOT',
                            'word' => 'harry'
                        )
                ),
            "correct_response(A) :- template_in_response(A,B,tom), not template_in_response(A,C,harry)." => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'template',
                            'operator' => 'NOT',
                            'word' => 'harry'
                        )
                )
        );

        $rulesandparameters = $this->get_parameters_from_amati_rules($comparerulesandparameters);

        // Check the result matches the expected result.
        $this->assertEquals($comparerulesandparameters, $rulesandparameters);
    }

    /**
     * Test separating amati precedes rules from the web service into their constituent parameters.
     */
    public function get_parameters_from_amati_precedes_rules() {

        // Set the expectation.
        $comparerulesandparameters = array (
            // This AMATI rule should be broken down into its associated array of parameters.
            "correct_response(A) :- template_in_response(A,B,jane), template_in_response(A,C,comp), precedes(B, C)." => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'jane'
                        ),
                    1 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'comp'
                        ),
                    2 => (object) array
                        (
                            'operator' => 'AND',
                            'command' => 'precedes',
                            'word' => array (
                                            0 => 'jane*',
                                            1 => 'comp*'
                                        )
                        )
                )
        );

        $rulesandparameters = $this->get_parameters_from_amati_rules($comparerulesandparameters);

        // Check the result matches the expected result.
        $this->assertEquals($comparerulesandparameters, $rulesandparameters);
    }

    /**
     * Test separating amati closely precedes rules from the web service into their constituent parameters.
     */
    public function get_parameters_from_amati_closely_precedes_rules() {

        // Set the expectation.
        $comparerulesandparameters = array (
            // This AMATI rule should be broken down into its associated array of parameters.
            "correct_response(A) :- template_in_response(A,B,jane), template_in_response(A,C,comp), closely_precedes(B, C)." => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'jane'
                        ),
                    1 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'comp'
                        ),
                    2 => (object) array (
                            'command' => 'closely_precedes',
                            'operator' => 'AND',
                            'word' => array (
                                            0 => 'jane*',
                                            1 => 'comp*'
                                        )
                        )
                )
        );

        $rulesandparameters = $this->get_parameters_from_amati_rules($comparerulesandparameters);

        // Check the result matches the expected result.
        $this->assertEquals($comparerulesandparameters, $rulesandparameters);
    }

    /**
     * Test separating amati complex rules from the web service into their constituent parameters.
     *
     * During initial development I tried a few random rule combinations and created working tests.
     * They test multiple commands and combine several sub rules into one rules which is how AMATI and
     * Pmatch rules are used in real life. These test go beyond the previous tests that focus on one
     * command unfortunately I only had time to provide a few. A more extenisve list would be very
     * helpful.
     */
    public function get_parameters_from_amati_complex_rules() {

        // Set the expectation.
        $comparerulesandparameters = array (
            // These AMATI rules should be broken down into their associated array of parameters.
            "correct_response(A) :- template_in_response(A,B,comp), term_in_response(A,C,tom), template_in_response(A,D,jane)." => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'comp'
                        ),
                    1 => (object) array  (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    2 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'jane'
                        )
                ),
            "correct_response(A) :- template_in_response(A,B,jane), template_in_response(A,C,comp), not term_in_response(A,D,not)." => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'jane'
                        ),
                    1 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'comp'
                        ),
                    2 => (object) array (
                            'command' => 'term',
                            'operator' => 'NOT',
                            'word' => 'not'
                        ),
                ),
            "correct_response(A) :- not template_in_response(A,B,annoy), term_in_response(A,C,dick), not term_in_response(A,D,dont)." => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'NOT',
                            'word' => 'annoy'
                        ),
                    1 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'dick'
                        ),
                    2 => (object) array (
                            'command' => 'term',
                            'operator' => 'NOT',
                            'word' => 'dont'
                        )
                ),
            "correct_response(A) :- term_in_response(A,B,tom); term_in_response(A,C,dick); term_in_response(A,D,harry), not template_in_response(A,E,annoy)." => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'OR',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'term',
                            'operator' => 'OR',
                            'word' => 'dick'
                        ),
                    2 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'harry'
                        ),
                    3 => (object) array (
                            'command' => 'template',
                            'operator' => 'NOT',
                            'word' => 'annoy'
                        )
                )
        );

        $rulesandparameters = $this->get_parameters_from_amati_rules($comparerulesandparameters);

        // Check the result matches the expected result.
        $this->assertEquals($comparerulesandparameters, $rulesandparameters);
    }

    /**
     * Test generating pattern match rules from rule parameters.
     *
     * Now that we can convert AMATI rules into arrays of parameters we can now convert
     * the parameters into valid PMatch rules. We won't test that they match responses yet, we are just
     * testing each part of the process before we test the whole later.
     */
    public function test_get_pmatch_rules_from_rule_parameters() {
        $this->get_pmatch_rule_from_term_rule_parameters();
        $this->get_pmatch_rule_from_template_rule_parameters();
        $this->get_pmatch_rule_from_precedes_rule_parameters();
        $this->get_pmatch_rule_from_closely_precedes_rule_parameters();
    }

    /**
     * Test generating pattern match rules from parameters based on rules containing terms.
     */
    public function get_pmatch_rule_from_term_rule_parameters() {

        // Set the expectation.
        $comparerulesandparameters = array (
            // These Pmatch rules should be generated from their associated array of parameters.
            'match_all(match_w(tom))' => array (
                    0 => (object) array  (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'tom'
                        )
                ),
            "match_all(match_w(tom) match_w(harry))" => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'harry'
                        )
                ),
            "match_all(not( match_w(harry)))" => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'NOT',
                            'word' => 'harry'
                        )
                ),
            "match_all(match_w(tom) not( match_w(harry)))" => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'term',
                            'operator' => 'NOT',
                            'word' => 'harry'
                        )
                ),
            "match_any(match_w(tom) match_w(harry))" => array (
                    0 => (object) array (
                            'command' => 'term',
                            'operator' => 'OR',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'term',
                            'operator' => 'AND',
                            'word' => 'harry'
                        )
                )
            );

        $rulesandparameters = $this->get_pmatch_rules_from_parameters($comparerulesandparameters);

        // Check the result matches the expected result.
        $this->assertEquals($comparerulesandparameters, $rulesandparameters);
        $this->check_valid_rules(array_keys($rulesandparameters));
    }

    /**
     * Test generating pattern match rules from parameters based on rules containing templates.
     */
    public function get_pmatch_rule_from_template_rule_parameters() {

        // Set the expectation.
        $comparerulesandparameters = array (
            // These Pmatch rules should be generated from their associated array of parameters.
            'match_all(match_wm(tom*))' => array (
                    0 => (object) array  (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'tom'
                        )
                ),
            "match_all(match_wm(tom*) match_wm(harry*))" => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'harry'
                        )
                ),
            "match_all(not( match_wm(harry*)))" => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'NOT',
                            'word' => 'harry'
                        )
                ),
            "match_all(match_wm(tom*) not( match_wm(harry*)))" => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'template',
                            'operator' => 'NOT',
                            'word' => 'harry'
                        )
                )
            );

        $rulesandparameters = $this->get_pmatch_rules_from_parameters($comparerulesandparameters);

        // Check the result matches the expected result.
        $this->assertEquals($comparerulesandparameters, $rulesandparameters);
        $this->check_valid_rules(array_keys($rulesandparameters));
    }

    protected function check_valid_rules ($rules) {
        foreach ($rules as $rule) {
            $expression = new pmatch_expression($rule);
            $this->assertTrue($expression->is_valid());
        }
    }

    /**
     * Test generating pattern match rules from parameters based on rules containing precedes.
     */
    public function get_pmatch_rule_from_precedes_rule_parameters() {

        // Set the expectation.
        $comparerulesandparameters = array (
            // These Pmatch rules should be generated from their associated array of parameters.
            "match_all(match_wm(tom*) match_wm(harry*) match_w(tom* harry*))" => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'harry'
                        ),
                    2 => (object) array (
                            'command' => 'precedes',
                            'operator' => 'AND',
                            'word' => array (
                                            0 => 'tom*',
                                            1 => 'harry*'
                                        )
                        )
                )
        );

        $rulesandparameters = $this->get_pmatch_rules_from_parameters($comparerulesandparameters);

        // Check the result matches the expected result.
        $this->assertEquals($comparerulesandparameters, $rulesandparameters);
        $this->check_valid_rules(array_keys($rulesandparameters));
    }

    /**
     * Test generating pattern match rules from parameters based on rules containing precedes.
     */
    public function get_pmatch_rule_from_closely_precedes_rule_parameters() {

        // Set the expectation.
        $comparerulesandparameters = array (
            // These Pmatch rules should be generated from their associated array of parameters.
            "match_all(match_wm(tom*) match_wm(harry*) match_w(tom*_harry*))" => array (
                    0 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'tom'
                        ),
                    1 => (object) array (
                            'command' => 'template',
                            'operator' => 'AND',
                            'word' => 'harry'
                        ),
                    2 => (object) array (
                            'command' => 'closely_precedes',
                            'operator' => 'AND',
                            'word' => array (
                                            0 => 'tom*',
                                            1 => 'harry*'
                                        )
                        )
                )
        );

        $rulesandparameters = $this->get_pmatch_rules_from_parameters($comparerulesandparameters);

        // Check the result matches the expected result.
        $this->assertEquals($comparerulesandparameters, $rulesandparameters);
        $this->check_valid_rules(array_keys($rulesandparameters));
    }

    /**
     * Test generating pattern match rules from amati rules containing terms.
     *
     * Now that we have tested each part of the translation process we can now test the whole
     * We can make sure that each amati rule is correctly translated to a valid pmatch rule.
     */
    public function test_get_pmatch_rules_from_amati_term_rules() {
        $this->get_pmatch_rules_from_amati_term_rules();
        $this->get_pmatch_rules_from_amati_template_rules();
        $this->get_pmatch_rules_from_amati_precedes_rules();
        $this->get_pmatch_rules_from_amati_closely_precedes_rules();
    }

    /**
     * Test generating pattern match rules from amati rules containing terms.
     */
    public function get_pmatch_rules_from_amati_term_rules() {

        // Set the expectation.
        $comparerules = array (
            // Each Pmatch rule should be generated from the AMATI rule that follows it.
            'match_all(match_w(tom))' => "correct_response(A) :- term_in_response(A,tom).",
            "match_all(match_w(tom) match_w(harry))" =>
                "correct_response(A) :- term_in_response(A,B,tom), term_in_response(A,C,harry).",
            "match_all(match_w(tom) not( match_w(harry)))" =>
                "correct_response(A) :- term_in_response(A,B,tom), not term_in_response(A,C,harry).",
            "match_any(match_w(tom) match_w(harry))" =>
                "correct_response(A) :- term_in_response(A,B,tom); term_in_response(A,C,harry)."
        );

        $rules = $this->get_pmatch_rules_from_amati_rules($comparerules);

        // Check the result matches the expected result.
        $this->assertEquals($comparerules, $rules);
        $this->check_valid_rules(array_keys($rules));
    }

    /**
     * Test generating pattern match rules from amati rules containing termplates.
     */
    public function get_pmatch_rules_from_amati_template_rules() {

        // Set the expectation.
        $comparerules = array (
            // Each Pmatch rule should be generated from the AMATI rule that follows it.
            'match_all(match_wm(tom*))' => "correct_response(A) :- template_in_response(A,tom).",
            "match_all(match_wm(tom*) match_wm(harry*))" =>
                "correct_response(A) :- template_in_response(A,B,tom), template_in_response(A,C,harry).",
            "match_all(not( match_wm(harry*)))" =>
                "correct_response(A) :- not template_in_response(A,B,harry).",
            "match_all(match_wm(tom*) not( match_wm(harry*)))" =>
                "correct_response(A) :- template_in_response(A,B,tom), not template_in_response(A,C,harry)."
        );

        $rules = $this->get_pmatch_rules_from_amati_rules($comparerules);

        // Check the result matches the expected result.
        $this->assertEquals($comparerules, $rules);
        $this->check_valid_rules(array_keys($rules));
    }

    /**
     * Test generating pattern match rules from amati rules containing precedes.
     */
    public function get_pmatch_rules_from_amati_precedes_rules() {

            // Set the expectation.
        $comparerules = array (
            // Each Pmatch rule should be generated from the AMATI rule that follows it.
            "match_all(match_wm(tom*) match_wm(harry*) match_w(tom* harry*))" =>
                "correct_response(A) :- template_in_response(A,B,tom), template_in_response(A,C,harry), precedes(B, C)."
        );

        $rules = $this->get_pmatch_rules_from_amati_rules($comparerules);

        // Check the result matches the expected result.
        $this->assertEquals($comparerules, $rules);
        $this->check_valid_rules(array_keys($rules));
    }

    /**
     * Test generating pattern match rules from amati rules containing closely precedes.
     */
    public function get_pmatch_rules_from_amati_closely_precedes_rules() {

        // Set the expectation.
        $comparerules = array (
            // Each Pmatch rule should be generated from the AMATI rule that follows it.
            "match_all(match_wm(tom*) match_wm(harry*) match_w(tom*_harry*))" =>
                "correct_response(A) :- template_in_response(A,B,tom), template_in_response(A,C,harry), closely_precedes(B, C)."
        );

        $rules = $this->get_pmatch_rules_from_amati_rules($comparerules);

        // Check the result matches the expected result.
        $this->assertEquals($comparerules, $rules);
        $this->check_valid_rules(array_keys($rules));
    }

    /**
     * Prepare the suggested rules from AMATI to be added to a pmatch question.
     * - Remove any suggested rules that duplicate the existing pmatch rules
     * - Remove and invalid rules
     * - Format the rules in pmatch format
     *
     */
    public function test_prepare_suggested_rules() {
        $this->resetAfterTest();

        // Start with these rules..
        $suggestedrules = array(
                "match_all(match_w(tom))",
                "match_all(match_w(tom) match_w(harry))",
                "match_all(not( match_w(harry)))",
                "match_all(match_w(tom) not( match_w(harry)))",
                // Add some invalid rules that should be removed.
                'match_any(not match_w(tom))',
                'match_any(match_w tom))',
                'match_all(match_wm(Felicity) match_w(dick))'
        );

         // Set the right expectation. These are the rules we should be left with.
        $comparesuggestedrules = array(
                "match_all(match_w(tom))",
                "match_all(not( match_w(harry)))",
                "match_all(match_w(tom) not( match_w(harry)))",
                'match_all(match_wm(Felicity) match_w(dick))'
        );
        // Format comparison rules for pmatch.
        $comparesuggestedrules = $this->format_rules($comparesuggestedrules);

        // Load the question.
        $this->currentquestion = $this->create_default_question();

        // Set correct existing rules.
        $this->currentquestion->options = new \stdClass();
        $this->currentquestion->options->answers = array();
        $this->currentquestion->options->answers[17] = new question_answer(17,
                'match_all(match_w(Tom) match_w(harry))', 1.0, '', FORMAT_HTML);
        $this->currentquestion->options->answers[18] = new question_answer(18,
                'match_any(match_w(tom) match_w(dick) match_w(harry) not( match_wm(annoy*)))', 1.0,
                '.', FORMAT_HTML);

        // Ensure the question object has the relevant form rule fields.
        $this->add_question_form_fields($this->currentquestion);

        // Run the test.
        $suggestedrules = \qtype_pmatch\amati_rule_suggestion::prepare_suggested_rules($this->currentquestion, $suggestedrules);

        // Check the results.
        $this->assertEquals($comparesuggestedrules, $suggestedrules);
        $this->check_valid_rules($suggestedrules);
    }

    protected function get_pmatch_rules_from_amati_rules($comparerules) {
        // Translate each rule into parameters.
        $rules = array();
        foreach ($comparerules as $rule) {
            $pmatchrule = qtype_pmatch\amati_rule_suggestion::get_pmatch_rule_from_amati_rule($rule);
            $rules[$pmatchrule] = $rule;
        }

        return $rules;
    }

    protected function format_rules($rules) {
        // Apply pmatch Formatting to  each rule.
        foreach ($rules as $key => $rule) {
            $expression = new \pmatch_expression($rule);
            if (!$expression->is_valid()) {
                continue;
            }
            $rules[$key] = $expression->get_formatted_expression_string();
        }
        return $rules;
    }

    protected function get_pmatch_rules_from_parameters($comparerulesandparameters) {
        // Translate each rule into parameters.
        $rulesandparameters = array();
        foreach ($comparerulesandparameters as $key => $subrules) {
            $rule = qtype_pmatch\amati_rule_suggestion::get_pmatch_rule_from_subrules($subrules);
            $rulesandparameters[$rule] = $subrules;
        }

        return $rulesandparameters;
    }

    protected function get_parameters_from_amati_rules($comparerulesandparameters) {
        // Get the AMATI rules fixture.
        $rules = $this->load_rules();

        // Create a rule lookup table.
        $rulestoindex = array();
        foreach ($rules as $index => $rule) {
            $rulestoindex[$rule->rule] = $index;
        }

        // Translate each rule into parameters.
        $rulesandparameters = array();
        foreach ($comparerulesandparameters as $key => $subrules) {
            $rule = $rules[$rulestoindex[$key]];
            $parameters = qtype_pmatch\amati_rule_suggestion::get_parameters_from_amati_rule($rule->rule);
            $rulesandparameters[$key] = $parameters;
        }

        return $rulesandparameters;
    }

    /**
     * Helper method returning the responses with rules that have been matched.
     * @param unknown $responses
     * @return array
     */
    protected function get_matched_responses($responses) {
        $matchedresponses = array();
        foreach ($responses as $response) {
            if (!count($response->ruleids)) {
                continue;
            }
            $matchedresponses[$response->id] = $response;
        }

        return $matchedresponses;
    }

    /**
     * Replace the existing question rules with the given rules.
     */
    protected function set_question_rules($newruleanswers, $question) {
        $newrules = array();
        $ruleid = 0;
        foreach ($newruleanswers as $newruleanswers) {
            $ruleid++;
            $newrules[$ruleid] = new question_answer($ruleid,
                                      $newruleanswers, 0.0, 'Feedback for rule: ' . $newruleanswers, FORMAT_HTML);
        }

        $question->answers = $newrules;
    }

    /**
     * Create a default pmatch question form object used in questiontype.php forms
     * @return qtype_pmatch_question
     */
    protected function add_question_form_fields($question) {
        // Convert answers object to separate arrays.
        $answers = $question->get_answers();
        $index = 0;
        $question->answer = array();
        $question->fraction = array();
        $question->feedback = array();
        foreach ($answers as $answer) {
            $question->answer[$index] = $answer->answer;
            $question->fraction[$index] = $answer->fraction;
            $question->feedback[$index] = array(
                array('text' => $answer->feedback, 'format' => FORMAT_HTML),
            );
            $index++;
        }
        return $question;
    }

    /**
     * Grade given responses according to given rule match data.
     */
    protected function grade_responses($comparerulematches, $responsecount=0, $responses=null) {
        if (!$responses) {
            $responses = $this->load_default_responses('fixtures/testresponseslong.csv', $responsecount);
        }
        // Set correct case sensitivity.
        $this->currentquestion->pmatchoptions->ignorecase = true;
        // Save the rules to the question.
        $this->set_question_rules(array_keys($comparerulematches), $this->currentquestion);
        $rules = $this->currentquestion->get_answers();
        foreach ($rules as $rule) {
            \qtype_pmatch\testquestion_responses::grade_responses_by_rule($responses, $rule, $this->currentquestion);
        }

        return $this->get_rule_matches($responses, $rules);
    }

    /*
     * Load rules from a given file path or the default rule path.
     * @param $filepath string path to file
     * @return string file contents
     */
    public function load_rules($filepath=null) {
        global $CFG;
        $filepath = $filepath ? $filepath : self::$rulesfilepath;
        $filepath = dirname(__FILE__) . '/' . $filepath;
        return qtype_pmatch\amati_rule_suggestion::load_rules_from_file($filepath);
    }
}

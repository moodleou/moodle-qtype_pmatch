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
 * This file contains of the pmatch library using files of examples.
 *
 * @package   qtype_pmatch
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/pmatch/tests/testquestion_testcase.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');

/**
 * Test the responses used in the test this question function.
 *
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     qtype_pmatch
 */
class qtype_pmatch_testquestion_responses_test extends qtype_pmatch_testquestion_testcase {

    /**
     * Test basic instantiation of the test_response class.
     */
    public function test_intialise_test_response() {
        $this->resetAfterTest();
        // No data returns null.
        $testresponse = \qtype_pmatch\testquestion_response::create();
        $this->assertEquals($testresponse, null);

        // Initialise a test_response object with proper data.
        $data = new stdClass();
        $data->id = 1000;
        $data->questionid = 1900;
        $data->expectedfraction = 0;
        $data->gradedfraction = 0;
        $data->response = 0;
        $testresponse = \qtype_pmatch\testquestion_response::create($data);

        // No contents.
        $this->assertEquals($testresponse->id, $data->id);
        $this->assertEquals($testresponse->questionid, $data->questionid);
        $this->assertEquals($testresponse->response, $data->response);
        $this->assertEquals($testresponse->expectedfraction, $data->expectedfraction);
        $this->assertEquals($testresponse->gradedfraction, $data->gradedfraction);

        // Set grade fraction.
        $testresponse->set_gradedfraction(0.5);
        $this->assertEquals($testresponse->gradedfraction, 1);

        $testresponse->set_gradedfraction(0.4);
        $this->assertEquals($testresponse->gradedfraction, 0);

        // Fraction fields contain floating numbers.
        $data->expectedfraction = 0.9999;
        $data->gradedfraction = 0.0001;
        $testresponse = \qtype_pmatch\testquestion_response::create($data);

        $this->assertEquals($testresponse->expectedfraction, 1);
        $this->assertEquals($testresponse->gradedfraction, 0);
    }

    /**
     * Test data is correctly converted to a response object.
     */
    public function test_data_to_responses() {
        $this->resetAfterTest();
        // Empty array.
        $responses = \qtype_pmatch\testquestion_responses::data_to_responses(array());
        $this->assertEquals($responses, array());

        // One class with all fields filled out.
        $data = new stdClass();
        $data->id = 1000;
        $data->questionid = 1900;
        $data->expectedfraction = 1;
        $data->gradedfraction = 1;
        $data->response = 'one two';

        $responses = \qtype_pmatch\testquestion_responses::data_to_responses(array($data));
        $testresponse = array_pop($responses);

        // No contents.
        $this->assertEquals($testresponse->id, $data->id);
        $this->assertEquals($testresponse->questionid, $data->questionid);
        $this->assertEquals($testresponse->response, $data->response);
        $this->assertEquals($testresponse->expectedfraction, $data->expectedfraction);
        $this->assertEquals($testresponse->gradedfraction, $data->gradedfraction);
    }

    /**
     * Test basic instantiation of the test_response class.
     */
    public function test_intialise_test_responses_create_for_question() {
        // An example using the DB.
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');

        // An example not using the DB.
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();

        // Before we test the DB.
        $testresponse = \qtype_pmatch\testquestion_responses::create_for_question($question);
        $this->assertEquals(get_class($testresponse), 'qtype_pmatch\testquestion_responses');
    }

    /**
     * Test responses are correctly retrieved by id.
     */
    public function test_get_responses_by_ids() {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('qtype_pmatch');

        // Create responses.
        $responses = array();
        for ($x = 0; $x < 10; $x++) {
            $response = $generator->create_test_response();
            $responses[$response->id] = $response;
        }

        $responses = \qtype_pmatch\testquestion_responses::data_to_responses($responses);

        // Get an array of the ids.
        $responseids = array_keys($responses);

        // Get responses udsing the ids array.
        $dbresponses = \qtype_pmatch\testquestion_responses::get_responses_by_ids($responseids);
        $this->assertEquals($responses, $dbresponses);
    }

    /**
     * Test that responses are correctly added to the db.
     */
    public function test_add_responses() {
        global $DB;
        $this->resetAfterTest();

        $question = $this->create_default_question();

        list($responses, $problems) = $this->load_responses($question);

        // Add responses to an empty DB table and get feedback.
        $feedback = \qtype_pmatch\testquestion_responses::add_responses($responses);
        $dbresponses = $DB->get_records('qtype_pmatch_test_responses');

        // Check results.
        $this->assertEquals(count($dbresponses), 18);
        $this->assertEquals($feedback->saved, 18);
        $this->assertEquals(count($feedback->duplicates), 0);

        // Test for duplicates by adding responses for the second time.
        $feedback = \qtype_pmatch\testquestion_responses::add_responses($responses);
        $dbresponses = $DB->get_records('qtype_pmatch_test_responses');

        // Check results.
        $this->assertEquals(count($dbresponses), 18);
        $this->assertEquals($feedback->saved, 0);
        $this->assertEquals(count($feedback->duplicates), 18);

        // Add the same data for a different question.
        $response = $responses[0];
        $response->questionid = 2;

        $feedback = \qtype_pmatch\testquestion_responses::add_responses(array($response));
        $dbresponses = $DB->get_records('qtype_pmatch_test_responses');

        // Check results.
        $this->assertEquals(count($dbresponses), 19);
        $this->assertEquals($feedback->saved, 1);
        $this->assertEquals(count($feedback->duplicates), 0);
    }

    /**
     * Test update_response.
     */
    public function test_update_response() {
        global $DB;
        $this->resetAfterTest();

        $responses = $this->load_default_responses();

        // Get one response and update it.
        $response = array_pop($responses);
        $response->gradedfraction = 1;
        $response->response = "this is updated";
        $updated = \qtype_pmatch\testquestion_responses::update_response($response);

        $dbresponse = $DB->get_record('qtype_pmatch_test_responses', array('id' => $response->id));
        // Convert to test_response object.
        $dbresponse = \qtype_pmatch\testquestion_response::create($dbresponse);

        // Confirm the updated response is returned from the db.
        $this->assertEquals($response, $dbresponse);
        $this->assertEquals($updated, true);
    }

    /**
     * Test delete_responses_by_ids.
     */
    public function test_delete_responses_by_ids() {
        global $DB;
        $this->resetAfterTest();

        $responses = $this->load_default_responses();

        // Get one response and delete it.
        $response = array_pop($responses);
        \qtype_pmatch\testquestion_responses::delete_responses_by_ids(array($response->id));

        // Confirm only the correct response was deleted.
        $dbresponseids = array_keys($DB->get_records('qtype_pmatch_test_responses'));
        $responseids = array_keys($responses);
        $this->assertEquals($responseids, $dbresponseids);
    }

    /**
     * Test get_question_grade_summary_counts.
     */
    public function test_get_question_grade_summary_counts() {
        global $DB;
        $this->resetAfterTest();

        $responses = $this->load_default_responses();

        $expectedcounts = $counts = new \stdClass();
        $expectedcounts->correct = 0;
        $expectedcounts->total = 18;
        $expectedcounts->misspositive = 0;
        $expectedcounts->missnegative = 0;
        $expectedcounts->accuracy = 0;

        // Get current grade counts.
        $actualcounts = \qtype_pmatch\testquestion_responses::get_question_grade_summary_counts($this->currentquestion);

        // Confirm counts for unmarked grades.
        $this->assertEquals($expectedcounts, $actualcounts);
        $params = array('questionid' => $this->currentquestion->id);
        $params['expectedfraction'] = 0;
        $params['gradedfraction'] = 0;

        // Update computer marked grade from fixture and saved to DB.
        $this->update_response_grades_from_file($responses);

        // Update expectations.
        $expectedcounts = $counts = new \stdClass();
        $expectedcounts->correct = 8;
        $expectedcounts->total = 18;
        $expectedcounts->misspositive = 2;
        $expectedcounts->missnegative = 3;
        $expectedcounts->accuracy = 44.0;

        // Get current grade counts.
        $actualcounts = \qtype_pmatch\testquestion_responses::get_question_grade_summary_counts($this->currentquestion);

        // Confirm counts for grades now they have been marked by the computer.
        $this->assertEquals($expectedcounts, $actualcounts);
    }

    /**
     * Tests grade_response.
     */
    public function test_grade_response() {
        global $DB;
        $this->resetAfterTest();

        $responses = $this->load_default_responses();

        // Test grading for a correct response.
        foreach ($responses as $r) {
            if ($r->response == 'Tom Dick or Harry') {
                $response = $r;
            }
            if ($r->response == 'Felicity') {
                $wrongresponse = $r;
            }
        }
        $expectedcomputergrade = 1;
        \qtype_pmatch\testquestion_responses::grade_response($response, $this->currentquestion);

        $dbresponses = \qtype_pmatch\testquestion_responses::get_responses_by_ids(array($response->id));
        $dbresponse = array_shift($dbresponses);
        $actualcomputergrade = $dbresponse->gradedfraction;

        // Confirm graded correct.
        $this->assertEquals($expectedcomputergrade, $actualcomputergrade);

        // Test grading for a wrong response.
        $expectedcomputergrade = 0;
        \qtype_pmatch\testquestion_responses::grade_response($wrongresponse, $this->currentquestion);

        $dbresponses = \qtype_pmatch\testquestion_responses::get_responses_by_ids(array($wrongresponse->id));
        $dbresponse = array_pop($dbresponses);
        $actualcomputergrade = $dbresponse->gradedfraction;

        // Confirm graded wrong.
        $this->assertEquals($expectedcomputergrade, $actualcomputergrade);
    }

    /**
     * Test load_responses using the helper. This was the original method prior to the amati
     * testquestion as used above.
     */
    public function test_load_responses_from_file() {
        $this->resetAfterTest();
        $question = qtype_pmatch_test_helper::make_a_pmatch_question();
        $question->id = 1;
        $responsesfile = 'fixtures/myfirstquestion_responses.csv';
        list($responses, $problems) = $this->load_responses($question, $responsesfile);

        // Create expected responses.
        $data = array(
                array(1, "testing one two three four"),
                array(0, "testing"),
                array(1, "one"),
                array(1, "two"),
                array(1, "three"),
                array(1, "four"),
                array(0, "for"),
                array(0, "free"),
                array(0, "€£¥©®™±≠≤≥÷×∞µαβπΩ∑"),
                array(0, "one not two but three and four."),
                array(1, "another test"),
                array(null, 'testing anything.'),
                array(null, '')
        );
        $expectedresponses = array();
        foreach ($data as $datarow) {
            $response = new \qtype_pmatch\testquestion_response();
            $response->questionid = $question->id;
            $response->response = $datarow[1];
            $response->expectedfraction = $datarow[0];
            $expectedresponses[] = $response;
        }

        // Test problems.
        $expectedproblems = array(
                'Each row should contain exactly two items, a numerical mark and a response.' .
                ' Row <b>11</b> contains <b>3</b> item(s).'
        );

        $this->assertEquals($expectedproblems, $problems);

        // Test responses.
        $this->assertEquals($expectedresponses, $responses);
    }

    /**
     * Test the try rule function.
     */
    public function test_try_rule() {
        $this->resetAfterTest();
        $responses = $this->load_default_responses();
        foreach ($responses as $response) {
            \qtype_pmatch\testquestion_responses::grade_response($response, $this->currentquestion);
        }
        $ruletxt = 'match_w(A non existant bit of text)';
        $grade = 1;
        $try = \qtype_pmatch\testquestion_responses::try_rule($this->currentquestion, $ruletxt, $grade);
        $expected = '<div>This rule does not match any graded responses.</div>';
        $this->assertEquals($expected, $try);
        $ruletxt = 'match_w(Tom)';
        $try = \qtype_pmatch\testquestion_responses::try_rule($this->currentquestion, $ruletxt, $grade);
        // Note at this point try will contain ids that could change, and will look something like:
        // '<div><div>Effect on sample responses</div><div>Responses not matched above: 5 <br> Correctly matched by this rule: 1,
        // <span class="qtype_pmatch-selftest-missed-positive">Incorrectly matched: 0</span> <br>
        // Responses still to be processed below: 4</div><div>Coverage</div><div><ul><li>' .
        //
        // '<span>133000: Tom Dick or Harry</span></li><li>' .
        // '<span class="qtype_pmatch-selftest-missed-positive">133001: Tom</span></li><li>' .
        // '<span>133004: Tom was janes companion</span></li></ul></div>'.
        // So lets just look for some elements of text.
        $effectresponses = 'Responses not matched above: 15 <br> Correctly matched by this rule: 2, ' .
                '<span class="qtype_pmatch-selftest-missed-positive">Incorrectly matched: 1</span> ' .
                '<br> Responses still to be processed below: 12';
        $this->assertTrue(strpos($try, $effectresponses) !== false);
        $this->assertTrue(strpos($try, 'Tom Dick or Harry') !== false);
        $this->assertTrue(strpos($try, 'Tom was janes companion') !== false);
        $this->assertTrue(strpos($try, 'qtype_pmatch-selftest-missed-positive') !== false);
    }

    /**
     * Test grading a response by a rule.
     *
     * Explore which method is used to grade a response to a specific rule.
     * question/type/questionbase.php::grade_response() the root method used by pmatch
     * grade_response calles grading strategy question_first_matching_answer_grading_strategy::grade()
     * which uses pmatch/question.php::compare_response_with_answer()
     */
    public function test_grade_rule_with_response() {
        $this->resetAfterTest();
        $this->currentquestion = $this->create_default_question();
        $answers = $this->currentquestion->get_answers();

        // Test grading for a correct response.
        // Note the response is fixed here as using load_default_responses gives unreliable results.
        $response = (object) array('response' => 'Tom or Dick');
        $answerstoruleids = array();
        foreach ($answers as $aid => $answer) {
            $match = false;
            $match = $this->currentquestion->compare_response_with_answer(array('answer' => $response->response), $answer);
            if ($match === true) {
                $response->ruleids[] = $aid;
            }
            $answerstoruleids[$answer->answer] = $aid;
        }

        $this->assertEquals(array($answerstoruleids['match_w(Tom|Harry)'],
                $answerstoruleids['match_w(Dick)']),
                $response->ruleids);
    }

    /**
     * Test grading responses by a rule.
     */
    public function test_grade_rule_with_responses() {
        $this->resetAfterTest();
        $this->currentquestion = $this->create_default_question();
        $rules = $this->currentquestion->get_answers();
        $rule = $rules[array_keys($rules)[0]];
        $responses = $this->load_default_responses();

        $responseids = array_keys($responses);
        $compareresponses = \qtype_pmatch\testquestion_responses::get_responses_by_ids($responseids);
        $responsestoruleids = array(
                'Tom Dick or Harry' => array(13),
                'Tom' => array(13),
                'Harry' => array(13),
                'Tom was janes companion' => array(13)
        );

        foreach ($compareresponses as $compareresponse) {
            if (array_key_exists($compareresponse->response, $responsestoruleids)) {
                $compareresponse->ruleids = $responsestoruleids[$compareresponse->response];
            }
        }

        // Test grading for a correct response.
        \qtype_pmatch\testquestion_responses::grade_responses_by_rule($responses, $rule, $this->currentquestion);

        $this->assertEquals($compareresponses, $responses);
    }

    /**
     * Test individual grading rule accuracy.
     */
    public function test_get_rule_accuracy_counts() {
        $this->resetAfterTest();

        $this->currentquestion = $this->create_default_question();
        $rules = $this->currentquestion->get_answers();
        $rule = $rules[array_keys($rules)[0]];
        $responses = $this->load_default_responses();
        // Update computer marked grade from fixture and saved to DB.
        $this->update_response_grades_from_file($responses, 'fixtures/testresponsesgraded.csv');
        // Grade a response and save results to the qtype_pmatch_rule_matches table.
        \qtype_pmatch\testquestion_responses::save_rule_matches($this->currentquestion);

        $responseids = array_keys($responses);
        $matches = \qtype_pmatch\testquestion_responses::get_rule_matches_for_responses($responseids, $this->currentquestion->id);

        $compareaccuracy = [
                'class' => 'qtype_pmatch-selftest-missed-positive',
                'responseneedmatch' => 18,
                'responsestillprocess' => 14,
                'correctlymatched' => 0,
                'incorrectlymatched' => 4,
        ];
        $responsesnegative = $responses;
        $responsespostive = $responses;
        // Test grading for a correct response.
        $accuracy = \qtype_pmatch\testquestion_responses::get_rule_accuracy_counts($responsespostive, $rule, $matches);

        $this->assertEquals($compareaccuracy, $accuracy);
        $rule->fraction = 0;
        $compareaccuracy['class'] = 'qtype_pmatch-selftest-missed-negative';
        // Test grading for a correct response.
        $accuracy = \qtype_pmatch\testquestion_responses::get_rule_accuracy_counts($responsesnegative, $rule, $matches);
        $this->assertEquals($compareaccuracy, $accuracy);
    }

    /**
     * Test re-grading.
     *
     * Test the grading process including saving rule matches works correctly by re grading responses
     * after updating a rule.
     */
    public function test_regrading() {
        global $DB;
        $this->resetAfterTest();

        // Start with responses with a null computer grade.
        $responses = $this->load_default_responses();
        $tomcat = $tomdickharry = '';
        foreach ($responses as $r) {
            if ($r->response == 'Tom Dick or Harry') {
                $tomdickharry = $r->gradedfraction;
            }
            if ($r->response == 'Tomcat') {
                $tomcat = $r->gradedfraction;
            }
        }
        // Confirm computer mark grade is null.
        $this->assertTrue(is_null($tomdickharry));
        $this->assertTrue(is_null($tomcat));

        // Update computer marked grades to 1.0 using a fixture.
        $this->update_response_grades_from_file($responses, 'fixtures/testresponsesgraded.csv');
        $dbresponses = $DB->get_records('qtype_pmatch_test_responses');
        foreach ($dbresponses as $r) {
            if ($r->response == 'Tom Dick or Harry') {
                $tomdickharry = $r->gradedfraction;
            }
            if ($r->response == 'Tomcat') {
                $tomcat = $r->gradedfraction;
            }
        }
        $this->assertEquals(1.0, $tomdickharry);
        $this->assertEquals(1.0, $tomcat); // Note fixture file has computer mark incorrect.

        // Grade a response and check the computer marked grades are now correct.
        \qtype_pmatch\testquestion_responses::grade_responses_and_save_matches($this->currentquestion);
        $dbresponses = $DB->get_records('qtype_pmatch_test_responses');
        foreach ($dbresponses as $r) {
            if ($r->response == 'Tom Dick or Harry') {
                $tomdickharry = $r->gradedfraction;
            }
            if ($r->response == 'Tomcat') {
                $tomcat = $r->gradedfraction;
            }
        }
        $this->assertEquals(1.0, $tomdickharry);
        $this->assertEquals(0.0, $tomcat); // Note re-grading is marking $tomcat correctly now.

        // Update the question rules and check re-grading assigns computer marks correctly.
        $rules = $this->currentquestion->get_answers();
        $rules[13]->answer = 'match_w(Tomcat)';
        $this->currentquestion->answers = $rules;
        \qtype_pmatch\testquestion_responses::grade_responses_and_save_matches($this->currentquestion);
        $dbresponses = $DB->get_records('qtype_pmatch_test_responses');
        foreach ($dbresponses as $r) {
            if ($r->response == 'Tom Dick or Harry') {
                $tomdickharry = $r->gradedfraction;
            }
            if ($r->response == 'Tomcat') {
                $tomcat = $r->gradedfraction;
            }
        }
        $this->assertEquals(1.0, $tomdickharry); // Note does not change.
        $this->assertEquals(1.0, $tomcat); // Note new rule affects $tomcat.
    }

    /**
     * Test the rule matching table
     */
    public function test_save_rule_matches() {
        $this->resetAfterTest();

        $responses = $this->load_default_responses();
        // Update computer marked grade from fixture and saved to DB.
        $this->update_response_grades_from_file($responses, 'fixtures/testresponsesgraded.csv');

        $rules = $this->currentquestion->get_answers();
        // Update the question rules.
        $this->currentquestion->answers = $rules;

        $responseids = array_keys($responses);
        $comparerulematches = array(
                'responseidstoruleids' => array(
                        'Tom Dick or Harry' => array(0 => 'match_w(Tom|Harry)', 1 => 'match_w(Dick)'),
                        'Tom' => array(0 => 'match_w(Tom|Harry)'),
                        'Dick' => array(0 => 'match_w(Dick)'),
                        'Harry' => array(0 => 'match_w(Tom|Harry)'),
                        'Tom was janes companion' => array(0 => 'match_w(Tom|Harry)'),
                        'Felicity' => array(0 => 'match_w(Felicity)'),
                ),
                'ruleidstoresponseids' => array(
                        'match_w(Tom|Harry)' => array(0 => 'Tom Dick or Harry', 1 => 'Tom', 2 => 'Harry',
                                3 => 'Tom was janes companion'),
                        'match_w(Dick)' => array(0 => 'Tom Dick or Harry', 1 => 'Dick'),
                        'match_w(Felicity)' => array(0 => "Felicity"),
                )
        );
        // Grade a response and save results to the qtype_pmatch_rule_matches table.
        \qtype_pmatch\testquestion_responses::save_rule_matches($this->currentquestion);

        // Determine which rules match which response using data from table qtype_pmatch_rule_matches.
        $rulematches = \qtype_pmatch\testquestion_responses::get_rule_matches_for_responses($responseids,
                $this->currentquestion->id);

        // Translate the rule and response ids into responses and rules to test.
        $responseandrulematches = $this->get_rule_matches_as_responses_and_rules($rulematches,
                $rules, $responses);

        $this->assertEquals($comparerulematches, $responseandrulematches);

        // Delete a rule.
        // Delete existing rule matches for the question.
        \qtype_pmatch\testquestion_responses::delete_rule_matches($this->currentquestion);

        // Set new expectations.
        $deletedrulecomparerulematches = array(
                'responseidstoruleids' => array(
                        'Tom Dick or Harry' => array(0 => 'match_w(Tom|Harry)', 1 => 'match_w(Dick)'),
                        'Tom' => array(0 => 'match_w(Tom|Harry)'),
                        'Dick' => array(0 => 'match_w(Dick)'),
                        'Harry' => array(0 => 'match_w(Tom|Harry)'),
                        'Tom was janes companion' => array(0 => 'match_w(Tom|Harry)'),
                ),
                'ruleidstoresponseids' => array(
                        'match_w(Tom|Harry)' => array(0 => 'Tom Dick or Harry', 1 => 'Tom', 2 => 'Harry',
                                3 => 'Tom was janes companion'),
                        'match_w(Dick)' => array(0 => "Tom Dick or Harry", 1 => "Dick"),
                ),
        );

        $deletedrule = array_pop($rules);
        // Update the question rules.
        $this->currentquestion->answers = $rules;

        // Grade a response and save results to the qtype_pmatch_rule_matches table.
        \qtype_pmatch\testquestion_responses::save_rule_matches($this->currentquestion);

        // Determine which rules match which response using data from table qtype_pmatch_rule_matches.
        $rulematches = \qtype_pmatch\testquestion_responses::get_rule_matches_for_responses($responseids,
                $this->currentquestion->id);

        // Translate the rule and response ids into responses and rules to test.
        $responseandrulematches = $this->get_rule_matches_as_responses_and_rules($rulematches, $rules, $responses);

        $this->assertEquals($deletedrulecomparerulematches, $responseandrulematches);

        // Add the rule back
        // Delete existing rule matches for the question.
        \qtype_pmatch\testquestion_responses::delete_rule_matches($this->currentquestion);

        // Restore the deleted rule.
        array_push($rules, $deletedrule);

        // Update the question rules.
        $this->currentquestion->answers = $rules;

        // Grade a response and save results to the qtype_pmatch_rule_matches table.
        \qtype_pmatch\testquestion_responses::save_rule_matches($this->currentquestion);

        // Determine which rules match which response using data from table qtype_pmatch_rule_matches.
        $rulematches = \qtype_pmatch\testquestion_responses::get_rule_matches_for_responses($responseids,
                $this->currentquestion->id);

        // Translate the rule and response ids into responses and rules to test.
        $responseandrulematches = $this->get_rule_matches_as_responses_and_rules($rulematches, $rules, $responses);

        $this->assertEquals($comparerulematches, $responseandrulematches);
    }

    /**
     * Test validate function with json file type.
     */
    public function test_validate_json_file_type() {
        $this->resetAfterTest();

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_json_error_1.json';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnless', $errcase));
        $this->assertTrue($errcase['columnless']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_json_error_2.json';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('row', $errcase));
        $this->assertTrue($errcase['row']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_json_error_3.json';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnbigger', $errcase));
        $this->assertTrue($errcase['columnbigger']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_json_normal.json';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertEquals([], $errcase);
    }

    /**
     * Test validate_html_file_type function.
     */
    public function test_validate_html_file_type() {
        $this->resetAfterTest();

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_html_error_1.html';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnless', $errcase));
        $this->assertTrue($errcase['columnless']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_html_error_2.html';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('row', $errcase));
        $this->assertTrue($errcase['row']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_html_error_3.html';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnbigger', $errcase));
        $this->assertTrue($errcase['columnbigger']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_html_normal.html';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertEquals([], $errcase);
    }

    /**
     * Test validate_csv_file_type function.
     */
    public function test_validate_csv_file_type() {
        $this->resetAfterTest();

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_csv_error_1.csv';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnless', $errcase));
        $this->assertTrue($errcase['columnless']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_csv_error_2.csv';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('row', $errcase));
        $this->assertTrue($errcase['row']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_csv_error_3.csv';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnbigger', $errcase));
        $this->assertTrue($errcase['columnbigger']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_csv_normal.csv';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertEquals([
                'row' => false,
                'columnbigger' => false,
                'columnless' => false,
        ], $errcase);
    }

    /**
     * Test validate_xlsx_file_type function.
     */
    public function test_validate_xlsx_file_type() {
        $this->resetAfterTest();

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_xlsx_error_1.xlsx';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnless', $errcase));
        $this->assertTrue($errcase['columnless']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_xlsx_error_2.xlsx';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('row', $errcase));
        $this->assertTrue($errcase['row']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_xlsx_error_3.xlsx';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnbigger', $errcase));
        $this->assertTrue($errcase['columnbigger']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_xlsx_normal.xlsx';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertEquals([
                'row' => false,
                'columnbigger' => false,
                'columnless' => false,
        ], $errcase);
    }

    /**
     * Test validate_ods_file_type function.
     */
    public function test_validate_ods_file_type() {
        $this->resetAfterTest();

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_ods_error_1.ods';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnless', $errcase));
        $this->assertTrue($errcase['columnless']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_ods_error_2.ods';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('row', $errcase));
        $this->assertTrue($errcase['row']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_ods_error_3.ods';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertTrue(array_key_exists('columnbigger', $errcase));
        $this->assertTrue($errcase['columnbigger']);

        $responsesfile = dirname(__FILE__) . '/' . 'fixtures/testreponses_ods_normal.ods';
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $errcase = $importer->validate();
        $this->assertEquals([
                'row' => false,
                'columnbigger' => false,
                'columnless' => false,
        ], $errcase);
    }

    /**
     * Test get responses function
     *
     * @dataProvider get_responses_cases
     *
     * @param string $responsesfile Response file path
     * @param int $expectedtotalrows Expected total rows
     * @param array $expectedrows List of expected rows
     */
    public function test_get_responses($responsesfile, $expectedtotalrows, $expectedrows) {
        $this->resetAfterTest();
        $testquestionimporthelper = new \qtype_pmatch\testquestion_import_helper($responsesfile);
        $importer = $testquestionimporthelper->import_factory();
        $importer->open($responsesfile);
        $content = $importer->get_responses();

        $this->assertCount($expectedtotalrows, $content);
        foreach ($expectedrows as $key => $expectedrow) {
            $this->assertEquals($expectedrow[0], $content[$key][0]);
            $this->assertEquals($expectedrow[1], $content[$key][1]);
        }
    }

    public function get_responses_cases() {
        return [
                [
                        dirname(__FILE__) . '/' . 'fixtures/testreponses_json_normal.json',
                        4,
                        [
                                [0, 'Response 1'],
                                [1, 'Response 2'],
                                [0, 'Response 3'],
                                [1, 'Response 4']
                        ]
                ],
                [
                        dirname(__FILE__) . '/' . 'fixtures/testreponses_html_normal.html',
                        4,
                        [
                                [0, 'Response 1'],
                                [1, 'Response 2'],
                                [0, 'Response 3'],
                                [1, 'Response 4']
                        ]
                ],
                [
                        dirname(__FILE__) . '/' . 'fixtures/testreponses_csv_normal.csv',
                        4,
                        [
                                [0, 'Response 1'],
                                [1, 'Response 2'],
                                [0, 'Response 3'],
                                [1, 'Response 4']
                        ]
                ],
                [
                        dirname(__FILE__) . '/' . 'fixtures/testreponses_xlsx_normal.xlsx',
                        4,
                        [
                                [0, 'Response 1'],
                                [1, 'Response 2'],
                                [0, 'Response 3'],
                                [1, 'Response 4']
                        ]
                ],
                [
                        dirname(__FILE__) . '/' . 'fixtures/testreponses_ods_normal.ods',
                        4,
                        [
                                [0, 'Response 1'],
                                [1, 'Response 2'],
                                [0, 'Response 3'],
                                [1, 'Response 4']
                        ]
                ]
        ];
    }

    /**
     * Test export function for qtype_pmatch
     */
    public function test_xml_export() {
        global $CFG;
        $this->resetAfterTest();

        $qdata = test_question_maker::get_question_data('pmatch', 'test0');

        $testresponse = [];
        $data = new stdClass();
        $data->id = 1000;
        $data->questionid = $qdata->id;
        $data->expectedfraction = '0';
        $data->gradedfraction = '0';
        $data->response = 'one two';
        $testresponse[] = \qtype_pmatch\testquestion_response::create($data);
        $data = new stdClass();
        $data->id = 1001;
        $data->questionid = $qdata->id;
        $data->expectedfraction = '1';
        $data->gradedfraction = '1';
        $data->response = 'one two three';
        $testresponse[] = \qtype_pmatch\testquestion_response::create($data);
        \qtype_pmatch\testquestion_responses::add_responses($testresponse);

        $exporter = new qformat_xml();
        $xml = $exporter->writequestion($qdata);
        if ($CFG->branch > 35) {
            $expectedxml = '<!-- question: 1  -->
  <question type="pmatch">
    <name>
      <text>test-0</text>
    </name>
    <questiontext format="html">
      <text>Listen, translate and write</text>
    </questiontext>
    <generalfeedback format="html">
      <text></text>
    </generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0.3333333</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <usecase>0</usecase>
    <allowsubscript>0</allowsubscript>
    <allowsuperscript>0</allowsuperscript>
    <forcelength>1</forcelength>
    <applydictionarycheck>en_GB</applydictionarycheck>
    <extenddictionary></extenddictionary>
    <sentencedividers>.?!</sentencedividers>
    <converttospace>,;:</converttospace>
    <modelanswer>testing one two three four</modelanswer>
    <answer fraction="100" format="plain_text">
      <text>match (testing one two three four)</text>
      <feedback format="moodle_auto_format">
        <text>Well done!</text>
      </feedback>
    </answer>
    <answer fraction="0" format="plain_text">
      <text>*</text>
      <feedback format="moodle_auto_format">
        <text>Sorry, no.</text>
      </feedback>
    </answer>
    <synonym>
      <word>
        <text>any</text>
      </word>
      <synonyms>
        <text>testing|one|two|three|four</text>
      </synonyms>
    </synonym>
    <testquestionresponse>
      <response>
        <text>one two</text>
      </response>
      <expectedfraction>
        <text>0</text>
      </expectedfraction>
      <gradedfraction>
        <text>0</text>
      </gradedfraction>
    </testquestionresponse>
    <testquestionresponse>
      <response>
        <text>one two three</text>
      </response>
      <expectedfraction>
        <text>1</text>
      </expectedfraction>
      <gradedfraction>
        <text>1</text>
      </gradedfraction>
    </testquestionresponse>
    <hint format="html">
      <text>Hint 1</text>
    </hint>
    <hint format="html">
      <text>Hint 2</text>
    </hint>
  </question>
';
        } else {
            $expectedxml = '<!-- question: 1  -->
  <question type="pmatch">
    <name>
      <text>test-0</text>
    </name>
    <questiontext format="html">
      <text>Listen, translate and write</text>
    </questiontext>
    <generalfeedback format="html">
      <text></text>
    </generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0.3333333</penalty>
    <hidden>0</hidden>
    <usecase>0</usecase>
    <allowsubscript>0</allowsubscript>
    <allowsuperscript>0</allowsuperscript>
    <forcelength>1</forcelength>
    <applydictionarycheck>en_GB</applydictionarycheck>
    <extenddictionary></extenddictionary>
    <sentencedividers>.?!</sentencedividers>
    <converttospace>,;:</converttospace>
    <modelanswer>testing one two three four</modelanswer>
    <answer fraction="100" format="plain_text">
      <text>match (testing one two three four)</text>
      <feedback format="moodle_auto_format">
        <text>Well done!</text>
      </feedback>
    </answer>
    <answer fraction="0" format="plain_text">
      <text>*</text>
      <feedback format="moodle_auto_format">
        <text>Sorry, no.</text>
      </feedback>
    </answer>
    <synonym>
      <word>
        <text>any</text>
      </word>
      <synonyms>
        <text>testing|one|two|three|four</text>
      </synonyms>
    </synonym>
    <testquestionresponse>
      <response>
        <text>one two</text>
      </response>
      <expectedfraction>
        <text>0</text>
      </expectedfraction>
      <gradedfraction>
        <text>0</text>
      </gradedfraction>
    </testquestionresponse>
    <testquestionresponse>
      <response>
        <text>one two three</text>
      </response>
      <expectedfraction>
        <text>1</text>
      </expectedfraction>
      <gradedfraction>
        <text>1</text>
      </gradedfraction>
    </testquestionresponse>
    <hint format="html">
      <text>Hint 1</text>
    </hint>
    <hint format="html">
      <text>Hint 2</text>
    </hint>
  </question>
';
        }
        $this->assert_same_xml($expectedxml, $xml);
    }

    /**
     * Test import function for qtype_pmatch
     */
    public function test_xml_import() {
        global $CFG;
        $this->resetAfterTest();
        $xml = '<!-- question: 0  -->
  <question type="pmatch">
    <name>
      <text>test-0</text>
    </name>
    <questiontext format="html">
      <text>Listen, translate and write</text>
    </questiontext>
    <generalfeedback format="html">
      <text></text>
    </generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0.3333333</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <usecase>0</usecase>
    <allowsubscript>0</allowsubscript>
    <allowsuperscript>0</allowsuperscript>
    <forcelength>1</forcelength>
    <applydictionarycheck>en_GB</applydictionarycheck>
    <extenddictionary></extenddictionary>
    <sentencedividers>.?!</sentencedividers>
    <converttospace>,;:</converttospace>
    <modelanswer></modelanswer>
    <answer fraction="100" format="plain_text">
      <text>match (testing one two three four)</text>
      <feedback format="moodle_auto_format">
        <text>Well done!</text>
      </feedback>
    </answer>
    <answer fraction="0" format="plain_text">
      <text>*</text>
      <feedback format="moodle_auto_format">
        <text>Sorry, no.</text>
      </feedback>
    </answer>
    <synonym>
      <word>
        <text>any</text>
      </word>
      <synonyms>
        <text>testing|one|two|three|four</text>
      </synonyms>
    </synonym>
    <testquestionresponse>
      <response>
        <text>one two</text>
      </response>
      <expectedfraction>
        <text>0</text>
      </expectedfraction>
      <gradedfraction>
        <text>0</text>
      </gradedfraction>
    </testquestionresponse>
    <testquestionresponse>
      <response>
        <text>one two three</text>
      </response>
      <expectedfraction>
        <text>1</text>
      </expectedfraction>
      <gradedfraction>
        <text>1</text>
      </gradedfraction>
    </testquestionresponse>
    <hint format="html">
      <text>Hint 1</text>
    </hint>
    <hint format="html">
      <text>Hint 2</text>
    </hint>
  </question>
';
        $xmldata = xmlize($xml);

        $importer = new qformat_xml();
        $q = $importer->try_importing_using_qtypes($xmldata['question'], null, null, 'pmatch');

        $expectedq = new stdClass();
        $expectedq->qtype = 'pmatch';
        $expectedq->name = 'test-0';
        if ($CFG->branch > 35) {
            // Question idnumber only available since Moodle 3.6.
            $expectedq->idnumber = '';
        }
        if ($CFG->branch > 34) {
            // Question tags only available since Moodle 3.5.
            $expectedq->tags = [];
        }
        $expectedq->questiontext = 'Listen, translate and write';
        $expectedq->questiontextformat = FORMAT_HTML;
        $expectedq->generalfeedback = '';
        $expectedq->generalfeedbackformat = FORMAT_HTML;
        $expectedq->applydictionarycheck = 'en_GB';

        $response = new \qtype_pmatch\testquestion_response();
        $response->response = 'one two';
        $response->expectedfraction = '0';
        $response->gradedfraction = '0';

        $expectedq->responsesdata[] = $response;

        $response = new \qtype_pmatch\testquestion_response();
        $response->response = 'one two three';
        $response->expectedfraction = '1';
        $response->gradedfraction = '1';

        $expectedq->responsesdata[] = $response;

        $this->assertEquals($expectedq->responsesdata, $q->responsesdata);
        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }
}

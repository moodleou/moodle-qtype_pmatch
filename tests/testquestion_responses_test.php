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
        for ($x = 0; $x < 10; $x ++) {
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
        $expectedcounts->correctlymarkedright = 0;
        $expectedcounts->correctlymarkedwrong = 0;
        $expectedcounts->humanmarkedright = 0;
        $expectedcounts->humanmarkedwrong = 0;
        $expectedcounts->ungraded = 18;
        $expectedcounts->graded = 0;
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
        $expectedcounts->correctlymarkedright = 4;
        $expectedcounts->correctlymarkedwrong = 4;
        $expectedcounts->humanmarkedright = 7;
        $expectedcounts->humanmarkedwrong = 6;
        $expectedcounts->ungraded = 5;
        $expectedcounts->graded = 13;
        $expectedcounts->accuracy = 62.0;

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
        $expectedresponses  = array();
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
        // '<div>Accuracy</div><div>Pos = 2 Neg = 1</div><div>Coverage</div><div><ul><li>' .
        // '<span>133000: Tom Dick or Harry</span></li><li>' .
        // '<span class="qtype_pmatch-selftest-missed-positive">133001: Tom</span></li><li>' .
        // '<span>133004: Tom was janes companion</span></li></ul></div>'.
        // So lets just look for some elements of text.
        $this->assertTrue(strpos($try, 'Pos = 2 Neg = 1') !== false);
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

        $compareaccuracy = array('positive' => 3, 'negative' => 1);

        // Test grading for a correct response.
        $accuracy = \qtype_pmatch\testquestion_responses::get_rule_accuracy_counts($responses, $rule->id, $matches);

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
        global $DB;
        $this->resetAfterTest();

        $responses = $this->load_default_responses();
        // Update computer marked grade from fixture and saved to DB.
        $this->update_response_grades_from_file($responses, 'fixtures/testresponsesgraded.csv');

        $rules = $this->currentquestion->get_answers();
        // Remove last rule.
        $ignorerule = array_pop($rules);
        // Update the question rules.
        $this->currentquestion->answers = $rules;

        $responseids = array_keys($responses);
        $comparerulematches = array (
            'responseidstoruleids' => array(
                    'Tom Dick or Harry' => array(0 => 'match_w(Tom|Harry)', 1 => 'match_w(Dick)'),
                    'Tom' => array(0 => 'match_w(Tom|Harry)'),
                    'Dick' => array(0 => 'match_w(Dick)'),
                    'Harry' => array(0 => 'match_w(Tom|Harry)'),
                    'Tom was janes companion' => array(0 => 'match_w(Tom|Harry)')
                ),
            'ruleidstoresponseids' => array(
                    'match_w(Tom|Harry)' => array(0 => 'Tom Dick or Harry', 1 => 'Tom', 2 => 'Harry',
                                    3 => 'Tom was janes companion'),
                    'match_w(Dick)' => array(0 => 'Tom Dick or Harry', 1 => 'Dick')
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
        $deletedrulecomparerulematches = array (
            'responseidstoruleids' => array(
                    'Tom Dick or Harry' => array(0 => 'match_w(Tom|Harry)'),
                    'Tom' => array(0 => 'match_w(Tom|Harry)'),
                    'Harry' => array(0 => 'match_w(Tom|Harry)'),
                    'Tom was janes companion' => array(0 => 'match_w(Tom|Harry)')
                ),
            'ruleidstoresponseids' => array(
                    'match_w(Tom|Harry)' => array(0 => 'Tom Dick or Harry', 1 => 'Tom', 2 => 'Harry',
                                    3 => 'Tom was janes companion')
                )
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
}

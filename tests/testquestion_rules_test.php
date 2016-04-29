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
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/question/type/pmatch/tests/testquestion_testcase.php');


/**
 * Test driver class that tests the pmatch library by loading examples from
 * text files in the examples folder.
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     qtype_pmatch
 */
class qtype_pmatch_testquestion_test_rules extends qtype_pmatch_testquestion_testcase {

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
        $compareresponses = \qtype_pmatch\test_responses::get_responses_by_ids($responseids);
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
        \qtype_pmatch\test_responses::grade_responses_by_rule($responses, $rule, $this->currentquestion);

        $this->assertEquals($compareresponses, $responses);
    }

    /**
     * Test individual grading rule accuracy.
     */
    public function test_individual_rule_grade_accuracy() {
        $this->resetAfterTest();
        $this->currentquestion = $this->create_default_question();
        $rules = $this->currentquestion->get_answers();
        $rule = $rules[array_keys($rules)[0]];
        $responses = $this->load_default_responses();

        $compareaccuracy = array('positive' => 3, 'negative' => 1);

        // Test grading for a correct response.
        \qtype_pmatch\test_responses::grade_responses_by_rule($responses, $rule, $this->currentquestion);
        $accuracy = \qtype_pmatch\test_responses::get_individual_grade_accuracy($responses, $rule->id);

        $this->assertEquals($compareaccuracy, $accuracy);
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
        $this->update_response_grades_from_file($responses);
        // Grade a response and save results to the qtype_pmatch_rule_matches table.
        \qtype_pmatch\test_responses::save_rule_matches($this->currentquestion);

        $responseids = array_keys($responses);
        $matches = \qtype_pmatch\test_responses::get_rule_matches_for_responses($responseids, $this->currentquestion->id);

        $compareaccuracy = array('positive' => 3, 'negative' => 1);

        // Test grading for a correct response.
        $accuracy = \qtype_pmatch\test_responses::get_rule_accuracy_counts($responses, $rule->id, $matches);

        $this->assertEquals($compareaccuracy, $accuracy);
    }

    /**
     * Test re-grading.
     */
    public function test_regrading() {
        global $DB;
        $this->resetAfterTest();
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
        $this->assertTrue(is_null($tomdickharry));
        $this->assertTrue(is_null($tomcat));
        // Update computer marked grade from fixture and saved to DB.
        $this->update_response_grades_from_file($responses);
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
        // Grade a response and save results to the qtype_pmatch_rule_matches table.
        \qtype_pmatch\test_responses::grade_responses_and_save_matches($this->currentquestion);
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
        // Update the question rules and check a re-grading.
        $rules = $this->currentquestion->get_answers();
        $rules[13]->answer = 'match_w(Tomcat)';
        $this->currentquestion->answers = $rules;
        \qtype_pmatch\test_responses::grade_responses_and_save_matches($this->currentquestion);
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
        $this->update_response_grades_from_file($responses);

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
        \qtype_pmatch\test_responses::save_rule_matches($this->currentquestion);

        // Determine which rules match which response using data from table qtype_pmatch_rule_matches.
        $rulematches = \qtype_pmatch\test_responses::get_rule_matches_for_responses($responseids,
                                                                            $this->currentquestion->id);

        // Translate the rule and response ids into responses and rules to test.
        $responseandrulematches = $this->get_rule_matches_as_responses_and_rules($rulematches,
                                                                            $rules, $responses);

        $this->assertEquals($comparerulematches, $responseandrulematches);

        // Delete a rule.
        // Delete existing rule matches for the question.
        \qtype_pmatch\test_responses::delete_rule_matches($this->currentquestion);

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
        \qtype_pmatch\test_responses::save_rule_matches($this->currentquestion);

        // Determine which rules match which response using data from table qtype_pmatch_rule_matches.
        $rulematches = \qtype_pmatch\test_responses::get_rule_matches_for_responses($responseids,
                                                                            $this->currentquestion->id);

        // Translate the rule and response ids into responses and rules to test.
        $responseandrulematches = $this->get_rule_matches_as_responses_and_rules($rulematches, $rules, $responses);

        $this->assertEquals($deletedrulecomparerulematches, $responseandrulematches);

        // Add the rule back
        // Delete existing rule matches for the question.
        \qtype_pmatch\test_responses::delete_rule_matches($this->currentquestion);

        // Restore the deleted rule.
        array_push($rules, $deletedrule);

        // Update the question rules.
        $this->currentquestion->answers = $rules;

        // Grade a response and save results to the qtype_pmatch_rule_matches table.
        \qtype_pmatch\test_responses::save_rule_matches($this->currentquestion);

        // Determine which rules match which response using data from table qtype_pmatch_rule_matches.
        $rulematches = \qtype_pmatch\test_responses::get_rule_matches_for_responses($responseids,
                                                                            $this->currentquestion->id);

        // Translate the rule and response ids into responses and rules to test.
        $responseandrulematches = $this->get_rule_matches_as_responses_and_rules($rulematches, $rules, $responses);

        $this->assertEquals($comparerulematches, $responseandrulematches);
    }

    /**
     * Convert the rule match look up table into the related responses and answers
     * so it can be tested.
     */
    protected function get_rule_matches_as_responses_and_rules($rulematches, $rules, $responses) {
        global $DB;

        $responseids = array();
        $ruleids = array();
        $matchedresponsesandrules = array();
        $matchedrulesandresponses = array();
        foreach ($rulematches['responseidstoruleids'] as $responseid => $responseruleids) {
            array_push($responseids, $responseid);
            $response = $responses[$responseid];
            if (!array_key_exists($response->response, $matchedresponsesandrules)) {
                $matchedresponsesandrules[$response->response] = array();
            }
            $matchedresponse = $matchedresponsesandrules[$response->response];
            foreach ($responseruleids as $ruleid) {
                $rule = $rules[$ruleid];
                if (in_array($rule->answer, $matchedresponse)) {
                    continue;
                }
                $matchedresponse[] = $rule->answer;
            }
            $matchedresponsesandrules[$response->response] = $matchedresponse;
        }

        foreach ($rulematches['ruleidstoresponseids'] as $ruleid => $ruleresponseids) {
            array_push($ruleids, $ruleid);
            $rule = $rules[$ruleid];
            if (!array_key_exists($rule->answer, $matchedrulesandresponses)) {
                $matchedrulesandresponses[$rule->answer] = array();
            }
            $matchedrule = $matchedrulesandresponses[$rule->answer];
            foreach ($ruleresponseids as $responseid) {
                $response = $responses[$responseid];
                if (in_array($response->response, $matchedrule)) {
                    continue;
                }
                $matchedrule[] = $response->response;
            }
            $matchedrulesandresponses[$rule->answer] = $matchedrule;
        }

        $responseandrulematches = array('responseidstoruleids' => $matchedresponsesandrules,
                                    'ruleidstoresponseids' => $matchedrulesandresponses);
        return $responseandrulematches;
    }

}

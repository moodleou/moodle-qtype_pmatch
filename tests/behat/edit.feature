@ou @ou_vle @qtype @qtype_pmatch @_switch_window
Feature: Test editing a pattern match question
  As a teacher
  In order to be able to update my Matching question
  I need to edit them

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teacher   |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype  | name                            | template |
      | Test questions   | pmatch | My first pattern match question | listen   |
      | Test questions   | pmatch | Frog but not toad               | frogtoad |
      | Test questions   | pmatch | Spanish question                | spanish  |
    And the default question test responses exist for question "My first pattern match question"

  @javascript
  Scenario: Edit the copy and verify the form field contents of pattern match question.
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    When I add a "Pattern match" question filling the form with:
      | Question name              | My edit match question                                          |
      | Question text              | Listen, translate and write                                     |
      | id_usecase                 | Yes, case must match                                            |
      | id_allowsubscript          | Yes                                                             |
      | id_allowsuperscript        | Yes                                                             |
      | id_forcelength             | warn that answer is too long and invite respondee to shorten it |
      | id_applydictionarycheck    | Do not check spelling of student                                |
      | id_sentencedividers        | ?!                                                              |
      | id_converttospace          | ;:                                                              |
      | id_synonymsdata_0_word     | any                                                             |
      | id_synonymsdata_0_synonyms | "testing\|one\|two\|three\|four"                                |
      | Answer 1 must match        | match (testing one two three four)                              |
      | Model answer               | testing one two three four                                      |
      | id_fraction_0              | 100%                                                            |
      | id_feedback_0              | Well done!                                                      |
      | id_otherfeedback           | Sorry, no.                                                      |
      | Hint 1                     | Please try again.                                               |
      | Hint 2                     | Use a calculator if necessary.                                  |
    And I choose "Edit question" action for "My edit match question" in the question bank
    And "Help with Answer matching" "icon" should exist
    And I click on "Help with Answer matching" "icon"
    And I should see "If you have a short phrase you want to match, you should enclose it in square brackets ([...])."
    And "More help" "link" should exist
    Then the following fields match these values:
      | Question name              | My edit match question             |
      | Question text              | Listen, translate and write        |
      | id_synonymsdata_0_word     | any                                |
      | id_synonymsdata_0_synonyms | "testing\|one\|two\|three\|four"   |
      | Answer 1 must match        | match (testing one two three four) |
      | Model answer               | testing one two three four         |
      | id_fraction_0              | 100%                               |
      | id_feedback_0              | Well done!                         |
      | id_otherfeedback           | Sorry, no.                         |
      | Hint 1                     | Please try again.                  |
      | Hint 2                     | Use a calculator if necessary.     |
    And I set the following fields to these values:
      | Question name | Edited question name |
      | Model answer  |                      |
    And I should see "You must provide a possible response to this question, which would be graded 100% correct."
    And the following fields match these values:
      | possibleanswerplaceholder-0 | ______  |
      | possibleanswerplaceholder-1 | __6__   |
      | possibleanswerplaceholder-2 | __6x2__ |
    And I set the following fields to these values:
      | Model answer  | testing one two three four |
    And the following fields match these values:
      | possibleanswerplaceholder-0 | ____________________________ |
      | possibleanswerplaceholder-1 | __28__                       |
      | possibleanswerplaceholder-2 | __28x2__                     |
    And I should not see "Overall grading accuracy"
    And I press "id_submitbutton"
    And I should see "Edited question name"

  @javascript
  Scenario: Validation of the model answer
    When I am on the "Frog but not toad" "core_question > edit" page logged in as teacher
    And I should see "Editing a Pattern match question"
    And I click on "Expand all" "link" in the "region-main" "region"
    And I set the following fields to these values:
      | id_modelanswer | I saw a toad which was bigger than a frog |
    And I press "id_submitbutton"
    Then I should see "'I saw a toad which was bigger than a frog' is not a correct answer to this question."
    And I set the following fields to these values:
      | id_modelanswer | frog |
    # Should save with no validation error.
    And I press "id_submitbutton"
    And "Frog but not toad" "table_row" in the "categoryquestions" "table" should be visible
    And I choose "Edit question" action for "Frog but not toad" in the question bank
    And I set the following fields to these values:
      | id_modelanswer | frog |
    And I press "id_submitbutton"
    And "Frog but not toad" "table_row" in the "categoryquestions" "table" should be visible

  @javascript
  Scenario: Validation of the model answer with non-standard options
    Given I am on the "Spanish question" "core_question > edit" page logged in as teacher
    # Should save with no validation error.
    When I press "id_submitbutton"
    Then "Spanish question" "table_row" in the "categoryquestions" "table" should be visible

  @javascript
  Scenario: Test the pmatch try rule feature
    Given I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    When I set the field "tqheadercheckbox" to "1"
    And I press "Test selected responses"
    And I press "Continue"
    # Confirm list responses is correct.
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Sample responses: 13 "
    And I should see "Marked correctly: 7 (54%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 5 (missed negative)"

    # The page needs refreshing now.
    And I am on the "My first pattern match question" "core_question > edit" page
    # The waiting is required probably because the editor takes a long time to load.
    # Without waiting I get an unexpected 'alert open' exception on my PC.
    # The try rule button click is an ajax call, and is wrapped in js_pending js_complete,
    # but a wait for page ready seems to still be required.
    And I wait until the page is ready
    And I should see "Responses not matched above: 12" in the "#fitem_accuracy_0" "css_element"
    And I should see "Correctly matched by this rule: 1" in the "#fitem_accuracy_0" "css_element"
    And I should see "Incorrectly matched: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Incorrectly matched: 0" in the "span.qtype_pmatch-selftest" "css_element"
    And I should see "Responses still to be processed below: 11" in the "#fitem_accuracy_0" "css_element"
    And I set the field "Answer 1 must match" to "match_w(test)"
    And I take focus off "Answer 1 must match" "field"
    And I press "Try rule"
    And I should see "Responses not matched above: 12" in the "div.try-rule-result" "css_element"
    And I should see "Correctly matched by this rule: 0" in the "div.try-rule-result" "css_element"
    And I should see "Incorrectly matched: 1" in the "div.try-rule-result" "css_element"
    And I should see "Responses still to be processed below: 11" in the "div.try-rule-result" "css_element"
    And I set the field "Answer 1 must match" to "match_w(test*)"
    And I press "Try rule"
    And I should see "Responses not matched above: 12" in the "div.try-rule-result" "css_element"
    And I should see "Correctly matched by this rule: 2" in the "div.try-rule-result" "css_element"
    And I should see "Incorrectly matched: 1" in the "div.try-rule-result" "css_element"
    And I should see "Responses still to be processed below: 9" in the "div.try-rule-result" "css_element"
    And I set the field "Answer 1 must match" to "match_w(testing)"
    And I press "Try rule"
    And I should see "Responses not matched above: 12" in the "div.try-rule-result" "css_element"
    And I should see "Correctly matched by this rule: 2" in the "div.try-rule-result" "css_element"
    And I should see "Incorrectly matched: 0" in the "span.qtype_pmatch-selftest" "css_element"
    And I should see "Responses still to be processed below: 10" in the "div.try-rule-result" "css_element"

  @javascript
  Scenario: Test the pmatch rules response feature
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    When I add a "Pattern match" question filling the form with:
      | Question name       | My first pattern match editor question |
      | Question text       | Draw ethanol                           |
      | Answer 1 must match | match (CCOO)                           |
      | id_fraction_0       | 100%                                   |
      | id_feedback_0       | Well done!                             |
      | Answer 2 must match | match (CCO)                            |
      | id_fraction_1       | 60%                                    |
      | Answer 3 must match | match (CO)                             |
      | id_fraction_2       | None                                   |
      | id_otherfeedback    | Sorry, no.                             |
      | Model answer        | CCOO                                   |
    Then I should see "My first pattern match editor question"
    And I am on the "My first pattern match editor question" "core_question > preview" page
    And I click on "Test this question" "link"
    And I click on "Add new response" "button"
    And I set the field "new-response" to "CCOO"
    And I click on "Save" "button"
    And I should see "1" in the "#qtype-pmatch-testquestion_r50_c2" "css_element"
    And I click on "Add new response" "button"
    And I set the field "new-response" to "CCO"
    And I click on "Save" "button"
    And I should see "2" in the "#qtype-pmatch-testquestion_r51_c2" "css_element"
    And I click on "Add new response" "button"
    And I set the field "new-response" to "CO"
    And I click on "Save" "button"
    And I should see "3" in the "#qtype-pmatch-testquestion_r52_c2" "css_element"
    And I click on "Add new response" "button"
    And I set the field "new-response" to "C"
    And I click on "Save" "button"
    And I should see "" in the "#qtype-pmatch-testquestion_r53_c2" "css_element"

  @javascript
  Scenario: Check validation of answer field real time.
    Given I am on the "My first pattern match question" "core_question > edit" page logged in as teacher
    When I set the field "Answer 1 must match" to "match()"
    And I take focus off "Answer 1 must match" "field"
    Then I should see "Unrecognised sub-content in code fragment \"match()\"."
    And I set the field "Answer 1 must match" to "test"
    And I take focus off "Answer 1 must match" "field"
    And I should see "Unrecognised expression."
    And I set the field "Answer 1 must match" to "match(test"
    And I take focus off "Answer 1 must match" "field"
    And I should see "Missing closing bracket in code fragment \"match(test\"."
    And I set the field "Answer 1 must match" to "matchtest("
    And I take focus off "Answer 1 must match" "field"
    And I should see "Illegal options in expression \"matchtest()\"."
    And I set the field "Answer 1 must match" to "match (3<sup>7</sup>)"
    And I take focus off "Answer 1 must match" "field"
    And I should not see "expression" in the "fitem_id_answer_0" "region"

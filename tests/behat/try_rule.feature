@ou @ou_vle @qtype @qtype_pmatch
Feature: Test the try rule feature
  In order to evaluate whether a rule should be saved
  As an teacher
  I need to test it first using the try rule button.

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
      | questioncategory | qtype    | name         | template |
      | Test questions   | pmatch   | My first pattern match question | listen    |
    And the default question test responses exist for question "My first pattern match question"
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I choose "Preview" action for "My first pattern match question" in the question bank
    And I switch to "questionpreview" window
    And I click on "Test this question" "link"
    And I set the field "tqheadercheckbox" to "1"
    And I press "Test selected responses"
    And I press "Continue"

  @javascript
  Scenario: Test the pmatch try rule feature
    # Confirm list responses is correct.
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Sample responses: 13 "
    And I should see "Marked correctly: 7 (54%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 5 (missed negative)"
    And I switch to the main window
    # The page needs refreshing now.
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I choose "Edit question" action for "My first pattern match question" in the question bank
    # The waiting is required probably because the editor takes a long time to load.
    # Without waiting I get an unexpected 'alert open' exception on my PC.
    # The try rule button click is an ajax call, and is wrapped in js_pending js_complete,
    # but a wait for page ready seems to still be required.
    And I wait until the page is ready
    Then I should see "Responses not matched above: 12" in the "#fitem_accuracy_0" "css_element"
    And I should see "Correctly matched by this rule: 1" in the "#fitem_accuracy_0" "css_element"
    And I should see "Incorrectly matched: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Incorrectly matched: 0" in the "span.qtype_pmatch-selftest" "css_element"
    And I should see "Responses still to be processed below: 11" in the "#fitem_accuracy_0" "css_element"
    When I set the field "Answer 1" to "match_w(test)"
    And I take focus off "Answer 1" "field"
    And I press "Try rule"
    Then I should see "Responses not matched above: 12" in the "div.try-rule-result" "css_element"
    And I should see "Correctly matched by this rule: 0" in the "div.try-rule-result" "css_element"
    And I should see "Incorrectly matched: 1" in the "div.try-rule-result" "css_element"
    And I should see "Responses still to be processed below: 11" in the "div.try-rule-result" "css_element"
    When I set the field "Answer 1" to "match_w(test*)"
    And I press "Try rule"
    Then I should see "Responses not matched above: 12" in the "div.try-rule-result" "css_element"
    And I should see "Correctly matched by this rule: 2" in the "div.try-rule-result" "css_element"
    And I should see "Incorrectly matched: 1" in the "div.try-rule-result" "css_element"
    And I should see "Responses still to be processed below: 9" in the "div.try-rule-result" "css_element"
    When I set the field "Answer 1" to "match_w(testing)"
    And I press "Try rule"
    Then I should see "Responses not matched above: 12" in the "div.try-rule-result" "css_element"
    And I should see "Correctly matched by this rule: 2" in the "div.try-rule-result" "css_element"
    And I should see "Incorrectly matched: 0" in the "span.qtype_pmatch-selftest" "css_element"
    And I should see "Responses still to be processed below: 10" in the "div.try-rule-result" "css_element"

  @javascript
  Scenario: Test the pmatch rules response feature
    Given I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    When I add a "Pattern match" question filling the form with:
      | Question name      | My first pattern match editor question |
      | Question text      | Draw ethanol                           |
      | Answer 1           | match (CCOO)                           |
      | id_fraction_0      | 100%                                   |
      | id_feedback_0      | Well done!                             |
      | Answer 2           | match (CCO)                            |
      | id_fraction_1      | 60%                                    |
      | Answer 3           | match (CO)                             |
      | id_fraction_2      | None                                   |
      | id_otherfeedback   | Sorry, no.                             |
    Then I should see "My first pattern match editor question"
    When I choose "Preview" action for "My first pattern match editor question" in the question bank
    And I switch to "questionpreview" window
    And I click on "Test this question" "link"
    And I click on "Add new response" "button"
    And I set the field "new-response" to "CCOO"
    When I click on "Save" "button"
    And I should see "1" in the "#qtype-pmatch-testquestion_r50_c2" "css_element"
    And I click on "Add new response" "button"
    And I set the field "new-response" to "CCO"
    When I click on "Save" "button"
    And I should see "2" in the "#qtype-pmatch-testquestion_r51_c2" "css_element"
    And I click on "Add new response" "button"
    And I set the field "new-response" to "CO"
    When I click on "Save" "button"
    And I should see "3" in the "#qtype-pmatch-testquestion_r52_c2" "css_element"
    And I click on "Add new response" "button"
    And I set the field "new-response" to "C"
    When I click on "Save" "button"
    And I should see "" in the "#qtype-pmatch-testquestion_r53_c2" "css_element"

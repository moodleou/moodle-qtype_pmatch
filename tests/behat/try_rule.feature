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
    And I click on "Preview" "link" in the "My first pattern match question" "table_row"
    And I switch to "questionpreview" window
    And I click on "Test this question" "link"
    And I set the field "tqheadercheckbox" to "1"
    And I press "Test selected responses"
    And I press "Continue"

  @javascript
  Scenario: Test the pmatch try rule feature
    # Confirm list responses is correct.
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "(Pos=1/6 Neg=6/6 Unm=1 Acc=58%)"
    And I switch to the main window
    # The page needs refreshing now.
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I click on "Edit" "link" in the "My first pattern match question" "table_row"
    # The waiting is required probably because the editor takes a long time to load.
    # Without waiting I get an unexpected 'alert open' exception on my PC.
    # The try rule button click is an ajax call, and is wrapped in js_pending js_complete,
    # but a wait for page ready seems to still be required.
    And I wait until the page is ready
    Then I should see "Pos = 1 Neg = 0" in the "#fitem_accuracy_0" "css_element"
    When I set the field "Answer 1" to "match_w(test)"
    And I take focus off "Answer 1" "field"
    And I press "Try rule"
    Then I should see "Pos = 1 Neg = 0" in the "div.try-rule-result" "css_element"
    When I set the field "Answer 1" to "match_w(test*)"
    And I press "Try rule"
    Then I should see "Pos = 2 Neg = 1" in the "div.try-rule-result" "css_element"

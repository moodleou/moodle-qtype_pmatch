@ou @ou_vle @qtype @qtype_pmatch
Feature: Test no test responses existing for this question
  In order to manage test responses in the test this question feature
  As a teacher
  I need to know when no test responses exist for pattern match questions.

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
    And I log in as "teacher"

  @javascript
  Scenario: Confirm the display when no test responses exist for a pattern match question.
    # Confirm list responses is correct.
    Given I am on the pattern match test responses page for question "My first pattern match question"
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "What to include in the report"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Sample responses: 0 "
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    And I should not see "Nothing to display"
    And I wait until the page is ready
    And "Add new response" "button" should be visible
    And I should see "Rules" in the "responses" "table"
    And the "#tqheadercheckbox" "css_element" should be disabled
    And the "Test selected responses" "button" should be disabled
    And the "Delete" "button" should be disabled
    And I click on "Add new response" "button"
    And I set the field "new-response" to "New test response"
    When I click on "Cancel" "button" in the ".generaltable" "css_element"
    And the "Test selected responses" "button" should be disabled
    And the "Delete" "button" should be disabled
    And I should not see "Cancel" in the ".generaltable" "css_element"
    And the "Add new response" "button" should be enabled
    And I click on "Add new response" "button"
    And I set the field "new-response" to "New test response"
    When I click on "Save" "button"
    Then I should see "Sample responses: 1"
    And I should see "Marked correctly: 1 (100%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    When I click on "#tqheadercheckbox" "css_element"
    And I press "Delete"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "The responses were successfully deleted."
    And I press "Continue"
    And the "Test selected responses" "button" should be disabled
    And the "Delete" "button" should be disabled
    And the "#tqheadercheckbox" "css_element" should be disabled

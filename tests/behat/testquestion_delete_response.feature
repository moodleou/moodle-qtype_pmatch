@ou @ou_vle @qtype @qtype_pmatch
Feature: Delete a test response for a pattern match question
  In order manage existing test responses
  As a teacher
  I need to delete test responses for pattern match questions.

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

  @javascript
  Scenario: Delete an existing test response for a pattern match question.
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    And I click on "Select response" "checkbox" in the "testing one two three four" "table_row"
    And I press "Delete"
    And  I click on "Yes" "button" in the "Confirmation" "dialogue"

    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "The responses were successfully deleted."
    And I press "Continue"
    And I should see "Sample responses: 12"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    And I should see "0" in the "#qtype-pmatch-testquestion_r0_c4" "css_element"
    And I should see "testing" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"

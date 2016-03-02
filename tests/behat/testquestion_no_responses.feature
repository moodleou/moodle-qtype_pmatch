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
    And I should see "Pos=0/0 Neg=0/0 Unm=0 Acc=0%"
    And I should see "Nothing to display"

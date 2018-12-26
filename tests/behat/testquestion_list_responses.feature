@ou @ou_vle @qtype @qtype_pmatch
Feature: List test responses for a pattern match question
  In order to manage test responses in the test this question feature
  As a teacher
  I need the list of test responses to be displayed correctly for pattern match questions.

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

  @javascript
  Scenario: List the test responses for a pattern match question.
    # Confirm list responses is correct.
    Given I am on the pattern match test responses page for question "My first pattern match question"
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "What to include in the report"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    # Confirm the human mark
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c4" "css_element"
    # Confirm the response
    Then I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"

  @javascript
  Scenario: Able to download the test responses for a pattern match question.
    Given I am on the pattern match test responses page for question "My first pattern match question"
    Then I should see "Download table data as"
    And the "Download table data as" select box should contain "Comma separated values (.csv)"
    And the "Download table data as" select box should contain "Microsoft Excel (.xlsx)"
    And "Download" "button" should exist

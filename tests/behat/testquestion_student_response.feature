@ou @ou_vle @qtype @qtype_pmatch
Feature: Confirm a student cannot access the test this question feature
  In order evaluate students understanding
  As an student
  I should not have access to test responses for pattern match questions.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | student  | Student   |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | student | C1     | student |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype    | name         | template |
      | Test questions   | pmatch   | My first pattern match question | listen    |
    And the default question test responses exist for question "My first pattern match question"
    And I log in as "student"

  @javascript
  Scenario: Confirm a student cannot access the test this question feature.
    Given I am on the pattern match test responses page for question "My first pattern match question"
    Then I should see "Sorry, but you do not currently have permissions to do that (view)"
    And I should not see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should not see "Showing the responses for the selected question: My first pattern match question"

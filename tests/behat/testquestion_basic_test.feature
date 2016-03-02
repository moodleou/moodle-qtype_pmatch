@ou @ou_vle @qtype @qtype_pmatch
Feature: Test all the basic functionality of this question type
  In order evaluate students understanding
  As an teacher
  I need to create and preview pattern match questions.

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
    And I follow "Course 1"
    And I navigate to "Question bank" node in "Course administration"
    When I click on "Preview" "link" in the "My first pattern match question" "table_row"
    And I switch to "questionpreview" window
    And I click on "Test this question" "link"

  @javascript
  Scenario: Create, edit then preview a pattern match question.
    # Confirm list responses is correct.
    And I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "What to include in the report"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Pos=0/0 Neg=0/0 Unm=13 Acc=0%"

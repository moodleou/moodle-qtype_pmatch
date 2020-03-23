@ou @ou_vle @qtype @qtype_pmatch
Feature: Test all the basic functionality of testquestion question type
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
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    When I choose "Preview" action for "My first pattern match question" in the question bank
    And I switch to "questionpreview" window
    And I click on "Test this question" "link"

  @javascript
  Scenario: Test basic functionality of testquestion
    # Confirm list responses is correct.
    And I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "What to include in the report"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"

  @javascript
  Scenario: Test edit response.
    # Confirm can edit inplace the response.
    Given I am on the pattern match test responses page for question "My first pattern match question"
    Then I should see "testing one two three four"
    When I follow "Edit response"
    Then I should see "Escape to cancel, Enter when finished"
    When I set the field "Edit response" to ""
    And I press key "13" in the field "Edit response"
    Then I should see "The response cannot be blank"
    And I click on "OK" "button" in the "Error" "dialogue"
    When I follow "Edit response"
    And I set the field "Edit response" to "testing"
    And I press key "13" in the field "Edit response"
    Then I should see "Duplicate responses are not allowed"
    And I click on "OK" "button" in the "Error" "dialogue"
    When I follow "Edit response"
    And I set the field "Edit response" to "New improved response"
    And I press key "13" in the field "Edit response"
    Then I should not see "testing one two three four"
    And I should see "New improved response"
    And I reload the page
    And I should not see "testing one two three four"
    And I should see "New improved response"

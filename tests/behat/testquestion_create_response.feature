@ou @ou_vle @qtype @qtype_pmatch
Feature: Create new a response for a pattern match question

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
  Scenario: Create an existing test response for a pattern match question.
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    # Check responses are listed correctly
    And I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"
    When I press "Add new response"
    Then the "Save" "button" should be disabled
    And the "Add new response" "button" should be disabled
    And the "Delete" "button" should be disabled
    And the "Test selected responses" "button" should be disabled
    When I click on "Correct" "checkbox"
    And I set the field "new-response" to "New test response"
    And I press "Save"
    And I should see "Sample responses: 14"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 1 (missed negative)"
    And "New test response" "table_row" should be visible
    # Check duplicate response.
    When I press "Add new response"
    And I set the field "new-response" to "New test response"
    Then I should see "Duplicate responses are not allowed"
    And the "Save" "button" should be disabled
    When I press "Cancel"
    Then I should not see "Save"
    And the "Add new response" "button" should be enabled
    # Check key press enter and esc.
    When I press "Add new response"
    And I press the escape key
    Then I should not see "Save"
    When I click on "Add new response" "button"
    And I press the enter key
    And I set the field "new-response" to "New test response 1"
    And I press the enter key
    And "New test response 1" row "Human mark" column of "responses" table should contain "1"
    And the "Delete" "button" should be disabled
    And the "Test selected responses" "button" should be disabled

  @javascript
  Scenario: Create an test involving superscript
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    When I press "Add new response"
    And I set the field "new-response" to "5<sup>-4</sup>"
    And I press "Save"
    And I should see "Sample responses: 14"
    And I should see "Marked correctly: 1 (7%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    And I should see "5<sup>-4</sup>" in the "#qtype-pmatch-testquestion_r50_c5" "css_element"

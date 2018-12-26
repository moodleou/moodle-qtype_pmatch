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
    Given I log in as "teacher"
    And I am on the pattern match test responses page for question "My first pattern match question"
    # Check responses are listed correctly
    And I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"
    When I click on "Add new response" "button"
    Then the "Save" "button" should be disabled
    And the "Add new response" "button" should be disabled
    And the "Delete" "button" should be disabled
    And the "Test selected responses" "button" should be disabled
    When I click on "Correct" "checkbox"
    And I set the field "new-response" to "New test response"
    And I click on "Save" "button"
    And I should see "Sample responses: 14"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 1 (missed negative)"
    And I should see "New test response" in the "#qtype-pmatch-testquestion_r50_c5" "css_element"
    # Check duplicate response.
    When I click on "Add new response" "button"
    And I set the field "new-response" to "New test response"
    Then I should see "Duplicate responses are not allowed"
    And the "Save" "button" should be disabled
    When I click on "Cancel" "button"
    Then I should not see "Save"
    And the "Add new response" "button" should be enabled
    # Check key press enter and esc.
    When I click on "Add new response" "button"
    And I press key "27" in the field "Correct"
    Then I should not see "Save"
    When I click on "Add new response" "button"
    And I press key "13" in the field "Correct"
    And I set the field "new-response" to "New test response 1"
    And I press key "13" in the field "new-response"
    Then I should see "New test response 1" in the "#qtype-pmatch-testquestion_r51_c5" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r51_c4" "css_element"

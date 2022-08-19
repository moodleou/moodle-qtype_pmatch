@ou @ou_vle @qtype @qtype_pmatch
Feature: Grade a test response for a pattern match question
  In order evaluate the accuracy of a question
  As a teacher
  I need to grade existing responses for pattern match questions.

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
  Scenario: Grade an existing test response for pattern match question.
    # Check responses are listed correctly
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    And I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"
    And I click on "Select response" "checkbox" in the "testing one two three four" "table_row"
    And I press "Test selected responses"
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Processing response 1 of 1: testing one two three four."

    When I press "Continue"
    Then I should see "Sample responses: 13"
    And I should see "Marked correctly: 1 (8%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    # Confirm the computer mark
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c3" "css_element"
    # Confirm the response
    And I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"

    # Now test changing the human mark
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c4" "css_element"
    When I click on "a.updater-ef" "css_element" in the "testing one two three four" "table_row"
    Then I should see "0" in the "#qtype-pmatch-testquestion_r0_c4" "css_element"

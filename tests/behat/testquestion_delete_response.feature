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
    And I log in as "teacher"

  @javascript
  Scenario: Delete an existing test response for a pattern match question.
    Given I am on the pattern match test responses page for question "My first pattern match question"
    # Check responses are listed correctly
    Given I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"
    When I set the field with xpath "//form[@id='attemptsform']//table[@id='responses']//td[@id='qtype-pmatch-testquestion_r0_c0']//input" to "1"
    And I press "Delete"
    #The step When I click on "Yes" "button" confirming the dialogue doesn't find a dialogue so we use the following step instead.
    When  I click on "//div[contains(@class, 'moodle-dialogue-confirm')]//div[contains(@class, 'confirmation-dialogue')]//div[contains(@class, 'confirmation-buttons')]//input[contains(@value, 'Yes')]" "xpath_element"
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "The responses were successfully deleted."

    # Confirm the response has been deleted
    When I press "Continue"
    Then I should see "Pos=0/0 Neg=0/0 Unm=12 Acc=0%"
    # Confirm the computer mark
    And I should see "0" in the "#qtype-pmatch-testquestion_r0_c4" "css_element"
    # Confirm the response
    And I should see "testing" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"

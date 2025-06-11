@ou @ou_vle @qtype @qtype_pmatch
Feature: Test responses existing for pattern match question
  In order to manage test responses in the test this question feature
  As a teacher
  I need to be able to test them

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teacher   |
      | student  | Student   |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |

    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype  | name                            | template |
      | Test questions   | pmatch | My first pattern match question | listen   |
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
    # Confirm the display of updated grade summary when human mark is changed.
    And I should see "Computed mark greater than human mark: 1 (missed positive)"

  @javascript
  Scenario: List the test responses for a pattern match question.
    # Confirm list responses is correct.
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Show responses that are"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    # Confirm the human mark
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c4" "css_element"
    # Confirm the response
    And I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"
    # Confirm display of null human mark
    And I should see "-" in the "testing anything." "table_row"
    And I click on "-" "link" in the "testing anything." "table_row"
    And I should see "1" in the "testing anything." "table_row"

  @javascript
  Scenario: Able to download the test responses for a pattern match question.
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    Then I should see "Download table data as"
    And the "Download table data as" select box should contain "Comma separated values (.csv)"
    And the "Download table data as" select box should contain "Microsoft Excel (.xlsx)"
    And "Download" "button" should exist

  @javascript
  Scenario: Confirm a student cannot access the test this question feature.
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as student
    Then I should not see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should not see "Showing the responses for the selected question: My first pattern match question"

  @javascript
  Scenario: Get standard responses graded against current answer rules.
    Given I am on the "My first pattern match question" "core_question > edit" page logged in as teacher
    # First check of question editing page.
    When I should see "Editing a Pattern match question"
    And I should see "Responses not matched above: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Correctly matched by this rule: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Incorrectly matched: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Responses still to be processed below: 0" in the "#fitem_accuracy_0" "css_element"
    # Now start the process of grading the responses.
    And I click on "Preview" "link"
    And I switch to "questionpreview" window
    And I click on "Test this question" "link"
    # Confirm the responses have no computed marks yet.
    Then I should see "Sample responses: 13"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    # Now grade the responses (add computer marks).
    And I set the field "tqheadercheckbox" to "1"
    And I press "Test selected responses"
    And I should see "Processing response 13 of 13: ."
    And I press "Continue"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 7 (54%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 5 (missed negative)"
    # Now check edit question page for updated accuracy and coverage.
    And I switch to the main window
    And I am on the "My first pattern match question" "core_question > edit" page
    And I should see "Editing a Pattern match question"
    And I should see "Responses not matched above: 12" in the "#fitem_accuracy_0" "css_element"
    And I should see "Correctly matched by this rule: 1" in the "#fitem_accuracy_0" "css_element"
    And I should see "Incorrectly matched: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Responses still to be processed below: 11" in the "#fitem_accuracy_0" "css_element"
    And I click on "Show coverage" "link"
    And I should see "testing one two three four" in the "//div[@id='matchedresponses_0_inner']" "xpath_element"

@ou @ou_vle @qtype @qtype_pmatch
Feature: Basic test of the question testing tool
  In order have confidence in my pattern-match questions
  As an teacher
  I need to be able to test them

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
    And the following config values are set as admin:
      | enableasyncbackup | 0 |

  @javascript
  Scenario: Navigate to the Test this question page from preview
    When I am on the "My first pattern match question" "core_question > preview" page logged in as teacher
    And I click on "Test this question" "link"
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"

  @javascript
  Scenario: Navigate to the Test this question page from the question bank
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I choose "Pattern-match testing tool" action for "My first pattern match question" in the question bank
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"

  @javascript
  Scenario: Test basic functionality of testquestion
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Show responses that are"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"

  @javascript
  Scenario: Get standard responses graded against current answer rules.
    Given I am on the "My first pattern match question" "core_question > edit" page logged in as teacher
    # First check of question editing page.
    When I should see "Editing a Pattern match question"
    Then I should see "Responses not matched above: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Correctly matched by this rule: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Incorrectly matched: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Responses still to be processed below: 0" in the "#fitem_accuracy_0" "css_element"
    # Now start the process of grading the responses.
    And I click on "Preview" "link"
    And I switch to "questionpreview" window
    And I click on "Test this question" "link"
    # Confirm the responses have no computed marks yet.
    And I should see "Sample responses: 13"
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

  Scenario: Edit a pattern match question and verify that the test responses are still present
    # Edit the question.
    Given I am on the "My first pattern match question" "core_question > edit" page logged in as teacher
    And I should see "Overall grading accuracy"
    And I should see "Sample responses: 13"
    When I set the following fields to these values:
      | Question name | Improved pattern match question                        |
      | Question text | What were the names of the tunnels in the Great Escape |
    And I press "id_submitbutton"

    # Check the sample responses are still present in the new version.
    And I am on the "Improved pattern match question" "qtype_pmatch > test responses" page
    Then I should see "Pattern-match question testing tool: Testing question: Improved pattern match question"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 7 (54%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 5 (missed negative)"
    And "testing one two three four" "table_row" should exist
    And "testing one two three four" row "Rules" column of "responses" table should contain "1"
    And "testing one two three four" row "Computed mark" column of "responses" table should contain "1"
    And "testing one two three four" row "Human mark" column of "responses" table should contain "1"

  @javascript
  Scenario: Test this question paging
    # Confirm list responses pagin options is correctly displayed
    Given I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    And I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Show responses that are"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    When the field "id_pagesize" matches value "50"

    # No paging should exist yet
    Then ".pagination" "css_element" should not exist

    # Set paging to 10 and check results
    And I set the field "id_pagesize" to "10"
    And I press "id_submitbutton"
    And the field "id_pagesize" matches value "10"
    And I should see "1" in the ".pagination .page-item.active" "css_element"
    And I should see "Next" in the ".pagination" "css_element"

  @javascript @_switch_window
  Scenario: Test backup and restore with testquestion data.
    Given I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as admin
    # Check course C1 version of uploaded responses.
    When I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    And I should see "1" in the "testing one two three four" "table_row"
    # Mark responses in order to test their backup.
    And I set the field "tqheadercheckbox" to "1"
    And I press "Test selected responses"
    And I press "Continue"

    # Make a backup and restore to new course.
    And I am on homepage
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    And I should see "Course 2"

    # Check the new course's testquestion data.
    And I navigate to "Question bank" in current page administration
    And I choose "Pattern-match testing tool" action for "My first pattern match question" in the question bank
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c4" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c3" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c2" "css_element"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 7 (54%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 5 (missed negative)"
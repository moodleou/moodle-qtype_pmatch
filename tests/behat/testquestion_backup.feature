@ou @ou_vle @qtype @qtype_pmatch @_switch_window @javascript
Feature: Test backup and restore of a pmatch question with responses and matches
  In order to manage pmatch questions
  As an admin
  I need to be able to backup and restore with all testquestion data.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype    | name         | template |
      | Test questions   | pmatch   | My first pattern match question | listen    |
    And the default question test responses exist for question "My first pattern match question"

  Scenario: Test backup and restore with testquestion data.
    Given I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as admin
    # Check course C1 version of uploaded responses.
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    And I should see "1" in the "testing one two three four" "table_row"
    # Mark responses in order to test their backup.
    When I set the field "tqheadercheckbox" to "1"
    And I press "Test selected responses"
    And I press "Continue"

    # Make a backup and restore to new course.
    Given I am on homepage
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    Then I should see "Course 2"

    # Check the new course's testquestion data.
    When I navigate to "Question bank" in current page administration
    When I choose "Pattern-match testing tool" action for "My first pattern match question" in the question bank
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c4" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c3" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c2" "css_element"
    And I should see "Sample responses: 13"
    And I should see "Marked correctly: 7 (54%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 5 (missed negative)"

@ou @ou_vle @qtype @qtype_pmatch @_switch_window @javascript
Feature: Test all the basic functionality of pmatch question type
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
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  Scenario: Create, edit then preview a pattern match question.
    # Create a new question.
    And I add a "Pattern match" question filling the form with:
      | Question name                 | My first pattern match question    |
      | Question text                 | Listen, translate and write        |
      | Spell checking                | Do not check spelling of student   |
      | id_synonymsdata_0_word        | any                                |
      | id_synonymsdata_0_synonyms    | "testing\|one\|two\|three\|four"   |
      | Answer 1                      | match (testing one two three four) |
      | id_fraction_0                 | 100%                               |
      | id_feedback_0                 | Well done!                         |
      | id_otherfeedback              | Sorry, no.                         |
      | Hint 1                        | Please try again.                  |
      | Hint 2                        | Use a calculator if necessary.     |
    Then I should see "My first pattern match question"

    # Preview it. Test correct and incorrect answers.
    When I choose "Preview" action for "My first pattern match question" in the question bank
    And I switch to "questionpreview" window

    And I set the following fields to these values:
      | How questions behave | Interactive with multiple tries |
      | Marked out of        | 3                               |
      | Marks                | Show mark and max               |
    And I press "Start again with these options"
    Then I should see "Listen, translate and write"
    And the state of "Listen, translate and write" question is shown as "Tries remaining: 3"
    When I set the field "Answer:" to "testing"
    And I press "Check"
    Then I should see "Sorry, no."
    And I should see "Please try again."
    When I press "Try again"
    Then the state of "Listen, translate and write" question is shown as "Tries remaining: 2"
    When I set the field "Answer:" to "testing one two three four"
    And I press "Check"
    Then I should see "Well done!"
    Then the state of "Listen, translate and write" question is shown as "Correct"
    And I switch to the main window

    # Backup the course and restore it.
    When I log out
    And I log in as "admin"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    When I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    Then I should see "Course 2"
    When I navigate to "Question bank" in current page administration
    Then I should see "My first pattern match question"

    # Edit the copy and verify the form field contents.
    When I choose "Edit question" action for "My first pattern match question" in the question bank
    Then the following fields match these values:
      | Question name                 | My first pattern match question           |
      | Question text                 | Listen, translate and write               |
      | id_synonymsdata_0_word        | any                                       |
      | id_synonymsdata_0_synonyms    | "testing\|one\|two\|three\|four"          |
      | Answer 1                      | match (testing one two three four)        |
      | id_fraction_0                 | 100%                                      |
      | id_feedback_0                 | Well done!                                |
      | id_otherfeedback              | Sorry, no.                                |
      | Hint 1                        | Please try again.                         |
      | Hint 2                        | Use a calculator if necessary.            |
    And I set the following fields to these values:
      | Question name | Edited question name |
    And I press "id_submitbutton"
    Then I should see "Edited question name"

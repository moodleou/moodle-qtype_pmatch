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

  Scenario: Create, edit then preview a pattern match question.
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    # Create a new question.
    And I add a "Pattern match" question filling the form with:
      | Question name              | My first pattern match question                                 |
      | Question text              | Listen, translate and write                                     |
      | id_usecase                 | Yes, case must match                                            |
      | id_allowsubscript          | Yes                                                             |
      | id_allowsuperscript        | Yes                                                             |
      | id_forcelength             | warn that answer is too long and invite respondee to shorten it |
      | id_applydictionarycheck    | Do not check spelling of student                                |
      | id_sentencedividers        | ?!                                                              |
      | id_converttospace          | ;:                                                              |
      | id_synonymsdata_0_word     | any                                                             |
      | id_synonymsdata_0_synonyms | "testing\|one\|two\|three\|four"                                |
      | Answer 1 must match        | match (testing one two three four)                              |
      | id_fraction_0              | 100%                                                            |
      | id_feedback_0              | Well done!                                                      |
      | id_otherfeedback           | Sorry, no.                                                      |
      | Hint 1                     | Please try again.                                               |
      | Hint 2                     | Use a calculator if necessary.                                  |
    Then I should see "My first pattern match question"
    # Checking that the next new question form displays user preferences settings.
    When I press "Create a new question ..."
    And I set the field "item_qtype_pmatch" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    Then the following fields match these values:
      | id_usecase              | Yes, case must match                                            |
      | id_allowsubscript       | Yes                                                             |
      | id_allowsuperscript     | Yes                                                             |
      | id_forcelength          | warn that answer is too long and invite respondee to shorten it |
      | id_applydictionarycheck | Do not check spelling of student                                |
      | id_sentencedividers     | ?!                                                              |
      | id_converttospace       | ;:                                                              |
    And I press "Cancel"

    # Preview it. Test correct and incorrect answers.
    And I am on the "My first pattern match question" "core_question > preview" page

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
      | Answer 1 must match           | match (testing one two three four)        |
      | id_fraction_0                 | 100%                                      |
      | id_feedback_0                 | Well done!                                |
      | id_otherfeedback              | Sorry, no.                                |
      | Hint 1                        | Please try again.                         |
      | Hint 2                        | Use a calculator if necessary.            |
    And I set the following fields to these values:
      | Question name | Edited question name |
    And I should not see "Overall grading accuracy"
    And I press "id_submitbutton"
    Then I should see "Edited question name"

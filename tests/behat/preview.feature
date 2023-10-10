@ou @ou_vle @qtype @qtype_pmatch @_switch_window @javascript
Feature: Preview a pattern match question
  As a teacher
  In order to check my pattern match questions will work for students
  I need to preview them

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
      | questioncategory | qtype  | name                            | template |
      | Test questions   | pmatch | My first pattern match question | listen   |
      | Test questions   | pmatch | Frog but not toad               | frogtoad |
      | Test questions   | pmatch | Spanish question                | spanish  |

  Scenario: Preview a pattern match question.
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    # Create a new question.
    When I add a "Pattern match" question filling the form with:
      | Question name              | Test pattern match question preview                             |
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
    And I am on the "My first pattern match question" "core_question > preview" page
    And I set the following fields to these values:
      | How questions behave | Interactive with multiple tries |
      | Marked out of        | 3                               |
      | Marks                | Show mark and max               |
    And I press "Start again with these options"
    Then I should see "Listen, translate and write"
    And the state of "Listen, translate and write" question is shown as "Tries remaining: 3"
    And I set the field "Answer:" to "testing"
    And I press "Check"
    And I should see "Sorry, no."
    And I should see "Please try again."
    And I press "Try again"
    And the state of "Listen, translate and write" question is shown as "Tries remaining: 2"
    And I set the field "Answer:" to "testing one two three four"
    And I press "Check"
    And I should see "Well done!"
    And the state of "Listen, translate and write" question is shown as "Correct"

  Scenario: Fill with correct in question preview uses the model answer
    Given I am on the "My first pattern match question" "core_question > preview" page logged in as teacher
    And I should see "Listen, translate and write"
    When I press "Fill in correct responses"
    Then I should see "testing one two three four"

  Scenario: Click on the reset button.
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    When I am on the "My first pattern match question" "core_question > preview" page
    And I set the field "Answer:" to "aicd"
    And I click on "Reset" "button"
    Then I should see "testing one wto there fuor"

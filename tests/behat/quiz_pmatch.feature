@ou @ou_vle @qtype @qtype_pmatch
Feature: Test creating a new Pattern Matching question in Quiz
  As a student
  In order to demonstrate what I know
  I need to be able to create a Pattern Matching question

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
    And the following "activities" exist:
      | activity | course |  name           | idnumber | intro                 | section | grade |
      | quiz     | C1     |  Test quiz name | quiz1    | Test quiz description | 1       | 10    |

  @javascript
  Scenario: Attempt the quiz with a pattern match question
    Given I am on the "Test quiz name" "quiz activity" page logged in as teacher
    # Create a new question.
    And I follow "Add question"
    And I press "Add"
    And I follow "a new question"
    And I set the field "Pattern match" to "1"
    And I click on ".submitbutton" "css_element"
    And I set the following fields to these values:
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
      | Model answer               | testing one two three four                                      |
      | id_otherfeedback           | Sorry, no.                                                      |
      | Hint 1                     | Please try again.                                               |
      | Hint 2                     | Use a calculator if necessary.                                  |
    And I wait "10" seconds
    And I click on "#id_submitbutton" "css_element"
    And I log out
    When I am on the "Test quiz name" "quiz activity" page logged in as student
    And I press "Attempt quiz"
    And I set the field "Answer" to "testing one two three four"
    And I press "Finish attempt ..."
    And I should see "Answer saved"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    Then I should see "10.00 out of 10.00"
    And I should see "Well done!"

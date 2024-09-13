@ou @ou_vle @qtype @qtype_pmatch @javascript
Feature: Test backup restore of pattern match question
  As a teacher
  In order re-use my courses containing pattern match questions
  I need to be able to backup and restore them

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
    And the following config values are set as admin:
      | enableasyncbackup | 0 |

  Scenario: Backup an restore a pattern match question.
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    When I add a "Pattern match" question filling the form with:
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
      | Model answer               | testing one two three four                                                             |
      | id_fraction_0              | 100%                                                            |
      | id_feedback_0              | Well done!                                                      |
      | id_otherfeedback           | Sorry, no.                                                      |
      | Hint 1                     | Please try again.                                               |
      | Hint 2                     | Use a calculator if necessary.                                  |
    And I log out
    And I log in as "admin"
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    Then I should see "Course 2"
    And I navigate to "Question bank" in current page administration
    And I should see "My first pattern match question"

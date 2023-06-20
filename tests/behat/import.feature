@ou @ou_vle @qtype @qtype_pmatch
Feature: Import pattern match questions
  As a teacher
  In order to reuse my pattern match questions
  I need to be able to import them

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

  @javascript @_file_upload
  Scenario: Import a pattern match questions
    Given I am on the "Course 1" "core_question > course question import" page logged in as teacher
    When I set the field "id_format_xml" to "1"
    And I upload "question/type/pmatch/tests/fixtures/testquestion.moodle.xml" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "Importing 1 questions from file"
    And I should see "1. Listen, translate and write"
    And I press "Continue"
    And I should see "Imported pattern match question"

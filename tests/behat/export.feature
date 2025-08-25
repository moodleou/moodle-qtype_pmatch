@ou @ou_vle @qtype @qtype_pmatch @_file_upload
Feature: Test exporting pattern match questions
  As a teacher
  In order to be able to reuse my pattern match questions
  I need to export them

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
  Scenario: Export pattern match questions
    # Import sample file
    Given I am on the "Course 1" "core_question > course question import" page logged in as teacher
    When I set the field "id_format_xml" to "1"
    And I upload "question/type/pmatch/tests/fixtures/testquestion.moodle.xml" file to "Import" filemanager
    And I press "id_submitbutton"
    And I press "Continue"
    And I am on the "Course 1" "core_question > course question export" page
    And I set the field "id_format_xml" to "1"
    And I set the field "category" to "Imported questions (1)"
    And I press "Export questions to file"
    Then following "click here" should download between "1500" and "2500" bytes
    # If the download step is the last in the scenario then we can sometimes run
    # into the situation where the download page causes a http redirect but behat
    # has already conducted its reset (generating an error). By putting a logout
    # step we avoid behat doing the reset until we are off that page.
    And I log out

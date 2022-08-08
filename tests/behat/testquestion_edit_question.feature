@ou @ou_vle @qtype @qtype_pmatch
Feature: Sample pattern match responses preserved when editing a question
  In order to be able to update my pattern-match questions without looksing testing information
  As an teacher
  I need the sample responses to be preserved when a new version of the quesiton is created

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
      | questioncategory | qtype    | name                            | template |
      | Test questions   | pmatch   | My first pattern match question | listen   |
    And the default question test responses exist for question "My first pattern match question"

  Scenario: Edit a pattern match question and verify that the test responses are still present
    # Edit the question.
    When I am on the "My first pattern match question" "core_question > edit" page logged in as teacher
    And I set the following fields to these values:
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

@ou @ou_vle @qtype @qtype_pmatch
Feature: Test uploading test responses in the pattern match test this question feature
  In order to test pattern match question accuracy
  As a teacher
  I need to upload test responses pattern match questions.

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
    And I log in as "teacher"
    Given I am on the pattern match test responses page for question "My first pattern match question"
    And I click on "Upload responses" "link"

  @javascript
  Scenario: Upload responses to test with.
    # Confirm list responses is correct.
    And I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Back to Test question"
    And I should see "Marked responses to upload"
    And I upload "question/type/pmatch/tests/fixtures/myfirstquestion_responses.csv" file to "Marked responses" filemanager
    And I press "Upload these responses"
    And I should see "Saved 13 responses"
    And I should see "The following 1 responses were not saved"
    And I should see "Each row should contain exactly two items, a numerical mark and a response. Row 11 contains 3 item(s)."
    And I should see "Upload another file"

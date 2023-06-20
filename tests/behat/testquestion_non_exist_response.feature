@ou @ou_vle @qtype @qtype_pmatch
Feature: Test no test responses existing for pattern match question
  In order to manage test responses in the test this question feature
  As a teacher
  I need to know when no test responses exist for pattern match questions.

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

  @javascript
  Scenario: Confirm the display when no test responses exist for a pattern match question.
    # Confirm list responses is correct.
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Show responses that are"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Sample responses: 0 "
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    And I should not see "Nothing to display"
    And I wait until the page is ready
    And "Add new response" "button" should be visible
    And I should see "Rules" in the "responses" "table"
    And the "#tqheadercheckbox" "css_element" should be disabled
    And the "Test selected responses" "button" should be disabled
    And the "Delete" "button" should be disabled
    And I click on "Add new response" "button"
    And I set the field "new-response" to "New test response"
    When I click on "Cancel" "button" in the ".generaltable" "css_element"
    And the "Test selected responses" "button" should be disabled
    And the "Delete" "button" should be disabled
    And I should not see "Cancel" in the ".generaltable" "css_element"
    And the "Add new response" "button" should be enabled
    And I click on "Add new response" "button"
    And I set the field "new-response" to "New test response"
    When I click on "Save" "button"
    Then I should see "Sample responses: 1"
    And I should see "Marked correctly: 1 (100%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    When I click on "#tqheadercheckbox" "css_element"
    And I press "Delete"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "The responses were successfully deleted."
    And I press "Continue"
    And the "Test selected responses" "button" should be disabled
    And the "Delete" "button" should be disabled
    And the "#tqheadercheckbox" "css_element" should be disabled

  @javascript @_file_upload
  Scenario: Upload responses to test with.
    When I am on the "My first pattern match question" "qtype_pmatch > test responses" page logged in as teacher
    And I click on "Upload responses" "button"
    # Confirm list responses is correct.
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Back to Test question"
    And I should see "Marked responses to upload"
    And I upload "question/type/pmatch/tests/fixtures/uploadreponses.csv" file to "Marked responses" filemanager
    And I press "Upload these responses"
    And I should see "Saved 8 responses"
    And I should see "Upload another file"

  @javascript @_file_upload
  Scenario: Test error message if the file doesn't meet the condition.
    When I am on the "My first pattern match question" "qtype_pmatch > test responses upload" page logged in as teacher
    # Case 1: The file must be in .csv format.
    And I upload "question/type/pmatch/tests/fixtures/testerrorcase1.xls" file to "Marked responses" filemanager
    And I press "Upload these responses"
    Then I should see "The file must be in .csv/.xlsx/.html/.json/.ods format."
    # Case 2: The file requires at least two rows (the first row is the header row, the second row onwards for responses).
    And I upload "question/type/pmatch/tests/fixtures/testerrorcase2.csv" file to "Marked responses" filemanager
    And I press "Upload these responses"
    And I should see "The file requires at least two rows (the first row is the header row, the second row onwards for responses)."
    # Case 3: The file has more than two columns. Please only include the expected mark and response.
    And I upload "question/type/pmatch/tests/fixtures/testerrorcase3.csv" file to "Marked responses" filemanager
    And I press "Upload these responses"
    And I should see "The file has more than two columns. Please only include the expected mark and response."
    # Case 4: The file requires at least two columns (the first column for expected marks, the second column for responses).
    And I upload "question/type/pmatch/tests/fixtures/testerrorcase4.csv" file to "Marked responses" filemanager
    And I press "Upload these responses"
    And I should see "The file requires at least two columns (the first column for expected marks, the second column for responses)."
    # Case 5: test error case 2 and case 4
    And I upload "question/type/pmatch/tests/fixtures/testerror.csv" file to "Marked responses" filemanager
    And I press "Upload these responses"
    And I should see "The file requires at least two rows (the first row is the header row, the second row onwards for responses)."
    And I should see "The file requires at least two columns (the first column for expected marks, the second column for responses)."
    # Case 6: The expected mark can be either 0 or 1.
    And I upload "question/type/pmatch/tests/fixtures/myfirstquestion_responses.csv" file to "Marked responses" filemanager
    And I press "Upload these responses"
    And I should see "greater than one: The expected mark was 2. Only 0 or 1 are allowed."
    And I should see "negative: The expected mark was -1. Only 0 or 1 are allowed."

  @javascript @_file_upload
  Scenario: Test upload XLSX file type.
    When I am on the "My first pattern match question" "qtype_pmatch > test responses upload" page logged in as teacher
    And I upload "question/type/pmatch/tests/fixtures/testreponses_xlsx_error_1.xlsx" file to "Marked responses" filemanager
    And I press "Upload these responses"
    Then I should see "The file requires at least two columns (the first column for expected marks, the second column for responses)."
    And I upload "question/type/pmatch/tests/fixtures/testreponses_xlsx_normal.xlsx" file to "Marked responses" filemanager
    And I press "Upload these responses"
    And I should see "Saved 4 responses"

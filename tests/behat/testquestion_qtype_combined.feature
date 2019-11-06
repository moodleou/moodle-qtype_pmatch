@ou @ou_vle @qtype @qtype_pmatch
Feature: Test the basic functionality of Test Question Link when preview combined Pattern Match question type
  In order to evaluate students responses, As a teacher I need to
  Create and preview combined (Combined) Pattern Match question type.

  Background:
    Given I check the "combined" question type already installed
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    Then I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    Then I press "Create a new question ..."
    And I set the field "Combined" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    Then I should see "Adding a combined question"
    And I set the field "Question name" to "Combined 001"
    And I set the field "Question text" to " What 5 + 5 ? [[1:pmatch:__10__]]. <br/>What is the IUPAC name of the molecule? [[2:pmatch:__20__]]. <br/>What is the pH of a 0.1M solution? [[3:numeric:__10__]]"
    Then I set the field "General feedback" to "The molecule is ethanoic acid which is more commonly known as acetic acid or in dilute solution as vinegar. The constituent elements are carbon (grey), hydrogen (white) and oxygen (red). A 0.1M solution has a pH of 2.88 and when a solution is combined with oil the result is a vinaigrette."
    And I press "Verify the question text and update the form"
    Then I follow "'pmatch' input '1'"
    And I set the following fields to these values:
      | id_subqpmatch1defaultmark     | 50%                                |
      | Spell checking                | Do not check spelling of student   |
      | id_subqpmatch1answer_0        | match_mw (ethanoic acid)           |
      | id_subqpmatch1generalfeedback | You have the incorrect IUPAC name. |
    Then I follow "'pmatch' input '2'"
    And I set the following fields to these values:
      | id_subqpmatch2defaultmark     | 25%                                |
      | Spell checking                | Do not check spelling of student   |
      | id_subqpmatch2answer_0        | match_m (10)                       |
      | id_subqpmatch2generalfeedback | You have the incorrect IUPAC name. |
    Then I follow "'numeric' input '3'"
    And I set the following fields to these values:
      | id_subqnumeric3defaultmark     | 25%                                     |
      | id_subqnumeric3answer_0        | 2.88                                    |
      | Scientific notation            | No                                      |
      | id_subqnumeric3generalfeedback | You have the incorrect value for the pH |
    And I press "id_submitbutton"
    Then I should see "Combined 001"
    When I choose "Preview" action for "Combined 001" in the question bank
    And I switch to "questionpreview" window

  @javascript
  Scenario: Should see the test question link on preview page Combined Pattern Match question type.
    Then "Test sub question 1" "link" should be visible
    And "Test sub question 2" "link" should be visible
    When I click on "Test sub question 1" "link"
    Then I should see "Pattern-match question testing tool: Testing question: 1"
    And I should see "Showing the responses for the selected question: 1"
    When I click on "Add new response" "button"
    And I set the field "new-response" to "New test response"
    And I click on "Save" "button"
    Then I should see "New test response"
    # Check Delete response.
    And I set the field with xpath "//form[@id='attemptsform']//table[@id='responses']//td[@id='qtype-pmatch-testquestion_r50_c0']//input" to "1"
    And I click on "Delete" "button"
    And I press "Yes"
    And I press "Continue"
    Then I should not see "New test response"

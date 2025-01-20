@ou @ou_vle @qtype @qtype_pmatch
Feature: Test the basic functionality of Test Question Link when preview combined Pattern Match question type
  In order to evaluate students responses, As a teacher I need to
  Create and preview combined (Combined) Pattern Match question type.

  Background:
    Given the qtype_combined plugin is installed
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype    | name         | template   |
      | Test questions   | combined | Combined 001 | twopmatchs |

  @javascript
  Scenario: Should see the test question link on preview page Combined Pattern Match question type.
    Given I am on the "Combined 001" "core_question > preview" page logged in as teacher1
    # Check teacher click on the reset button.
    And I set the field "Answer 1" to "aicd"
    When I click on "Reset" "button"
    And the field "Answer 1" matches value "ethaic aicd"
    And "Test sub question 1" "link" should be visible
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

  @javascript
  Scenario: Spell checking is disable
    Given I am on the "Combined 001" "core_question > preview" page logged in as teacher1
    When "//input[@value='ethaic aicd' and @spellcheck='false']" "xpath" should be visible
    And "//textarea[@spellcheck='false']" "xpath" should exist
    And I press "Save"
    Then "//input[@value='ethaic aicd' and @spellcheck='false']" "xpath" should be visible
    And "//textarea[@spellcheck='false']" "xpath" should exist

  @javascript
  Scenario: Spell checking disable when use sup-sub on combined pmatch.
    Given I am on the "Combined 001" "core_question > edit" page logged in as teacher1
    And I expand all fieldsets
    When I set the field "Allow use of subscript" to "Yes"
    Then the "Spell checking" "field" should be disabled
    And the "Add these words to dictionary" "field" should be disabled
    And I should see "Allowing use of sub- or superscript will disable spellchecking."

  @javascript
  Scenario: Edit combine pmatch question and check the placeholder.
    Given I am on the "Combined 001" "core_question > edit" page logged in as teacher1
    When I expand all fieldsets
    And I should see "Appropriate input size:"
    And the following fields match these values:
      | subq:pmatch:1:placeholder | __15__ |
    And I set the following fields to these values:
      | Question name             | Edited question name |
      | id_subqpmatch1modelanswer |                      |
    Then the following fields match these values:
      | subq:pmatch:1:placeholder | __6__ |
    And I set the following fields to these values:
      | id_subqpmatch1modelanswer | testing one two three four |
    And the following fields match these values:
      | subq:pmatch:1:placeholder | __28__ |

  @javascript
  Scenario: Edit combine pmatch question and check model answer validation.
    Given I am on the "Combined 001" "core_question > edit" page logged in as teacher1
    And I expand all fieldsets
    And "Help with Answer matching" "icon" should exist
    And I click on "Help with Answer matching" "icon"
    And I should see "If you have a short phrase you want to match, you should enclose it in square brackets ([...])."
    And "More help" "link" should exist
    When I set the following fields to these values:
      | id_subqpmatch1modelanswer |  |
    And I press "id_submitbutton"
    Then I should see "You must provide a possible response to this question, which would be graded 100% correct."

  @javascript
  Scenario: Edit combine pmatch question and check the quotematching.
    Given I am on the "Combined 001" "core_question > edit" page logged in as teacher1
    And I expand all fieldsets
    When I set the following fields to these values:
      | id_subqpmatch1quotematching   | 0                                             |
      | id_subqpmatch1generalfeedback | Correct response: “ethanoic acid”. ‘Not bad!’ |
      | id_subqpmatch2quotematching   | 1                                             |
      | id_subqpmatch2generalfeedback | Correct response: “ethanoic acid”. ‘Not bad!’ |
    And I press "id_submitbutton"
    And I choose "Edit question" action for "Combined 001 " in the question bank
    Then the following fields match these values:
      | id_subqpmatch1quotematching   | 0                                             |
      | id_subqpmatch1generalfeedback | Correct response: "ethanoic acid". 'Not bad!' |
      | id_subqpmatch2quotematching   | 1                                             |
      | id_subqpmatch2generalfeedback | Correct response: “ethanoic acid”. ‘Not bad!’ |

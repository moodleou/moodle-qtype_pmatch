@ou @ou_vle @qtype @qtype_pmatch @_switch_window @javascript
Feature: Test the model answer functionality of pmatch question type
  In order evaluate students understanding
  As an teacher
  I need to create and preview pattern match questions.

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
    And the default question test responses exist for question "My first pattern match question"

  Scenario: Fill with correct in question preview uses the model answer
    When I am on the "My first pattern match question" "core_question > preview" page logged in as teacher
    Then I should see "Listen, translate and write"
    And I press "Fill in correct responses"
    And I should see "testing one two three four"

  Scenario: Validation of the model answer
    When I am on the "Frog but not toad" "core_question > edit" page logged in as teacher
    And I should see "Editing a Pattern match question"
    And I click on "Expand all" "link"
    And I set the following fields to these values:
      | id_modelanswer | I saw a toad which was bigger than a frog |
    And I press "id_submitbutton"
    Then I should see "'I saw a toad which was bigger than a frog' is not a correct answer to this question."

    When I set the following fields to these values:
      | id_modelanswer | |
    # Should save with no validation error.
    And I press "id_submitbutton"
    Then "Frog but not toad" "table_row" in the "categoryquestions" "table" should be visible

    When I choose "Edit question" action for "Frog but not toad" in the question bank
    And I set the following fields to these values:
      | id_modelanswer | frog |
    And I press "id_submitbutton"
    Then "Frog but not toad" "table_row" in the "categoryquestions" "table" should be visible

  Scenario: Validation of the model answer with non-standard options
    When I am on the "Spanish question" "core_question > edit" page logged in as teacher
    # Should save with no validation error.
    And I press "id_submitbutton"
    Then "Spanish question" "table_row" in the "categoryquestions" "table" should be visible

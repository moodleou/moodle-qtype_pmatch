@ou @ou_vle @qtype @qtype_pmatch @_switch_window @javascript
Feature: Test backup and restore of a pmatch question with responses and matches
  In order to manage pmatch questions
  As an admin
  I need to be able to backup and restore with all testquestion data.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype    | name         | template |
      | Test questions   | pmatch   | My first pattern match question | listen    |
    And the default question test responses exist for question "My first pattern match question"
    And I log in as "admin"

  Scenario: Test backup and restore with testquestion data.
    Given I am on the pattern match test responses page for question "My first pattern match question"
    # Check course C1 version of uploaded responses.
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "Showing the responses for the selected question: My first pattern match question"
    And I should see "Pos=0/0 Neg=0/0 Unm=13 Acc=0%"
    And I should see "1" in the "testing one two three four" "table_row"
    # Now mark responses in order to test their backup.
    When I set the field "tqheadercheckbox" to "1"
    And I press "Test selected responses"
    And I press "Continue"
    # Make a backup and restore to new course.
    Given I am on homepage
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    Then I should see "Course 2"
    # Check the new course's testquestion data.
    When I navigate to "Question bank" in current page administration
    Then I should see "My first pattern match question"
    When I follow "Edit"
    Then I should see "Editing a Pattern match question"
    When I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I follow "Preview"
    And I switch to "questionpreview" window
    And I follow "Test this question"
    # Final check that the marked responses have been restored properly.
    Then I should see "Pattern-match question testing tool: Testing question: My first pattern match question"
    And I should see "testing one two three four" in the "#qtype-pmatch-testquestion_r0_c5" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c4" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c3" "css_element"
    And I should see "1" in the "#qtype-pmatch-testquestion_r0_c2" "css_element"
    And I should see "Pos=1/6 Neg=6/6 Unm=1 Acc=58%"

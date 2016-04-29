@ou @ou_vle @qtype @qtype_pmatch
Feature: Test answer accuracy and response coverage
  In order to find out whether an answer rule is accurate and covers the responses
  As an teacher
  I need to see the accuracy and coverage features on the question edit page.

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
    And I log in as "teacher"
    And I follow "Course 1"
    And I navigate to "Question bank" node in "Course administration"

  @javascript
  Scenario: Get standard responses graded against current answer rules.
    When I click on "Edit" "link" in the "My first pattern match question" "table_row"
    # First check of question editing page.
    Then I should see "Editing a Pattern match question"
    And I should see "Pos = 0 Neg = 0" in the "//div[@id='fitem_accuracy_0']" "xpath_element"
    # Now start the process of grading the responses.
    When I click on "Preview" "link"
    And I switch to "questionpreview" window
    And I click on "Test this question" "link"
    # Confirm the responses have no computed marks yet.
    And I should see "Pos=0/0 Neg=0/0 Unm=13 Acc=0%"
    # Now grade the responses (add computer marks).
    When I click on "Select all" "link"
    And I press "Test the question using these responses"
    Then I should see "Processing response 13 of 13: ."
    And I press "Continue"
    Then I should see "Pos=1/6 Neg=6/6 Unm=1 Acc=58%"
    # Now check edit question page for updated accuracy and coverage.
    When I switch to the main window
    And I am on homepage
    And I follow "Course 1"
    And I navigate to "Question bank" node in "Course administration"
    And I click on "Edit" "link" in the "My first pattern match question" "table_row"
    Then I should see "Editing a Pattern match question"
    And I should see "Pos = 1 Neg = 0" in the "//div[@id='fitem_accuracy_0']" "xpath_element"
    When I click on "Show coverage" "link"
    Then I should see "testing one two three four" in the "//div[@id='matchedresponses_0_inner']" "xpath_element"

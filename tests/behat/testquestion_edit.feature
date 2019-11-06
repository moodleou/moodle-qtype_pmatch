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
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  @javascript
  Scenario: Get standard responses graded against current answer rules.
    When I choose "Edit question" action for "My first pattern match question" in the question bank
    # First check of question editing page.
    Then I should see "Editing a Pattern match question"
    Then I should see "Responses not matched above: 0" in the "#fitem_accuracy_0" "css_element"
    Then I should see "Correctly matched by this rule: 0" in the "#fitem_accuracy_0" "css_element"
    Then I should see "Incorrectly matched: 0" in the "#fitem_accuracy_0" "css_element"
    Then I should see "Responses still to be processed below: 0" in the "#fitem_accuracy_0" "css_element"
    # Now start the process of grading the responses.
    When I click on "Preview" "link"
    And I switch to "questionpreview" window
    And I click on "Test this question" "link"
    # Confirm the responses have no computed marks yet.
    Then I should see "Sample responses: 13"
    And I should see "Marked correctly: 0 (0%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 0 (missed negative)"
    # Now grade the responses (add computer marks).
    When I set the field "tqheadercheckbox" to "1"
    And I press "Test selected responses"
    Then I should see "Processing response 13 of 13: ."
    And I press "Continue"
    Then I should see "Sample responses: 13"
    And I should see "Marked correctly: 7 (54%)"
    And I should see "Computed mark greater than human mark: 0 (missed positive)"
    And I should see "Computed mark less than human mark: 5 (missed negative)"
    # Now check edit question page for updated accuracy and coverage.
    When I switch to the main window
    And I am on homepage
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I choose "Edit question" action for "My first pattern match question" in the question bank
    Then I should see "Editing a Pattern match question"
    And I should see "Responses not matched above: 12" in the "#fitem_accuracy_0" "css_element"
    And I should see "Correctly matched by this rule: 1" in the "#fitem_accuracy_0" "css_element"
    And I should see "Incorrectly matched: 0" in the "#fitem_accuracy_0" "css_element"
    And I should see "Responses still to be processed below: 11" in the "#fitem_accuracy_0" "css_element"
    When I click on "Show coverage" "link"
    Then I should see "testing one two three four" in the "//div[@id='matchedresponses_0_inner']" "xpath_element"

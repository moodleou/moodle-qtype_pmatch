@ou @ou_vle @qtype @qtype_pmatch
Feature: Test the rule creation assistant
  In order to create a basic pattern match question
  As an in-experienced teacher
  I need to use the rule creation assistant to help populate the first answer.

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
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  @javascript
  Scenario: Using the rule creation assistant.
    # Do not use I add a "Pattern match" question filling the form with as it requires a save
    And I follow "Question bank"
    And I press "Create a new question ..."
    And I set the field "Pattern match" to "1"
    And I click on ".submitbutton" "css_element"
    And I should see "Show/hide rule creation assistant"
    # Open the rule creation assistant
    And I follow "Show/hide rule creation assistant"
    # Test term add
    When I set the field "Term" to "a"
    And I press "rc_termadd_0"
    Then I should see "match_w(a)"
    # Test term exclude
    When I set the field "Term" to "b"
    And I press "rc_termexclude_0"
    Then I should see "not(match_w(b))"
    And I should see "match_all"
    # Test term or
    When I set the field "Term" to "c"
    And I press "rc_termor_0"
    Then I should see "match_w(c)"
    And I should see "match_any"
    # Test reset
    When I press "rc_clear_0"
    Then I should not see "match_w(c)"
    # Test template
    When I set the field "Term" to "a"
    And I press "rc_termadd_0"
    Then I should see "match_w(a)"
    And I set the field "Template" to "b"
    And I press "rc_templateadd_0"
    Then I should see "match_wm(b*)"
    # Test precedes
    When I select "a" from the "precedes1" singleselect
    And I select "b*" from the "precedes2" singleselect
    And I press "rc_precedesadd_0"
    Then I should see "match_w(a b*)"
    And I press "rc_clear_0"
    # Test closely precedes
    When I set the field "Term" to "a"
    And I press "rc_termadd_0"
    And I set the field "Term" to "b"
    And I press "rc_termadd_0"
    When I select "a" from the "cprecedes1" singleselect
    And I select "b" from the "cprecedes2" singleselect
    And I press "rc_cprecedesadd_0"
    Then I should see "match_w(a_b)"
    # It would have been nice to check for the full string
    # but "match_all(match_w(a) match_w(b) match_w(a_b))" is now
    # multi-line (for better user experience) and that seems
    # difficult to test for with current tests.

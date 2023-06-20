@ou @ou_vle @qtype @qtype_pmatch
Feature: Test creating a new Pattern Matching question
  In order to create a pattern match question
  As an in-experienced teacher
  I need to be able to create a Pattern Matching question

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

  @javascript
  Scenario: Create a pattern match question.
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    # Create a new question.
    And I add a "Pattern match" question filling the form with:
      | Question name              | My first pattern match question                                 |
      | Question text              | Listen, translate and write                                     |
      | id_usecase                 | Yes, case must match                                            |
      | id_allowsubscript          | Yes                                                             |
      | id_allowsuperscript        | Yes                                                             |
      | id_forcelength             | warn that answer is too long and invite respondee to shorten it |
      | id_applydictionarycheck    | Do not check spelling of student                                |
      | id_sentencedividers        | ?!                                                              |
      | id_converttospace          | ;:                                                              |
      | id_synonymsdata_0_word     | any                                                             |
      | id_synonymsdata_0_synonyms | "testing\|one\|two\|three\|four"                                |
      | Answer 1 must match        | match (testing one two three four)                              |
      | id_fraction_0              | 100%                                                            |
      | id_feedback_0              | Well done!                                                      |
      | id_otherfeedback           | Sorry, no.                                                      |
      | Hint 1                     | Please try again.                                               |
      | Hint 2                     | Use a calculator if necessary.                                  |
    Then I should see "My first pattern match question"
    # Checking that the next new question form displays user preferences settings.
    When I press "Create a new question ..."
    And I set the field "item_qtype_pmatch" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    Then the following fields match these values:
      | id_usecase              | Yes, case must match                                            |
      | id_allowsubscript       | Yes                                                             |
      | id_allowsuperscript     | Yes                                                             |
      | id_forcelength          | warn that answer is too long and invite respondee to shorten it |
      | id_applydictionarycheck | Do not check spelling of student                                |
      | id_sentencedividers     | ?!                                                              |
      | id_converttospace       | ;:                                                              |
    And I press "Cancel"

  @javascript
  Scenario: Create the rule creation assistant.
    # Do not use I add a "Pattern match" question filling the form with as it requires a save
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    When I press "Create a new question ..."
    And I set the field "Pattern match" to "1"
    And I click on ".submitbutton" "css_element"
    And I should see "Show/hide rule creation assistant"
    And the field "Answer 1 must match" matches value "match ()"
    # Open the rule creation assistant
    And I follow "Show/hide rule creation assistant"
    # Test term add
    And I set the field "Term" to "a"
    And I press "rc_termadd_0"
    Then I should see "match_w(a)"
    # Test term exclude
    And I set the field "Term" to "b"
    And I press "rc_termexclude_0"
    And I should see "not(match_w(b))"
    And I should see "match_all"
    # Test term or
    And I set the field "Term" to "c"
    And I press "rc_termor_0"
    And I should see "match_w(c)"
    And I should see "match_any"
    # Test reset
    And I press "rc_clear_0"
    And I should not see "match_w(c)"
    # Test template
    And I set the field "Term" to "a"
    And I press "rc_termadd_0"
    And I should see "match_w(a)"
    And I set the field "Template" to "b"
    And I press "rc_templateadd_0"
    And I should see "match_wm(b*)"
    # Test precedes
    And I select "a" from the "precedes1" singleselect
    And I select "b*" from the "precedes2" singleselect
    And I press "rc_precedesadd_0"
    And I should see "match_w(a b*)"
    And I press "rc_clear_0"
    # Test closely precedes
    And I set the field "Term" to "a"
    And I press "rc_termadd_0"
    And I set the field "Term" to "b"
    And I press "rc_termadd_0"
    And I select "a" from the "cprecedes1" singleselect
    And I select "b" from the "cprecedes2" singleselect
    And I press "rc_cprecedesadd_0"
    And I should see "match_w(a_b)"
    # It would have been nice to check for the full string
    # but "match_all(match_w(a) match_w(b) match_w(a_b))" is now
    # multi-line (for better user experience) and that seems
    # difficult to test for with current tests.

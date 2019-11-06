@ou @ou_vle @qtype @qtype_pmatch @_switch_window @javascript
Feature: Test spelling check of a pmatch question
  In order to support multi language for spell check
  As an admin
  I need to be able to select which language for Question to check the spelling

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype  | name                            | template |
      | Test questions   | pmatch | My first pattern match question | listen   |
    And I setup the available dictionaries for the pattern-match question type

  Scenario: Spell checking with Spell checking library is set to No spell checking available
    Given the following config values are set as admin:
      | spellchecker | null | qtype_pmatch |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    When I choose "Edit question" action for "My first pattern match question" in the question bank
    When I expand all fieldsets
    Then I should see "Spell checking"
    And I should see "Do not check spelling of student"
    And the "Spell checking" "select" should be disabled
    And I press "Cancel"

  Scenario: Spell checking with Spell checking library is set to Pspell spell-checking library
    Given I check the "pspell" spell checking library already installed
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And the following config values are set as admin:
      | config       | value  | plugin       |
      | spellchecker | pspell | qtype_pmatch |
    And I choose "Edit question" action for "My first pattern match question" in the question bank
    When I expand all fieldsets
    Then I should see "Spell checking"
    And I click on "Spell checking" "select"
    And I should see "English"
    And I set the field "Spell checking" to "Do not check spelling of student"
    And the "Add these words to dictionary" "field" should be disabled
    And I press "Cancel"

  Scenario: Spell checking with Spell checking library is set to Enchant spell-checking library
    Given I check the "enchant" spell checking library already installed
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And the following config values are set as admin:
      | config       | value   | plugin       |
      | spellchecker | enchant | qtype_pmatch |
    And I choose "Edit question" action for "My first pattern match question" in the question bank
    When I expand all fieldsets
    Then I should see "Spell checking"
    And I should not see "No dictionaries available"
    And I click on "Spell checking" "select"
    And I should see "English"
    And I set the field "Spell checking" to "Do not check spelling of student"
    And the "Add these words to dictionary" "field" should be disabled
    And I press "Cancel"

  Scenario: Question author/administrator will see warning when edit an Pmatch question with missing dictionary
    Given I check the "enchant" spell checking library already installed
    And the following "questions" exist:
      | questioncategory | qtype  | name                                      | template | applydictionarycheck |
      | Test questions   | pmatch | Missing dictionary pattern match question | listen   | vi                   |
    And the following config values are set as admin:
      | config       | value   | plugin       |
      | spellchecker | enchant | qtype_pmatch |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I choose "Edit question" action for "Missing dictionary pattern match question" in the question bank
    When I expand all fieldsets
    Then I should see "Spell checking"
    And I should see "Vietnamese (Warning! Dictionary not installed on this server)"

  Scenario: Question author/administrator will see warning when preview/attempt an Pmatch question with missing dictionary
    Given I check the "enchant" spell checking library already installed
    And the following "questions" exist:
      | questioncategory | qtype  | name                                      | template | applydictionarycheck |
      | Test questions   | pmatch | Missing dictionary pattern match question | listen   | vi                   |
    And the following config values are set as admin:
      | config       | value   | plugin       |
      | spellchecker | enchant | qtype_pmatch |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    When I choose "Preview" action for "Missing dictionary pattern match question" in the question bank
    When I switch to "questionpreview" window
    Then I should see "This question is set to use Vietnamese spell-check, but that language is not available on this server."

  Scenario: Spell check work normally
    Given I check the "enchant" spell checking library already installed
    And the following "questions" exist:
      | questioncategory | qtype  | name                                       | template | applydictionarycheck |
      | Test questions   | pmatch | English Spell Check pattern match question | listen   | en_GB                |
    And the following config values are set as admin:
      | config       | value   | plugin       |
      | spellchecker | enchant | qtype_pmatch |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    When I choose "Preview" action for "English Spell Check pattern match question" in the question bank
    And I switch to "questionpreview" window
    And I set the field "Answer" to "Bonjour"
    When I click on "Save" "button"
    Then I should see "The following words are not in our dictionary: bonjour. Please correct your spelling."

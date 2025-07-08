# Change log for the pattern-match question type


## Changes in 3.3

* This version is compatible with Moodle 5.0.
* Added new validations when uploading responses in the question testing tool.
* Improved validations on the question editing form.
* Fixed the question testing tool to correctly process uploaded files when the expectedfraction field is missing.
* Fixed display issues in the question testing tool for grade summaries and test responses.
* The Overall grading accuracy text on the edit form is now shown only when responses exist.
* Improved the edit form: the first answer box is now prefilled with match () to help the author.
* Renamed the 'Answer' field on the edit form to 'Answer must match'.
* Fixed an issue where images inserted in the Feedback section were not displayed on the edit form.
* New feature: Added a new field to pre-fill the answer box for students, so this question type can be used for
  grammar/spelling correction tasks without requiring students to retype the whole sentence.
* Added an option in the edit form to enable or disable the Spellchecks.
* Supports both OpenSpout and Spout libraries for importing/exporting sample responses.
* Fixed an issue where leading space characters in the responses were skipped during conversion.
* Added a new setting to treat curly and straight punctuation marks as interchangeable (default = on).
* Defined excluded hash fields and implemented conversion of legacy backup data
  to align with new question data format (per MDL-83541).
* Fixed to codechecker and PHPDoc issues.
* Fixed automated tests.


## Changes in 3.2

* Upgrade to work with Moodle 4.0 and 3.11. In particular, the question testing tool needed to
  be upgraded to work with Moodle 4.0 question versioning 
* Fix the question testing tool, so it can handle responses including &lt;sup> and &lt;sub> tags.
* Improve the UI for the options form at the top of the question testing tool.
* Improve other controls the question testing tool, e.g. the select all/none behaviour.
* When creating new questions, for some key settings, the option used for the next
  question you create will be the same as the options you just used.
* Improve styling of the edit question form.
* Fix compatibility with PHP 8


## Changes in 3.1

* Tweak layout in Moodle 3.9+
* Correct validation of the model answer.
* Fix for a bug that only happens with the quiz_answersheets plugin.


## Changes in 3.0

* Fix spell-checking of proper nouns.
* Updated so that the question testing tool works with Moodle 3.8.

## Changes in 2.9

* Improved styling in Bootstrap-based themes.


## Changes in 2.8

* In Moodle 3.8, there is now a link straight to the Test question tool from the question bank.
* Change hard-coded strings on the editing for to language strings, so they can be translated.
* Fix compatibility with PHP 7.0, which Moodle 3.5 still supports.
* Fix Behat tests to pass with Moodle 3.8.


## Changes in 2.7

* Ability for the question author to input a 'Model answer' for the question.
* Fixes to the punctuation handling.


## Changes in 2.6

* Fix a bug where upgrading from the previous version got stuck on the upgradesettings.php screen.


## Changes in 2.5

* Partial support for this question type in the Moodle mobile app. (Questions work if they don't use the superscript/subscript editor.)
* Many enhancements to adding / editing / removing sample responses, including improved validation.
* Sample responses can now be exported and imported in a range of formats, not just CSV.
* Sample responses are now exported and imported when the question is.
* The sample responses tool can now be used for pattern-match questions used within [https://moodle.org/plugins/qtype_combined](combined questions).
* The sample responses tool now works when questions are being created in a [https://moodle.org/plugins/mod_studentquiz](StudentQuiz) activity.
* The display of information about how well each matching rule is performing is now much clearer.
* When matching number, very small numbers (less than 1.e-6) are now matched better.
* The spell-check language can now be set for each question.
* Spell checking now works in the presence of punctuation. Previously you would get messages like "Hello," is not in the dictionary.
* Improvements to the ability to match punctuation characters. Useful for assessing grammar. This is quite new. There may be more issues to fix in this area.
* A few other bug fixes or code improvements.


## Changes in 2.4

* New button on the question testing screen to add a new sample response.
* Fix Behat tests to pass in Moodle 3.6.
* Some coding style fixes.


## Changes in 2.3

* Privacy API implementation.
* Fix some coding style.
* Due to privacy API support, this version now only works in Moodle 3.4+
  For older Moodles, you will need to use a previous version of this plugin.


## 2.2 and before

Changes were not documented.

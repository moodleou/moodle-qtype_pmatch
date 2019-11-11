# Change log for the pattern-match question type

## Changes in 2.9

* Improved styling in Bootstrap-based themes.


## Changes in 2.8

* In Moodle 3.8, there is now a link stright to the Test question tool from the question bank.
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

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * This file contains tests of the student response parsing in the pmatch library.
 *
 * @package   qtype_pmatch
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_pmatch\local\spell\qtype_pmatch_spell_checker;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/pmatch/tests/helper.php');
require_once($CFG->dirroot . '/question/type/pmatch/pmatchlib.php');

/**
 * Tests of the student response parsing in the pmatch library.
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     qtype_pmatch
 */
class qtype_pmatch_parse_string_test extends basic_testcase {
    public function test_pmatch_parse_string() {
        $options = new pmatch_options();

        $parsedstring = new pmatch_parsed_string('abc.def', $options);
        $this->assertEquals($parsedstring->get_words(), array('abc.', 'def'));

        $parsedstring = new pmatch_parsed_string('abc def', $options);
        $this->assertEquals($parsedstring->get_words(), array('abc', 'def'));

        $parsedstring = new pmatch_parsed_string('abc<sup>3</sup>', $options);
        $this->assertEquals($parsedstring->get_words(), array('abc<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('123<sup>3</sup>', $options);
        $this->assertEquals($parsedstring->get_words(), array('123<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('123<sup>3</sup>?456<sup>3</sup>', $options);
        $this->assertEquals($parsedstring->get_words(),
                                                array('123<sup>3</sup>?', '456<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('123<sup>3</sup>!456<sup>3</sup>', $options);
        $this->assertEquals($parsedstring->get_words(),
                                                array('123<sup>3</sup>!', '456<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('1.23', $options);
        $this->assertEquals($parsedstring->get_words(), array('1.23'));

        $parsedstring = new pmatch_parsed_string('1.23e-10', $options);
        $this->assertEquals($parsedstring->get_words(), array('1.23e-10'));

        $parsedstring = new pmatch_parsed_string('1.23x10<sup>3</sup>', $options);
        $this->assertEquals($parsedstring->get_words(), array('1.23x10<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('123<sup>3</sup>', $options);
        $this->assertEquals($parsedstring->get_words(), array('123<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('cat. dog', $options);
        $this->assertEquals($parsedstring->get_words(), array('cat.', 'dog'));

        $parsedstring = new pmatch_parsed_string('cat? dog', $options);
        $this->assertEquals($parsedstring->get_words(), array('cat?', 'dog'));

        $parsedstring = new pmatch_parsed_string('Test?', $options);
        $this->assertEquals(['Test?'], $parsedstring->get_words());
    }

    public function pmatch_spelling_testcases() {
        return [
            [[], 'e.g. tool'],                         // Default extra dictionary word & normal word.
            [[], 'e.g.. tool.'],                       // Trailing punctuation is skipped.
            [[], 'e.g., tool!!'],
            [['queenking'], 'queenking'],              // Not a word.
            [['awerawefaw'], 'awerawefaw awerawefaw'], // Wrong words only reported once.
            [['awerawefaw'], 'awerawefaw, test'],      // Not a word. Punctuation stripped.
            [[], 'e.g. tool. queek queek abcde fghij', // Synonyms automatically OK.
                    pmatch_options::make(['synonyms' => ['queek' => 'abcde|fghij']])],
            [[], 'queeking',                           // Synonyms may include * wild card.
                    pmatch_options::make(['synonyms' => ['queek*' => 'abcde|fghij']])],
            [['queenking'], 'queenking',
                    pmatch_options::make(['synonyms' => ['queek*' => 'abcde|fghij']])],
            [[], 'Frog-toad'],                         // Any hyphenated group of real words is fine.
            [[], '"Frog-toad"'],                       // Even if surrounded.
            [['frog"-"toad'], '"Frog"-"toad"'],        // But not if the bits have extra punctuation.
            [[], 'Why, e.g. "Frog" or \'A toad,\' would co-operate?'], // Final combined example.
        ];
    }

    /**
     * @dataProvider pmatch_spelling_testcases
     *
     * @param array $misspelledwords
     * @param $string
     * @param null $options
     */
    public function test_pmatch_spelling(array $misspelledwords, $string, $options = null) {
        if ($options === null) {
            $options = new pmatch_options();
        }

        if (empty($options->lang)) {
            $options->lang = 'en_GB';
        }

        qtype_pmatch_test_helper::skip_test_if_no_spellcheck($this, $options->lang);

        $parsedstring = new pmatch_parsed_string($string, $options);
        $ok = $parsedstring->is_spelled_correctly();

        $this->assertEquals($misspelledwords, $parsedstring->get_spelling_errors());

        if (empty($misspelledwords)) {
            $this->assertTrue($ok);
        } else {
            $this->assertFalse($ok);
        }
    }

    /**
     * Test get_display_name_for_language_code
     *
     * @dataProvider get_display_name_for_language_code_provider
     *
     * @param string $langcode Language code
     * @param string $expectedlangname Expected language name
     * @param string $expecteddisplayname Expected language display name
     */
    public function test_get_display_name_for_language_code($langcode, $expectedlangname, $expecteddisplayname) {
        $language = new stdClass();
        $language->name = qtype_pmatch_spell_checker::get_display_name_for_language_code($langcode);
        $language->code = $langcode;
        $displayname = get_string('apply_spellchecker_select', 'qtype_pmatch', $language);

        $this->assertEquals($expectedlangname, $language->name);
        $this->assertEquals($expecteddisplayname, $displayname);
    }

    /**
     * Test case for test_get_display_name_for_language_code
     *
     * @return array Dataset
     */
    public function get_display_name_for_language_code_provider(): array {
        return [
                ['en_US', 'English', 'English (en_US)'],
                ['en_GB', 'English', 'English (en_GB)'],
                ['en_AG', 'English', 'English (en_AG)'],
                ['es', 'Spanish; Castilian', 'Spanish; Castilian (es)'],
                ['es_AR', 'Spanish; Castilian', 'Spanish; Castilian (es_AR)'],
                ['es_BO', 'Spanish; Castilian', 'Spanish; Castilian (es_BO)'],
                ['fr_BE', 'French', 'French (fr_BE)'],
                ['fr_CA', 'French', 'French (fr_CA)'],
                ['fr_CH', 'French', 'French (fr_CH)']
        ];
    }

    /**
     * Test get_default_spell_check_dictionary
     *
     * @dataProvider get_default_spell_check_dictionary_provider
     *
     * @param string $checklanguage Language code need to check
     * @param array $availablelangs List of available languages
     * @param string $expectedmatch Expected language match
     */
    public function test_get_default_spell_check_dictionary($checklanguage, $availablelangs, $expectedmatch) {
        $matched = qtype_pmatch_spell_checker::get_default_spell_check_dictionary($checklanguage, $availablelangs);
        $this->assertEquals($expectedmatch, $matched);
    }

    /**
     * Test case for test_get_default_spell_check_dictionary
     *
     * @return array Dataset
     */
    public function get_default_spell_check_dictionary_provider(): array {
        return [
            ['en', ['en', 'en_US', 'en_GB'], 'en'],
            ['en', ['en_US', 'en_GB'], 'en_GB'],
            ['fr', ['fr', 'fr_FR'], 'fr'],
            ['fr', ['fr_FR'], 'fr_FR'],
            ['fr', ['fr_FR'], 'fr_FR'],
            ['de', ['de_AT', 'de_CH'], 'de_AT']
        ];
    }
}

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
defined('MOODLE_INTERNAL') || die();
global $CFG;
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
    }
    public function test_pmatch_spelling() {

        if (!function_exists('pspell_new')) {
            $this->markTestSkipped(
                    'pspell not installed on your server. Spell checking will not work.');
        }

        $options = new pmatch_options();
        $options->lang = 'en';
        $options->set_synonyms(array((object)array('word' => 'queek', 'synonyms' => 'abcde|fghij')));

        // For example passes as it is an extra dictionary word
        // tool passes as it is correctly spelt.
        $parsedstring = new pmatch_parsed_string('e.g. tool', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        // Full stop (sentence divider) should pass test.
        $parsedstring = new pmatch_parsed_string('e.g.. tool.', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        // Only allow one full stop (sentence divider).
        $parsedstring = new pmatch_parsed_string('e.g... tool.', $options);
        $this->assertFalse($parsedstring->is_parseable());

        // Anything in synonyms automatically passes.
        $parsedstring = new pmatch_parsed_string('e.g.. tool. queek queek', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        // Anything in synonyms automatically passes.
        $parsedstring = new pmatch_parsed_string('e.g.. tool. abcde fghij.', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        // Synonyms may include * wild card.
        $options = new pmatch_options();
        $options->lang = 'en';
        $options->set_synonyms(
                    array((object)array('word' => 'queek*', 'synonyms' => 'abcde|fghij')));
        $parsedstring = new pmatch_parsed_string('e.g.. tool. queeking.', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        $parsedstring = new pmatch_parsed_string('e.g.. tool. queenking.', $options);
        $this->assertFalse($parsedstring->is_spelt_correctly());
    }
}
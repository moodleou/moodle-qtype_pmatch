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
 * This file contains of the pmatch library using files of examples.
 *
 * @package    qtype
 * @subpackage pmatch
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/question/type/pmatch/pmatchlib.php');


/**
 * Test driver class that tests the pmatch library by loading examples from
 * text files in the examples folder.
 *
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      qtype_pmatch
 */
class qtype_pmatch_examples_test extends basic_testcase {
    /** @var string where to look for examples. */
    protected $examplesdir = 'examples';

    /**
     * Main entry point. Run all the tests in all the example files.
     */
    public function test_examples() {
        foreach ($this->get_examples_list() as $name) {
            $this->run_tests_from($name);
        }
    }

    /**
     * Get a list of all the pairs of example files <name>.rules.txt and
     * <name>.responses.csv in the examples folder.
     * @return array of full path names .../examples/<name>. The extension needs
     * to be added back to get the actual files.
     */
    protected function get_examples_list() {
        $examplespath = dirname(__FILE__) . '/' . $this->examplesdir;
        $files = glob($examplespath . '/*.rules.txt');
        if (!$files) {
            return array();
        }

        $examples = array();
        foreach ($files as $path) {
            $example = preg_replace('/\.rules\.txt$/', '', $path);
            if (is_readable($example . '.rules.txt') && is_readable($example . '.responses.csv')) {
                $examples[] = $example;
            }
        }

        return $examples;
    }

    /**
     * Run all the tests one one pair of example files.
     * @param string one of the paths returned by {@link get_examples_list()}.
     */
    protected function run_tests_from($name) {
        $expression = new pmatch_expression(file_get_contents($name . '.rules.txt'));
        if (!$expression->is_valid()) {
            $this->fail('Error parsing match rules in ' . $name . '.rules.txt' .
                    '. Error message: ' . $expression->get_parse_error());
            return;
        }

        $handle = fopen($name . '.responses.csv', 'r');
        if (!$handle) {
            $this->fail('Could not open responses file ' . $name . '.responses.csv');
            return;
        }

        $row = -1;
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if ($row == 0 || $data[0]{0} === '#') {
                continue; // Skipping header row or comment
            }

            if (defined('TIME_ALLOWED_PER_UNIT_TEST')) {
                set_time_limit(TIME_ALLOWED_PER_UNIT_TEST);
            }

            if (count($data) < 2 || !is_numeric($data[1])) {
                $this->fail('Skipping bad line in responses file '.
                            '(file ' . $name . '.responses.csv, line ' . ($row+1) . ').');
                continue;
            }
            $options = new pmatch_options();
            switch (count($data)) {
                case 5 :
                    $options->worddividers = $data[4];
                case 4 :
                    $options->sentencedividers = $data[3];
                case 3 :
                    (bool)$options->ignorecase = $data[2];
            }

            $string = new pmatch_parsed_string($data[0], $options);
            $this->assertEquals((bool) trim($data[1]), $expression->matches($string),
                    'File ' . $name . '.responses.csv, line ' . ($row+1) .
                    ' "' . s($data[0]) . '", %s');
        }

        fclose($handle);
    }
}

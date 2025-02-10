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

namespace qtype_pmatch;

defined('MOODLE_INTERNAL') || die();


/**
 * Unit tests for for utility class.
 *
 * @package   qtype_pmatch
 * @copyright 2025 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qtype_pmatch\utils
 */
class utils_test extends \basic_testcase {

    public function test_convert_quote_to_straight_quote(): void {
        $array = [
            'arrayelement' => ['hasrecursion' => '‘ single smart quote ’ and “ double smart quote ”'],
            'test' => '&lsquo; HTML entities single quote &rsquo; and &ldquo; HTML entities double quote &rdquo;',
        ];
        $result = utils::convert_quote_to_straight_quote($array);
        $this->assertEquals($result['arrayelement']['hasrecursion'], "' single smart quote ' and " . '" double smart quote "');
        $this->assertEquals($result['test'], "' HTML entities single quote ' and " . '" HTML entities double quote "');

        $object = new \stdClass();
        $object->test = '&lsquo; HTML entities single quote &rsquo; and &ldquo; HTML entities double quote &rdquo;';
        $object->element = new \stdClass();
        $object->element->recursion = '‘ single smart quote ’ and “ double smart quote ”';
        $result = utils::convert_quote_to_straight_quote($object);
        $this->assertEquals($result->test, "' HTML entities single quote ' and " . '" HTML entities double quote "');
        $this->assertEquals($result->element->recursion, "' single smart quote ' and " . '" double smart quote "');
    }
}

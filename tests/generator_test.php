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

/**
 * Pattern match question type data generator tests
 *
 * @package   qtype_pmatch
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qtype_pmatch_generator
 */
final class generator_test extends \advanced_testcase {
    public function test_create(): void {
        global $DB;

        $this->resetAfterTest();
        /** @var \qtype_pmatch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('qtype_pmatch');

        $count = $DB->count_records('qtype_pmatch_test_responses');
        $response = $generator->create_test_response();
        $this->assertEquals($count + 1, $DB->count_records('qtype_pmatch_test_responses'));
        $this->assertTrue(is_null($response->expectedfraction),
                'Generator should create a default response with a null entry for expectedfraction');
        $this->assertTrue(is_null($response->gradedfraction),
                'Generator should create a default response with a null entry for gradedfraction');

        $response->expectedfraction = 1;
        $response->gradedfraction = 0;
        $response1 = $generator->create_test_response($response);
        $this->assertEquals(1, $response1->expectedfraction,
                'Generator should use response data for expectedfraction');
        $this->assertEquals(0, $response->gradedfraction,
                'Generator should use response data for gradedfraction');

        for ($x = 0; $x < 10; $x ++) {
            $generator->create_test_response();
        }

        $this->assertEquals($count + 12, $DB->count_records('qtype_pmatch_test_responses'));

    }
}

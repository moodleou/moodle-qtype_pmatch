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
 * Privacy API tests for qtype_pmatch
 *
 * @package qtype_pmatch
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch\tests;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\writer;

class privacy_provider_test extends \core_privacy\tests\provider_testcase {

    public function test_export_user_preferences() {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        set_user_preferences([
                'qtype_pmatch_testquestion_pagesize' => 5
        ], $user1->id);
        \qtype_pmatch\privacy\provider::export_user_preferences($user1->id);
        $writer = writer::with_context(\context_system::instance());
        $export = (object) [
            'qtype_pmatch_testquestion_pagesize' => (object) [
                'value' => 5,
                'description' => get_string('privacy:metadata:preference:pagesize', 'qtype_pmatch')
            ]
        ];
        $this->assertEquals($export, $writer->get_user_preferences('qtype_pmatch'));

        // Test that another user with no user preferences doesn't get any data exported.
        writer::reset();
        \qtype_pmatch\privacy\provider::export_user_preferences($user2->id);
        $writer = writer::with_context(\context_system::instance());
        $export = (object) [];
        $this->assertEquals($export, $writer->get_user_preferences('qtype_pmatch'));
    }
}

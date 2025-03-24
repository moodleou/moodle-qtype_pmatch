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

global $CFG;

require_once($CFG->dirroot . '/lib/external/externallib.php');

use external_api;
use core_external;
use context_module;
use moodle_exception;

/**
 * Tests of the external API for the pmatch question type.
 *
 * @package   qtype_pmatch
 * @copyright 2025 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qtype_pmatch\external
 */
class external_test extends \advanced_testcase {
    /**
     * Test the qtype_pmatch_inplace_editable function.
     * @runInSeparateProcess
     */
    public function test_pmatch_inplace_editable() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $coregenerator = $this->getDataGenerator();
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $coregenerator->create_course();
        $quiz = $coregenerator->create_module('quiz', ['course' => $course->id]);
        $quizcontext = context_module::instance($quiz->cmid);
        $cat = $questiongenerator->create_question_category(['contextid' => $quizcontext->id]);

        $question = $questiongenerator->create_question('pmatch', 'listen', ['category' => $cat->id]);
        $generator = $this->getDataGenerator()->get_plugin_generator('qtype_pmatch');

        // Create a response.
        $response = $generator->create_test_response(null, $question);
        $responsevalue = array_values([$response]);

        // Test the response is updated with blank value.
        try {
            core_external::update_inplace_editable('qtype_pmatch', 'responsetable', $responsevalue[0]->id, '');
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertEquals(get_string('error:blank', 'qtype_pmatch'), $e->getMessage());
        }

        // Test the response is updated with valid value.
        $res = core_external::update_inplace_editable('qtype_pmatch', 'responsetable', $responsevalue[0]->id,
            'updated response value');
        $res = external_api::clean_returnvalue(core_external::update_inplace_editable_returns(), $res);
        $this->assertEquals('updated response value', $res['displayvalue']);
    }
}

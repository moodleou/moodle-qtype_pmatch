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

use qtype_pmatch\privacy\provider;
use core_privacy\local\request\writer;

global $CFG;
require_once($CFG->dirroot . '/question/type/pmatch/classes/privacy/provider.php');

/**
 * Privacy provider tests class.
 *
 * @package    qtype_pmatch
 * @copyright  2021 The Open university
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qtype_pmatch\privacy\provider
 */
final class privacy_provider_test extends \core_privacy\tests\provider_testcase {
    // Include the privacy helper which has assertions on it.

    public function test_get_metadata(): void {
        $collection = new \core_privacy\local\metadata\collection('qtype_pmatch');
        $actual = provider::get_metadata($collection);
        $this->assertEquals($collection, $actual);
    }

    public function test_export_user_preferences_no_pref(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test the export_user_preferences given different inputs.
     *
     * @dataProvider user_preference_provider
     *
     * @param string $name The name of the user preference to get/set
     * @param string $value The value stored in the database
     * @param string $expected The expected transformed value
     */
    public function test_export_user_preferences(string $name, string $value, string $expected): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        set_user_preference("qtype_pmatch_$name", $value, $user);
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $this->assertTrue($writer->has_any_data());
        $preferences = $writer->get_user_preferences('qtype_pmatch');
        foreach ($preferences as $key => $pref) {
            $preference = get_user_preferences("qtype_pmatch_$key", null, $user->id);
            if ($preference === null) {
                continue;
            }
            $desc = get_string("privacy:preference:$key", 'qtype_pmatch');
            $this->assertEquals($expected, $pref->value);
            $this->assertEquals($desc, $pref->description);
        }
    }

    /**
     * Create an array of valid user preferences for the pattern match question type.
     *
     * @return array Array of valid user preferences.
     */
    public static function user_preference_provider(): array {
        return [
                'testquestion_pagesize2' => ['testquestion_pagesize', '5', '5'],
                'default mark 2' => ['defaultmark', '2', '2'],
                'penalty 33.33333%' => ['penalty', '0.3333333', '33.33333%'],
                'use case yes' => ['usecase', '1', get_string('caseyes', 'qtype_pmatch')],
                'use case no' => ['usecase', '0', get_string('caseno', 'qtype_pmatch')],
                'allowsubscript yes' => ['allowsubscript', '1', 'Yes'],
                'allowsubscript no' => ['allowsubscript', '0', 'No'],
                'allowsuperscript yes' => ['allowsuperscript', '1', 'Yes'],
                'allowsuperscript no' => ['allowsuperscript', '0', 'No'],
                'forcelength yes' => ['forcelength', '1', get_string('forcelengthyes', 'qtype_pmatch')],
                'forcelength no' => ['forcelength', '0', get_string('forcelengthno', 'qtype_pmatch')],
                'applydictionarycheck yes' => ['applydictionarycheck', get_string('iso6391', 'langconfig'), 'en'],
                'sentencedividers' => ['sentencedividers', '?.', '?.'],
                'converttospace' => ['converttospace', ';:', ';:'],
        ];
    }
}

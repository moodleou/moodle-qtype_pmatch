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
 * Privacy provider for qtype_pmatch
 *
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package qtype_pmatch
 */

namespace qtype_pmatch\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

defined('MOODLE_INTERNAL') || die();
/**
 * Privacy Subsystem for qtype_pmatch implementing user_preference_provider.
 *
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This component has data.
        // We need to return default options that have been set a user preferences.
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\user_preference_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference('qtype_pmatch_testquestion_pagesize', 'privacy:preference:testquestion_pagesize');
        $collection->add_user_preference('qtype_pmatch_defaultmark', 'privacy:preference:defaultmark');
        $collection->add_user_preference('qtype_pmatch_penalty', 'privacy:preference:penalty');
        $collection->add_user_preference('qtype_pmatch_usecase', 'privacy:preference:usecase');
        $collection->add_user_preference('qtype_pmatch_allowsubscript', 'privacy:preference:allowsubscript');
        $collection->add_user_preference('qtype_pmatch_allowsuperscript', 'privacy:preference:allowsuperscript');
        $collection->add_user_preference('qtype_pmatch_applydictionarycheck', 'privacy:preference:applydictionarycheck');
        $collection->add_user_preference('qtype_pmatch_sentencedividers', 'privacy:preference:sentencedividers');
        $collection->add_user_preference('qtype_pmatch_converttospace', 'privacy:preference:converttospace');
        return $collection;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $pagesize = get_user_preferences('qtype_pmatch_testquestion_pagesize', null, $userid);

        if (null !== ($pagesize)) {
            $desc = get_string('privacy:preference:testquestion_pagesize', 'qtype_pmatch');
            writer::export_user_preference('qtype_pmatch', 'qtype_pmatch_testquestion_pagesize', $pagesize, $desc);
        }

        $preference = get_user_preferences('qtype_pmatch_defaultmark', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:defaultmark', 'qtype_pmatch');
            writer::export_user_preference('qtype_pmatch', 'defaultmark', $preference, $desc);
        }

        $preference = get_user_preferences('qtype_pmatch_penalty', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:penalty', 'qtype_pmatch');
            writer::export_user_preference('qtype_pmatch', 'penalty', transform::percentage($preference), $desc);
        }

        $preference = get_user_preferences('qtype_pmatch_usecase', null, $userid);
        if (null !== $preference) {
            if ($preference) {
                $stringvalue = get_string('caseyes', 'qtype_pmatch');
            } else {
                $stringvalue = get_string('caseno', 'qtype_pmatch');
            }
            $desc = get_string('privacy:preference:usecase', 'qtype_pmatch');
            writer::export_user_preference('qtype_pmatch', 'usecase', $stringvalue, $desc);
        }

        $preferences = [
                'allowsubscript',
                'allowsuperscript'
        ];
        foreach ($preferences as $key) {
            $preference = get_user_preferences("qtype_pmatch_{$key}", null, $userid);
            if (null !== $preference) {
                $desc = get_string("privacy:preference:{$key}", 'qtype_pmatch');
                writer::export_user_preference('qtype_pmatch', $key, transform::yesno($preference), $desc);
            }
        }

        $preference = get_user_preferences('qtype_pmatch_forcelength', null, $userid);
        if (null !== $preference) {
            if ($preference) {
                $stringvalue = get_string('forcelengthyes', 'qtype_pmatch');
            } else {
                $stringvalue = get_string('forcelengthno', 'qtype_pmatch');
            }
            $desc = get_string('privacy:preference:forcelength', 'qtype_pmatch');
            writer::export_user_preference('qtype_pmatch', 'forcelength', $stringvalue, $desc);
        }

        $preference = get_user_preferences('qtype_pmatch_applydictionarycheck', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:applydictionarycheck', 'qtype_pmatch');
            writer::export_user_preference('qtype_pmatch', 'applydictionarycheck', $preference, $desc);
        }
        $preference = get_user_preferences('qtype_pmatch_sentencedividers', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:sentencedividers', 'qtype_pmatch');
            writer::export_user_preference('qtype_pmatch', 'sentencedividers', $preference, $desc);
        }

        $preference = get_user_preferences('qtype_pmatch_converttospace', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:converttospace', 'qtype_pmatch');
            writer::export_user_preference('qtype_pmatch', 'converttospace', $preference, $desc);
        }
    }
}

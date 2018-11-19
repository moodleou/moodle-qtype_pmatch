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

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * External dashboard provider for user preferences
 *
 * @package qtype_pmatch
 */
class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\user_preference_provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_user_preference('qtype_pmatch_testquestion_pagesize', 'privacy:metadata:preference:pagesize');
        return $collection;
    }

    public static function export_user_preferences(int $userid) {
        $pagesize = get_user_preferences('qtype_pmatch_testquestion_pagesize', null, $userid);

        if (!is_null($pagesize)) {
            writer::export_user_preference('qtype_pmatch', 'qtype_pmatch_testquestion_pagesize',
                    $pagesize, get_string('privacy:metadata:preference:pagesize', 'qtype_pmatch'));
        }
    }
}

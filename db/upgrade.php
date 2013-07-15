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
 * Pattern-match question type upgrade code.
 *
 * @package   qtype_pmatch
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the Pattern-match question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_pmatch_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013021201) {

        require_once($CFG->dirroot . '/question/type/pmatch/spellinglib.php');
        $backends = qtype_pmatch_spell_checker::get_installed_backends();
        end($backends);
        set_config('spellchecker', key($backends), 'qtype_pmatch');

        upgrade_plugin_savepoint(true, 2013021201, 'qtype', 'pmatch');
    }

    return true;
}

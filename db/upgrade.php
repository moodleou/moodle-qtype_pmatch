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

    if ($oldversion < 2015101300) {
        // Define table qtype_pmatch_test_responses to be created.
        $table = new xmldb_table('qtype_pmatch_test_responses');

        // Adding fields to table qtype_pmatch_test_responses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('expectedfraction', XMLDB_TYPE_NUMBER, '12, 7', null, null, null, '0');
        $table->add_field('gradedfraction', XMLDB_TYPE_NUMBER, '12, 7', null, null, null, null);

        // Adding keys to table qtype_pmatch_test_responses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

        // Conditionally launch create table for qtype_pmatch_test_responses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Question savepoint reached.
        upgrade_plugin_savepoint(true, 2015101300, 'qtype', 'pmatch');
    }

    if ($oldversion < 2016010400) {
        // Define table qtype_pmatch_rule_matches to be created.
        $table = new xmldb_table('qtype_pmatch_rule_matches');

        // Adding fields to table qtype_pmatch_rule_matches.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('answerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('testresponseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table qtype_pmatch_rule_matches.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('answerid', XMLDB_KEY_FOREIGN, array('answerid'), 'question_answers', array('id'));
        $table->add_key('testresponseid', XMLDB_KEY_FOREIGN, array('testresponseid'), 'qtype_pmatch_test_responses', array('id'));

        // Conditionally launch create table for qtype_pmatch_rule_matches.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Question savepoint reached.
        upgrade_plugin_savepoint(true, 2016010400, 'qtype', 'pmatch');
    }

    if ($oldversion < 2016012600) {

            // Define field questionid to be added to qtype_pmatch_rule_matches.
        $table = new xmldb_table('qtype_pmatch_rule_matches');
        $field = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'testresponseid');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

        // Conditionally launch add field questionid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            // Launch add key questionid.
            $dbman->add_key($table, $key);
        }

        // Pmatch savepoint reached.
        upgrade_plugin_savepoint(true, 2016012600, 'qtype', 'pmatch');
    }

    if ($oldversion < 2016020500) {
        // Correct not null fields.
        $table = new xmldb_table('qtype_pmatch_test_responses');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('response', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $dbman->change_field_notnull($table, $field);
            $field = new xmldb_field('expectedfraction', XMLDB_TYPE_NUMBER, '12, 7', null, null, null, '0');
            $dbman->change_field_notnull($table, $field);
        }
        // Question savepoint reached.
        upgrade_plugin_savepoint(true, 2016020500, 'qtype', 'pmatch');
    }

    return true;
}

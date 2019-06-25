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
 * Admin settings for the pmatch question type.
 *
 * @package   qtype_pmatch
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/pmatch/classes/admin/admin_setting_spell_checker.php');
require_once($CFG->dirroot . '/question/type/pmatch/classes/admin/admin_setting_environment_check.php');
require_once($CFG->dirroot . '/question/type/pmatch/classes/admin/admin_setting_spell_check_languages.php');

$settings->add(new \qtype_pmatch\admin\qtype_pmatch_admin_setting_spell_checker('qtype_pmatch/spellchecker',
        get_string('spellcheckertype', 'qtype_pmatch'),
        get_string('spellcheckertype_desc', 'qtype_pmatch'), null, null));

$settings->add(new \qtype_pmatch\admin\qtype_pmatch_admin_setting_environment_check('qtype_pmatch_environment_check',
        get_string('environmentcheck', 'qtype_pmatch'), null));

$settings->add(new admin_setting_configtext('qtype_pmatch/amatiwsurl',
        get_string('amatiwsurl', 'qtype_pmatch'),
        get_string('amatiwsurl_desc', 'qtype_pmatch'), '', PARAM_URL));

$settings->add(new admin_setting_configtext('qtype_pmatch/minresponses',
        get_string('minresponses', 'qtype_pmatch'),
        get_string('minresponses_desc', 'qtype_pmatch'), 10, PARAM_INT));

$settings->add(new \qtype_pmatch\admin\qtype_pmatch_admin_setting_spell_check_languages('qtype_pmatch/spellcheck_languages',
        get_string('setting_installed_spell_check_dictionaries', 'qtype_pmatch'),
        get_string('setting_installed_spell_check_dictionaries_des', 'qtype_pmatch'), null, null));

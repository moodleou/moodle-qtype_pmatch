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
 * This page lets admins check the environment requirements for this question type.
 *
 * @package    qtype
 * @subpackage pmatch
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Check the user is logged in.
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/question:config', $context);

admin_externalpage_setup('qtypepmatchenvironmentcheck');

// Header.
echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('environmentcheck', 'qtype_pmatch'), '', 'qtype_pmatch');

$erroroutput = '';
if (!class_exists('Normalizer')) {
    echo html_writer::tag('p', get_string('env_peclnormalisationmissing', 'qtype_pmatch'));
} else {
    echo html_writer::tag('p', get_string('env_peclnormalisationok', 'qtype_pmatch'));
}
if (!function_exists('pspell_new')) {
    echo html_writer::tag('p', get_string('env_pspellmissing', 'qtype_pmatch'));
} else {
    echo html_writer::tag('p', get_string('env_pspellok', 'qtype_pmatch'));
    $listofinstalledlangs = '';
    foreach (get_string_manager()->get_list_of_translations() as $lang => $humanfriendlylang) {
        $langidparts = explode('_', $lang);
        $a = new stdClass();
        $a->lang = $lang;
        $a->humanfriendlylang = $humanfriendlylang;
        $a->langforspellchecker = $langidparts[0];
        if (pspell_new($a->langforspellchecker)) {
            $listofinstalledlangs .= html_writer::tag('li', get_string('env_dictok', 'qtype_pmatch', $a));
        } else {
            $listofinstalledlangs .= html_writer::tag('li', get_string('env_dictmissing', 'qtype_pmatch', $a));
        }
    }
    if ($listofinstalledlangs) {
        echo html_writer::tag('ul', $listofinstalledlangs);
    }
}

// Footer.
echo $OUTPUT->footer();

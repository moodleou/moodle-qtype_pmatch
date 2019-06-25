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
 * Admin settings class for chosing a spell-checker back-end.
 *
 * @package   qtype_pmatch
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch\admin;

use qtype_pmatch\local\spell\qtype_pmatch_null_spell_checker;
use qtype_pmatch\local\spell\qtype_pmatch_spell_checker;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin settings class for chosing a spell-checker back-end.
 *
 * @package   qtype_pmatch
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_admin_setting_environment_check extends \admin_setting_heading {

    /**
     * Returns an HTML string
     *
     * @return string Returns an HTML string
     */
    public function output_html($data, $query = '') {
        $results = [];

        if (class_exists('Normalizer')) {
            $results[] = get_string('env_peclnormalisationok', 'qtype_pmatch');
        } else {
            $results[] = get_string('env_peclnormalisationmissing', 'qtype_pmatch');
        }

        $spellchecker = qtype_pmatch_spell_checker::make();
        $results[] = $spellchecker->get_name();

        if (!$spellchecker instanceof qtype_pmatch_null_spell_checker) {
            $stringmanager = get_string_manager();
            $availablelangs = qtype_pmatch_spell_checker::get_available_languages();
            foreach (get_string_manager()->get_list_of_translations() as $lang => $humanfriendlylang) {
                $a = new stdClass();
                $a->lang = $lang;
                $a->humanfriendlylang = $humanfriendlylang;
                $langcode = $stringmanager->get_string('iso6391', 'langconfig', null, $lang);
                $a->langforspellchecker = qtype_pmatch_spell_checker::get_default_spell_check_dictionary(
                        $langcode, $availablelangs);
                if ($a->langforspellchecker &&
                        !(qtype_pmatch_spell_checker::make($a->langforspellchecker) instanceof qtype_pmatch_null_spell_checker)) {
                    $results[] = get_string('env_dictok', 'qtype_pmatch', $a);
                } else {
                    $results[] = get_string('env_dictmissing', 'qtype_pmatch', $a);
                }
            }
        }

        $this->description = implode("\n\n", $results);

        return parent::output_html($data, $query);
    }

}

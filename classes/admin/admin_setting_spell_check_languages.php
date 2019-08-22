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
 * Admin settings class for generate spell-checker dictionaries checkbox.
 *
 * @package   qtype_pmatch
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch\admin;

use qtype_pmatch\local\spell\qtype_pmatch_spell_checker;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin settings class for generate spell-checker dictionaries checkbox.
 *
 * @package   qtype_pmatch
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class qtype_pmatch_admin_setting_spell_check_languages extends \admin_setting_configmulticheckbox {

    /**
     * This function may be used in ancestors for lazy loading of choices
     *
     * Override this method if loading of choices is expensive, such
     * as when it requires multiple db requests.
     *
     * @return bool true if loaded, false if error
     */
    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }

        $this->choices = [];
        $availablelangs = qtype_pmatch_spell_checker::get_available_languages();
        foreach ($availablelangs as $availablelang) {
            $language = new \stdClass();
            $language->name = qtype_pmatch_spell_checker::get_display_name_for_language_code($availablelang);
            $language->code = $availablelang;
            $this->choices[$availablelang] = get_string('apply_spellchecker_select', 'qtype_pmatch', $language);
        }
        ksort($this->choices);

        if (empty($this->choices)) {
            $this->choices[''] = get_string('nolanguagesfound', 'qtype_pmatch');
        }

        return true;
    }

}

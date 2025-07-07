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

namespace qtype_pmatch\local\spell;

/**
 * Implements the {@link qtype_pmatch_spell_checker} API using pspell.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_pspell_spell_checker extends qtype_pmatch_spell_checker {

    /** @var int the pspell link handle. */
    protected $pspell;

    /**
     * Constructor for the pspell spell checker.
     *
     * @param string $lang The language code for the spell checker.
     */
    public function __construct($lang) {
        parent::__construct($lang);
        $this->pspell = pspell_new($lang);
    }

    #[\Override]
    public function is_initialised() {
        return (bool) $this->pspell;
    }

    #[\Override]
    public function is_in_dictionary($word) {
        return pspell_check($this->pspell, $word);
    }

    #[\Override]
    public static function get_name() {
        return get_string('spellcheckerpspell', 'qtype_pmatch');
    }

    #[\Override]
    public static function is_available() {
        return function_exists('pspell_new');
    }

    #[\Override]
    public static function available_languages(): array {
        $installeddicts = explode(PHP_EOL, rtrim(shell_exec('aspell dicts')));
        $availablelanguages = [];
        foreach ($installeddicts as $dict) {
            if (preg_match(qtype_pmatch_spell_checker::LANGUAGE_FILTER_REGEX, $dict, $m)) {
                $availablelanguages[] = $dict;
            }
        }
        return $availablelanguages;
    }

}

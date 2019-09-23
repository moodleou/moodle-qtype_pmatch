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
 * This file contains an API for accessing spell-checking back-ends.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch\local\spell;

defined('MOODLE_INTERNAL') || die();

/**
 * Object that provides spell-checking, to make it easy to support different back-ends.
 *
 * Which back-end to use is controlled by get_config('qtype_pmatch', 'spellchecker').
 * Create an instance of this class using
 * $spellchecker = qtype_pmatch_spell_checker::make(); then test
 * words using $spellchecker->is_in_dictionary($word);
 */
abstract class qtype_pmatch_spell_checker {

    /**
     * @var array lang code => qtype_pmatch_spell_checker, so we only load each dictionary once.
     * We were experiencing incomprehensible errors if we loaded the same dictionaries
     * repeatedly (it just died on enchant_broker_request_dict with no error message).
     * Using this cache avoids that.
     */
    protected static $checkers = array();

    /** @var string Value in the database define that do not check the spelling. */
    const DO_NOT_CHECK_OPTION = '-';

    /** @var string Value in the database define that the server do not use any spell check library. */
    const NULL_SPELL_CHECK = 'null';

    /** @var string Regex string to get only needed dictionaries with format: xx or xx_YY Example: en or en_US. */
    const LANGUAGE_FILTER_REGEX = '~^([a-z]+)(_[A-Z]+)?~';

    /**
     * Spell-check a word.
     * @param string $word the word to check.
     * @return bool whether the word is in the dictionary.
     */
    public abstract function is_in_dictionary($word);

    /**
     * Factory method create a new spell-checker object for a given language.
     *
     * @param string $lang the language code. If null, defaults to get_string('iso6391', 'langconfig').
     * @return qtype_pmatch_spell_checker the requested object.
     */
    public static function make($lang = null): qtype_pmatch_spell_checker {
        $spellchecker = get_config('qtype_pmatch', 'spellchecker');

        if ($lang === null) {
            $lang = 'en_GB';
        }

        if (isset(self::$checkers[$lang])) {
            return self::$checkers[$lang];
        }

        $backends = self::get_known_backends();
        if (!array_key_exists($spellchecker, $backends)) {
            debugging('Unknown spell checker back end ' . $spellchecker);
            return self::make_null_checker($lang);
        }
        $classname = $backends[$spellchecker];
        if (!$classname::is_available()) {
            debugging('Selected spell checker back end ' . $spellchecker . ' is not available.');
            return self::make_null_checker($lang);
        }

        $checker = new $classname($lang);
        if (!$checker->is_initialised()) {
            debugging('Spell checker back end ' . $spellchecker .
                    ' could not be initialised for language ' . $lang);
            return self::make_null_checker($lang);
        }

        self::$checkers[$lang] = $checker;
        return $checker;
    }

    /**
     * Helper method used by {@link make()} when a real dictionary can't be found.
     * @param string $lang a language code.
     * @return qtype_pmatch_null_spell_checker
     */
    protected static function make_null_checker($lang) {
        self::$checkers[$lang] = new qtype_pmatch_null_spell_checker($lang);
        return self::$checkers[$lang];
    }

    /**
     * @return array a list of all the back-end library classes.
     */
    public static function get_known_backends() {
        return array(
                'null'    => 'qtype_pmatch\local\spell\qtype_pmatch_null_spell_checker',
                'pspell'  => 'qtype_pmatch\local\spell\qtype_pmatch_pspell_spell_checker',
                'enchant' => 'qtype_pmatch\local\spell\qtype_pmatch_enchant_spell_checker',
        );
    }

    /**
     * @return array a list of the back-end library classes that might work on this server.
     */
    public static function get_installed_backends() {
        $backends = self::get_known_backends();
        $installedbackends = array();
        foreach ($backends as $key => $classname) {
            if ($classname::is_available()) {
                $installedbackends[$key] = $classname;
            }
        }
        return $installedbackends;
    }

    /**
     * Return the available language for spell check.
     *
     * @return array List of available languages.
     */
    public static function get_available_languages(): array {
        $spellchecker = get_config('qtype_pmatch', 'spellchecker');
        $backends = self::get_known_backends();
        $classname = $backends[$spellchecker];
        $availablelanguages = $classname::available_languages();

        return $availablelanguages;
    }

    /**
     * Just to document the expected constructor API.
     * @param string $lang the language code.
     */
    protected function __construct($lang) {
    }

    /**
     * This method only exists to support pspell. Pspell cannot tell us if the
     * required dictionary exists until we have tried to create it.
     * @return boolean whether this class was initialised correctly.
     */
    public function is_initialised() {
        return true;
    }

    /**
     * Subclasses must implement this.
     * @return string translated name of this back-end, for use in the UI.
     */
    public static function get_name() {
        return '';
    }

    /**
     * Subclasses must implement this.
     * @return bool whether the necessary libraries are installed for this back-end to work.
     */
    public static function is_available() {
        return false;
    }

    /**
     * Subclasses must implement this.
     *
     * @return array List of available languages.
     */
    public static function available_languages(): array {
        return [];
    }

    /**
     * Return the display name for language code
     *
     * @param string $langcode Language code
     * @return string Display name for given language code
     */
    public static function get_display_name_for_language_code($langcode): string {
        $langname = '';
        $languagenames = get_string_manager()->get_list_of_languages();

        if (preg_match(self::LANGUAGE_FILTER_REGEX, $langcode, $m)) {
            $langname = $languagenames[$m[1]];
        }

        return $langname;
    }

    /**
     * Check and return the suitable dictionary code
     *
     * @param string $checklanguage Language code need to check
     * @param array $availablelangs List of available languages
     * @return string Language code that can be set be default
     */
    public static function get_default_spell_check_dictionary($checklanguage, $availablelangs): string {
        $matchedlang = $checklanguage;
        if (!in_array($checklanguage, $availablelangs)) {
            $matchedlang = '';
            // Default language is not available.
            // We need to looking for xx_XX format. This will work for languages like fr and de.
            $alternatelanguage = $checklanguage . '_' . strtoupper($checklanguage);
            if (in_array($alternatelanguage, $availablelangs)) {
                // The xx_XX format is available.
                $matchedlang = $alternatelanguage;
            } else if ($checklanguage == 'en') {
                // Default language is en. Set to en_GB.
                $matchedlang = 'en_GB';
            } else {
                // Take the first xx_YY found.
                $matches = preg_grep('/^' . $checklanguage . '(\w+)/i', $availablelangs);
                if (!empty($matches)) {
                    $matchedlang = $matches[0];
                }
            }
        }

        return $matchedlang;
    }

    /**
     * Get the installed spell check language on the server.
     *
     * @param object $question Question object
     * @return array with two elements:
     *      array List of available language
     *      bool Disable the select box or not
     */
    public static function get_spell_checker_language_options($question): array {
        $disable = false;
        $options = [];

        $options[self::DO_NOT_CHECK_OPTION] = get_string('apply_spellchecker_label', 'qtype_pmatch');

        $spellchecklanguagesdata = get_config('qtype_pmatch', 'spellcheck_languages');
        if (!$spellchecklanguagesdata ||
                get_config('qtype_pmatch', 'spellchecker') == self::NULL_SPELL_CHECK) {
            $disable = true;
            return [$options, $disable];
        }
        $availablelangs = explode(',', $spellchecklanguagesdata);

        foreach ($availablelangs as $availablelang) {
            $language = new \stdClass();
            $language->name = self::get_display_name_for_language_code($availablelang);
            $language->code = $availablelang;
            $options[$availablelang] = get_string('apply_spellchecker_select', 'qtype_pmatch', $language);
        }

        if (isset($question->options)) {
            $originallanguage = $question->options->applydictionarycheck;
            if ($originallanguage != self::DO_NOT_CHECK_OPTION &&
                    !in_array($originallanguage, $availablelangs)) {
                $missinglangname = self::get_display_name_for_language_code($originallanguage);
                $options[$originallanguage] =
                        get_string('apply_spellchecker_missing_language_select', 'qtype_pmatch', $missinglangname);
            }
        }
        ksort($options);

        return [$options, $disable];
    }

}

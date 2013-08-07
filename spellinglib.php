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
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/adminlib.php');

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

    /**
     * Spell-check a word.
     * @param string $word the word to check.
     * @return bool whether the word is in the dictionary.
     */
    public abstract function is_in_dictionary($word);

    /**
     * Factory method create a new spell-checker object for a given language.
     * @param string $lang the language code. If null, defaults to get_string('iso6391', 'langconfig').
     * @return qtype_pmatch_spell_checker the requested object.
     */
    public static function make($lang = null) {
        $spellchecker = get_config('qtype_pmatch', 'spellchecker');

        if ($lang === null) {
            $lang = get_string('iso6391', 'langconfig');
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
            'null'    => 'qtype_pmatch_null_spell_checker',
            'pspell'  => 'qtype_pmatch_pspell_spell_checker',
            'enchant' => 'qtype_pmatch_enchant_spell_checker',
        );
    }

    /**
     * @return array a list of the back-end library classes that might work on this server.
     */
    public static function get_installed_backends() {
        $backends = qtype_pmatch_spell_checker::get_known_backends();
        $installedbackends = array();
        foreach ($backends as $key => $classname) {
            if ($classname::is_available()) {
                $installedbackends[$key] = $classname;
            }
        }
        return $installedbackends;
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
}


/**
 * Implements the {@core_spell_checker} by saying that that any string is a
 * correctly spelled word. This can be used when there is no back-end installed.
 */
class qtype_pmatch_null_spell_checker extends qtype_pmatch_spell_checker {
    public function is_in_dictionary($word) {
        return true;
    }

    public static function get_name() {
        return get_string('spellcheckernull', 'qtype_pmatch');
    }

    public static function is_available() {
        return true;
    }
}


/**
 * Implements the {@core_spell_checker} API using pspell.
 */
class qtype_pmatch_pspell_spell_checker extends qtype_pmatch_spell_checker {

    /** @var int the pspell link handle. */
    protected $pspell;

    public function __construct($lang) {
        parent::__construct($lang);
        $this->pspell = pspell_new($lang);
    }

    public function is_initialised() {
        return (bool) $this->pspell;
    }

    public function is_in_dictionary($word) {
        return pspell_check($this->pspell, $word);
    }

    public static function get_name() {
        return get_string('spellcheckerpspell', 'qtype_pmatch');
    }

    public static function is_available() {
        return function_exists('pspell_new');
    }
}


/**
 * Implements the {@core_spell_checker} API using enchant.
 */
class qtype_pmatch_enchant_spell_checker extends qtype_pmatch_spell_checker {

    /** @var resource the enchant broker. */
    protected static $broker = null;

    /** @var resource the enchant dictionary. */
    protected $dictionary = null;

    public function __construct($lang) {
        parent::__construct($lang);

        $broker = self::get_broker();
        if (!$broker) {
            throw new coding_exception('Failed to create an enchant broker.');
        }

        $this->dictionary = enchant_broker_request_dict($broker, $lang);
    }

    public function __destruct() {
        if ($this->dictionary) {
            enchant_broker_free_dict($this->dictionary);
        }
    }

    public function is_in_dictionary($word) {
        return enchant_dict_check($this->dictionary, $word);
    }

    public static function get_name() {
        return get_string('spellcheckerenchant', 'qtype_pmatch');
    }

    public static function is_available() {
        if (!function_exists('enchant_broker_init')) {
            return false;
        }

        return (bool) self::get_broker();
    }

    public function is_initialised() {
        return (bool) $this->dictionary;
    }

    /**
     * @return a broker.
     */
    protected static function get_broker() {
        if (self::$broker === null) {
            self::$broker = enchant_broker_init();
        }

        return self::$broker;
    }
}


/**
 * Admin settings class for chosing a spell-checker back-end.
 *
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_admin_setting_spell_checker extends admin_setting_configselect {
    public function load_choices() {

        if (is_array($this->choices)) {
            return true;
        }

        $this->choices = array();
        $backends = qtype_pmatch_spell_checker::get_installed_backends();
        foreach ($backends as $key => $classname) {
            $this->choices[$key] = $classname::get_name();
        }

        return true;
    }
}


/**
 * Admin settings class for chosing a spell-checker back-end.
 *
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_admin_setting_environment_check extends admin_setting_heading {
    public function output_html($data, $query = '') {
        $results = array();

        if (class_exists('Normalizer')) {
            $results[] = get_string('env_peclnormalisationok', 'qtype_pmatch');
        } else {
            $results[] = get_string('env_peclnormalisationmissing', 'qtype_pmatch');
        }

        $spellchecker = qtype_pmatch_spell_checker::make();
        $results[] = $spellchecker->get_name();

        if (!$spellchecker instanceof qtype_pmatch_null_spell_checker) {
                    $stringmanager = get_string_manager();
            foreach (get_string_manager()->get_list_of_translations() as $lang => $humanfriendlylang) {
                $a = new stdClass();
                $a->lang = $lang;
                $a->humanfriendlylang = $humanfriendlylang;
                $a->langforspellchecker = $stringmanager->get_string('iso6391', 'langconfig', null, $lang);
                if (qtype_pmatch_spell_checker::make($a->langforspellchecker) instanceof qtype_pmatch_null_spell_checker) {
                    $results[] = get_string('env_dictmissing', 'qtype_pmatch', $a);
                } else {
                    $results[] = get_string('env_dictok', 'qtype_pmatch', $a);
                }
            }
        }

        $this->description = implode("\n\n", $results);

        return parent::output_html($data, $query);
    }
}

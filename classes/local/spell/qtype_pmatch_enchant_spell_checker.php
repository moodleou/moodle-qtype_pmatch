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
 * Enchant spell checker class.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch\local\spell;

defined('MOODLE_INTERNAL') || die();

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
            throw new \coding_exception('Failed to create an enchant broker.');
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

    /**
     * Get the available languages on server.
     *
     * @return array List of available languages.
     */
    public static function available_languages(): array {
        $broker = self::get_broker();
        $dicts = enchant_broker_list_dicts($broker);
        if (empty($dicts)) {
            return [];
        }
        $availablelanguages = [];
        foreach ($dicts as $dict) {
            if (preg_match(qtype_pmatch_spell_checker::LANGUAGE_FILTER_REGEX, $dict['lang_tag'], $m)) {
                $availablelanguages[] = $dict['lang_tag'];
            }
        }
        return $availablelanguages;
    }
}

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
 * Pattern match form utils.
 *
 * @package qtype_pmatch
 * @copyright 2025 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_pmatch;

class utils {

    /**
     * Convert smart quotes to straight quotes, handling recursion for arrays.
     *
     * @param mixed $input Form input data can be a string / number / array.
     * @return mixed
     */
    public static function convert_quote_to_straight_quote(mixed $input): mixed {
        if (is_array($input)) {
            // If input is an array, process each element recursively.
            foreach ($input as $key => $subvalue) {
                $input[$key] = self::convert_quote_to_straight_quote($subvalue);
            }
        } else if (is_object($input)) {
            foreach ($input as $key => $subvalue) {
                $input->{$key} = self::convert_quote_to_straight_quote($subvalue);
            }
        } else if (is_string($input)) {
            // If input is a string, convert quotes.
            // Replace smart quotes with straight quotes.
            $input = str_replace(
                ['&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '‘', '’', '“', '”'], // HTML entities and smart quotes.
                ["'", "'", '"', '"', "'", "'", '"', '"'],                         // Corresponding straight quotes.
                $input
            );
        }

        return $input;
    }
}

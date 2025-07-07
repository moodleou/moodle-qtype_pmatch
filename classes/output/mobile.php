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

namespace qtype_pmatch\output;

/**
 * Mobile output class for question type pattern match.
 *
 * @package qtype_pmatch
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the mobile view for the pattern match question type.
     *
     * This method provides the HTML and JavaScript required for rendering
     * the pattern match question type in mobile app.
     *
     * @return array An array containing templates and JavaScript for the mobile view.
     */
    public static function pmatch_view() {
        global $CFG;
        // General notes:
        // If you have worked on mobile activities, there is no cmid or courseid in $args here.
        // This is not equivalent to mod/quiz/attempt.php?attempt=57&cmid=147, rather
        // this is just a section of that page, with all the access checking already done for us.
        // The full file path is required even though file_get_contents should work with relative paths.
        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => file_get_contents($CFG->dirroot . '/question/type/pmatch/mobile/pmatch.html'),
                ],
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/question/type/pmatch/mobile/pmatch.js'),
        ];
    }
}

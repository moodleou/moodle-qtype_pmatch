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
 * This file contains code to interpret a pmatch expression.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/pmatch/pmatch/matcher.php');

define('PMATCH_SPECIAL_CHARACTER', '[\(\)\\\\\|\?\*_\[\]]');
// All characters in many Unicode classes, but not the special ones.
define('PMATCH_CHARACTER', '(?:(?!' . PMATCH_SPECIAL_CHARACTER . ')[\pL\pM\pN\pP\pS])');

define('PMATCH_LNUM', '[0-9]+');
define('PMATCH_DNUM', PMATCH_LNUM.'[\.]'.PMATCH_LNUM);
define('PMATCH_HTML_EXPONENT', '[*xX]10<(sup|SUP)>([+-]?'.PMATCH_LNUM.')</(sup|SUP)>');
define('PMATCH_EXPONENT_DNUM', '(('.PMATCH_LNUM.'|'.PMATCH_DNUM.')'.
                            '([eE][+-]?'.PMATCH_LNUM.'|'.PMATCH_HTML_EXPONENT.'))');
define('PMATCH_NUMBER', '((([+|-])?'.PMATCH_EXPONENT_DNUM.')'.
                            '|(([+|-])?'.PMATCH_DNUM.')'.
                            '|(([+|-])?'.PMATCH_LNUM.'))');

/**
 * This file contains code to interpret a pmatch expression.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class pmatch_interpreter_item {
    /**
     * The error message from the last interpretation.
     * @var string
     */
    protected $interpretererrormessage;
    /** @var string */
    public $codefragment;

    /** @var pmatch_options */
    public $pmatchoptions;
    /** @var string */
    protected $pattern;

    /**
     * Constructor for the pmatch_interpreter_item class.
     * @param pmatch_options $pmatchoptions the options to use for this interpreter.
     *                                      If null then a default pmatch_options object is used.
     */
    public function __construct($pmatchoptions = null) {
        if (is_null($pmatchoptions)) {
            $pmatchoptions = new pmatch_options();
        }
        $this->pmatchoptions = $pmatchoptions;
    }

    /**
     * Interpret the $string starting at $start into a code fragment.
     * This is the main method that should be called to interpret a string.
     * It will return an array with two elements:
     * 1. boolean true if the string was interpreted, false otherwise.
     * 2. integer the position in the string after the code fragment.
     *
     * @param string $string a pmatch expression as a string.
     * @param integer $start where to start parsing.
     * @return array
     */
    public function interpret($string, $start = 0) {
        $this->interpretererrormessage = '';
        list($found, $endofmatch) = $this->interpret_contents($string, $start);
        if ($found) {
            $this->codefragment = core_text::substr($string, $start, $endofmatch - $start);
        } else {
            $this->codefragment = '';
        }
        return [$found, $endofmatch];
    }

    /**
     * Convert the $string starting at $start into a tree of object representing parts of pmatch
     * code. This is the default method which is often overriden. It looks for $pattern which is a
     * regex with no modifying options.
     *
     * @param string $string
     * @param integer $start
     */
    protected function interpret_contents($string, $start) {
        // Regex pattern to match one character of pmatch code.
        list($found, $endofpattern, $subpatterns) = $this->find_pattern(
                $this->pattern, $string, $start);
        return [$found, $endofpattern];
    }

    /**
     * Find an anchored case insensitive regular expression, searching from $start.
     *
     * @param string $pattern
     * @param string $string
     * @param integer $start
     * @return array $found boolean is the pattern found,
     *               $endofpattern integer the position of the end of the pattern,
     *               $matches array of matches of sub patterns with offset from $start
     */
    public function find_pattern($pattern, $string, $start) {
        $matches = [];
        preg_match($pattern.'iAu', core_text::substr($string, $start), $matches, PREG_OFFSET_CAPTURE);
        $found = !empty($matches);
        if ($found) {
            $endofpattern = $matches[0][1] + core_text::strlen($matches[0][0]) + $start;
        } else {
            $endofpattern = $start;
        }

        array_shift($matches); // Pop off the matched string and only return sub patterns.
        return [$found, $endofpattern, $matches];
    }

    /**
     * Get the error message from the last interpretation.
     * If there is no error message then an empty string is returned.
     *
     * @return string the error message or empty string if there is no error message
     */
    public function get_error_message() {
        if (!empty($this->interpretererrormessage)) {
            return $this->interpretererrormessage;
        } else {
            return '';
        }
    }

    /**
     * Set the error message for this interpreter object.
     * This is used to set an error message when the interpretation fails.
     *
     * @param string $errormessage the error message to set, without 'ie_' prefix
     * @param string $codefragment the code fragment that caused the error
     */
    public function set_error_message($errormessage, $codefragment) {
        $this->interpretererrormessage =
                                get_string('ie_'.$errormessage, 'qtype_pmatch', $codefragment);
    }

    /**
     * Get the matcher tree for this interpreter object. Can be used from an interpreter object at
     * any point in the tree.
     *
     * @param pmatch_options $externaloptions
     * @return pmatch_matcher_item a tree of child classes of pmatch_matcher_item
     */
    public function get_matcher($externaloptions) {
        $thistypename = $this->get_type_name_of_interpreter_object($this);
        $matchclassname = 'pmatch_matcher_'.$thistypename;
        return new $matchclassname($this, $externaloptions);
    }

    /**
     * Get the type name of the interpreter object.
     * This is used to get the class name of the matcher object that corresponds to this interpreter
     * object.
     *
     * @param object $object
     * @return string the type name of the interpreter object
     */
    public function get_type_name_of_interpreter_object($object) {
        return core_text::substr(get_class($object), 19);
    }

    /**
     * Get the code fragment that was interpreted.
     * This is the code that was matched in the string and interpreted by this interpreter object.
     *
     * @return string the code fragment
     */
    public function get_code_fragment() {
        return $this->codefragment;
    }

    /**
     * Get the formatted expression string for this item.
     * This is the string that represents the pmatch expression for this item.
     * It is indented according to the indent level.
     *
     * @param integer $indentlevel the level of indentation to use
     * @return string the formatted expression string
     */
    public function get_formatted_expression_string($indentlevel = 0) {
        return $this->codefragment;
    }

    /**
     * Get the indent string for the given indent level.
     * This is used to indent the formatted expression string.
     *
     * @param integer $indentlevel the level of indentation to use
     * @return string the indent string
     */
    protected function indent($indentlevel) {
        return str_repeat('    ', $indentlevel);
    }
}

/**
 * This class is used to interpret a pmatch expression that has sub contents.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class pmatch_interpreter_item_with_subcontents extends pmatch_interpreter_item {

    /**
     * @var pmatch_interpreter_item[] The sub contents of this item. This is an array of items that are sub contents of this item.
     */
    protected $subcontents = [];
    /**
     * @var int How many items can be contained as sub contents of this item. If 0 then no limit.
     */
    protected $limitsubcontents = 0;

    /**
     * Interpret sub contents of item.
     *
     * @param string $string code that is to be interpreted
     * @param int $start position at which to start
     * @param array $branchfoundsofar (optional) items found so far, if any
     * @return array (longest possible branch that matches the longest string,
     *                string position after code for these items.)
     */
    protected function interpret_subcontents($string, $start, $branchfoundsofar = []) {
        $typestotry = $this->next_possible_subcontent($branchfoundsofar);
        $branchindex = 0;
        $childbranches = [];
        $childbranchcursor = [];

        // Iterate down all branches.
        foreach ($typestotry as $typetotry) {
            $childbranches[$branchindex] = $branchfoundsofar;
            list($typefound, $found, $childbranchcursor[$branchindex]) =
                    $this->interpret_subcontent_item($typetotry, $string, $start);
            if ($found && ($childbranchcursor[$branchindex] > $start)) {
                $childbranches[$branchindex][] = $typefound;
                if (($this->limitsubcontents == 0) ||
                            (count($childbranches[$branchindex]) < $this->limitsubcontents)) {
                    list($childbranches[$branchindex], $childbranchcursor[$branchindex]) =
                                $this->interpret_subcontents($string,
                                                            $childbranchcursor[$branchindex],
                                                            $childbranches[$branchindex]);
                }
            }
            if ($anyerrormessage = $typefound->get_error_message()) {
                $this->interpretererrormessage = $anyerrormessage;
            }
            $branchindex++;
        }

        // Find the branch that matches the longest string.
        array_multisort($childbranchcursor, SORT_DESC, SORT_NUMERIC, $childbranches);
        return [array_shift($childbranches), array_shift($childbranchcursor)];
    }

    /**
     * What was the last type of sub contents found in $foundsofar
     *
     * @param array $foundsofar
     * @return string the type of sub contents last found
     *                (prefix with 'pmatch_interpreter_' to get classname)
     */
    protected function last_subcontent_type_found($foundsofar) {
        if (!empty($foundsofar)) {
            return $this->get_type_name_of_interpreter_object($foundsofar[count($foundsofar) - 1]);
        } else {
            return '';
        }
    }

    /**
     * In the branch of code matched so far what could be the next type.
     *
     * @param array $foundsofar
     * @return array the types of sub contents that could come next
     *                (prefix with 'pmatch_interpreter_' to get classname)
     */
    protected function next_possible_subcontent($foundsofar) {
        return [];
    }

    /**
     * Try to match $cancontaintype in $string starting at $start.
     *
     * @param string $cancontaintype
     * @param string $string
     * @param integer $start
     */
    protected function interpret_subcontent_item($cancontaintype, $string, $start) {
        $cancontainclassname = 'pmatch_interpreter_'.$cancontaintype;
        $cancontain = new $cancontainclassname($this->pmatchoptions);
        list($found, $aftercontent) = $cancontain->interpret($string, $start);
        if ($found) {
            return [$cancontain, true, $aftercontent];
        } else {
            return [$cancontain, false, $start];
        }
    }

    #[\Override]
    protected function interpret_contents($string, $start) {
        list($this->subcontents, $endofcontents) = $this->interpret_subcontents($string, $start);
        $this->check_subcontents();
        return [(!empty($this->subcontents)), $endofcontents];
    }

    /**
     *
     * Any checks that need to be done on sub contents found, are done here. The default is to check
     * the last content type found and if the type is included in lastcontenttypeerrors report an
     * error.
     */
    protected function check_subcontents() {
        if (array_key_exists($this->last_subcontent_type_found($this->subcontents),
                                                                    $this->lastcontenttypeerrors)) {
            $this->set_error_message(
                $this->lastcontenttypeerrors[$this->last_subcontent_type_found($this->subcontents)],
                $this->codefragment);
        }
    }

    /**
     * @var array The last content type errors that can be reported.
     */
    protected $lastcontenttypeerrors = ['or_character' => 'lastsubcontenttypeorcharacter',
                                 'word_delimiter_space' => 'lastsubcontenttypeworddelimiter',
                                 'word_delimiter_proximity' => 'lastsubcontenttypeworddelimiter'];
    #[\Override]
    public function interpret($string, $start = 0) {
        list($found, $endofmatch) = parent::interpret($string, $start);
        $this->check_subcontents();
        return [$found, $endofmatch];
    }

    /**
     * Get the sub contents of this item.
     * This is used to get the sub contents of this item after it has been interpreted.
     * It returns an array of pmatch_interpreter_item objects that are the sub contents of this item.
     *
     * @return pmatch_interpreter_item[] the sub contents of this item
     */
    public function get_subcontents() {
        return $this->subcontents;
    }

    #[\Override]
    public function get_formatted_expression_string($indentlevel = 0) {
        $string = '';
        foreach ($this->subcontents as $subcontent) {
            $string .= $subcontent->get_formatted_expression_string($indentlevel + 1);
        }
        return $string;
    }
}

/**
 * This class is used to interpret a pmatch expression that has an opening and closing pattern.
 * It is used to interpret items that have sub contents enclosed in brackets or other delimiters.
 * It is used to interpret the not, match_any, match_all and match_options items.
 */
abstract class pmatch_interpreter_item_with_enclosed_subcontents
                    extends pmatch_interpreter_item_with_subcontents {

    /**
     * @var string The patterns used to find the opening and closing of this item.
     */
    protected $openingpattern;

    /**
     * @var string The closing pattern must match the end of the string.
     */
    protected $closingpattern;

    /**
     * The error message to use when the closing pattern is not found.
     * If empty then no error message is set.
     * @var string
     */
    protected $missingclosingpatternerror = '';

    #[\Override]
    protected function interpret_contents($string, $start) {
        $subpatterns = [];
        list($found, $endofopening, $subpatterns) =
                                $this->find_pattern($this->openingpattern, $string, $start);

        if (!$found) {
            return [false, $start];
        }

        if (!empty($subpatterns)) {
            $subpattern = $subpatterns[0][0];
        } else {
            $subpattern = '';
        }
        if (!$this->interpret_subpattern_in_opening($subpattern)) {
            return [false, $start];
        }
        list($this->subcontents, $endofcontents) =
                                            $this->interpret_subcontents($string, $endofopening);
        if (empty($this->subcontents)) {
            $this->set_error_message('unrecognisedsubcontents', shorten_text($string, 20, true));
            return [false, $start];
        }
        list($found, $endofclosing, $subpatterns) =
                            $this->find_pattern($this->closingpattern, $string, $endofcontents);
        if (!$found) {
            if (!empty($this->missingclosingpatternerror)) {
                $this->set_error_message($this->missingclosingpatternerror,
                                            core_text::substr($string, $start, $endofcontents - $start));
            }
            return [true, $start];
        }
        return [true, $endofclosing];
    }

    /**
     * Interpret the subpattern in the opening of this item.
     * This is used to check if the subpattern in the opening is valid for this item.
     * The default implementation returns true, but subclasses can override this to check
     * the subpattern.
     *
     * @param string $subpattern the subpattern found in the opening
     * @return boolean true if the subpattern is valid, false otherwise
     */
    protected function interpret_subpattern_in_opening($subpattern) {
        return true;
    }

    #[\Override]
    public function get_formatted_expression_string($indentlevel = 0) {
        $string = $this->indent($indentlevel). $this->formatted_opening()." (\n";
        $string .= parent::get_formatted_expression_string($indentlevel);
        $string .= $this->indent($indentlevel). ")\n";
        return $string;
    }

    /**
     * Get the formatted opening string for this item.
     * This is used to format the opening of this item in the formatted expression string.
     * The default implementation returns an empty string, but subclasses can override this
     * to return a formatted opening string.
     *
     * @return string the formatted opening string
     */
    protected function formatted_opening() {
        return ''; // Overridden in sub classes.
    }

}

/**
 * This class is used to interpret a pmatch expression that contains a whole expression.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_whole_expression extends pmatch_interpreter_item_with_subcontents {
    /**
     * @var int How many items can be contained as sub contents of this item. If 0 then no limit.
     */
    protected $limitsubcontents = 1;

    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        return ['not', 'match_any', 'match_all', 'match_options'];
    }

    #[\Override]
    public function get_formatted_expression_string($indentlevel = 0) {
        return $this->subcontents[0]->get_formatted_expression_string($indentlevel);
    }
}

/**
 * This class is used to interpret a pmatch expression not match
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_not extends pmatch_interpreter_item_with_enclosed_subcontents {
    /**
     * @var string The patterns used to find the opening and closing of this item.
     */
    protected $openingpattern = '~\s*not\s*\(\s*~';
    /**
     * @var string The closing pattern must match the end of the string.
     */
    protected $closingpattern = '~\s*\)\s*~';
    /**
     * The error message to use when the closing pattern is not found.
     * If empty then no error message is set.
     * @var string
     */
    protected $missingclosingpatternerror = 'missingclosingbracket';
    /**
     * @var int How many items can be contained as sub contents of this item. If 0 then no limit.
     */
    protected $limitsubcontents = 1;

    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        return ['match_any', 'match_all', 'match_options'];
    }

    #[\Override]
    protected function formatted_opening() {
        return 'not';
    }
}

/**
 * This class is used to interpret a pmatch expression that matches any or all items.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_match extends pmatch_interpreter_item_with_enclosed_subcontents {
    /**
     * @var string The patterns used to find the opening and closing of this item.
     */
    protected $openingpattern = '~match([_a-z0-4]*)\s*\(\s*~';
    /**
     * @var string The closing pattern must match the end of the string.
     */
    protected $closingpattern = '~\s*\)\s*~';
    /**
     * The error message to use when the closing pattern is not found.
     * If empty then no error message is set.
     * @var string
     */
    protected $missingclosingpatternerror = 'missingclosingbracket';
}

/**
 * This class is used to interpret a pmatch expression that matches any.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_match_any extends pmatch_interpreter_match {
    #[\Override]
    protected function interpret_subpattern_in_opening($options) {
        return ($options == '_any');
    }
    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        return ['match_any', 'match_all', 'match_options', 'not'];
    }
    #[\Override]
    protected function formatted_opening() {
        return 'match_any';
    }
}

/**
 * This class is used to interpret a pmatch expression that matches all items.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_match_all extends pmatch_interpreter_match {
    #[\Override]
    protected function interpret_subpattern_in_opening($options) {
        return ($options == '_all');
    }
    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        return ['match_any', 'match_all', 'match_options', 'not'];
    }
    #[\Override]
    protected function formatted_opening() {
        return 'match_all';
    }
}

/**
 * This class is used to set options for matching at the word level.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_word_level_options {
    /**
     * @var bool Whether to allow extra characters in the word.
     */
    protected $allowextracharacters;
    /**
     * @var bool Whether to allow replacing a character in the word.
     */
    protected $misspellingallowreplacechar;
    /**
     * @var bool Whether to allow transposing two characters in the word.
     */
    protected $misspellingallowtransposetwochars;
    /**
     * @var bool Whether to allow an extra character in the word.
     */
    protected $misspellingallowextrachar;
    /**
     * @var bool Whether to allow fewer characters in the word.
     */
    protected $misspellingallowfewerchar;
    /**
     * @var int The number of misspellings allowed.
     */
    protected $misspellings;

    /**
     * Constructor for the pmatch_word_level_options class.
     * This sets the default options for matching at the word level.
     */
    public function __construct() {
        $this->reset_options();
    }

    /**
     * Reset the options to their default values.
     * This is used to reset the options to their default values.
     */
    public function reset_options() {
        $this->allowextracharacters = false;
        $this->misspellingallowreplacechar = false;
        $this->misspellingallowtransposetwochars = false;
        $this->misspellingallowextrachar = false;
        $this->misspellingallowfewerchar = false;
        $this->misspellings = 1;
    }


    /**
     * Enable or disable allowing extra characters in input.
     *
     * @param bool $allowextracharacters Whether extra characters are permitted.
     */
    public function set_allow_extra_characters($allowextracharacters) {
        $this->allowextracharacters = $allowextracharacters;
    }

    /**
     * Enable or disable allowing character replacements in misspellings.
     *
     * @param bool $misspellingallowreplacechar Whether replacement of characters is allowed in misspellings.
     */
    public function set_misspelling_allow_replace_char($misspellingallowreplacechar) {
        $this->misspellingallowreplacechar = $misspellingallowreplacechar;
    }

    /**
     * Enable or disable allowing transposition of two characters in misspellings.
     *
     * @param bool $misspellingallowtransposetwochars Whether transposing two characters is allowed in misspellings.
     */
    public function set_misspelling_allow_transpose_two_chars($misspellingallowtransposetwochars) {
        $this->misspellingallowtransposetwochars = $misspellingallowtransposetwochars;
    }

    /**
     * Enable or disable allowing additional characters in misspellings.
     *
     * @param bool $misspellingallowextrachar Whether extra characters are allowed in misspellings.
     */
    public function set_misspelling_allow_extra_char($misspellingallowextrachar) {
        $this->misspellingallowextrachar = $misspellingallowextrachar;
    }

    /**
     * Enable or disable allowing fewer characters in misspellings.
     *
     * @param bool $misspellingallowfewerchar Whether fewer characters are allowed in misspellings.
     */
    public function set_misspelling_allow_fewer_char($misspellingallowfewerchar) {
        $this->misspellingallowfewerchar = $misspellingallowfewerchar;
    }

    /**
     * Set the number of misspellings allowed.
     *
     * @param int $misspellings The number of misspellings allowed.
     */
    public function set_misspellings($misspellings) {
        $this->misspellings = $misspellings;
    }

    /**
     * Get the status of whether extra characters are allowed.
     *
     * @return bool
     */
    public function get_allow_extra_characters() {
        return $this->allowextracharacters;
    }

    /**
     * Get the status of whether replacement of characters in misspellings is allowed.
     *
     * @return bool
     */
    public function get_misspelling_allow_replace_char() {
        return $this->misspellingallowreplacechar;
    }

    /**
     * Get the status of whether transposing two characters in misspellings is allowed.
     *
     * @return bool
     */
    public function get_misspelling_allow_transpose_two_chars() {
        return $this->misspellingallowtransposetwochars;
    }

    /**
     * Get the status of whether extra characters in misspellings are allowed.
     *
     * @return bool
     */
    public function get_misspelling_allow_extra_char() {
        return $this->misspellingallowextrachar;
    }

    /**
     * Get the status of whether fewer characters in misspellings are allowed.
     *
     * @return bool
     */
    public function get_misspelling_allow_fewer_char() {
        return $this->misspellingallowfewerchar;
    }

    /**
     * Get the number of misspellings allowed.
     *
     * @return int
     */
    public function get_misspellings() {
        return $this->misspellings;
    }

    /**
     * Get the options as a string.
     * This is used to get the options as a string that can be used in the pmatch expression.
     * The string will contain the following characters:
     * - 'c' if extra characters are allowed
     * - 'm' if misspellings are allowed
     * - 'r' if replacing characters is allowed
     * - 't' if transposing two characters is allowed
     * - 'x' if extra characters in misspellings are allowed
     * - 'f' if fewer characters in misspellings are allowed
     * - '2' if two misspellings are allowed
     *
     * @return string the options as a string
     */
    public function get_options_as_string() {
        $string = '';
        if ($this->misspellingallowreplacechar &&
                    $this->misspellingallowextrachar &&
                    $this->misspellingallowfewerchar &&
                    $this->misspellingallowtransposetwochars) {
            $string .= 'm';
            if ($this->misspellings == 2) {
                $string .= '2';
            }
        } else if ($this->misspellingallowreplacechar ||
                    $this->misspellingallowextrachar ||
                    $this->misspellingallowfewerchar ||
                    $this->misspellingallowtransposetwochars) {
            $string .= 'm';
            if ($this->misspellingallowreplacechar) {
                $string .= 'r';
            }
            if ($this->misspellingallowtransposetwochars) {
                $string .= 't';
            }
            if ($this->misspellingallowextrachar) {
                $string .= 'x';
            }
            if ($this->misspellingallowfewerchar) {
                $string .= 'f';
            }
        } else if ($this->allowextracharacters) {
            $string .= 'c';
        }
        return $string;
    }
}

/**
 * This class is used to set options for matching at the phrase level.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_phrase_level_options {
    /**
     * @var int The proximity of words in the phrase.
     * This is the maximum number of words that can be between two words in the phrase.
     */
    protected $allowproximityof;

    /**
     * @var bool Whether to allow any word order in the phrase.
     * If true then the words in the phrase can be in any order.
     */
    protected $allowanywordorder;

    /**
     * @var bool Whether to allow extra words in the phrase.
     * If true then extra words can be present in the phrase that are not in the original phrase.
     */
    protected $allowextrawords;

    /**
     * Constructor for the pmatch_phrase_level_options class.
     * This sets the default options for matching at the phrase level.
     */
    public function __construct() {
        $this->reset_options();
    }
    /**
     * Get the status of whether extra words are allowed in the phrase.
     *
     * @return bool
     */
    public function get_allow_proximity_of() {
        return $this->allowproximityof;
    }

    /**
     * Get the status of whether any word order is allowed in the phrase.
     *
     * @return bool
     */
    public function get_allow_any_word_order() {
        return $this->allowanywordorder;
    }
    /**
     * Get the status of whether extra words are allowed in the phrase.
     *
     * @return bool
     */
    public function get_allow_extra_words() {
        return $this->allowextrawords;
    }

    /**
     * Reset the options to their default values.
     */
    public function reset_options() {
        $this->allowanywordorder = false;
        $this->allowextrawords = false;
        $this->allowproximityof = 2;
    }

    /**
     * Set the proximity of words in the phrase.
     * This is the maximum number of words that can be between two words in the phrase.
     *
     * @param int $allowproximityof The proximity of words in the phrase.
     */
    public function set_allow_proximity_of($allowproximityof) {
        $this->allowproximityof = $allowproximityof;
    }

    /**
     * Set whether any word order is allowed in the phrase.
     *
     * @param bool $allowanywordorder Whether any word order is allowed in the phrase.
     */
    public function set_allow_any_word_order($allowanywordorder) {
        $this->allowanywordorder = $allowanywordorder;
    }

    /**
     * Set whether extra words are allowed in the phrase.
     *
     * @param bool $allowextrawords Whether extra words are allowed in the phrase.
     */
    public function set_allow_extra_words($allowextrawords) {
        $this->allowextrawords = $allowextrawords;
    }

    /**
     * Get the options as a string.
     * This is used to get the options as a string that can be used in the pmatch expression.
     * The string will contain the following characters:
     * - 'o' if any word order is allowed
     * - 'w' if extra words are allowed
     * - 'p0', 'p1', 'p2', 'p3', or 'p4' for proximity of words in the phrase
     *
     * @return string the options as a string
     */
    public function get_options_as_string() {
        $string = '';
        if ($this->allowanywordorder) {
            $string .= 'o';
        }
        if ($this->allowextrawords) {
            $string .= 'w';
        }
        if ($this->allowproximityof != 2) {
            $string .= 'p'.$this->allowproximityof;
        }
        return $string;
    }
}

/**
 * This class is used to set options for matching at the phrase level.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_match_options extends pmatch_interpreter_match {

    /** @var pmatch_word_level_options */
    public $wordleveloptions;

    /** @var pmatch_phrase_level_options */
    public $phraseleveloptions;

    /**
     * Constructor for the pmatch_interpreter_match_options class.
     * This sets the default options for matching at the word and phrase level.
     *
     * @param pmatch_options $pmatchoptions The options for the pmatch expression.
     */
    public function __construct($pmatchoptions) {
        parent::__construct($pmatchoptions);
        $this->wordleveloptions = new pmatch_word_level_options();
        $this->phraseleveloptions = new pmatch_phrase_level_options();
    }

    #[\Override]
    protected function interpret_subpattern_in_opening($options) {
        // General checks.
        if (empty($options)) {
            return true;
        }
        if ($options == '_any' || $options == '_all') {
            return false;
        }
        if ($options[0] != '_') {
            $this->set_error_message('illegaloptions', $options);
            return false;
        }
        $this->phraseleveloptions->reset_options();
        $wlopt = $this->wordleveloptions;
        $wlopt->reset_options();
        $misspellingoptionmatches = [];
        $cursor = 1; // Start at second character after '_'.
        while ($cursor < core_text::strlen($options)) {
            if (0 === preg_match('~c|o|w|m([frtx2])*|p[0-4]~A',
                                        core_text::substr($options, $cursor),
                                        $misspellingoptionmatches)) {
                $this->set_error_message('illegaloptions', $options);
                return false;
            }
            $thisoption = $misspellingoptionmatches[0];
            switch ($thisoption[0]) {
                case 'm' :
                    if (core_text::strlen($thisoption) == 1) {
                        $wlopt->set_misspelling_allow_replace_char(true);
                        $wlopt->set_misspelling_allow_transpose_two_chars(true);
                        $wlopt->set_misspelling_allow_extra_char(true);
                        $wlopt->set_misspelling_allow_fewer_char(true);
                    } else {
                        $misspellingoptioncursor = 0;
                        do {
                            switch ($thisoption[1 + $misspellingoptioncursor]) {
                                case 'r' :
                                    $wlopt->set_misspelling_allow_replace_char(true);
                                    break;
                                case 't' :
                                    $wlopt->set_misspelling_allow_transpose_two_chars(true);
                                    break;
                                case 'x' :
                                    $wlopt->set_misspelling_allow_extra_char(true);
                                    break;
                                case 'f' :
                                    $wlopt->set_misspelling_allow_fewer_char(true);
                                    break;
                                case '2' :
                                    $wlopt->set_misspellings(2);
                                    $wlopt->set_misspelling_allow_replace_char(true);
                                    $wlopt->set_misspelling_allow_transpose_two_chars(true);
                                    $wlopt->set_misspelling_allow_extra_char(true);
                                    $wlopt->set_misspelling_allow_fewer_char(true);
                                    break;
                                default :
                                    $this->set_error_message('illegaloptions', $options);
                                    return false;
                            }
                            $misspellingoptioncursor ++;
                        } while (isset($thisoption[1 + $misspellingoptioncursor]));
                    }
                    break;
                case 'c' :
                    $wlopt->set_allow_extra_characters(true);
                    break;
                case 'p' :
                    if (0 === preg_match('~[0-4]$~A', $thisoption[1])) {
                        $this->set_error_message('illegaloptions', $options);
                        return false;
                    } else {
                        $this->phraseleveloptions->set_allow_proximity_of($thisoption[1]);
                    }
                    break;
                case 'o' :
                    $this->phraseleveloptions->set_allow_any_word_order(true);
                    break;
                case 'w' :
                    $this->phraseleveloptions->set_allow_extra_words(true);
                    break;
                default :
                    $this->set_error_message('illegaloptions', $options);
                    return false;
            }
            $cursor = $cursor + core_text::strlen($thisoption);
        }
        return true;
    }

    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        switch ($this->last_subcontent_type_found($foundsofar)) {
            case '':
            case 'word_delimiter_space':
            case 'word_delimiter_proximity':
                return ['or_list'];
            case 'or_list':
                return ['word_delimiter_space', 'word_delimiter_proximity'];
        }
    }

    #[\Override]
    public function get_formatted_expression_string($indentlevel = 0) {
        $string = $this->indent($indentlevel);
        $string .= $this->formatted_opening();
        $string .= ' (';
        foreach ($this->subcontents as $subcontent) {
            $string .= $subcontent->get_formatted_expression_string($indentlevel + 1);
        }
        $string .= ")\n";
        return $string;
    }

    #[\Override]
    protected function formatted_opening() {
        $options = '';
        $options .= $this->wordleveloptions->get_options_as_string();
        $options .= $this->phraseleveloptions->get_options_as_string();
        if (!empty($options)) {
            return 'match_'.$options;
        } else {
            return 'match';
        }
    }

    #[\Override]
    protected function interpret_subcontents($string, $start, $branchfoundsofar = []) {
        list($found, $end) = parent::interpret_subcontents($string, $start, $branchfoundsofar);
        if (!count($branchfoundsofar)) {
            if ($found && !empty($this->pmatchoptions->wordstoreplace)) {
                $subcontentsstr = core_text::substr($string, $start, $end - $start);
                $subcontentsstrwithsyn = preg_replace($this->pmatchoptions->wordstoreplace,
                        $this->pmatchoptions->synonymtoreplacewith, $subcontentsstr);
                if ($subcontentsstrwithsyn != $subcontentsstr) {
                    list($found, ) =
                        parent::interpret_subcontents($subcontentsstrwithsyn, 0, $branchfoundsofar);
                }
            }
        }
        return [$found, $end];
    }
}

/**
 * This class is used to interpret a pmatch expression that contains list.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_or_list extends pmatch_interpreter_item_with_subcontents {

    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        switch ($this->last_subcontent_type_found($foundsofar)) {
            case '':
            case 'or_character':
                return ['or_list_phrase', 'number', 'word'];
            case 'word':
            case 'number':
            case 'or_list_phrase':
                return ['or_character'];
        }
    }
}

/**
 * This class is used to interpret a pmatch expression that contains a synonym.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_synonym extends pmatch_interpreter_item_with_subcontents {
    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        switch ($this->last_subcontent_type_found($foundsofar)) {
            case '':
            case 'or_character':
                return ['number', 'word'];
            case 'number':
            case 'word':
                return ['or_character'];
        }
    }
}

/**
 * This class is used to interpret a pmatch expression that contains an or character.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_or_character extends pmatch_interpreter_item {
    /**
     * @var string The pattern used to find the or character in the pmatch expression.
     */
    protected $pattern = '~\|~';
}

/**
 * This class is used to interpret a pmatch expression that contains an or character.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_or_list_phrase extends pmatch_interpreter_item_with_enclosed_subcontents {
    /**
     * @var string The patterns used to find the opening this item.
     */
    protected $openingpattern = '~\[~';
    /**
     * @var string The patterns used to find the closing this item.
     */
    protected $closingpattern = '~\]~';
    /**
     * The error message to use when the closing pattern is not found.
     * If empty then no error message is set.
     * @var string
     */
    protected $missingclosingpatternerror = 'missingclosingbracket';
    /**
     * @var int How many items can be contained as sub contents of this item. If 0 then no limit.
     */
    protected $limitsubcontents = 1;

    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        return ['phrase'];
    }

    #[\Override]
    public function get_formatted_expression_string($indentlevel = 0) {
        $string = '[';
        foreach ($this->subcontents as $subcontent) {
            $string .= $subcontent->get_formatted_expression_string($indentlevel + 1);
        }
        $string .= ']';
        return $string;
    }
}

/**
 * This class is used to interpret a pmatch expression that contains a phrase.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_phrase extends pmatch_interpreter_item_with_subcontents {
    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        switch ($this->last_subcontent_type_found($foundsofar)) {
            case '':
            case 'word_delimiter_space':
            case 'word_delimiter_proximity':
                return ['synonym'];
            case 'synonym':
                return ['word_delimiter_space', 'word_delimiter_proximity'];
        }
    }
}

/**
 * This class is used to interpret a pmatch expression that contains a word delimiter space.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_word_delimiter_space extends pmatch_interpreter_item {
    /**
     * @var string The pattern used to find the word delimiter space in the pmatch expression.
     */
    protected $pattern = '~\s+~';
}

/**
 * This class is used to interpret a pmatch expression that contains a word delimiter proximity.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_word_delimiter_proximity extends pmatch_interpreter_item {
    /**
     * @var string The pattern used to find the word delimiter proximity in the pmatch expression.
     */
    protected $pattern = '~\_~';
}

/**
 * This class is used to interpret a pmatch expression that contains a word.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_word extends pmatch_interpreter_item_with_subcontents {
    #[\Override]
    protected function next_possible_subcontent($foundsofar) {
        return ['character_in_word', 'special_character_in_word',
                     'wildcard_match_multiple', 'wildcard_match_single'];
    }
}

/**
 * This class is used to interpret a pmatch expression that contains a phrase.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_number extends pmatch_interpreter_item {
    /**
     * Constructor for the pmatch_interpreter_number class.
     * @param pmatch_options $pmatchoptions options for the pmatch expression.
     */
    public function __construct($pmatchoptions) {
        parent::__construct($pmatchoptions);
        $this->pattern = '~'.PMATCH_NUMBER.'~';
    }
}

/**
 * This class is used to interpret a pmatch expression that contains a character in a word.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_character_in_word extends pmatch_interpreter_item {
    /**
     * Constructor for the pmatch_interpreter_character_in_word class.
     * @param pmatch_options $pmatchoptions options for the pmatch expression.
     */
    public function __construct($pmatchoptions) {
        parent::__construct($pmatchoptions);
        $this->pattern = '~'.PMATCH_CHARACTER.'~';
    }
}

/**
 * This class is used to interpret a pmatch expression that contains a special character in a word.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_special_character_in_word extends pmatch_interpreter_item {
    /**
     * Constructor for the pmatch_interpreter_special_character_in_word class.
     * @param pmatch_options $pmatchoptions options for the pmatch expression.
     */
    public function __construct($pmatchoptions) {
        parent::__construct($pmatchoptions);
        $this->pattern = '~\\\\'.PMATCH_SPECIAL_CHARACTER.'~';
    }
}

/**
 * This class is used to interpret a pmatch expression that contains a wildcard match for a single character.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_wildcard_match_single extends pmatch_interpreter_item {
    /**
     * @var string The pattern used to find the wildcard match for a single character in the pmatch expression.
     */
    protected $pattern = '~\?~';
}

/**
 * This class is used to interpret a pmatch expression that contains a wildcard match for multiple characters.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pmatch_interpreter_wildcard_match_multiple extends pmatch_interpreter_item {
    /**
     * @var string The pattern used to find the wildcard match for multiple characters in the pmatch expression.
     */
    protected $pattern = '~\*~';
}

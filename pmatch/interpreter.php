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
 * @package pmatch
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/question/type/pmatch/pmatch/matcher.php');

define('PMATCH_SPECIAL_CHARACTER', '\\\\[()\\\\ |?*_\[\]]');
define('PMATCH_CHARACTER', '[a-z0-9\!"#£$%&\'/\-+<=>@\^`{}~]');


abstract class pmatch_interpreter_item{
    protected $interpretererrormessage;
    public $codefragment;
    /**@var pmatch_options */
    public $pmatchoptions;
    /**
     * @param pmatch_options $pmatchoptions
     */
    public function __construct($pmatchoptions){
        $this->pmatchoptions = $pmatchoptions;
    }
    public function interpret($string, $start = 0){
        $this->interpretererrormessage = '';
        list($found, $endofmatch) = $this->interpret_contents($string, $start);
        if ($found){
            $this->codefragment = substr($string, $start, $endofmatch-$start);
        } else {
            $this->codefragment = '';
        }
        return array($found, $endofmatch);
    }
    protected $pattern;
    /**
     * 
     * Convert the $string starting at $start into a tree of object representing parts of pmatch code.
     * This is the default method which is often overriden. It looks for $pattern which is a regex with no
     * modifying options.
     * @param string $string
     * @param integer $start
     */
    protected function interpret_contents($string, $start){
        //regex pattern to match one character of pmatch code
        list($found, $endofpattern, $subpatterns) = $this->find_pattern($this->pattern, $string, $start);
        return array($found, $endofpattern);
    }

    /**
     * 
     * Find an anchored case insensitive regular expression, searching from $start.
     * @param string $pattern
     * @param string $string
     * @param integer $start
     * @return array $found boolean is the pattern found,
     *               $endofpattern integer the position of the end of the pattern,
     *               $matches array of matches of sub patterns with offset from $start
     */
    public function find_pattern($pattern, $string, $start){
        $matches = array();
        preg_match($pattern.'iA', substr($string, $start), $matches, PREG_OFFSET_CAPTURE);
        $found = !empty($matches);
        if ($found){
            $endofpattern = $matches[0][1]+strlen($matches[0][0])+$start;
        } else {
            $endofpattern = $start;
        }

        array_shift($matches);//pop off the matched string and only return sub patterns
        return array($found, $endofpattern, $matches);
    }
    public function get_error_message(){
        if (!empty($this->interpretererrormessage)){
            return $this->interpretererrormessage;
        } else {
            return '';
        }
    }
    public function set_error_message($errormessage, $codefragment){
        $this->interpretererrormessage = get_string('ie_'.$errormessage, 'qtype_pmatch', $codefragment);
    }
    /**
     * 
     * Get the matcher tree for this interpreter object. Can be used from an interpreter object at any point in the tree.
     * @param pmatch_options $externaloptions
     * @return pmatch_matcher_item a tree of child classes of pmatch_matcher_item
     */
    public function get_matcher($externaloptions){
        $thistypename = $this->get_type_name_of_interpreter_object($this);
        $matchclassname = 'pmatch_matcher_'.$thistypename;
        return new $matchclassname($this, $externaloptions);
    }
    public function get_type_name_of_interpreter_object($object){
        return substr(get_class($object), 19);
    }
    public function get_code_fragment(){
        return $this->codefragment;
    }
    public function get_formatted_expression_string($indentlevel = 0){
        return $this->codefragment;
    }
    protected function indent($indentlevel){
        return str_repeat('    ', $indentlevel);
    }
}
abstract class pmatch_interpreter_item_with_subcontents extends pmatch_interpreter_item{


    protected $subcontents = array();
    /**
     * 
     * How many items can be contained as sub contents of this item. If 0 then no limit.
     * @var integer
     */
    protected $limitsubcontents = 0;
    
    /**
     * 
     * Interpret sub contents of item.
     * @param string $string code that is to be interpreted
     * @param integer $start position at which to start
     * @param array $branchfoundsofar (optional) items found so far, if any
     * @return array (longest possible branch that matches the longest string,
     *                string position after code for these items.)
     */
    protected function interpret_subcontents($string, $start, $branchfoundsofar = array()){
        $typestotry = $this->next_possible_subcontent($branchfoundsofar);
        $branchindex = 0;
        $childbranches = array();
        $childbranchcursor = array();
        //iterate down all branches
        foreach ($typestotry as $typetotry){
            $childbranches[$branchindex] = $branchfoundsofar;
            list($typefound, $found, $childbranchcursor[$branchindex]) = 
                    $this->interpret_subcontent_item($typetotry, $string, $start);
            if ($found){
                $childbranches[$branchindex][] = $typefound;
                if (($this->limitsubcontents == 0) || (count($childbranches[$branchindex]) < $this->limitsubcontents)){
                    list($childbranches[$branchindex], $childbranchcursor[$branchindex]) = 
                        $this->interpret_subcontents($string, $childbranchcursor[$branchindex], $childbranches[$branchindex]);
                }
            }
            if ($anyerrormessage = $typefound->get_error_message()){
                $this->interpretererrormessage = $anyerrormessage;
            }
            $branchindex++;
        }
        //find the branch that matches the longest string
        array_multisort($childbranchcursor, SORT_DESC, SORT_NUMERIC, $childbranches);
        return array(array_shift($childbranches), array_shift($childbranchcursor));
    }
    /**
     * 
     * What was the last type of sub contents found in $foundsofar
     * @param array $foundsofar
     * @return string the type of sub contents last found (prefix with 'pmatch_interpreter_' to get classname)
     */
    protected function last_subcontent_type_found($foundsofar){
        if (!empty($foundsofar)){
            return $this->get_type_name_of_interpreter_object($foundsofar[count($foundsofar)-1]);
        } else {
            return '';
        }
    }
    /**
     * 
     * In the branch of code matched so far what could be the next type.
     * @param array $foundsofar
     * @return array the types of sub contents that could come next
     *                (prefix with 'pmatch_interpreter_' to get classname)
     */
    protected function next_possible_subcontent($foundsofar){
        return array();
    }
    /**
     * 
     * Try to match $cancontaintype in $string starting at $start.
     * @param string $cancontaintype
     * @param string $string
     * @param integer $start 
     */
    protected function interpret_subcontent_item($cancontaintype, $string, $start){
        $cancontainclassname = 'pmatch_interpreter_'.$cancontaintype;
        $cancontain = new $cancontainclassname($this->pmatchoptions);
        list($found, $aftercontent) = $cancontain->interpret($string, $start);
        if ($found) {
            return array($cancontain, true, $aftercontent);
        } else {
            return array($cancontain, false, $start);
        }
    }
    protected function interpret_contents($string, $start){
        list($this->subcontents, $endofcontents) = $this->interpret_subcontents($string, $start);
        $this->check_subcontents();
        return array((!empty($this->subcontents)), $endofcontents);
    }
    /**
     * 
     * Any checks that need to be done on sub contents found, are done here. The default is to check 
     * the last content type found and if the type is included in lastcontenttypeerrors report an error.
     */
    protected function check_subcontents(){
        if (array_key_exists($this->last_subcontent_type_found($this->subcontents), $this->lastcontenttypeerrors)){
            $this->set_error_message($this->lastcontenttypeerrors[$this->last_subcontent_type_found($this->subcontents)], 
                                    $this->codefragment);
        }
    }
    protected $lastcontenttypeerrors = array('or_character' => 'lastsubcontenttypeorcharacter',
                                             'word_delimiter_space' => 'lastsubcontenttypeworddelimiter',
                                             'word_delimiter_proximity' => 'lastsubcontenttypeworddelimiter');
    public function interpret($string, $start = 0){
        list($found, $endofmatch) = parent::interpret($string, $start);
        $this->check_subcontents();
        return array($found, $endofmatch);
    }
    public function get_subcontents(){
        return $this->subcontents;
    }
    public function get_formatted_expression_string($indentlevel = 0){
        $string = '';
        foreach ($this->subcontents as $subcontent){
            $string .= $subcontent->get_formatted_expression_string($indentlevel+1);
        }
        return $string;
    }
}


abstract class pmatch_interpreter_item_with_enclosed_subcontents extends pmatch_interpreter_item_with_subcontents{


    protected $openingpattern;
    protected $closingpattern;
    protected $missingclosingpatternerror = '';

    protected function interpret_contents($string, $start){
        $subpatterns = array();
        list($found, $endofopening, $subpatterns) = $this->find_pattern($this->openingpattern, $string, $start);

        if (!$found){
            return array(false, $start);
        }

        if (!empty($subpatterns)){
            $subpattern = $subpatterns[0][0];
        } else {
            $subpattern = '';
        }
        if (!$this->interpret_subpattern_in_opening($subpattern)) {
            return array(false, $start);
        }
        list($this->subcontents, $endofcontents) = $this->interpret_subcontents($string, $endofopening);
        if (empty($this->subcontents)){
            $this->set_error_message('unrecognisedsubcontents', substr($string, $start, 20));
            return array(false, $start);
        }
        list($found, $endofclosing, $subpatterns) = $this->find_pattern($this->closingpattern, $string, $endofcontents);
        if (!$found){
            if (!empty($this->missingclosingpatternerror)){
                $this->set_error_message($this->missingclosingpatternerror, substr($string, $start, $endofcontents - $start));
            }
            return array(true, $start);
        }
        return array(true, $endofclosing);
    }
    protected function interpret_subpattern_in_opening($subpattern){
        return true;
    }
    public function get_formatted_expression_string($indentlevel = 0){
        $string = $this->indent($indentlevel). $this->formatted_opening()." (\n";
        $string .= parent::get_formatted_expression_string($indentlevel);
        $string .= $this->indent($indentlevel). ")\n";
        return $string;
    }
    protected function formatted_opening(){
        return '';//overridden in sub classes
    }
    
}
class pmatch_interpreter_whole_expression extends pmatch_interpreter_item_with_subcontents{


    protected function next_possible_subcontent($foundsofar){
        return array('not', 'match_any', 'match_all', 'match_options');
    }

    protected $limitsubcontents = 1;
    public function get_formatted_expression_string($indentlevel = 0){
        return $this->subcontents[0]->get_formatted_expression_string($indentlevel);
    }
}
class pmatch_interpreter_not extends pmatch_interpreter_item_with_enclosed_subcontents{

    protected $openingpattern = '!\s*not\s*\(\s*!';
    protected $closingpattern = '!\s*\)\s*!';
    protected $missingclosingpatternerror = 'missingclosingbracket';

    protected function next_possible_subcontent($foundsofar){
        return array('match_any', 'match_all', 'match_options');
    }

    protected $limitsubcontents = 1;
    protected function formatted_opening(){
        return 'not';
    }
}
class pmatch_interpreter_match extends pmatch_interpreter_item_with_enclosed_subcontents{

    protected $openingpattern = '!match([_a-z0-4]*)\s*\(\s*!';
    protected $closingpattern = '!\s*\)\s*!';
    protected $missingclosingpatternerror = 'missingclosingbracket';
    
}
class pmatch_interpreter_match_any extends pmatch_interpreter_match{
    protected function interpret_subpattern_in_opening($options){
        return ($options == '_any');
    }
    protected function next_possible_subcontent($foundsofar){
        return array('match_any', 'match_all', 'match_options', 'not');
    }
    protected function formatted_opening(){
        return 'match_any';
    }
}

class pmatch_interpreter_match_all extends pmatch_interpreter_match{
    protected function interpret_subpattern_in_opening($options){
        return ($options == '_all');
    }
    protected function next_possible_subcontent($foundsofar){
        return array('match_any', 'match_all', 'match_options', 'not');
    }
    protected function formatted_opening(){
        return 'match_all';
    }
}
class pmatch_word_level_options {
    protected $allowextracharacters;
    protected $misspellingallowreplacechar;
    protected $misspellingallowtransposetwochars;
    protected $misspellingallowextrachar;
    protected $misspellingallowfewerchar;
    protected $misspellings;

    public function __construct(){
        $this->reset_options();
    }

    public function reset_options(){
        $this->allowextracharacters = false;
        $this->misspellingallowreplacechar = false;
        $this->misspellingallowtransposetwochars = false;
        $this->misspellingallowextrachar = false;
        $this->misspellingallowfewerchar = false;
        $this->misspellings = 1;
    }
    public function set_allow_extra_characters($allowextracharacters){
        $this->allowextracharacters = $allowextracharacters;
    }
    public function set_misspelling_allow_replace_char($misspellingallowreplacechar){
        $this->misspellingallowreplacechar = $misspellingallowreplacechar;
    }
    public function set_misspelling_allow_transpose_two_chars($misspellingallowtransposetwochars){
        $this->misspellingallowtransposetwochars = $misspellingallowtransposetwochars;
    }
    public function set_misspelling_allow_extra_char($misspellingallowextrachar){
        $this->misspellingallowextrachar = $misspellingallowextrachar;
    }
    public function set_misspelling_allow_fewer_char($misspellingallowfewerchar){
        $this->misspellingallowfewerchar = $misspellingallowfewerchar;
    }
    public function set_misspellings($misspellings){
        $this->misspellings = $misspellings;
    }
    public function get_allow_extra_characters(){
        return $this->allowextracharacters;
    }
    public function get_misspelling_allow_replace_char(){
        return $this->misspellingallowreplacechar;
    }
    public function get_misspelling_allow_transpose_two_chars(){
        return $this->misspellingallowtransposetwochars;
    }
    public function get_misspelling_allow_extra_char(){
        return $this->misspellingallowextrachar;
    }
    public function get_misspelling_allow_fewer_char(){
        return $this->misspellingallowfewerchar;
    }
    public function get_misspellings(){
        return $this->misspellings;
    }
    public function get_options_as_string(){
        $string = '';
        if ($this->misspellingallowreplacechar && $this->misspellingallowextrachar && $this->misspellingallowfewerchar && $this->misspellingallowtransposetwochars){
            $string .= 'm';
            if ($this->misspellings == 2){
                $string .= '2';
            }
        } else if ($this->misspellingallowreplacechar || $this->misspellingallowextrachar || $this->misspellingallowfewerchar || $this->misspellingallowtransposetwochars){
            $string .= 'm';
            if ($this->misspellingallowreplacechar){
                $string .= 'r';
            }
            if ($this->misspellingallowtransposetwochars){
                $string .= 't';
            }
            if ($this->misspellingallowextrachar){
                $string .= 'x';
            }
            if ($this->misspellingallowfewerchar){
                $string .= 'f';
            }
        } else if ($this->allowextracharacters) {
            $string .= 'c';
        }
        return $string;
    }
}
class pmatch_phrase_level_options {
    protected $allowproximityof;
    protected $allowanywordorder;
    protected $allowextrawords;

    public function __construct(){
        $this->reset_options();
    }

    public function get_allow_proximity_of(){
        return $this->allowproximityof;
    }
    public function get_allow_any_word_order(){
        return $this->allowanywordorder;
    }
    public function get_allow_extra_words(){
        return $this->allowextrawords;
    }
    public function reset_options(){
        $this->allowanywordorder = false;
        $this->allowextrawords = false;
        $this->allowproximityof = 2;
    }
    public function set_allow_proximity_of($allowproximityof){
        $this->allowproximityof = $allowproximityof;
    }
    public function set_allow_any_word_order($allowanywordorder){
        $this->allowanywordorder = $allowanywordorder;
    }
    public function set_allow_extra_words($allowextrawords){
        $this->allowextrawords = $allowextrawords;
    }
    public function get_options_as_string(){
        $string = '';
        if ($this->allowanywordorder){
            $string .= 'o';
        }
        if ($this->allowextrawords){
            $string .= 'w';
        }
        if ($this->allowproximityof != 2){
            $string .= 'p'.$this->allowproximityof;
        }
        return $string;
    }
}
class pmatch_interpreter_match_options extends pmatch_interpreter_match{

    /**
     * @var pmatch_word_level_options
     */
    public $wordleveloptions;

    /**
     * @var pmatch_phrase_level_options
     */
    public $phraseleveloptions;

    public function __construct($pmatchoptions){
        parent::__construct($pmatchoptions);
        $this->wordleveloptions = new pmatch_word_level_options();
        $this->phraseleveloptions = new pmatch_phrase_level_options();
    }


    protected function interpret_subpattern_in_opening($options){
        //general checks
        if (empty($options)){
            return true;
        }
        if ($options == '_any' || $options == '_all'){
            return false;
        }
        if ($options[0] != '_'){
            $this->set_error_message('illegaloptions', $options);
            return false;
        }
        $this->phraseleveloptions->reset_options();
        $this->wordleveloptions->reset_options();
        $misspellingoptionmatches = array();
        $cursor = 1;//start at second character after '_'
        while($cursor < strlen($options)){
            if (FALSE === preg_match('!c|o|w|m[frtx2]*|p[0-4]!A', substr($options, $cursor), $misspellingoptionmatches)){
                $this->set_error_message('illegaloptions', $options);
                return false;
            }
            $thisoption = $misspellingoptionmatches[0];
            switch ($thisoption[0]){
                case 'm' :
                    if (strlen($thisoption) == 1){
                        $this->wordleveloptions->set_misspelling_allow_replace_char(true);
                        $this->wordleveloptions->set_misspelling_allow_transpose_two_chars(true);
                        $this->wordleveloptions->set_misspelling_allow_extra_char(true);
                        $this->wordleveloptions->set_misspelling_allow_fewer_char(true);
                    } else {
                        $misspellingoptioncursor = 0;
                        do {
                            switch ($thisoption[1 + $misspellingoptioncursor]){
                                case 'r' :
                                    $this->wordleveloptions->set_misspelling_allow_replace_char(true);
                                    break;
                                case 't' :
                                    $this->wordleveloptions->set_misspelling_allow_transpose_two_chars(true);
                                    break;
                                case 'x' :
                                    $this->wordleveloptions->set_misspelling_allow_extra_char(true);
                                    break;
                                case 'f' :
                                    $this->wordleveloptions->set_misspelling_allow_fewer_char(true);
                                    break;
                                case '2' :
                                    $this->wordleveloptions->set_misspellings(2);
                                    $this->wordleveloptions->set_misspelling_allow_replace_char(true);
                                    $this->wordleveloptions->set_misspelling_allow_transpose_two_chars(true);
                                    $this->wordleveloptions->set_misspelling_allow_extra_char(true);
                                    $this->wordleveloptions->set_misspelling_allow_fewer_char(true);
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
                    $this->wordleveloptions->set_allow_extra_characters(true);
                    break;
                case 'p' :
                    if (FALSE !== preg_match('![0-4]$!A', $thisoption[1])){
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
            $cursor = $cursor + strlen($thisoption);
        }
        return true;
    }
    protected function next_possible_subcontent($foundsofar){
        switch ($this->last_subcontent_type_found($foundsofar)){
            case '':
            case 'word_delimiter_space':
            case 'word_delimiter_proximity':
                return array('or_list');
            case 'or_list':
                return array('word_delimiter_space', 'word_delimiter_proximity');
        }
    }
    public function get_formatted_expression_string($indentlevel = 0){
        $string = $this->indent($indentlevel);
        $string .= $this->formatted_opening();
        $string .= ' (';
        foreach ($this->subcontents as $subcontent){
            $string .= $subcontent->get_formatted_expression_string($indentlevel+1);
        }
        $string .= ")\n";
        return $string;
    }
    protected function formatted_opening(){
        $options = '';
        $options .= $this->wordleveloptions->get_options_as_string();
        $options .= $this->phraseleveloptions->get_options_as_string();
        if (!empty($options)){
            return 'match_'.$options;
        } else {
            return 'match';
        }
    }

    protected function interpret_subcontents($string, $start, $branchfoundsofar = array()){
        list($found, $end) = parent::interpret_subcontents($string, $start, $branchfoundsofar);
        if (!count($branchfoundsofar)){
            if ($found && !empty($this->pmatchoptions->wordstoreplace)){
                $subcontentsstr = substr($string, $start, $end - $start);
                $subcontentsstrwithsyn = preg_replace($this->pmatchoptions->wordstoreplace, 
                        $this->pmatchoptions->synonymtoreplacewith, $subcontentsstr);
                if ($subcontentsstrwithsyn != $subcontentsstr) {
                    list($found, ) = parent::interpret_subcontents($subcontentsstrwithsyn, 0, $branchfoundsofar);
                }
            }
        }
        return array($found, $end);
    }

}
class pmatch_interpreter_or_list extends pmatch_interpreter_item_with_subcontents{
    protected function next_possible_subcontent($foundsofar){
        switch ($this->last_subcontent_type_found($foundsofar)){
            case '':
            case 'or_character':
                return array('or_list_phrase', 'number', 'word');
            case 'word':
            case 'number':
            case 'or_list_phrase':
                return array('or_character');
        }
    }
}
/**
 * 
 * This is the same as an or_list but with no or_list_phrases. 
 *
 */
class pmatch_interpreter_synonym extends pmatch_interpreter_item_with_subcontents{
    protected function next_possible_subcontent($foundsofar){
        switch ($this->last_subcontent_type_found($foundsofar)){
            case '':
            case 'or_character':
                return array('number', 'word');
            case 'number':
            case 'word':
                return array('or_character');
        }
    }
}
class pmatch_interpreter_or_character extends pmatch_interpreter_item{
    protected $pattern = '!\|!';
}
class pmatch_interpreter_or_list_phrase extends pmatch_interpreter_item_with_enclosed_subcontents{

    protected $openingpattern = '!\[!';
    protected $closingpattern = '!\]!';
    protected $missingclosingpatternerror = 'missingclosingbracket';
    
    protected function next_possible_subcontent($foundsofar){
        return array('phrase');
    }
    
    protected $limitsubcontents = 1;
    
    public function get_formatted_expression_string($indentlevel = 0){
        $string = '[';
        foreach ($this->subcontents as $subcontent){
            $string .= $subcontent->get_formatted_expression_string($indentlevel+1);
        }
        $string .= ']';
        return $string;
    }
    
}

class pmatch_interpreter_phrase extends pmatch_interpreter_item_with_subcontents{
    protected function next_possible_subcontent($foundsofar){
        switch ($this->last_subcontent_type_found($foundsofar)){
            case '':
            case 'word_delimiter_space':
            case 'word_delimiter_proximity':
                return array('synonym');
            case 'synonym':
                return array('word_delimiter_space', 'word_delimiter_proximity');
        }
    }
}
class pmatch_interpreter_word_delimiter_space extends pmatch_interpreter_item{
    protected $pattern = '!\s+!';
}
class pmatch_interpreter_word_delimiter_proximity extends pmatch_interpreter_item{
    protected $pattern = '!\_!';
}
class pmatch_interpreter_word extends pmatch_interpreter_item_with_subcontents{
    protected function next_possible_subcontent($foundsofar){
        return array('character_in_word', 'special_character_in_word', 'wildcard_match_multiple', 'wildcard_match_single');
    }
}
class pmatch_interpreter_number extends pmatch_interpreter_item{
    protected $pattern = '!([+|-]( )?)?[0-9]+(\.[0-9]+)?!';
}
class pmatch_interpreter_character_in_word extends pmatch_interpreter_item{
    public function __construct($pmatchoptions){
        parent::__construct($pmatchoptions);
        $this->pattern = '!'.PMATCH_CHARACTER.'!';
    }
}
class pmatch_interpreter_special_character_in_word extends pmatch_interpreter_item{
    public function __construct($pmatchoptions){
        parent::__construct($pmatchoptions);
        $this->pattern = '!'.PMATCH_SPECIAL_CHARACTER.'!';
    }
}
class pmatch_interpreter_wildcard_match_single extends pmatch_interpreter_item{
    protected $pattern = '!\?!';
}
class pmatch_interpreter_wildcard_match_multiple extends pmatch_interpreter_item{
    protected $pattern = '!\*!';
}

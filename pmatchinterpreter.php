<?php
require_once($CFG->dirroot.'/question/type/pmatch/pmatchmatcher.php');
abstract class qtype_pmatch_interpreter_item{
    protected $interpretererrormessage;
    protected $codefragment;
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
    public function get_matcher(){
        $thistypename = $this->get_type_name_of_interpreter_object($this);
        $matchclassname = 'qtype_pmatch_matcher_'.$thistypename;
        return new $matchclassname($this);
    }
    public function get_type_name_of_interpreter_object($object){
        return substr(get_class($object), 25);
    }
    public function get_code_fragment(){
        return $this->codefragment;
    }
}
abstract class qtype_pmatch_interpreter_item_with_subcontents extends qtype_pmatch_interpreter_item{

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
     * @return string the type of sub contents last found (prefix with 'qtype_pmatch_interpreter_' to get classname)
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
     *                (prefix with 'qtype_pmatch_interpreter_' to get classname)
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
        $cancontainclassname = 'qtype_pmatch_interpreter_'.$cancontaintype;
        $cancontain = new $cancontainclassname();
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
                                             'word_delimiter' => 'lastsubcontenttypeworddelimiter');
    public function interpret($string, $start = 0){
        list($found, $endofmatch) = parent::interpret($string, $start);
        $this->check_subcontents();
        return array($found, $endofmatch);
    }
    public function get_subcontents(){
        return $this->subcontents;
    }
}


abstract class qtype_pmatch_interpreter_item_with_enclosed_subcontents extends qtype_pmatch_interpreter_item_with_subcontents{


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
            return array(false, $start);
        }
        return array(true, $endofclosing);
    }
    protected function interpret_subpattern_in_opening($subpattern){
        return true;
    }
}
class qtype_pmatch_interpreter_whole_expression extends qtype_pmatch_interpreter_item_with_subcontents{

    protected function next_possible_subcontent($foundsofar){
        return array('not', 'match_any', 'match_all', 'match_options');
    }

    protected $limitsubcontents = 1;
}
class qtype_pmatch_interpreter_not extends qtype_pmatch_interpreter_item_with_enclosed_subcontents{

    protected $openingpattern = '!\s*not\s*\(\s*!';
    protected $closingpattern = '!\s*\)\s*!';
    protected $missingclosingpatternerror = 'missingclosingbracket';

    protected function next_possible_subcontent($foundsofar){
        return array('match_any', 'match_all', 'match_options');
    }

    protected $limitsubcontents = 1;
}
class qtype_pmatch_interpreter_match extends qtype_pmatch_interpreter_item_with_enclosed_subcontents{

    protected $openingpattern = '!match([_a-z2]*)\s*\(\s*!';
    protected $closingpattern = '!\s*\)\s*!';
    protected $missingclosingpatternerror = 'missingclosingbracket';
    
}
class qtype_pmatch_interpreter_match_any extends qtype_pmatch_interpreter_match{
    protected function interpret_subpattern_in_opening($options){
        return ($options == '_any');
    }
    protected function next_possible_subcontent($foundsofar){
        return array('match_any', 'match_all', 'match_options', 'not');
    }

}

class qtype_pmatch_interpreter_match_all extends qtype_pmatch_interpreter_match{
    protected function interpret_subpattern_in_opening($options){
        return ($options == '_all');
    }
    protected function next_possible_subcontent($foundsofar){
        return array('match_any', 'match_all', 'match_options', 'not');
    }
}

class qtype_pmatch_interpreter_match_options extends qtype_pmatch_interpreter_match{

    protected $allowextracharacters;
    protected $allowanywordorder;
    protected $allowextrawords;
    protected $misspellingallowreplacechar;
    protected $misspellingallowtransposetwochars;
    protected $misspellingallowextrachar;
    protected $misspellingallowfewerchar;
    protected $allowproximityof;

    public function __construct(){
        $this->reset_options();
    }
    protected function reset_options(){
        $this->allowextracharacters = false;
        $this->allowanywordorder = false;
        $this->allowextrawords = false;
        $this->misspellingallowreplacechar = false;
        $this->misspellingallowtransposetwochars = false;
        $this->misspellingallowextrachar = false;
        $this->misspellingallowfewerchar = false;
        $this->allowproximityof = 2;
        $this->misspellings = 1;
    }
    
    protected function interpret_subpattern_in_opening($options){
        $this->reset_options();
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
        if (!preg_match('!\_(c|o|w|m|mf|mr|mt|mx|m2|p0|p1|p2|p3|p4)+$!A', $options)){
            $this->set_error_message('illegaloptions', $options);
            return false;
        }
        if (FALSE !== strpos($options, 'c')){
            $this->allowextracharacters = true;
        }
        if (FALSE !== strpos($options, 'o')){
            $this->allowanywordorder = true;
        }
        if (FALSE !== strpos($options, 'w')){
            $this->allowextrawords = true;
        }
        $moptionpos = strpos($options, 'm');
        if (isset($options[$moptionpos+1])){
            $msecondchar = $options[$moptionpos+1];
        } else {
            $msecondchar = '';
        }

        switch ($msecondchar){
            case 'r' :
                $this->misspellingallowreplacechar = true;
                break;
            case 't' :
                $this->misspellingallowtransposetwochars = true;
                break;
            case 'x' :
                $this->misspellingallowextrachar = true;
                break;
            case 'f' :
                $this->misspellingallowfewerchar = true;
                break;
            case '2' :
                $this->misspellings = 2;
            default :
                $this->misspellingallowreplacechar = true;
                $this->misspellingallowtransposetwochars = true;
                $this->misspellingallowextrachar = true;
                $this->misspellingallowfewerchar = true;
                break;
        }
        if (FALSE !== strpos($options, 'm', $moptionpos+1)){
            $this->set_error_message('illegaloptions', $options);
            return false;
        }
        if ($this->allowextracharacters && ($this->misspellingallowreplacechar||$this->misspellingallowtransposetwochars
                                ||$this->misspellingallowextrachar||$this->misspellingallowfewerchar)){
            $this->set_error_message('illegaloptions', $options);
            return false;
        }
        $proximitymatches = array();
        $noofproximitymatches = preg_match_all('!p([0-4])!i', $options, $proximitymatches, PREG_PATTERN_ORDER);
        if ($noofproximitymatches > 1){
            $this->set_error_message('illegaloptions', $options);
            return false;
        } else if ($noofproximitymatches == 1){
            $this->allowproximityof = $proximitymatches[1][0];
        }
        return true;
    }
    protected function next_possible_subcontent($foundsofar){
        switch ($this->last_subcontent_type_found($foundsofar)){
            case '':
            case 'word_delimiter':
                return array('phrase', 'or_list');
            case 'or_list':
            case 'phrase':
                return array('word_delimiter');
        }
    }
}
class qtype_pmatch_interpreter_or_list extends qtype_pmatch_interpreter_item_with_subcontents{
    protected function next_possible_subcontent($foundsofar){
        switch ($this->last_subcontent_type_found($foundsofar)){
            case '':
            case 'or_character':
                return array('or_list_phrase', 'word');
            case 'word':
            case 'or_list_phrase':
                return array('or_character');
        }
    }
}
class qtype_pmatch_interpreter_or_character extends qtype_pmatch_interpreter_item{
    protected $pattern = '!\|!';
}
class qtype_pmatch_interpreter_or_list_phrase extends qtype_pmatch_interpreter_item_with_enclosed_subcontents{

    protected $openingpattern = '!\[!';
    protected $closingpattern = '!\]!';
    protected $missingclosingpatternerror = 'missingclosingbracket';
    
    protected function next_possible_subcontent($foundsofar){
        return array('phrase');
    }
    
    protected $limitsubcontents = 1;
}


class qtype_pmatch_interpreter_phrase extends qtype_pmatch_interpreter_item_with_subcontents{
    protected function next_possible_subcontent($foundsofar){
        switch ($this->last_subcontent_type_found($foundsofar)){
            case '':
            case 'word_delimiter':
                return array('word');
            case 'word':
                return array('word_delimiter');
        }
    }
}
class qtype_pmatch_interpreter_word_delimiter extends qtype_pmatch_interpreter_item{
    protected $pattern = '!\_|\s+!';
}
class qtype_pmatch_interpreter_word extends qtype_pmatch_interpreter_item_with_subcontents{
    protected function next_possible_subcontent($foundsofar){
        return array('character_in_word', 'special_character_in_word', 'wildcard_match_multiple', 'wildcard_match_single');
    }
}
class qtype_pmatch_interpreter_character_in_word extends qtype_pmatch_interpreter_item{
    protected $pattern = '![a-z0-9\!"#£$%&\'/\-+<=>@\^`{}~]!';
}
class qtype_pmatch_interpreter_special_character_in_word extends qtype_pmatch_interpreter_item{
    protected $pattern = '!\\\\[()\\\\ |?*_\[\]]!';
}
class qtype_pmatch_interpreter_wildcard_match_single extends qtype_pmatch_interpreter_item{
    protected $pattern = '!\?!';
}
class qtype_pmatch_interpreter_wildcard_match_multiple extends qtype_pmatch_interpreter_item{
    protected $pattern = '!\*!';
}

<?php
abstract class qtype_pmatch_item{
    protected $matched;
    public function interpret($string, $start){
        list($found, $endofmatch) = $this->interpret_contents($string, $start);
        if ($found){
            $this->matched = substr($string, $start, $endofmatch-$start);
        } else {
            $this->matched = '';
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
     * Invert the returned match for this item.
     * @var boolean
     */
    var $not;
    /**
     * Search for a pattern with some common options (case insensitive, multiline)
     * @param string $pattern the pattern to match
     * @param string $string the string to be checked
     * @param the position to start at in the string
     * @see qtype_pmatch_negatable_item::match()
     */
    public function find_pattern($pattern, $string, $start){
        $matches = array();
        preg_match($pattern.'im', substr($string, $start), $matches, PREG_OFFSET_CAPTURE);
        $found = !empty($matches);
        if ($found){
            $endofpattern = $matches[0][1]+strlen($matches[0][0])+$start;
        } else {
            $endofpattern = $start;
        }
        array_shift($matches);//pop off the matched string and only return sub patterns
        return array($found, $endofpattern, $matches);
    }
    /**
     * Is there a not here?
     * @param string $string the string to be match
     * @param the position to start at in the string
     * @see qtype_pmatch_negatable_item::interpret()
     */
    public function interpret_not($string, $start){
        list($found, $endofpattern, $subpatterns) = $this->find_pattern('!^\s*not\s+!', $string, $start);
        return array($found, $endofpattern);
    }
}
abstract class qtype_pmatch_item_with_contents extends qtype_pmatch_item{

    protected $contents = array();

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
        $endofchildbranch = array();
        //iterate down all branches
        foreach ($typestotry as $typetotry){
            $childbranches[$branchindex] = $branchfoundsofar;
            list($typefound, $found, $childbranchcursor[$branchindex]) = 
                    $this->interpret_subcontent_item($typetotry, $string, $start);
            if ($found){
                $childbranches[$branchindex][] = $typefound;
                list($childbranches[$branchindex], $childbranchcursor[$branchindex]) = 
                    $this->interpret_subcontents($string, $childbranchcursor[$branchindex], $childbranches[$branchindex]);
            }
            $branchindex++;
        }
        //find the branch that matches the longest string
        array_multisort($childbranchcursor, SORT_DESC, SORT_NUMERIC, $childbranches);
        //print_object(compact('childbranchcursor', 'childbranches'));
        return array(array_shift($childbranches), array_shift($childbranchcursor));
    }
    /**
     * 
     * What was the last type of sub contents found in $foundsofar
     * @param array $foundsofar
     * @return string the type of sub contents last found (prefix with 'qtype_pmatch_' to get classname)
     */
    protected function last_subcontent_type_found($foundsofar){
        if (!empty($foundsofar)){
            return substr(get_class($foundsofar[count($foundsofar)-1]), 13);
        } else {
            return '';
        }
    }
    /**
     * 
     * In the branch of code matched so far what could be the next type.
     * @param array $foundsofar
     * @return array the types of sub contents that could come next
     *                (prefix with 'qtype_pmatch_' to get classname)
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
        $cancontainclassname = 'qtype_pmatch_'.$cancontaintype;
        $cancontain = new $cancontainclassname();
        list($found, $aftercontent) = $cancontain->interpret($string, $start);
        if ($found) {
            $founditem = $cancontain;
            return array($founditem, true, $aftercontent);
        } else {
            return array(null, false, $start);
        }
    }
    protected function interpret_contents($string, $start){
        list($this->contents, $endofcontents) = $this->interpret_subcontents($string, $start);
        return array((!empty($this->contents)), $endofcontents);
    }
}


abstract class qtype_pmatch_match extends qtype_pmatch_item_with_contents{

    abstract protected function interpret_options($options);

    protected function interpret_contents($string, $start){
        //first check for a not
        list(, $matchstart) = $this->interpret_not($string, $start);
        $subpatterns = array();
        list($found, $endofopening, $subpatterns) = $this->find_pattern('!^match(.*)\s*\(!', $string, $matchstart);
        if (!$found){
            echo 'opening not matched';
            return array(false, $start);
        }
        if (!empty($subpatterns)){
            $options = $subpatterns[0][0];
        } else {
            $options = '';
        }
        if (!$this->interpret_options($options)) {
            echo 'options not matched';
            return array(false, $start);
        }
        list($this->contents, $endofcontents) = $this->interpret_subcontents($string, $endofopening);
        if (empty($this->contents)){
            echo 'contents not matched';
            return array(false, $start);
        }
        list($found, $endofclosing, $subpatterns) = $this->find_pattern('!^\)\s*!', $string, $endofcontents);
        if (!$found){
            echo 'closing not matched';
            echo substr($string, $endofcontents);
            print_object($this);
            return array(false, $start);
        }
        return array(true, $endofclosing);
    }

}
/*
class qtype_pmatch_match_any extends qtype_pmatch_match{
    protected function interpret_options($options){
        return ($options == '_any');
    }
}

class qtype_pmatch_match_all extends qtype_pmatch_match{
    protected function interpret_options($options){
        return ($options == '_all');
    }
}*/

class qtype_pmatch_match_options extends qtype_pmatch_match{
    protected function interpret_options($options){
        return true;
    }
    protected function next_possible_subcontent($foundsofar){
       return array('or_list', 'phrase');
    }
}
class qtype_pmatch_or_list extends qtype_pmatch_item_with_contents{
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
class qtype_pmatch_or_character extends qtype_pmatch_item{
    protected $pattern = '!^\|!';
}
class qtype_pmatch_or_list_phrase extends qtype_pmatch_item_with_contents{
    protected function interpret_contents($string, $start){
        if ($string[$start] == '[') {
            list($phrase, $found, $endofcontents) = $this->interpret_subcontent_item('phrase', $string, $start+1);
            if ($found && $string[$endofcontents] == ']') {
                $this->content = array($phrase);
                return array(true, $endofcontents+1);
            } else {
                return array(false, $start); // TODO maybe bubble up an error here
            }
        }
        return array(false, $start);
    }
}

class qtype_pmatch_phrase extends qtype_pmatch_item_with_contents{
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
class qtype_pmatch_word_delimiter extends qtype_pmatch_item{
    protected $pattern = '!^[_\s]!';
}
class qtype_pmatch_word extends qtype_pmatch_item_with_contents{
    protected function next_possible_subcontent($foundsofar){
        return array('character_in_word', 'wildcard_in_word');
    }
}
class qtype_pmatch_character_in_word extends qtype_pmatch_item{
    protected $pattern = '!^[a-z0-9\!"#£$%&\'/\-+<=>@\^`{}~]|(\\\\[()\\\\ |?*_\[\]])!';
}
class qtype_pmatch_wildcard_in_word extends qtype_pmatch_item{
    protected $pattern = '!^[?*]!';
}

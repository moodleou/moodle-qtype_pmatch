<?php
abstract class qtype_pmatch_item{
    abstract public function match($string, $start);
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
            $newstart = $matches[0][1]+strlen($matches[0][0])+$start;
        } else {
            $newstart = $start;
        }
        $not = $found;
        array_shift($matches);//pop off the matched string and only return sub patterns
        return array($found, $newstart, $matches);
    }
    /**
     * Is there a not here?
     * @param string $string the string to be match
     * @param the position to start at in the string
     * @see qtype_pmatch_negatable_item::match()
     */
    public function match_not($string, $start){
        return $this->find_pattern('!^\s*not\s+!', $string, $start);
    }
}
class qtype_pmatch_match extends qtype_pmatch_item{
    
    private function check_options($options){}
    
    public function match($string, $start){
        //first check for a not
        list(, $matchstart) = $this->match_not($string, $start);
        list($found, $newstart, $subpatterns) = $this->find_pattern('!^pmatch(.*)\s*\(!', $string, $matchstart);

        if (!$this->check_options($subpatterns[0][0])) {
            $found = false;
        }
        return array($found, $newstart);
    }
    
}

class qtype_pmatch_match_any extends qtype_pmatch_match{
    private function check_options($options){
        return ($options == '_any');
    }
}

class qtype_pmatch_match_all extends qtype_pmatch_match{
    private function check_options($options){
        return ($options == '_all');
    }
    }
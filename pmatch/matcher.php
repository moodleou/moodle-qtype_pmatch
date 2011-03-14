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
 * This file contains code to match an already interpreted a pmatch expression to a student response.
 *
 * @package pmatch
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

interface pmatch_word_delimiter {
    /**
     * 
     * Check that items separated pmatch expressions are in the right order 
     * and / or proximity to be matched validly. Do not need to check that the two words are not the same.
     * 
     * @param array $phrase the words that are being matched
     * @param array $wordsmatched index no of words that have been matched so far
     * @param integer $wordtotry word we want to know if it is in the right position to match
     * @param pmatch_phrase_level_options $phraseleveloptions
     * @return boolean 
     */
    public function valid_match($phrase, $wordsmatched, $wordtotry, $phraseleveloptions);
    
    /**
     * 
     * A hook to override allow any word order in word delimiter that precedes a phrase.
     */
    public function allow_any_word_order_in_adjacent_phrase($allowanywordorder);
}
interface pmatch_can_match_char {
    /**
     * Can possibly match a char.
     * @param string $char a character
     * @return boolean successful match?
     */
    public function match_char($char);
}
interface pmatch_can_match_multiple_or_no_chars {
    /**
     * Can possibly match some characters.
     * @param string $chars some characters to match
     * @return boolean successful match?
     */
    public function match_chars($chars);
}
interface pmatch_can_match_word {
    /**
     * Can possibly match a word.
     * @param array $word a word
     * @param pmatch_word_level_options $wordleveloptions
     * @return boolean successful match?
     */
    public function match_word($word, $wordleveloptions);
}
interface pmatch_can_match_phrase {
    /**
     * 
     * Can possibly match a phrase - can accept more than one word at once.
     * @param array $phrase array of words
     * @param pmatch_phrase_level_options $phraseleveloptions
     * @param pmatch_word_level_options $wordleveloptions
     * @return boolean successful match?
     */
    public function match_phrase($phrase, $phraseleveloptions, $wordleveloptions);
    
}
interface pmatch_can_match_whole_expression {
    /**
     * 
     * Can possibly match the whole expression.
     * @param array $phrase array of words
     * @return boolean successful match?
     */
    public function match_whole_expression($words);

}

interface pmatch_can_contribute_to_length_of_phrase{
        /**
     * 
     * How many words can this phrase match? Minimum and max.
     * @param pmatch_phrase_level_options $phraseleveloptions
     * @return array with two values 0=> minimum and 1=> maximum. Values are the same if only
     *                    number of words possible. Maximum is null if no max.
     */
    public function contribution_to_length_of_phrase_can_try($phraseleveloptions);
    
}

abstract class pmatch_matcher_item{
    /** @var pmatch_interpreter_item */
    protected $interpreter;
    /** @var boolean */
    protected $ignorecase;
    /**
     * 
     * Constructor normally called by pmatch_interpreter_item->get_matcher method
     * @param pmatch_interpreter_item $interpreter
     * @param boolean $ignorecase
     */
    public function __construct($interpreter, $ignorecase){
        $this->interpreter = $interpreter;
        $this->ignorecase = $ignorecase;
    }

    /**
     * 
     * Used for testing purposes. To make sure type and type of contents is as expected.
     */
    public function get_type(){
        $typeobj = new stdClass();
        $typeobj->name = $this->get_type_name($this);
        $typeobj->codefragment = $this->interpreter->get_code_fragment();
        return $typeobj;
    }
    public function get_type_name($object){
        return substr(get_class($object), 15);
    }
}
abstract class pmatch_matcher_item_with_subcontents extends pmatch_matcher_item{

    protected $subcontents = array();
    
    /**
     * 
     * Create a tree of matcher items.
     * @param pmatch_interpreter_item_with_subcontents $interpreter
     */
    public function __construct($interpreter, $ignorecase){
        parent::__construct($interpreter, $ignorecase);
        $interpretersubcontents = $interpreter->get_subcontents();
        foreach ($interpretersubcontents as $interpretersubcontent){
            $this->subcontents[] = $interpretersubcontent->get_matcher($ignorecase);
        }
    }
    /**
     * 
     * Used for testing purposes. To make sure type and type of contents is as expected.
     */
    public function get_type(){
        $typeobj = new stdClass();
        $typeobj->name = $this->get_type_name($this);
        $typeobj->codefragment = $this->interpreter->get_code_fragment();
        $typeobj->subcontents = array();
        foreach ($this->subcontents as $subcontent){
            $typeobj->subcontents[] = $subcontent->get_type();
        }
        return $typeobj;
    }

    /**
     * 
     * @param array $phrase Array of words
     * @param pmatch_phrase_level_options $phraseleveloptions
     * @param pmatch_word_level_options $wordleveloptions
     * @return boolean Successfully matched?
     */
    public function match_phrase($phrase, $phraseleveloptions, $wordleveloptions){
        $this->phraseleveloptions = $phraseleveloptions;
        $this->wordleveloptions = $wordleveloptions;
        list($phraseminlength, $phrasemaxlength) = $this->contribution_to_length_of_phrase_can_try($phraseleveloptions);
        if (count($phrase) < $phraseminlength){
            return false;
        }
        if ((!$this->phraseleveloptions->get_allow_extra_words())
                && (!is_null($phrasemaxlength)) && (count($phrase) > $phrasemaxlength)){
            return false;
        }
        return $this->check_match_phrase_branch($phrase);
    }

    /**
     * 
     * Used to check for a match to a phrase of words separated by white space. Code shared by 
     * phrase in a phrase list as well as for the whole expression in match_options. This is a recursive function
     * that can be started by just calling it with the phrase to check and leaving other params as the default.
     * @param array $phrase
     * @param integer $itemtotry
     * @param integer $wordtotry
     * @param array $wordsmatched
     * @return boolean found a match?
     */
    protected function check_match_phrase_branch($phrase, $itemtotry = 0, $wordtotry = 0, $wordsmatched = array()){
        if ($wordtotry >= count($phrase)){
            return false;
        }
        //is this a valid item to try to match?
        $shallwetry = ((!count($wordsmatched)) || 
                    $this->subcontents[$itemtotry - 1]->valid_match($phrase, $wordsmatched,
                                                                    $wordtotry, $this->phraseleveloptions))
                    && (!in_array($wordtotry, $wordsmatched, true));
        if ($shallwetry && $this->subcontents[$itemtotry]->match_word($phrase[$wordtotry], $this->wordleveloptions)){
            //we found a match
            $newwordsmatched = $wordsmatched;
            $newwordsmatched[] = $wordtotry;
            if ($itemtotry == count($this->subcontents) -1){
                //last item matched : success
                if (count($newwordsmatched) == count($phrase) || $this->phraseleveloptions->get_allow_extra_words()){
                    return true;
                } else {
                    return false;
                }
            } else {
                //item matched, find next item to try to match
                if ($this->phraseleveloptions->get_allow_any_word_order()){
                    $nextwordtotry = 0;
                } else {
                    $nextwordtotry = $wordtotry + 1;
                }
                if ($this->check_match_phrase_branch($phrase, $itemtotry + 2, $nextwordtotry, $newwordsmatched)) {
                    return true;
                }
            }
        } 
        if ($this->subcontents[$itemtotry] instanceof pmatch_can_match_phrase){
            list($phraseminlength, $phrasemaxlength) = $this->subcontents[$itemtotry]->contribution_to_length_of_phrase_can_try($this->phraseleveloptions);
            if (is_null($phrasemaxlength)){
                $phrasemaxlength = count($phrase)- ($wordtotry);
            }
            //check all possible lengths of phrase
            for ($phraselength = $phraseminlength; $phraselength <= $phrasemaxlength; $phraselength++){
                if (in_array(($wordtotry + $phraselength -1), $wordsmatched, true)){
                    break;//next word has been matched already, stop
                }
                $nextwordtotry = $wordtotry + $phraselength;
                $nextphraseleveloptions = clone($this->phraseleveloptions);
                $allowanywordorder = $this->phraseleveloptions->get_allow_any_word_order();
                if (isset($this->subcontents[$itemtotry - 1])){
                    $allowanywordorder = $this->subcontents[$itemtotry - 1]->allow_any_word_order_in_adjacent_phrase($allowanywordorder);
                }
                if (isset($this->subcontents[$itemtotry + 1])){
                    $allowanywordorder = $this->subcontents[$itemtotry + 1]->allow_any_word_order_in_adjacent_phrase($allowanywordorder);
                }
                $nextphraseleveloptions->set_allow_any_word_order($allowanywordorder);
                if ($this->subcontents[$itemtotry]->match_phrase(array_slice($phrase, $wordtotry, $phraselength), $nextphraseleveloptions, $this->wordleveloptions)){
                    $wordsmatchedandphrasewords = array_merge($wordsmatched, range($wordtotry, $wordtotry + $phraselength -1));
                    if (($itemtotry) == count($this->subcontents) -1){
                        if (count($wordsmatchedandphrasewords) == count($phrase) || $this->phraseleveloptions->get_allow_extra_words()){
                            return true;
                        } else {
                            return false;
                        }
                    } else if ($this->check_match_phrase_branch($phrase, $itemtotry + 2, $nextwordtotry, $wordsmatchedandphrasewords)) {
                        return true;
                    }
                }
            }
        }

        //if it is allowed try next word also
        if ($this->phraseleveloptions->get_allow_extra_words() || $this->phraseleveloptions->get_allow_any_word_order()){
            $nextwordtotry = $wordtotry + 1;
            //try next word
            if ($this->check_match_phrase_branch($phrase, $itemtotry, $nextwordtotry, $wordsmatched)){
                return true;
            }
        }
        return false;
    }

    
}

class pmatch_matcher_whole_expression extends pmatch_matcher_item_with_subcontents implements pmatch_can_match_whole_expression {
    public function match_whole_expression($words){
        return $this->subcontents[0]->match_whole_expression($words);
    }
}
class pmatch_matcher_not extends pmatch_matcher_item_with_subcontents{
    public function match_whole_expression($words){
        return !$this->subcontents[0]->match_whole_expression($words);
    }
}
class pmatch_matcher_match extends pmatch_matcher_item_with_subcontents{
}
class pmatch_matcher_match_any extends pmatch_matcher_match implements pmatch_can_match_whole_expression {
    public function match_whole_expression($words){
        foreach ($this->subcontents as $subcontent){
            if ($subcontent->match_whole_expression($words)){
                return true;
            }
        }
        return false;
    }
}

class pmatch_matcher_match_all extends pmatch_matcher_match implements  pmatch_can_match_whole_expression {
    public function match_whole_expression($words){
        foreach ($this->subcontents as $subcontent){
            if (!$subcontent->match_whole_expression($words)){
                return false;
            }
        }
        return true;
    }
}

class pmatch_matcher_match_options extends pmatch_matcher_match
            implements pmatch_can_match_phrase, pmatch_can_contribute_to_length_of_phrase, 
                       pmatch_can_match_whole_expression {
    /**
     * @var pmatch_word_level_options
     */
    public $wordleveloptions;

    /**
     * @var pmatch_phrase_level_options
     */
    public $phraseleveloptions;

    public function match_whole_expression($words){
        return $this->match_phrase($words, $this->interpreter->phraseleveloptions, $this->interpreter->wordleveloptions);
    }




    public function contribution_to_length_of_phrase_can_try($phraseleveloptions){
        $min = 0;
        $max = 0;
        foreach ($this->subcontents as $subcontent){
            if ($subcontent instanceof pmatch_can_contribute_to_length_of_phrase) {
                list($subcontentmin, $subcontentmax) = $subcontent->contribution_to_length_of_phrase_can_try($phraseleveloptions);
                if (is_null($subcontentmax) || is_null($max)){
                    $max = null;
                } else {
                    $max = $max + $subcontentmax;
                }
                $min = $min + $subcontentmin;
            }
        }
        return array($min, $max);
    }

}
class pmatch_matcher_or_list extends pmatch_matcher_item_with_subcontents
            implements pmatch_can_match_phrase, pmatch_can_match_word, 
                                pmatch_can_contribute_to_length_of_phrase{

    public function match_word($word, $wordleveloptions){
        foreach ($this->subcontents as $subcontent){
            if ($subcontent instanceof pmatch_can_match_word &&
                        $subcontent->match_word($word, $wordleveloptions) === true){
                return true;
            }
        }
        return false;
    }
    public function match_phrase($phrase, $phraseleveloptions, $wordleveloptions){
        foreach ($this->subcontents as $subcontent){
            if ($subcontent instanceof pmatch_can_match_phrase &&
                        $subcontent->match_phrase($phrase, $phraseleveloptions, $wordleveloptions) === true){
                return true;
            }
        }
        return false;
    }
    public function contribution_to_length_of_phrase_can_try($phraseleveloptions){
        $min = 1;
        $max = 1;
        foreach ($this->subcontents as $subcontent){
            if ($subcontent instanceof pmatch_can_contribute_to_length_of_phrase) {
                list($subcontentmin, $subcontentmax) = $subcontent->contribution_to_length_of_phrase_can_try($phraseleveloptions);
                if (is_null($subcontentmax) || is_null($max)){
                    $max = null;
                } else {
                    $max = max($max, $subcontentmax);
                }
                $min = min($min, $subcontentmin);
            }
        }
        return array($min, $max);
    }
}

/**
 * 
 * This is the same as an or_list but with no or_list_phrases. 
 *
 */
class pmatch_matcher_synonym extends pmatch_matcher_item_with_subcontents
            implements pmatch_can_match_word, pmatch_can_contribute_to_length_of_phrase{
                protected $usedmisspellings;
    /**
     * 
     * Called after running match_word or match_phrase. This function returns the minimum number of mispellings used to match the student response word to the
     * pmatch expression.
     * @return integer the number of misspellings found.
     */
    public function get_used_misspellings(){
        return $this->usedmisspellings;
    }
    public function match_word($word, $wordleveloptions){
        for ($this->usedmisspellings = 0; $this->usedmisspellings <= $wordleveloptions->get_misspellings(); $this->usedmisspellings++){
            foreach ($this->subcontents as $subcontent){
                $nextwordleveloptions = clone($wordleveloptions);
                $nextwordleveloptions->set_misspellings($this->usedmisspellings);
                if ($subcontent instanceof pmatch_can_match_word &&
                            $subcontent->match_word($word, $nextwordleveloptions) === true){
                    return true;
                }
            }
        }
        return false;
    }
    public function contribution_to_length_of_phrase_can_try($phraseleveloptions){
        return array(1, 1);
    }
}

class pmatch_matcher_or_character extends pmatch_matcher_item {

}
class pmatch_matcher_or_list_phrase extends pmatch_matcher_item_with_subcontents
            implements pmatch_can_match_phrase, pmatch_can_contribute_to_length_of_phrase{
    public function match_phrase($phrase, $phraseleveloptions, $wordleveloptions){
        foreach ($this->subcontents as $subcontent){
            if ($subcontent instanceof pmatch_can_match_phrase &&
                        $subcontent->match_phrase($phrase, $phraseleveloptions, $wordleveloptions) === true){
                return true;
            }
        }
        return false;
    }
    public function contribution_to_length_of_phrase_can_try($phraseleveloptions){
        $subcontent = reset($this->subcontents);
        return $subcontent->contribution_to_length_of_phrase_can_try($phraseleveloptions);
    }
}


class pmatch_matcher_phrase extends pmatch_matcher_item_with_subcontents
            implements pmatch_can_match_phrase, pmatch_can_contribute_to_length_of_phrase{
/*    public function match_phrase($phrase, $phraseleveloptions, $wordleveloptions){
        $wordno = 0;
        $subcontentno = 0;
        do {
            $subcontent = $this->subcontents[$subcontentno];
            $word = $phrase[$wordno];
            if ($subcontent instanceof pmatch_can_match_word){
                if ($subcontent->match_word($word, $wordleveloptions) !== true){
                    return false;
                }
                $wordno++;
            } 
            $subcontentno++;
            $nomorewords = (count($phrase) < ($wordno + 1));
            $nomoreitems = (count($this->subcontents) < ($subcontentno + 1));
            if ($nomorewords && $nomoreitems){
                return true;
            } else if ($nomorewords || $nomoreitems) {
                return false;
            }
        } while (true);
    }*/
    public function contribution_to_length_of_phrase_can_try($phraseleveloptions){
        $noofwords = (count($this->subcontents) + 1) / 2;
        if ($phraseleveloptions->get_allow_extra_words()){
            return array($noofwords, null);
        } else {
            return array($noofwords, $noofwords);
        }
    }

    
}
class pmatch_matcher_word_delimiter_space extends pmatch_matcher_item
            implements pmatch_word_delimiter, pmatch_can_contribute_to_length_of_phrase {
    public function valid_match($phrase, $wordsmatched, $wordtotry, $phraseleveloptions){
        $lastwordmatched = $wordsmatched[count($wordsmatched) -1];
        if (!$phraseleveloptions->get_allow_any_word_order() && !$phraseleveloptions->get_allow_extra_words()){
            return ($wordtotry == ($lastwordmatched + 1));
        } else if (!$phraseleveloptions->get_allow_any_word_order()){
            return ($wordtotry > $lastwordmatched);
        } else {
            return true;
        }
    }
    public function contribution_to_length_of_phrase_can_try($phraseleveloptions){
        if ($phraseleveloptions->get_allow_extra_words()){
            return array(0, null);
        } else {
            return array(0, 0);
        }
    }
    public function allow_any_word_order_in_adjacent_phrase($allowanywordorder){
        return $allowanywordorder;
    }
}
class pmatch_matcher_word_delimiter_proximity extends pmatch_matcher_item
            implements pmatch_word_delimiter, pmatch_can_contribute_to_length_of_phrase {
    public function valid_match($phrase, $wordsmatched, $wordtotry, $phraseleveloptions){
        $lastwordmatched = $wordsmatched[count($wordsmatched) -1];
        if ($wordtotry < $lastwordmatched){
            return false;
        }
        if (($wordtotry - $lastwordmatched) > ($phraseleveloptions->get_allow_proximity_of() + 1)){
            return false;
        }
        for ($wordno = $lastwordmatched; $wordno < $wordtotry; $wordno++){
            if (preg_match('!\.$!', $phrase[$wordno])){
                return false;
            }
        }
        return true;
    }
    public function contribution_to_length_of_phrase_can_try($phraseleveloptions){
        if ($phraseleveloptions->get_allow_extra_words()){
            return array(0, $phraseleveloptions->get_allow_proximity_of());
        } else {
            return array(0, 0);
        }
    }

    public function allow_any_word_order_in_adjacent_phrase($allowanywordorder){
        return false;
    }
}
class pmatch_matcher_number extends pmatch_matcher_item 
            implements pmatch_can_match_phrase, pmatch_can_contribute_to_length_of_phrase {
    public function __construct($interpreter, $ignorecase){
        parent::__construct($interpreter, $ignorecase);
    }
    public function match_phrase($words, $phraseleveloptions, $wordleveloptions){
        if (count($words) == 2){
            $studentinput = $words[0].$words[1];
        } else {
            $studentinput = $words[0];
        }
        if (0 === preg_match('![+|-]?[0-9]+(\.[0-9]+)?$!A', $studentinput)){
            return false;
        } else {
            $teacherinput = str_replace(' ', '', $this->interpreter->get_code_fragment());
            $numberparts = array();
            preg_match('!([+|-]( )?)?[0-9]+(\.[0-9]+)?$!A', $this->interpreter->get_code_fragment(), $numberparts);
            if (isset($numberparts[3]) && strlen($numberparts[3]) > 0){
                $decplaces = strlen($numberparts[3]) - 1;
                $studentinputrounded = round((float)$studentinput, $decplaces);
                $teacherinput = (float)$teacherinput;
                return $studentinputrounded == $teacherinput;
            } else {
                return ($teacherinput + 0) == ($studentinput + 0);
            }
        }
    }
    public function contribution_to_length_of_phrase_can_try($phraseleveloptions){
        return array(1, 2);//a number can look like two words to the matcher as it may have a space
    }
}
class pmatch_matcher_word extends pmatch_matcher_item_with_subcontents 
            implements pmatch_can_match_word, pmatch_can_contribute_to_length_of_phrase {
    /**
     * @var pmatch_word_level_options
     */
    private $wordleveloptions;
    
    /**
     * @param pmatch_word_level_options $wordleveloptions
     * @return pmatch_word_level_options word level options with some options disabled if word too short
     */
    protected function check_word_level_options($wordleveloptions){
        $normalcharactercount = 0;
        $adjustedwordleveloptions = clone($wordleveloptions);
        foreach ($this->subcontents as $subcontent){
            if (in_array($this->get_type_name($subcontent), array('character_in_word', 'special_character_in_word'))){
                $normalcharactercount++;
            }
        }
        if ($normalcharactercount < 3) {
            $adjustedwordleveloptions->set_misspelling_allow_extra_char(false);
        }
        if ($normalcharactercount < 4) {
            $adjustedwordleveloptions->set_misspelling_allow_replace_char(false);
            $adjustedwordleveloptions->set_misspelling_allow_transpose_two_chars(false);
            $adjustedwordleveloptions->set_misspelling_allow_fewer_char(false);
        }
        if ($normalcharactercount < 8 && ($adjustedwordleveloptions->get_misspellings() == 2)) {
            $adjustedwordleveloptions->set_misspellings(1);
        }
        return $adjustedwordleveloptions;
    }
    public function match_word($word, $wordleveloptions){
        $word = rtrim($word, '.');
        $this->wordleveloptions = $this->check_word_level_options($wordleveloptions);
        if ($this->check_match_branches($word, $this->wordleveloptions->get_misspellings())){
            return true;
        }
        return false;
    }
    /**
     * 
     * Check each character against each item and iterate down branches of possible matches to whole
     * word.
     * @param string $word word to match from student response
     * @param integer $charpos position of character in word we are currently checking for a match
     * @param integer $subcontentno subcontent item to match this character against
     * @param integer $noofcharactertomatch no of characters to match
     * @return boolean true if we find one match branch that successfully matches the whole word
     */
    private function check_match_branches($word, $allowmispellings, $charpos = 0, $subcontentno = 0, $noofcharactertomatch = 1){
        $itemslefttomatch = count($this->subcontents) - ($subcontentno + 1);
        $charslefttomatch = strlen($word) - ($charpos + $noofcharactertomatch);
        //check if we have gone beyond limit of what can be matched
        if ($itemslefttomatch < 0){
            if ($charslefttomatch < 0){
                return true;
            } else if ($this->wordleveloptions->get_allow_extra_characters()){
                return true;
            }else if ($this->wordleveloptions->get_misspelling_allow_extra_char() && ($allowmispellings > $charslefttomatch)){
                return true;
            } else {
                return false;
            }
        } else if ($charslefttomatch < 0) {
            if ($this->wordleveloptions->get_misspelling_allow_fewer_char() && ($allowmispellings > $itemslefttomatch)){
                return true;
            } else if (($this->subcontents[$subcontentno] instanceof pmatch_can_match_multiple_or_no_chars)
                    && ($this->check_match_branches($word, $allowmispellings, $charpos + 1, $subcontentno + 1, $noofcharactertomatch))){
                //no chars left to match but this is a multiple match wild card, so no match needed.
                return true;
            } else {
                return false;
            }
        }
        if ($this->subcontents[$subcontentno] instanceof pmatch_can_match_multiple_or_no_chars){
            $thisfragmentmatched = $this->subcontents[$subcontentno]->match_chars(substr($word, $charpos, $noofcharactertomatch));
        } else {
            $thisfragmentmatched = $this->subcontents[$subcontentno]->match_char(substr($word, $charpos, $noofcharactertomatch));
        }

        if (($noofcharactertomatch == 1) &&
                $this->subcontents[$subcontentno] instanceof pmatch_can_match_multiple_or_no_chars){
            //check for the multiple char match wild card matching no characters at the same time as checking for matching one
            if ($this->check_match_branches($word, $allowmispellings, $charpos, $subcontentno + 1, 1)){
                return true;
            }
        }
        if ((!$thisfragmentmatched) && $this->wordleveloptions->get_allow_extra_characters()){
            if ($this->check_match_branches($word, $allowmispellings, $charpos + 1, $subcontentno, 1)){
                return true;
            }
        }
        if ((!$thisfragmentmatched) && ($allowmispellings > 0)) {
            //if there is no match but we can match the next character 
            if ($this->wordleveloptions->get_misspelling_allow_transpose_two_chars()&& 
                        ($itemslefttomatch > 0) && ($charslefttomatch > 0)){
                if (!$this->subcontents[$subcontentno + 1] instanceof pmatch_can_match_multiple_or_no_chars){
                    $wordtransposed = $word;
                    $wordtransposed[$charpos] = $word[$charpos + 1];
                    $wordtransposed[$charpos + 1] = $word[$charpos];
                    if ($this->check_match_branches($wordtransposed, $allowmispellings - 1, $charpos, $subcontentno, 1)){
                        return true;
                    }
                }
            }
            //and if there is no match try ignoring this item
            if ($this->wordleveloptions->get_misspelling_allow_fewer_char()){
                if ($this->check_match_branches($word, $allowmispellings - 1, $charpos, $subcontentno + 1, 1)){
                    return true;
                }
            }
            //and if there is no match try ignoring this character
            if ($this->wordleveloptions->get_misspelling_allow_extra_char()){
                if ($this->check_match_branches($word, $allowmispellings - 1, $charpos + 1, $subcontentno, 1)){
                    return true;
                }
            }
            //and if there is no match try going on as if it was a match
            if ($this->wordleveloptions->get_misspelling_allow_replace_char()){
                if ($this->check_match_branches($word, $allowmispellings - 1, $charpos + 1, $subcontentno + 1, 1)){
                    return true;
                }
            }
        }
        
        if ($thisfragmentmatched){
            if ($this->subcontents[$subcontentno] instanceof pmatch_can_match_multiple_or_no_chars){
                if ($this->check_match_branches($word, $allowmispellings, $charpos, $subcontentno, $noofcharactertomatch + 1)){
                    return true;
                }
                if ($this->check_match_branches($word, $allowmispellings, $charpos + $noofcharactertomatch, $subcontentno + 1, 1)){
                    return true;
                }
            } else if ($this->check_match_branches($word, $allowmispellings, $charpos + $noofcharactertomatch, $subcontentno + 1, 1)){
                return true;
            }
        } else {
            return false;
        }
    }
    public function contribution_to_length_of_phrase_can_try($phraseleveloptions){
        return array(1, 1);
    }
}
class pmatch_matcher_character_in_word extends pmatch_matcher_item implements pmatch_can_match_char{
    public function match_char($character){
        $codefragment = $this->interpreter->get_code_fragment();
        if ($this->ignorecase){
            $textlib = textlib_get_instance();
            return ($textlib->strtolower($character) == $textlib->strtolower($codefragment));
        } else {
            return ($character == $codefragment);
        }
    }
}
class pmatch_matcher_special_character_in_word extends pmatch_matcher_item implements pmatch_can_match_char{
    public function match_char($character){
        $codefragment = $this->interpreter->get_code_fragment();
        return ($character == $codefragment[1]);
    }
}
class pmatch_matcher_wildcard_match_single extends pmatch_matcher_item implements pmatch_can_match_char{
    public function match_char($character){
        return true;
    }
}
class pmatch_matcher_wildcard_match_multiple 
            extends pmatch_matcher_item implements pmatch_can_match_multiple_or_no_chars{

    public function match_chars($characters){
        return true;
    }

}
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
 * This file contains the API for accessing pmatch expression interpreter and matcher.
 *
 * @package pmatch
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/question/type/pmatch/pmatch/interpreter.php');

/**
 * Options that control the overall way the matching is done.
 */
class pmatch_options {

    /** @var boolean */
    public $ignorecase;
 
    /** @var string of sentence divider characters. */
    public $sentencedividers = '.';
 
    /** @var string of word diveder characters. */
    public $worddividers = " \f\n\r\t";

    /** @var string of characters that will be converted to spaces before matching. */
    public $converttospace = "";

    /**
     * @var array of words to recognise. These words may include sentence or
     * word divider characters.
     */
    public $extradictionarywords = array('e.g.', 'eg.', 'etc.', 'i.e.', 'ie.');

    /**
     * @var array of strings with preg expressions to match words that can be replaced.
     */
    public $wordstoreplace = array();
    /**
     * @var array of strings to replace words with.
     */
    public $synonymtoreplacewith = array();

    /** @var array of words from synonyms that are exempt from spell check. */
    public $nospellcheckwords = array();

    public function set_synonyms($synonyms){
        $toreplace = array();
        $replacewith = array();
        foreach ($synonyms as $synonym){
            $toreplaceitem = preg_quote($synonym->word, '!');
            $toreplaceitem = str_replace('\*', '('.PMATCH_CHARACTER.'|'.PMATCH_SPECIAL_CHARACTER.')*', $toreplaceitem);
            $toreplaceitem = '!(^|\PL)'.$toreplaceitem.'(\PL|$)!';
            if ($this->ignorecase){
                $toreplaceitem .= 'i';
            }
            $this->wordstoreplace[] = $toreplaceitem;
            $this->synonymtoreplacewith[] = "{$synonym->word}|{$synonym->synonyms}";
            $this->nospellcheckwords[] = $synonym->word;
            $synonymsforthisword = explode('|', $synonym->synonyms);
            foreach ($synonymsforthisword as $synonymforthisword){
                $this->nospellcheckwords[] = $synonymforthisword;
            }
        }
    }
}
 
/**
 * Represents a string that is ready for matching, and provides the method to
 * match expressions against it.
 */
class pmatch_parsed_string {

    /** @var pmatch_options */
    protected $options;

    /** @var array of words created by splitting $string by $options->worddividers */
    public $words;

    private $misspelledwords = null;

    /**
     * Constructor.
     * @param string $string the string to match against.
     * @param pmatch_options $options the options to use.
     */
    public function __construct($string, pmatch_options $options = null){
        if (!is_null($options)){
            $this->options = $options;
        } else {
            $this->options = new pmatch_options();
        }

        $this->words = array();
        $word = strtok($string, $this->options->worddividers . $this->options->converttospace."\n\r");
        while ($word !== false) {
            if ($word != ''){
                $this->words[] = $word;
            }
            $word = strtok($this->options->worddividers . $this->options->converttospace);
        };
        if (count($this->words) == 0){
            $this->words = array('');
        }
    }
 
    /**
     * @return boolean returns false if any word is misspelt.
     */
    public function is_spelt_correctly($lang = null){
        $this->misspelledwords = $this->spell_check($this->words, $lang);
        return (count($this->misspelledwords) == 0);
    }

    protected function spell_check($words, $lang){
        if ($lang == null){
            $langidparts = explode('_', current_language());
            $lang = $langidparts[0];
        }
        if (!function_exists('pspell_new')){
            error_log('Attempted to spell check but pspell is not installed.');
            return array();
        }
        $pspell_link = pspell_new($lang);
        if ($pspell_link === false){
            error_log("Attempted a spell check for a language with no aspell dictionary installed - '{$lang}'.");
            return array();//if dictionary is not installed for this language we cannot spell check
        }
        $misspelledwords = array();
        $wordstoignore = array_merge($this->options->extradictionarywords, $this->options->nospellcheckwords);
        $wordstoignorepatterns = array(PMATCH_NUMBER);
        foreach ($wordstoignore as $wordtoignore){
            $wordstoignorepattern = preg_quote($wordtoignore, '!');
            $wordstoignorepattern = str_replace('\*', '('.PMATCH_CHARACTER.'|'.PMATCH_SPECIAL_CHARACTER.')*', $wordstoignorepattern);
            $wordstoignorepatterns[] = $wordstoignorepattern;
        }
        $sentencedividerpattern = '';
        foreach (str_split($this->options->sentencedividers) as $sentencedivider){
            if ($sentencedividerpattern != ''){
                $sentencedividerpattern .= '|';
            }
            $sentencedividerpattern .= preg_quote($sentencedivider);
        }
        $endofpattern = '('.$sentencedividerpattern.')?$!A';
        if ($this->options->ignorecase){
            $endofpattern .= 'i';
        }
        $words = array_unique($words);
        foreach ($wordstoignorepatterns as $wordstoignorepattern){
            $words = (preg_grep('!'.$wordstoignorepattern.$endofpattern, $words, PREG_GREP_INVERT));
        }
        foreach ($words as $word){
            $textlib = textlib_get_instance();
            if (FALSE !== strpos($this->options->sentencedividers, $textlib->substr($word, -1))){
                $word = $textlib->substr($word, 0, $textlib->strlen($word)-1);
            }

            if (!pspell_check($pspell_link, $word)) {
                $misspelledwords[] = $word;
            }
        }
        return $misspelledwords;
    }

    /**
     * @return array all the distinct misspelt words.
     */
    public function get_spelling_errors(){
        return $this->misspelledwords;
    }

    /**
     * @return integer no of words.
     */
    public function get_word_count(){
        return count($this->words);
    }

    /**
     * @return pmatch_options the options that were used to construct this object.
     */
    public function get_options(){
        return $this->options;
    }
    /**
     * @return array the words to try to match.
     */
    public function get_words(){
        return $this->words;
    }
}
 
/**
 * Represents a pmatch_expression.
 */
class pmatch_expression {
    /**
     * @var pmatch_interpreter_whole_expression
     */
    protected $interpreter;
    /**
     * @var string the original expression passed to the constructor
     */
    protected $originalexpression;
    /**
     * @var string
     */
    protected $errormessage;
    
    /**
     * @var boolean
     */
    protected $valid;
    
    /**
     * 
     * @param string $string the string to match against.
     * @param pmatch_options $options the options to use.
     */
    public function __construct($expression, $options = null){
        if (!is_null($options)){
            $this->options = $options;
        } else {
            $this->options = new pmatch_options();
        }
        $this->originalexpression = $expression;
        $this->interpreter = new pmatch_interpreter_whole_expression($options);
        list($matched, $endofmatch) = $this->interpreter->interpret($expression);
        $this->errormessage = $this->interpreter->get_error_message();
        if ($endofmatch == strlen($expression) && $matched && $this->errormessage == ''){
            $this->valid = true;
        } else {
            $this->valid = false;
            if ($this->errormessage == ''){
                $this->errormessage = get_string('ie_unrecognisedexpression', 'qtype_pmatch');
            }
        }
    }
 
    /**
     * Test a string with a given pmatch expression.
     * @param pmatch_parsed_string $parsedstring the parsed string to match.
     * @return boolean whether this string matches the expression.
     */
    public function matches(pmatch_parsed_string $parsedstring){
        if (!$this->is_valid()){
            throw new coding_exception('Oops. You called matches for an expression that is not valid. You should call is_valid first. Interpreter error :'.$this->get_parse_error());
            return false;
        }
        $matcher = $this->interpreter->get_matcher($this->options);
        return $matcher->match_whole_expression($parsedstring->get_words());
    }
    
    /**
     * @return boolean returns false if the string passed to the constructor
     * could not be parsed as a valid pmatch expression.
     */
    public function is_valid(){
        return $this->valid;
    }
 
    /**
     * @return string description of the syntax error in the expression string
     * if is_valid returned false. Otherwise returns an empty string.
     */
    public function get_parse_error(){
        return $this->errormessage;
    }
 
    /**
     * @return pmatch_options the options that were used to construct this object.
     */
    public function get_options(){
        return $this->options;
    }
 
    /**
     * @return string the expression, exactly as it was passed to the constructor.
     */
    public function get_original_expression_string(){
        return $this->originalexpression;
    }
 
    /**
     * @return string a nicely formatted version of the expression.
     */
    public function get_formatted_expression_string(){
        if (!$this->is_valid()){
            throw new coding_exception('Oops. You called get_formatted_expression_string for an expression that is not valid. You should call is_valid first.');
            return false;
        }
        return $this->interpreter->get_formatted_expression_string();
    }
}
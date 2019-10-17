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
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/pmatch/pmatch/interpreter.php');

use qtype_pmatch\local\spell\qtype_pmatch_spell_checker;

// The following is required because the xdebug library defaults to throwing a fatal error if
// there is more than 100 nested function calls.
if (extension_loaded('xdebug')) {
     ini_set('xdebug.max_nesting_level', 1000);
}


/**
 * Options that control the overall way the matching is done.
 */
class pmatch_options {

    /** @var boolean */
    public $ignorecase;

    /** @var string of sentence divider characters. */
    public $sentencedividers = '.?!';

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
     * @var string language of string -
     *             current language of respondee or saved for this attempt step.
     */
    public $lang;

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

    /**
     * Static factory to make an options object with various values set.
     *
     * @param array $settings field name => value to set.
     * @return pmatch_options new options object.
     */
    public static function make(array $settings): pmatch_options {
        $options = new pmatch_options();
        foreach ($settings as $name => $value) {
            if ($name === 'synonyms') {
                foreach ($value as $word => $synonyms) {
                    $options->set_synonyms([(object) ['word' => $word, 'synonyms' => $synonyms]]);
                }
            } else if (!property_exists($options, $name)) {
                throw new coding_exception("pmatch_options does not have a $name field");
            } else {
                $options->$name = $value;
            }
        }
        return $options;
    }

    public function set_synonyms($synonyms) {
        $toreplace = array();
        $replacewith = array();
        foreach ($synonyms as $synonym) {
            $synonym->word = $this->unicode_normalisation($synonym->word);
            $synonym->synonyms = $this->unicode_normalisation($synonym->synonyms);
            $toreplaceitem = preg_quote($synonym->word, '~');
            $toreplaceitem = preg_replace('~\\\\\*~u',
                        '('.$this->character_in_word_pattern().')*', $toreplaceitem);
            // The ?<= and ?= ensures that the adjacent characters are not replaced also.
            $toreplaceitem = '~(?<=^|\PL)'.$toreplaceitem.'(?=\PL|$)~u';
            if ($this->ignorecase) {
                $toreplaceitem .= 'i';
            }
            $this->wordstoreplace[] = $toreplaceitem;
            $this->synonymtoreplacewith[] = "{$synonym->word}|{$synonym->synonyms}";
            $this->nospellcheckwords[] = $synonym->word;
            $synonymsforthisword = explode('|', $synonym->synonyms);
            foreach ($synonymsforthisword as $synonymforthisword) {
                $this->nospellcheckwords[] = $synonymforthisword;
            }
        }
    }

    public function set_extra_dictionary_words($wordlist) {
        $wordlist = $this->unicode_normalisation($wordlist);
        $this->extradictionarywords = preg_split('~\s+~', $wordlist);
    }

    public function unicode_normalisation($unicodestring) {
        static $errorlogged = false;
        if (class_exists('Normalizer')) {
            return Normalizer::normalize($unicodestring);
        } else {
            // Limit the errors added to the log to one per pagee load.
            if (!$errorlogged) {
                debugging(get_string('env_peclnormalisationmissing', 'qtype_pmatch'), DEBUG_NORMAL);
                $errorlogged = true;
            }
            return $unicodestring;
        }
    }

    public function words_to_ignore_patterns() {
        $words = array_merge($this->extradictionarywords, $this->nospellcheckwords);
        $wordpatterns = array(PMATCH_NUMBER);
        foreach ($words as $word) {
            if (trim($word) === '') {
                continue;
            }
            $wordpattern = preg_quote($word, '~');
            $wordpattern = preg_replace('~\\\\\*~u',
                                        '('.$this->character_in_word_pattern().')*',
                                        $wordpattern);
            $wordpatterns[] = $wordpattern;
        }
        return $wordpatterns;
    }

    /**
     * @return string fragment of preg_match pattern to match sentence separator.
     */
    public function sentence_divider_pattern() {
        return $this->pattern_to_match_any_of($this->sentencedividers);
    }

    /**
     * @param string $word fragment of preg_match pattern to match sentence separator.
     * @return bool wether $words ends in one of the sentence divider characters.
     */
    public function word_has_sentence_divider_suffix($word) {
        $sd = $this->sentence_divider_pattern();
        return (1 === preg_match('~('.$sd.')$~u', $word));
    }

    /**
     *
     * Strip one and only one sentence divider from the end of a string.
     * @param string $string
     * @return string with sentence divider stripped off.
     */
    public function strip_sentence_divider($string) {
        if ($this->word_has_sentence_divider_suffix($string)) {
            $string = core_text::substr($string, 0, -1);
        }
        return $string;
    }

    public function word_divider_pattern() {
        return $this->pattern_to_match_any_of($this->worddividers . $this->converttospace);
    }

    public function character_in_word_pattern() {
        return PMATCH_CHARACTER.'|'.PMATCH_SPECIAL_CHARACTER;
    }

    public function pattern_options() {
        if ($this->ignorecase) {
            return 'i';
        } else {
            return '';
        }
    }

    private function pattern_to_match_any_of($charsinstring) {
        $pattern = '';
        for ($i = 0; $i < core_text::strlen($charsinstring); $i++) {
            $char = core_text::substr($charsinstring, $i, 1);
            if ($pattern != '') {
                $pattern .= '|';
            }
            $pattern .= preg_quote($char, '~');
            if ($char === '.') {
                // Full stop should only match if it is not a decimal point (followed by a digit).
                $pattern .= '(?![0-9])';
            }
        }
        // If there is no pattern, then we should never match anything.
        // Make a pattern that does that, to avoid bugs.
        if ($pattern === '') {
            $pattern = '(?!X)X';
        }
        return $pattern;
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

    private $unrecognizedfragment = '';

    /**
     * Constructor. Parses string.
     * @param string $string the string to match against.
     * @param pmatch_options $options the options to use.
     */
    public function __construct($string, pmatch_options $options = null) {
        if (!is_null($options)) {
            $this->options = $options;
        } else {
            $this->options = new pmatch_options();
        }

        $this->words = array();
        $cursor = 0;
        $string = trim($string); // Trim off any extra whitespace.
        $string = $this->options->unicode_normalisation($string);

        $sd = $this->options->sentence_divider_pattern();
        $wd = $this->options->word_divider_pattern();
        $wtis = $this->options->words_to_ignore_patterns();
        $po = $this->options->pattern_options();
        while ($cursor < core_text::strlen($string)) {
            $toprocess = core_text::substr($string, $cursor);
            $matches = array();
            // Using a named sub pattern to make sure to capture the sentence divider.
            $endofword = "(((?'sd'{$sd})({$wd})*)|({$wd})+|$)";
            foreach ($wtis as $wti) {
                if (preg_match("~({$wti})$endofword~Au$po", $toprocess, $matches)) {
                    // We found a number or extra dictionary word.
                    break;
                }
            }
            if (!count($matches)) {
                if (!preg_match("~(.+?)$endofword~Au$po", $toprocess, $matches)) {
                    // Ignore the rest of the string.
                    break;
                }
            }
            $word = $matches[1];
            if (isset($matches['sd'])) {
                $word .= $matches['sd'];
            }
            $this->words[] = $word;
            $cursor = $cursor + core_text::strlen($matches[0]);

            if ('' === $this->options->strip_sentence_divider($word)) {
                $this->unrecognizedfragment = core_text::substr($string, 0, $cursor);
            }
        }

        if (count($this->words) == 0) {
            $this->words = array('');
        }
    }

    /**
     * @return boolean returns false if any word is misspelled.
     */
    public function is_spelled_correctly() {
        $this->misspelledwords = $this->spell_check();
        return (count($this->misspelledwords) == 0);
    }

    public function is_parseable() {
        if ($this->unrecognizedfragment === '') {
            return true;
        } else {
            return false;
        }
    }

    public function unparseable() {
        return $this->unrecognizedfragment;
    }

    protected function spell_check() {
        $misspelledwords = [];
        foreach (array_unique($this->words) as $word) {
            $word = core_text::strtolower($word);
            $wrongword = $this->is_word_misspelled($word);
            if ($wrongword !== null) {
                $misspelledwords[] = $wrongword;
            }
        }
        return $misspelledwords;
    }

    /**
     * Test a 'word' to see if it is in the dictonary. If not, return
     *
     * The reason we need this is that 'words' in a parsed string contain
     * any surrounding punctuation. For example in '"Hello!" shouted Fred.'
     * the first word is "Hello!", and if that was not in the dictionary,
     * then eventually 'Hello' would be returned, however, in this case,
     * it is correct, so null will be returned.
     *
     * This may seem a bit complicated, but you need to remember that
     * 'e.g.', 'co-operate', and 'AC/DC' are all words found in dictionaries.
     *
     * @param qtype_pmatch_spell_checker $spellchecker
     * @param string $word
     * @return string|null
     */
    protected function is_word_misspelled(string $word) {
        if (trim($word) === '') {
            return null;
        }
        $endofpattern = '(' . $this->options->sentence_divider_pattern() . ')?$~Au';
        if ($this->options->ignorecase) {
            $endofpattern .= 'i';
        }
        foreach ($this->options->words_to_ignore_patterns() as $wordstoignorepattern) {
            if (preg_match('~'.$wordstoignorepattern.$endofpattern, $word)) {
                // Is a number, extra dictionary word or synonym.
                return null;
            }
        }

        $spellchecker = qtype_pmatch_spell_checker::make($this->options->lang);
        if ($spellchecker->is_in_dictionary($word)) {
            return null;
        }

        // Try trimming one non-letter character from the end or start, if present.
        if (preg_match('~[^\pL]$~u', $word)) {
            return $this->is_word_misspelled(core_text::substr($word, 0, -1));
        } else if (preg_match('~^[^\pL]~u', $word)) {
            return $this->is_word_misspelled(core_text::substr($word, 1));
        }

        // Finally, if what is left after all puncution is removed contains -s,
        // then the word is spelled right if all the bits are, so test that.
        if (core_text::strpos($word, '-') === false) {
            // No hyphens, so Word is misspelled.
            return $word;
        }

        foreach (explode('-', $word) as $fragment) {
            if (!$spellchecker->is_in_dictionary($fragment)) {
                // A fragment is misspelled, so reject the whole thing.
                return $word;
            }
        }

        // All fragments are OK, so fine.
        return null;
    }

    /**
     * @return array all the distinct misspelled words.
     */
    public function get_spelling_errors() {
        return $this->misspelledwords;
    }

    /**
     * @return integer no of words.
     */
    public function get_word_count() {
        return count($this->words);
    }

    /**
     * @return pmatch_options the options that were used to construct this object.
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * @return array the words to try to match.
     */
    public function get_words() {
        return $this->words;
    }
}


/**
 * Represents a pmatch_expression.
 */
class pmatch_expression {

    /** @var pmatch_interpreter_whole_expression */
    protected $interpreter;

    /** @var string the original expression passed to the constructor */
    protected $originalexpression;

    /** @var string */
    protected $errormessage;

    /** @var boolean */
    protected $valid;

    /**
     * @param string $string the string to match against.
     * @param pmatch_options $options the options to use.
     */
    public function __construct($expression, $options = null) {
        if (!is_null($options)) {
            $this->options = $options;
        } else {
            $this->options = new pmatch_options();
        }
        $expression = $this->options->unicode_normalisation($expression);
        $this->originalexpression = $expression;
        $this->interpreter = new pmatch_interpreter_whole_expression($options);
        list($matched, $endofmatch) = $this->interpreter->interpret($expression);
        $this->errormessage = $this->interpreter->get_error_message();
        if ($endofmatch == core_text::strlen($expression) && $matched && $this->errormessage == '') {
            $this->valid = true;
        } else {
            $this->valid = false;
            if ($this->errormessage == '') {
                $this->errormessage = get_string('ie_unrecognisedexpression', 'qtype_pmatch');
            }
        }
    }

    /**
     * Test a string with a given pmatch expression.
     * @param pmatch_parsed_string $parsedstring the parsed string to match.
     * @return boolean whether this string matches the expression.
     */
    public function matches(pmatch_parsed_string $parsedstring) {
        if (!$this->is_valid()) {
            throw new coding_exception('Oops. You called matches for an expression that is not '.
                                'valid. You should call is_valid first. Interpreter error :'.
                                $this->get_parse_error());
            return false;
        }
        $matcher = $this->interpreter->get_matcher($this->options);
        return $matcher->match_whole_expression($parsedstring->get_words());
    }

    /**
     * @return boolean returns false if the string passed to the constructor
     * could not be parsed as a valid pmatch expression.
     */
    public function is_valid() {
        return $this->valid;
    }

    /**
     * @return string description of the syntax error in the expression string
     * if is_valid returned false. Otherwise returns an empty string.
     */
    public function get_parse_error() {
        return $this->errormessage;
    }

    /**
     * @return pmatch_options the options that were used to construct this object.
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * @return string the expression, exactly as it was passed to the constructor.
     */
    public function get_original_expression_string() {
        return $this->originalexpression;
    }

    /**
     * @return string a nicely formatted version of the expression.
     */
    public function get_formatted_expression_string() {
        if (!$this->is_valid()) {
            throw new coding_exception('Oops. You called get_formatted_expression_string for an '.
                                'expression that is not valid. You should call is_valid first.');
            return false;
        }
        return $this->interpreter->get_formatted_expression_string();
    }
}

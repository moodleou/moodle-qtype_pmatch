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
 * This file containscode to match an already interpreted a pmatch expression to a student
 * response.
 *
 * @package   qtype_pmatch
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for word delimiters that can be used in pmatch expressions.
 */
interface pmatch_word_delimiter {
    /**
     *
     * Check that items separated pmatch expressions are in the right order
     * and / or proximity to be matched validly. Do not need to check that the two words are not
     * the same.
     *
     * @param array $phrase the words that are being matched
     * @param array $wordsmatched index no of words that have been matched so far
     * @param integer $wordtotry word we want to know if it is in the right position to match
     * @param pmatch_phrase_level_options $phraseleveloptions
     * @return bool
     */
    public function valid_match($phrase, $wordsmatched, $wordtotry, $phraseleveloptions);

    /**
     *
     * A hook to override allow any word order in word delimiter that precedes a phrase.
     */
    public function allow_any_word_order_in_adjacent_phrase($allowanywordorder);
    /**
     *
     * Does this word delimiter also require intervening words between matched word to not be
     * matched by other words in expression?
     * @return bool
     */
    public function also_match_intervening_words();
}

/**
 * Interface for pmatch matchers that can match a single character or multiple characters.
 */
interface pmatch_can_match_char {
    /**
     * Can possibly match a char.
     * @param string $char a character
     * @return bool successful match?
     */
    public function match_char($char);
}
/**
 * Interface for pmatch matchers that can match multiple characters or no characters.
 */
interface pmatch_can_match_multiple_or_no_chars {
    /**
     * Can possibly match some characters.
     * @param string $chars some characters to match
     * @return bool successful match?
     */
    public function match_chars($chars);
}
/**
 * Interface for pmatch matchers that can match a word or phrase.
 */
interface pmatch_can_match_word {
    /**
     * Can possibly match a word.
     * @param array $word a word
     * @param pmatch_word_level_options $wordleveloptions
     * @return bool successful match?
     */
    public function match_word($word, $wordleveloptions);
}
/**
 * Interface for pmatch matchers that can match a phrase.
 */
interface pmatch_can_match_phrase {
    /**
     *
     * Can possibly match a phrase - can accept more than one word at once.
     * @param array $phrase array of words
     * @param pmatch_phrase_level_options $phraseleveloptions
     * @param pmatch_word_level_options $wordleveloptions
     * @return bool successful match?
     */
    public function match_phrase($phrase, $phraseleveloptions, $wordleveloptions);

}
/**
 * Interface for pmatch matchers that can match the whole expression.
 */
interface pmatch_can_match_whole_expression {
    /**
     * Can possibly match the whole expression.
     *
     * @param array $phrase array of words
     * @return bool successful match?
     */
    public function match_whole_expression($words);

}
/**
 * Interface for pmatch matchers that can contribute to the length of a phrase.
 */
interface pmatch_can_contribute_to_length_of_phrase {
    /**
     *
     * How many words can this phrase match? Minimum and max.
     * @param pmatch_phrase_level_options $phraseleveloptions
     * @return array with two values 0=> minimum and 1=> maximum. Values are the same if only
     *                    number of words possible. Maximum is null if no max.
     */
    public function can_match_len($phraseleveloptions);

}
/**
 * Base class for all matcher items.
 */
abstract class pmatch_matcher_item {
    /** @var pmatch_interpreter_item */
    protected $interpreter;
    /** @var pmatch_options */
    protected $externaloptions;
    /**
     *
     * Constructor normally called by pmatch_interpreter_item->get_matcher method
     * @param pmatch_interpreter_item $interpreter
     * @param pmatch_options $externaloptions
     */
    public function __construct($interpreter, $externaloptions) {
        $this->interpreter = $interpreter;
        $this->externaloptions = $externaloptions;
    }

    /**
     * Used for testing purposes. To make sure type and type of contents is as expected.
     */
    public function get_type() {
        $typeobj = new stdClass();
        $typeobj->name = $this->get_type_name($this);
        $typeobj->codefragment = $this->interpreter->get_code_fragment();
        return $typeobj;
    }
    /**
     * Get the type name of this matcher item.
     *
     * @param object $object the object to get the type name for
     * @return string the type name
     */
    public function get_type_name($object) {
        return core_text::substr(get_class($object), 15);
    }
}
/**
 * Base class for matcher items that have subcontents.
 */
abstract class pmatch_matcher_item_with_subcontents extends pmatch_matcher_item {

    /** @var pmatch_interpreter_item_with_subcontents */
    protected $subcontents = [];

    /** @var pmatch_word_level_options */
    protected $wordleveloptions;

    /** @var pmatch_phrase_level_options */
    protected $phraseleveloptions;

    /**
     * Create a tree of matcher items.
     *
     * @param pmatch_interpreter_item_with_subcontents $interpreter
     */
    public function __construct($interpreter, $externaloptions) {
        parent::__construct($interpreter, $externaloptions);
        $interpretersubcontents = $interpreter->get_subcontents();
        foreach ($interpretersubcontents as $interpretersubcontent) {
            $this->subcontents[] = $interpretersubcontent->get_matcher($externaloptions);
        }
    }
    /**
     * Used for testing purposes. To make sure type and type of contents is as expected.
     */
    public function get_type() {
        $typeobj = new stdClass();
        $typeobj->name = $this->get_type_name($this);
        $typeobj->codefragment = $this->interpreter->get_code_fragment();
        $typeobj->subcontents = [];
        foreach ($this->subcontents as $subcontent) {
            $typeobj->subcontents[] = $subcontent->get_type();
        }
        return $typeobj;
    }
    /**
     *
     * This property controls whether extra words are matched on the beginning and end of a phrase
     * when the extra words option is enabled for the expression.
     * @var bool
     */
    protected $greedyphrasematch = false;

    /**
     * This function checks if the phrase can be matched by this matcher item.
     *
     * @param array $phrase Array of words
     * @param pmatch_phrase_level_options $phraseleveloptions
     * @param pmatch_word_level_options $wordleveloptions
     * @return bool Successfully matched?
     */
    public function match_phrase($phrase, $phraseleveloptions, $wordleveloptions) {
        $this->phraseleveloptions = $phraseleveloptions;
        $this->wordleveloptions = $wordleveloptions;
        list($phraseminlength, $phrasemaxlength) =
                            $this->can_match_len($phraseleveloptions);
        if (count($phrase) < $phraseminlength) {
            return false;
        }
        if ((!$this->phraseleveloptions->get_allow_extra_words())
                && (!is_null($phrasemaxlength)) && (count($phrase) > $phrasemaxlength)) {
            return false;
        }
        return $this->check_match_phrase_branch($phrase);
    }

    /**
     *
     * Used to check for a match to a phrase of words. Code shared by phrase in a phrase list as
     * well as for the whole expression in match_options. This is a recursive function that can be
     * started by just calling it with the phrase to check and leaving other params as the default.
     *
     * @param array $phrase
     * @param integer $itemtotry
     * @param integer $wordtotry
     * @param array $wordsmatched
     * @return bool found a match?
     */
    protected function check_match_phrase_branch($phrase, $itemtotry = 0, $wordtotry = 0,
                                                                        $wordsmatched = []) {
        if ($wordtotry >= count($phrase)) {
            return false;
        }
        // Is this a valid item to try to match?
        if (!isset($this->subcontents[$itemtotry - 1])) {
            $shallwetry = true;
        } else {
            $lastsub = $this->subcontents[$itemtotry - 1];
            if ($lastsub->valid_match($phrase, $wordsmatched,
                                      $wordtotry, $this->phraseleveloptions) &&
                                      (!in_array($wordtotry, $wordsmatched, true))) {
                $shallwetry = true;
            } else {
                $shallwetry = false;
            }
        }
        // See if we can match this word to next subcontents item.
        if ($shallwetry &&
                        $this->subcontents[$itemtotry]->match_word($phrase[$wordtotry],
                                                                    $this->wordleveloptions)) {
            // We found a match for one word.
            $wordsmatchedwithnewword = $wordsmatched;
            if ((count($wordsmatched) > 0) && isset($this->subcontents[$itemtotry - 1])
                        && $this->subcontents[$itemtotry - 1]->also_match_intervening_words()) {
                // We need to mark all words since last match as matched too,
                // for some separator types.
                $lastwordmatched = $wordsmatched[count($wordsmatched) - 1];
                $wordno = $lastwordmatched + 1;
                while ($wordno < $wordtotry) {
                    $wordsmatchedwithnewword[] = $wordno;
                    $wordno++;
                }
            }
            $wordsmatchedwithnewword[] = $wordtotry;
            if ($itemtotry == count($this->subcontents) - 1) {
                // Last item matched.
                if (count($wordsmatchedwithnewword) == count($phrase) ||
                                        $this->phraseleveloptions->get_allow_extra_words()) {
                    // All words matched or words are left but extra words are allowed.
                    return true;
                }
            } else {
                // Item matched, find next item to try to match.
                if ($this->phraseleveloptions->get_allow_any_word_order()) {
                    $nextwordtotry = 0;
                } else {
                    $nextwordtotry = $wordtotry + 1;
                }
                // Not reached the end of this branch, continue following branches down and
                // return true if we find a branch which finds a complete match.
                if ($this->check_match_phrase_branch($phrase, $itemtotry + 2, $nextwordtotry,
                                                                    $wordsmatchedwithnewword)) {
                    return true;
                }
            }
        }
        // See if we can match these next few words as a phrase to next subcontents item.
        if ($shallwetry && $this->subcontents[$itemtotry] instanceof pmatch_can_match_phrase) {
            // Calculate min and max phrase lengths given the epxression and the length of phrase.
            list($phraseminlength, $phrasemaxlength) =
                    $this->subcontents[$itemtotry]->can_match_len($this->phraseleveloptions);
            if (is_null($phrasemaxlength)) {
                $phrasemaxlength = count($phrase) - ($wordtotry);
            }
            // Check all possible lengths of phrase.
            for ($plength = $phraseminlength; $plength <= $phrasemaxlength; $plength++) {
                if (in_array(($wordtotry + $plength - 1), $wordsmatched, true)) {
                    break; // Next word has been matched already, stop.
                }
                // Word separator in expression can affect how phrases should be matched.
                $nextphraseleveloptions = clone($this->phraseleveloptions);
                $allowanywordorder = $this->phraseleveloptions->get_allow_any_word_order();
                if (isset($this->subcontents[$itemtotry - 1])) {
                    $separator1 = $this->subcontents[$itemtotry - 1];
                    if (!$separator1->allow_any_word_order_in_adjacent_phrase($allowanywordorder)) {
                        $allowanywordorder = false;
                    }
                }
                if (isset($this->subcontents[$itemtotry + 1])) {
                    $separator2 = $this->subcontents[$itemtotry + 1];
                    if (!$separator2->allow_any_word_order_in_adjacent_phrase($allowanywordorder)) {
                        $allowanywordorder = false;
                    }
                }
                $nextphraseleveloptions->set_allow_any_word_order($allowanywordorder);
                if ($this->subcontents[$itemtotry]->match_phrase(
                                            array_slice($phrase, $wordtotry, $plength),
                                            $nextphraseleveloptions,
                                            $this->wordleveloptions)) {
                    // We matched a phrase.
                    $nextwordtotry = $wordtotry + $plength;
                    if ($allowanywordorder) {
                        $nextwordtotry = 0;
                    }
                    $wordsmatchedandphrasewords = array_merge($wordsmatched,
                                                    range($wordtotry, $wordtotry + $plength - 1));
                    // Was this the last item to match?
                    if (($itemtotry) == count($this->subcontents) - 1) {
                        if (count($wordsmatchedandphrasewords) == count($phrase)) {
                            // Matched all sub items and no more words left.
                            return true;
                        } else if ($this->phraseleveloptions->get_allow_extra_words()) {
                            // Matched all sub items, there are more words left
                            // but extra words are allowed.
                            return true;
                        }
                    } else if ($this->check_match_phrase_branch($phrase, $itemtotry + 2,
                                                    $nextwordtotry, $wordsmatchedandphrasewords)) {
                        return true;
                    }
                    break;
                }
            }
        }

        // Make sure we have a match for the first word if doing a non greedy phrase match and items
        // must match words in order.
        $allowextrawordshere = ($this->greedyphrasematch || count($wordsmatched))
                                            && $this->phraseleveloptions->get_allow_extra_words();
        // If it is allowed try next word also.
        if ($allowextrawordshere || $this->phraseleveloptions->get_allow_any_word_order()) {
            $nextwordtotry = $wordtotry + 1;
            // Try next word.
            if ($this->check_match_phrase_branch($phrase, $itemtotry,
                                                        $nextwordtotry, $wordsmatched)) {
                return true;
            }
        }
        return false;
    }


}
/**
 * This class is used to match the whole expression.
 */
class pmatch_matcher_whole_expression extends pmatch_matcher_item_with_subcontents
                                                implements pmatch_can_match_whole_expression {
    /**
     * This function matches the whole expression against the words.
     *
     * @param array $words the words to match against
     * @return bool true if the whole expression matches the words
     */
    public function match_whole_expression($words) {
        return $this->subcontents[0]->match_whole_expression($words);
    }
}

/**
 * This class is used to match the whole expression but negated.
 */
class pmatch_matcher_not extends pmatch_matcher_item_with_subcontents {
    /**
     * This function matches the whole expression against the words but negates the result.
     *
     * @param array $words the words to match against
     * @return bool true if the whole expression does not match the words
     */
    public function match_whole_expression($words) {
        return !$this->subcontents[0]->match_whole_expression($words);
    }
}

/**
 * This class is used to match a single match item.
 */
class pmatch_matcher_match extends pmatch_matcher_item_with_subcontents {
}

/**
 * This class is used to match a single match item that can match either all or any of its
 * subcontents.
 */
class pmatch_matcher_match_any extends pmatch_matcher_match
                                implements pmatch_can_match_whole_expression {
    /**
     * This function matches the whole expression against the words.
     *
     * @param array $words the words to match against
     * @return bool true if the whole expression matches the words
     */
    public function match_whole_expression($words) {
        foreach ($this->subcontents as $subcontent) {
            if ($subcontent->match_whole_expression($words)) {
                return true;
            }
        }
        return false;
    }
}

/**
 * This class is used to match a single match item that can match all of its subcontents.
 */
class pmatch_matcher_match_all extends pmatch_matcher_match
        implements  pmatch_can_match_whole_expression {
    /**
     * This function matches the whole expression against the words.
     *
     * @param array $words the words to match against
     * @return bool true if the whole expression matches the words
     */
    public function match_whole_expression($words) {
        foreach ($this->subcontents as $subcontent) {
            if (!$subcontent->match_whole_expression($words)) {
                return false;
            }
        }
        return true;
    }
}

/**
 * This class is used to match a single match item that can match options.
 */
class pmatch_matcher_match_options extends pmatch_matcher_match
        implements pmatch_can_match_phrase, pmatch_can_contribute_to_length_of_phrase,
                    pmatch_can_match_whole_expression {
    /** @var pmatch_word_level_options */
    public $wordleveloptions;

    /** @var pmatch_phrase_level_options */
    public $phraseleveloptions;

    /**
     * This property controls whether extra words are matched on the beginning and end of a phrase
     * when the extra words option is enabled for the expression.
     * @var bool
     */
    protected $greedyphrasematch = true;

    /**
     * This function matches the whole expression against the words.
     *
     * @param array $words the words to match against
     * @return bool true if the whole expression matches the words
     */
    public function match_whole_expression($words) {
        return $this->match_phrase($words, $this->interpreter->phraseleveloptions,
                                    $this->interpreter->wordleveloptions);
    }

    /**
     * This function matches a phrase against the options.
     *
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase to match
     * @return array with two values 0=> minimum and 1=> maximum. Values are the same if only
     */
    public function can_match_len($phraseleveloptions) {
        $min = 0;
        $max = 0;
        foreach ($this->subcontents as $subcontent) {
            if ($subcontent instanceof pmatch_can_contribute_to_length_of_phrase) {
                list($subcontentmin, $subcontentmax) =
                        $subcontent->can_match_len($phraseleveloptions);
                if (is_null($subcontentmax) || is_null($max)) {
                    $max = null;
                } else {
                    $max = $max + $subcontentmax;
                }
                $min = $min + $subcontentmin;
            }
        }
        return [$min, $max];
    }
}

/**
 * This class is used to match a list of items that can match a phrase or word.
 */
class pmatch_matcher_or_list extends pmatch_matcher_item_with_subcontents
        implements pmatch_can_match_phrase, pmatch_can_match_word,
                    pmatch_can_contribute_to_length_of_phrase {

    /**
     * This function matches a phrase against the options.
     *
     * @param array $word the word to match
     * @param pmatch_word_level_options $wordleveloptions the word level options
     * @return bool true if the word matches any of the subcontents
     */
    public function match_word($word, $wordleveloptions) {
        foreach ($this->subcontents as $subcontent) {
            if ($subcontent instanceof pmatch_can_match_word &&
                        $subcontent->match_word($word, $wordleveloptions) === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * This function matches a phrase against the options.
     *
     * @param array $phrase the phrase to match
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @param pmatch_word_level_options $wordleveloptions the word level options
     * @return bool true if the phrase matches any of the subcontents
     */
    public function match_phrase($phrase, $phraseleveloptions, $wordleveloptions) {
        foreach ($this->subcontents as $subcontent) {
            if ($subcontent instanceof pmatch_can_match_phrase &&
                    $subcontent->match_phrase($phrase, $phraseleveloptions, $wordleveloptions) === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * This function returns the minimum and maximum number of words that can be matched by this
     * matcher item.
     *
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @return array with two values 0=> minimum and 1=> maximum. Values are the same if only
     *                    number of words possible. Maximum is null if no max.
     */
    public function can_match_len($phraseleveloptions) {
        $min = 1;
        $max = 1;
        foreach ($this->subcontents as $subcontent) {
            if ($subcontent instanceof pmatch_can_contribute_to_length_of_phrase) {
                list($subcontentmin, $subcontentmax) =
                        $subcontent->can_match_len($phraseleveloptions);
                if (is_null($subcontentmax) || is_null($max)) {
                    $max = null;
                } else {
                    $max = max($max, $subcontentmax);
                }
                $min = min($min, $subcontentmin);
            }
        }
        return [$min, $max];
    }
}


/**
 * This is the same as an or_list but with no or_list_phrases.
 */
class pmatch_matcher_synonym extends pmatch_matcher_item_with_subcontents
        implements pmatch_can_match_word, pmatch_can_contribute_to_length_of_phrase {

    /**
     * This property is used to count the number of misspellings used to match the student response
     * word to the pmatch expression.
     * @var int
     */
    protected $usedmisspellings;

    /**
     * Called after running match_word or match_phrase. This function returns the minimum number of
     * mispellings used to match the student response word to the pmatch expression.
     * @return int the number of misspellings found.
     */
    public function get_used_misspellings() {
        return $this->usedmisspellings;
    }

    /**
     * This function matches a word against the options.
     *
     * @param string $word the word to match
     * @param pmatch_word_level_options $wordleveloptions the word level options
     * @return bool true if the word matches any of the subcontents
     */
    public function match_word($word, $wordleveloptions) {
        // phpcs:disable Squiz.ControlStructures.ForLoopDeclaration.SpacingAfterFirst
        // phpcs:disable Squiz.ControlStructures.ForLoopDeclaration.SpacingAfterSecond
        for ($this->usedmisspellings = 0;
                $this->usedmisspellings <= $wordleveloptions->get_misspellings();
                $this->usedmisspellings++) {
        // phpcs:enable Squiz.ControlStructures.ForLoopDeclaration.SpacingAfterFirst
        // phpcs:enable Squiz.ControlStructures.ForLoopDeclaration.SpacingAfterSecond
            foreach ($this->subcontents as $subcontent) {
                $nextwordleveloptions = clone($wordleveloptions);
                $nextwordleveloptions->set_misspellings($this->usedmisspellings);
                if ($subcontent instanceof pmatch_can_match_word &&
                            $subcontent->match_word($word, $nextwordleveloptions) === true) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * This function returns the minimum and maximum number of words that can be matched by this
     *
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @return bool true if the phrase matches any of the subcontents
     */
    public function can_match_len($phraseleveloptions) {
        return [1, 1];
    }
}

/**
 * This class is used to match a single character in a word.
 */
class pmatch_matcher_or_character extends pmatch_matcher_item {

}

/**
 * This class is used to match a single character in a word or phrase.
 */
class pmatch_matcher_or_list_phrase extends pmatch_matcher_item_with_subcontents
            implements pmatch_can_match_phrase, pmatch_can_contribute_to_length_of_phrase {

    /**
     * This function matches a phrase against the options.
     *
     * @param array $phrase the phrase to match
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @param pmatch_word_level_options $wordleveloptions the word level options
     * @return bool true if the phrase matches any of the subcontents
     */
    public function match_phrase($phrase, $phraseleveloptions, $wordleveloptions) {
        foreach ($this->subcontents as $subcontent) {
            if ($subcontent instanceof pmatch_can_match_phrase &&
                    $subcontent->match_phrase($phrase, $phraseleveloptions, $wordleveloptions) === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * This function returns the minimum and maximum number of words that can be matched by this
     * matcher item.
     *
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @return array with two values 0=> minimum and 1=> maximum. Values are the same if only
     *                    number of words possible. Maximum is null if no max.
     */
    public function can_match_len($phraseleveloptions) {
        $subcontent = reset($this->subcontents);
        return $subcontent->can_match_len($phraseleveloptions);
    }
}

/**
 * This class is used to match a phrase that is made up of a number of subcontents.
 */
class pmatch_matcher_phrase extends pmatch_matcher_item_with_subcontents
        implements pmatch_can_match_phrase, pmatch_can_contribute_to_length_of_phrase {

    /**
     * This function matches a phrase against the options.
     *
     * @param array $phrase the phrase to match
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @param pmatch_word_level_options $wordleveloptions the word level options
     * @return bool true if the phrase matches any of the subcontents
     */
    public function can_match_len($phraseleveloptions) {
        $noofwords = (count($this->subcontents) + 1) / 2;
        if ($phraseleveloptions->get_allow_extra_words()) {
            return [$noofwords, null];
        } else {
            return [$noofwords, $noofwords];
        }
    }
}

/**
 * This class is used to match a word delimiter that is a space.
 */
class pmatch_matcher_word_delimiter_space extends pmatch_matcher_item
        implements pmatch_word_delimiter, pmatch_can_contribute_to_length_of_phrase {

    /**
     * This function checks if the word delimiter is valid for the given phrase, wordsmatched,
     *
     * @param $phrase
     * @param $wordsmatched
     * @param $wordtotry
     * @param $phraseleveloptions
     * @return bool
     */
    public function valid_match($phrase, $wordsmatched, $wordtotry, $phraseleveloptions) {
        $lastwordmatched = $wordsmatched[count($wordsmatched) - 1];
        if (!$phraseleveloptions->get_allow_any_word_order() &&
                                                !$phraseleveloptions->get_allow_extra_words()) {
            return ($wordtotry == ($lastwordmatched + 1));
        } else if (!$phraseleveloptions->get_allow_any_word_order()) {
            return ($wordtotry > $lastwordmatched);
        } else {
            return true;
        }
    }

    /**
     * This function returns the minimum and maximum number of words that can be matched by this
     * matcher item.
     *
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @return array with two values 0=> minimum and 1=> maximum. Values are the same if only
     *                    number of words possible. Maximum is null if no max.
     */
    public function can_match_len($phraseleveloptions) {
        if ($phraseleveloptions->get_allow_extra_words()) {
            return [0, null];
        } else {
            return [0, 0];
        }
    }

    /**
     * This function is used to check if this word delimiter allows any word order in adjacent
     * phrases.
     *
     * @param bool $allowanywordorder whether to allow any word order in adjacent phrases
     * @return bool true if any word order is allowed
     */
    public function allow_any_word_order_in_adjacent_phrase($allowanywordorder) {
        return $allowanywordorder;
    }

    /**
     * This function is used to check if this word delimiter also requires intervening words
     * between matched words to not be matched by other words in expression.
     *
     * @return bool true if intervening words are also matched
     */
    public function also_match_intervening_words() {
        return false;
    }
}

/**
 * This class is used to match a word delimiter that is a space but with proximity.
 */
class pmatch_matcher_word_delimiter_proximity extends pmatch_matcher_item
        implements pmatch_word_delimiter, pmatch_can_contribute_to_length_of_phrase {

    /**
     * This function checks if the word delimiter is valid for the given phrase, wordsmatched,
     * and wordtotry.
     *
     * @param array $phrase the phrase to match
     * @param array $wordsmatched the words that have been matched so far
     * @param int $wordtotry the word to try to match
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @return bool true if the match is valid
     */
    public function valid_match($phrase, $wordsmatched, $wordtotry, $phraseleveloptions) {
        $lastwordmatched = $wordsmatched[count($wordsmatched) - 1];
        if ($wordtotry < $lastwordmatched) {
            return false;
        }
        if (($wordtotry - $lastwordmatched) >
                                ($phraseleveloptions->get_allow_proximity_of() + 1)) {
            return false;
        }
        for ($wordno = $lastwordmatched; $wordno < $wordtotry; $wordno++) {
            // Is there a sentence divider (such as a full stop) on the end of this word?
            if ($this->externaloptions->word_has_sentence_divider_suffix($phrase[$wordno])) {
                return false;
            }
            if (($wordno != $lastwordmatched) && in_array($wordno, $wordsmatched, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * This function returns the minimum and maximum number of words that can be matched by this
     * matcher item.
     *
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @return array with two values 0=> minimum and 1=> maximum. Values are the same if only
     *                    number of words possible. Maximum is null if no max.
     */
    public function can_match_len($phraseleveloptions) {
        if ($phraseleveloptions->get_allow_extra_words()) {
            return [0, $phraseleveloptions->get_allow_proximity_of()];
        } else {
            return [0, 0];
        }
    }

    /**
     * This function is used to check if this word delimiter allows any word order in adjacent
     * phrases.
     *
     * @param bool $allowanywordorder whether to allow any word order in adjacent phrases
     * @return bool true if any word order is allowed
     */
    public function allow_any_word_order_in_adjacent_phrase($allowanywordorder) {
        return false;
    }

    /**
     * This function is used to check if this word delimiter also requires intervening words
     * between matched words to not be matched by other words in expression.
     *
     * @return bool true if intervening words are also matched
     */
    public function also_match_intervening_words() {
        return true;
    }
}

/**
 * This class is used to match a number
 */
class pmatch_matcher_number extends pmatch_matcher_item
            implements pmatch_can_match_word {

    /**
     * This function matches a word against the options.
     *
     * @param $word
     * @param $wordleveloptions
     * @return bool
     */
    public function match_word($word, $wordleveloptions) {
        $word = $this->externaloptions->strip_sentence_divider($word);
        if (0 === preg_match('~'.PMATCH_NUMBER.'$~A', $word)) {
            return false;
        } else {
            $studentinput = $this->cleanup_number($word);
            $teacherinput = $this->cleanup_number($this->interpreter->get_code_fragment());
            // Allow for floating-point errors.
            return abs($teacherinput - $studentinput) <= abs(1e-14 * $teacherinput);
        }
    }

    /**
     * Take a string that is part of the pmatch expression or that the student has entered
     * and clean it up into a format that php understands then cast it as a float and
     * return it.
     *
     * @param string $numberstr the number string to clean up
     */
    public function cleanup_number($numberstr) {
        $numberstr = str_replace(' ', '', $numberstr);
        $numberstr = preg_replace('~'.PMATCH_HTML_EXPONENT.'~', 'e$2', $numberstr);
        return (float)$numberstr;
    }
}

/**
 * This class is used to match a word that is made up of a number of subcontents.
 */
class pmatch_matcher_word extends pmatch_matcher_item_with_subcontents
        implements pmatch_can_match_word, pmatch_can_contribute_to_length_of_phrase {

    /**
     * This function check a word options.
     *
     * @param pmatch_word_level_options $wordleveloptions
     * @return pmatch_word_level_options word level options with some options
     *      disabled if word too short.
     */
    protected function check_word_level_options($wordleveloptions) {
        $normalcharactercount = 0;
        $adjustedwordleveloptions = clone($wordleveloptions);
        foreach ($this->subcontents as $subcontent) {
            if (in_array($this->get_type_name($subcontent),
                                ['character_in_word', 'special_character_in_word'])) {
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

    /**
     * This function matches a word against the options.
     *
     * @param string $word the word to match
     * @param pmatch_word_level_options $wordleveloptions the options to use for matching
     * @return bool true if the word matches the options
     */
    public function match_word($word, $wordleveloptions) {
        $word = $this->externaloptions->strip_sentence_divider($word);
        $this->wordleveloptions = $this->check_word_level_options($wordleveloptions);
        if ($this->check_match_branches($word, $this->wordleveloptions->get_misspellings())) {
            return true;
        }
        return false;
    }

    /**
     * Check each character against each item and iterate down branches of possible matches to whole
     * word.
     * @param string $word word to match from student response
     * @param integer $charpos position of character in word we are currently checking for a match
     * @param integer $subcontentno subcontent item to match this character against
     * @param integer $noofcharactertomatch no of characters to match
     * @return bool true if we find one match branch that successfully matches the whole word
     */
    private function check_match_branches($word, $allowmispellings,
                                            $charpos = 0, $subcontentno = 0,
                                            $noofcharactertomatch = 1) {
        $itemslefttomatch = count($this->subcontents) - ($subcontentno + 1);
        $charslefttomatch = core_text::strlen($word) - ($charpos + $noofcharactertomatch);
        // Check if we have gone beyond limit of what can be matched.
        if ($itemslefttomatch < 0) {
            if ($charslefttomatch < 0) {
                return true;
            } else if ($this->wordleveloptions->get_allow_extra_characters()) {
                return true;
            } else if ($this->wordleveloptions->get_misspelling_allow_extra_char()
                                    && ($allowmispellings > $charslefttomatch)) {
                return true;
            } else {
                return false;
            }
        } else if ($charslefttomatch < 0) {
            if ($this->wordleveloptions->get_misspelling_allow_fewer_char()
                                        && ($allowmispellings > $itemslefttomatch)) {
                return true;
            } else if (($this->subcontents[$subcontentno]
                                    instanceof pmatch_can_match_multiple_or_no_chars)
                    && ($this->check_match_branches($word, $allowmispellings,
                                        $charpos + 1, $subcontentno + 1, $noofcharactertomatch))) {
                // No chars left to match but this is a multiple match wild card, so no match needed.
                return true;
            } else {
                return false;
            }
        }
        $thisfragment = core_text::substr($word, $charpos, $noofcharactertomatch);
        if ($this->subcontents[$subcontentno] instanceof pmatch_can_match_multiple_or_no_chars) {
            $thisfragmentmatched = $this->subcontents[$subcontentno]->match_chars($thisfragment);
        } else {
            $thisfragmentmatched = $this->subcontents[$subcontentno]->match_char($thisfragment);
        }

        if (($noofcharactertomatch == 1) &&
                        $this->subcontents[$subcontentno]
                        instanceof pmatch_can_match_multiple_or_no_chars) {
            // Check for the multiple char match wild card matching no characters at
            // the same time as checking for matching one.
            if ($this->check_match_branches($word, $allowmispellings,
                                                $charpos, $subcontentno + 1, 1)) {
                return true;
            }
        }
        if ((!$thisfragmentmatched) && $this->wordleveloptions->get_allow_extra_characters()) {
            if ($this->check_match_branches($word, $allowmispellings,
                                                $charpos + 1, $subcontentno, 1)) {
                return true;
            }
        }
        if ((!$thisfragmentmatched) && ($allowmispellings > 0)) {
            // If there is no match but we can match the next character.
            if ($this->wordleveloptions->get_misspelling_allow_transpose_two_chars()&&
                        ($itemslefttomatch > 0) && ($charslefttomatch > 0)) {
                if (!$this->subcontents[$subcontentno + 1]
                                            instanceof pmatch_can_match_multiple_or_no_chars) {
                    $wordtransposed = core_text::substr($word, 0, $charpos);
                    $wordtransposed .= core_text::substr($word, $charpos + 1, 1);
                    $wordtransposed .= core_text::substr($word, $charpos, 1);
                    $wordtransposed .= core_text::substr($word, $charpos + 2, core_text::strlen($word));

                    if ($this->check_match_branches($wordtransposed, $allowmispellings - 1,
                                                        $charpos, $subcontentno, 1)) {
                        return true;
                    }
                }
            }
            // ... and if there is no match try ignoring this item.
            if ($this->wordleveloptions->get_misspelling_allow_fewer_char()) {
                if ($this->check_match_branches($word, $allowmispellings - 1,
                                                        $charpos, $subcontentno + 1, 1)) {
                    return true;
                }
            }
            // ... and if there is no match try ignoring this character.
            if ($this->wordleveloptions->get_misspelling_allow_extra_char()) {
                if ($this->check_match_branches($word, $allowmispellings - 1,
                                                        $charpos + 1, $subcontentno, 1)) {
                    return true;
                }
            }
            // ... and if there is no match try going on as if it was a match.
            if ($this->wordleveloptions->get_misspelling_allow_replace_char()) {
                if ($this->check_match_branches($word, $allowmispellings - 1,
                                                        $charpos + 1, $subcontentno + 1, 1)) {
                    return true;
                }
            }
        }

        if ($thisfragmentmatched) {
            if ($this->subcontents[$subcontentno]
                                    instanceof pmatch_can_match_multiple_or_no_chars) {
                if ($this->check_match_branches($word, $allowmispellings,
                                                    $charpos,
                                                    $subcontentno, $noofcharactertomatch + 1)) {
                    return true;
                }
                if ($this->check_match_branches($word, $allowmispellings,
                                                    $charpos + $noofcharactertomatch,
                                                    $subcontentno + 1, 1)) {
                    return true;
                }
            } else if ($this->check_match_branches($word, $allowmispellings,
                                                    $charpos + $noofcharactertomatch,
                                                    $subcontentno + 1, 1)) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * This function returns the minimum and maximum length of the phrase that can be matched by
     * this matcher item.
     *
     * @param pmatch_phrase_level_options $phraseleveloptions the phrase level options
     * @return array with two values 0=> minimum and 1=> maximum. Values are the same if only one
     *      length is allowed.
     */
    public function can_match_len($phraseleveloptions) {
        return [1, 1];
    }
}

/**
 * This class is used to match a single character in a word.
 */
class pmatch_matcher_character_in_word extends pmatch_matcher_item
        implements pmatch_can_match_char {

    /**
     * This function matches a single character against the code fragment.
     *
     * @param string $character the character to match
     * @return bool true if the character matches the code fragment
     */
    public function match_char($character) {
        $codefragment = $this->interpreter->get_code_fragment();
        if ($this->externaloptions->ignorecase) {
            return (core_text::strtolower($character) == core_text::strtolower($codefragment));
        } else {
            return ($character == $codefragment);
        }
    }
}

/**
 * This class is used to match a special character in a word.
 */
class pmatch_matcher_special_character_in_word extends pmatch_matcher_item
                                                implements pmatch_can_match_char {
    /**
     * This function matches a single character against the special character.
     *
     * @param string $character the character to match
     * @return bool true if the character matches the special character
     */
    public function match_char($character) {
        $codefragment = $this->interpreter->get_code_fragment();
        return ($character == $codefragment[1]);
    }
}

/**
 * This class is used to match a single character in a word that can be a wildcard.
 */
class pmatch_matcher_wildcard_match_single extends pmatch_matcher_item
                                            implements pmatch_can_match_char {
    /**
     * This function matches a single character against the wildcard.
     *
     * @param string $character the character to match
     * @return bool true if the character matches the wildcard
     */
    public function match_char($character) {
        return true;
    }
}

/**
 * This class is used to match a single character in a word that can be a wildcard and can match
 * multiple characters.
 */
class pmatch_matcher_wildcard_match_multiple
            extends pmatch_matcher_item
            implements pmatch_can_match_multiple_or_no_chars {

    /**
     * This function matches a string of characters against the wildcard.
     *
     * @param string $characters the characters to match
     * @return bool true if the characters match the wildcard
     */
    public function match_chars($characters) {
        return true;
    }
}

<?php
abstract class qtype_pmatch_matcher_item{
    protected $interpreter;

}
abstract class qtype_pmatch_matcher_item_with_subcontents extends qtype_pmatch_matcher_item{

    protected $subcontents = array();
}


class qtype_pmatch_matcher_whole_expression extends qtype_pmatch_matcher_item_with_subcontents{

}
class qtype_pmatch_matcher_not extends qtype_pmatch_matcher_item_with_subcontents{
}
class qtype_pmatch_matcher_match extends qtype_pmatch_matcher_item_with_subcontents{
}
class qtype_pmatch_matcher_match_any extends qtype_pmatch_matcher_match{
}

class qtype_pmatch_matcher_match_all extends qtype_pmatch_matcher_match{
}

class qtype_pmatch_matcher_match_options extends qtype_pmatch_matcher_match{
}
class qtype_pmatch_matcher_or_list extends qtype_pmatch_matcher_item_with_subcontents{
}
class qtype_pmatch_matcher_or_character extends qtype_pmatch_matcher_item{
}
class qtype_pmatch_matcher_or_list_phrase extends qtype_pmatch_matcher_item_with_subcontents{
}


class qtype_pmatch_matcher_phrase extends qtype_pmatch_matcher_item_with_subcontents{
}
class qtype_pmatch_matcher_word_delimiter extends qtype_pmatch_matcher_item{
}
class qtype_pmatch_matcher_word extends qtype_pmatch_matcher_item_with_subcontents{
}
class qtype_pmatch_matcher_character_in_word extends qtype_pmatch_matcher_item{
}
class qtype_pmatch_matcher_special_character_in_word extends qtype_pmatch_matcher_item{
}
class qtype_pmatch_matcher_wildcard_in_word extends qtype_pmatch_matcher_item{
}

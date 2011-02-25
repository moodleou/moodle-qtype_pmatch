<?php
abstract class qtype_pmatch_matcher_item{
    protected $interpreter;
    /**
     * 
     * Constructor normally called by qtype_pmatch_interpreter_item->get_matcher method
     * @param qtype_pmatch_interpreter_item $interpreter
     */
    public function __construct($interpreter){
        $this->interpreter = $interpreter;
    }

    /**
     * 
     * Used for testing purposes. To make sure type and type of contents is as expected.
     */
    public function get_type(){
        $typeobj = new stdClass();
        $typeobj->name = $this->get_type_name($this);
        return $typeobj;
    }
    public function get_type_name($object){
        return substr(get_class($object), 21);
    }
    public function match($studentresponse){}
}
abstract class qtype_pmatch_matcher_item_with_subcontents extends qtype_pmatch_matcher_item{

    protected $subcontents = array();
    
    /**
     * 
     * Create a tree of matcher items.
     * @param qtype_pmatch_interpreter_item_with_subcontents $interpreter
     */
    public function __construct($interpreter){
        parent::__construct($interpreter);
        $interpretersubcontents = $interpreter->get_subcontents();
        foreach ($interpretersubcontents as $interpretersubcontent){
            $this->subcontents[] = $interpretersubcontent->get_matcher();
        }
    }
    /**
     * 
     * Used for testing purposes. To make sure type and type of contents is as expected.
     */
    public function get_type(){
        $typeobj = new stdClass();
        $typeobj->name = $this->get_type_name($this);
        $typeobj->subcontents = array();
        foreach ($this->subcontents as $subcontent){
            $typeobj->subcontents[] = $subcontent->get_type();
        }
        return $typeobj;
    }

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
    public function match($studentresponse, $start){
        $codefragment = $this->interpreter->get_code_fragment();
        if ($studentresponse[$start] == $codefragment){
            return array(true, $start+1);
        } else {
            return array(false, $start);
        }
    }
}
class qtype_pmatch_matcher_special_character_in_word extends qtype_pmatch_matcher_item{
    public function match($studentresponse, $start){
        $codefragment = $this->interpreter->get_code_fragment();
        if ($studentresponse[$start] == $codefragment[2]){
            return array(true, $start+1);
        } else {
            return array(false, $start);
        }
    }
}
class qtype_pmatch_matcher_wildcard_in_word_single extends qtype_pmatch_matcher_item{

    public function match($studentresponse, $start){
        return array(true, $start+1);
    }
}
class qtype_pmatch_matcher_wildcard_in_word_multiple extends qtype_pmatch_matcher_item{
    protected $branchiterator = 0;
    public function more_match_branches_available(){
        return true;
    }
    public function first_branch(){
        $this->branchiterator = 0;
    }
    public function match($studentresponse, $start){
        $endofmatch = $start + 1 + $this->branchiterator;
        $this->branchiterator++;
        return array(true, $endofmatch);
    }
}
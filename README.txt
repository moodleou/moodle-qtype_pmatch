Pattern match question type

This question type was created by Jamie Pratt, working for the Open University
(http://www.open.ac.uk/).

Students can enter a response of up to 20 words, which is then graded by matching
it against vaious model answers expressed using a sophisticated patten
matching algorithm. See http://docs.moodle.org/dev/The_OU_PMatch_algorithm

This question type is has been available since Moodle 2.1+. This version is
compatible with Moodle 2.5+.

You will want to install superscript/subscript editor plugin
(see https://github.com/moodleou/moodle-editor_supsub). This makes the superscript
and subscript options in the question editing form.  If the editor is not
installed the question type can still be used but these options will not be
available.


This question type should be installed like any other Moodle add-on. See
http://docs.moodle.org/25/en/Installing_add-ons.

To install the question type using git, type this command in the root of your
Moodle install

    git clone git://github.com/moodleou/moodle-qtype_pmatch.git question/type/pmatch
    echo /question/type/pmatch/ >> .git/info/exclude
    git clone git://github.com/moodleou/moodle-editor_supsub.git lib/editor/supsub
    echo /lib/editor/supsub/ >> .git/info/exclude

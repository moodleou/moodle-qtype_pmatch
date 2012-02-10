Pattern match question type

This question type was created by Jamie Pratt, working for the Open University
(http://www.open.ac.uk/).

Students can enter a response of up to 20 words, which is then graded by matching
it against vaious model answers that are expressed using a sophisticated patten
matching algorithm. See http://docs.moodle.org/dev/The_OU_PMatch_algorithm

This question type is compatible with Moodle 2.1+.

You will want to install Tim's stripped down tinymce editor that only allows the
use of superscript and subscript see (https://github.com/moodleou/moodle-editor_supsub).
To install this editor using git, type this command in the root of your Moodle install:

    git clone git://github.com/moodleou/moodle-editor_supsub.git lib/editor/supsub

Then add lib/editor/supsub to your git ignore.

If the editor is not installed the question type can still be used but the super
script and sub script options in the question editing form will not be available.

To install the question type using git, type this command in the root of your
Moodle install

    git clone git://github.com/moodleou/moodle-qtype_pmatch.git question/type/pmatch

And add question/type/pmatch to your git ignore.

Alternatively, download the zip from

    https://github.com/moodleou/moodle-qtype_pmatch/zipball/master

unzip it into the question/type folder, and then rename the new folder to pmatch.

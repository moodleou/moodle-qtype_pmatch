You should add pairs of files to this folder like

<name>.rules.txt - contains a single pmatch expression.
<name>.responses.csv - a CSV file with two columns:
    Response - a student response.
    Matches? - whether the response should match the rule, 1 or 0.

There should be a row in the CSV file with the column headings, but the actual
text of the column headings is ignored.

These example files are read by the test script ../testexamples.php, and
used to test the pmatch library.

Tim Hunt, March 2011.

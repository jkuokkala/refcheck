# refcheck
Citation / references list cross-checking utility

Cross-checking utility for in-text citations and reference literature list within one document.
Citation style currently supported: "Generic Style Rules for Linguistics" 
(https://www.eva.mpg.de/linguistics/past-research-resources/resources/generic-style-rules/).

Quite a rudimentary tool for the time being but pretty handy for finding the most obvious missing references.
Currently only checks citations with a year for existence in the References list.

TODO:
- Check citations without a year (as far as possible)
- Check that all References list items are cited in the document

USAGE:
Unix: 
 cat _source.txt | python refcheck.py > _result.txt
Windows:
 Get-Content _source.txt | python refcheck.py > _result.txt
 
Input text (stdin) should be first converted into UTF-8 plain text.

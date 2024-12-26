# refcheck
Citation / references list cross-checking utility

Cross-checking utility for in-text citations and reference literature list within one document.
Works currently with the "Generic Style Rules for Linguistics" 
(https://www.eva.mpg.de/linguistics/past-research-resources/resources/generic-style-rules/) and some similar styles.

The tool is available in two variants: Python (the primarily developed version) 
and PHP (for web applications unable to access Python scripts).
An experimental site using the PHP variant is available at http://refcheck.free.nf/ .

## Usage

Unix:

    cat _source.txt | python refcheck.py > _result.txt

Windows (Powershell):

    Get-Content _source.txt | python refcheck.py > _result.txt
 
Input text (stdin) should be first converted into UTF-8 plain text.

## TODO & Development ideas

- Fix citation author regex patterns to recognize more comprehensive uppercase/lowercase letter ranges
- Support for additional style sheets
- Client-side web version implemented with JavaScript?

## Licence

RefCheck is published under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/4.0/">Creative Commons Attribution-ShareAlike 4.0 International License</a>.

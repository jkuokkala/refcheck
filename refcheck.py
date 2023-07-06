#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""Cross-checking utility for in-text citations and reference list within one document.

 Works currently with the "Generic Style Rules for Linguistics" style
 (https://www.eva.mpg.de/linguistics/past-research-resources/resources/generic-style-rules/).

 Input text (stdin) should be first converted into UTF-8 plain text.

 Author: juha.kuokkala(at)helsinki.fi (2023)
"""

import sys, re
from collections import defaultdict
from argparse import ArgumentParser

# Python 3.7 and newer
sys.stdin.reconfigure(encoding='utf-8')
sys.stdout.reconfigure(encoding='utf-8')

ERRSTR = dict(reflist_not_found_en = 'ERROR: No references list found (abnormally named section heading?)',
              reflist_not_found_fi = 'VIRHE: Lähdeluetteloa ei löydy (epätavallisesti nimetty otsikko?)',
              citation_missing_in_reflist_en = 'Citation "%s" not found in references list',
              citation_missing_in_reflist_fi = 'Viitettä "%s" ei löydy lähdeluettelosta',
              no_citations_for_ref_en = 'References list item "%s" not cited in text',
              no_citations_for_ref_fi = 'Lähdeluettelon teokseen "%s" ei ole viittauksia',
)

def check_references(input, lang='en'):
    """ Cross-checks citations and references in the <input> text sequence.
    
    Returns the noted errors in a list of strings or an empty list if no errors were found.
    """
    
    cits = list()  #  in-text citations (with year and/or page numbers)
    posscits = set()  # possible citations (words that look like reference abbreviations etc.)
    refs = defaultdict(list)  # dict key = first author / title abbreviation, value contains a list of corresponding ref. list items
    uncited = set()  # reference list items that have not (yet) been seen cited in the text; initially, contains the same authorlist/year 2-tuples as the refs lists

    in_refs = False

    for line in input:
        if not in_refs and re.match(r'(References|Lähteet|Kirjallisuus|Allikad|Források)\s*$', line):
            in_refs = True
        elif in_refs and re.match(r'(Appendix|Liite)\b', line):
            in_refs = False
        if in_refs:
            m = re.match(r'''
                    \s*
                    (
                        (?:
                            (?:[^,.=]+)
                            (?:
                                ,
                                (?:
                                    \s+
                                    [^.=]+\b\.?[\])]?
                                )+
                            )?
                        )
                        (?:
                            \s+\&\s+
                            (?:
                                (?:[^,.=]+)
                                (?:
                                    ,
                                    (?:
                                        \s+
                                        [^.=]+\b\.?[\])]?
                                    )+
                                )?
                            )
                        )*
                    )
                    \.\s*
                    (
                        (?:
                            [12][0-9]{3}
                            (?:
                                [–-][0-9]+
                            )?
                            [a-z]?
                            (?:
                                \s+
                                \[
                                [12][0-9]{3}
                                (?:
                                    [–-][0-9]+
                                )?
                                \]
                            )?
                        |
                            \([^)]+\)
                        )
                    )
                    \.
                    ''', line, flags=re.VERBOSE)
            if m:
                auths = m.group(1)
                year = m.group(2)
                if auths:
                    auths = re.sub(r'\s+\([^)]+\)', '', auths)
                    auths = re.split(r'\s+\&\s+', auths)
                    auths = tuple([ tuple(re.split(r',\s*', a)) for a in auths ])
                if year:
                    year = year.strip('()')
                refitem = (auths, year)
                refs[auths[0][0]].append(refitem)
                uncited.add(refitem)
                #print('#ADD: ', repr(auths), repr(year)) ### DEBUG
            else:
                m = re.match(r'\s*([^.=]+)\s+=\s+', line)
                if m:
                    m = re.match(r'\s*((?:(?:[^,.=]+)(?:,\s*(?:[^.=]+))?)(?:\s+\&\s+(?:(?:[^,.=]+)(?:,\s*(?:[^.=]+))?))*)', m.group(1))
                    if m:
                        auths = m.group(1)
                        year = None
                        m = re.search(r'\s+([12][0-9]{3}(?:[–-][0-9]+)?[a-z]?|\([^)]+\))$', auths)
                        if m:
                            year = m.group(1)
                            auths = auths[:-(len(year)+1)]
                        if auths:
                            auths = re.split(r'\s+\&\s+', auths)
                            auths = tuple([ tuple(re.split(r',\s*', a, maxsplit=1)) for a in auths ])
                        if year:
                            year = year.strip('()')
                        refitem = (auths, year)
                        refs[auths[0][0]].append(refitem)
                        uncited.add(refitem)
                        #print('#ADD: ', repr(auths), repr(year)) ### DEBUG
        else:
            # Collect a list of possible yearless, pageless citations for final checking
            posscits.update( re.findall(r'\b([A-ZÅÄÖÜĈŠŽ]\S*[A-ZÅÄÖÜĈŠŽ]\S*)\b(?!\s*(?::|s\.\s*\.v|[0-9]{4}))', line) )
            # Find formally clear citations
            for citcand in re.findall(r'''
                    \b
                    (
                        (?:(?:[Dd][aei]|[Tt]e|[Vv]an[Dd]er|[Vv][ao]n)\s+)?
                        (?:[A-ZÅÄÖÜĈŠŽ]\.\s+)?
                        [A-ZÅÄÖÜĈŠŽ]\S+?
                        (?:
                            \s+(?:et\ al\.?|ym\.?|jt\.?)
                        |
                            (?:\s+\&\s+[A-ZÅÄÖÜĈŠŽ]\S+?)+
                        )?
                    )
                    (?:['’]s)?
                    (
                        \s+
                        (?:
                            \(?
                            (?:
                                [12][0-9]{3}
                                (?:
                                    [–-][0-9]+
                                )?
                                [a-z]?
                                (?:
                                    \s+
                                    \[
                                    [12][0-9]{3}
                                    (?:
                                        [–-][0-9]+
                                    )?
                                    \]
                                )?
                            |
                                (?:
                                    \(?
                                    (?:
                                        forthcoming|in\ press|in\ preparation|tulossa|painossa
                                    )
                                    \)?
                                )
                            )
                            (?<=\w|\])(?!\w)
                            (?:
                                \s*:\s*
                                [0-9]+
                                (?:
                                    [ ,–-]+[0-9]+
                                )*
                            )?
                            (?:
                                ;\s+
                            )?
                        )+
                    |
                        (?:
                            \s*\(?
                            (?:
                                \s*:\s*
                                [0-9]+
                                (?:
                                    [ ,–-]+[0-9]+
                                )*
                            |
                                (?:
                                    \s*:\s*
                                |
                                    \s*s\.\s*v\.\s*
                                ){1,2}
                                [A-῾*-]+
                                (?:
                                    [ ,–-]+[A-῾*-]+
                                )*
                            )
                            (?:
                                ;\s+
                            )?
                        )
                    )''', line, flags=re.VERBOSE):
                #print('#CITCAND: ', repr(citcand)) ### DEBUG
                auths = citcand[0]
                #auths = re.sub(r'[\'’´]s$', '', auths)
                auths = re.split(r'\s+\&\s+', auths)
                for i in range(len(auths)):
                    m = re.match(r'((?:[A-ZÅÄÖÜĈŠŽ][a-zåäöüĉšž]*\.\s*)+)(.*)', auths[i])
                    if m:
                        auths[i] = [ m.group(2), m.group(1).strip() ]
                    elif re.search(r'\s+(?:et\s+al\.|ym\.?|jt\.?|[A-ZÅÄÖÜĈŠŽ][a-zåäöüĉšž]*\.)', auths[i]):
                        auths[i] = re.split(r'\s+(?=et\s+al\.|ym\.?|jt\.?|[A-ZÅÄÖÜĈŠŽ][a-zåäöüĉšž]*\.)', auths[i])
                    else:
                        auths[i] = [ auths[i] ]
                years = re.findall(r'(?:^\s*|;\s*|\s*\()([^;:,.()]*\w[^;:,.()]+)', citcand[1])
                years = [ y.strip() for y in years]
                if years:
                    for year in years:
                        cits.append( (auths, year) )
                        #print('#ADD: ', repr(auths), repr(year)) ### DEBUG
                else:
                    cits.append( (auths, None) )
                    #print('#ADD: ', repr(auths), None) ### DEBUG
                
    errlist = []
    
    if not refs:
        errlist.append(ERRSTR['reflist_not_found_'+lang])
    else:
        for auths, year in sorted(cits):
            found = False
            firstauth = auths[0]
            if firstauth[0] in refs:
                if re.match(r'(et\ al\.?|ym\.?|jt\.?)$', firstauth[-1]):
                    for ref in refs[firstauth[0]]:
                        if len(ref[0]) > 1 and ref[1] == year:
                            found = True
                            uncited.discard(ref)
                            break
                elif firstauth[-1].endswith('.'):
                    gname_pref = firstauth[-1][:-1]
                    for ref in refs[firstauth[0]]:
                        refauth = ref[0][0]
                        if len(refauth) > 1 and refauth[1].startswith(gname_pref) and ref[1] == year:
                            found = True
                            uncited.discard(ref)
                            break
                else:
                    auths_fam = [ a[0] for a in auths ]
                    for ref in refs[firstauth[0]]:
                        refauths_fam = [ a[0] for a in ref[0] ]
                        #print('#auths_fam, year: ', repr(auths_fam), repr(year)) ### DEBUG
                        #print('#refauths_fam, ref[1]: ', repr(refauths_fam), repr(ref[1])) ### DEBUG
                        if refauths_fam == auths_fam and ref[1] == year:
                            found = True
                            uncited.discard(ref)
                            break
            if not found:
                auths_j = ' & '.join([ ' '.join(names) for names in auths ])
                if year:
                    auths_j = auths_j + ' ' + year
                errlist.append(ERRSTR['citation_missing_in_reflist_'+lang] %(auths_j) )

        for auths, year in uncited:
            if not year and len(auths) == 1 and len(auths[0]) == 1:
                if auths[0][0] in posscits:
                    continue
            auths_j = ' & '.join([ names[0] for names in auths ])
            if year:
                auths_j = auths_j + ' ' + year
            errlist.append(ERRSTR['no_citations_for_ref_'+lang] %(auths_j) )
                
    #print('#cits: ', repr(cits)) ### DEBUG
    #print('#refs: ', repr(refs)) ### DEBUG
    #print('#uncited: ', repr(uncited)) ### DEBUG
    #print('#posscits: ', repr(posscits)) ### DEBUG

    return errlist

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # 

def main():
    ap = ArgumentParser(description=__doc__)
    ap.add_argument('-I', '--input', type=str, 
                        help="Input file (default: stdin)")
    ap.add_argument('-l', '--language', choices=['en', 'fi'], default='en', 
                        help="Language for the output texts: en = English, fi = Finnish (default: en)")
    opts = ap.parse_args()

    if opts.input:
        infile = open(opts.input, 'r', encoding='utf-8')
    else:
        infile = sys.stdin

    errors = check_references(infile, lang=opts.language)
    
    for err in errors:
        print(err)

    infile.close()
    return( 1 if errors else 0 )

if __name__ == '__main__':
    main()

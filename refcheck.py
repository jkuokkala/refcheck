#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""Cross-checking utility for in-text citations and reference list within one document.

 Works currently with the "Generic Style Rules for Linguistics" style
 (https://www.eva.mpg.de/linguistics/past-research-resources/resources/generic-style-rules/).

 Input text (stdin) should be first converted into UTF-8 plain text.

 Author: juha.kuokkala(at)helsinki.fi (2023)
 RefCheck is published under a Creative Commons Attribution-ShareAlike 4.0 International License.
"""

import sys, re
from collections import defaultdict
from argparse import ArgumentParser

# Use if needed, e.g. under Windows (only Python 3.7 and newer)
sys.stdin.reconfigure(encoding='utf-8')
sys.stdout.reconfigure(encoding='utf-8')

ERRSTR = dict(reflist_not_found_en = 'ERROR: No references list found (abnormally named section heading?)',
              reflist_not_found_fi = 'VIRHE: Lähdeluetteloa ei löydy (epätavallisesti nimetty otsikko?)',
              citation_missing_in_reflist_en = 'Citation "%s" not found in references list',
              citation_missing_in_reflist_fi = 'Viitettä "%s" ei löydy lähdeluettelosta',
              no_citations_for_ref_en = 'References list item "%s" not cited in text',
              no_citations_for_ref_fi = 'Lähdeluettelon teokseen "%s" ei ole viittauksia',
)

def base_forms(authors):
    """ Takes a list of [surname (othernames?)] lists, returns a modified list with the surnames in
    (guessed, possible) base form. e.g. Swedish "Wesséns" -> "Wessén", Finnish "Itkosen" -> "Itkonen".
    If no modifications would be made, returns an empty list.
    NB! Only Finnish rules are used for the time being. A separate text language parameter could be useful.
    """
    authors_base = list()
    modified = False
    for auth in authors:
        family = auth[0]
        if family.endswith('ksen'):
            fam_b = family[:-4] + 's'
            modified = True
        elif re.search('[aeiouyäö](sen|st[aä])$', family):
            fam_b = family[:-3] + 'nen'
            modified = True
        elif family.endswith('hden'):
            fam_b = family[:-4] + 'hti'
            modified = True
        elif re.search('[^aeiouyäö]in$', family):
            fam_b = family[:-2]
            modified = True
        elif re.search('[aeiouyäö]n$', family):
            fam_b = family[:-1]
            modified = True
        else:
            fam_b = family
            modified = True
        authors_base.append([fam_b] + auth[1:])
    if modified:
        return authors_base
    else:
        return []

def check_references(input, lang='en'):
    """ Cross-checks citations and references in the <input> text sequence.
    
    Returns the noted errors in a list of strings or an empty list if no errors were found.
    """
    
    cits = list()  # in-text citations (with year and/or page numbers)
    # (A set could be used instead of list to store identical citations only once; list has been used mainly to prepare for possible reporting of the exact locations of erroneous citations.)
    posscits = set()  # possible citations (words that look like reference abbreviations etc.)
    refs = defaultdict(list)  # dict key = first author / title abbreviation, value contains a list of corresponding ref. list items
    uncited = set()  # reference list items that have not (yet) been seen cited in the text; initially, contains the same authorlist/year 2-tuples as the refs lists

    in_refs = False

    for line in input:
        if not in_refs and re.match(r'(References|Literature?|Lähteet|Kirjallisuus|Allikad|Források)\s*$', line):
            in_refs = True
        elif in_refs and re.match(r'(Appendix|Liite|(Ala|Loppu)viitteet|(Foot|End)notes)\b', line):
            in_refs = False
        if in_refs:
            m = re.match(r'''
                    \s*
                    (
                        (?:
                            (?:[^,.=0-9]+)
                            (?:
                                ,
                                (?:
                                    \s+
                                    [^.=0-9]+\b\.?[\])]?
                                )+
                            )?
                        )
                        (?:
                            \s+\&\s+
                            (?:
                                (?:[^,.=0-9]+)
                                (?:
                                    ,
                                    (?:
                                        \s+
                                        [^.=0-9]+\b\.?[\])]?
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
                    auths = re.split(r'\s+\&\s+|\s+(?=et\s+al\.|ym\.?|jt\.?|u\.a\.)', auths)
                    auths = tuple([ tuple(re.split(r',\s*', a, maxsplit=1)) for a in auths ])
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
                        year = ''
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
            posscits.update( re.findall(r'\b([A-ZÅÄÖÜČŠŽ]\S*[A-ZÅÄÖÜČŠŽ]\S*)\b(?!\s*(?::|s\.\s*\.v|[0-9]{4}))', line) )
            # Find formally clear citations
            for citcand in re.findall(r'''
                    \b
                    (
                        (?:[A-ZÅÄÖÜČŠŽ]\.\s+)?                             # Given name initial ?
                        (?:(?:[Dd][aei]|[Tt]e|[Vv]an\ [Dd]er|[Vv][ao]n)\s+)? # De, von etc. ?
                        [A-ZÅÄÖÜČŠŽ][A-\u1FFE\'’-]+?                            # Surname / Reference title
                        (?:
                            \s+(?:et\ al\.?|ym\.?|jt\.?|u\.a\.)   # et al. ?
                        |
                            (?:                            # & More & Authors ?
                                \s+\&\s+
                                (?:(?:[Dd][aei]|[Tt]e|[Vv]an\ [Dd]er|[Vv][ao]n)\s+)?
                                [A-ZÅÄÖÜČŠŽ][A-\u1FFE\'’-]+?
                            )+
                        )?
                    )
                    (?:['’]s)?   # Author's ?
                    (
                        \s+
                        (?:
                            \(?
                            (?:
                                [12][0-9]{3}        # Year
                                (?:
                                    [–-][0-9]+      # span ?
                                )?
                                [a-z]?              # 2000a ?
                                (?:
                                    \s+
                                    \[
                                    [12][0-9]{3}    # [original_year] ?
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
                            (?<=\w|\])(?!\w)        # End of word or closing bracket
                            (?:
                                :\s*                # Colon
                                [0-9IVXivx]+        # Page numbers
                                (?:
                                    [ ,–-]+[0-9IVXivx]+
                                )*
                            )?
                            (?:
                                ;\s+
                            )?
                        )+
                    |
                        (?:
                            \s*\(?
                            (?:[0-9]{1,2}|[IVX]+)?  # reference work volume number ?
                            (?:
                                :\s*                # Colon
                                [0-9IVXivx]+        # Page numbers
                                (?:
                                    [ ,–-]+[0-9IVXivx]+
                                )*
                            |
                                :?                  # Colon ?
                                \s*s\.\s*v\.\s*     # s.v.
                                [A-\u1FFE*-]+     # Word consisting of letters (asterisks or hyphens can be included)
                                (?:
                                    [ ,–-]+[A-\u1FFE*-]+ # More words ?
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
                    m = re.match(r'((?:[A-ZÅÄÖÜČŠŽ][a-zåäöüčšž]*\.\s*)+)(.*)', auths[i])
                    if m:
                        auths[i] = [ m.group(2), m.group(1).strip() ]
                    elif re.search(r'\s+(?:et\s+al\.|ym\.?|jt\.?|u\.a\.|[A-ZÅÄÖÜĈŠŽ][a-zåäöüčšž]*\.)', auths[i]):
                        auths[i] = re.split(r'\s+(?=et\s+al\.|ym\.?|jt\.?|u\.a\.|[A-ZÅÄÖÜČŠŽ][a-zåäöüčšž]*\.)', auths[i])
                    else:
                        auths[i] = [ auths[i] ]
                years = re.findall(r'(?:^\s*|;\s*|\s*\()([^;:,.()]*\w[^;:,.()]+)', citcand[1])
                years = [ y.strip() for y in years]
                if years:
                    for year in years:
                        if not re.match(r'[0-9IVX]{1,3}\b', year):
                            cits.append( (auths, year) )
                        #print('#ADD: ', repr(auths), repr(year)) ### DEBUG
                else:
                    cits.append( (auths, '') )
                    #print('#ADD: ', repr(auths), '') ### DEBUG
                
    errlist = []
    
    if not refs:
        errlist.append(ERRSTR['reflist_not_found_'+lang])
    else:
        for auths, year in sorted(cits):
            found = False
            # Test author (list) first as given in text, then "base form" modifications
            for auths_v in [ auths, base_forms(auths) ]:
                if not auths_v:
                    break
                firstauth = auths_v[0]
                if firstauth[0] in refs:
                    if re.match(r'(et\ al\.?|ym\.?|jt\.?|u\.a\.)$', firstauth[-1]):
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
                        auths_fam = [ a[0] for a in auths_v ]
                        for ref in refs[firstauth[0]]:
                            refauths_fam = [ a[0] for a in ref[0] ]
                            #print('#auths_fam, year: ', repr(auths_fam), repr(year)) ### DEBUG
                            #print('#refauths_fam, ref[1]: ', repr(refauths_fam), repr(ref[1])) ### DEBUG
                            if refauths_fam == auths_fam and ref[1] == year:
                                found = True
                                uncited.discard(ref)
                                break
                if found: break
            if not found:
                auths_j = ' & '.join([ ' '.join(names) for names in auths ])
                if year:
                    auths_j = auths_j + ' ' + year
                errlist.append(ERRSTR['citation_missing_in_reflist_'+lang] %(auths_j) )

        for auths, year in sorted(uncited):
            if not year and len(auths) == 1 and len(auths[0]) == 1:
                if auths[0][0] in posscits:
                    continue
            auths_j = ' & '.join([ names[0] for names in auths ])
            if year:
                auths_j = auths_j + ' ' + year
            errlist.append(ERRSTR['no_citations_for_ref_'+lang] %(auths_j) )
            
        # Compact sequences of identical lines into one
        i = 0
        while i < len(errlist):
            count = 1
            while i + 1 < len(errlist) and errlist[i + 1] == errlist[i]:
                del errlist[i]
                count += 1
            if count > 1:
                errlist[i] += ' (x %d)' %(count)
            i += 1

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

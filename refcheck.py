#!/usr/bin/env python3
# -*- coding: utf-8 -*-

#
# Cross-checking utility for in-text citations and reference literature list within one document.
# Works with the "Generic Style Rules for Linguistics" style
# (https://www.eva.mpg.de/linguistics/past-research-resources/resources/generic-style-rules/).
# Quite a rudimentary tool for the time being but pretty handy for finding the most obvious missing references.
#
# Input text (stdin) should be first converted into UTF-8 plain text.
#
# Author: juha.kuokkala@helsinki.fi (2023)
#

import sys, re
from collections import defaultdict

# Python 3.7 and newer
sys.stdin.reconfigure(encoding='utf-8')
sys.stdout.reconfigure(encoding='utf-8')


cits = list()  #  in-text citations
posscits = list()  # possible citations (with no year)
refs = defaultdict(list)  # dict key = first author / title abbreviation, value contains a list of corresponding ref. list items

in_refs = False

for line in sys.stdin:
    if not in_refs and re.match(r'(References|Lähteet|Kirjallisuus)\s*$', line):
        in_refs = True
    if in_refs:
        m = re.match(r'\s*((?:(?:[^,.=]+)(?:,(?:\s+[^.=]+\b\.?[\])]?)+)?)(?:\s+\&\s+(?:(?:[^,.=]+)(?:,(?:\s+[^.=]+\b\.?[\])]?)+)?))*)\.\s*((?:[12][0-9]{3}(?:[–-][0-9]+)?[a-z]?|\([^)]+\)))\.', line)
        if m:
            auths = m.group(1)
            year = m.group(2)
            if auths:
                auths = re.sub(r'\s+\([^)]+\)', '', auths)
                auths = re.split(r'\s+\&\s+', auths)
                auths = [ re.split(r',\s*', a) for a in auths ]
            if year:
                year = year.strip('()')
            refs[auths[0][0]].append( (auths, year) )
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
                        auths = [ re.split(r',\s*', a, maxsplit=1) for a in auths ]
                    if year:
                        year = year.strip('()')
                    refs[auths[0][0]].append( (auths, year) )
                    #print('#ADD: ', repr(auths), repr(year)) ### DEBUG
    else:
        for citcand in re.findall(r'''\b
                (
                   (?:(?:[Dd][aei]|[Tt]e|[Vv]an[Dd]er|[Vv][ao]n)\s+)?
                   (?:[A-ZÅÄÖÜĈŠŽ]\.\s+)?
                   [A-ZÅÄÖÜĈŠŽ]\S+
                   (?:\s+(?:et\ al\.?|ym\.?)
                   |(?:\s+\&\s+[A-ZÅÄÖÜĈŠŽ]\S+)+)?
                )
                (?:['’]s)?
                \s+
                (
                    (?:
                        \(?
                        (?:
                            [12][0-9]{3}
                            (?:
                                [–-][0-9]+
                            )
                            ?[a-z]?|
                            (?:
                                \(?
                                (?:
                                    forthcoming|in\ press|in\ preparation|tulossa|painossa
                                )
                                \)?
                            )
                        )
                        \b
                        (?:
                            \s*:\s*[0-9]+
                            (?:
                                [ ,–-]+[0-9]+
                            )*
                        )?
                        (?:
                            ;\s+
                        )?
                    )+
                    |(?:
                        :
                        (?:
                            \s*:\s*[0-9]+
                            (?:
                                [ ,–-]+[0-9]+
                            )*
                        )?
                        (?:
                            ;\s+
                        )?
                    )
                )''', line, flags=re.VERBOSE):
            auths = citcand[0]
            auths = re.sub(r'[\'’´]s$', '', auths)
            auths = re.split(r'\s+\&\s+', auths)
            for i in range(len(auths)):
                m = re.match(r'((?:[A-ZÅÄÖÜĈŠŽ][a-zåäöüĉšž]*\.\s*)+)(.*)', auths[i])
                if m:
                    auths[i] = [ m.group(2), m.group(1).strip() ]
                elif re.search(r'\s+(?:et\s+al\.|ym\.|[A-ZÅÄÖÜĈŠŽ][a-zåäöüĉšž]*\.)', auths[i]):
                    auths[i] = re.split(r'\s+(?=et\s+al\.|ym\.|[A-ZÅÄÖÜĈŠŽ][a-zåäöüĉšž]*\.)', auths[i])
                else:
                    auths[i] = [ auths[i] ]
            years = re.findall(r'(?:^\s*|;\s*|\s*\()([^;:,.()]*\w[^;:,.()]+)', citcand[1])
            years = [ y.strip() for y in years]
            if years:
                for year in years:
                    cits.append( (auths, year) )
                    #print('#ADD: ', repr(auths), repr(year)) ### DEBUG
            else:
                posscits.append( (auths, None) )
                #print('#ADD: ', repr(auths), None) ### DEBUG
            
if not in_refs:
    print('# ERROR: No references list found (abnormally named section heading?)')
else:
    for auths, year in sorted(cits):
        found = False
        firstauth = auths[0]
        if firstauth[0] in refs:
            if re.match(r'(et\ al\.?|ym\.?)$', firstauth[-1]):
                for ref in refs[firstauth[0]]:
                    if len(ref[0]) > 1 and ref[1] == year:
                        found = True
                        break
            elif firstauth[-1].endswith('.'):
                gname_pref = firstauth[-1][:-1]
                for ref in refs[firstauth[0]]:
                    refauth = ref[0][0]
                    if len(refauth) > 1 and refauth[1].startswith(gname_pref) and ref[1] == year:
                        found = True
                        break
            else:
                auths_fam = [ a[0] for a in auths ]
                for ref in refs[firstauth[0]]:
                    refauths_fam = [ a[0] for a in ref[0] ]
                    #print('#auths_fam, year: ', repr(auths_fam), repr(year)) ### DEBUG
                    #print('#refauths_fam, ref[1]: ', repr(refauths_fam), repr(ref[1])) ### DEBUG
                    if refauths_fam == auths_fam and ref[1] == year:
                        found = True
                        break
        if not found:
            auths_j = ' & '.join([ ' '.join(names) for names in auths ])
            if year:
                auths_j = auths_j + ' ' + year
            print('Citation "%s" not found in references list' %(auths_j) )
    
    # posscits to be checked too...

#print('#cits: ', repr(cits)) ### DEBUG
#print('#refs: ', repr(refs)) ### DEBUG

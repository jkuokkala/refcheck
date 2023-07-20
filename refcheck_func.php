<?php
// refcheck - References list and citations cross-checking utility
// Version 1.0 (converted from the original Python version to PHP)
// by Juha Kuokkala, juha.kuokkala ät helsinki.fi, 2023
// RefCheck is published under a Creative Commons Attribution-ShareAlike 4.0 International License.

$ERRSTR = array(
    'reflist_not_found_en' => 'No references list found (abnormally named section heading?)',
    'reflist_not_found_fi' => 'Lähdeluetteloa ei löydy (epätavallisesti nimetty otsikko?)',
    'citation_missing_in_reflist_en' => 'Citation "%s" not found in references list',
    'citation_missing_in_reflist_fi' => 'Viitettä "%s" ei löydy lähdeluettelosta',
    'no_citations_for_ref_en' => 'References list item "%s" not cited in text',
    'no_citations_for_ref_fi' => 'Lähdeluettelon teokseen "%s" ei ole viittauksia',
);

function base_forms($authors) {
    $authors_base = [];
    $modified = false;
    foreach ($authors as $auth) {
        $family = $auth[0];
        if (substr($family, -4) == 'ksen') {
            $fam_b = substr($family, 0, -4) . 's';
            $modified = true;
		} elseif (preg_match('/[aeiouyäö](sen|st[aä])$/u', $family)) {
            $fam_b = preg_replace('/(sen|st[aä])$/u', 'nen', $family);
            $modified = true;
        } elseif (substr($family, -4) == 'hden') {
            $fam_b = substr($family, 0, -4) . 'hti';
            $modified = true;
		} elseif (preg_match('/[^aeiouyäö]in$/u', $family)) {
            $fam_b = substr($family, 0, -2);
            $modified = true;
		} elseif (preg_match('/[aeiouyäö]n$/u', $family)) {
            $fam_b = substr($family, 0, -1);
            $modified = true;
		} else {
            $fam_b = $family;
            $modified = true;
		}
        $authors_base[] = array_merge([$fam_b], array_slice($auth, 1));
	}
    if ($modified)
        return $authors_base;
    else
        return [];
}

// Forming of string index into $uncited array (Python version uses ref object (tuple structure) reference directly)
function ref_key($ref) {
	[$auths, $year] = $ref;
	$auths_j = implode(' & ', array_column($auths, 0));
	if ($year) {
		$auths_j .= ' ' . $year;
	}
	return $auths_j;
}

function check_references($input, $lang = 'en') {
	global $ERRSTR;
    $cits = array();  // in-text citations (with year and/or page numbers)
    $posscits = array();  // possible citations (words that look like reference abbreviations etc.)
    $refs = array();  // dict: first author / title abbreviation => list of corresponding ref. list items
    $uncited = array();  // reference list items that have not (yet) been seen cited in the text; initially, contains the same authorlist/year 2-tuples as the refs lists
    $in_refs = false;

	foreach ($input as $line) {
		if (!$in_refs && preg_match('/^(References|Literature|Lähteet|Kirjallisuus|Allikad|Források)\s*$/u', $line)) {
			$in_refs = true;
		} elseif ($in_refs && preg_match('/^(Appendix|Liite|(Ala|Loppu)viitteet|(Foot|End)notes)\b/u', $line)) {
			$in_refs = false;
		}
		if ($in_refs) {
			preg_match('/^\s*((?:(?:[^,.=0-9]+)(?:,(?:\s+[^.=0-9]+\b\.?[\])]?)+)?)(?:\s+\&\s+(?:(?:[^,.=0-9]+)(?:,(?:\s+[^.=0-9]+\b\.?[\])]?)+)?))*)\.\s*((?:[12][0-9]{3}(?:[–-][0-9]+)?[a-z]?(?:\s+\[[12][0-9]{3}(?:[–-][0-9]+)?\])?|\([^)]+\)))\./u', $line, $matches, PREG_UNMATCHED_AS_NULL);
			// (See more readable versions of the regexes in Python version)
			if ($matches) {
				$auths = $matches[1];
				$year = $matches[2];
				if ($auths) {
					$auths = preg_replace('/\s+\([^)]+\)/u', '', $auths);
					$auths = preg_split('/\s+\&\s+|\s+(?=et\s+al\.|ym\.?|jt\.?)/u', $auths);
					$auths = array_map(function($a) {
						return preg_split('/,\s*/', $a, 2);
					}, $auths);
				}
				if ($year) {
					$year = trim($year, '()');
				}
				$refitem = array($auths, $year);
				$refs[$auths[0][0]][] = $refitem;
				$uncited[ref_key($refitem)] = $refitem;
                //echo(sprintf('#ADD_REF: "%s" "%s"<br>', print_r($auths, true), $year)); ### DEBUG
			}
			else {
				$m = preg_match('/^\s*([^.=]+)\s+=\s+/', $line, $matches);
				if ($m) {
					$m = preg_match('/^\s*((?:(?:[^,.=]+)(?:,\s*(?:[^.=]+))?)(?:\s+\&\s+(?:(?:[^,.=]+)(?:,\s*(?:[^.=]+))?))*)/u', $matches[1], $matches);
					if ($m) {
						$auths = $matches[1];
						$year = '';
						$m = preg_match('/\s+([12][0-9]{3}(?:[–-][0-9]+)?[a-z]?|\([^)]+\))$/u', $auths, $matches);
						if ($m) {
							$year = $matches[1];
							$auths = substr($auths, 0, -(strlen($year) + 1));
						}
						if ($auths) {
							$auths = preg_split('/\s+\&\s+/', $auths);
							$auths = array_map(function($a) {
								return preg_split('/,\s*/', $a, 2);
							}, $auths);
						}
						if ($year) {
							$year = trim($year, '()');
						}
						$refitem = array($auths, $year);
						$refs[$auths[0][0]][] = $refitem;
						$uncited[ref_key($refitem)] = $refitem;
						//echo(sprintf('#ADD_REF: "%s" "%s"<br>', print_r($auths, true), $year)); ### DEBUG
					}
				}
			}
		}
		else {
            // Collect a list of possible yearless, pageless citations for final checking
			preg_match_all('/\b([A-ZÅÄÖÜČŠŽ]\S*[A-ZÅÄÖÜČŠŽ]\S*)\b(?!\s*(?::|s\.\s*\.v|[0-9]{4}))/u', $line, $matches);
			$newposscits = array_combine( array_map( function($a) {
							return (string) $a;
						}, $matches[1] ), $matches[1] );
			$posscits = array_merge($posscits, $newposscits);
			//$posscits = array_unique($posscits);
            
			// Find formally clear citations
			preg_match_all('/\b((?:(?:[Dd][aei]|[Tt]e|[Vv]an\ [Dd]er|[Vv][ao]n)\s+)?(?:[A-ZÅÄÖÜČŠŽ]\.\s+)?[A-ZÅÄÖÜČŠŽ][A-\x{1FFE}\'’-]+?(?:\s+(?:et\ al\.?|ym\.?|jt\.?)|(?:\s+\&\s+(?:(?:[Dd][aei]|[Tt]e|[Vv]an\ [Dd]er|[Vv][ao]n)\s+)?[A-ZÅÄÖÜČŠŽ][A-\x{1FFE}\'’-]+?)+)?)(?:[\'’]s)?(\s+(?:\(?(?:[12][0-9]{3}(?:[–-][0-9]+)?[a-z]?(?:\s+\[[12][0-9]{3}(?:[–-][0-9]+)?\])?|(?:\(?(?:forthcoming|in\ press|in\ preparation|tulossa|painossa)\)?))(?<=\w|\])(?!\w)(?::\s*[0-9IVXivx]+(?:[ ,–-]+[0-9IVXivx]+)*)?(?:;\s+)?)+|(?:\s*\(?(?:[0-9]{1,2}|[IVX]+)?(?::\s*[0-9IVXivx]+(?:[ ,–-]+[0-9IVXivx]+)*|:?\s*s\.\s*v\.\s*[A-\x{1FFE}*-]+(?:[ ,–-]+[A-\x{1FFE}*-]+)*)(?:;\s+)?))/u', $line, $citcands, PREG_SET_ORDER);
			// (See more readable versions of the regexes in Python version)
			foreach ($citcands as $citcand) {
				$auths = $citcand[1];
				$auths = preg_split('/\s+\&\s+/', $auths);
				foreach ($auths as $i => $auth) {
					if (preg_match('/^((?:[A-ZÅÄÖÜČŠŽ][a-zåäöüčšž]*\.\s*)+)(.*)/u', $auth, $m)) {
						$auths[$i] = array($m[2], trim($m[1]));
					} elseif (preg_match('/\s+(?:et\s+al\.|ym\.?|jt\.?|[A-ZÅÄÖÜČŠŽ][a-zåäöüčšž]*\.)/u', $auth)) {
						$auths[$i] = preg_split('/\s+(?=et\s+al\.|ym\.?|jt\.?|[A-ZÅÄÖÜČŠŽ][a-zåäöüčšž]*\.)/u', $auth);
					} else {
						$auths[$i] = array($auth);
					}
				}
				preg_match_all('/(?:^\s*|;\s*|\s*\()([^;:,.()]*\w[^;:,.()]+)/u', $citcand[2], $years);
				$years = array_map('trim', $years[1]);
				if (!empty($years)) {
					foreach ($years as $year) {
                        if (! preg_match('/^[0-9IVX]{1,3}\b/u', $year)) {
							$cits[] = array($auths, $year);
							#echo(sprintf('#ADD_CIT: "%s" "%s"<br>', print_r($auths, true), $year)); ### DEBUG
						}
					}
				} else {
					$cits[] = array($auths, '');
					#echo(sprintf('#ADD_CIT: "%s" "%s"<br>', print_r($auths, true), $year)); ### DEBUG
				}
			}
		}			
	}

    #echo "<br/>\n<b>#uncited (init.):</b> ", var_dump($uncited); ### DEBUG
	// $uncited = array_unique($uncited);
	$errlist = [];
	if (empty($refs)) {
		$errlist[] = $ERRSTR['reflist_not_found_'.$lang];
	} else {
		usort($cits, function($a, $b) { return ref_key($a) <=> ref_key($b); });
		foreach ($cits as [$auths, $year]) {
			$found = false;
            # Test author (list) first as given in text, then "base form" modifications
            foreach ([$auths, base_forms($auths)] as $auths_v) {
                if (empty($auths_v))
                    break;
				$firstauth = $auths_v[0];
				if (array_key_exists($firstauth[0], $refs)) {
					if (preg_match('/^(et\ al\.?|ym\.?|jt\.?)$/u', $firstauth[count($firstauth)-1])) {
						foreach ($refs[$firstauth[0]] as $ref) {
							if (count($ref[0]) > 1 && $ref[1] == $year) {
								$found = true;
								unset($uncited[ref_key($ref)]);
								break;
							}
						}
					} elseif (substr($firstauth[count($firstauth)-1], -1) == '.') {
						$gname_pref = substr($firstauth[count($firstauth)-1], 0, -1);
						foreach ($refs[$firstauth[0]] as $ref) {
							$refauth = $ref[0][0];
							if (count($refauth) > 1 && strpos($refauth[1], $gname_pref) === 0 && $ref[1] == $year) {
								$found = true;
								unset($uncited[ref_key($ref)]);
								break;
							}
						}
					} else {
						$auths_fam = array_column($auths_v, 0);
						foreach ($refs[$firstauth[0]] as $ref) {
							$refauths_fam = array_column($ref[0], 0);
							if ($refauths_fam == $auths_fam && $ref[1] == $year) {
								$found = true;
								unset($uncited[ref_key($ref)]);
								break;
							}
						}
					}
				}
                if ($found) break;
			}
			if (! $found) {
				$auths_j = implode(' & ', array_map(function($names) {
					return implode(' ', $names);
				}, $auths));
				if ($year) {
					$auths_j .= ' ' . $year;
				}
				$errlist[] = sprintf($ERRSTR['citation_missing_in_reflist_'.$lang], $auths_j);
			}
		}
	}
	ksort($uncited);
	foreach ($uncited as [$auths, $year]) {
		if (! $year && count($auths) == 1 && count($auths[0]) == 1) {
			if (in_array($auths[0][0], $posscits)) {
				continue;
			}
		}
		$auths_j = implode(' & ', array_column($auths, 0));
		if ($year) {
			$auths_j .= ' ' . $year;
		}
		$errlist[] = sprintf($ERRSTR['no_citations_for_ref_'.$lang], $auths_j);
	}
	// Compact sequences of identical lines into one
	$i = 0;
	while ($i < count($errlist)) {
		$count = 1;
		while ($i + 1 < count($errlist) && $errlist[$i + 1] == $errlist[$i]) {
			array_splice($errlist, $i, 1);
			$count += 1;
		}
		if ($count > 1) {
			$errlist[$i] .= ' (x ' . $count . ')';
		}
		$i += 1;
	}
    #echo "<br/>\n<b>#cits:</b> "; var_dump($cits); ### DEBUG
    #echo "<br/>\n<b>#refs:</b> ", var_dump($refs); ### DEBUG
    #echo "<br/>\n<b>#uncited:</b> ", var_dump($uncited); ### DEBUG
    #echo "<br/>\n<b>#posscits:</b> "; var_dump($posscits); ### DEBUG

	return $errlist;
}

?>

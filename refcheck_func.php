<!-- refcheck - References list and citations cross-checking utility -->
<!-- Version 0.1, 2023-07-10 (converted from the original Python version to PHP) -->
<!-- Juha Kuokkala, juha.kuokkala ät helsinki.fi -->


<?php
$ERRSTR = array(
    'reflist_not_found_en' => 'No references list found (abnormally named section heading?)',
    'reflist_not_found_fi' => 'Lähdeluetteloa ei löydy (epätavallisesti nimetty otsikko?)',
    'citation_missing_in_reflist_en' => 'Citation "%s" not found in references list',
    'citation_missing_in_reflist_fi' => 'Viitettä "%s" ei löydy lähdeluettelosta',
    'no_citations_for_ref_en' => 'References list item "%s" not cited in text',
    'no_citations_for_ref_fi' => 'Lähdeluettelon teokseen "%s" ei ole viittauksia',
);

function check_references($input, $lang = 'en') {
	global $ERRSTR;
    $cits = array();  //  in-text citations (with year and/or page numbers)
    $posscits = array();  // possible citations (words that look like reference abbreviations etc.)
    $refs = array();  ** dict: first author / title abbreviation => list of corresponding ref. list items
    $uncited = array();  // reference list items that have not (yet) been seen cited in the text; initially, contains the same authorlist/year 2-tuples as the refs lists
    $in_refs = false;

	foreach ($input as $line) {
		if (!$in_refs && preg_match('/^(References|Lähteet|Kirjallisuus|Allikad|Források)\s*$/u', $line)) {
			$in_refs = true;
		} elseif ($in_refs && preg_match('/^(Appendix|Liite|(Ala|Loppu)viitteet|(Foot|End)notes)\b/u', $line)) {
			$in_refs = false;
		}
		if ($in_refs) {
			preg_match('/^\s*((?:(?:[^,.=0-9]+)(?:,(?:\s+[^.=0-9]+\b\.?[\])]?)+)?)(?:\s+\&\s+(?:(?:[^,.=0-9]+)(?:,(?:\s+[^.=0-9]+\b\.?[\])]?)+)?))*)\.\s*((?:[12][0-9]{3}(?:[–-][0-9]+)?[a-z]?(?:\s+\[[12][0-9]{3}(?:[–-][0-9]+)?\])?|\([^)]+\)))\./u', $line, $matches, PREG_UNMATCHED_AS_NULL);
			if ($matches) {
				$auths = $matches[1];
				$year = $matches[2];
				if ($auths) {
					$auths = preg_replace('/\s+\([^)]+\)/', '', $auths);
					$auths = preg_split('/\s+\&\s+|\s+(?=et\s+al\.|ym\.?|jt\.?)/', $auths);
					$auths = array_map(function($a) {
						return preg_split('/,\s*/', $a);
					}, $auths);
				}
				if ($year) {
					$year = trim($year, '()');
				}
				$refitem = array($auths, $year);
				$refs[$auths[0][0]][] = $refitem;
				$uncited[(string)$refitem] = $refitem;
                //echo(sprintf('#ADD_REF: "%s" "%s"<br>', print_r($auths, true), $year)); ### DEBUG
			}
			else {
				$m = preg_match('/^\s*([^.=]+)\s+=\s+/', $line, $matches);
				if ($m) {
					$m = preg_match('/^\s*((?:(?:[^,.=]+)(?:,\s*(?:[^.=]+))?)(?:\s+\&\s+(?:(?:[^,.=]+)(?:,\s*(?:[^.=]+))?))*)/', $matches[1], $matches);
					if ($m) {
						$auths = $matches[1];
						$year = '';
						$m = preg_match('/\s+([12][0-9]{3}(?:[–-][0-9]+)?[a-z]?|\([^)]+\))$/', $auths, $matches);
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
						$uncited[(string)$refitem] = $refitem;
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
			preg_match_all('/\b((?:(?:[Dd][aei]|[Tt]e|[Vv]an[Dd]er|[Vv][ao]n)\s+)?(?:[A-ZÅÄÖÜČŠŽ]\.\s+)?[A-ZÅÄÖÜČŠŽ]\S+?(?:\s+(?:et\al\.?|ym\.?|jt\.?)|(?:\s+\&\s+[A-ZÅÄÖÜČŠŽ]\S+?)+)?)(?:[\'’]s)?(\s+(?:\(?(?:[12][0-9]{3}(?:[–-][0-9]+)?[a-z]?(?:\s+\[[12][0-9]{3}(?:[–-][0-9]+)?\])?|(?:\(?(?:forthcoming|in\press|in\preparation|tulossa|painossa)\)?))(?<=\w|\])(?!\w)(?:\s*:\s*[0-9]+(?:[,–-]+[0-9]+)*)?(?:;\s+)?)+|(?:\s*\(?(?:\s*:\s*[0-9]+(?:[,–-]+[0-9]+)*|(?:\s*:\s*|\s*s\.\s*v\.\s*){1,2}[A-῾*-]+(?:[,–-]+[A-῾*-]+)*)(?:;\s+)?))/u', $line, $citcands, PREG_SET_ORDER);
			foreach ($citcands as $citcand) {
				$auths = $citcand[1];
				$auths = preg_split('/\s+\&\s+/', $auths);
				foreach ($auths as $i => $auth) {
					if (preg_match('/^((?:[A-ZÅÄÖÜČŠŽ][a-zåäöüčšž]*\.\s*)+)(.*)/', $auth, $m)) {
						$auths[$i] = array($m[2], trim($m[1]));
					} elseif (preg_match('/\s+(?:et\s+al\.|ym\.?|jt\.?|[A-ZÅÄÖÜČŠŽ][a-zåäöüčšž]*\.)/', $auth)) {
						$auths[$i] = preg_split('/\s+(?=et\s+al\.|ym\.?|jt\.?|[A-ZÅÄÖÜČŠŽ][a-zåäöüčšž]*\.)/u', $auth);
					} else {
						$auths[$i] = array($auth);
					}
				}
				preg_match_all('/(?:^\s*|;\s*|\s*\()([^;:,.()]*\w[^;:,.()]+)/u', $citcand[2], $years);
				$years = array_map('trim', $years[1]);
				if (!empty($years)) {
					foreach ($years as $year) {
						$cits[] = array($auths, $year);
						#echo(sprintf('#ADD_CIT: "%s" "%s"<br>', print_r($auths, true), $year)); ### DEBUG
					}
				} else {
					$cits[] = array($auths, '');
					#echo(sprintf('#ADD_CIT: "%s" "%s"<br>', print_r($auths, true), $year)); ### DEBUG
				}
			}
		}			
	}

	// $uncited = array_unique($uncited);
	$errlist = [];
	if (empty($refs)) {
		$errlist[] = $ERRSTR['reflist_not_found_'.$lang];
	} else {
		//sort($cits, SORT_STRING);
		usort($cits, function($a, $b) { return [$a[0][0][0], $a[1]] <=> [$b[0][0][0], $b[1]]; });
		foreach ($cits as [$auths, $year]) {
			$found = false;
			$firstauth = $auths[0];
			if (array_key_exists($firstauth[0], $refs)) {
				if (preg_match('/^(et\ al\.?|ym\.?|jt\.?)$/', $firstauth[count($firstauth)-1])) {
					foreach ($refs[$firstauth[0]] as $ref) {
						if (count($ref[0]) > 1 && $ref[1] == $year) {
							$found = true;
							unset($uncited[array_search($ref, $uncited)]);
							break;
						}
					}
				} elseif (substr($firstauth[count($firstauth)-1], -1) == '.') {
					$gname_pref = substr($firstauth[count($firstauth)-1], 0, -1);
					foreach ($refs[$firstauth[0]] as $ref) {
						$refauth = $ref[0][0];
						if (count($refauth) > 1 && strpos($refauth[1], $gname_pref) === 0 && $ref[1] == $year) {
							$found = true;
							unset($uncited[array_search($ref, $uncited)]);
							break;
						}
					}
				} else {
					$auths_fam = array_column($auths, 0);
					foreach ($refs[$firstauth[0]] as $ref) {
						$refauths_fam = array_column($ref[0], 0);
						if ($refauths_fam == $auths_fam && $ref[1] == $year) {
							$found = true;
							unset($uncited[array_search($ref, $uncited)]);
							break;
						}
					}
				}
			}

			if (!$found) {
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
	// Compact sequences of identical lines into one
	foreach ($uncited as [$auths, $year]) {
		if (!$year && count($auths) == 1 && count($auths[0]) == 1) {
			if (in_array($auths[0][0], $posscits)) {
				continue;
			}
		}
		$auths_j = implode(' & ', array_map(function($names) {
			return $names[0];
		}, $auths));
		if ($year) {
			$auths_j .= ' ' . $year;
		}
		$errlist[] = sprintf($ERRSTR['no_citations_for_ref_'.$lang], $auths_j);
	}
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
	return $errlist;
}


?>

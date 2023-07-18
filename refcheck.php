<?php
header("Content-type: text/html; charset=utf-8");

$lang = "";
$content = "";
$addcontent = "";

if (isset($_GET['lang'])) {
	$lang = $_GET['lang'];
} elseif (isset($_POST["lang"])) {
	$lang = $_POST["lang"];
} elseif (isset($_COOKIE["lang"])) {
	$lang = $_COOKIE["lang"];
}
if (!in_array($lang, ['en','fi'])) {
	$lang = "fi";
}
setcookie('lang', $lang, time() + (86400 * 365 * 5), "/"); // expiration time 5y

if (isset($_POST["content"])) {
	$content = $_POST["content"];
}
if (isset($_POST["addcontent"])) {
	$addcontent = $_POST["addcontent"];
}
?>
<!-- refcheck - Web interface for RefCheck, References list and citations cross-checking utility -->
<!-- Version 1.0 by juha.kuokkala ät helsinki.fi, 2023 -->

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>RefCheck - Cross-checking utility for in-text citations and references list</title>
    <!-- <link rel="icon" type="image" href="images/logo.png"> -->

    <!-- load stylesheets -->
	<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="css/bootstrap.min.css">                                      <!-- Bootstrap style -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php

$UISTR = array(
    'main_heading_en' => 'RefCheck – Cross-checking utility for in-text citations and references list within one document',
    'main_heading_fi' => 'RefCheck – Lähdeviitteiden ja lähdeluettelon ristiintarkastustyökalu',
    'main_heading_note_en' => 'Currently only supports the <a href="https://www.eva.mpg.de/linguistics/past-research-resources/resources/generic-style-rules/">Generic Style Rules for Linguistics</a> and similar enough stylesheets.',
    'main_heading_note_fi' => 'Tukee toistaiseksi vain <a href="https://www.eva.mpg.de/linguistics/past-research-resources/resources/generic-style-rules/">Generic Style Rules for Linguistics</a> -tyyliä ja muita samanlaista lähdeviitemuotoilua käyttäviä tyylejä.',
    'input_heading_en' => 'Paste your document text here, including References list:',
    'input_heading_fi' => 'Kopioi tähän dokumenttisi teksti, sisältäen lähdeluettelon:',
    'add_input_heading_en' => 'Footnotes and other additional text with citations can be pasted here:',
    'add_input_heading_fi' => 'Tähän voi kopioida alaviitteet ja muut erilliset lähdeviitteitä sisältävät tekstinosat:',
    'submit_button_en' => 'Check Citations against References List',
    'submit_button_fi' => 'Tarkasta lähdeviitteiden ja -luettelon yhtäpitävyys',
    'reset_button_en' => 'Clear form fields',
    'reset_button_fi' => 'Tyhjennä lomakkeen tiedot',
    'result_head_ok_en' => 'All references seem to be OK! :)️',
    'result_head_ok_fi' => 'Kaikki viittaukset näyttäisivät olevan kunnossa! :)️',
    'result_head_nok_en' => 'Some problems were found (NB: the tool may not recognize references in more special formats; check also the punctuation of References list):',
    'result_head_nok_fi' => 'Joitakin ongelmia löytyi (työkalu ei ehkä tunnista erikoisemman muotoisia viitteitä; tarkista myös lähdeluettelon välimerkitys):',
    'footer_en' => '<a href="about.php">About / Open Source</a> -- • -- <a href="privacy.php">Privacy</a>',
    'footer_fi' => '<a href="about.php">Tietoja / Lähdekoodi</a> -- • -- <a href="privacy.php">Yksityisyys</a>',
);

include('refcheck_func.php');


echo "<div class=\"maincontent\">\n";

echo "<form action=\"#res\" method=\"post\">\n";

echo "<div class=\"lang-select\">\n";
echo "<label for=\"lang\">Language:</label>\n";
echo "<select name=\"lang\" id=\"lang\" onchange=\"this.form.submit()\">";
echo "<option value=\"en\"" . ($lang == 'en' ? " selected" : "") . ">English</option>";
echo "<option value=\"fi\"" . ($lang == 'fi' ? " selected" : "") . ">Suomi</option>";
echo "</select>\n";
echo "</div>\n";


echo "<h2>{$UISTR['main_heading_'.$lang]}</h2>\n";
echo "<div class=\"heading-note\">{$UISTR['main_heading_note_'.$lang]}</div>";
echo "<hr/>\n";

echo "<div class=\"ref-form\">\n";
echo "<div class=\"input-heading\">{$UISTR['input_heading_'.$lang]}</div>\n<textarea name=\"content\" width=\"500\" height=\"500\">$content</textarea>\n<br/><br/>\n";
echo "<div class=\"input-heading\">{$UISTR['add_input_heading_'.$lang]}</div>\n<textarea name=\"addcontent\" width=\"500\" height=\"500\">$addcontent</textarea>\n<br/><br/>\n";
echo "<button type=\"submit\" class=\"refch-button refch-button-wide\">{$UISTR['submit_button_'.$lang]}</button>\n";
echo "</div>\n";
echo "</form><hr/>\n";

if ($content != "")
{
	echo "<div class=\"proc-warnings\">\n";
	$content .= "\nFootnotes\n" . $addcontent;
	$content = explode("\n", $content);
	$output = check_references($content, $lang);
	echo "</div>\n";
	
	if (empty($output))
	{
		echo "<div id=\"res\" class=\"result-head-ok\">{$UISTR['result_head_ok_'.$lang]}</div>\n<hr/>\n";
	}
	else
	{
		echo "<div id=\"res\" class=\"result-head-nok\">{$UISTR['result_head_nok_'.$lang]}</div>\n<hr/>\n";
		echo "<div class=\"trans\">\n";
		foreach ($output as $outline) {
			echo "<div>$outline</div>\n";
		}
		echo "</div>\n";

		echo "<br/><form action=\"/\">\n";
		echo "<button type=\"submit\" class=\"clear-button\">{$UISTR['reset_button_'.$lang]}</button>\n";
		echo "</form>\n";
	}
}

echo "</div>";

echo "<div class=\"footer\">{$UISTR['footer_'.$lang]}</div>";

?>

</body>
</html>

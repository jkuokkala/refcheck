<?php
header("Content-type: text/html; charset=utf-8");

$lang = "";

if (isset($_GET['lang'])) {
	$lang = $_GET['lang'];
} elseif (isset($_POST["lang"])) {
	$lang = $_POST["lang"];
} elseif (isset($_COOKIE["lang"])) {
	$lang = $_COOKIE["lang"];
}
if (!in_array($lang, ['en','fi'])) {
	$lang = "en";
}
setcookie('lang', $lang, time() + (86400 * 365 * 5), "/"); // expiration time 5y

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>About - RefCheck - Cross-checking utility for in-text citations and references list</title>
    <!-- <link rel="icon" type="image" href="images/logo.png"> -->

    <!-- load stylesheets -->
	<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="css/bootstrap.min.css">                                      <!-- Bootstrap style -->
    <link rel="stylesheet" href="css/style.css">                                   <!-- Templatemo style -->
</head>

<body>

<?php

$UISTR = array(
    'main_heading_en' => 'RefCheck: About',
    'main_heading_fi' => 'RefCheck: Tietoja',
    'body_content_en' => ''.
		' <p>The current site (service) is an experimental interface for using the RefCheck program.'.
		' No guarantee is given on the usability or availability of the service, and the user takes'.
		' full responsibility of using the service.</p>'.
		' <p>The source code of RefCheck program and its web interface is available in Github at'.
		' <br/><a href="https://github.com/jkuokkala/refcheck">https://github.com/jkuokkala/refcheck</a>.</p>'.
		' <p>RefCheck and this site are created by juha DOT kuokkala AT helsinki DOT fi.'.
		' This small tool has been made for my own need in the first place, but further contributors'.
		' for developing the program are welcomed, if anyone happens to be interested.</p>',
    'body_content_fi' => ''.
		' <p>Tämä verkkosivusto (palvelu) on RefCheck-ohjelman kokeellinen käyttöympäristö.'.
		' Palvelun toimivuudesta ei anneta mitään takuuta, ja jokainen käyttää sitä omalla vastuullaan.</p>'.
		' <p>RefCheck-ohjelman ja sen verkkokäyttöliittymän lähdekoodi on saatavissa Githubissa osoitteessa'.
		' <br/><a href="https://github.com/jkuokkala/refcheck">https://github.com/jkuokkala/refcheck</a>.</p>'.
		' <p>RefCheck-työkalun ja tämän verkkosivuston takana on juha PISTE kuokkala ÄT helsinki PISTE fi.'.
		' Tämä pikku työkalu on syntynyt ensi sijassa omiin tarpeisiini, mutta jos jollain muulla'.
		' on kiinnostusta osallistua ohjelman jatkokehittelyyn, niin kaikin mokomin.</p>',
    'mainpage_link_en' => '<a href="refcheck.php">Back to the main RefCheck page</a>',
    'mainpage_link_fi' => '<a href="refcheck.php">Takaisin RefCheck-pääsivulle</a>',
    'footer_en' => '<a href="about.php">About / Open Source</a> -- • -- <a href="privacy.php">Privacy</a>',
    'footer_fi' => '<a href="about.php">Tietoja / Lähdekoodi</a> -- • -- <a href="privacy.php">Yksityisyys</a>',
);



echo "<div class=\"maincontent\">\n";

echo "<form action=\"?\" method=\"post\">\n";

echo "<div class=\"lang-select\">\n";
echo "<label for=\"lang\">Language:</label>\n";
echo "<select name=\"lang\" id=\"lang\" onchange=\"this.form.submit()\">";
echo "<option value=\"en\"" . ($lang == 'en' ? " selected" : "") . ">English</option>";
echo "<option value=\"fi\"" . ($lang == 'fi' ? " selected" : "") . ">Suomi</option>";
echo "</select>\n";
echo "</div>\n";


echo "<h2>{$UISTR['main_heading_'.$lang]}</h2>\n";
echo "<div class=\"body-text\">{$UISTR['body_content_'.$lang]}</div>";
echo "<div class=\"body-text\">{$UISTR['mainpage_link_'.$lang]}</div>";

echo "</form><hr/>\n";


echo "</div>";

echo "<div class=\"footer\">{$UISTR['footer_'.$lang]}</div>";

?>

</body>
</html>

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

    <title>Privacy - RefCheck - Cross-checking utility for in-text citations and references list</title>
    <!-- <link rel="icon" type="image" href="images/logo.png"> -->

    <!-- load stylesheets -->
	<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="css/bootstrap.min.css">                                      <!-- Bootstrap style -->
    <link rel="stylesheet" href="css/style_trans.css">                                   <!-- Templatemo style -->
</head>

<body>

<?php

$UISTR = array(
    'main_heading_en' => 'RefCheck: Privacy',
    'main_heading_fi' => 'RefCheck: Yksityisyys',
    'body_content_en' => '<h3>Data protection</h3>'.
		'<p>Any data sent to the RefCheck service will not be stored but will be wiped from the server memory immediately after forming an analysis report for the user. Note, though, that using the service through an unsecured http connection, your data will be sent through internet in an unencrypted form.</p>'.
		'<h3>Cookies</h3>'.
		' <p>The service uses a single cookie for storing the language setting of the user.'.
		' Alternatively, the interface language can be chosen by using <b>lang</b> parameter in page address, e.g.'.
		' <a href="refcheck.php?lang=fi">refcheck.php?lang=fi</a>.</p>',
    'body_content_fi' => '<h3>Palveluun lähetettyjen tietojen suoja</h3>'.
		'<p>RefCheck-lomakkeella lähetettyjä tietoja ei tallenneta mitenkään, vaan ne poistuvat palvelimen muistista heti, kun niistä on muodostettu käyttäjälle raportti. Huomaa kuitenkin, että jos/kun käytät palvelua suojaamattomalla http-yhteydellä, tiedot kulkevat avoimessa internetissä salaamattomina.</p>'.
		'<h3>Evästeet</h3>'.
		' <p>Palvelu käyttää evästettä käyttäjän kieliasetuksen tallentamiseen.'.
		' Vaihtoehtoisesti käyttöliittymän kielen voi valita <b>lang</b>-osoiteparametrilla, esim.'.
		' <a href="refcheck.php?lang=fi">refcheck.php?lang=fi</a>.</p>',
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

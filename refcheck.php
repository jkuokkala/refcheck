<!-- refcheck - Web interface for RefCheck, References list and citations cross-checking utility -->
<!-- Version 0.1, 2023-07-10 -->
<!-- Juha Kuokkala, juha.kuokkala ät helsinki.fi -->
<!-- Interface PHP & CSS worked out on the base of Copius code, https://www.copius.eu/ortho.php -->

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
    <link rel="stylesheet" href="css/style_trans.css">                                   <!-- Templatemo style -->
</head>

<body>

<?php

header("Content-type: text/html; charset=utf-8");

include('refcheck_func.php');

$lang = "x";
$content = "";
$addcontent = "";

if (isset($_GET['l']))
{
	$lang = $_GET['l'];
}
else
{
	$lang = "en";
}

if (isset($_POST["content"]))
{
	$content = $_POST["content"];
}
if (isset($_POST["addcontent"]))
{
	$addcontent = $_POST["addcontent"];
}

echo "<div class=\"maincontent\">\n";

echo "<h2  class=\"cop-blue-text cop-section-title cop-margin-b-45\">RefCheck – Cross-checking utility for in-text citations and references list within one document</h2>\n";
echo "<div class=\"heading-note\"> Currently only supports the <a href=\"https://www.eva.mpg.de/linguistics/past-research-resources/resources/generic-style-rules/\">Generic Style Rules for Linguistics</a> and similar enough stylesheets.
</div>";
echo "<hr>";

echo "<form action=\"?l=$lang\" method=\"post\">\n";

// echo "<div width=\"90%\">\";
echo"<div class=\"input-heading\">Paste your document text here, including References list:</div>\n<textarea name=\"content\" width=\"500\" height=\"500\">$content</textarea>\n<br/><br/>\n";
echo"<div class=\"input-heading\">Footnotes and other additional text with citations can be pasted here:</div>\n<textarea name=\"addcontent\" width=\"500\" height=\"500\">$addcontent</textarea>\n<br/><br/>\n";
echo "<button type=\"submit\" class=\"cop-button cop-button-wide\">Check Citations vs. References List</button>\n";
// echo "</div>";
echo "</form><hr>\n";

if ($content != "")
{
	echo "<div class=\"proc-warnings\">\n";
	$content .= "\nFootnotes\n" . $addcontent;
	$content = explode("\n", $content);
	$output = check_references($content, $lang);
	echo "</div>\n";
	
	if (empty($output))
	{
		echo "<div class=\"trans\"><b>All references seem to be OK! :)</b></div>\n<hr>\n";
	}
	else
	{
		// $output = str_replace("\n", "<br/>", $output);
		echo "<div><b>Some problems were found:</b></div>\n<hr>\n";
		echo "<div class=\"trans\">\n";
		foreach ($output as $outline) {
			echo "<div>$outline</div>\n";
		}
		echo "</div>\n";
	}

}

echo "</div>";

?> 






</body>
</html>

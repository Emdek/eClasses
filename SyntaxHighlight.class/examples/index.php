<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<meta name="author" content="Emdek">
<link href="../style.css" rel="stylesheet" type="text/css">
<title>SyntaxHighlight.class :: example</title>
</head>
<body>
<?php
require ('../SyntaxHighlight.class.php');
?>
<h2>Example: CSS</h2>
<?php
echo SyntaxHighlight::highlightString('.highlight 		'."\n".'{    '."\n".'	margin:5px auto;
	padding:3px;
	border:1px #8C8C8C solid;
	font:14px monospace !important;
	line-height:15px !important; '."\n".'	background:white;
}
.highlight < div
{
	width:35px;
	margin:0;
	padding:0 1px;
	border:1px solid #B7B7B7;
	float:left;
	background:#F1F1F1;
}
.highlight .code[id~="test"]
{
	padding:0 2px;
	overflow:auto;
	color:#0000BB;
}
#code:hover
{
	background:url(\'./images/background.png\') no-repeat;
}', 'css');
?>
<h2>Example: SQL</h2>
<?php
echo SyntaxHighlight::highlightString('SELECT "test", SUM("test2") FROM "table" WHERE "name" LIKE \'value%\' ORDER BY "test" DESC;
INSERT INTO "table2" VALUES(\'value\');
CREATE TABLE "table3" ("field" TEXT, "field2" INTEGER(1, 2), "field3" FLOAT DEFAULT 3.3);', 'sql');
?>
<h2>Example: (X)HTML</h2>
<?php
echo SyntaxHighlight::highlightFile('test.html', 'html');
?>
<h2>Example: PHP</h2>
<?php
echo SyntaxHighlight::highlightFile('../SyntaxHighlight.class.php', 'php');
?>
</body>
</html>
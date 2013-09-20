<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<meta name="author" content="Emdek">
<title>SyntaxHighlight.class :: example</title>
<style type="text/css">
.highlight
{
	height:auto;
	margin:5px auto;
	padding:3px;
	overflow:auto;
	border:1px solid #8C8C8C;
	background:#FFF;
}
.highlight *
{
	font:14px \15px monospace !important;
}
.highlight a
{
	text-decoration:none;
}
.highlight a:hover
{
	border-bottom:1px dashed #313197;
}
.highlight .numbers
{
	display:inline-block;
	margin:0;
	padding:0 3px;
	border:1px solid #B7B7B7;
	float:left;
	background:#F1F1F1;
	color:#777;
}
.highlight .code
{
	margin:0;
	padding:0 2px;
	overflow:auto;
	color:#444;
}
.highlight .borders
{
	font-weight:bold;
	background:#D5E8F8;
	color:black;
}
.highlight .comment
{
	font-style:italic;
	color:#929292;
}
.highlight .value
{
	font-style:normal;
	color:#DD0000;
}
.highlight .datatype
{
	color:#FF9900;
}
.highlight .keyword
{
	font-weight:bold !important;
	color:#990099;
}
.highlight .number
{
	color:#095DB1;
}
.highlight .punctuation
{
	color:#F559F5;
}
.highlight .class, .highlight .identify
{
	color:#8C1A8C;
}
.highlight .identify
{
	font-weight:bold;
}
.highlight .pseudoclass
{
	color:#4747FB;
}
.highlight .doctype, .highlight .doctype *
{
	font-style:italic;
	color:#C4D7E7 !important;
}
.highlight .tag
{
	font-weight:bold !important;
	color:#000;
}
.highlight .attribute
{
	color:#098409;
}
.highlight .entity
{
	color:#B08000;
}
.highlight .variable
{
	color:#6666FE;
}
.highlight .object
{
	color:#168A16;
}.highlight .method
{
	color:#820505;
}
.highlight .function
{
	color:#313197;
}
.highlight .control
{
	color:#B6B65E;
}
.highlight .package, .highlight .package *
{
	color:#B6B65E;
}
.highlight .event
{
	color:#D1AAC8;
}
.highlight .preprocessor
{
	color:#399B39;
}
.highlight .documentation
{
	font-style:italic;
	font-weight:bold;
	color:#008000;
}
.highlight .documentationtag
{
	font-style:normal;
	font-weight:bold;
	color:#808080;
}
.highlight .notice
{
	font-style:normal;
	font-weight:bold !important;
	background:#F7E6E6;
	color:#BF0303;
}
.highlight .stray
{
	background:#EE3434;
}
.highlight .whitespace
{
	border-right:1px dotted #B7B7B7;
}
.highlight .whitespace:hover
{
	background:#F3F3F3;
}
.highlight .highlightrange
{
	opacity:0.75;
	background:#DDE3EA;
}
.highlight .switcher
{
	cursor:pointer;
}
</style>
</head>
<body>
<?php
require ('../SyntaxHighlight.class.php');
?>
<h2>Example: CSS</h2>
<?php
echo SyntaxHighlight::highlightFormat(SyntaxHighlight::highlightString('.highlight 		'."\n".'{    '."\n".'	margin:5px auto;
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
}', 'css'), 1);
?>
<h2>Example: SQL</h2>
<?php
echo SyntaxHighlight::highlightFormat(SyntaxHighlight::highlightString('SELECT "test", SUM("test2") FROM "table" WHERE "name" LIKE \'value%\' ORDER BY "test" DESC;
INSERT INTO "table2" VALUES(\'value\');
CREATE TABLE "table3" ("field" TEXT, "field2" INTEGER(1, 2), "field3" FLOAT DEFAULT 3.3);', 'sql'), 1);
?>
<h2>Example: (X)HTML</h2>
<?php
echo SyntaxHighlight::highlightFormat(SyntaxHighlight::highlightFile('test.html', 'html'));
?>
<h2>Example: PHP</h2>
<?php
echo SyntaxHighlight::highlightFormat(SyntaxHighlight::highlightFile('../SyntaxHighlight.class.php', 'php'), 1);
?>
</body>
</html>
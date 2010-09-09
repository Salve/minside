<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="no"
 lang="no" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>
    MinSide::Nyheter -> Legg til bilde
  </title>
<?php
    define('DOKU_BASE', $_REQUEST['dokubase']);
?>
  <meta name="generator" content="MinSide" />
<meta name="robots" content="noindex,nofollow" />
<script type="text/javascript" charset="utf-8" ><!--//--><![CDATA[//><!--
var NS='msbilder';var SIG='Signatur ikke tilgjengelig';var JSINFO = {"id":"msbilder","namespace":"msbilder"};
//--><!]]></script>
<script type="text/javascript" charset="utf-8" src="<?php print DOKU_BASE ?>lib/exe/js.php" ></script>

  <link rel="shortcut icon" href="<?php print DOKU_BASE ?>lib/tpl/simple_sidebar/images/favicon.ico" />

  <style type="text/css">
	div.nyhetimgadd textarea#wiki__text {
		height: 1px;
		width: 1px;
		position: absolute;
		overflow: hidden;
		top: -999px;
	}
  </style>
  </head>

<body onLoad="javascript: openNyhetImgSelect()"></body>
<div class="nyhetimgadd">
<form id="formsubimg" >
	<input id="nyhetidvalue" type="hidden" value="<?php echo $_REQUEST['nyhetid'] ?>">
	<textarea name="wikitext" id="wiki__text" class="edit" type="text" onFocus="submitFormImgSub()">
	</textarea>
</form>

<a href="#" onClick="openNyhetImgSelect()">Trykk her eller skru av popup blocker og prøv på nytt.</a>
</div>
</html>
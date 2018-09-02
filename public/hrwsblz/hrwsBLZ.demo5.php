<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title>Class Demo: hrwsBLZ (PHP5)</title>
<style type="text/css">
	body {
		margin: 0px;
		padding: 0px;
		top: 0px;
		left: 0px;
		font-family: verdana, helvetica, sans-serif;
		font-size: 0.9em;
		color: #000;
		background: #DFE;
	}
	a {
		font-weight: bold;
		text-decoration: underline;
		color: #243;
	}
	a:hover {
		text-decoration: none;
	}
	div.menu {
		position: absolute;
		margin: 0px auto auto 0px;
		padding: 10px;
		width: 180px;
		font-size: 0.8em;
		background: #BBB;
	}
	div.main {
		position: absolute;
		margin: 0px auto auto 200px;
		padding: 10px;
		width: 580px;
	}
	div.title {
		margin: 10px;
		padding: 5px;
		border: 1px solid;
		font-size: 1.5em;
		font-weight:bold;
		background: #FFF;
	}
	div.error {
		margin: 10px;
		padding: 5px;
		font-weight:bold;
		background: #FAA;
	}
	div.info {
		margin: 10px;
		padding: 5px;
		font-weight:bold;
		background: #AFA;
	}
</style>
</head>

<body>
<?php
  $content = "";
	// configuration array for class:
	$conf = array("sqluser" => "",					// MySQL Username
					"sqlpass" => "",				// MySQL Password
					"sqldb" => "",					// MySQL Database
					"sqltable" => "hrv_blz",		// MySQL Databasetable
					"clearb4import" => false);		// clear Databasetable before importing Datasets
	include('hrwsBLZ.class5.php');
	$blz = new hrwsBLZ($conf);
	if ($blz->lasterror != "") {
    $content .= "<div class=\"error\">".$blz->lasterror."</div>\n";
    $blz->lasterror = "";
  }
	$mode = isset($_REQUEST['mode'])?$_REQUEST['mode']:'';
	if ($mode != '') $forlink = "?mode=".$mode;
?>
<div class="menu">
<div class="title">Menu</div>
<a href="?mode=file">import file</a><br />
<a href="?mode=text">import text</a><br />
<a href="?mode=upfile">import update file</a><br />
<a href="?mode=uptext">import update text</a><br />
<a href="?mode=query">query</a><br />
<a href="?mode=verify">verify Kto</a><br />
<a href="?mode=viban">verify IBAN</a><br />
<a href="?mode=vdeiban">verify DE-IBAN</a><br />
<hr />
<a href="?mode=create">create table</a><br />
<a href="?mode=clear">clear table</a><br />
<a href="?mode=drop">drop table</a><br />
<a href="?mode=entries">entries</a><br />
</div>
<div class="main">
<div class="title">hrwsBLZ: Class Demo (PHP5)</div>
<?php
	if (isset($content)) echo $content;
	switch($mode) {
	default:
		echo "Please select a function from the left menu.";
	break;
	case 'file':
		if (isset($_GET['fname'])) {
			if ($blz->blz_importfile($_GET['fname']) !== false)
				echo "<div class=\"info\">file successfully imported</div>\n";
			else echo "<div class=\"error\">".$blz->lasterror."</div>\n";
		} else {
			echo "Filelist:<br>\n";
			$blist = dir('.');
			while ($entry = $blist->read()) {
				if ((!is_dir($entry)) && ($entry != '.') && ($entry != '..') && (strpos($entry, ".txt") !== false))
					echo "<a href=\"".$forlink."&amp;fname=".$entry."\">".$entry."</a><br>\n";
			}
		}
	break;
	case 'text':
		if (isset($_POST['ftext'])) {
			if ($blz->blz_importtext($_POST['ftext']) !== false)
				echo "<div class=\"info\">text successfully imported</div>\n";
			else echo "<div class=\"error\">".$blz->lasterror."</div>\n";
		} else {
			echo "Datatext:<br>\n<form action=\"".$forlink."\" method=\"post\">".
				"<textarea name=\"ftext\" rows=\"15\" cols=\"80\"></textarea><br>\n".
				"<input type=\"Submit\" value=\"import\"></form>";
		}
	break;
	case 'upfile':
		if (isset($_GET['fname'])) {
			if ($blz->blz_importupdatefile($_GET['fname']) !== false)
				echo "<div class=\"info\">file successfully imported</div>\n";
			else echo "<div class=\"error\">".$blz->lasterror."</div>\n";
		} else {
			echo "Update Filelist:<br>\n";
			$blist = dir('.');
			while ($entry = $blist->read()) {
				if ((!is_dir($entry)) && ($entry != '.') && ($entry != '..') && (strpos($entry, ".txt") !== false))
					echo "<a href=\"".$forlink."&amp;fname=".$entry."\">".$entry."</a><br>\n";
			}
		}
	break;
	case 'uptext':
		if (isset($_POST['ftext'])) {
			if ($blz->blz_importupdatetext($_POST['ftext']) !== false)
				echo "<div class=\"info\">text successfully imported</div>\n";
			else echo "<div class=\"error\">".$blz->lasterror."</div>\n";
		} else {
			echo "Update Datatext:<br>\n<form action=\"".$forlink."\" method=\"post\">".
				"<textarea name=\"ftext\" rows=\"15\" cols=\"80\"></textarea><br>\n".
				"<input type=\"Submit\" value=\"import\"></form>";
		}
	break;
	case 'query':
		if (isset($_REQUEST['q'])) {
			if (isset($_REQUEST['n']) && $_REQUEST['n']!="") {
				echo "<div style=\"font-weight:bold;\">Kto-nr ".$_REQUEST['n']." is ".
					($blz->blz_isKtoValid($_REQUEST['n'],$_REQUEST['q'])?"valid":"invalid").
					".</div><br>\n";
			}
			$num = $blz->blz_queryblz($_REQUEST['q']);
			if ($num !== false) {
				echo "Results: ".$num."<br>\n";
				$pos = isset($_GET['pos'])?$_GET['pos']:0;
				$i = 0;
				$foralink = $forlink."&amp;q=".$_REQUEST['q']."&amp;pos=";
				foreach($blz->data as $key => $values) {
					if ($pos == $i) {
						echo "Entry: ".$values['hrz_id']." BLZ: ".$values['hrz_blz']." PZV: ".$values['hrz_pzc']."<br>\n".
							"Name: ".$values['hrz_namelong']."<br>\nTown: ".$values['hrz_zipcode']." ".
							$values['hrz_town']."<br>\n";
					} else {
						echo "Entry: <a href=\"".$foralink.$i."\">".$values['hrz_id']."</a><br>\n";
					}
					$i++;
				}
			} else echo "<div class=\"error\">".$blz->lasterror."</div>\n";
		} else {
			echo "<form action=\"".$forlink."\" method=\"post\">Search bank identification code:\n".
				"<input type=\"Text\" name=\"q\" maxlength=\"10\"><br><br>\n".
				"with Kto-nr. check: <input type=\"Text\" name=\"n\" maxlength=\"12\"><br>\n".
				"<input type=\"Submit\" value=\"search\"></form>\n";
		}
	break;
  case 'verify':
    $b = isset($_REQUEST['b'])?$_REQUEST['b']:"";
    $n = isset($_REQUEST['n'])?$_REQUEST['n']:"";
    $m = isset($_REQUEST['m'])?strtoupper($_REQUEST['m']):"";
    echo "<form action=\"".$forlink."\" method=\"post\">\n".
      "Verify Kto-nr: <input type=\"Text\" name=\"n\" maxlength=\"12\" value=\"".$n."\" />\n".
      "with Method <input type=\"Text\" name=\"m\" maxlength=\"2\" value=\"".$m."\" />\n".
      "<input type=\"Submit\" value=\"verify\"><br />\n".
			"optional BLZ: <input type=\"Text\" name=\"b\" maxlength=\"8\" value=\"".$b."\" />\n".
			" (required for Method 52, 53, B6, C0)</form>\n";
    if ($n<>"" && $m<>"") {
			if ($b <> "") $blz->data[] = array('hrz_blz' => $b);
      echo "<p>Testresult: Kto is ".($blz->blz_ValidateKtoByMethod($n, $m)?"valid":"invalid")."<br />\n";
      if ($blz->lasterror != "") echo "<div class=\"error\">".$blz->lasterror."</div>\n";
    }
  break;
	case 'viban':
		$i = isset($_REQUEST['i'])?$_REQUEST['i']:"";
    echo "<form action=\"".$forlink."\" method=\"post\">\n".
      "Verify IBAN: <input type=\"Text\" name=\"i\" maxlength=\"32\" value=\"".$i."\" />\n".
      "<input type=\"Submit\" value=\"verify\"><br />\n".
			"</form>\n";
		if ($i<>"") {
			echo "<p>Testresult: IBAN is ".($blz->blz_isIBANvalid($i)?"valid":"invalid")."</p>\n";
			if ($blz->lasterror != "") echo "<div class=\"error\">".$blz->lasterror."</div>\n";
		}
	break;
	case 'vdeiban':
		$i = isset($_REQUEST['i'])?$_REQUEST['i']:"";
    echo "<form action=\"".$forlink."\" method=\"post\">\n".
      "Verify German IBAN: <input type=\"Text\" name=\"i\" maxlength=\"32\" value=\"".$i."\" />\n".
      "<input type=\"Submit\" value=\"verify\"><br />\n".
			"</form>\n";
		if ($i<>"") {
			echo "<p>Testresult: IBAN is ".($blz->blz_isIBANvalid($i)?"valid":"invalid").
			", in Kto-Check it is ".($blz->blz_checkKtoFromIBAN($i)?"valid":"invalid")."</p>\n";
			if ($blz->lasterror != "") echo "<div class=\"error\">".$blz->lasterror."</div>\n";
			else {
				echo "Results: ".count($blz->data)."<br>\n";
				$pos = isset($_GET['pos'])?$_GET['pos']:0;
				$i = 0;
				$foralink = $forlink."&amp;i=".$_REQUEST['i']."&amp;pos=";
				foreach($blz->data as $key => $values) {
					if ($pos == $i) {
						echo "Entry: ".$values['hrz_id']." BLZ: ".$values['hrz_blz']." PZV: ".$values['hrz_pzc']."<br>\n".
							"Name: ".$values['hrz_namelong']."<br>\nTown: ".$values['hrz_zipcode']." ".
							$values['hrz_town']."<br>\n";
					} else {
						echo "Entry: <a href=\"".$foralink.$i."\">".$values['hrz_id']."</a><br>\n";
					}
					$i++;
				}
			}
		}
	break;
	case 'create':
		if (isset($_GET['sure'])) {
			if ($blz->blz_createtable()) echo "<div class=\"info\">table successfully created</div>\n";
			else echo "<div class=\"error\">".$blz->lasterror."</div>\n";
		} else echo "Please click <a href=\"".$forlink."&amp;sure=1\">here</a> to create the database table<br>\n";
	break;
	case 'clear':
		if (isset($_GET['sure'])) {
			if ($blz->blz_cleartable()) echo "<div class=\"info\">table successfully cleared</div>\n";
			else echo "<div class=\"error\">".$blz->lasterror."</div>\n";
		} else echo "Please click <a href=\"".$forlink."&amp;sure=1\">here</a> to clear the database table<br>\n";
	break;
	case 'drop':
		if (isset($_GET['sure'])) {
			if ($blz->blz_droptable()) echo "<div class=\"info\">table successfully dropped</div>\n";
			else echo "<div class=\"error\">".$blz->lasterror."</div>\n";
		} else echo "Please click <a href=\"".$forlink."&amp;sure=1\">here</a> to drop the database table<br>\n";
	break;
	case 'entries':
		if ($blz->blz_getnumentries() !== false) echo "Entries in Database: ".$blz->blz_getnumentries();
		else echo "<div class=\"error\">".$blz->lasterror."</div>";
	break;
	}
?>

</div>


</body>
</html>

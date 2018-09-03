<?php
namespace ProDevelopement\GermanBankVerification\Classes;

class hrwsBLZ {
	protected $_cfg = array("sqlhost" => "localhost",		// MySQL Server Hostname
                          "sqluser" => "pb",				// MySQL Username
                          "sqlpass" => "Pentium1.",				// MySQL Password
                          "sqldb" => "pkgKtoBlzDev",					// MySQL Database
                          "sqltable" => "hrv_blz",		// MySQL Databasetable
                          "extconnid" => NULL,			// an external MySQL connect ID
                           "clearb4import" => true);		// clear Databasetable before importing Datasets
	protected $_db = NULL;
  protected $_corrblz = array(    // Array to correct blz pz assignment format: (int-BLZ) => 'pz'
    );
	public $lasterror = ""; 
    public $data = array();
    protected $specResponse = array(
        'status' => false,
        'alert' => 'danger'
    );

	// constructor
	public function __construct($aConfig = NULL) {
		if (($aConfig !== NULL) && is_array($aConfig))
			$this->_cfg = array_merge($this->_cfg, $aConfig);
		if ($this->_cfg['extconnid'] !== NULL)
			$this->_db = &$this->_cfg['extconnid'];
	}
	
	// public public functions
	public function blz_isIBANvalid($aIBAN) {
		$s = $this->blz_alpha2num(substr($aIBAN,4).substr($aIBAN,0,4));
		if ($this->blz_lmod($s) == '01') return true;
		return false;
	}
	
	public function blz_checkKtoFromIBAN($aIBAN) {
		if (substr($aIBAN,0,2)!='DE') {
			$this->specResponse['msg'] = "Kto check from IBAN only for Germany possible.";
			return false;
		}
		if (!$this->blz_isIBANvalid($aIBAN)) return false; // IBAN invalid
		return $this->blz_isKtoValid(substr($aIBAN,12,10), substr($aIBAN,4,8));
	}
	
	public function blz_queryblz($aBLZ = NULL) {
		if ($aBLZ !== NULL) {
			if (!$this->blz_sqlconnect()) return false;
			$sql = "SELECT * FROM ".$this->_cfg['sqltable']." WHERE hrz_blz = '".$this->blz_cleanblz($aBLZ)."'";
			$res = $this->_db->query($sql);
			if ($res !== false) {
				$this->data = array();
				if ($res->num_rows > 0) {
					while ($row = $res->fetch_array(MYSQLI_ASSOC)) $this->data[$row['hrz_id']] = $row;
					return count($this->data);
				} else return 0;
			} else $this->specResponse['msg'] = "wrong bank indentification code submitted";
		} else $this->specResponse['msg'] = "no bank identification code submitted";
		return false;
	}
	// PB: Working
	public function blz_isKtoValid($aKto, $aBLZ = NULL) {
		if ($aBLZ !== NULL) $this->blz_queryblz($this->blz_cleanblz($aBLZ));
		if (count($this->data)==0) {
			$this->specResponse['msg'] = "no bank identification code present";
			return false;
		}
		$bank = current($this->data);
    if (isset($this->_corrblz[$bank['hrz_blz']])) {
      $usrfunc = "blz_kt".$this->_corrblz[$bank['hrz_blz']];
    } else $usrfunc = "blz_kt".$bank['hrz_pzc'];
		if (method_exists($this, $usrfunc))
			return call_user_func(array($this, $usrfunc), $this->blz_cleankto($aKto));
		else $this->specResponse['msg'] = "account number validation method unknown";
		return false;
    }
      
    public function blz_ValidateKtoByMethod($aKto, $aMethod) {
    $usrfunc = "blz_kt".$aMethod;
    if (method_exists($this, $usrfunc)) {
      return call_user_func(array($this, $usrfunc), $this->blz_cleankto($aKto));
    } else $this->specResponse['msg'] = "validation method unknown";
    return false;
  }
	
	public function blz_getnumentries() {
		if ($this->numBLZ !== NULL) return $this->numBLZ;
		if ($this->blz_sqlconnect()) {
			$sql = "SELECT COUNT(*) FROM ".$this->_cfg['sqltable'];
			$res = $this->_db->query($sql);
			if ($res !== false) {
				list($this->numBLZ) = $res->fetch_array(MYSQLI_NUM);
				return $this->numBLZ;
			} else $this->specResponse['msg'] = "could not retrieve data";
		}
		return false;
	}
	
	public function blz_importfile($aFilename = "") {
		if ((trim($aFilename) != "") && file_exists($aFilename)) {
			$this->import = array();
			$fp = fopen($aFilename, "r");
			if ($fp !== false) {
				while (!feof($fp)) $this->import[] = fgets($fp);
			} else $this->specResponse['msg'] = "couldn't open importfile";
			fclose($fp);
			if (count($this->import) > 0) return $this->blz_importarray();
			else $this->specResponse['msg'] = "no entries found";
		} else $this->specResponse['msg'] = "importfile doesn't exist";
		return false;
	}
	
	public function blz_importtext($aText = "") {
		if (trim($aText) != "") {
			$this->import = explode("\n", $aText);
			if (count($this->import) > 0) return $this->blz_importarray();
			else $this->specResponse['msg']("no entries found");
		} else $this->specResponse['msg'] = "no content in text detected";
		return false;
	}
	
	public function blz_importupdatefile($aFilename = "") {
		if ((trim($aFilename) != "") && file_exists($aFilename)) {
			$this->import = array();
			$fp = fopen($aFilename, "r");
			if ($fp !== false) {
				while (!feof($fp)) $this->import[] = fgets($fp);
			} else $this->specResponse['msg'] = "couldn't open importfile";
			fclose($fp);
			if (count($this->import) > 0) return $this->blz_importupdatearray();
			else $this->specResponse['msg'] = "no entries found";
		} else $this->specResponse['msg'] = "importfile doesn't exist";
		return false;
	}
	
	public function blz_importupdatetext($aText = "") {
		if (trim($aText) != "") {
			$this->import = explode("\n", $aText);
			if (count($this->import) > 0) return $this->blz_importupdatearray();
			else $this->specResponse['msg']("no entries found");
		} else $this->specResponse['msg'] = "no content in text detected";
		return false;
	}
	
	protected $import = array();
	protected $importcount = 0;
	protected $numBLZ = NULL;
	
	protected function blz_importarray() {
		if (count($this->import) > 0) {
			if ($this->_cfg['clearb4import'] === true) {
				if (!$this->blz_cleartable()) return false;
			}
			if (!$this->blz_sqlconnect()) return false;
			$this->specResponse['msg'] = "";
			$this->importcount = 0;
			$this->numBLZ = NULL;
			foreach ($this->import as $key => $line) {
				$hrz_id = trim(substr($line, 152, 6));
				$hrz_blz = trim(substr($line, 0, 8));
				$hrz_namelong = trim(substr($line, 9, 58));
				$hrz_nameshort = trim(substr($line, 107, 27));
				$hrz_zipcode = trim(substr($line, 67, 5));
				$hrz_town = trim(substr($line, 72, 35));
				$hrz_own = trim(substr($line, 8, 1));
				$hrz_bbk = 0;
				$hrz_deldate = "0000-00-00 00:00:00";
				$hrz_followid = trim(substr($line, 160, 8));
				$hrz_bic = trim(substr($line, 139, 11));
				$hrz_btxname = '';
				$hrz_pzc = trim(substr($line, 150, 2));
				if ($hrz_id != "") {
					$sql = "INSERT INTO ".$this->_cfg['sqltable']." (hrz_id, hrz_blz, hrz_namelong, hrz_nameshort, ".
						"hrz_zipcode, hrz_town, hrz_own, hrz_bbk, hrz_deldate, hrz_followid, hrz_bic, hrz_btxname, hrz_pzc) ".
						"VALUES ('".$hrz_id."', '".$hrz_blz."', '".$hrz_namelong."', '".$hrz_nameshort."', '".$hrz_zipcode.
						"', '".$hrz_town."', '".$hrz_own."', '".$hrz_bbk."', '".$hrz_deldate."', '".$hrz_followid."', '".
						$hrz_bic."', '".$hrz_btxname."', '".$hrz_pzc."')";
					$res = $this->_db->query($sql);
					if ($res !== false) $this->importcount++;
					else $this->specResponse['msg'] .= "error in line ".($key+1)."<br>";
				}
			}
			if ($this->specResponse['msg'] == "") return $this->importcount;
			else $this->specResponse['msg'] .= $this->importcount." lines successfully imported";
		} else $this->specResponse['msg'] = "no entries found";
		return false;
	}
	
	protected function blz_importupdatearray() {
		if (count($this->import) > 0) {
			if (!$this->blz_sqlconnect()) return false;
			$this->specResponse['msg'] = "";
			$this->importcount = 0;
			$this->numBLZ = NULL;
			foreach ($this->import as $key => $line) {
				$hrz_id = trim(substr($line, 152, 6));
				$hrz_blz = trim(substr($line, 0, 8));
				$hrz_namelong = trim(substr($line, 9, 58));
				$hrz_nameshort = trim(substr($line, 107, 27));
				$hrz_zipcode = trim(substr($line, 67, 5));
				$hrz_town = trim(substr($line, 72, 35));
				$hrz_own = trim(substr($line, 8, 1));
				$hrz_bbk = 0;
				$hrz_deldate = "0000-00-00 00:00:00";
				$hrz_followid = trim(substr($line, 160, 8));
				$hrz_bic = trim(substr($line, 139, 11));
				$hrz_btxname = '';
				$hrz_pzc = trim(substr($line, 150, 2));
				if ($hrz_id != "") {
					$sqli = "INSERT INTO ".$this->_cfg['sqltable']." ( hrz_id, hrz_blz, hrz_namelong, hrz_nameshort, ".
						"hrz_zipcode, hrz_town, hrz_own, hrz_bbk, hrz_deldate, hrz_followid, hrz_bic, ".
						" hrz_btxname ) VALUES ('".$hrz_id."', '".$hrz_blz."', '".$hrz_namelong."', '".$hrz_nameshort.
						"', '".$hrz_zipcode."', '".$hrz_town."', '".$hrz_own."', '".$hrz_bbk."', '".$hrz_deldate."', '".
						$hrz_followid."', '".$hrz_bic."', '".$hrz_btxname."', '".$hrz_pzc."')";
					$sqlu = "UPDATE ".$this->_cfg['sqltable']." SET hrz_blz = '".$hrz_blz."', hrz_namelong = '".
						$hrz_namelong."', hrz_nameshort = '".$hrz_nameshort."', hrz_zipcode = '".$hrz_zipcode."', hrz_town = '".
						$hrz_town."', hrz_own = '".$hrz_own."', hrz_bbk = '".$hrz_bbk."', hrz_deldate = '".$hrz_deldate.
						"', hrz_followid = '".$hrz_followid."', hrz_bic = '".$hrz_bic."', hrz_btxname = '".$hrz_btxname.
						"', hrz_pzc = '".$hrz_pzc."' WHERE hrz_id = '".$hrz_id."'";
					$res = $this->_db->query("SELECT COUNT(*) FROM ".$this->_cfg['sqltable']." WHERE hrz_id = '".$hrz_id."'");
					list($exist) = $res->fetch_array(MYSQLI_NUM);
					if ($exist != 0) $res = $this->_db->query($sqlu);
					else $res = $this->_db->query($sqli);
					if ($res !== false) $this->importcount++;
					else $this->specResponse['msg'] .= "error in line ".($key+1)."<br>";
				}
			}
			if ($this->specResponse['msg'] == "") return $this->importcount;
			else $this->specResponse['msg'] .= $this->importcount." lines successfully imported";
		} else $this->specResponse['msg'] = "no entries found";
		return false;
	}

	protected function blz_sqlconnect() {
		if ($this->_db !== NULL) {
			if ($this->_db->select_db($this->_cfg['sqldb'])) return true;
		} else {
			$this->_db = new mysqli($this->_cfg['sqlhost'], $this->_cfg['sqluser'], $this->_cfg['sqlpass']);
			if ($this->_db !== false) {
				if ($this->_db->select_db($this->_cfg['sqldb'])) return true;
				else $this->specResponse['msg'] = "unable to access database";
			} else $this->specResponse['msg'] = "unable to connect to database";
		}
		return false;
	}
	
	public function blz_cleartable() {
		if ($this->blz_sqlconnect()) {
			$sql = "DELETE FROM `".$this->_cfg['sqltable']."`";
			$res = $this->_db->query($sql);
			$this->numBLZ = NULL;
			if ($res !== false) return true;
			else $this->specResponse['msg'] = "error clearing database table";
		}
	}
	
	public function blz_createtable() {
		if ($this->blz_sqlconnect()) {
			$sql = "CREATE TABLE `".$this->_cfg['sqltable']."` (`hrz_id` INT UNSIGNED NOT NULL , ".
					"`hrz_blz` INT UNSIGNED NOT NULL , ".
					"`hrz_namelong` VARCHAR( 58 ) NOT NULL , `hrz_nameshort` VARCHAR( 27 ) NOT NULL , ".
					"`hrz_zipcode` VARCHAR( 5 ) NOT NULL , `hrz_town` VARCHAR( 35 ) NOT NULL , ".
					"`hrz_own` TINYINT( 1 )  UNSIGNED NOT NULL , `hrz_bbk` TINYINT( 1 ) UNSIGNED NOT NULL , ".
					"`hrz_deldate` DATETIME NOT NULL , `hrz_followid` INT UNSIGNED NOT NULL , ".
					"`hrz_bic` VARCHAR( 11 ) NOT NULL , `hrz_btxname` VARCHAR( 27 ) NOT NULL , `hrz_pzc` char(2) NOT NULL ,".
					" PRIMARY KEY ( `hrz_id` ) ) ENGINE = MyISAM COMMENT = 'hrwsBLZ - Datatable'";
			$res = $this->_db->query($sql);
			if ($res !== false) return true;
			else $this->specResponse['msg'] = "error creating database table";
		}
		return false;
	}
	
	public function blz_droptable() {
		if ($this->blz_sqlconnect()) {
			$sql = "DROP TABLE `".$this->_cfg['sqltable']."`";
			$res = $this->_db->query($sql);
			$this->numBLZ = NULL;
			if ($res !== false) return true;
			else $this->specResponse['msg'] = "error dropping database table";
		}
	}
	
	protected function blz_cleanblz($aBLZ) {
		return str_replace(array(" ","-"), "", $aBLZ);
	}
	
	protected function blz_cleankto($aKto) {
		return substr('0000000000'.strval(str_replace(array(" ","-"),"",$aKto)),-10);
	}
	
	// protected functions for IBAN validation
	protected function blz_alpha2num($aString) {
		$cnv = array(' ' => "");
		for ($i=ord('A'); $i<=ord('Z'); $i++) {$cnv[chr($i)] = (string)($i-ord('A')+10);}
		return strtr(strtoupper($aString), $cnv);
	}

	protected function blz_lmod($aString, $mod=97) {
		while(strlen($aString) > 2) {
			$r = substr('00'.(((int)substr($aString, 0, 9))%$mod),-2);
			$aString = substr($aString, 9);
			if ($aString===false) $aString = $r; else $aString = $r.$aString;
		}
		return $aString;
	}
	
	// protected function Validating kto numbers
	protected function blz_sod ($x) {	// Sum of Digits
		$xStr = strval ($x);
		$res = 0;
		for ($i=0; $i <= strlen($xStr)-1; $i++) $res += intval ($xStr[$i]);
		return $res;
	}
	protected function blz_kt00_var ($ktonr, $weight, $a, $e, $p, $mod=10) {
		$sod = 0;
		for ($i=$e; $i>=$a; $i--) $sod += $this->blz_sod($ktonr[$i]*$weight[($e-$i)]);
		$pz = ($mod-($sod%$mod))%(($mod==7)?$mod:10);
		if (intval($pz)==intval($ktonr[$p])) return true;
		else return false;
	 } // End 00 Variable
	protected function blz_kt00 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		return $this->blz_kt00_var($ktonr, $weight, 0,8,9);
	 } // End 00
	protected function blz_kt01 ($ktonr, $weight = array(3,7,1,3,7,1,3,7,1)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = 10-($sod%10);
		$pz = ($pz==10)?0:$pz;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	} // End 01
	protected function blz_kt02 ($ktonr, $weight = array(2,3,4,5,6,7,8,9,2)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = 11-($sod%11);
		$pz = ($pz == 11)?0:$pz;
		if ($pz == 10) return false;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	} // End 02
	protected function blz_kt03 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		return $this->blz_kt01($ktonr, $weight);
	} // End 03
	protected function blz_kt04 ($ktonr, $weight = array(2,3,4,5,6,7,2,3,4)) {
		return $this->blz_kt02($ktonr, $weight);
	} // End 04
	protected function blz_kt05 ($ktonr, $weight = array(7,3,1,7,3,1,7,3,1)) {
		return $this->blz_kt01($ktonr, $weight);
	} // End 05
	protected function blz_kt06_var( $ktonr, $weight, $a, $e, $p, $mod=11, $rest0=false) {
		$sod = 0;
		for ($i=$e; $i>=$a; $i--) $sod += $ktonr[$i]*$weight[($e-$i)];
		$pz = ($mod-($sod%$mod))%($rest0?$mod:100000); // kt06
		$pz = ($pz>=10)?0:$pz;
		if (intval($pz)==intval($ktonr[$p])) return true;
		else return false;
	}
	protected function blz_kt06 ($ktonr, $weight = array(2,3,4,5,6,7,2,3,4)) {
		return $this->blz_kt06_var($ktonr, $weight, 0,8,9);
	} // End 06
	protected function blz_kt07 ($ktonr, $weight = array(2,3,4,5,6,7,8,9,10)) {
		return $this->blz_kt02($ktonr, $weight);
	}
	protected function blz_kt08 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		if (intval($ktonr >= 60000))
			return $this->blz_kt00($ktonr, $weight);
		else return false;
	}
	protected function blz_kt09 ($ktonr) {
		return true;
	}
	protected function blz_kt10 ($ktonr, $weight = array(2,3,4,5,6,7,8,9,10)) {
		return $this->blz_kt06($ktonr, $weight);
	}
	protected function blz_kt11 ($ktonr, $weight = array(2,3,4,5,6,7,8,9,10)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = 11-($sod%11);
		$pz = ($pz==10)?9:(($pz==11)?0:$pz);
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	} // End 11
	protected function blz_kt12 ($ktonr) {
		return false; // Frei
	} // End 12
	protected function blz_kt13 ($ktonr, $weight = array(2,1,2,1,2,1)) {
		$first = 0;
		while ($first < 2) {
			$first++;
			if ($this->blz_kt00_var($ktonr, $weight, 1,6,7)) return true;
			else $ktonr = substr($ktonr.'00', -10);
		}
		return false;
	}
	protected function blz_kt14 ($ktonr, $weight = array(2,3,4,5,6,7)) {
		$sod = 0;
		for ($i=8; $i>=3; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = 11-($sod%11);
		$pz = ($pz == 11)?0:$pz;
		if ($pz == 10) return false;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt15 ($ktonr, $weight = array(2,3,4,5)) {
		return $this->blz_kt06_var($ktonr, $weight, 5,8,9);
	}
	protected function blz_kt16 ($ktonr, $weight = array(2,3,4,5,6,7,2,3,4)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = 11-($sod%11);
		if (($pz==10) && ($ktonr[9]==$ktonr[8])) return true;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt17 ($ktonr, $weight = array(1,2,1,2,1,2)) {
		$sod = -1;
		for ($i=1; $i<=6; $i++) $sod += $this->blz_sod($ktonr[$i]*$weight[($i-1)]);
		$pz = 10-($sod%11);
		$pz = ($pz==10)?0:$pz;
		if (intval($pz)==intval($ktonr[7])) return true;
		else return false;
	}
	protected function blz_kt18 ($ktonr, $weight = array(3,9,7,1,3,9,7,1,3)) {
		return $this->blz_kt01($ktonr, $weight);
	}
	protected function blz_kt19 ($ktonr, $weight = array(2,3,4,5,6,7,8,9,1)) {
		return $this->blz_kt06($ktonr, $weight);
	}
	protected function blz_kt20 ($ktonr, $weight = array(2,3,4,5,6,7,8,9,3)) {
		return $this->blz_kt06($ktonr, $weight);
	}
	protected function blz_kt21 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += $this->blz_sod($ktonr[$i]*$weight[(8-$i)]);
		while($sod > 9) $sod = $this->blz_sod($sod);
		$pz = 10-$sod;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt22 ($ktonr, $weight = array(3,1,3,1,3,1,3,1,3)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += ($ktonr[$i]*$weight[(8-$i)])%10;
		$pz = (10-($sod%10))%10;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt23 ($ktonr, $weight = array(2,3,4,5,6,7)) {
		$sod = 0;
		for ($i=5; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(5-$i)];
		$pz = 11-($sod%11);
		$pz = ($pz==11)?0:$pz;
		if (($pz==10) && ($ktonr[5]==$ktonr[6])) return true;
		if (intval($pz)==intval($ktonr[6])) return true;
		else return false;
	}
	protected function blz_kt24 ($ktonr, $weight = array(1,2,3,1,2,3,1,2,3)) {
		if (($ktonr[0] >= 3) && ($ktonr[0] <=6)) $ktonr[0]=0;
		if ($ktonr[0] == 9) $ktonr[0]=$ktonr[1]=$ktonr[2]=0;
		for ($start=0; $ktonr[$start]==0; $start++);
		$sod = 0;
		for ($i = $start; $i <= 8; $i++) $sod += (($ktonr[$i]*$weight[($i-$start)])+$weight[($i-$start)])%11;
		$pz = $sod%10;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt25 ($ktonr, $weight = array(2,3,4,5,6,7,8,9)) {
		$sod = 0;
		for($i = 8; $i > 0; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = 11-($sod%11);
		$pz = ($pz == 11)?0:$pz;
		if (($pz == 10) && (($ktonr[1]==8)||($ktonr[1]==9))) $pz=0;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt26 ($ktonr, $weight = array(2,3,4,5,6,7,2)) {
		if (($ktonr[0]==0) && ($ktonr[1]==0)) $ktonr = substr($ktonr.'00',-10);
		$sod = 0;
		for ($i=6; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(6-$i)];
		$pz = 11-($sod%11);
		$pz = ($pz>=10)?0:$pz;
		if (intval($pz)==intval($ktonr[7])) return true;
		else return false;
	}
	protected function blz_m10h ($ktonr) {
		$code = array(1 => array(0,1,5,9,3,7,4,8,2,6), array(0,1,7,6,9,8,3,2,5,4),
							array(0,1,8,4,6,2,9,5,7,3), array(0,1,2,3,4,5,6,7,8,9));
		$sum = 0;
		for ($i = 8; $i >= 0; $i--) $sum += $code[((8-$i)%4)+1][$ktonr[$i]];
		$pz = (10-($sum%10)%10);
		return (($ktonr[9]==$pz)?true:false);
	}
	protected function blz_kt27 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		if ($ktonr[0] == 0) return $this->blz_kt00($ktonr, $weight);
		else return $this->blz_m10h($ktonr);
	}
	protected function blz_kt28 ($ktonr, $weight = array(2,3,4,5,6,7,8)) {
		$sod = 0;
		for ($i=6; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(6-$i)];
		$pz = 11-($sod%11);
		$pz = ($pz>=10)?0:$pz;
		if (intval($pz)==intval($ktonr[7])) return true;
		else return false;
	}
	protected function blz_kt29 ($ktonr) {
		return $this->blz_m10h($ktonr);
	}
	protected function blz_kt30 ($ktonr, $weight = array(2,0,0,0,0,1,2,1,2)) {
		$sod = 0;
		for ($i=0; $i<=8; $i++) $sod += $ktonr[$i]*$weight[$i];
		$pz = 10-($sod%10);
		$pz = ($pz==10)?0:$pz;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt31 ($ktonr, $weight = array(9,8,7,6,5,4,3,2,1)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = $sod%11;
		$pz = ($pz==11)?0:$pz;
		if ($pz == 1) return false;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt32 ($ktonr, $weight = array(2,3,4,5,6,7)) {
		return $this->blz_kt06_var($ktonr, $weight, 3,8,9);
	}
	protected function blz_kt33 ($ktonr, $weight = array(2,3,4,5,6)) {
		return $this->blz_kt06_var($ktonr, $weight, 4,8,9);
	}
	protected function blz_kt34 ($ktonr, $weight = array(2,4,8,5,10,9,7)) {
		return $this->blz_kt28($ktonr, $weight);
	}
	protected function blz_kt35 ($ktonr, $weight = array(2,3,4,5,6,7,8,9,10)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = $sod%11;
		if ($pz == 10) return (($ktonr[9]==$ktonr[8])?true:false);
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt36 ($ktonr, $weight = array(2,4,8,5)) {
		return $this->blz_kt06_var($ktonr, $weight, 5,8,9);
	}
	protected function blz_kt37 ($ktonr, $weight = array(2,4,8,5,10)) {
		return $this->blz_kt33($ktonr, $weight);
	}
	protected function blz_kt38 ($ktonr, $weight = array(2,4,8,5,10,9)) {
		return $this->blz_kt32($ktonr, $weight);
	}
	protected function blz_kt39 ($ktonr, $weight = array(2,4,8,5,10,9,7)) {
		return $this->blz_kt06_var($ktonr, $weight, 2,8,9);
	}
	protected function blz_kt40 ($ktonr, $weight = array(2,4,8,5,10,9,7,3,6)) {
		return $this->blz_kt06($ktonr, $weight);
	}
	protected function blz_kt41 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		if ($ktonr[3] == 9) $ktonr[0]=$ktonr[1]=$ktonr[2] = 0;
		return $this->blz_kt00($ktonr, $weight);
	}
	protected function blz_kt42 ($ktonr, $weight = array(2,3,4,5,6,7,8,9)) {
		return $this->blz_kt06_var($ktonr, $weight, 1,8,9);
	}
	protected function blz_kt43 ($ktonr, $weight = array(1,2,3,4,5,6,7,8,9)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = 10-($sod%10);
		$pz = ($pz==10)?0:$pz;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt44 ($ktonr, $weight = array(2,4,8,5,10,0,0,0,0)) {
		return $this->blz_kt33($ktonr, $weight);
	}
	protected function blz_kt45 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) { // last update for 04.06.2018
		if (($ktonr[0] == 0) || ($ktonr[4] == 1) || /* no check sum if 1st digit is 0 or 5th digit is a 1 or ... */
				($ktonr[0] == 4 && $ktonr[1] == 8) ) { /* ... 1st two digits have the value 48 (new since 04.06.2018) */
			return true;
		} else return $this->blz_kt00 ($ktonr, $weight);
	}
	protected function blz_kt46 ($ktonr, $weight = array(2,3,4,5,6)) {
		return $this->blz_kt06_var($ktonr, $weight, 2,6,7);
	}
	protected function blz_kt47 ($ktonr, $weight = array(2,3,4,5,6)) {
		return $this->blz_kt06_var($ktonr, $weight, 3,7,8);
	}
	protected function blz_kt48 ($ktonr, $weight = array(2,3,4,5,6,7)) {
		return $this->blz_kt06_var($ktonr, $weight, 2,7,8);
	}
	protected function blz_kt49 ($ktonr) {
		if ($this->blz_kt00($ktonr)) return true;
		return $this->blz_kt01($ktonr);
	}
	protected function blz_kt50 ($ktonr, $weight = array(2,3,4,5,6,7)) {
		if ($this->blz_kt06_var($ktonr, $weight, 0,5,6)) return true;
		return $this->blz_kt06_var(substr($ktonr.'000',-10), $weight, 0,5,6);
	}
	protected function blz_kt51 ($ktonr) {	// latest update for 03.06.2013
		if ($ktonr[2] == 9) { // Exception ( digit 3 = 9 )
			if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8), 2,8,9)) return true; // Variant 1
			return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8,9,10), 0,8,9); // Variant 2
		}
		if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7), 3,8,9)) return true; // Method A (06)
		if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6), 4,8,9)) return true; // Method B (33)
		if ($this->blz_kt00_var($ktonr, array(2,1,2,1,2,1), 3,8,9)) return true; // Method C (00, 4..9) new since 03.06.2013
		if (($ktonr[9] >= 7) && ($ktonr[9] <= 9)) return false; // Filter invalid Numbers
		return $this->blz_kt06_var($ktonr, array(2,3,4,5,6), 4,8,9,7); // Final Method D (33 mod7)
	}
	protected function blz_kt52 ($ktonr, $weight = array(2,4,8,5,10,9,7,3,6,1,2,4)) {
		if ($ktonr[0] == 9) return $this->blz_kt20($ktonr);
		$bank = current($this->data);
		while($ktonr[0] == 0) $ktonr = substr($ktonr, 1);
		$pzsoll = $ktonr[1]; $digit = $ktonr[0];
		$ktonr = substr($ktonr, 2);
		while($ktonr[0] == 0) $ktonr = substr($ktonr, 1);
		$ESER = substr($bank['hrz_blz'],-4).$digit.'0'.$ktonr;
		$pzweight = $weight[strlen($ktonr)];
		$sod = 0;
		for ($i = strlen($ESER)-1; $i >= 0; $i--) $sod += $ESER[$i]*$weight[(strlen($ESER)-1-$i)];
		if ( ((($sod%11) + $pzsoll*$pzweight) % 11) == 10) return true;
		else return false;
	}
	protected function blz_kt53 ($ktonr, $weight = array(2,4,8,5,10,9,7,3,6,1,2,4)) {
		if ($ktonr[0] == 9) return $this->blz_kt20($ktonr);
		$bank = current($this->data);
		while($ktonr[0] == 0) $ktonr = substr($ktonr, 1);
		$digit = $ktonr[0]; $T = $ktonr[1]; $pzsoll = $ktonr[2];
		$ktonr = substr($ktonr, 3);
		while($ktonr[0] == 0) $ktonr = substr($ktonr, 1);
		$ESER = substr($bank['hrz_blz'],4,2).$T.substr($bank['hrz_blz'],-1).$digit.'0'.$ktonr;
		$pzweight = $weight[strlen($ktonr)];
		$sod = 0;
		for ($i = strlen($ESER)-1; $i >= 0; $i--) $sod += $ESER[$i]*$weight[(strlen($ESER)-1-$i)];
		if ( ((($sod%11) + $pzsoll*$pzweight) % 11) == 10) return true;
		else return false;
	}
	protected function blz_kt54 ($ktonr, $weight = array(2,3,4,5,6,7,2)) {
		$sod = 0;
		for ($i=8; $i>=2; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = 11-($sod%11); // kt06
		if ($pz>=10) return false;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt55 ($ktonr, $weight = array(2,3,4,5,6,7,8,7,8)) {
		return $this->blz_kt06_var($ktonr, $weight, 0,8,9);
	}
	protected function blz_kt56 ($ktonr, $weight = array(2,3,4,5,6,7,2,3,4)) {
		$sod = 0;
		for ($i=8; $i>=0; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = 11-($sod%11); // kt06
		if (($pz==10) && ($ktonr[0]==9)) $pz = 7;
		if (($pz==11) && ($ktonr[0]==9)) $pz = 8;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt57 ($ktonr, $weight = array(1,2,1,2,1,2,1,2,1)) { // changed on 09.09.2013, fixed in v1.0.19
		if (substr($ktonr,0,2) == "00") return false; // starting with 00 always false!
		$chk = array(51, 55, 61, 64, 65, 66, 70, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 88, 94, 95); // added missing 77 in v1.0.19
		if (in_array((int)substr($ktonr,0,2), $chk )) { // Variant 1: Method 00
			if ((substr($ktonr,0,6)=='777777') || (substr($ktonr,0,6)=='888888')) return true; // no check, always valid!
			return $this->blz_kt00_var($ktonr, $weight, 0, 8, 9, 10); // fix for method call in v1.0.19
		}
		$chk = array(32, 33, 34, 35, 36, 37, 38, 39, 41, 42, 43, 44, 45, 46, 47, 48, 49, 52, 53, 54,
			56, 57, 58, 59, 60, 62, 63, 67, 68, 69, 71, 72, 83, 84, 85, 86, 87, 89, 90, 92, 93, 96, 97, 98);
		if (in_array((int)substr($ktonr,0,2), $chk )) { // Variant 2: Method 00 but checksum at digit 3!
			return $this->blz_kt00_var($ktonr, array(1,2,1,2,1,2,1,0,2,1), 0, 9, 2, 10); // fix for method call in v1.0.19
		}
		$chk = array(40, 50, 91, 99);
		if (in_array((int)substr($ktonr,0,2), $chk )) {  // Variant 3: Method 09, no check
			return true;
		}
		if ( ((substr($ktonr,0,2)>=1) && (substr($ktonr,0,2)<=31)) ) { // Variant 4
			if ($ktonr == '0185125434') return true; // exception is valid;
			if ( ((substr($ktonr,2,2)>=1) && (substr($ktonr,2,2)<=12)) && (substr($ktonr,6,3)<500) ) return true;
		}
		return false;
	}
	protected function blz_kt58 ($ktonr, $weight = array(2,3,4,5,6,0,0,0,0)) {
		return $this->blz_kt02($ktonr, $weight);
	}
	protected function blz_kt59 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		if (substr($ktonr,0,2)=='00') return true;
		else return $this->blz_kt00($ktonr, $weight);
	}
	protected function blz_kt60 ($ktonr, $weight = array(2,1,2,1,2,1,2,0,0)) {
		return $this->blz_kt00($ktonr, $weight);
	}
	protected function blz_kt61 ($ktonr, $weight = array(2,1,2,1,2,1,2,0,1,2)) {
		$sod = 0;
		for ($i=0; $i<=6; $i++) $sod += $this->blz_sod($ktonr[$i]*$weight[$i]);
		if ($ktonr[8] == 8) for ($i=8; $i<=9; $i++) $sod += $this->blz_sod($ktonr[$i]*$weight[$i]);
		$pz = (10-($sod%10))%10;
		if (intval($pz)==intval($ktonr[7])) return true;
		else return false;
	}
	protected function blz_kt62 ($ktonr, $weight = array(2,1,2,1,2)) {
		return $this->blz_kt00_var($ktonr, $weight, 2,6,7);
	}
	protected function blz_kt63 ($ktonr, $weight = array(2,1,2,1,2,1)) {
		if ($ktonr[0] != 0) return false;
		if ($this->blz_kt00_var($ktonr, $weight, 1,6,7)) return true;
		else {
			if (substr($ktonr,0,3)!='000') return false;
			return $this->blz_kt00_var($ktonr, $weight, 3,8,9);
		}
	}
	protected function blz_kt64 ($ktonr, $weight = array(2,4,8,5,10,9)) {
		return $this->blz_kt06_var($ktonr, $weight, 0,5,6);
	}
	protected function blz_kt65 ($ktonr, $weight = array(2,1,2,1,2,1,2,0,1,2)) {
		$sod = 0;
		for ($i=0; $i<=6; $i++) $sod += $this->blz_sod($ktonr[$i]*$weight[$i]);
		if ($ktonr[8] == 9) for ($i=8; $i<=9; $i++) $sod += $this->blz_sod($ktonr[$i]*$weight[$i]);
		$pz = (10-($sod%10))%10;
		if (intval($pz)==intval($ktonr[7])) return true;
		else return false;
	}
	protected function blz_kt66 ($ktonr, $weight = array(2,3,4,5,6,0,0,7)) { // change on March 3rd, 2014
		if ($ktonr[0] != 0) return false;
		if ($ktonr[1] == 9) return true; // exception added on 03.03.14
		$sod = 0;
		for ($i=8; $i>=1; $i--) $sod += $ktonr[$i]*$weight[(8-$i)];
		$pz = (11-($sod%11))%10; // kt06
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt67 ($ktonr, $weight = array(2,1,2,1,2,1,2)) {
		return $this->blz_kt00_var($ktonr, $weight, 0,6,7);
	}
	protected function blz_kt68 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		if ($ktonr[0]!=0) {
			if ($ktonr[3]==9) return $this->blz_kt00_var($ktonr, $weight, 3,8,9);
			else return false;
		} else {
			if ($ktonr[1]==4) return true;
			if ($this->blz_kt00_var($ktonr, $weight, 0,8,9)) return true;
			else return $this->blz_kt00_var($ktonr, array(2,1,2,1,2,0,0,1,2), 0,8,9);
		}
	}
	protected function blz_kt69 ($ktonr, $weight = array(2,3,4,5,6,7,8)) {
		switch(substr($ktonr,0,2)) {
		case '93': return true;
		case '97': return $this->blz_m10h($ktonr);
		default:
			if ($this->blz_kt28($ktonr, $weight)) return true;
			else return $this->blz_m10h($ktonr);
		}
	}
	protected function blz_kt70 ($ktonr, $weight = array(2,3,4,5,6,7,2,3,4)) {
		if (($ktonr[3] == 5) || (substr($ktonr,3,2) == '69'))
			return $this->blz_kt06_var($ktonr, $weight, 3,8,9);
		else return $this->blz_kt06_var($ktonr, $weight, 0,8,9);
	}
	protected function blz_kt71 ($ktonr, $weight = array(6,5,4,3,2,1)) {
		$sod = 0;
		for($i=1;$i<=6;$i++) $sod += $ktonr[$i]*$weight[($i-1)];
		$pz = 11-($sod%11);
		$pz = ($pz==11)?0:($pz==10)?1:$pz;
		if (intval($pz)==intval($ktonr[9])) return true;
		else return false;
	}
	protected function blz_kt72 ($ktonr, $weight = array(2,1,2,1,2,1)) {
		return $this->blz_kt00_var($ktonr, $weight, 3,8,9);
	}
	protected function blz_kt73 ($ktonr, $weight = array(2,1,2,1,2,1)) {
		if ($ktonr[2] == 9) {
			if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8), 2,8,9)) return true;
			else return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8,9,10), 0,8,9);
		} else {
			if ($this->blz_kt00_var($ktonr, $weight, 3,8,9)) return true;
			if ($this->blz_kt00_var($ktonr, $weight, 4,8,9)) return true;
			return $this->blz_kt00_var($ktonr, $weight, 4,8,9,7);
		}
	}
	protected function blz_kt74 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) { // change on December 5th, 2016
		if ($this->blz_kt00_var($ktonr, $weight, 0,8,9)) return true;
		if (substr($ktonr,0,4)=='0000' && substr($ktonr,4,1)!='0' && substr($ktonr,4,2)!='00') { // fix for exception in v1.0.19
			if ($this->blz_kt00_var($ktonr, $weight, 4,8,9,5)) return true;
		}
		return $this->blz_kt04($ktonr); // added on December 5th, 2016
	}
	protected function blz_kt75 ($ktonr, $weight = array(2,1,2,1,2)) {
		if (substr($ktonr,0,3)=='000') return $this->blz_kt00_var($ktonr, $weight, 4,8,9);
		if ($ktonr[1]==9) return $this->blz_kt00_var($ktonr, $weight, 2,6,7);
		return $this->blz_kt00_var($ktonr, $weight, 1,5,6);
	}
	protected function blz_kt76 ($ktonr, $weight = array(2,3,4,5,6,7)) {
		if (!preg_match("/[046789]/", $ktonr[0])) return false;
		$first = 0;
		while($first++ < 2) {
			$sod = 0;
			for ($i=6; $i>=1; $i--) $sod += $ktonr[$i]*$weight[(6-$i)];
			$pz = $sod%11;
			if (intval($pz)==intval($ktonr[7])) return true;
			$ktonr = substr($ktonr.'00',-10);
		}
		return false;
	}
	protected function blz_kt77 ($ktonr, $weight = array(1,2,3,4,5)) {
		$first = 0;
		while($first++ < 2) {
			$sod = 0;
			for ($i=9; $i>=5;$i--) $sod += $ktonr[$i]*$weight[(9-$i)];
			if (($sod%11)==0) return true;
			$weight = array(5,4,3,4,5);
		}
		return false;
	}
	protected function blz_kt78 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		if (substr($ktonr,0,2)=='00') return true;
		return $this->blz_kt00($ktonr, $weight);
	}
	protected function blz_kt79 ($ktonr, $weight = array(2,1,2,1,2,1,2,1,2)) {
		if ($ktonr[0] == 0) return false;
		if (preg_match("/[345678]/", $ktonr[0]))
			return $this->blz_kt00_var($ktonr, $weight, 0,8,9);
		return $this->blz_kt00_var($ktonr, $weight, 0,7,8);
	}
	protected function blz_kt80 ($ktonr, $weight = array(2,1,2,1,2)) {
		if ($ktonr[2] == 9) {
			if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8), 2,8,9)) return true;
			return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8,9,10), 0,8,9);
		}
		if ($this->blz_kt00_var($ktonr, $weight, 4,8,9)) return true;
		return $this->blz_kt00_var($ktonr, $weight, 4,8,9,7);
	}
	protected function blz_kt81 ($ktonr, $weight = array(2,3,4,5,6,7)) {
		if ($ktonr[2] == 9) {
			if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8), 2,8,9)) return true;
			return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8,9,10), 0,8,9);
		} else return $this->blz_kt06_var($ktonr, $weight, 3,8,9);
	}
	protected function blz_kt82 ($ktonr, $weight = array(2,3,4,5,6)) {
		if (substr($ktonr,2,2) == '99') return $this->blz_kt10($ktonr);
		return $this->blz_kt06_var($ktonr, $weight, 4,8,9);
	}
	protected function blz_kt83 ($ktonr, $weight = array(2,3,4,5,6,7,8)) {
		if (substr($ktonr, 2,2) == '99') return $this->blz_kt06_var($ktonr, $weight, 2,8,9);
		if ($this->blz_kt06_var($ktonr, $weight, 3,8,9)) return true;
		if ($this->blz_kt06_var($ktonr, $weight, 4,8,9)) return true;
		return $this->blz_kt06_var($ktonr, $weight, 3,8,9,7);
	}
	protected function blz_kt84 ($ktonr, $weight = array(2,3,4,5,6)) { // modified on 25.05.2013
		if ($ktonr[2] == 9) { // Exception ( digit 3 = 9 ) see kt51
			if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8), 2,8,9)) return true; // Variant 1
			return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8,9,10), 0,8,9); // Variant 2
		}
		if ($this->blz_kt06_var($ktonr, $weight, 4,8,9)) return true; // Method A (06)
		if ($this->blz_kt06_var($ktonr, $weight, 4,8,9,7)) return true; // Method B (06 mod7) modified
		return $this->blz_kt06_var($ktonr, array(2,1,2,1,2), 4,8,9,10); // Method C (06 mod10) new since 03.06.2013
	}
	protected function blz_kt85 ($ktonr, $weight = array(2,3,4,5,6,7,8,0,0)) {
		if (substr($ktonr, 2,2) == '99') return $this->blz_kt02($ktonr, $weight);
		if ($this->blz_kt06_var($ktonr, $weight, 3,8,9)) return true;
		if ($this->blz_kt06_var($ktonr, $weight, 4,8,9)) return true;
		return $this->blz_kt06_var($ktonr, $weight, 3,8,9,7);
	}
	protected function blz_kt86 ($ktonr) {
		if ($ktonr[2] == 9) {
			if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8), 2,8,9)) return true;
			return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8,9,10), 0,8,9);
		}
		if ($this->blz_kt00_var($ktonr, array(2,1,2,1,2,1), 3,8,9)) return true;
		return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7), 3,8,9);
	}
	protected function blz_kt87 ($ktonr) { // changed 07.09.2015
		if ($ktonr[2] == 9) { // Exception similar to Method 51
			if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8), 2,8,9)) return true;
			return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,8,9,10), 0,8,9);
		}
		// Method A
		$code = array(5,6,2,3,4,10,1,7,8,9);
		$pzcodes = array(array(7,1,5,9,8), array(0,4,3,2,6));
		for ($start=3; $ktonr[$start]==0; $start++);
		$sod = 0; $h = 1;
		for ($i=$start; $i<9; $i++) {
			if (($i%2)==$h) {
				if ($code[$ktonr[$i]]>5) {
					$sod += ($h==1)?12-$code[$ktonr[$i]]:$code[$ktonr[$i]];
					$h = ($h==1)?0:1;
				} else $sod += $code[$ktonr[$i]];
			} else {
				if ($code[$ktonr[$i]]>5) {
					$sod += ($h==0)?$code[$ktonr[$i]]-12:-$code[$ktonr[$i]];
					$h = ($h==0)?1:0;
				} else $sod -= $code[$ktonr[$i]];
			}
		}
		while(($sod < 0) || ($sod > 4)) $sod += ($sod>4)?-5:5;
		if (($pz=$pzcodes[$h][$sod])==$ktonr[9]) return true;
		if ($ktonr[3] == 0) {
			if ( (($pz>4)?$pz-5:$pz+5)==$ktonr[9]) return true;
		}
		// Method B,C&D
		if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6), 4,8,9)) return true; // Method B
		if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6), 4,8,9,7)) return true; // Method C
		return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7), 3,8,9); // Method D (new since 07.09.2015)
	}
	protected function blz_kt88 ($ktonr, $weight = array(2,3,4,5,6,7,8)) {
		if ($ktonr[2] == 9) return $this->blz_kt06_var($ktonr, $weight, 2,8,9);
		return $this->blz_kt06_var($ktonr, $weight, 3,8,9);
	}
	protected function blz_kt89 ($ktonr, $weight = array(2,3,4,5,6,7,8,9,10)) {
		for ($start=0; $ktonr[$start]==0; $start++);
		switch(10-$start) {
		case 1: case 2: case 3: case 4: case 5: case 6: case 10: return true;
		case 7:
			$sod = 0;
			for ($i=8; $i>=3; $i--) $sod += $this->blz_sod($ktonr[$i]*$weight[(8-$i)]);
			$pz = 11-($sod%11); // kt06
			$pz = ($pz>=10)?0:$pz;
			if (intval($pz)==intval($ktonr[9])) return true;
			return false;
		break;
		case 8: case 9: return $this->blz_kt06($ktonr, $weight);
		}
	}
	protected function blz_kt90 ($ktonr, $weight = array(2,3,4,5,6,7,8)) { // description changed on 08.09.2014
		if ($ktonr[2] == 9) return $this->blz_kt06_var($ktonr, $weight, 2,8,9); // changed on 9.6.14: Sachkonten, Method F
		if ($this->blz_kt06_var($ktonr, $weight, 3,8,9)) return true; // A
		if ($this->blz_kt06_var($ktonr, $weight, 4,8,9)) return true; // B
		if ($this->blz_kt06_var($ktonr, $weight, 4,8,9,7,true)) return true; // C
		if ($this->blz_kt06_var($ktonr, $weight, 4,8,9,9,true)) return true; // D
		if ($this->blz_kt06_var($ktonr, array(2,1,2,1,2), 4,8,9,10)) return true; // E
		if ($this->blz_kt06_var($ktonr, array(2,1,2,1,2,1), 3,8,9,7)) return true; // G - new since 9.6.14
		return false;
	}
	protected function blz_kt91 ($ktonr) {
		if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7), 0,5,6)) return true; // 1
		if ($this->blz_kt06_var($ktonr, array(7,6,5,4,3,2), 0,5,6)) return true; // 2
		if ($this->blz_kt06_var($ktonr, array(2,3,4,0,5,6,7,8,9,10), 0,9,6)) return true; // 3
		return $this->blz_kt06_var($ktonr, array(2,4,8,5,10,9), 0,5,6); // 4
	}
	protected function blz_kt92 ($ktonr, $weight = array(3,7,1,3,7,1)) {
		return $this->blz_kt06_var($ktonr, $weight, 3,8,9,10);
	}
	protected function blz_kt93 ($ktonr, $weight = array(2,3,4,5,6)) {
		if (substr($ktonr,0,4)=='0000') $ktonr = substr($ktonr.'0000',-10);
		if ($this->blz_kt06_var($ktonr, $weight, 0,4,5)) return true;
		return $this->blz_kt06_var($ktonr, $weight, 0,4,5,7,true);
	}
	protected function blz_kt94 ($ktonr, $weight = array(1,2,1,2,1,2,1,2,1)) {
		return $this->blz_kt00_var($ktonr, $weight, 0,8,9);
	}
	protected function blz_kt95 ($ktonr, $weight = array(2,3,4,5,6,7,2,3,4)) { // changed on 09.09.2013
		if (preg_match("/^0(000|001|009|01|02[0-5]|39[6-9]|4[0-9]|7[0-9]|9[1-8])[0-9]*/",$ktonr)) return true;
		return $this->blz_kt06_var($ktonr, $weight, 0,8,9);
	}
	protected function blz_kt96 ($ktonr) {
		if ($this->blz_kt19($ktonr)) return true;
		if ($this->blz_kt00($ktonr)) return true;
		if ((substr($ktonr,0,5) >= '00013') && (substr($ktonr,0,5) < '00994')) return true;
		return false;
	}
	protected function blz_kt97 ($ktonr) {
		$pz = (intval(substr($ktonr,0,9))%11)%10;
		if (intval($pz)==intval($ktonr[9])) return true;
		return false;
	}
	protected function blz_kt98 ($ktonr, $weight = array(3,1,7,3,1,7,3,0,0)) {
		return $this->blz_kt01($ktonr, $weight);
	}
	protected function blz_kt99 ($ktonr, $weight = array(2,3,4,5,6,7,2,3,4)) {
		if (preg_match("/^0(39[6-9]|4[0-9])[0-9]*/", $ktonr)) return true;
		return $this->blz_kt06_var($ktonr, $weight, 0,8,9);
	}
	protected function blz_ktA0 ($ktonr, $weight = array(2,4,8,5,10,0,0,0,0)) {
		if (preg_match("/^0000000[0-9]*/", $ktonr)) return true;
		return $this->blz_kt06_var($ktonr, $weight, 0,8,9);
	}
	protected function blz_ktA1 ($ktonr, $weight = array(2,1,2,1,2,1,2,0,0)) {
		for ($start=0; $ktonr[$start]==0; $start++);
		if (($start==1) || ($start>2)) return false;
		return $this->blz_kt00($ktonr, $weight);
	}
	protected function blz_ktA2 ($ktonr) {
		if ($this->blz_kt00($ktonr)) return true;
		return $this->blz_kt04($ktonr);
	}
	protected function blz_ktA3 ($ktonr) {
		if ($this->blz_kt00($ktonr)) return true;
		return $this->blz_kt10($ktonr);
	}
	protected function blz_ktA4 ($ktonr, $weight = array(2,3,4,5,6,7,0,0,0)) {
		if (substr($ktonr,2,2)=='99')
			if ($this->blz_kt06_var($ktonr, $weight, 4,8,9)) return true;
		else {
			if ($this->blz_kt06_var($ktonr, $weight, 3,8,9)) return true;
			if ($this->blz_kt06_var($ktonr, $weight, 3,8,9,7,true)) return true;
		}
		return $this->blz_kt93($ktonr);
	}
	protected function blz_ktA5 ($ktonr) {
		if ($this->blz_kt00($ktonr)) return true;
		if ($ktonr[0]==9) return false;
		return $this->blz_kt10($ktonr);
	}
	protected function blz_ktA6 ($ktonr) {
		if ($ktonr[1]==8) return $this->blz_kt00($ktonr);
		return $this->blz_kt01($ktonr);
	}
	protected function blz_ktA7 ($ktonr) {
		if ($this->blz_kt00($ktonr)) return true;
		return $this->blz_kt03($ktonr);
	}
	protected function blz_ktA8 ($ktonr) {
		if ($this->blz_kt81($ktonr)) return true;
		if ($ktonr[2]==9) return false;
		return $this->blz_kt73($ktonr);
	}
	protected function blz_ktA9 ($ktonr) {
		if ($this->blz_kt01($ktonr)) return true;
		return $this->blz_kt06($ktonr);
	}
	protected function blz_ktB0 ($ktonr) {
		if (preg_match("/^(0|8)[0-9]*/", $ktonr)) return false;
		if (preg_match("/([1-3]|6)/", $ktonr[7])) return true;
		return $this->blz_kt06($ktonr);
	}
	protected function blz_ktB1 ($ktonr) { // Change on 5th June 2017: added variant 3
		if ($this->blz_kt05($ktonr)) return true; // Variant 1: method 05, weight: 3,7,1,3,7,1,3,7,1
		if ($this->blz_kt01($ktonr)) return true; // Variant 2: method 01, weight: 3,7,1,3,7,1,3,7,1
		return $this->blz_kt00($ktonr); // Variant 3: method 00, weight: 2,1,2,1,2,1,2,1,2
	}
	protected function blz_ktB2 ($ktonr) {
		if (preg_match("/[0-7]/", $ktonr[0])) return $this->blz_kt02($ktonr);
		return $this->blz_kt00($ktonr);
	}
	protected function blz_ktB3 ($ktonr) {
		if (preg_match("/[0-8]/", $ktonr[0])) return $this->blz_kt32($ktonr);
		return $this->blz_kt06($ktonr);
	}
	protected function blz_ktB4 ($ktonr) { // New since 7th March 2005
		if (preg_match("/[0-8]/", $ktonr[0])) return $this->blz_kt02($ktonr);
		return $this->blz_kt00($ktonr);
	}
	protected function blz_ktB5 ($ktonr) { // New since 6th June 2005
		if ($this->blz_kt01($ktonr, array(7,3,1,7,3,1,7,3,1)) != true) {
			if (preg_match("/[8-9]/", $ktonr[0])) return false;
			return $this->blz_kt00($ktonr);
		} else return true;
	}
	protected function blz_ktB6 ($ktonr) { // New since 5th September 2005 change on 5th September 2011
		if (preg_match("/^[1-9][0-9]{9}$/", $ktonr)||preg_match("/^0269[1-9][0-9]{5}$/", $ktonr)) {
      return $this->blz_kt20($ktonr);
    }
		return $this->blz_kt53($ktonr);
	}
	protected function blz_ktB7 ($ktonr) { // New since 5th September 2005
		if (preg_match("/^0(00[1-5]|[7-8][0-9])[0-9]*/",$ktonr)) return $this->blz_kt01($ktonr);
		return $this->blz_kt09($ktonr);
	}
	protected function blz_ktB8 ($ktonr) { // New since 5th September 2005 change on 6th June 2011
		if ($this->blz_kt20($ktonr) != true) {
			if ($this->blz_kt29($ktonr) != true) {
				$num = (int)substr($ktonr,0,3);
				if ((($num>=510) && ($num<=599)) || (($num>=901) && ($num<=910))) return true;
				else return false;
			} else return true;
		} else return true;
	}
	protected function blz_ktB9 ($ktonr) { // New since 5th December 2005 change on 6th June 2011
		if (preg_match("/^00[0-9]*/", $ktonr)) {
			$weight = array(1,3,2,1,3,2,1);
			$sod = 0;
			for ($i = 2; $i <= 8; $i++) $sod += ($ktonr[$i]*$weight(8-$i)+$weight(8-$i))%11;
			if (($sod%10 != $ktonr[9]) && (($sod+5)%10 != $ktonr[9])) return false;
			return true;
		}
		if (preg_match("/^000[0-9]*/", $ktonr)) {
			$weight = array(1,2,3,4,5,6);
			$sod = 0;
			for ($i = 3; $i <= 8; $i++) $sod += ($ktonr[$i]*$weight(8-$i));
			if (($sod%11 != $ktonr[9]) && (($sod%11+5)%10 != $ktonr[9])) return false;
			return true;
		}
		return false;
	}
	protected function blz_ktC0 ($ktonr) { // New since 5th December 2005
		if (preg_match("/^00[0-9]*/", $ktonr)) {
			if ($this->blz_kt52($ktonr) == true) return true;
		}
		return $this->blz_kt20($ktonr);
	}
	protected function blz_ktC1 ($ktonr) { // New since 5th June 2006
		if (preg_match("/^5[0-9]*/", $ktonr)) {
			$weight = array(1,2,1,2,1,2,1,2,1);
			$sod = 0;
			for ($i = 0; $i <= 8; $i++) $sod += $this->blz_sod($ktonr[$i]*$weight[($i)]);
			if ((10-($sod-1)%11)%10 == $ktonr[9]) return true;
			return false;
		}
		return $this->blz_kt17($ktonr);
	}
	protected function blz_ktC2 ($ktonr) { // New since 5th June 2006, modified 4th September 2017
		if ($this->blz_kt22($ktonr) == true) return true; // Variant 1
		if ($this->blz_kt00($ktonr) == true) return true; // Variant 2, until September 2017 this was the last variant
		return $this->blz_kt04($ktonr); // Variant 3, new since September 2017
	}
	protected function blz_ktC3 ($ktonr) { // New since 5th March 2007
		if (preg_match("/^9[0-9]/", $ktonr)) return $this->blz_kt58($ktonr);
		return $this->blz_kt00($ktonr);
	}
	protected function blz_ktC4 ($ktonr) { // New since 5th March 2007
		if (preg_match("/^9[0-9]/", $ktonr)) return $this->blz_kt58($ktonr);
		return $this->blz_kt15($ktonr);
	}
	protected function blz_ktC5 ($ktonr) { // New since 3rd September 2007
		if (preg_match("/^0(000[1-8]|[1-8])[0-9]*/", $ktonr)) return $this->blz_kt75($ktonr);
		if (preg_match("/^(1|[4-6]|9)[0-9]*/", $ktonr)) return $this->blz_kt29($ktonr);
		if (preg_match("/^3[0-9]*/", $ktonr)) return $this->blz_kt00($ktonr);
		if (preg_match("/^(00[3-5]|70|85)[0-9]*/", $ktonr)) return $this->blz_kt09($ktonr);
		return false;
	}
	protected function blz_ktC6 ($ktonr) { // New since 3rd September 2007
		// changed on 9th March 2009, changed on 7th June 2010, changed on 6th June 2011, changed on 4th March 2013
		$cnst = array(0 => '4451970', 1 => '4451981', 2 => '4451992', 3 => '4451993', 4=> '4344992',
			5 => '4344990', 6 => '4344991', 7 => '5499570', 8 => '4451994', 9 => '5499579');
		$nr = $cnst[$ktonr[0]].substr($ktonr,1,8);
		$weight = array(2,1,2,1,2,1,2,1,2,1,2,1,2,1,2);
		$sod = 0;
		for ($i = 0; $i <= 14; $i++) $sod += $this->blz_sod($nr[$i]*$weight[$i]);
		if (10-$sod%10 == $ktonr[9]) return true;
		return false;
	}
	protected function blz_ktC7 ($ktonr) { // New since 3rd December 2007
		if ($this->blz_kt63($ktonr) == true) return true;
		return $this->blz_kt06($ktonr);
	}
	protected function blz_ktC8 ($ktonr) { // New since 9th June 2008, changed 7th September 2009
		if ($this->blz_kt00($ktonr) == true) return true;
		if ($this->blz_kt04($ktonr) == true) return true;
		return $this->blz_kt07($ktonr);
	}
	protected function blz_ktC9 ($ktonr) { // New since 9th June 2008
		if ($this->blz_kt00($ktonr) == true) return true;
		return $this->blz_kt07($ktonr);
	}
	protected function blz_ktD0 ($ktonr) { // New since 8th September 2008
		if (substr($ktonr,0,2) != '57') return $this->blz_kt20($ktonr);
		return true; // Method 09 all numbers starting with 57 are treaded as valid
	}
	protected function blz_ktD1 ($ktonr) { // New since 8th September 2008
		// changed on 7th June 2010, changed 7th March 2011, changed 5th September 2011, changed 4th March 2013
		if ($ktonr[0] == 8) return false;
		$cnst = array(0 => '436338', 1 => '436338', 2 => '436338', 3 => '436338', 4 => '436338',
									5 => '436338', 6 => '436338', 7 => '436338', 9 => '436338');
		$nr = $cnst[(int)$ktonr[0]].substr($ktonr,0,9);
		$weight = array(2,1,2,1,2,1,2,1,2,1,2,1,2,1,2);
		$sod = 0;
		for ($i = 0; $i <= 14; $i++) $sod += $this->blz_sod($nr[$i]*$weight[$i]);
		if (10-$sod%10 == $ktonr[9]) return true;
		return false;
	}
	protected function blz_ktD2 ($ktonr) { // New since 8th December 2008
		if ($this->blz_kt95($ktonr) == true) return true;
		if ($this->blz_kt00($ktonr) == true) return true;
		return $this->blz_kt68($ktonr);
	}
	protected function blz_ktD3 ($ktonr) { // New since 8th December 2008
		if ($this->blz_kt00($ktonr) == true) return true;
		return $this->blz_kt27($ktonr);
	}
	protected function blz_ktD4 ($ktonr) { // New since 7th June 2010
    // changed 6th June 2011
		if ($ktonr[0]==0) return false;
		$cnst = array(1 => '428259', 2 => '428259', 3 => '428259', 4 => '428259', 5 => '428259', 
      6 => '428259', 7 => '428259', 8 => '428259', 9 => '428259');
		$nr = $cnst[(int)$ktonr[0]].substr($ktonr,0,9);
		$weight = array(2,1,2,1,2,1,2,1,2,1,2,1,2,1,2);
		$sod = 0;
		for ($i = 0; $i <= 14; $i++) $sod += $this->blz_sod($nr[$i]*$weight[$i]);
		if (10-$sod%10 == $ktonr[9]) return true;
		return false;
	}
	protected function blz_ktD5 ($ktonr) { // New since 6th December 2010
		if (substr($ktonr,2,2)=='99') {
      return $this->blz_kt06($ktonr, array(2,3,4,5,6,7,8,0,0));
    } else {
      if ($this->blz_kt06($ktonr, array(2,3,4,5,6,7,0,0,0)) == true) return true;
      if ($this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,0,0,0), 0,8,9, 7) == true) return true;
      return $this->blz_kt06_var($ktonr, array(2,3,4,5,6,7,0,0,0), 0,8,9, 10);
    }
		return false;
	}
  protected function blz_ktD6 ($ktonr) { // New since 7th March 2011
    if ($this->blz_kt07($ktonr) == true) return true;
    if ($this->blz_kt03($ktonr) == true) return true;
    return $this->blz_kt00($ktonr);
  }
  protected function blz_ktD7 ($ktonr) { // New since 6th June 2011
		// implementation modified 2nd March 2013
    $weight = array(2,1,2,1,2,1,2,1,2);
		$sod = 0;
		for ($i = 0; $i <= 8; $i++) $sod += $this->blz_sod($ktonr[$i]*$weight[$i]);
		if (($sod%10) == $ktonr[9]) return true;
		return false;    
  }
  protected function blz_ktD8 ($ktonr) { // New since 6th June 2011
    if ($ktonr[0]>=1 && $ktonr[0]<=9) return $this->blz_kt00($ktonr);
    if (substr($ktonr,0,2)=='00' && $ktonr[2]>=1 && $ktonr[2]<=9) return true; // Method 09 all true
    return false;
  }
	protected function blz_ktD9 ($ktonr) { // New since 4th June 2012
		// implementation modified 2nd March 2013
		if ($this->blz_kt00($ktonr) == true) return true;	  // Variante 1
		if ($this->blz_kt10($ktonr) == true) return true;	  // Variante 2
		return $this->blz_kt18($ktonr); 										// Variante 3
	}
	protected function blz_ktE0 ($ktonr) { // New since 4th March 2013, description update 03.06.2013
		$weight = array(2,1,2,1,2,1,2,1,2); // similar to Variant 00 with SOD offset
		$sod = 7;	// offset of sum
		for ($i = 0; $i <= 8; $i++) $sod += $this->blz_sod($ktonr[$i]*$weight[$i]);
		if (10-$sod%10 == $ktonr[9]) return true;
		return false;
	}
	protected function blz_ktE1 ($ktonr) { // New since 09.12.2013: char-value based method
		$weight = array_reverse(array(1,2,3,4,5,6,11,10,9)); // reversed for easier access
		$sod = 0;
		for ($i = 0; $i <= 8; $i++) $sod += ord($ktonr[$i])*$weight[$i];
		if (($sod%11) == (int)$ktonr[9]) return true;
		return false;
	}
	protected function blz_ktE2 ($ktonr) { // New since 08.06.2015: calc based on method 00
		if ((int)$ktonr[0]>=6) return false; // 1st digit with 6,7,8 or 9 are false
		$cnst = array(0 => '438320', 1 => '438320', 2 => '438320', 3 => '438320', 4 => '438320', 5 => '438320');
		$nr = $cnst[(int)$ktonr[0]].substr($ktonr,0,9);
		$weight = array(2,1,2,1,2,1,2,1,2,1,2,1,2,1,2);
		$sod = 0;
		for ($i = 0; $i <= 14; $i++) $sod += $this->blz_sod($nr[$i]*$weight[$i]);
		if (10-$sod%10 == $ktonr[9]) return true;
		return false;
	}
	protected function blz_ktE3 ($ktonr) { // New since 06.03.2017: using method 00 or 21
		if ($this->blz_kt00($ktonr) == true) return true; // if method 00 fails, use method 21
		return $this->blz_kt21($ktonr);		
	}
	protected function blz_ktE4 ($ktonr) { // New since 05.06.2017: using method 02 or 00
		if ($this->blz_kt02($ktonr)) return true; // Variant 1: method 02, weight=2,3,4,5,6,7,8,9,2
		return $this->blz_kt00($ktonr); // Variant 2: method 00, weight=2,1,2,1,2,1,2,1,2
	}
}
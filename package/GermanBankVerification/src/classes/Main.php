<?php
namespace ProDevelopement\GermanBankVerification\Classes;

use ProDevelopement\GermanBankVerification\Models\blz as BlzModel;

class GBV extends hrwsBLZ
{
    protected $specResponse = array(
        'status' => false,
        'alert' => 'danger'
    );

    public function getBanksByBLZ($aBLZ = NULL){
        $res = BlzModel::where('blz', $this->blz_cleanblz($aBLZ))->get();
        $this->specResponse = array(
            'blz' => $aBLZ,
            'bank' => $res,
            'alert' => 'success'
        );
        return $this->specResponse;
    }

    public function checkKtoBlz($kto, $blz){
        $this->specResponse['kto'] = $kto;
        $this->specResponse['blz'] = $blz;

        if($this->blz_isKtoValid($kto, $blz)){
            $this->specResponse['status'] = true;
            $this->generateIBAN($kto, $blz);
            $this->getMainBank($blz);
            $this->specResponse['msg'] = 'Bank Information successfylly Verified!';
            $this->specResponse['alert'] = 'success';
        }
        return $this->specResponse;
    }
    public function checkIBAN($iban){

        return $this->specResponse;
    }
    public function generateIBAN($kto, $blz){
        $kto = $this->blz_cleankto($kto);
        $blz = $this->blz_cleanblz($blz);
        $bin = $blz . $kto . '131400';
        $rem = bcmod($bin, '97');
        $rem = 98 - $rem;
        if(strlen($rem) == 1){
            $rem = '0'.$rem;
        }
        $iban = 'DE' . $rem . $blz . $kto;
        $this->specResponse['iban'] = $iban;
        return true;
    }
    public function getSwiftBlz($blz){

        return $this->specResponse;
    }
    public function getSwiftIban($iban){

        return $this->specResponse;
    }
    public function getMainBank($blz){
        $bank = BlzModel::where('blz', $this->blz_cleanblz($blz))->orderBy('bic', 'desc')->firstOrFail();
        $this->specResponse['bank'] = $bank->namelong;
        $this->specResponse['swift'] = $bank->bic;
        return true;
    }
    
	public function blz_queryblz($aBLZ = NULL) {
		if ($aBLZ !== NULL) {
            $res = BlzModel::where('blz', $this->blz_cleanblz($aBLZ))->get();
			if ($res !== false) {
				$this->data = array();
				if ($res->count() > 0) {
                    foreach($res as $row) $this->data[$row->id] = $row;
					return count($this->data);
				} else return 0;
			} else $this->specResponse['msg'] = "wrong bank indentification code submitted";
		} else $this->specResponse['msg'] = "no bank identification code submitted";
		return false;
	}

    public function blz_getnumentries() {
		if ($this->numBLZ !== NULL) return $this->numBLZ;
        $res = BlzModel::count();
        return $res;
        if ($res !== false) {
            list($this->numBLZ) = $res;
            return $this->numBLZ;
        } else $this->specResponse['msg'] = "could not retrieve data";
		return false;
    }
    
    public function blz_isKtoValid($aKto, $aBLZ = NULL) {
		if ($aBLZ !== NULL) $this->blz_queryblz($this->blz_cleanblz($aBLZ));
		if (count($this->data)==0) {
			$this->specResponse['msg'] = "no bank identification code present";
			return false;
        }
		$bank = current($this->data);
    if (isset($this->_corrblz[$bank->blz])) {
      $usrfunc = "blz_kt".$this->_corrblz[$bank->blz];
    } else $usrfunc = "blz_kt".$bank->pzc;
		if (method_exists($this, $usrfunc))
			return call_user_func(array($this, $usrfunc), $this->blz_cleankto($aKto));
		else $this->specResponse['msg'] = "account number validation method unknown";
		return false;
	}


    // protected function blz_importarray() {
	// 	if (count($this->import) > 0) {
	// 		if ($this->_cfg['clearb4import'] === true) {
	// 			if (!$this->blz_cleartable()) return false;
	// 		}
	// 		if (!$this->blz_sqlconnect()) return false;
	// 		$this->specResponse['msg'] = "";
	// 		$this->importcount = 0;
	// 		$this->numBLZ = NULL;
	// 		foreach ($this->import as $key => $line) {
	// 			$hrz_id = trim(substr($line, 152, 6));
	// 			$hrz_blz = trim(substr($line, 0, 8));
	// 			$hrz_namelong = trim(substr($line, 9, 58));
	// 			$hrz_nameshort = trim(substr($line, 107, 27));
	// 			$hrz_zipcode = trim(substr($line, 67, 5));
	// 			$hrz_town = trim(substr($line, 72, 35));
	// 			$hrz_own = trim(substr($line, 8, 1));
	// 			$hrz_bbk = 0;
	// 			$hrz_deldate = "0000-00-00 00:00:00";
	// 			$hrz_followid = trim(substr($line, 160, 8));
	// 			$hrz_bic = trim(substr($line, 139, 11));
	// 			$hrz_btxname = '';
	// 			$hrz_pzc = trim(substr($line, 150, 2));
	// 			if ($hrz_id != "") {
	// 				$sql = "INSERT INTO ".$this->_cfg['sqltable']." (hrz_id, hrz_blz, hrz_namelong, hrz_nameshort, ".
	// 					"hrz_zipcode, hrz_town, hrz_own, hrz_bbk, hrz_deldate, hrz_followid, hrz_bic, hrz_btxname, hrz_pzc) ".
	// 					"VALUES ('".$hrz_id."', '".$hrz_blz."', '".$hrz_namelong."', '".$hrz_nameshort."', '".$hrz_zipcode.
	// 					"', '".$hrz_town."', '".$hrz_own."', '".$hrz_bbk."', '".$hrz_deldate."', '".$hrz_followid."', '".
	// 					$hrz_bic."', '".$hrz_btxname."', '".$hrz_pzc."')";
	// 				$res = $this->_db->query($sql);
	// 				if ($res !== false) $this->importcount++;
	// 				else $this->specResponse['msg'] .= "error in line ".($key+1)."<br>";
	// 			}
	// 		}
	// 		if ($this->specResponse['msg'] == "") return $this->importcount;
	// 		else $this->specResponse['msg'] .= $this->importcount." lines successfully imported";
	// 	} else $this->specResponse['msg'] = "no entries found";
	// 	return false;
	// }

	// protected function blz_importupdatearray() {
	// 	if (count($this->import) > 0) {
	// 		if (!$this->blz_sqlconnect()) return false;
	// 		$this->specResponse['msg'] = "";
	// 		$this->importcount = 0;
	// 		$this->numBLZ = NULL;
	// 		foreach ($this->import as $key => $line) {
	// 			$hrz_id = trim(substr($line, 152, 6));
	// 			$hrz_blz = trim(substr($line, 0, 8));
	// 			$hrz_namelong = trim(substr($line, 9, 58));
	// 			$hrz_nameshort = trim(substr($line, 107, 27));
	// 			$hrz_zipcode = trim(substr($line, 67, 5));
	// 			$hrz_town = trim(substr($line, 72, 35));
	// 			$hrz_own = trim(substr($line, 8, 1));
	// 			$hrz_bbk = 0;
	// 			$hrz_deldate = "0000-00-00 00:00:00";
	// 			$hrz_followid = trim(substr($line, 160, 8));
	// 			$hrz_bic = trim(substr($line, 139, 11));
	// 			$hrz_btxname = '';
	// 			$hrz_pzc = trim(substr($line, 150, 2));
	// 			if ($hrz_id != "") {
	// 				$sqli = "INSERT INTO ".$this->_cfg['sqltable']." ( hrz_id, hrz_blz, hrz_namelong, hrz_nameshort, ".
	// 					"hrz_zipcode, hrz_town, hrz_own, hrz_bbk, hrz_deldate, hrz_followid, hrz_bic, ".
	// 					" hrz_btxname ) VALUES ('".$hrz_id."', '".$hrz_blz."', '".$hrz_namelong."', '".$hrz_nameshort.
	// 					"', '".$hrz_zipcode."', '".$hrz_town."', '".$hrz_own."', '".$hrz_bbk."', '".$hrz_deldate."', '".
	// 					$hrz_followid."', '".$hrz_bic."', '".$hrz_btxname."', '".$hrz_pzc."')";
	// 				$sqlu = "UPDATE ".$this->_cfg['sqltable']." SET hrz_blz = '".$hrz_blz."', hrz_namelong = '".
	// 					$hrz_namelong."', hrz_nameshort = '".$hrz_nameshort."', hrz_zipcode = '".$hrz_zipcode."', hrz_town = '".
	// 					$hrz_town."', hrz_own = '".$hrz_own."', hrz_bbk = '".$hrz_bbk."', hrz_deldate = '".$hrz_deldate.
	// 					"', hrz_followid = '".$hrz_followid."', hrz_bic = '".$hrz_bic."', hrz_btxname = '".$hrz_btxname.
	// 					"', hrz_pzc = '".$hrz_pzc."' WHERE hrz_id = '".$hrz_id."'";
	// 				$res = $this->_db->query("SELECT COUNT(*) FROM ".$this->_cfg['sqltable']." WHERE hrz_id = '".$hrz_id."'");
	// 				list($exist) = $res->fetch_array(MYSQLI_NUM);
	// 				if ($exist != 0) $res = $this->_db->query($sqlu);
	// 				else $res = $this->_db->query($sqli);
	// 				if ($res !== false) $this->importcount++;
	// 				else $this->specResponse['msg'] .= "error in line ".($key+1)."<br>";
	// 			}
	// 		}
	// 		if ($this->specResponse['msg'] == "") return $this->importcount;
	// 		else $this->specResponse['msg'] .= $this->importcount." lines successfully imported";
	// 	} else $this->specResponse['msg'] = "no entries found";
	// 	return false;
	// }

    // protected function blz_sqlconnect() {
	// 	if ($this->_db !== NULL) {
	// 		if ($this->_db->select_db($this->_cfg['sqldb'])) return true;
	// 	} else {
	// 		$this->_db = new mysqli($this->_cfg['sqlhost'], $this->_cfg['sqluser'], $this->_cfg['sqlpass']);
	// 		if ($this->_db !== false) {
	// 			if ($this->_db->select_db($this->_cfg['sqldb'])) return true;
	// 			else $this->specResponse['msg'] = "unable to access database";
	// 		} else $this->specResponse['msg'] = "unable to connect to database";
	// 	}
	// 	return false;
	// }

    // public function blz_cleartable() {
	// 	if ($this->blz_sqlconnect()) {
	// 		$sql = "DELETE FROM `".$this->_cfg['sqltable']."`";
	// 		$res = $this->_db->query($sql);
	// 		$this->numBLZ = NULL;
	// 		if ($res !== false) return true;
	// 		else $this->specResponse['msg'] = "error clearing database table";
	// 	}
	// }

	// public function blz_createtable() {
	// 	if ($this->blz_sqlconnect()) {
	// 		$sql = "CREATE TABLE `".$this->_cfg['sqltable']."` (`hrz_id` INT UNSIGNED NOT NULL , ".
	// 				"`hrz_blz` INT UNSIGNED NOT NULL , ".
	// 				"`hrz_namelong` VARCHAR( 58 ) NOT NULL , `hrz_nameshort` VARCHAR( 27 ) NOT NULL , ".
	// 				"`hrz_zipcode` VARCHAR( 5 ) NOT NULL , `hrz_town` VARCHAR( 35 ) NOT NULL , ".
	// 				"`hrz_own` TINYINT( 1 )  UNSIGNED NOT NULL , `hrz_bbk` TINYINT( 1 ) UNSIGNED NOT NULL , ".
	// 				"`hrz_deldate` DATETIME NOT NULL , `hrz_followid` INT UNSIGNED NOT NULL , ".
	// 				"`hrz_bic` VARCHAR( 11 ) NOT NULL , `hrz_btxname` VARCHAR( 27 ) NOT NULL , `hrz_pzc` char(2) NOT NULL ,".
	// 				" PRIMARY KEY ( `hrz_id` ) ) ENGINE = MyISAM COMMENT = 'hrwsBLZ - Datatable'";
	// 		$res = $this->_db->query($sql);
	// 		if ($res !== false) return true;
	// 		else $this->specResponse['msg'] = "error creating database table";
	// 	}
	// 	return false;
	// }
	
	// public function blz_droptable() {
	// 	if ($this->blz_sqlconnect()) {
	// 		$sql = "DROP TABLE `".$this->_cfg['sqltable']."`";
	// 		$res = $this->_db->query($sql);
	// 		$this->numBLZ = NULL;
	// 		if ($res !== false) return true;
	// 		else $this->specResponse['msg'] = "error dropping database table";
	// 	}
	// }

}

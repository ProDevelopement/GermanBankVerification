<?php

namespace ProDevelopement\GermanBankVerification\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use ProDevelopement\GermanBankVerification\Models\blz;
use ProDevelopement\GermanBankVerification\Classes\GBV as GermanBankVerification;

class AutoPopulateController extends Controller
{
    public function index(){
        return view('GBV::autopopulate.index');
    }

    public function autopopulate(Request $request){
        
        $res = file_get_contents($request->input('url'));
        unlink(public_path('storage/kto.txt'));
        file_put_contents(public_path('storage/kto.txt'), $res);

        $delimiter = "\t";
        $fp = fopen(public_path('storage/kto.txt'), 'r');
        while ( !feof($fp) )
        {
            $line = fgets($fp, 2048);
            $arrgs['blz'] = substr($line, 0, 8);
            $arrgs['namelong'] = utf8_encode(rtrim(substr($line, 9, 58)));
            $arrgs['nameshort'] = utf8_encode(rtrim(substr($line, 107, 27)));
            $arrgs['zipcode'] = substr($line, 67, 5);
            $arrgs['town'] = utf8_encode(rtrim(substr($line, 72, 35)));
            $arrgs['own'] = substr($line, 8, 1);
            $arrgs['bbk'] = 0;
            $arrgs['followid'] = substr($line, 160, 8);
            $arrgs['bic'] = rtrim(substr($line, 139, 11));
            $arrgs['pzc'] = rtrim(substr($line, 150, 2));
            $arrgs['btxname'] = '';
            if(strlen($arrgs['blz']) > 7){
                $blz = blz::create($arrgs);
            }
        }                              
        fclose($fp);
        return 'Database was Updated!';
    }

    public function test($blz = NULL, $kto = NULL){
        //return $blz;
        $t = new GermanBankVerification();
        $blzCount = $t->checkKtoBlz($kto, $blz);
        return response()->json($blzCount);
    }
}

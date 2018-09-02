<?php

namespace ProDevelopement\GermanBankVerification\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use ProDevelopement\GermanBankVerification\Models\blz;

class AutoPopulateController extends Controller
{
    public function index(){
        return view('GBV::autopopulate.index');
    }

    public function autopopulate(Request $request){
        
        // //$res = file_get_contents($request->input('url'));
        // //file_put_contents(public_path('storage/kto.txt'), $res);

        // $delimiter = "\t";
        // $fp = fopen(public_path('storage/kto.txt'), 'r');
        // //$fp = iconv('windows-1250', 'utf-8', file_get_contents(public_path('storage/kto.txt')));
        // //die(print_r($fp));
        // while ( !feof($fp) )
        // {
        //     $line = fgets($fp, 2048);
        //         $arrgs['blz'] = substr($line, 0, 8);
        //         $arrgs['namelong'] = utf8_encode(rtrim(substr($line, 9, 58)));
        //         $arrgs['nameshort'] = utf8_encode(rtrim(substr($line, 107, 27)));
        //         $arrgs['zipcode'] = substr($line, 67, 5);
        //         $arrgs['town'] = utf8_encode(rtrim(substr($line, 72, 35)));
        //         $arrgs['own'] = substr($line, 8, 1);
        //         $arrgs['bbk'] = 0;
        //         $arrgs['followid'] = substr($line, 160, 8);
        //         $arrgs['bic'] = rtrim(substr($line, 139, 11));
        //         $arrgs['pzc'] = rtrim(substr($line, 150, 2));
        //         // $arrgs['deldate'] = '0000-00-00 00:00:00';
        //         $arrgs['btxname'] = '';
        //     $blz = blz::create($arrgs);
        // }                              

        // fclose($fp);

        // return 'done';
    }
}

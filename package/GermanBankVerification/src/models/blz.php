<?php

namespace ProDevelopement\GermanBankVerification\Models;

use Illuminate\Database\Eloquent\Model;

class blz extends Model
{
    protected $fillable = [
        'blz',
        'namelong',
        'nameshort',
        'zipcode',
        'town',
        'own',
        'bbk',
        'deldate',
        'followid',
        'bic',
        'btxname',
        'pzc'
    ];
}

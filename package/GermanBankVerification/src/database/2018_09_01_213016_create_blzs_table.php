<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBlzsTable extends Migration
{
    /**
     * Run the migrations.
     * According to https://www.bundesbank.de/Redaktion/DE/Downloads/Aufgaben/Unbarer_Zahlungsverkehr/Bankleitzahlen/2018_12_02/blz_2018_09_03_txt.txt?__blob=publicationFile
     * Bankleitzahl	Merkmal	Bezeichnung	PLZ	Ort	Kurzbezeichnung	PAN	BIC	Prüfziffer-berechnungs-methode	Datensatz-nummer	Änderungs-kennzeichen	Bankleitzahl-löschung	Nachfolge-Bankleitzahl
     * @return void
     */
    public function up()
    {
        Schema::create('blzs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('blz', false, true)->length(10);
            $table->string('namelong', 58);
            $table->string('nameshort', 27);
            $table->string('zipcode', 5);
            $table->string('town', 35);
            $table->tinyInteger('own', false, true)->length(1);
            $table->tinyInteger('bbk', false, true)->length(1);
            $table->dateTime('deldate')->nullable();
            $table->integer('followid', false, true)->length(10);
            $table->string('bic', 11);
            $table->string('btxname', 27);
            $table->char('pzc', 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blzs');
    }
}

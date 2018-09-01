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
            $table->string('Bankleitzahl');
            $table->string('Merkmal');
            $table->string('Bezeichnung');
            $table->string('PLZ');
            $table->string('Ort');
            $table->string('Kurzbezeichnung');
            $table->string('Prüfziffer-berechnungs-methode');
            $table->string('Datensatz-nummer');
            $table->string('Änderungs-kennzeichen');
            $table->string('Bankleitzahl-löschung');
            $table->string('Nachfolge-Bankleitzahl');
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

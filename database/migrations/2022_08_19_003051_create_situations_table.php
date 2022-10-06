<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * 旅行者の現在の状況を管理するテーブル
*/
class CreateSituationsTable extends Migration
{
    public function up()
    {
        Schema::create('situations', function (Blueprint $table) {
            $table->bigIncrements('situation_id');
            $table->text('situation');
            $table->integer('travel_id');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    public function down()
    {
        Schema::dropIfExists('situations');
    }
}

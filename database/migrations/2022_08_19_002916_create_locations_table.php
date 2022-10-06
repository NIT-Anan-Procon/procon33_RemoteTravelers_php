<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 旅行者の現在の位置情報、閲覧者に提案された行き先の位置情報を
 * travel_idと紐づけて管理するテーブル
*/
class CreateLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->integer('travel_id');
            $table->integer('user_id');
            $table->double('lat');
            $table->double('lon');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->tinyInteger('flag');
        });
    }

    public function down()
    {
        Schema::dropIfExists('locations');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 旅行に参加するユーザを管理するテーブル
 * travelerが1であれば旅行者、0であれば閲覧者
 */
class CreateTravelsTable extends Migration
{
    public function up()
    {
        Schema::create('travels', function (Blueprint $table) {
            $table->integer('travel_id');
            $table->integer('user_id');
            $table->tinyInteger('traveler');
            $table->tinyInteger('finished');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('travels');
    }
}

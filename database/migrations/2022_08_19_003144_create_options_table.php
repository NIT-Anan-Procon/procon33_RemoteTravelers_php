<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * クイズの選択肢を管理するテーブル
 */
class CreateOptionsTable extends Migration
{
    public function up()
    {
        Schema::create('options', function (Blueprint $table) {
            $table->integer('quiz_id');
            $table->tinyInteger('option_id');
            $table->text('option');
            $table->tinyInteger('answer');
        });
    }

    public function down()
    {
        Schema::dropIfExists('options');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * クイズに関する情報を管理するテーブル
 */
class CreateQuizzesTable extends Migration
{
    public function up()
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->bigIncrements('quiz_id');
            $table->integer('travel_id');
            $table->text('quiz');
            $table->text('image');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quizzes');
    }
}

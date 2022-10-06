<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 旅行時の旅レポートを管理するテーブル
*/
class CreateReportsTable extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->bigIncrements('report_id');
            $table->integer('travel_id');
            $table->text('image');
            $table->text('comment');
            $table->integer('excitement');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->double('lat');
            $table->double('lon');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
}

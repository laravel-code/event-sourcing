<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Events extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commands', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('class');
            $table->longText('payload');
            $table->string('status', 32);
            $table->string('author_id')->nullable();
            $table->string('key');
            $table->timestamps();
        });

        Schema::create('command_errors', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('command_id')->index();
            $table->string('class');
            $table->longText('message');
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('command_id');
            $table->string('resource_id');
            $table->string('model');
            $table->string('class');
            $table->longText('payload');
            $table->integer('revision_number');
            $table->string('author_id')->nullable();
            $table->string('key');
            $table->bigIncrements('position');
            $table->timestamps();
            $table->index(['model', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('events');
    }
}

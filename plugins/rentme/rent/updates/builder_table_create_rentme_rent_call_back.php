<?php namespace RentMe\Rent\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateRentmeRentCallBack extends Migration
{
    public function up()
{
    Schema::create('rentme_rent_call_back', function($table)
    {
        $table->engine = 'InnoDB';
        $table->increments('id')->unsigned();
        $table->string('name', 255);
        $table->string('email', 255)->nullable();
        $table->string('phone', 255);
        $table->text('message');
        $table->string('locale', 10)->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->string('status', 50)->default('new');
        $table->boolean('is_read')->default(false);
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
        $table->text('admin_note')->nullable();
        $table->dateTime('processed_at')->nullable();
    });
}

public function down()
{
    Schema::dropIfExists('rentme_rent_call_back');
}
}

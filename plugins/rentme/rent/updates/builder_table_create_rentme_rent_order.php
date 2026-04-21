<?php namespace RentMe\Rent\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateRentmeRentOrder extends Migration
{
    public function up()
{
    Schema::create('rentme_rent_order', function($table)
    {
        $table->engine = 'InnoDB';
        $table->increments('id')->unsigned();
        $table->integer('car_id')->unsigned();
        $table->integer('location_id')->unsigned();
        $table->integer('return_location_id')->unsigned();
        $table->dateTime('date_from');
        $table->dateTime('date_to');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email');
        $table->string('phone');
        $table->date('dob');
        $table->text('description')->nullable(); 
        $table->boolean('is_active')->default(true);
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
    });
}

public function down()
{
    Schema::dropIfExists('rentme_rent_order');
}
}
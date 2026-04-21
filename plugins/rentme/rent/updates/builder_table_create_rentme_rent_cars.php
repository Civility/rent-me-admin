<?php namespace RentMe\Rent\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateRentmeRentCars extends Migration
{
    public function up()
{
    Schema::create('rentme_rent_cars', function($table)
    {
        $table->engine = 'InnoDB';
        $table->increments('id')->unsigned();
        $table->integer('category_id')->unsigned();
        $table->integer('type_id')->unsigned();
        $table->integer('location_id')->nullable()->unsigned();
        $table->integer('orders_id')->nullable()->unsigned();
        $table->string('name')->nullable();
        $table->integer('year')->nullable();
        $table->integer('seats')->default(5);
        $table->integer('doors')->default(4);
        $table->integer('bags')->default(2);
        $table->boolean('ac')->default(true);
        $table->integer('horsepower')->nullable();
        $table->string('transmission')->nullable();
        $table->string('fuel')->nullable();
        $table->string('body')->nullable();
        $table->string('drive_type')->nullable();
        $table->decimal('engine_capacity', 4, 1)->nullable();
        $table->integer('min_age')->default(21);
        $table->decimal('excess', 15, 2)->default(0);
        $table->decimal('price_day', 15, 2)->default(0);
        $table->decimal('deposit', 15, 2)->default(0);
        $table->decimal('discount', 15, 2)->default(0);
        $table->boolean('is_active')->default(true);
        $table->string('img')->nullable();                
        $table->text('images')->nullable();             
        $table->text('description')->nullable();             
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
    });
}

public function down()
{
    Schema::dropIfExists('rentme_rent_cars');
}
}
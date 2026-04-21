<?php namespace RentMe\Rent\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateRentmeRentLocations extends Migration
{
    public function up()
{
    Schema::create('rentme_rent_locations', function($table)
    {
        $table->engine = 'InnoDB';
        $table->increments('id')->unsigned();
        $table->string('code', 25)->nullable();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
    });
}

public function down()
{
    Schema::dropIfExists('rentme_rent_locations');
}
}

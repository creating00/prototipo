<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_internal_number_unique');

            $table->unique(
                ['branch_id', 'internal_number'],
                'sales_branch_internal_number_unique'
            );
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_branch_internal_number_unique');
            $table->unique('internal_number', 'sales_internal_number_unique');
        });
    }
};

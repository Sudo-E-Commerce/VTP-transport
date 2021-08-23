<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('viettelpot_id')->after('id')->default('null'); // max đơn hàng ở VTP
            $table->string('shipping_method')->after('payment_status')->default(null); // Loại dịch vụ vận chuyển VTP
            $table->string('shipping_fee')->after('shipping_method')->default(null); // Phí vận chuyển VTP trả về
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('viettelpot_id');
            $table->dropColumn('shipping_method');
            $table->dropColumn('shipping_fee');
        });
    }
}

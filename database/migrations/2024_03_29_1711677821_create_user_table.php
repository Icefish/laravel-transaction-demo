<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTable extends Migration
{
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 30)->default('')->comment('帳號');
            $table->string('alias', 50)->default('')->comment('暱稱');
            $table->tinyInteger('enable', false)->comment('停啟用');
            $table->tinyInteger('block', false)->comment('凍結');
            $table->string('code', 10)->default('')->comment('邀請碼');
            $table->decimal('balance', 16, 4)->comment('帳戶餘額');
            $table->decimal('pre_sub', 16, 4)->comment('預扣');
            $table->datetime('created_at')->comment('創建時間');
            $table->datetime('modified_at')->comment('修改時間');
            $table->string('password', 100)->default('')->comment('登入密碼');
            $table->datetime('login_at')->nullable()->default(null)->comment('登入時間');
            $table->integer('err_num', false, 11)->comment('登入錯誤次數');
            $table->string('memo', 100)->default('')->comment('備註');
            $table->primary('id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user');
    }
}


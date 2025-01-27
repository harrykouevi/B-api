<?php
/*
 * File name: 2018_07_17_180731_create_settings_table.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;

class CreateSettingsTable extends Migration
{
    public function __construct()
    {
        if (version_compare(Application::VERSION, '5.0', '>=')) {
            $this->tablename = Config::get('settings.table');
            $this->keyColumn = Config::get('settings.keyColumn');
            $this->valueColumn = Config::get('settings.valueColumn');
        } else {
            $this->tablename = Config::get('anlutro/l4-settings::table');
            $this->keyColumn = Config::get('anlutro/l4-settings::keyColumn');
            $this->valueColumn = Config::get('anlutro/l4-settings::valueColumn');
        }
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->increments('id');
            $table->string($this->keyColumn)->index();
            $table->text($this->valueColumn);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists($this->tablename);
    }
}

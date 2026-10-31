<?php

use App\Support\Members;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Member accounts. On Hostinger the tables are created on first use instead
// (Members::ensureTables), so this only matters for local / fresh installs.
return new class extends Migration
{
    public function up(): void
    {
        Members::forgetTables();
        Members::ensureTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('member_password_resets');
        Schema::dropIfExists('members');
        Members::forgetTables();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * O módulo 'agenda' é novo — sem isso, todo mundo (inclusive quem já
     * é admin full) ficaria sem acesso à Agenda até alguém entrar na tela
     * de permissões e liberar manualmente. Pra evitar essa surpresa, quem
     * já tinha admin=full em uma empresa ganha agenda=full automático.
     * Usuários "comuns" continuam nascendo em 'none', como sempre.
     */
    public function up(): void
    {
        $admins = DB::table('permissions')
            ->where('module', 'admin')
            ->where('level', 'full')
            ->get(['company_id', 'user_id']);

        foreach ($admins as $admin) {
            DB::table('permissions')->updateOrInsert(
                ['company_id' => $admin->company_id, 'user_id' => $admin->user_id, 'module' => 'agenda'],
                ['level' => 'full', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('module', 'agenda')->delete();
    }
};

<?php

use App\Models\Caja;
use App\Models\User;
//use Illuminate\Foundation\Testing\RefreshDatabase;

//uses(RefreshDatabase::class);

describe('Modelo Caja', function () {

    it('verifica que el modelo Caja existe y usa la tabla correcta', function () {
        $caja = new Caja();

        dump('Tabla usada por el modelo:', $caja->getTable());

        expect($caja)
            ->toBeInstanceOf(Caja::class)
            ->and($caja->getTable())->toBe('cajas');
    });

    it('Verifica los campos permitidos para registro o actualización en Caja', function () {
        $caja = new Caja();
        $fillable = $caja->getFillable();

        dump('Campos definidos:', $fillable);

        $expected = [
            'user_id',
            'fecha_apertura',
            'fecha_cierre',
            'saldo_inicial',
            'saldo_final',
            'estado',
            'observacion',
        ];

        expect($fillable)->toEqual($expected);
    });

    it('verifica que la relación user funciona correctamente', function () {
        $user = User::factory()->create();
        $caja = Caja::factory()->create(['user_id' => $user->id]);

        dump('Usuario relacionado:', $user->toArray());
        dump('Caja creada con relación:', $caja->toArray());

        expect($caja->user)->toBeInstanceOf(User::class)
            ->and($caja->user->id)->toBe($user->id);
    });

    it('verifica la lógica del saldo positivo en Caja cerrada', function () {
        $caja = Caja::factory()->cerrada()->create([
            'saldo_inicial' => 500.00,
            'saldo_final' => 750.00,
        ]);

        dump('Caja cerrada creada:', $caja->toArray());

        $diferencia = $caja->saldo_final - $caja->saldo_inicial;

        dump('Diferencia de saldo calculada:', $diferencia);

        expect($diferencia)
            ->toBe(250.00)
            ->and($diferencia)->toBeGreaterThan(0);
    });
});

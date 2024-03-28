<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CashEntry extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const DEPOSIT = 1001; // 入款
    const WITHDRAWAL = 1002; // 出款
    const DEPOSIT_MANUAL = 1010; // 人工存入
    const WITHDRAWAL_MANUAL = 1019; // 人工提出

    protected $table = 'cash_entry';

    /**
     * The model default value
     *
     * @var array
     */
    protected $attributes = [
        'ref_id' => 0,
        'memo' => '',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'opcode',
        'created_at',
        'amount',
        'balance',
        'ref_id',
        'memo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'opcode' => 'integer',
        'created_at' => 'datetime',
        'amount' => 'double',
        'balance' => 'double',
        'ref_id' => 'integer',
        'memo' => 'string'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
}

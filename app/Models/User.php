<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User Model
 *
 * @category Model
 * @package  App\Models
 * @author   Icefish <by160311@gmail.com>
 * @license MIT
 * @link     https://laravel.com/docs/9.x/eloquent
 */

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user';

    /**
     * The model default value
     *
     * @var array
     */
    protected $attributes = [
        'system' => 0,
        'enable' => true,
        'block' => false,
        'code' => '',
        'balance' => 0,
        'pre_sub' => 0,
        'err_num' => 0,
        'memo' => '',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'alias',
        'system',
        'enable',
        'block',
        'code',
        'balance',
        'pre_sub',
        'created_at',
        'modified_at',
        'password',
        'err_num',
        'memo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'username' => 'string',
        'alias' => 'string',
        'role' => 'integer',
        'enable' => 'boolean',
        'block' => 'boolean',
        'code' => 'string',
        'balance' => 'double',
        'pre_sub' => 'double',
        'created_at' => 'datetime',
        'modified_at' => 'datetime',
        'password' => 'string',
        'login_at' => 'datetime',
        'err_num' => 'integer',
        'memo' => 'string'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'modified_at';
}

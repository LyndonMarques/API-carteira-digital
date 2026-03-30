<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// ESTAS DUAS LINHAS SÃO OBRIGATÓRIAS PARA O RELACIONAMENTO FUNCIONAR
use Illuminate\Database\Eloquent\Relations\HasOne; 
use App\Models\Wallet; 

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasOne<Wallet, $this>
     */
    public function wallet(): HasOne
    {
        // Agora o User sabe exatamente onde encontrar a classe Wallet
        return $this->hasOne(Wallet::class);
    }
}
<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    // Apenas estes campos podem ser inseridos via array. O saldo está protegido.
    protected $fillable = [
        'user_id',
        'currency',
    ];

    // Garante que o balance seja sempre tratado com precisão matemática pelo framework
    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     *  @return Hasmany<Transaction,  $this>
    */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
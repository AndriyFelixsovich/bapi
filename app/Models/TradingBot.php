<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingBot extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'base_order_size',
        'grid_count',
        'grid_step',
        'martingale_percent',
        'take_profit_percent',
        'stop_loss_percent',
        'leverage',
        'is_active',
        'total_invested',
        'total_profit',
        'current_cycle_orders',
        'config'
    ];

    protected $casts = [
        'config' => 'array',
        'current_cycle_orders' => 'array'
    ];

    public function orders()
    {
        return $this->hasMany(TradingOrder::class);
    }
}

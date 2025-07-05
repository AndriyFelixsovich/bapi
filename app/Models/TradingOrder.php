<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingOrder extends Model
{
    protected $fillable = [
        'trading_bot_id',
        'bybit_order_id',
        'symbol',
        'side',
        'order_type',
        'qty',
        'price',
        'status',
        'executed_qty',
        'executed_price',
        'order_level',
        'is_take_profit'
    ];

    public function bot()
    {
        return $this->belongsTo(TradingBot::class);
    }
}

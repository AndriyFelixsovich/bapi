<?php

namespace App\Services;

use App\Models\TradingBot;
use App\Models\TradingOrder;
use Illuminate\Support\Facades\Log;

class DCABotService
{
    private $bybitApi;

    public function __construct(BybitApiService $bybitApi)
    {
        $this->bybitApi = $bybitApi;
    }

    public function createBot($config)
    {
        return TradingBot::create([
            'name' => $config['name'],
            'symbol' => $config['symbol'],
            'base_order_size' => $config['base_order_size'],
            'grid_count' => $config['grid_count'],
            'grid_step' => $config['grid_step'],
            'martingale_percent' => $config['martingale_percent'],
            'take_profit_percent' => $config['take_profit_percent'],
//            'stop_loss_percent' => $config['stop_loss_percent'],
//            'leverage' => $config['leverage'],
            'is_active' => false,
            'config' => $config
        ]);
    }

    public function startBot(TradingBot $bot)
    {
        $currentPrice = $this->bybitApi->getCurrentPrice($bot->symbol);

        if (!$currentPrice) {
            Log::error("Не вдалося отримати поточну ціну для {$bot->symbol}");
            return false;
        }

        // Створюємо сітку ордерів
        $this->createGridOrders($bot, $currentPrice);

        $bot->update(['is_active' => true]);

        Log::info("Бот {$bot->name} запущено. Поточна ціна: {$currentPrice}");
        return true;
    }

    private function createGridOrders(TradingBot $bot, $currentPrice)
    {
        $gridStep = $bot->grid_step / 100; // конвертуємо в десятковий формат
        $baseOrderSize = $bot->base_order_size;
        $martingalePercent = $bot->martingale_percent / 100;

        for ($i = 1; $i <= $bot->grid_count; $i++) {
            // Розрахунок ціни для ордера
            $orderPrice = $currentPrice * (1 - ($gridStep * $i));

            // Розрахунок розміру ордера з мартингейлом
            $orderSize = $baseOrderSize * pow((1 + $martingalePercent), $i - 1);

            // Розміщення ордера на Bybit
            $response = $this->bybitApi->placeOrder(
                $bot->symbol,
                'Buy',
                'Limit',
                $orderSize,
                $orderPrice
            );

            if ($response && $response['retCode'] === 0) {
                // Збереження в БД
                TradingOrder::create([
                    'trading_bot_id' => $bot->id,
                    'bybit_order_id' => $response['result']['orderId'],
                    'symbol' => $bot->symbol,
                    'side' => 'Buy',
                    'order_type' => 'Limit',
                    'qty' => $orderSize,
                    'price' => $orderPrice,
                    'status' => 'New',
                    'order_level' => $i,
                    'is_take_profit' => false
                ]);

                Log::info("Створено ордер рівня {$i}: {$orderSize} за ціною {$orderPrice}");
            } else {
                Log::error("Помилка створення ордера рівня {$i}: " . json_encode($response));
            }
        }
    }

    public function checkAndUpdateOrders(TradingBot $bot)
    {
        $activeOrders = $bot->orders()->where('status', 'New')->get();
        $executedOrders = [];

        foreach ($activeOrders as $order) {
            $status = $this->bybitApi->getOrderStatus($order->bybit_order_id);

            if ($status && $status['retCode'] === 0) {
                $orderData = $status['result']['list'][0] ?? null;

                if ($orderData && $orderData['orderStatus'] === 'Filled') {
                    $order->update([
                        'status' => 'Filled',
                        'executed_qty' => $orderData['cumExecQty'],
                        'executed_price' => $orderData['avgPrice']
                    ]);

                    $executedOrders[] = $order;
                    Log::info("Ордер {$order->bybit_order_id} виконано за ціною {$orderData['avgPrice']}");
                }
            }
        }

        // Якщо є виконані ордери, перевіряємо чи потрібно створити take profit
        if (!empty($executedOrders)) {
            $this->checkTakeProfitCondition($bot);
        }
    }

    private function checkTakeProfitCondition(TradingBot $bot)
    {
        $filledOrders = $bot->orders()
            ->where('status', 'Filled')
            ->where('is_take_profit', false)
            ->get();

        if ($filledOrders->isEmpty()) {
            return;
        }

        // Розрахунок середньої ціни
        $totalQty = $filledOrders->sum('executed_qty');
        $totalCost = $filledOrders->sum(function ($order) {
            return $order->executed_qty * $order->executed_price;
        });

        $avgPrice = $totalCost / $totalQty;
        $takeProfitPrice = $avgPrice * (1 + $bot->take_profit_percent / 100);

        // Створення take profit ордера
        $response = $this->bybitApi->placeOrder(
            $bot->symbol,
            'Sell',
            'Limit',
            $totalQty,
            $takeProfitPrice
        );

        if ($response && $response['retCode'] === 0) {
            TradingOrder::create([
                'trading_bot_id' => $bot->id,
                'bybit_order_id' => $response['result']['orderId'],
                'symbol' => $bot->symbol,
                'side' => 'Sell',
                'order_type' => 'Limit',
                'qty' => $totalQty,
                'price' => $takeProfitPrice,
                'status' => 'New',
                'order_level' => 0,
                'is_take_profit' => true
            ]);

            Log::info("Створено Take Profit ордер: {$totalQty} за ціною {$takeProfitPrice}");
        }
    }

    public function stopBot(TradingBot $bot)
    {
        // Скасовуємо всі активні ордери
        $activeOrders = $bot->orders()->where('status', 'New')->get();

        foreach ($activeOrders as $order) {
            // Тут код для скасування ордера через API
            $order->update(['status' => 'Cancelled']);
        }

        $bot->update(['is_active' => false]);
        Log::info("Бот {$bot->name} зупинено");
    }
}

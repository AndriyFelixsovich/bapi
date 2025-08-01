<?php

namespace App\Http\Controllers;

use App\Models\TradingBot;
use Illuminate\Http\Request;

class TradingBotController extends Controller
{
    private $botService;

    public function __construct(DCABotService $botService)
    {
        $this->botService = $botService;
    }

    public function index()
    {
        $bots = TradingBot::with('orders')->get();
        return view('trading.bots.index', compact('bots'));
    }

    public function create()
    {
        return view('trading.bots.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string',
            'base_order_size' => 'required|numeric|min:0',
            'grid_count' => 'required|integer|min:1|max:50',
            'grid_step' => 'required|numeric|min:0.1|max:10',
            'martingale_percent' => 'required|numeric|min:0|max:50',
            'take_profit_percent' => 'required|numeric|min:0.1|max:100',
            'stop_loss_percent' => 'required|numeric|min:1|max:50',
            'leverage' => 'required|integer|min:1|max:10'
        ]);

        $bot = $this->botService->createBot($validated);

        return redirect()->route('trading.bots.index')
            ->with('success', 'Бот створено успішно');
    }

    public function start(TradingBot $bot)
    {
        if ($this->botService->startBot($bot)) {
            return back()->with('success', 'Бот запущено успішно');
        }

        return back()->with('error', 'Помилка запуску бота');
    }

    public function stop(TradingBot $bot)
    {
        $this->botService->stopBot($bot);
        return back()->with('success', 'Бот зупинено');
    }
}

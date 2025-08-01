const ws = new WebSocket('wss://stream.bybit.com/v5/public/spot');

ws.onopen = function() {
    console.log('WebSocket підключено');
    // Підписатися на тікер BTCUSDT
    ws.send(JSON.stringify({
        "op": "subscribe",
        "args": ["tickers.BTCUSDT"]
    }));
};

ws.onmessage = function(event) {
    const data = JSON.parse(event.data);

    if (data.topic === 'tickers.BTCUSDT' && data.data) {
        const price = data.data.lastPrice;
        document.getElementById('btc-price').textContent =
            parseFloat(price).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
    }
};

ws.onerror = function(error) {
    console.error('WebSocket помилка:', error);
    document.getElementById('btc-price').textContent = 'Помилка';
};

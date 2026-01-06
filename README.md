# Exchange Engine (Assessment)

## Stack
- Laravel 12 + Inertia + Vue 3 (Composition API) + Tailwind
- MySQL
- Sanctum (auth)
- Pusher (realtime)

## Setup

**Backend:**
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

**Frontend:**
```bash
npm install
npm run dev
```

**Environment:**

Update `.env` with your database and Pusher credentials:
```env
DB_DATABASE=exchange_engine
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=ap2

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
VITE_BACKEND_URL=http://localhost:8000

APP_URL=http://localhost:8000
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000
SESSION_DOMAIN=localhost
```

## Demo Users (seeded)
- `buyer@test.com` / `password` (has USD)
- `seller@test.com` / `password` (has BTC/ETH)

## Features
- `/dashboard` - Wallet, orderbook, orders, trades, realtime updates
- `/exchange/order` - Limit order form

## API (auth:sanctum)
- `GET /api/profile`
- `GET /api/orders?symbol=BTC`
- `POST /api/orders`
- `POST /api/orders/{id}/cancel`
- `GET /api/trades?symbol=BTC` (bonus)
- `GET /api/my/trades?symbol=BTC` (bonus)

## Realtime
Broadcasts `OrderMatched` event to `private-user.{buyer_id}` and `private-user.{seller_id}` using `ShouldBroadcastNow` (no queue worker required).
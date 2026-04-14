# BC Platform - Postup lokalneho overenia funkcnosti

## 1. Rozsah riesenia

System sa sklada z troch casti:
- Backend API: Laravel 11 (PHP)
- Frontend klient: Vue 3 + Vite
- Realtime vrstva: Laravel Reverb (WebSocket)

## 2. Vstupne podmienky

Pre lokalne overenie je potrebne mat:
- PHP 8.2+
- Composer 2+
- Node.js 20+
- npm
- MySQL alebo MariaDB

Poznamka:
- Repozitar obsahuje `.env.example` subory s placeholder hodnotami; produkcne kluce v nich nie su.

## 3. Postup inicializacie

### 3.1 Priprava zdrojoveho kodu

~~~bash
git clone <URL_REPOZITARA>
cd bc_platform
~~~

### 3.2 Inicializacia backendu

~~~bash
cd backend
composer install
~~~

Vytvorenie lokalnej konfiguracie:

~~~powershell
Copy-Item .env.example .env
~~~

Minimalne nastavit lokalne hodnoty pre:
- URL (`APP_URL`, `FRONTEND_URL`)
- databazu (`DB_*`)
- CORS/Sanctum (`SANCTUM_STATEFUL_DOMAINS`, `CORS_ALLOWED_ORIGINS`)
- cookies pre lokalny HTTP rezim (`SESSION_SECURE_COOKIE=false`)

Dokoncenie backend pripravy:

~~~bash
php artisan key:generate
php artisan migrate
~~~

### 3.3 Inicializacia frontendu

~~~bash
cd ../frontend
npm install
~~~

Vytvorenie lokalnej konfiguracie:

~~~powershell
Copy-Item .env.example .env.local
~~~

Minimalne nastavit:
- `VITE_API_URL` na lokalny backend (`http://127.0.0.1:8000/api`)
- `VITE_REVERB_*` hodnoty v sulade s backend `REVERB_*`

## 4. Spustenie lokalneho prostredia

Backend cast:

~~~bash
cd backend
php artisan serve --host=127.0.0.1 --port=8000
php artisan queue:work
php artisan reverb:start
~~~

Frontend cast (samostatny terminal):

~~~bash
cd frontend
npm run dev
~~~

## 5. Overenie funkcnosti

Po spusteni ma byt dostupne:
- frontend: `http://localhost:5173`
- backend API: `http://127.0.0.1:8000`

Uroven overenia pre oponenta:
- aplikacia sa nacita bez runtime chyby,
- autentifikacia a praca so session funguje v lokalnom prostredi,
- CRUD operacie nad datami prebiehaju bez DB chyb,
- realtime komunikacia funguje pri bezacom Reverb serveri.
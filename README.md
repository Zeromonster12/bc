# BC Platform - Postup lokalneho overenia funkcnosti

## 1. Rozsah riesenia

System sa sklada z troch casti:
- Backend API: Laravel 11 (PHP)
- Frontend klient: Vue 3 + Vite
- Realtime vrstva: Laravel Reverb (WebSocket)

Pre plnu funkcionalitu system pouziva aj:
- S3 kompatibilne objektove ulozisko (MinIO)
- Antivirus kontrolu CV suborov (ClamAV)

## 2. Vstupne podmienky

Pre lokalne overenie je potrebne mat:
- PHP 8.2+
- Composer 2+
- Node.js 20+
- npm
- MySQL alebo MariaDB
- Docker Desktop alebo iny Docker runtime (pre MinIO a ClamAV)

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
- MinIO/S3 (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`)
- buckety (`USERCV_BUCKET`, `USERPFP_BUCKET`, `COMPANYAVATAR_BUCKET`, `GROUPAVATAR_BUCKET`)
- ClamAV (`CV_ANTIVIRUS_ENABLED`, `CV_ANTIVIRUS_REQUIRED`, `CV_ANTIVIRUS_DRIVER`, `CV_ANTIVIRUS_CLAMD_HOST`, `CV_ANTIVIRUS_CLAMD_PORT`)

Dokoncenie backend pripravy:

~~~bash
php artisan key:generate
php artisan migrate
~~~

### 3.3 Spustenie podpornych sluzieb (MinIO a ClamAV)

MinIO image a kontajner:

~~~bash
docker build -t bc-minio -f minio/Dockerfile minio
docker run -d --name bc-minio -p 9000:9000 -p 9001:9001 -e MINIO_ROOT_USER=minioadmin -e MINIO_ROOT_PASSWORD=minioadmin123 bc-minio
~~~

ClamAV image a kontajner:

~~~bash
docker build -t bc-clamav -f ClamAV/Dockerfile ClamAV
docker run -d --name bc-clamav -p 3310:3310 bc-clamav
~~~

Pozadovane kroky po starte MinIO:
- otvorit konzolu MinIO na `http://127.0.0.1:9001`,
- vytvorit buckety `usercv`, `userpfp`, `companyavatar`, `groupavatar`,
- pouzit rovnake pristupove udaje ako v backend `.env` (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`).

Odporucane lokalne hodnoty pre backend `.env`:
- `AWS_ENDPOINT=http://127.0.0.1:9000`
- `AWS_USE_PATH_STYLE_ENDPOINT=true`
- `CV_ANTIVIRUS_ENABLED=true`
- `CV_ANTIVIRUS_REQUIRED=true`
- `CV_ANTIVIRUS_DRIVER=clamd_tcp`
- `CV_ANTIVIRUS_CLAMD_HOST=127.0.0.1`
- `CV_ANTIVIRUS_CLAMD_PORT=3310`

### 3.4 Inicializacia frontendu

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
- realtime komunikacia funguje pri bezacom Reverb serveri,
- upload avatarov a CV prebieha do MinIO,
- CV subory su po uploade overene cez ClamAV (stav skenu sa zobrazuje v aplikacii).

## 6. Dopad neaktivnych sluzieb

Ak nie je dostupne S3 kompatibilne ulozisko (MinIO alebo ekvivalent), nebudu fungovat funkcionality zavisle od diskov `usercv`, `userpfp`, `companyavatar`, `groupavatar`.

Ak nie je dostupny ClamAV a `CV_ANTIVIRUS_REQUIRED=true`, upload CV bude blokovany.

Ak je cielom iba ciastocne lokalne overenie bez antivirusu, je mozne docasne pouzit:
- `CV_ANTIVIRUS_ENABLED=false`
- `CV_ANTIVIRUS_REQUIRED=false`
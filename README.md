# Номын сангийн удирдлагын систем (LMS)

Номын санчид зориулсан вэб систем. Номыг ангиллаар бүртгэж, хувь тус бүрийг бүртгэлийн дугаартай хадгална. Уншигчдад ном зээлүүлж буцааж авна, одоо аль ном хэнд байгааг нэг дороос харна.

**Stack:** Laravel 13 · Inertia 3 + Vue 3 + TypeScript · Tailwind 4 (shadcn-vue) · MySQL 8.4 · Docker

## Боломжууд

| Хэсэг               | Юу хийдэг вэ                                                                                   |
| ------------------- | ---------------------------------------------------------------------------------------------- |
| Хянах самбар        | Номын тоо, зээлд байгаа, хугацаа хэтэрсэн, ойрын 3 хоногт буцаах зээлүүд                       |
| Ном                 | Нэр, зохиогч, ISBN, бүртгэлийн дугаараар хайх. Ангилал, байгаа эсэхээр шүүх                    |
| Хувь                | Нэг номын олон биет хувь (`INV-000001`). Төлөв: байгаа, зээлсэн, гэмтсэн, алдагдсан, хасагдсан |
| Ангилал             | Ангилал нэмэх, засах. Номтой ангиллыг устгахгүй                                                |
| Уншигч              | Үнэмлэхний дугаар (`M-00001`) автоматаар үүснэ. Идэвхгүй болгох боломжтой                      |
| Ном зээлүүлэх       | Уншигч, хувийг хайж сонгоно. Буцаах огноо анхдагчаар 14 хоног                                  |
| Хэнд ямар ном байна | Одоо зээлд байгаа бүх ном, хугацаа хэтэрсэн нь улаанаар. Эндээс буцааж авах, сунгах            |
| Зээлийн түүх        | Бүх зээл огноо, төлөвөөр шүүгдэнэ                                                              |

Зээлийн дүрэм:

- Нэг хувь нэг зэрэг зөвхөн нэг хүнд байна. Үүнийг өгөгдлийн сангийн unique index бас хамгаална.
- Идэвхгүй уншигч ном зээлэхгүй.
- Нэг уншигч зэрэг 5-аас олон ном зээлэхгүй.
- Хугацаа хэтэрсэн зээлийг сунгавал өнөөдрөөс эхэлж тоолно.
- Буцааж авахдаа хувийг «гэмтсэн» эсвэл «алдагдсан» гэж тэмдэглэж болно.

Дүрмийн тоонуудыг `.env` дотор өөрчилнө:

```dotenv
LIBRARY_LOAN_DAYS=14
LIBRARY_RENEW_DAYS=7
LIBRARY_MAX_ACTIVE_LOANS=5
```

Зөвхөн номын санч нэвтэрнэ. Өөрөө бүртгүүлэх боломжгүй. Уншигчид систем рүү нэвтрэхгүй, зөвхөн бүртгэл болж хадгалагдана.

## Хөгжүүлэлт (dev)

Компьютер дээр зөвхөн **Docker** болон **make** хэрэгтэй. PHP, Composer, Node суулгах шаардлагагүй.

```bash
make init     # анх удаа: image build, composer/npm install, APP_KEY, migrate + demo өгөгдөл
make dev      # дараагийн удаа асаах
```

| Хаяг                  |                          |
| --------------------- | ------------------------ |
| http://localhost:8080 | Систем                   |
| http://localhost:5173 | Vite dev server (HMR)    |
| `localhost:3306`      | MySQL (`lms` / `secret`) |

Нэвтрэх: `admin@lms.local` / `password`

Портыг `.env` дотор `APP_PORT`, `VITE_PORT`, `FORWARD_DB_PORT`-оор сольж болно.

### Түгээмэл командууд

```bash
make help                        # бүх командын жагсаалт
make fresh                       # DB-г цэвэрлээд demo өгөгдөл дахин үүсгэх
make test                        # тест (lms_test DB дээр)
make check                       # lint + type check + тест
make lint                        # Pint + vp check --fix
make shell                       # app контейнер руу орох
make artisan cmd="route:list"
make composer cmd="require vendor/package"
make npm cmd="install some-package"
make logs
make down
```

### Контейнерууд

| Service | Үүрэг                                                         |
| ------- | ------------------------------------------------------------- |
| `app`   | PHP 8.4 FPM + Composer + Node 24, код bind mount-оор орно     |
| `vite`  | `npm run dev` (HMR, Wayfinder route-уудыг автоматаар үүсгэнэ) |
| `nginx` | HTTP, 8080 порт                                               |
| `mysql` | MySQL 8.4, `lms` болон тестийн `lms_test` DB                  |

## Production

Сервер дээр Docker, make, git байхад хангалттай. Image-ийг сервер дээр build хийнэ.

### Анх удаа суулгах

```bash
git clone <repo-url> lms && cd lms
cp .env.production.example .env.production
# .env.production дотор APP_URL, DB_PASSWORD, DB_ROOT_PASSWORD, ADMIN_* утгуудыг бөглөнө
make prod-key        # APP_KEY үүсгэнэ
make prod-deploy     # build -> migrate -> асаана
make prod-seed       # анхны номын санч + үндсэн ангиллууд (нэг удаа)
```

Систем `APP_PORT` (анхдагч 80) дээр HTTP-ээр ажиллана. HTTPS-ийг урд талын reverse proxy (Caddy, Traefik, nginx) хариуцна. Proxy-гийн ард байгаа тул `.env.production` дотор `APP_URL`-ийг `https://...` гэж бичнэ.

### Шинэчлэх

```bash
git pull
make prod-backup     # заавал биш, гэхдээ зөвлөмж
make prod-deploy
```

`prod-deploy` шинэ image build хийнэ. Дараа нь шинэ код дээр migration ажиллуулаад контейнеруудыг солино.

### Бусад

```bash
make prod-logs
make prod-ps
make prod-shell
make prod-artisan cmd="about"
make prod-backup                              # backups/lms-YYYYmmdd-HHMMSS.sql.gz
make prod-restore file=backups/lms-....sql.gz # асуулт асууж баталгаажуулна
make prod-down                                # өгөгдөл (volume) устахгүй
```

Prod image-д Composer, Node байхгүй. Front-end build хийгдсэн байдлаар `nginx` image дотор ордог. `app` контейнер асахдаа `php artisan optimize` ажиллуулна. Migration автоматаар ажиллахгүй, `prod-deploy` эсвэл `prod-migrate`-аар ажиллана.

## Номын санч нэмэх

Бүртгүүлэх хуудас хаалттай тул шинэ номын санчийг командаар нэмнэ. Мөн мартсан нууц үгийг энэ командаар солино.

```bash
make artisan cmd="library:librarian"           # dev
make prod-artisan cmd="library:librarian"      # production
```

## Кодын бүтэц

```
app/
  Enums/CopyStatus.php          хувийн төлөв + монгол нэр
  Services/LoanService.php      зээл олгох / буцаах / сунгах (бүх дүрэм энд)
  Exceptions/LoanException.php  дүрэм зөрчигдсөн үеийн мессеж
  Http/Controllers/             Book, BookCopy, Category, Member, Loan, Lookup, Dashboard
  Http/Requests/                form validation
  Models/                       Book, BookCopy, Category, Member, Loan
config/library.php              зээлийн дүрмийн тохиргоо
database/seeders/               AdminUser, Category, DemoData, Production
resources/js/pages/             books/, categories/, members/, loans/, Dashboard.vue
resources/js/components/        SearchSelect, ReturnLoanDialog, DueBadge, ...
docker/                         Dockerfile (dev/build/prod/nginx stage), nginx, php.ini
compose.yml / compose.prod.yml  dev / production стек
tests/Feature/                  LoanService, Loan, Book, Member, Category, Access
```

Хуудаснууд route-уудыг [Wayfinder](https://github.com/laravel/wayfinder)-ээр дууддаг. `resources/js/actions` болон `resources/js/routes` хавтсууд Vite ажиллах үед автоматаар үүснэ, git-д орохгүй.

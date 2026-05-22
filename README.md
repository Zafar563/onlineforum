# StackHub — Texnologik Hamjamiyat va Muloqot Markazi

> Zamonaviy, premium dizaynli va to'liq funksional onlayn forum platformasi.

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

---

## 📖 Loyiha Haqida

**StackHub** — bu PHP va MySQL yordamida qurilgan, zamonaviy **Glassmorphism + Sleek Dark Mode** dizayniga ega to'laqonli onlayn forum platformasi. Foydalanuvchilar kategoriyalar bo'yicha mavzular ochishi, izohlar qoldirishi, mavzularga layk bosishi va profilini boshqarishi mumkin. AJAX (Fetch API) yordamida sahifani yangilamasdan real-vaqtda ishlash ta'minlangan.

---

## 🛠️ Ishlatilgan Texnologiyalar

### Backend
| Texnologiya | Versiya | Maqsad |
|---|---|---|
| **PHP** | 7.4+ | Server-side mantiqi va sahifalarni yaratish |
| **MySQL** | 8.0+ | Ma'lumotlar bazasi |
| **PDO** | PHP built-in | Xavfsiz va zamonaviy DB aloqasi |
| **PHP Sessions** | built-in | Foydalanuvchi autentifikatsiyasi |
| **password_hash()** | built-in | Parollarni xavfsiz shifrlash (bcrypt) |

### Frontend
| Texnologiya | Versiya | Maqsad |
|---|---|---|
| **HTML5** | — | Sahifalar tuzilmasi (semantic HTML) |
| **Vanilla CSS3** | — | Premium Glassmorphism dizayn tizimi |
| **JavaScript (ES6+)** | — | AJAX, form validatsiyasi, interaktivlik |
| **Fetch API** | built-in | AJAX so'rovlari (layk, izoh) |
| **Quill.js** | 2.0.2 | Rich Text Editor (matn muharriri) |
| **Bootstrap Icons** | 1.11.1 | UI ikonkalari |
| **Google Fonts** | — | Outfit & Inter shriftlari |

---

## 🗂️ Fayl Tuzilmasi

```
stackhub/
│
├── 📄 index.php          → Bosh sahifa (kategoriyalar + statistika + qidiruv)
├── 📄 category.php       → Kategoriya ichidagi mavzular ro'yxati
├── 📄 topic.php          → Alohida mavzu va uning izohlari
├── 📄 create-topic.php   → Yangi mavzu yaratish formasi (Quill.js)
│
├── 📄 login.php          → Tizimga kirish sahifasi
├── 📄 register.php       → Ro'yxatdan o'tish sahifasi
├── 📄 logout.php         → Seansni xavfsiz tugatish
├── 📄 profile.php        → Shaxsiy kabinet va profil sozlamalari
├── 📄 admin.php          → Administrator boshqaruv paneli
│
├── 📄 api.php            → AJAX so'rovlari uchun PHP endpoint (layk + izoh)
├── 📄 config.php         → DB ulanishi, sessiya, xavfsizlik funksiyalari
├── 📄 setup.php          → Baza va jadvallarni avtomatik o'rnatish sahifasi
│
├── 🎨 style.css          → Asosiy CSS dizayn tizimi (Dark Mode + Glassmorphism)
├── ⚙️ app.js             → Frontend JavaScript mantiqi (AJAX, Quill, Toast)
└── 🗃️ schema.sql         → Ma'lumotlar bazasi tuzilmasi (SQL skripti)
```

---

## 🗃️ Ma'lumotlar Bazasi Tuzilmasi

Loyiha 5 ta asosiy jadvaldan iborat:

```sql
users        → Foydalanuvchilar (id, username, email, password_hash, role, avatar_color, bio)
categories   → Forum bo'limlari (id, name, description, icon)
topics       → Mavzular (id, category_id, user_id, title, content, views)
posts        → Izohlar (id, topic_id, user_id, content)
likes        → Layklar (id, user_id, topic_id) — UNIQUE constraint bilan
```

Jadvallar orasidagi bog'liqliklar **Foreign Key + CASCADE DELETE** orqali ta'minlangan.

---

## 👤 Foydalanuvchi Rollari

| Rol | Imkoniyatlar |
|---|---|
| **Mehmon** | Faqat mavzularni o'qiydi |
| **User (A'zo)** | Mavzu ochadi, izoh yozadi, layk bosadi, profilini tahrirlaydi |
| **Moderator** | A'zo imkoniyatlari + kengaytirilgan huquqlar |
| **Admin** | Barcha huquqlar: kategoriyalar qo'shish/o'chirish, a'zolar rollarini boshqarish |

---

## 🔒 Xavfsizlik

- **SQL Injection** dan himoya: barcha so'rovlar `PDO Prepared Statements` orqali amalga oshiriladi
- **XSS** dan himoya: foydalanuvchi kiritgan barcha matnlar `htmlspecialchars()` (`esc()` funksiyasi) orqali filtrlangan
- **Parol shifrlash**: `password_hash()` (bcrypt algoritmi) va `password_verify()` ishlatiladi
- **Sessiya xavfsizligi**: `httponly` cookie, `session_destroy()` orqali to'liq chiqish

---

## ⚡ Asosiy Xususiyatlar

- 🌑 **Premium Dark Mode** — Sleek qorong'u dizayn + Glassmorphism kartalar
- 🔍 **Qidiruv tizimi** — Barcha mavzular bo'yicha kalit so'z qidirish
- ❤️ **AJAX Layk tizimi** — Sahifa yangilanmasdan layk bosish/qaytarish
- 💬 **AJAX Izohlar** — Sahifa yangilanmasdan real-vaqtda izoh qo'shish
- 📝 **Quill.js muharriri** — Qalin, kursiv, ro'yxat va havolali matn yozish
- 🎨 **Profil sozlamalari** — Bio tahrirlash va 6 ta rangli avatar tanlash
- 📊 **Forum statistikasi** — A'zolar, mavzular va izohlar soni
- 📱 **Responsiv dizayn** — Telefon, planshet va kompyuterga to'liq moslashgan

---

## 🚀 Loyihani Ishga Tushirish

### 1-qadam: Mahalliy serverni tayyorlash
XAMPP yoki Laragon dasturini o'rnating va **Apache** hamda **MySQL** xizmatlarini ishga tushiring.

```
XAMPP uchun papka: C:\xampp\htdocs\stackhub\
Laragon uchun papka: C:\laragon\www\stackhub\
```

### 2-qadam: Bazani avtomatik sozlash
Brauzerda quyidagi manzilni oching:

```
http://localhost/stackhub/setup.php
```

Bu sahifa avtomatik ravishda:
- ✅ `online_forum` bazasini yaratadi
- ✅ Barcha 5 ta jadvalni o'rnatadi
- ✅ Boshlang'ich kategoriyalarni qo'shadi
- ✅ Default **Admin** foydalanuvchisini yaratadi

### 3-qadam: Forumga kirish
```
http://localhost/stackhub/index.php
```

**Default Admin hisobi:**
```
Foydalanuvchi nomi : admin
Parol              : adminpassword
```

> ⚠️ **Muhim:** Birinchi kirishdan so'ng Admin profilidan parolni o'zgartirishingizni tavsiya qilamiz!

---

## ⚙️ Sozlamalar (`config.php`)

Agar MySQL sozlamalaringiz farq qilsa, `config.php` faylini oching va quyidagilarni o'zgartiring:

```php
define('DB_HOST', '127.0.0.1');   // MySQL server manzili
define('DB_NAME', 'online_forum'); // Baza nomi
define('DB_USER', 'root');         // MySQL foydalanuvchi nomi
define('DB_PASS', '');             // MySQL paroli (bo'sh qoldiring yoki o'zgartiring)
```

---

## 📸 Sahifalar

| Sahifa | Manzil | Tavsif |
|---|---|---|
| Bosh sahifa | `/index.php` | Kategoriyalar, statistika, qidiruv |
| Kategoriya | `/category.php?id=1` | Mavzular ro'yxati |
| Mavzu | `/topic.php?id=1` | Mavzu + izohlar + layk |
| Yangi mavzu | `/create-topic.php` | Quill.js muharriri bilan |
| Kirish | `/login.php` | Tizimga kirish |
| Ro'yxat | `/register.php` | Hisob yaratish |
| Profil | `/profile.php` | Shaxsiy kabinet |
| Admin | `/admin.php` | Boshqaruv paneli |
| Sozlash | `/setup.php` | Bir martalik o'rnatish |

---

## 📝 Litsenziya

Ushbu loyiha ochiq maqsadlarda va ta'lim uchun yaratilgan.

---

*StackHub — O'rganing, ulashing, o'sing!* 🚀

# 🚀 نصب TaskFlow در XAMPP (راهنمای کامل)

به زبان دری — قدم به قدم

---

## 📦 مرحله ۱: نصب XAMPP

اگر هنوز XAMPP نصب نکرده‌اید، از آدرس زیر دانلود و نصب کنید:

👉 https://www.apachefriends.org/download.html

---

## 📁 مرحله ۲: کپی کردن پروژه

1. فایل **`taskflow.zip`** را دانلود کنید.
2. آن را در پوشه زیر **اکسترکت (Extract)** کنید:

   ```
   C:\xampp\htdocs\        ← ویندوز
   /Applications/XAMPP/htdocs/   ← مک
   /opt/lampp/htdocs/      ← لینوکس
   ```

   ⚠️ **مهم:** مطمئن شوید که فایل‌های پروژه (مثل `index.php`، `login.php` و...) مستقیماً داخل پوشه `taskflow` باشند، نه در یک پوشه اضافی.

   یعنی ساختار درست:
   ```
   C:\xampp\htdocs\taskflow\
   ├── index.php
   ├── login.php
   ├── dashboard.php
   ├── database.sql
   ├── config\
   ├── includes\
   ├── api\
   └── assets\
   ```

---

## 🗄️ مرحله ۳: ساخت دیتابیس

### روش اول: با phpMyAdmin (آسان‌تر) ✅

1. **XAMPP Control Panel** را باز کنید.
2. دکمه **Start** کنار **Apache** و **MySQL** را بزنید (هر دو باید سبز شوند).
3. روی دکمه **Admin** کنار **MySQL** کلیک کنید — phpMyAdmin باز می‌شود.
4. از منوی بالا روی تب **Import** کلیک کنید.
5. روی **Choose File** کلیک کنید و فایل **`database.sql`** (که داخل پوشه پروژه است) را انتخاب کنید.
6. روی دکمه **Go / Import** در پایین صفحه کلیک کنید.
7. ✅ پیام موفقیت ظاهر می‌شود و دیتابیس `taskflow` با تمام جدول‌ها و اطلاعات نمونه ساخته می‌شود.

### روش دوم: با خط فرمان

```bash
# در Command Prompt ویندوز:
cd C:\xampp\mysql\bin
mysql -u root < C:\xampp\htdocs\taskflow\database.sql
```

---

## ⚙️ مرحله ۴: بررسی تنظیمات اتصال

فایل **`config/config.php`** را باز کنید. تنظیمات پیش‌فرض برای XAMPP آماده است:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'taskflow');
define('DB_USER', 'root');     // یوزر پیش‌فرض XAMPP
define('DB_PASS', '');         // در XAMPP پسورد خالی است
```

> اگر برای MySQL پسورد تنظیم کرده‌اید، آن را در `DB_PASS` بنویسید.

---

## 🌐 مرحله ۵: اجرای پروژه

مرورگر خود را باز کنید و به این آدرس بروید:

```
http://localhost/taskflow/
```

🎉 صفحه ورود TaskFlow ظاهر می‌شود!

---

## 👤 حساب‌های آماده برای ورود

| نقش (Role) | ایمیل | پسورد |
|---|---|---|
| **مدیر کل (Admin)** | `admin@taskflow.com` | `admin123` |
| **منیجر (Manager)** | `manager@taskflow.com` | `manager123` |
| **کارمند (Employee)** | `employee@taskflow.com` | `employee123` |

---

## 🔧 اگر مشکل پیش آمد

### مشکل: صفحه سفید یا خطای دیتابیس
- مطمئن شوید **MySQL** در XAMPP روشن (سبز) است.
- در phpMyAdmin چک کنید که دیتابیس `taskflow` وجود دارد.

### مشکل: خطای Connection refused
- اگر MySQL روی پورت دیگری اجرا می‌شود، `DB_PORT` را در `config/config.php` تغییر دهید.

### مشکل: فایل‌ها آپلود نمی‌شوند (attachments)
- به پوشه `uploads` دسترسی بدهید:
  ```bash
  # راست‌کلیک روی پوشه uploads → Properties → Security → اضافه کردن Everyone → Full Control
  ```

### مشکل: پسورد root تنظیم شده
- اگر هنگام نصب XAMPP پسورد برای MySQL گذاشته‌اید:
  ```php
  define('DB_PASS', 'پسورد_شما');
  ```

---

## 📞 خلاصه سریع

```
1. XAMPP را باز کنید → Apache و MySQL را Start کنید
2. فایل‌ها را در htdocs\taskflow بریزید
3. در phpMyAdmin فایل database.sql را Import کنید
4. مرورگر را باز کنید → localhost/taskflow
5. با admin@taskflow.com / admin123 وارد شوید
```

🎉 **تمام!** حالا TaskFlow شما کاملاً آماده استفاده است.

---

اگر سوالی داشتید بپرسید! 🙌

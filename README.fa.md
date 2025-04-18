# جدول لیگ ایران
[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/LordArma/Iranian-League-Table)
[![fa](https://img.shields.io/badge/lang-fa-yellow.svg)](https://github.com/LordArma/Iranian-League-Table/blob/master/README.fa.md)

افزونه وردپرس که به سایت‌های وردپرسی کمک می‌کند جدول لیگ برتر ایران (لیگ خلیج فارس) یا لیگ یک ایران (لیگ آزادگان) را به صورت ویجت یا شورتکد به فارسی نمایش دهند.

جدول‌ها به زبان فارسی نمایش داده می‌شوند و می‌توان آنها را در رنگ، نوع و اندازه‌های گوناگون سفارشی کرد تا با محل نمایش آنها مطابقت داشته باشد.

اطلاعات جدول‌ها در حال حاضر از سایت  [ورزش ۳](https://www.varzesh3.com/developer-tools) گرفته می‌شود.

## دانلود
شما می‌توانید آخرین نسخه را از  [اینجا](https://github.com/LordArma/Iranian-League-Table/releases) دریافت کنید.

## شیوه استفاده
به دو شکل می‌توانید از این افزونه استفاده کنید:
- به عنوان ابزارک
- به عنوان شورتکد

### جدول لیگ ایران به عنوان ابزارک
اگر قالب وردپرس شما از ویجت‌های قدیمی پشتیبانی می‌کند، می‌توانید به راحتی از قسمت ابزارک‌های وردپرس جدول مورد نظر خود را در جای مناسب قرار دهید و سپس گزینه‌های آن را تنظیم کنید.

### جدول لیگ ایران به عنوان شورتکد
این روش در تمامی نسخه‌های وردپرس به درستی کار می‌کند. تنها کاری که باید انجام دهید این است که شورتکد این افزونه را به همراه پارامترهای مورد نظر خود در قسمت قرار دادن شورت کد مانند بلوک‌های وردپرس در صفحات و پست‌های سایت وردپرسی خود قرار دهید.

### چند مثال از شیوه استفاده
‌نمایش مدل پایه جدول لیگ برتر ایران (خلیج فارس)
```
[iran_league league="persiangulf" mode="basic"]
```

نمایش پیشرفته جدول لیگ یک ایران (آزادگان)
```
[iran_league league="azadegan" mode="advanced"]
```

‌نمایش مدل پایه جدول لیگ برتر ایران بدون لوگوی تیم‌ها
```
[iran_league league="persiangulf" mode="basic" logo="false"]
```

نمایش مدل پایه جدول لیگ برتر ایران بدون لوگوی تیم‌ها با اعداد فارسی
```
[iran_league league="persiangulf" mode="basic" logo="false" farsi_numbers="true"]
```

نمایش مدل پایه لیگ زنان ایران (کوثر) بدون لوگو
```
[iran_league league="kowsar" mode="basic" logo="false"]
```

نمایش جدول لیگ برتر ایران با رنگ و اندازه دلخواه
```
[iran_league league="bartar" mode="advanced" title_backcolor="#212121" title_color="#ffffff" text_color="#212121" odd_color="#ffffff" even_color="#eeeeee" logo_size="15" logo="true" title_font="12" text_font="13" farsi_numbers="false"]
```

## توضیحات بیشتر به زبان فارسی
در وبلاگم مطلبی با عنوان [پلاگین وردپرس جدول لیگ برتر و لیگ یک فوتبال ایران](https://lordarma.com/iranian-league-table/) منتشر کردم. در صورت تمایل می‌توانید [این مطلب](https://lordarma.com/iranian-league-table/) را هم برای آشنایی بیشتر با این افزونه مطالعه کنید.

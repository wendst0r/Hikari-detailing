CREATE TABLE IF NOT EXISTS "Клиент" (
    "Электронная_почта" VARCHAR(100) NOT NULL PRIMARY KEY CHECK ("Электронная_почта" LIKE '%@%')
    "Фамилия_клиента" VARCHAR(50) NOT NULL,
    "Имя_клиента" VARCHAR(50) NOT NULL,
    "Отчество_клиента" VARCHAR(50) NULL DEFAULT NULL,
    "Номер_телефона_клиента" VARCHAR(11) NOT NULL UNIQUE CHECK (LENGTH("Номер_телефона_клиента") = 11),
    "Хэш_пароль" TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "Мастер" (
    "Табельный_номер_мастера" SERIAL NOT NULL PRIMARY KEY,
    "Фамилия_мастера" VARCHAR(50) NOT NULL,
    "Имя_мастера" VARCHAR(50) NOT NULL,
    "Отчество_мастера" VARCHAR(50) NULL DEFAULT NULL,
    "Номер_телефона_мастера" VARCHAR(11) NOT NULL UNIQUE CHECK (LENGTH("Номер_телефона_мастера") = 11)
);

CREATE TABLE IF NOT EXISTS "Процедура" (
    "Название_процедуры" VARCHAR(100) NOT NULL PRIMARY KEY,
    "Длительность_процедуры" INTERVAL NOT NULL
);

CREATE TABLE IF NOT EXISTS "Материал" (
    "Название_материала" VARCHAR(100) NOT NULL PRIMARY KEY,
    "Тип_материала" VARCHAR(50) NULL DEFAULT NULL,
    "Единица_измерения" VARCHAR(20) NOT NULL,
    "Стоимость_единицы" NUMERIC(10, 2) NOT NULL CHECK ("Стоимость_единицы" >= 0)
);

CREATE TABLE IF NOT EXISTS "Услуга" (
    "Название_услуги" VARCHAR(100) NOT NULL PRIMARY KEY,
    "Тип_услуги" VARCHAR(50) NULL CHECK ("Тип_услуги" IN ('Внутренняя', 'Внешняя', 'Смешанная')),
    "Стоимость_услуги" NUMERIC(10, 2) NOT NULL CHECK ("Стоимость_услуги" >= 0)
);
CREATE TABLE IF NOT EXISTS "Автомобиль" (
    "VIN_номер" VARCHAR(17) NOT NULL PRIMARY KEY CHECK (LENGTH("VIN_номер") = 17),
    "Модель_автомобиля" VARCHAR(50) NOT NULL,
    "Марка_автомобиля" VARCHAR(50) NOT NULL,
    "Год_выпуска" INTEGER NOT NULL CHECK ("Год_выпуска" BETWEEN 1900 AND EXTRACT(YEAR FROM CURRENT_DATE)),
    "Регистрационный_номер" VARCHAR(9) NULL DEFAULT NULL UNIQUE CHECK (LENGTH("Регистрационный_номер") = 9)
);

CREATE TABLE IF NOT EXISTS "Автомобиль_клиента" (
    "Электронная_почта" VARCHAR(100) NOT NULL,
    "VIN_номер" VARCHAR(17) NOT NULL,
    PRIMARY KEY ("Электронная_почта", "VIN_номер"),
    FOREIGN KEY ("VIN_номер") REFERENCES "Автомобиль" ("VIN_номер") ON UPDATE NO ACTION ON DELETE NO ACTION,
    FOREIGN KEY ("Электронная_почта") REFERENCES "Клиент" ("Электронная_почта") ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS "Заказ" (
    "ИД_Заказа" SERIAL NOT NULL PRIMARY KEY,
    "Электронная_почта" VARCHAR(100) NOT NULL,
    "VIN_номер" VARCHAR(17) NOT NULL,
    "datatime_создания_заказа" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "Итоговая_стоимость" NUMERIC(10, 2) NOT NULL CHECK ("Итоговая_стоимость" >= 0),
    "Способ_оплаты" VARCHAR(50) NOT NULL CHECK ("Способ_оплаты" IN ('Наличные', 'Карта')),
    "Текущий_статус_заказа" VARCHAR(50) NOT NULL DEFAULT 'Ожидается' CHECK ("Текущий_статус_заказа" IN ('Ожидается', 'Выполняется')),
    UNIQUE ("Электронная_почта", "VIN_номер", "datatime_создания_заказа"),
    FOREIGN KEY ("VIN_номер") REFERENCES "Автомобиль" ("VIN_номер") ON UPDATE NO ACTION ON DELETE NO ACTION,
    FOREIGN KEY ("Электронная_почта") REFERENCES "Клиент" ("Электронная_почта") ON UPDATE NO ACTION ON DELETE NO ACTION
);
CREATE TABLE IF NOT EXISTS "Квалификация" (
    "Табельный_номер_мастера" INTEGER NOT NULL,
    "Название_процедуры" VARCHAR(100) NOT NULL,
    PRIMARY KEY ("Табельный_номер_мастера", "Название_процедуры"),
    FOREIGN KEY ("Табельный_номер_мастера") REFERENCES "Мастер" ("Табельный_номер_мастера") ON UPDATE NO ACTION ON DELETE NO ACTION,
    FOREIGN KEY ("Название_процедуры") REFERENCES "Процедура" ("Название_процедуры") ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS "Материалы_для_услуги" (
    "Название_услуги" VARCHAR(100) NOT NULL,
    "Название_материала" VARCHAR(100) NOT NULL,
    "Количество_материала" NUMERIC(10, 2) NOT NULL CHECK ("Количество_материала" > 0),
    PRIMARY KEY ("Название_услуги", "Название_материала"),
    FOREIGN KEY ("Название_материала") REFERENCES "Материал" ("Название_материала") ON UPDATE NO ACTION ON DELETE NO ACTION,
    FOREIGN KEY ("Название_услуги") REFERENCES "Услуга" ("Название_услуги") ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS "Состав_услуги" (
    "Название_услуги" VARCHAR(100) NOT NULL,
    "Название_процедуры" VARCHAR(100) NOT NULL,
    "Очередность_процедуры" INTEGER NOT NULL CHECK ("Очередность_процедуры" > 0),
    PRIMARY KEY ("Название_услуги", "Название_процедуры"),
    FOREIGN KEY ("Название_услуги") REFERENCES "Услуга" ("Название_услуги") ON UPDATE NO ACTION ON DELETE NO ACTION,
    FOREIGN KEY ("Название_процедуры") REFERENCES "Процедура" ("Название_процедуры") ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS "Отзыв" (
    "ИД_Заказа" INTEGER NOT NULL,
    "datatime_написания_отзыва" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "Текст_отзыва" TEXT NULL DEFAULT NULL,
    "Рейтинг" INTEGER NULL DEFAULT NULL CHECK ("Рейтинг" BETWEEN 1 AND 5),
    PRIMARY KEY ("ИД_Заказа", "datatime_написания_отзыва"),
    FOREIGN KEY ("ИД_Заказа") REFERENCES "Заказ" ("ИД_Заказа") ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS "Рекламация" (
    "ИД_Заказа" INTEGER NOT NULL,
    "datatime_написания_рекламации" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "Описание_проблемы" TEXT NOT NULL,
    "Статус_реализации" VARCHAR(50) NULL DEFAULT NULL CHECK ("Статус_реализации" IN ('Ожидает', 'Закрыта')),
    PRIMARY KEY ("ИД_Заказа", "datatime_написания_рекламации"),
    FOREIGN KEY ("ИД_Заказа") REFERENCES "Заказ" ("ИД_Заказа") ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS "Состав_заказа" (
    "Название_услуги" VARCHAR(100) NOT NULL,
    "ИД_Заказа" INTEGER NOT NULL,
    PRIMARY KEY ("Название_услуги", "ИД_Заказа"),
    FOREIGN KEY ("ИД_Заказа") REFERENCES "Заказ" ("ИД_Заказа") ON UPDATE NO ACTION ON DELETE NO ACTION,
    FOREIGN KEY ("Название_услуги") REFERENCES "Услуга" ("Название_услуги") ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS "Работа_мастера" (
    "Табельный_номер_мастера" INTEGER NOT NULL,
    "datatime_выполнения_процедуры" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "Название_процедуры" VARCHAR(100) NOT NULL,
    "ИД_Заказа" INTEGER NOT NULL,
    PRIMARY KEY ("Табельный_номер_мастера", "ИД_Заказа", "Название_процедуры", "datatime_выполнения_процедуры"),
    FOREIGN KEY ("ИД_Заказа") REFERENCES "Заказ" ("ИД_Заказа") ON UPDATE NO ACTION ON DELETE NO ACTION,
    FOREIGN KEY ("Табельный_номер_мастера") REFERENCES "Мастер" ("Табельный_номер_мастера") ON UPDATE NO ACTION ON DELETE NO ACTION,
    FOREIGN KEY ("Название_процедуры") REFERENCES "Процедура" ("Название_процедуры") ON UPDATE NO ACTION ON DELETE NO ACTION
);
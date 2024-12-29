<?php
require 'admin/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $reg = $_POST['reg'];
    $vin = $_POST['vin'];
    $reg = str_replace(' ', '', $reg);

    try {
        $sql = "INSERT INTO \"Автомобиль\" (\"VIN_номер\", \"Модель_автомобиля\", \"Марка_автомобиля\", \"Год_выпуска\", \"Регистрационный_номер\") 
                VALUES (:vin, :model, :brand, :year, :reg)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['brand' => $brand, 'model' => $model, 'year' => $year, 'reg' => $reg, 'vin' => $vin]);
    } catch (PDOException $e) {
        // Если ошибка связана с дубликатом VIN-номера, просто игнорируем её
        if ($e->getCode() != '23505') { // Код ошибки для нарушения уникальности в PostgreSQL
            $error = "Произошла ошибка при добавлении автомобиля: " . $e->getMessage();
        }
    }

    try {
        $sql = "INSERT INTO \"Автомобиль_клиента\" (\"Электронная_почта\", \"VIN_номер\") 
                VALUES (:email, :vin)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $_COOKIE['email'], 'vin' => $vin]);
    } catch (PDOException $e) {
        // Если ошибка связана с дубликатом записи (автомобиль уже привязан к клиенту)
        if ($e->getCode() == '23505') { // Код ошибки для нарушения уникальности в PostgreSQL
            $error = "Этот автомобиль уже принадлежит вам";
        } else {
            $error = "Произошла ошибка при добавлении: " . $e->getMessage();
        }
    }

    if(empty($error)){
        header('Location: success_car_add.php');
        exit(); // Завершаем выполнение скрипта после перенаправления
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить автомобиль</title>
    <link rel="stylesheet" href="new_form.css">
    <link rel="stylesheet" href="globals.css">
</head>
<body>
    <main>
        <form action="new_car.php" method="post" onsubmit="return validateForm()">
            <img src="Pictures/car-icon.png">
            <h1>Добавить автомобиль</h1>
            <label for="modelSelect">Марка:</label>
            <select name="brand" required id="brandSelect"></select>
            <label for="modelSelect">Модель:</label>
            <select name="model" required id="modelSelect"></select>
            <input type="text" required placeholder="Год выпуска" maxlength="4" name="year">
            <label for="reg" style="opacity: 50%;">Формат номера: X 000 XX 000</label>
            <input 
                type="text" 
                id="reg" 
                name="reg" 
                placeholder="Регистрационный номер" 
                maxlength="12" 
                required>
            <input 
                type="text" 
                id="vin" 
                name="vin" 
                placeholder="VIN-номер" 
                maxlength="17" 
                required>
            
            <?php if (!empty($error)): ?>
                <p style="color: red; text-align: center;"><?php echo $error; ?></p>
            <?php endif; ?>

            <button type="submit">Сохранить</button>
        </form>
    </main> 
</body>
<script>
    // Загрузка JSON-файла
    fetch('car_brands.json')
      .then(response => response.json())
      .then(data => {
        const brandSelect = document.getElementById('brandSelect');
        const modelSelect = document.getElementById('modelSelect');

        // Заполняем список марок
        data.brands.forEach(brand => {
        const option = document.createElement('option');
        option.value = brand.name; // Используем название марки вместо id
        option.textContent = brand.name;
        brandSelect.appendChild(option);
        });

        // Обновляем список моделей при выборе марки
        brandSelect.addEventListener('change', (e) => {
        const selectedBrand = data.brands.find(brand => brand.name == e.target.value);
        modelSelect.innerHTML = ''; // Очищаем список моделей
        if (selectedBrand) {
            selectedBrand.models.forEach(model => {
            const option = document.createElement('option');
            option.value = model; // Используем название модели
            option.textContent = model;
            modelSelect.appendChild(option);
            });
        }
        });
      })
      .catch(error => console.error('Ошибка загрузки JSON:', error));
      
    //формаиторвниае рег номера
    document.getElementById('reg').addEventListener('input', function(e) {
        // Разрешенные символы (буквы и цифры)
        const allowedChars = /[АВЕКМНОРСТУХавекмнорстухABEKMHOPCTYXabekmhopctyx1234567890]/g;
        // Получаем текущее значение поля ввода
        let value = e.target.value;
        // Оставляем только разрешенные символы и преобразуем в верхний регистр
        let filteredValue = value.match(allowedChars);
        if (filteredValue) {
            filteredValue = filteredValue.join('').toUpperCase();
        } else {
            filteredValue = ''; // Если нет разрешенных символов, очищаем поле
        }
        // Форматируем значение
        let formattedValue = '';
        for (let i = 0; i < filteredValue.length; i++) {
            if (i === 1 || i === 4 || i === 6) {
                formattedValue += ' '; // Добавляем пробелы после 1 и 4 символов
            }
            formattedValue += filteredValue[i];
        }
        // Обновляем значение поля ввода
        e.target.value = formattedValue;
    });

    // Проверка VIN-номера
    document.getElementById('vin').addEventListener('input', function(e) {
        // Разрешенные символы для VIN
        const allowedChars = /[A-HJ-NPR-Za-hj-npr-z0-9]/g;
        // Получаем текущее значение поля ввода
        let value = e.target.value;
        // Оставляем только разрешенные символы
        let filteredValue = value.match(allowedChars);
        // Если есть разрешенные символы, преобразуем их в верхний регистр
        if (filteredValue) {
            filteredValue = filteredValue.join('').toUpperCase();
        } else {
            filteredValue = ''; // Если нет разрешенных символов, очищаем поле
        }
        // Обновляем значение поля ввода
        e.target.value = filteredValue;
    });

    // Функция для проверки формы перед отправкой
    function validateForm() {
        const vinInput = document.getElementById('vin');
        if (vinInput.value.length !== 17) {
            alert('VIN-номер должен содержать ровно 17 символов.');
            return false; // Предотвращаем отправку формы
        }
        return true; // Продолжаем отправку формы
    }
</script>
</html>
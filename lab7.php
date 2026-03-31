<?php

function clean($data) {
    return htmlspecialchars(trim($data));
}

$errors = [];
$data = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data['name'] = clean($_POST['name'] ?? '');
    $data['type'] = clean($_POST['type'] ?? '');
    $data['gender'] = $_POST['gender'] ?? '';
    $data['features'] = $_POST['features'] ?? [];
    $data['description'] = clean($_POST['description'] ?? '');
    $data['secret'] = clean($_POST['secret'] ?? '');
    $data['password'] = $_POST['password'] ?? '';

    if (!preg_match('/^[a-zA-Zа-яА-Я]{2,}$/u', $data['name'])) {
        $errors[] = "Невірне ім'я";
    }

    if (!$data['type']) {
        $errors[] = "Оберіть тип";
    }

    if (!in_array($data['gender'], ['male', 'female'])) {
        $errors[] = "Оберіть стать";
    }

    if (!preg_match('/^.{5,}$/', $data['description'])) {
        $errors[] = "Опис занадто короткий";
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d_]{8,}$/', $data['password'])) {
        $errors[] = "Слабкий пароль";
    }

    if (!$errors) {
        echo "<h2>Дані валідні ✅</h2>";
        echo "Ім'я: {$data['name']}<br>";
        echo "Тип: {$data['type']}<br>";
        echo "Стать: {$data['gender']}<br>";
        echo "Особливості: " . implode(", ", $data['features']) . "<br>";
        echo "Опис: {$data['description']}<br>";
        echo "Приховане поле: {$data['secret']}<br>";
        exit;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Animal Form</title>
</head>
<body>

<h2>Форма тварини</h2>

<?php
if ($errors) {
    echo "<div style='color:red'>";
    foreach ($errors as $e) echo "$e<br>";
    echo "</div><hr>";
}
?>

<form method="POST">

    Ім'я:<br>
    <input type="text" name="name"><br><br>

    Тип тварини:<br>
    <select name="type">
        <option value="">--оберіть--</option>
        <option value="cat">Кіт</option>
        <option value="dog">Собака</option>
        <option value="bird">Птах</option>
    </select><br><br>

    Стать:<br>
    <input type="radio" name="gender" value="male"> Самець
    <input type="radio" name="gender" value="female"> Самка
    <br><br>

    Особливості:<br>
    <input type="checkbox" name="features[]" value="fast"> Швидкий
    <input type="checkbox" name="features[]" value="cute"> Милий
    <input type="checkbox" name="features[]" value="wild"> Дикий
    <br><br>

    Опис:<br>
    <textarea name="description"></textarea><br><br>

    Пароль:<br>
    <input type="password" name="password"><br><br>

    <input type="hidden" name="secret" value="12345">

    <button type="submit">Відправити</button>
    <button type="reset">Очистити</button>

</form>

</body>
</html>

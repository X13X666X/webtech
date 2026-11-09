
<?php
require_once "db.php";

// Функція для безпечного виведення тексту
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Видалення запису
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete"])) {
    $visit_id = (int)($_POST["visit_id"] ?? 0);

    if ($visit_id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM visits WHERE visit_id = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $visit_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header("Location: index.php");
    exit;
}

// Отримання параметрів фільтрування
$client_filter = trim($_GET["client"] ?? "");
$membership_filter = $_GET["membership"] ?? "";
$date_from = $_GET["date_from"] ?? "";
$date_to = $_GET["date_to"] ?? "";
$duration_min = $_GET["duration_min"] ?? "";
$duration_max = $_GET["duration_max"] ?? "";

// Формування умов фільтрування
$where = [];

if ($client_filter !== "") {
    $client_safe = mysqli_real_escape_string($conn, $client_filter);
    $where[] = "CONCAT(c.first_name, ' ', c.last_name) LIKE '%$client_safe%'";
}

if ($membership_filter !== "" && ctype_digit((string)$membership_filter)) {
    $membership_id = (int)$membership_filter;
    $where[] = "cm.membership_type_id = $membership_id";
}

if ($date_from !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $where[] = "DATE(v.visit_date) >= '$date_from_safe'";
}

if ($date_to !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to_safe = mysqli_real_escape_string($conn, $date_to);
    $where[] = "DATE(v.visit_date) <= '$date_to_safe'";
}

if ($duration_min !== "" && is_numeric($duration_min)) {
    $duration_min_value = (int)$duration_min;
    $where[] = "v.duration_minutes >= $duration_min_value";
}

if ($duration_max !== "" && is_numeric($duration_max)) {
    $duration_max_value = (int)$duration_max;
    $where[] = "v.duration_minutes <= $duration_max_value";
}

$where_sql = "";

if (count($where) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}

// Дозволені поля для сортування
$sort_fields = [
    "date" => "v.visit_date",
    "client" => "c.last_name",
    "membership" => "mt.name",
    "duration" => "v.duration_minutes"
];

// Отримання параметрів сортування
$sort1 = $_GET["sort1"] ?? "date";
$sort2 = $_GET["sort2"] ?? "client";
$dir1 = strtoupper($_GET["dir1"] ?? "DESC");
$dir2 = strtoupper($_GET["dir2"] ?? "ASC");

// Перевірка параметрів сортування
if (!array_key_exists($sort1, $sort_fields)) {
    $sort1 = "date";
}

if (!array_key_exists($sort2, $sort_fields)) {
    $sort2 = "client";
}

if (!in_array($dir1, ["ASC", "DESC"], true)) {
    $dir1 = "DESC";
}

if (!in_array($dir2, ["ASC", "DESC"], true)) {
    $dir2 = "ASC";
}

// Формування ORDER BY
$order_sql = "ORDER BY " .
    $sort_fields[$sort1] . " " . $dir1 . ", " .
    $sort_fields[$sort2] . " " . $dir2;

// Отримання списку відвідувань
$sql = "
    SELECT
        v.visit_id,
        v.visit_date,
        v.duration_minutes,
        c.first_name,
        c.last_name,
        mt.name AS membership_name
    FROM visits v
    JOIN clients c ON v.client_id = c.client_id
    JOIN client_memberships cm
        ON v.client_membership_id = cm.client_membership_id
    JOIN membership_types mt
        ON cm.membership_type_id = mt.membership_type_id
    $where_sql
    $order_sql
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Помилка отримання даних: " . mysqli_error($conn));
}

// Отримання типів абонементів для списку фільтрування
$memberships_result = mysqli_query(
    $conn,
    "SELECT membership_type_id, name FROM membership_types ORDER BY name"
);

if (!$memberships_result) {
    die("Помилка отримання типів абонементів: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Облік відвідувань спортзалу</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 30px;
        }

        form {
            margin: 15px auto;
        }

        input, select, button {
            padding: 6px;
            margin: 3px;
        }

        table {
            margin: 20px auto;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            border: 1px solid #999;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #eee;
        }

        .filter-form, .sort-form {
            max-width: 1000px;
            padding: 12px;
            border: 1px solid #ccc;
        }

        .actions form {
            display: inline-block;
        }
    </style>
</head>

<body>

    <h1>Облік відвідувань спортзалу</h1>

    <!-- Перехід до форми додавання -->
    <h2>Додавання відвідування</h2>

    <a href="visit_form.php">
        <button type="button">Додати відвідування</button>
    </a>

    <!-- Фільтрування -->
    <h2>Фільтрування відвідувань</h2>

    <form method="GET" class="filter-form">

        <label>Клієнт:</label>
        <input
            type="text"
            name="client"
            placeholder="Ім'я або прізвище"
            value="<?= h($client_filter) ?>"
        >

        <label>Тип абонемента:</label>
        <select name="membership">
            <option value="">Усі типи</option>

            <?php while ($membership = mysqli_fetch_assoc($memberships_result)): ?>
                <option
                    value="<?= h($membership['membership_type_id']) ?>"
                    <?= (string)$membership_filter === (string)$membership['membership_type_id'] ? 'selected' : '' ?>
                >
                    <?= h($membership['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <br><br>

        <label>Дата від:</label>
        <input
            type="date"
            name="date_from"
            value="<?= h($date_from) ?>"
        >

        <label>Дата до:</label>
        <input
            type="date"
            name="date_to"
            value="<?= h($date_to) ?>"
        >

        <br><br>

        <label>Тривалість від (хв):</label>
        <input
            type="number"
            name="duration_min"
            min="0"
            value="<?= h($duration_min) ?>"
        >

        <label>Тривалість до (хв):</label>
        <input
            type="number"
            name="duration_max"
            min="0"
            value="<?= h($duration_max) ?>"
        >

        <br><br>

        <button type="submit">Застосувати фільтри</button>
        <a href="index.php">
            <button type="button">Скинути фільтри</button>
        </a>
    </form>

    <!-- Сортування -->
    <h2>Сортування</h2>

    <form method="GET" class="sort-form">

        <!-- Збереження параметрів фільтрування -->
        <input type="hidden" name="client" value="<?= h($client_filter) ?>">
        <input type="hidden" name="membership" value="<?= h($membership_filter) ?>">
        <input type="hidden" name="date_from" value="<?= h($date_from) ?>">
        <input type="hidden" name="date_to" value="<?= h($date_to) ?>">
        <input type="hidden" name="duration_min" value="<?= h($duration_min) ?>">
        <input type="hidden" name="duration_max" value="<?= h($duration_max) ?>">

        <label>Перше поле:</label>
        <select name="sort1">
            <option value="date" <?= $sort1 === "date" ? "selected" : "" ?>>Дата</option>
            <option value="client" <?= $sort1 === "client" ? "selected" : "" ?>>Клієнт</option>
            <option value="membership" <?= $sort1 === "membership" ? "selected" : "" ?>>Абонемент</option>
            <option value="duration" <?= $sort1 === "duration" ? "selected" : "" ?>>Тривалість</option>
        </select>

        <select name="dir1">
            <option value="ASC" <?= $dir1 === "ASC" ? "selected" : "" ?>>За зростанням</option>
            <option value="DESC" <?= $dir1 === "DESC" ? "selected" : "" ?>>За спаданням</option>
        </select>

        <br><br>

        <label>Друге поле:</label>
        <select name="sort2">
            <option value="date" <?= $sort2 === "date" ? "selected" : "" ?>>Дата</option>
            <option value="client" <?= $sort2 === "client" ? "selected" : "" ?>>Клієнт</option>
            <option value="membership" <?= $sort2 === "membership" ? "selected" : "" ?>>Абонемент</option>
            <option value="duration" <?= $sort2 === "duration" ? "selected" : "" ?>>Тривалість</option>
        </select>

        <select name="dir2">
            <option value="ASC" <?= $dir2 === "ASC" ? "selected" : "" ?>>За зростанням</option>
            <option value="DESC" <?= $dir2 === "DESC" ? "selected" : "" ?>>За спаданням</option>
        </select>

        <br><br>

        <button type="submit">Сортувати</button>
    </form>

    <!-- Таблиця відвідувань -->
    <h2>Список відвідувань</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Клієнт</th>
            <th>Абонемент</th>
            <th>Дата та час</th>
            <th>Тривалість (хв)</th>
            <th>Дії</th>
        </tr>

        <?php if (mysqli_num_rows($result) > 0): ?>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <tr>
                    <td><?= h($row['visit_id']) ?></td>

                    <td>
                        <?= h($row['first_name'] . " " . $row['last_name']) ?>
                    </td>

                    <td><?= h($row['membership_name']) ?></td>

                    <td><?= h($row['visit_date']) ?></td>

                    <td><?= h($row['duration_minutes']) ?></td>

                    <td class="actions">

                        <a href="visit_form.php?id=<?= h($row['visit_id']) ?>">
                            <button type="button">Редагувати</button>
                        </a>

                        <form method="POST">
                            <input
                                type="hidden"
                                name="visit_id"
                                value="<?= h($row['visit_id']) ?>"
                            >

                            <button
                                type="submit"
                                name="delete"
                                onclick="return confirm('Видалити цей запис?')"
                            >
                                Видалити
                            </button>
                        </form>

                    </td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="6">Записів не знайдено</td>
            </tr>

        <?php endif; ?>
    </table>

</body>
</html>

<?php
mysqli_free_result($result);
mysqli_free_result($memberships_result);
mysqli_close($conn);
?>

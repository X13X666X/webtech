
<?php
require_once "db.php";

$message = "";

// ДОДАВАННЯ ВІДВІДУВАННЯ
if (isset($_POST['add'])) {
    $membership_id = (int)$_POST['membership_id'];
    $date = str_replace('T', ' ', $_POST['visit_date']);
    $duration = (int)$_POST['duration'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT client_id FROM client_memberships
         WHERE client_membership_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $membership_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $membership = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($membership && $duration > 0 && strtotime($date) !== false) {
        $client_id = (int)$membership['client_id'];

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO visits
             (client_id, client_membership_id, visit_date, duration_minutes)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "iisi",
            $client_id,
            $membership_id,
            $date,
            $duration
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $message = "Відвідування додано!";
    } else {
        $message = "Перевір введені дані.";
    }
}

// РЕДАГУВАННЯ ВІДВІДУВАННЯ
if (isset($_POST['update'])) {
    $id = (int)$_POST['visit_id'];
    $duration = (int)$_POST['duration'];

    if ($id > 0 && $duration > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE visits SET duration_minutes = ? WHERE visit_id = ?"
        );

        mysqli_stmt_bind_param($stmt, "ii", $duration, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $message = "Відвідування оновлено!";
    } else {
        $message = "Перевір введені дані.";
    }
}

// ВИДАЛЕННЯ ВІДВІДУВАННЯ
if (isset($_POST['delete'])) {
    $id = (int)$_POST['visit_id'];

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM visits WHERE visit_id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $message = mysqli_stmt_affected_rows($stmt) > 0
            ? "Відвідування видалено!"
            : "Запис не знайдено.";

        mysqli_stmt_close($stmt);
    }
}

$client_filter = trim($_GET['client'] ?? '');
$membership_filter = (int)($_GET['membership'] ?? 0);

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$duration_min = $_GET['duration_min'] ?? '';
$duration_max = $_GET['duration_max'] ?? '';

if ($date_from !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = '';
}

if ($date_to !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = '';
}

if ($duration_min !== '' && !ctype_digit((string)$duration_min)) {
    $duration_min = '';
}

if ($duration_max !== '' && !ctype_digit((string)$duration_max)) {
    $duration_max = '';
}

$where = [];

if ($client_filter !== '') {
    $client = mysqli_real_escape_string($conn, $client_filter);
    $where[] = "(c.first_name LIKE '%$client%'
                 OR c.last_name LIKE '%$client%')";
}

if ($membership_filter > 0) {
    $where[] = "mt.membership_type_id = $membership_filter";
}

if ($date_from !== '') {
    $where[] = "v.visit_date >= '$date_from 00:00:00'";
}

if ($date_to !== '') {
    $where[] = "v.visit_date < DATE_ADD('$date_to', INTERVAL 1 DAY)";
}

if ($duration_min !== '') {
    $where[] = "v.duration_minutes >= " . (int)$duration_min;
}

if ($duration_max !== '') {
    $where[] = "v.duration_minutes <= " . (int)$duration_max;
}

$where_sql = '';

if (count($where) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}

// СОРТУВАННЯ
$sort_fields = [
    'date' => 'v.visit_date',
    'client' => 'c.last_name',
    'membership' => 'mt.name',
    'duration' => 'v.duration_minutes'
];

$sort1 = $_GET['sort1'] ?? 'date';
$sort2 = $_GET['sort2'] ?? 'duration';

$dir1 = strtoupper($_GET['dir1'] ?? 'DESC');
$dir2 = strtoupper($_GET['dir2'] ?? 'ASC');

if (!array_key_exists($sort1, $sort_fields)) {
    $sort1 = 'date';
}

if (!array_key_exists($sort2, $sort_fields)) {
    $sort2 = 'duration';
}

if (!in_array($dir1, ['ASC', 'DESC'], true)) {
    $dir1 = 'DESC';
}

if (!in_array($dir2, ['ASC', 'DESC'], true)) {
    $dir2 = 'ASC';
}

$order_sql = "ORDER BY "
    . $sort_fields[$sort1] . " " . $dir1 . ", "
    . $sort_fields[$sort2] . " " . $dir2;

$sql = "
    SELECT
        v.visit_id,
        c.first_name,
        c.last_name,
        mt.name AS membership_name,
        mt.membership_type_id,
        v.visit_date,
        v.duration_minutes
    FROM visits v
    JOIN clients c ON v.client_id = c.client_id
    JOIN client_memberships cm
        ON v.client_membership_id = cm.client_membership_id
    JOIN membership_types mt
        ON cm.membership_type_id = mt.membership_type_id
    $where_sql
    $order_sql
";

$visits = mysqli_query($conn, $sql);

$memberships = mysqli_query(
    $conn,
    "SELECT cm.client_membership_id,
            c.first_name, c.last_name,
            mt.name AS membership_name
     FROM client_memberships cm
     JOIN clients c ON cm.client_id = c.client_id
     JOIN membership_types mt
        ON cm.membership_type_id = mt.membership_type_id
     ORDER BY c.last_name"
);

$membership_types = mysqli_query(
    $conn,
    "SELECT membership_type_id, name
     FROM membership_types
     ORDER BY membership_type_id"
);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Спортзал</title>

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
            padding: 10px;
            text-align: center;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin: 20px auto;
            max-width: 1100px;
        }

        .filters label {
            display: block;
            margin-bottom: 5px;
        }

        .filter-item {
            margin: 5px;
        }

        .sort-section {
            margin: 20px auto;
        }

        .sort-row {
            margin: 10px;
        }
    </style>
</head>

<body>

<h1>Журнал відвідувань спортзалу</h1>

<?php if ($message != ""): ?>
    <p><b><?= htmlspecialchars($message) ?></b></p>
<?php endif; ?>

<h2>Додавання відвідування</h2>

<form method="POST">

    <label>Клієнт та абонемент:</label><br>

    <select name="membership_id" required>
        <?php while ($m = mysqli_fetch_assoc($memberships)): ?>
            <option value="<?= $m['client_membership_id'] ?>">
                <?= htmlspecialchars(
                    $m['first_name'] . " " .
                    $m['last_name'] . " — " .
                    $m['membership_name']
                ) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <br><br>

    <label>Дата та час:</label><br>
    <input type="datetime-local" name="visit_date" required>

    <br><br>

    <label>Тривалість (хвилини):</label><br>
    <input type="number" name="duration" min="1" required>

    <br><br>

    <button type="submit" name="add">Додати</button>

</form>

<hr>

<h2>Фільтрування та сортування</h2>

<form method="GET">

    <div class="filters">

        <div class="filter-item">
            <label>Клієнт:</label>
            <input type="text" name="client"
                   placeholder="Ім'я або прізвище"
                   value="<?= htmlspecialchars($client_filter) ?>">
        </div>

        <div class="filter-item">
            <label>Тип абонемента:</label>
            <select name="membership">
                <option value="0">Усі абонементи</option>

                <?php while ($mt = mysqli_fetch_assoc($membership_types)): ?>
                    <option value="<?= $mt['membership_type_id'] ?>"
                        <?= $membership_filter == $mt['membership_type_id']
                            ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mt['name']) ?>
                    </option>
                <?php endwhile; ?>

            </select>
        </div>

        <div class="filter-item">
            <label>Дата від:</label>
            <input type="date" name="date_from"
                   value="<?= htmlspecialchars($date_from) ?>">
        </div>

        <div class="filter-item">
            <label>Дата до:</label>
            <input type="date" name="date_to"
                   value="<?= htmlspecialchars($date_to) ?>">
        </div>

        <div class="filter-item">
            <label>Тривалість від (хв):</label>
            <input type="number" name="duration_min"
                   min="0"
                   value="<?= htmlspecialchars((string)$duration_min) ?>">
        </div>

        <div class="filter-item">
            <label>Тривалість до (хв):</label>
            <input type="number" name="duration_max"
                   min="0"
                   value="<?= htmlspecialchars((string)$duration_max) ?>">
        </div>

    </div>

    <div class="sort-section">

        <h3>Перше сортування</h3>

        <div class="sort-row">

            <label>Поле:</label>

            <select name="sort1">
                <option value="date" <?= $sort1 == 'date' ? 'selected' : '' ?>>
                    Дата відвідування
                </option>

                <option value="client" <?= $sort1 == 'client' ? 'selected' : '' ?>>
                    Клієнт
                </option>

                <option value="membership" <?= $sort1 == 'membership' ? 'selected' : '' ?>>
                    Абонемент
                </option>

                <option value="duration" <?= $sort1 == 'duration' ? 'selected' : '' ?>>
                    Тривалість
                </option>
            </select>

            <select name="dir1">
                <option value="ASC" <?= $dir1 == 'ASC' ? 'selected' : '' ?>>
                    За зростанням
                </option>

                <option value="DESC" <?= $dir1 == 'DESC' ? 'selected' : '' ?>>
                    За спаданням
                </option>
            </select>

        </div>

        <h3>Друге сортування</h3>

        <div class="sort-row">

            <label>Поле:</label>

            <select name="sort2">
                <option value="date" <?= $sort2 == 'date' ? 'selected' : '' ?>>
                    Дата відвідування
                </option>

                <option value="client" <?= $sort2 == 'client' ? 'selected' : '' ?>>
                    Клієнт
                </option>

                <option value="membership" <?= $sort2 == 'membership' ? 'selected' : '' ?>>
                    Абонемент
                </option>

                <option value="duration" <?= $sort2 == 'duration' ? 'selected' : '' ?>>
                    Тривалість
                </option>
            </select>

            <select name="dir2">
                <option value="ASC" <?= $dir2 == 'ASC' ? 'selected' : '' ?>>
                    За зростанням
                </option>

                <option value="DESC" <?= $dir2 == 'DESC' ? 'selected' : '' ?>>
                    За спаданням
                </option>
            </select>

        </div>

    </div>

    <button type="submit">Застосувати</button>

    <a href="index.php">Скинути фільтри</a>

</form>

<hr>

<h2>Список відвідувань</h2>

<table border="1" cellpadding="8" cellspacing="0">

    <tr>
        <th>ID</th>
        <th>Клієнт</th>
        <th>Абонемент</th>
        <th>Дата</th>
        <th>Тривалість (хв)</th>
        <th>Дії</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($visits)): ?>

        <tr>
            <td><?= $row['visit_id'] ?></td>

            <td>
                <?= htmlspecialchars(
                    $row['first_name'] . " " . $row['last_name']
                ) ?>
            </td>

            <td><?= htmlspecialchars($row['membership_name']) ?></td>

            <td><?= htmlspecialchars($row['visit_date']) ?></td>

            <td><?= $row['duration_minutes'] ?></td>

            <td>
                <form method="POST">

                    <input type="hidden" name="visit_id"
                           value="<?= $row['visit_id'] ?>">

                    <input type="number" name="duration"
                           value="<?= $row['duration_minutes'] ?>"
                           min="1" required>

                    <button type="submit" name="update">
                        Змінити
                    </button>

                    <button type="submit" name="delete"
                            onclick="return confirm('Видалити цей запис?')">
                        Видалити
                    </button>

                </form>
            </td>
        </tr>

    <?php endwhile; ?>

</table>

<?php
mysqli_free_result($visits);
mysqli_free_result($memberships);
mysqli_free_result($membership_types);
mysqli_close($conn);
?>

</body>
</html>

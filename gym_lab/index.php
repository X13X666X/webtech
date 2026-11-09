
<?php
require_once "db.php";

$message = "";

if (isset($_POST['add'])) {
    $membership_id = (int)$_POST['membership_id'];
    $date = str_replace('T', ' ', $_POST['visit_date']);
    $duration = (int)$_POST['duration'];

    // Отримуємо клієнта за вибраним абонементом
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

$sql = "
    SELECT
        v.visit_id,
        c.first_name,
        c.last_name,
        mt.name AS membership_name,
        v.visit_date,
        v.duration_minutes
    FROM visits v
    JOIN clients c ON v.client_id = c.client_id
    JOIN client_memberships cm
        ON v.client_membership_id = cm.client_membership_id
    JOIN membership_types mt
        ON cm.membership_type_id = mt.membership_type_id
    ORDER BY v.visit_date DESC
";

$visits = mysqli_query($conn, $sql);

$memberships = mysqli_query(
    $conn,
    "SELECT cm.client_membership_id,
            c.first_name, c.last_name, mt.name AS membership_name
     FROM client_memberships cm
     JOIN clients c ON cm.client_id = c.client_id
     JOIN membership_types mt
        ON cm.membership_type_id = mt.membership_type_id
     ORDER BY c.last_name"
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
            min-width: 750px;
        }

        th, td {
            padding: 10px;
            text-align: center;
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

</body>
</html>

<?php
mysqli_free_result($visits);
mysqli_free_result($memberships);
mysqli_close($conn);
?>

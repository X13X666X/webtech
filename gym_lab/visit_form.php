
<?php
require_once "db.php";

$errors = [];
$message = "";

$visit_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visit_id = (int)($_POST['visit_id'] ?? 0);
} else {
    $visit_id = (int)($_GET['id'] ?? 0);
}

$is_edit = $visit_id > 0;

$client_id = "";
$membership_id = "";
$visit_date = "";
$duration = "";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $is_edit) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM visits WHERE visit_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $visit_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $visit = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$visit) {
        die("Відвідування не знайдено.");
    }

    $client_id = $visit['client_id'];
    $membership_id = $visit['client_membership_id'];
    $visit_date = date('Y-m-d\TH:i', strtotime($visit['visit_date']));
    $duration = $visit['duration_minutes'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = trim($_POST['client_id'] ?? '');
    $membership_id = trim($_POST['membership_id'] ?? '');
    $visit_date = trim($_POST['visit_date'] ?? '');
    $duration = trim($_POST['duration'] ?? '');

    if (!ctype_digit((string)$client_id) || (int)$client_id <= 0) {
        $errors['client_id'] = "Виберіть клієнта.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT client_id FROM clients WHERE client_id = ?"
        );

        $id = (int)$client_id;
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 0) {
            $errors['client_id'] = "Такого клієнта не існує.";
        }

        mysqli_stmt_close($stmt);
    }

    if (!ctype_digit((string)$membership_id) ||
        (int)$membership_id <= 0) {
        $errors['membership_id'] = "Виберіть абонемент.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT client_id
             FROM client_memberships
             WHERE client_membership_id = ?"
        );

        $id = (int)$membership_id;
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $membership = mysqli_fetch_assoc($result);

        if (!$membership) {
            $errors['membership_id'] = "Такого абонемента не існує.";
        } elseif ((int)$membership['client_id'] !== (int)$client_id) {
            $errors['membership_id'] =
                "Вибраний абонемент не належить цьому клієнту.";
        }

        mysqli_stmt_close($stmt);
    }

    // Перевірка дати та часу
    $date_object = DateTime::createFromFormat(
        'Y-m-d\TH:i',
        $visit_date
    );

    if (
        !$date_object ||
        $date_object->format('Y-m-d\TH:i') !== $visit_date
    ) {
        $errors['visit_date'] = "Введіть коректну дату та час.";
    }

    // Перевірка тривалості
    if (
        !ctype_digit((string)$duration) ||
        (int)$duration < 1 ||
        (int)$duration > 1440
    ) {
        $errors['duration'] =
            "Тривалість повинна бути від 1 до 1440 хвилин.";
    }

    if (empty($errors)) {
        $client_id = (int)$client_id;
        $membership_id = (int)$membership_id;
        $duration = (int)$duration;

        $date_for_db = str_replace('T', ' ', $visit_date) . ':00';

        if ($is_edit) {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE visits
                 SET client_id = ?,
                     client_membership_id = ?,
                     visit_date = ?,
                     duration_minutes = ?
                 WHERE visit_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iisii",
                $client_id,
                $membership_id,
                $date_for_db,
                $duration,
                $visit_id
            );
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO visits
                 (client_id, client_membership_id,
                  visit_date, duration_minutes)
                 VALUES (?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iisi",
                $client_id,
                $membership_id,
                $date_for_db,
                $duration
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            mysqli_close($conn);

            header("Location: index.php");
            exit;
        } else {
            $errors['general'] = "Помилка збереження: " .
                mysqli_stmt_error($stmt);

            mysqli_stmt_close($stmt);
        }
    }
}

// Отримання списку клієнтів
$clients = mysqli_query(
    $conn,
    "SELECT client_id, first_name, last_name
     FROM clients
     ORDER BY last_name"
);

// Отримання абонементів разом з інформацією про клієнта
$memberships = mysqli_query(
    $conn,
    "SELECT cm.client_membership_id,
            cm.client_id,
            mt.name AS membership_name,
            c.first_name,
            c.last_name
     FROM client_memberships cm
     JOIN membership_types mt
        ON cm.membership_type_id = mt.membership_type_id
     JOIN clients c
        ON cm.client_id = c.client_id
     ORDER BY c.last_name"
);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?= $is_edit ? 'Редагування' : 'Додавання' ?> відвідування</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 30px;
        }

        form {
            width: 400px;
            margin: 20px auto;
        }

        input, select, button {
            padding: 8px;
            margin: 5px;
            width: 90%;
        }

        .error {
            color: red;
            font-size: 14px;
        }

        .invalid {
            border: 2px solid red;
            background-color: #fff0f0;
        }

        .field {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

<h1>
    <?= $is_edit ? 'Редагування відвідування' : 'Додавання відвідування' ?>
</h1>

<?php if (isset($errors['general'])): ?>
    <p class="error"><?= h($errors['general']) ?></p>
<?php endif; ?>

<form method="POST">

    <input type="hidden" name="visit_id"
           value="<?= $visit_id ?>">

    <!-- Вибір клієнта -->
    <div class="field">
        <label>Клієнт:</label><br>

        <select name="client_id"
            class="<?= isset($errors['client_id']) ? 'invalid' : '' ?>"
            required>

            <option value="">Виберіть клієнта</option>

            <?php while ($c = mysqli_fetch_assoc($clients)): ?>
                <option value="<?= $c['client_id'] ?>"
                    <?= (string)$client_id === (string)$c['client_id']
                        ? 'selected' : '' ?>>

                    <?= h($c['first_name'] . ' ' . $c['last_name']) ?>

                </option>
            <?php endwhile; ?>

        </select>

        <?php if (isset($errors['client_id'])): ?>
            <div class="error"><?= h($errors['client_id']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Вибір абонемента -->
    <div class="field">
        <label>Абонемент:</label><br>

        <select name="membership_id"
            class="<?= isset($errors['membership_id']) ? 'invalid' : '' ?>"
            required>

            <option value="">Виберіть абонемент</option>

            <?php while ($m = mysqli_fetch_assoc($memberships)): ?>
                <option value="<?= $m['client_membership_id'] ?>"
                    <?= (string)$membership_id ===
                        (string)$m['client_membership_id']
                        ? 'selected' : '' ?>>

                    <?= h(
                        $m['first_name'] . ' ' .
                        $m['last_name'] . ' — ' .
                        $m['membership_name']
                    ) ?>

                </option>
            <?php endwhile; ?>

        </select>

        <?php if (isset($errors['membership_id'])): ?>
            <div class="error"><?= h($errors['membership_id']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Дата та час -->
    <div class="field">
        <label>Дата та час відвідування:</label><br>

        <input type="datetime-local"
               name="visit_date"
               value="<?= h($visit_date) ?>"
               class="<?= isset($errors['visit_date']) ? 'invalid' : '' ?>"
               required>

        <?php if (isset($errors['visit_date'])): ?>
            <div class="error"><?= h($errors['visit_date']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Тривалість -->
    <div class="field">
        <label>Тривалість (хвилини):</label><br>

        <input type="number"
               name="duration"
               min="1"
               max="1440"
               value="<?= h($duration) ?>"
               class="<?= isset($errors['duration']) ? 'invalid' : '' ?>"
               required>

        <?php if (isset($errors['duration'])): ?>
            <div class="error"><?= h($errors['duration']) ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" name="save">
        <?= $is_edit ? 'Зберегти зміни' : 'Додати відвідування' ?>
    </button>

</form>

<a href="index.php">Повернутися до журналу відвідувань</a>

</body>
</html>

<?php
mysqli_free_result($clients);
mysqli_free_result($memberships);
mysqli_close($conn);
?>

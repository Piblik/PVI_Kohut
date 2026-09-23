<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$processor = new \App\SubmissionProcessor();

$errors = [];
$result = null;

// Створюємо історію в сесії
if (!isset($_SESSION['submissions'])) {
    $_SESSION['submissions'] = [];
}

// Обробка POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Нормалізація введених даних
    $customer = trim((string)($_POST['customer'] ?? ''));
    $hours = (int)($_POST['hours'] ?? 0);
    $equipment = trim((string)($_POST['equipment'] ?? ''));
    $date = trim((string)($_POST['date'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    $submission = new \App\Submission(
            $customer,
            $hours,
            $equipment,
            $date,
            $description
    );

    $errors = $processor->validate($submission);

    if (empty($errors)) {

        $result = $processor->calculateCost($submission);

        // Зберігаємо заявку в сесію
        $_SESSION['submissions'][] = [
                'customer' => $submission->customer,
                'hours' => $submission->hours,
                'equipment' => $submission->equipment,
                'date' => $submission->date,
                'description' => $submission->description,
                'cost' => $result
        ];

        // Залишаємо тільки останні 3 заявки
        $_SESSION['submissions'] = array_slice(
                $_SESSION['submissions'],
                -3
        );

        // POST → 303 → GET
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
        exit;
    }
}

// Перевіряємо успішне перенаправлення
$success = isset($_GET['success']) && $_GET['success'] === '1';

// GET-фільтр за типом техніки
$filter = trim((string)($_GET['equipment'] ?? ''));

$history = $_SESSION['submissions'];

if ($filter !== '') {
    $history = array_filter(
            $history,
            static function (array $submission) use ($filter): bool {
                return $submission['equipment'] === $filter;
            }
    );
}

$equipmentNames = [
        'tractor' => 'Трактор',
        'harvester' => 'Комбайн',
        'loader' => 'Навантажувач'
];

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">

    <title>Оренда сільськогосподарської техніки</title>
</head>

<body>

<h1>Оренда сільськогосподарської техніки</h1>

<?php if (!empty($errors)): ?>

    <div>
        <h3>Помилки:</h3>

        <ul>
            <?php foreach ($errors as $error): ?>

                <li>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </li>

            <?php endforeach; ?>
        </ul>
    </div>

<?php endif; ?>


<?php if ($success): ?>

    <h2>Заявку успішно прийнято!</h2>

<?php endif; ?>


<form method="POST">

    <p>
        <label>
            Замовник:

            <input
                    type="text"
                    name="customer"
                    value="<?= htmlspecialchars(
                            $_POST['customer'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                    ) ?>"
                    required
            >
        </label>
    </p>


    <p>
        <label>
            Кількість годин:

            <input
                    type="number"
                    name="hours"
                    min="1"
                    max="100"
                    value="<?= htmlspecialchars(
                            $_POST['hours'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                    ) ?>"
                    required
            >
        </label>
    </p>


    <p>
        <label>
            Тип техніки:

            <select name="equipment" required>

                <option value="">
                    Оберіть техніку
                </option>

                <option
                        value="tractor"
                        <?= (($_POST['equipment'] ?? '') === 'tractor')
                                ? 'selected'
                                : '' ?>
                >
                    Трактор — 900 грн/год
                </option>

                <option
                        value="harvester"
                        <?= (($_POST['equipment'] ?? '') === 'harvester')
                                ? 'selected'
                                : '' ?>
                >
                    Комбайн — 1800 грн/год
                </option>

                <option
                        value="loader"
                        <?= (($_POST['equipment'] ?? '') === 'loader')
                                ? 'selected'
                                : '' ?>
                >
                    Навантажувач — 1200 грн/год
                </option>

            </select>
        </label>
    </p>


    <p>
        <label>
            Дата:

            <input
                    type="date"
                    name="date"
                    value="<?= htmlspecialchars(
                            $_POST['date'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                    ) ?>"
                    required
            >
        </label>
    </p>


    <p>
        <label>
            Опис робіт:<br>

            <textarea
                    name="description"
                    rows="5"
                    cols="50"
                    required
            ><?= htmlspecialchars(
                        $_POST['description'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                ) ?></textarea>

        </label>
    </p>


    <button type="submit">
        Розрахувати вартість
    </button>

</form>


<hr>


<h2>Історія заявок</h2>


<form method="GET">

    <label>
        Фільтр за типом техніки:

        <select name="equipment">

            <option value="">
                Усі
            </option>

            <option
                    value="tractor"
                    <?= $filter === 'tractor' ? 'selected' : '' ?>
            >
                Трактор
            </option>

            <option
                    value="harvester"
                    <?= $filter === 'harvester' ? 'selected' : '' ?>
            >
                Комбайн
            </option>

            <option
                    value="loader"
                    <?= $filter === 'loader' ? 'selected' : '' ?>
            >
                Навантажувач
            </option>

        </select>
    </label>

    <button type="submit">
        Фільтрувати
    </button>

</form>


<?php if (empty($history)): ?>

    <p>Заявок поки немає.</p>

<?php else: ?>

    <?php foreach ($history as $submission): ?>

        <div>

            <h3>
                <?= htmlspecialchars(
                        $submission['customer'],
                        ENT_QUOTES,
                        'UTF-8'
                ) ?>
            </h3>

            <p>
                Тип техніки:
                <?= htmlspecialchars(
                        $equipmentNames[$submission['equipment']]
                        ?? $submission['equipment'],
                        ENT_QUOTES,
                        'UTF-8'
                ) ?>
            </p>

            <p>
                Кількість годин:
                <?= (int)$submission['hours'] ?>
            </p>

            <p>
                Дата:
                <?= htmlspecialchars(
                        $submission['date'],
                        ENT_QUOTES,
                        'UTF-8'
                ) ?>
            </p>

            <p>
                Опис:
                <?= htmlspecialchars(
                        $submission['description'],
                        ENT_QUOTES,
                        'UTF-8'
                ) ?>
            </p>

            <p>
                Вартість:
                <strong>
                    <?= number_format(
                            (float)$submission['cost'],
                            2,
                            ',',
                            ' '
                    ) ?>
                    грн
                </strong>
            </p>

        </div>

        <hr>

    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>
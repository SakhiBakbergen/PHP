<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use University\Library\Exception\LoanException;
use University\Library\Model\Book;
use University\Library\Model\Member;
use University\Library\Repository\InMemoryBookRepository;
use University\Library\Service\LoanService;

session_start();

/* ---------- Данные (хранятся в сессии) ---------- */

function seed(): array
{
    $repo = new InMemoryBookRepository();
    $books = [
        ['9785170000011', 'Программирование на PHP', 'Иванов И.', 'Программирование', 2023, 'Современный PHP 8: типы, классы, Composer и автозагрузка PSR-4.'],
        ['9785170000012', 'Чистый код', 'Роберт Мартин', 'Программирование', 2019, 'Практики написания читаемого и сопровождаемого кода.'],
        ['9785170000013', 'Паттерны проектирования', 'Э. Гамма и др.', 'Программирование', 2015, 'Классический каталог приёмов объектно-ориентированного проектирования.'],
        ['9785170000021', 'Базы данных', 'Петров П.', 'Базы данных', 2021, 'Реляционная модель, SQL, нормализация и транзакции.'],
        ['9785170000022', 'Основы SQL', 'Сидорова А.', 'Базы данных', 2020, 'Запросы, соединения, индексы и оптимизация.'],
        ['9785170000031', 'Web-разработка', 'Сидоров С.', 'Веб', 2022, 'HTML, CSS, HTTP и построение веб-приложений на PHP.'],
        ['9785170000032', 'JavaScript для начинающих', 'Нурпеисов Д.', 'Веб', 2021, 'Язык JavaScript, DOM и работа с браузером.'],
        ['9785170000041', 'Алгоритмы и структуры данных', 'Ахметов А.', 'Алгоритмы', 2018, 'Сортировки, деревья, графы и оценка сложности.'],
        ['9785170000051', 'Слова назидания', 'Абай Кунанбаев', 'Классика', 1904, 'Философские размышления великого казахского поэта.'],
        ['9785170000052', 'Путь Абая', 'Мухтар Ауэзов', 'Классика', 1942, 'Роман-эпопея о жизни и времени Абая.'],
        ['9785170000061', 'Краткая история времени', 'Стивен Хокинг', 'Наука', 1988, 'Популярный рассказ о Вселенной, чёрных дырах и природе времени.'],
        ['9785170000062', 'Sapiens. Краткая история человечества', 'Юваль Харари', 'Наука', 2011, 'История человечества от древности до наших дней.'],
    ];
    foreach ($books as [$isbn, $title, $author, $genre, $year, $desc]) {
        $repo->add(new Book($isbn, $title, $author, $genre, $year, $desc));
    }

    $service = new LoanService($repo);
    // Демонстрационные выдачи: одна просроченная, одна обычная
    $service->lend('9785170000041', new Member(2, 'Алия Сатпаева', 2), new DateTimeImmutable('-20 days'));
    $service->lend('9785170000061', new Member(2, 'Алия Сатпаева', 2), new DateTimeImmutable('-3 days'));
    $service->lend('9785170000051', new Member(3, 'Данияр Омаров', 3), new DateTimeImmutable('-5 days'));

    return ['v' => 3, 'repository' => $repo, 'service' => $service];
}

if (isset($_POST['reset']) || (($_SESSION['app']['v'] ?? null) !== 3)) {
    $_SESSION['app'] = seed();
    $_SESSION['reader'] = 1;
    if (isset($_POST['reset'])) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$repository = $_SESSION['app']['repository'];
$service = $_SESSION['app']['service'];

$members = [
    1 => new Member(1, 'Айдар Нурланов', 3),
    2 => new Member(2, 'Алия Сатпаева', 2),
    3 => new Member(3, 'Данияр Омаров', 3),
];

$reader = $members[(int) ($_SESSION['reader'] ?? 1)] ?? $members[1];

/* ---------- Вспомогательные функции ---------- */

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function link_to(array $params): string
{
    $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
    return $_SERVER['PHP_SELF'] . ($params ? '?' . http_build_query($params) : '');
}

/** @return array{0:string,1:string} */
function cover_colors(string $genre): array
{
    return match ($genre) {
        'Программирование' => ['#1e3a5f', '#3b6ea8'],
        'Базы данных' => ['#5b2a6e', '#9a55b3'],
        'Веб' => ['#0f5e63', '#2aa3a8'],
        'Алгоритмы' => ['#7a3b10', '#c9792e'],
        'Классика' => ['#6e1f2b', '#b04555'],
        'Наука' => ['#1f4d3a', '#4a9a76'],
        default => ['#444444', '#888888'],
    };
}

function cover(Book $book): string
{
    [$c1, $c2] = cover_colors($book->genre);
    return sprintf(
        '<div class="cover" style="background:linear-gradient(135deg,%s,%s)"><span class="c-genre">%s</span><span class="c-title">%s</span><span class="c-author">%s</span></div>',
        $c1, $c2, e($book->genre), e($book->title), e($book->author),
    );
}

function fmt(DateTimeImmutable $d): string
{
    return $d->format('d.m.Y');
}

/* ---------- Обработка действий ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    parse_str((string) ($_POST['qs'] ?? ''), $back);
    try {
        $isbn = (string) ($_POST['isbn'] ?? '');
        switch ($_POST['action']) {
            case 'login':
                $id = (int) ($_POST['member'] ?? 1);
                if (isset($members[$id])) {
                    $_SESSION['reader'] = $id;
                    $_SESSION['message'] = ['ok', 'Вы вошли как ' . $members[$id]->name . '.'];
                }
                break;
            case 'lend':
                $loan = $service->lend($isbn, $reader);
                $_SESSION['message'] = ['ok', 'Книга выдана. Верните её до ' . fmt($loan->dueAt) . '.'];
                break;
            case 'return':
                $service->giveBack($isbn, $reader);
                $_SESSION['message'] = ['ok', 'Книга принята. Спасибо!'];
                break;
        }
    } catch (LoanException $ex) {
        $_SESSION['message'] = ['error', $ex->getMessage()];
    }
    header('Location: ' . link_to($back));
    exit;
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

/* ---------- Маршрутизация ---------- */

$page = $_GET['page'] ?? 'catalog';
$isbnParam = (string) ($_GET['book'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$genre = (string) ($_GET['genre'] ?? '');
$currentQs = http_build_query(array_filter($_GET, static fn ($v) => $v !== ''));

$book = $isbnParam !== '' ? $repository->findByIsbn($isbnParam) : null;
$found = $repository->search($q, $genre !== '' ? $genre : null);

/** Кнопка действия для книги (выдать / вернуть / занято) */
function action_button(Book $book, LoanService $service, Member $reader, string $qs): string
{
    $holder = $service->holderOf($book->isbn);
    $form = '<form method="post" class="inline"><input type="hidden" name="isbn" value="%s">'
        . '<input type="hidden" name="qs" value="%s"><button class="%s" name="action" value="%s">%s</button></form>';

    if ($book->isAvailable()) {
        return sprintf($form, e($book->isbn), e($qs), 'btn', 'lend', 'Взять домой');
    }
    if ($holder === $reader->id) {
        return sprintf($form, e($book->isbn), e($qs), 'btn btn-outline', 'return', 'Вернуть книгу');
    }
    return '<span class="muted">Недоступна</span>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Городская библиотека</title>
<style>
    :root { --green:#1f4d3a; --green2:#2f6b52; --paper:#faf7f2; --line:#e6dfd2; --ink:#2b2b2b; --muted:#7a7468; }
    * { box-sizing: border-box; }
    body { margin:0; background:var(--paper); color:var(--ink); font-family: system-ui, "Segoe UI", Arial, sans-serif; }
    a { color: inherit; text-decoration: none; }
    header.top { background: var(--green); color:#fff; }
    .bar { max-width:1100px; margin:0 auto; padding:14px 20px; display:flex; gap:20px; align-items:center; flex-wrap:wrap; }
    .logo { font-family: Georgia, serif; font-size:24px; font-weight:bold; }
    .logo small { display:block; font-size:12px; font-weight:normal; opacity:.75; font-family: system-ui, sans-serif; }
    nav { display:flex; gap:6px; }
    nav a { padding:8px 14px; border-radius:8px; opacity:.9; }
    nav a.active, nav a:hover { background: rgba(255,255,255,.15); opacity:1; }
    .who { margin-left:auto; display:flex; gap:8px; align-items:center; font-size:14px; }
    .who select { padding:6px 8px; border-radius:8px; border:0; }
    .who button { padding:6px 12px; border-radius:8px; border:0; background:#fff; color:var(--green); cursor:pointer; }
    .hero { background: linear-gradient(180deg, var(--green2), var(--green)); color:#fff; padding:28px 20px 34px; }
    .hero-in { max-width:1100px; margin:0 auto; }
    .hero h1 { font-family: Georgia, serif; margin:0 0 6px; font-size:30px; }
    .hero p { margin:0 0 16px; opacity:.85; }
    .search { display:flex; gap:8px; max-width:640px; }
    .search input { flex:1; padding:12px 14px; border-radius:10px; border:0; font-size:15px; }
    .search button { padding:12px 20px; border-radius:10px; border:0; background:#e8c872; color:#3a2f10; font-weight:600; cursor:pointer; }
    main { max-width:1100px; margin:0 auto; padding:24px 20px 50px; }
    .msg { padding:12px 16px; border-radius:10px; margin-bottom:18px; }
    .msg.ok { background:#e1f3e8; color:#1d5b35; }
    .msg.error { background:#fbe3e3; color:#8f2222; }
    .chips { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
    .chip { padding:6px 14px; border-radius:999px; border:1px solid var(--line); background:#fff; font-size:14px; }
    .chip.on { background:var(--green); color:#fff; border-color:var(--green); }
    .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:20px; }
    .card { background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; }
    .cover { height:200px; padding:16px; color:#fff; display:flex; flex-direction:column; justify-content:space-between; border-left:8px solid rgba(0,0,0,.25); }
    .c-genre { font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:.8; }
    .c-title { font-family:Georgia, serif; font-size:19px; font-weight:bold; line-height:1.2; }
    .c-author { font-size:13px; opacity:.9; }
    .card-body { padding:12px 14px 14px; display:flex; flex-direction:column; gap:8px; flex:1; }
    .card-body .meta { font-size:13px; color:var(--muted); }
    .badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; }
    .free { background:#dff3e6; color:#1d5b35; }
    .busy { background:#fbe9d0; color:#8a5a12; }
    .late { background:#fbe3e3; color:#8f2222; }
    .btn { padding:8px 14px; border-radius:8px; border:1px solid var(--green); background:var(--green); color:#fff; cursor:pointer; font-size:14px; }
    .btn-outline { background:#fff; color:var(--green); }
    .btn-link { color:var(--green); font-size:14px; text-decoration:underline; }
    form.inline { margin:0; display:inline; }
    .muted { color:var(--muted); font-size:14px; }
    .actions { margin-top:auto; display:flex; justify-content:space-between; align-items:center; gap:8px; }
    .panel { background:#fff; border:1px solid var(--line); border-radius:12px; padding:20px; margin-bottom:20px; }
    .detail { display:grid; grid-template-columns:260px 1fr; gap:28px; }
    .detail .cover { height:340px; border-radius:10px; }
    .detail h2 { font-family:Georgia, serif; margin:0 0 4px; }
    dl { display:grid; grid-template-columns:140px 1fr; gap:6px 12px; margin:16px 0; }
    dt { color:var(--muted); }
    dd { margin:0; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; padding:10px 8px; border-bottom:1px solid var(--line); font-size:14px; }
    th { color:var(--muted); font-weight:600; }
    .ticket { display:flex; gap:20px; align-items:center; flex-wrap:wrap; }
    .ticket .num { font-family:Georgia, serif; font-size:22px; }
    .meter { height:8px; background:#eee; border-radius:99px; width:200px; overflow:hidden; }
    .meter > i { display:block; height:100%; background:var(--green2); }
    footer { text-align:center; color:var(--muted); font-size:13px; padding-bottom:30px; }
    footer button { background:none; border:0; color:var(--muted); text-decoration:underline; cursor:pointer; }
    @media (max-width:700px) { .detail { grid-template-columns:1fr; } }
</style>
</head>
<body>

<header class="top">
    <div class="bar">
        <a class="logo" href="<?= e(link_to([])) ?>">📚 Городская библиотека<small>Абонемент и читальный зал</small></a>
        <nav>
            <a href="<?= e(link_to([])) ?>" class="<?= $page === 'catalog' ? 'active' : '' ?>">Каталог</a>
            <a href="<?= e(link_to(['page' => 'account'])) ?>" class="<?= $page === 'account' ? 'active' : '' ?>">Мой кабинет</a>
        </nav>
        <form method="post" class="who">
            <input type="hidden" name="qs" value="<?= e($currentQs) ?>">
            <span>Читатель:</span>
            <select name="member">
                <?php foreach ($members as $m): ?>
                    <option value="<?= $m->id ?>" <?= $m->id === $reader->id ? 'selected' : '' ?>><?= e($m->name) ?></option>
                <?php endforeach; ?>
            </select>
            <button name="action" value="login">Войти</button>
        </form>
    </div>
</header>

<?php if ($page === 'catalog' && $book === null): ?>
<section class="hero">
    <div class="hero-in">
        <h1>Найдите свою следующую книгу</h1>
        <p>В фонде <?= count($repository->all()) ?> изданий. Поиск по названию, автору или ISBN.</p>
        <form class="search" method="get">
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Например: PHP, Абай, алгоритмы…">
            <?php if ($genre !== ''): ?><input type="hidden" name="genre" value="<?= e($genre) ?>"><?php endif; ?>
            <button>Найти</button>
        </form>
    </div>
</section>
<?php endif; ?>

<main>
<?php if ($message !== null): ?>
    <div class="msg <?= e($message[0]) ?>"><?= e($message[1]) ?></div>
<?php endif; ?>

<?php if ($book !== null): /* ----- Карточка книги ----- */ ?>
    <?php $loan = $service->loanFor($book->isbn); ?>
    <p><a class="btn-link" href="<?= e(link_to([])) ?>">← Назад в каталог</a></p>
    <div class="panel detail">
        <?= cover($book) ?>
        <div>
            <h2><?= e($book->title) ?></h2>
            <div class="muted"><?= e($book->author) ?></div>
            <p><?= e($book->description) ?></p>
            <dl>
                <dt>Жанр</dt><dd><?= e($book->genre) ?></dd>
                <dt>Год издания</dt><dd><?= $book->year ?></dd>
                <dt>ISBN</dt><dd><?= e($book->isbn) ?></dd>
                <dt>Статус</dt>
                <dd>
                    <?php if ($book->isAvailable()): ?>
                        <span class="badge free">В наличии</span>
                    <?php elseif ($loan !== null && $loan->isOverdue()): ?>
                        <span class="badge late">Выдана, срок истёк <?= fmt($loan->dueAt) ?></span>
                    <?php elseif ($loan !== null): ?>
                        <span class="badge busy">Выдана до <?= fmt($loan->dueAt) ?></span>
                    <?php endif; ?>
                </dd>
                <dt>Срок выдачи</dt><dd><?= LoanService::LOAN_DAYS ?> дней</dd>
            </dl>
            <?= action_button($book, $service, $reader, $currentQs) ?>
        </div>
    </div>

<?php elseif ($page === 'account'): /* ----- Кабинет читателя ----- */ ?>
    <?php $myLoans = $service->loansOf($reader); ?>
    <div class="panel ticket">
        <div>
            <div class="muted">Читательский билет</div>
            <div class="num">№ <?= str_pad((string) $reader->id, 6, '0', STR_PAD_LEFT) ?></div>
        </div>
        <div>
            <strong><?= e($reader->name) ?></strong><br>
            <span class="muted">Выдано <?= count($myLoans) ?> из <?= $reader->maxLoans ?></span>
            <div class="meter"><i style="width:<?= (int) round(count($myLoans) / $reader->maxLoans * 100) ?>%"></i></div>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-top:0">Книги на руках</h3>
        <?php if ($myLoans === []): ?>
            <p class="muted">Сейчас у вас нет книг. Загляните в <a class="btn-link" href="<?= e(link_to([])) ?>">каталог</a>.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Книга</th><th>Выдана</th><th>Вернуть до</th><th>Состояние</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($myLoans as $loan): ?>
                    <?php $b = $repository->findByIsbn($loan->isbn); ?>
                    <?php if ($b === null) { continue; } ?>
                    <tr>
                        <td><a class="btn-link" href="<?= e(link_to(['book' => $b->isbn])) ?>"><?= e($b->title) ?></a><br><span class="muted"><?= e($b->author) ?></span></td>
                        <td><?= fmt($loan->issuedAt) ?></td>
                        <td><?= fmt($loan->dueAt) ?></td>
                        <td>
                            <?php if ($loan->isOverdue()): ?>
                                <span class="badge late">Просрочена</span>
                            <?php else: ?>
                                <span class="badge free">В срок</span>
                            <?php endif; ?>
                        </td>
                        <td><?= action_button($b, $service, $reader, $currentQs) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php else: /* ----- Каталог ----- */ ?>
    <div class="chips">
        <a class="chip <?= $genre === '' ? 'on' : '' ?>" href="<?= e(link_to(['q' => $q])) ?>">Все жанры</a>
        <?php foreach ($repository->genres() as $g): ?>
            <a class="chip <?= $genre === $g ? 'on' : '' ?>" href="<?= e(link_to(['q' => $q, 'genre' => $g])) ?>"><?= e($g) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($found === []): ?>
        <div class="panel muted">По вашему запросу ничего не найдено.</div>
    <?php endif; ?>

    <div class="grid">
        <?php foreach ($found as $b): ?>
            <?php $loan = $service->loanFor($b->isbn); ?>
            <div class="card">
                <a href="<?= e(link_to(['book' => $b->isbn])) ?>"><?= cover($b) ?></a>
                <div class="card-body">
                    <div class="meta"><?= $b->year ?> · ISBN <?= e(substr($b->isbn, -6)) ?></div>
                    <div>
                        <?php if ($b->isAvailable()): ?>
                            <span class="badge free">В наличии</span>
                        <?php elseif ($loan !== null && $loan->isOverdue()): ?>
                            <span class="badge late">Срок истёк</span>
                        <?php else: ?>
                            <span class="badge busy">Выдана<?= $loan !== null ? ' до ' . fmt($loan->dueAt) : '' ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="actions">
                        <a class="btn-link" href="<?= e(link_to(['book' => $b->isbn])) ?>">Подробнее</a>
                        <?= action_button($b, $service, $reader, $currentQs) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</main>

<footer>
    Лабораторная работа 11 · Вариант 1 · Composer + PSR-4 ·
    <form method="post" class="inline"><button name="reset" value="1">сбросить демо-данные</button></form>
</footer>
</body>
</html>
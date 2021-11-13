<?php
require_once 'vendor/autoload.php';
use DigitalStars\SimpleVK\{Bot, SimpleVK as vk};
use DigitalStars\DataBase\DB;

$vk = vk::create('ff133c2466c34f0599d3646e74bd2e00b70852cc43cddbcbd6b15383897d32aa0a61ef78989af086c984e', '5.131')->setConfirm('8f02bc38');

$db_type = 'mysql';
$db_name = 'delivery'; // Имя БД
$login = 'delivery'; // Логин
$pass = ''; // Пароль
$ip = 'localhost'; // Адрес

$db = new DB("$db_type:host=$ip;dbname=$db_name;", $login, $pass, [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

//$vk->setUserLogError(); //ID - это id vk, кому бот будет отправлять все ошибки, возникшие в скрипте
$bot = Bot::create($vk);
$bot->setDefaultColor('blue');

$bot->btn('Посмотреть декларацию', 'Посмотреть декларацию')->attachment('photo-208807125_457239018');

function addProducts() {
    global $bot, $vk, $db;
    $vk->initText($text)->initPayload($payload)->initUserID($user_id);
    $text = explode('2', $payload['name'])[0];

    $db->query("UPDATE users SET status = 's1', status_data = ?s WHERE vk_id = ?i", [$text, $user_id]);
    $bot->msg('Напишите название производителя:')->kbd()->send();
}

function sendProducts() {
    global $bot, $vk, $db;
    $vk->initText($text)->initPayload($payload);
    $text = $payload['name'];

    $data = $db->rows("SELECT * FROM products WHERE category = ?s", [$text]);
    if(!$data) {
        $vk->reply('В данной категории еще ничего нет');
    }

    foreach ($data as $key => $product) {
        $key2 = $key+1;
        $bot->btn('Заказать', $vk->buttonText('Заказать', 'green', ['idt' => $product['id']]));
        $bot->msg("$key2) $product[seller_name]\n$product[city]\n$product[product_name](кг)\nДоступно: $product[count]кг\nЦена: $product[price] р/кг\n")
            ->kbd([['Заказать'], ['Посмотреть декларацию']], true)->send();
    }
}

$kbd1 = [['Мясо', 'Птица', 'Рыба'],['Молоко/Сыр'],['Овощи/Фрукты/Ягоды'], ['Назад']];
$kbd2 = [['Мясо2', 'Птица2', 'Рыба2'],['Молоко/Сыр2'],['Овощи/Фрукты/Ягоды2'], ['Назад']];

//$bot->msg($vk->json_online())->send();

$bot->btn('Покупатель', 'Покупатель')->text('Какую категорию продуктов вы хотите купить?')->kbd($kbd1);
$bot->btn('Продавец', 'Продавец')->text('Какую категорию продуктов вы хотите продать?')->kbd($kbd2);
$bot->btn('Доставщик', '📦Доставщик')->text('Выберите пункт меню:')->kbd([['Выбор маршрута'], ['Назад']]);
$bot->btn('Выбор маршрута', 'Выбор маршрута')->text('В разработке.');
$bot->btn('Назад', ['< Назад', 'white'])->redirect('other');

$bot->btn('Мои заказы', ['Мои заказы', 'green'])->func(function () use($bot, $vk, $db) {
    $vk->initUserID($user_id);
    $rows = $db->rows("SELECT * FROM orders WHERE vk_id = ?i", [$user_id]);
    $str = '';
    foreach ($rows as $row) {
        $str .= "Заказ №$row[id]\n$row[name] {$row['count']}кг\nАдрес доставки: $row[city]\nБлижайший срок доставки: пятница 26.11\n\n";
    }
    $vk->reply($str);
});

//для покупателей
$bot->btn('Мясо', '🥩Мясо')->a_sendProducts();
$bot->btn('Птица', '🐔Птица')->a_sendProducts();
$bot->btn('Рыба', '🐟Рыба')->a_sendProducts();
$bot->btn('Молоко/Сыр', '🥛Молоко / 🧀Сыр')->a_sendProducts();
$bot->btn('Овощи/Фрукты/Ягоды', '🥔Овощи / 🍏Фрукты / 🍓Ягоды')->a_sendProducts();
$bot->btn('Заказать')->func(function () use($bot, $vk, $db) {
    $vk->initPayload($payload)->initUserID($user_id);
    $id = $payload['idt'];
    $data = $db->row("SELECT * FROM users WHERE vk_id = ?i", [$user_id]);
    if(!$data) {
        $db->query("INSERT INTO users SET vk_id = ?i, status = 'buy', status_data = ?i", [$user_id, $id]);
    } else {
        $db->query("UPDATE users SET status = 'buy', status_data = ?i WHERE vk_id = ?i", [$id, $user_id]);
    }
    $row = $db->row("SELECT * FROM products WHERE id = ?i", [$id]);
    $vk->reply("Сколько кг необходимо? (от 1 до $row[count]).\nНапишите число в сообщении");
});
$bot->btn('Оплатить картой')->func(function () use($bot, $vk, $db) {
    $vk->initPayload($payload)->initUserID($user_id);
    $city = $payload['city'];
    $count = $payload['count'];
    $id = $payload['id'];
    $row = $db->row("SELECT * FROM products WHERE id = ?i", [$id]);
    $db->query("INSERT INTO orders SET vk_id = ?i, count = ?i, amount = ?i, name = ?s, city = ?s", [$user_id, $count, $row['price']*$count, $row['product_name'], $city]);
    $new_id = $db->lastInsertId();
    $vk->reply("*Оплата в разработке*\nВы оформили заказ №$new_id!\nСвои актуальные заказы вы можете посмотреть в главном меню.");
    $db->query("UPDATE products SET count = count - ?i WHERE id = ?i", [$count, $id]);
});

//для продавцов
$bot->btn('Мясо2', '🥩Мясо')->a_addProducts();
$bot->btn('Птица2', '🐔Птица')->a_addProducts();
$bot->btn('Рыба2', '🐟Рыба')->a_addProducts();
$bot->btn('Молоко/Сыр2', '🥛Молоко/🧀Сыр')->a_addProducts();
$bot->btn('Овощи/Фрукты/Ягоды2', '🥔Овощи/🍏Фрукты/🍓Ягоды')->a_addProducts();

$bot->cmd('начать', '!начать')->text('Выберите, кем вы являетесь')->kbd([['Покупатель'], ['Продавец'], ['Доставщик'], ['Мои заказы']]);
$bot->redirect('other', 'first'); //если пришла неизвестная кнопка/текст, то выполняем first
$bot->cmd('first')->func(function () use($bot, $vk, $db) {
    $vk->initPayload($payload)->initUserID($user_id)->initText($text);
    $data = $db->row("SELECT * FROM users WHERE vk_id = ?i", [$user_id]);
    if(!$data) {
        $db->query("INSERT INTO users SET vk_id = ?i, status = '', status_data = ''", [$user_id]);
        $bot->msg('Выберите, кем вы являетесь')->kbd([['Покупатель'], ['Продавец'], ['Доставщик'], ['Мои заказы']])->send();
        exit();
    }

    if($data['status'] == 'buy') {
        if(is_numeric($text)) {
            $row = $db->row("SELECT * FROM products WHERE id = ?i", [$data['status_data']]);
            if($text >= 1 && $text <= $row['count']) {
                $bot->msg('Напишите адрес доставки')->send();
                $db->query("UPDATE users SET status = 'city', status_data = ?s WHERE vk_id = ?i", [json_encode(['count' => $text, 'id' => $data['status_data']]), $user_id]);
            } else {
                $vk->reply("Напишите число от 1 до $row[count]");
            }
        } else {
            $vk->reply('Напишите только число');
        }
    } else if($data['status'] == 'city') {
        $status_data = json_decode($data['status_data'], true);
        $row = $db->row("SELECT * FROM products WHERE id = ?i", [$status_data['id']]);
        $price = $status_data['count']*$row['price'];
        $bot->btn('Оплатить картой', $vk->buttonText('Оплатить картой', 'blue', ['count' => $status_data['count'], 'id' => $status_data['id'], 'city' => $text]));
        $bot->msg("Ваш заказ:\n$row[product_name] {$status_data['count']}кг\nАдрес доставки: $text\nБлижайший срок доставки: пятница 26.11\nК оплате: {$price}р.")->kbd('Оплатить картой', true)->send();
        $db->query("UPDATE users SET status = '', status_data = '' WHERE  vk_id = ?i", [$user_id]);
    } else if($data['status'] == 's1') {
        $vk->reply('Напишите название населенного пункта');
        $db->query("INSERT INTO products SET category = ?s, seller_name = ?s", [$data['status_data'], $text]);
        $id = $db->lastInsertId();
        $db->query("UPDATE users SET status = 's2', status_data = ?i WHERE vk_id = ?i", [$id, $user_id]);
    } else if($data['status'] == 's2') {
        $vk->reply('Напишите наименование товара');
        $db->query("UPDATE products SET city = ?s WHERE id = ?i", [$text, $data['status_data']]);
        $db->query("UPDATE users SET status = 's3' WHERE vk_id = ?i", [$user_id]);
    } else if($data['status'] == 's3') {
        $vk->reply('Напишите количество КГ товара');
        $db->query("UPDATE products SET product_name = ?s WHERE id = ?i", [$text, $data['status_data']]);
        $db->query("UPDATE users SET status = 's4' WHERE vk_id = ?i", [$user_id]);
    } else if($data['status'] == 's4') {
        $vk->reply('Напишите цену в рублях за 1 кг');
        $db->query("UPDATE products SET count = ?s WHERE id = ?i", [$text, $data['status_data']]);
        $db->query("UPDATE users SET status = 's5' WHERE vk_id = ?i", [$user_id]);
    } else if($data['status'] == 's5') {
        $vk->reply('Отправьте файл декларации или сертификат');
        $db->query("UPDATE products SET price = ?s WHERE id = ?i", [$text, $data['status_data']]);
        $db->query("UPDATE users SET status = 's6 'WHERE vk_id = ?i", [$user_id]);
    } else if($data['status'] == 's6') {
        $bot->msg('Ваш товар был успешно добавлен!')->kbd([['Покупатель'], ['Продавец'], ['Доставщик'], ['Мои заказы']])->send();
        $db->query("UPDATE products SET city = ?s WHERE id = ?i", [$text, $data['status_data']]);
        $db->query("UPDATE users SET status = 's6 'WHERE vk_id = ?i", [$user_id]);
    } else {
        $bot->msg('Выберите, кем вы являетесь')->kbd([['Покупатель'], ['Продавец'], ['Доставщик'], ['Мои заказы']])->send();
    }
});

$bot->run();
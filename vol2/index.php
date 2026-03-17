<?php
// 初期化
$page_flag = 0;
$errors = [];
$your_name = '';
$email = '';

// HTMLエスケープ用
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// POST値を取得
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $your_name = isset($_POST['your_name']) ? trim($_POST['your_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    // 確認ボタン
    if (!empty($_POST['btn_confirm'])) {

        if ($your_name === '') {
            $errors['your_name'] = '氏名を入力してください。';
        }

        if ($email === '') {
            $errors['email'] = 'メールアドレスを入力してください。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '正しいメールアドレス形式で入力してください。';
        }

        if (empty($errors)) {
            $page_flag = 1;
        }

    // 送信ボタン
    } elseif (!empty($_POST['btn_submit'])) {

        if ($your_name === '') {
            $errors['your_name'] = '氏名を入力してください。';
        }

        if ($email === '') {
            $errors['email'] = 'メールアドレスを入力してください。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '正しいメールアドレス形式で入力してください。';
        }

        if (empty($errors)) {
            $page_flag = 2;

            // ここでメール送信やDB保存などを行う
            // 例:
            // mb_send_mail($email, 'お問い合わせありがとうございます', '送信を受け付けました。');
        }

    // 戻るボタン
    } elseif (!empty($_POST['btn_back'])) {
        $page_flag = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>お問い合わせフォーム</title>
<style>
body {
    padding: 20px;
    text-align: center;
    font-family: sans-serif;
}

.container {
    max-width: 700px;
    margin: 0 auto;
}

h1 {
    margin-bottom: 20px;
    padding: 20px 0;
    color: #209eff;
    font-size: 122%;
    border-top: 1px solid #999;
    border-bottom: 1px solid #999;
}

input[type=text] {
    width: 100%;
    max-width: 350px;
    padding: 8px 10px;
    font-size: 14px;
    border: 1px solid #bcdff8;
    border-radius: 4px;
    background: #ddf0ff;
    box-sizing: border-box;
}

input[name=btn_confirm],
input[name=btn_submit],
input[name=btn_back] {
    margin-top: 10px;
    padding: 8px 20px;
    font-size: 14px;
    color: #fff;
    cursor: pointer;
    border: none;
    border-radius: 4px;
    box-shadow: 0 3px 0 #2887d1;
    background: #4eaaf1;
}

input[name=btn_back] {
    margin-right: 20px;
    box-shadow: 0 3px 0 #777;
    background: #999;
}

.element_wrap {
    margin-bottom: 14px;
    padding: 10px 0;
    border-bottom: 1px solid #ccc;
    text-align: left;
}

label {
    display: inline-block;
    margin-bottom: 10px;
    font-weight: bold;
    width: 150px;
    vertical-align: top;
}

.element_wrap p {
    display: inline-block;
    margin: 0;
    text-align: left;
}

.error {
    color: #d00;
    font-size: 13px;
    margin-top: 6px;
}
</style>
</head>
<body>
<div class="container">
    <h1>お問い合わせフォーム</h1>

<?php if ($page_flag === 1): ?>

    <form method="post" action="">
        <div class="element_wrap">
            <label>氏名</label>
            <p><?php echo h($your_name); ?></p>
        </div>
        <div class="element_wrap">
            <label>メールアドレス</label>
            <p><?php echo h($email); ?></p>
        </div>

        <input type="hidden" name="your_name" value="<?php echo h($your_name); ?>">
        <input type="hidden" name="email" value="<?php echo h($email); ?>">

        <input type="submit" name="btn_back" value="戻る">
        <input type="submit" name="btn_submit" value="送信">
    </form>

<?php elseif ($page_flag === 2): ?>

    <p>送信が完了しました。</p>

<?php else: ?>

    <form method="post" action="">
        <div class="element_wrap">
            <label>氏名</label>
            <input type="text" name="your_name" value="<?php echo h($your_name); ?>">
            <?php if (!empty($errors['your_name'])): ?>
                <div class="error"><?php echo h($errors['your_name']); ?></div>
            <?php endif; ?>
        </div>

        <div class="element_wrap">
            <label>メールアドレス</label>
            <input type="text" name="email" value="<?php echo h($email); ?>">
            <?php if (!empty($errors['email'])): ?>
                <div class="error"><?php echo h($errors['email']); ?></div>
            <?php endif; ?>
        </div>

        <input type="submit" name="btn_confirm" value="入力内容を確認する">
    </form>

<?php endif; ?>

</div>
</body>
</html>

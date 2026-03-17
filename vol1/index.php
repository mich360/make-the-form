<?php
session_start();

/* 文字化け対策 */
mb_language("Japanese");
mb_internal_encoding("UTF-8");

/* エスケープ関数 */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/* 初期化 */
$errors = [];
$mode = 'input';

$your_name = '';
$email = '';

/* 送信先 */
$to = 'yourmail@example.com';   // ← あなたの受信用メールアドレスに変更
$subject = 'お問い合わせが届きました';

/* 入力値取得 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $your_name = trim($_POST['your_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    /* 確認ボタン */
    if (isset($_POST['btn_confirm'])) {

        if ($your_name === '') {
            $errors[] = '氏名を入力してください。';
        }

        if ($email === '') {
            $errors[] = 'メールアドレスを入力してください。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'メールアドレスの形式が正しくありません。';
        }

        if (empty($errors)) {
            $_SESSION['form'] = [
                'your_name' => $your_name,
                'email' => $email
            ];
            $mode = 'confirm';
        } else {
            $mode = 'input';
        }
    }

    /* 戻るボタン */
    if (isset($_POST['btn_back'])) {
        $mode = 'input';
    }

    /* 送信ボタン */
    if (isset($_POST['btn_submit'])) {
        if (!isset($_SESSION['form'])) {
            $errors[] = 'セッションが切れました。もう一度入力してください。';
            $mode = 'input';
        } else {
            $your_name = $_SESSION['form']['your_name'] ?? '';
            $email = $_SESSION['form']['email'] ?? '';

            $body  = "お問い合わせ内容\n";
            $body .= "-------------------------\n";
            $body .= "氏名: " . $your_name . "\n";
            $body .= "メールアドレス: " . $email . "\n";

            $headers = "From: " . $email . "\r\n";
            $headers .= "Reply-To: " . $email . "\r\n";

            $result = mb_send_mail($to, $subject, $body, $headers);

            if ($result) {
                unset($_SESSION['form']);
                $mode = 'complete';
            } else {
                $errors[] = 'メール送信に失敗しました。サーバー設定を確認してください。';
                $mode = 'confirm';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>お問い合わせフォーム</title>
<style>
body {
    padding: 20px;
    text-align: center;
    font-family: sans-serif;
    background: #f8fbff;
}

.wrapper {
    max-width: 700px;
    margin: 0 auto;
    background: #fff;
    padding: 24px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
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
    max-width: 400px;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid #bcdcff;
    border-radius: 5px;
    background: #ddf0ff;
    box-sizing: border-box;
}

input[name=btn_confirm],
input[name=btn_submit],
input[name=btn_back] {
    margin-top: 10px;
    padding: 10px 22px;
    font-size: 100%;
    color: #fff;
    cursor: pointer;
    border: none;
    border-radius: 5px;
    box-shadow: 0 3px 0 #2887d1;
    background: #4eaaf1;
}

input[name=btn_back] {
    margin-right: 20px;
    box-shadow: 0 3px 0 #777;
    background: #999;
}

.element_wrap {
    margin-bottom: 16px;
    padding: 12px 0;
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
    word-break: break-all;
}

.error_list {
    margin: 0 0 20px;
    padding: 12px 18px;
    text-align: left;
    color: #c00;
    background: #fff3f3;
    border: 1px solid #f1b5b5;
    border-radius: 6px;
}

.complete_message {
    padding: 20px;
    background: #eef9ee;
    border: 1px solid #b7dfb7;
    border-radius: 6px;
    color: #2d6b2d;
}
</style>
</head>
<body>
<div class="wrapper">
    <h1>お問い合わせフォーム</h1>

    <?php if (!empty($errors)): ?>
        <div class="error_list">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($mode === 'input'): ?>
        <form method="post" action="">
            <div class="element_wrap">
                <label for="your_name">氏名</label>
                <input type="text" id="your_name" name="your_name" value="<?php echo h($your_name); ?>">
            </div>

            <div class="element_wrap">
                <label for="email">メールアドレス</label>
                <input type="text" id="email" name="email" value="<?php echo h($email); ?>">
            </div>

            <input type="submit" name="btn_confirm" value="入力内容を確認する">
        </form>

    <?php elseif ($mode === 'confirm'): ?>
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
            <input type="submit" name="btn_submit" value="送信する">
        </form>

    <?php elseif ($mode === 'complete'): ?>
        <div class="complete_message">
            <p>お問い合わせありがとうございました。</p>
            <p>送信が完了しました。</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

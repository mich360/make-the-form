<?php
// 初期化
$page_flag = 0;
$errors = [];
$send_result = false;

$your_name = '';
$email = '';

// 日本語メール設定
mb_language("Japanese");
mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

// HTMLエスケープ
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ヘッダインジェクション対策
function clean_mail_value($str) {
    return str_replace(["\r", "\n"], '', trim($str));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $your_name = isset($_POST['your_name']) ? trim($_POST['your_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    // 戻る
    if (!empty($_POST['btn_back'])) {
        $page_flag = 0;
    }

    // 確認
    elseif (!empty($_POST['btn_confirm'])) {

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
    }

    // 送信
    elseif (!empty($_POST['btn_submit'])) {

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

            $safe_name = clean_mail_value($your_name);
            $safe_email = clean_mail_value($email);

            // メールヘッダ
            $header = '';
            $header .= "MIME-Version: 1.0\r\n";
            $header .= "From: GRAYCODE <noreply@gray-code.com>\r\n";
            $header .= "Reply-To: GRAYCODE <noreply@gray-code.com>\r\n";

            // 自動返信
            $auto_reply_subject = 'お問い合わせありがとうございます。';
            $auto_reply_text = "この度は、お問い合わせ頂き誠にありがとうございます。\n";
            $auto_reply_text .= "下記の内容でお問い合わせを受け付けました。\n\n";
            $auto_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
            $auto_reply_text .= "氏名：" . $safe_name . "\n";
            $auto_reply_text .= "メールアドレス：" . $safe_email . "\n\n";
            $auto_reply_text .= "GRAYCODE 事務局";

            // 管理者通知
            $admin_reply_subject = "お問い合わせを受け付けました";
            $admin_reply_text = "下記の内容でお問い合わせがありました。\n\n";
            $admin_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
            $admin_reply_text .= "氏名：" . $safe_name . "\n";
            $admin_reply_text .= "メールアドレス：" . $safe_email . "\n\n";

            $user_mail_sent = mb_send_mail($safe_email, $auto_reply_subject, $auto_reply_text, $header);
            $admin_mail_sent = mb_send_mail('webmaster@gray-code.com', $admin_reply_subject, $admin_reply_text, $header);

            $send_result = ($user_mail_sent && $admin_mail_sent);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>お問い合わせフォーム</title>
<style type="text/css">
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
    max-width: 360px;
    padding: 8px 10px;
    font-size: 14px;
    border: 1px solid #bcdff8;
    border-radius: 3px;
    background: #ddf0ff;
    box-sizing: border-box;
}

input[name=btn_confirm],
input[name=btn_submit],
input[name=btn_back] {
    margin-top: 10px;
    padding: 8px 20px;
    font-size: 100%;
    color: #fff;
    cursor: pointer;
    border: none;
    border-radius: 3px;
    box-shadow: 0 3px 0 #2887d1;
    background: #4eaaf1;
}

input[name=btn_back] {
    margin-right: 20px;
    box-shadow: 0 3px 0 #777;
    background: #999;
}

.element_wrap {
    margin-bottom: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #ccc;
    text-align: left;
}

label {
    display: inline-block;
    margin-bottom: 10px;
    font-weight: bold;
    width: 150px;
}

.element_wrap p {
    display: inline-block;
    margin: 0;
    text-align: left;
}

.error {
    color: #d00;
    margin-top: 6px;
    font-size: 13px;
}

.message {
    margin-top: 20px;
    font-weight: bold;
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

    <?php if ($send_result): ?>
        <p class="message">送信が完了しました。</p>
    <?php else: ?>
        <p class="message">送信に失敗しました。時間をおいて再度お試しください。</p>
    <?php endif; ?>

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

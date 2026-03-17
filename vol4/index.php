<?php
$page_flag = 0;
$errors = [];

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function clean_mail_value($str) {
    return str_replace(["\r", "\n"], '', trim($str ?? ''));
}

function age_label($age) {
    switch ($age) {
        case "1": return "〜19歳";
        case "2": return "20歳〜29歳";
        case "3": return "30歳〜39歳";
        case "4": return "40歳〜49歳";
        case "5": return "50歳〜59歳";
        case "6": return "60歳〜";
        default: return "";
    }
}

function gender_label($gender) {
    if ($gender === "male") return "男性";
    if ($gender === "female") return "女性";
    return "";
}

mb_language("Japanese");
mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

$your_name = $_POST['your_name'] ?? '';
$email = $_POST['email'] ?? '';
$gender = $_POST['gender'] ?? '';
$age = $_POST['age'] ?? '';
$contact = $_POST['contact'] ?? '';
$agreement = $_POST['agreement'] ?? '';

if (!empty($_POST['btn_confirm'])) {

    if (trim($your_name) === '') {
        $errors['your_name'] = '氏名を入力してください。';
    }

    if (trim($email) === '') {
        $errors['email'] = 'メールアドレスを入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = '正しいメールアドレス形式で入力してください。';
    }

    if ($gender === '') {
        $errors['gender'] = '性別を選択してください。';
    }

    if ($age === '') {
        $errors['age'] = '年齢を選択してください。';
    }

    if (trim($contact) === '') {
        $errors['contact'] = 'お問い合わせ内容を入力してください。';
    }

    if ($agreement !== '1') {
        $errors['agreement'] = 'プライバシーポリシーへの同意が必要です。';
    }

    if (empty($errors)) {
        $page_flag = 1;
    }

} elseif (!empty($_POST['btn_submit'])) {

    if (trim($your_name) === '') {
        $errors['your_name'] = '氏名を入力してください。';
    }

    if (trim($email) === '') {
        $errors['email'] = 'メールアドレスを入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = '正しいメールアドレス形式で入力してください。';
    }

    if ($gender === '') {
        $errors['gender'] = '性別を選択してください。';
    }

    if ($age === '') {
        $errors['age'] = '年齢を選択してください。';
    }

    if (trim($contact) === '') {
        $errors['contact'] = 'お問い合わせ内容を入力してください。';
    }

    if ($agreement !== '1') {
        $errors['agreement'] = 'プライバシーポリシーへの同意が必要です。';
    }

    if (empty($errors)) {
        $page_flag = 2;

        $safe_name = clean_mail_value($your_name);
        $safe_email = clean_mail_value($email);
        $safe_gender = gender_label($gender);
        $safe_age = age_label($age);
        $safe_contact = trim($contact);

        $header = "MIME-Version: 1.0\r\n";
        $header .= "From: GRAYCODE <noreply@gray-code.com>\r\n";
        $header .= "Reply-To: GRAYCODE <noreply@gray-code.com>\r\n";

        $auto_reply_subject = 'お問い合わせありがとうございます。';
        $auto_reply_text = "この度は、お問い合わせ頂き誠にありがとうございます。\n";
        $auto_reply_text .= "下記の内容でお問い合わせを受け付けました。\n\n";
        $auto_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
        $auto_reply_text .= "氏名：" . $safe_name . "\n";
        $auto_reply_text .= "メールアドレス：" . $safe_email . "\n";
        $auto_reply_text .= "性別：" . $safe_gender . "\n";
        $auto_reply_text .= "年齢：" . $safe_age . "\n";
        $auto_reply_text .= "お問い合わせ内容：\n" . $safe_contact . "\n\n";
        $auto_reply_text .= "GRAYCODE 事務局";

        mb_send_mail($safe_email, $auto_reply_subject, $auto_reply_text, $header);

        $admin_reply_subject = "お問い合わせを受け付けました";
        $admin_reply_text = "下記の内容でお問い合わせがありました。\n\n";
        $admin_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
        $admin_reply_text .= "氏名：" . $safe_name . "\n";
        $admin_reply_text .= "メールアドレス：" . $safe_email . "\n";
        $admin_reply_text .= "性別：" . $safe_gender . "\n";
        $admin_reply_text .= "年齢：" . $safe_age . "\n";
        $admin_reply_text .= "お問い合わせ内容：\n" . $safe_contact . "\n\n";

        mb_send_mail('webmaster@gray-code.com', $admin_reply_subject, $admin_reply_text, $header);
    }

} elseif (!empty($_POST['btn_back'])) {
    $page_flag = 0;
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
}
h1 {
    margin-bottom: 20px;
    padding: 20px 0;
    color: #209eff;
    font-size: 122%;
    border-top: 1px solid #999;
    border-bottom: 1px solid #999;
}
input[type=text], select {
    padding: 5px 10px;
    font-size: 86%;
    border: none;
    border-radius: 3px;
    background: #ddf0ff;
}
input[name=btn_confirm],
input[name=btn_submit],
input[name=btn_back] {
    margin-top: 10px;
    padding: 5px 20px;
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
    vertical-align: top;
}
.element_wrap p {
    display: inline-block;
    margin: 0;
    text-align: left;
}
label[for=gender_male],
label[for=gender_female],
label[for=agreement] {
    margin-right: 10px;
    width: auto;
    font-weight: normal;
}
textarea[name=contact] {
    padding: 5px 10px;
    width: 60%;
    height: 100px;
    font-size: 86%;
    border: none;
    border-radius: 3px;
    background: #ddf0ff;
}
.error {
    color: #d00;
    font-size: 13px;
    margin-top: 6px;
}
</style>
</head>
<body>
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
    <div class="element_wrap">
        <label>性別</label>
        <p><?php echo h(gender_label($gender)); ?></p>
    </div>
    <div class="element_wrap">
        <label>年齢</label>
        <p><?php echo h(age_label($age)); ?></p>
    </div>
    <div class="element_wrap">
        <label>お問い合わせ内容</label>
        <p><?php echo nl2br(h($contact)); ?></p>
    </div>
    <div class="element_wrap">
        <label>プライバシーポリシー</label>
        <p>同意する</p>
    </div>

    <input type="hidden" name="your_name" value="<?php echo h($your_name); ?>">
    <input type="hidden" name="email" value="<?php echo h($email); ?>">
    <input type="hidden" name="gender" value="<?php echo h($gender); ?>">
    <input type="hidden" name="age" value="<?php echo h($age); ?>">
    <input type="hidden" name="contact" value="<?php echo h($contact); ?>">
    <input type="hidden" name="agreement" value="1">

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
        <?php if (!empty($errors['your_name'])): ?><div class="error"><?php echo h($errors['your_name']); ?></div><?php endif; ?>
    </div>

    <div class="element_wrap">
        <label>メールアドレス</label>
        <input type="text" name="email" value="<?php echo h($email); ?>">
        <?php if (!empty($errors['email'])): ?><div class="error"><?php echo h($errors['email']); ?></div><?php endif; ?>
    </div>

    <div class="element_wrap">
        <label>性別</label>
        <label for="gender_male"><input id="gender_male" type="radio" name="gender" value="male" <?php if ($gender === "male") echo 'checked'; ?>>男性</label>
        <label for="gender_female"><input id="gender_female" type="radio" name="gender" value="female" <?php if ($gender === "female") echo 'checked'; ?>>女性</label>
        <?php if (!empty($errors['gender'])): ?><div class="error"><?php echo h($errors['gender']); ?></div><?php endif; ?>
    </div>

    <div class="element_wrap">
        <label>年齢</label>
        <select name="age">
            <option value="">選択してください</option>
            <option value="1" <?php if ($age === "1") echo 'selected'; ?>>〜19歳</option>
            <option value="2" <?php if ($age === "2") echo 'selected'; ?>>20歳〜29歳</option>
            <option value="3" <?php if ($age === "3") echo 'selected'; ?>>30歳〜39歳</option>
            <option value="4" <?php if ($age === "4") echo 'selected'; ?>>40歳〜49歳</option>
            <option value="5" <?php if ($age === "5") echo 'selected'; ?>>50歳〜59歳</option>
            <option value="6" <?php if ($age === "6") echo 'selected'; ?>>60歳〜</option>
        </select>
        <?php if (!empty($errors['age'])): ?><div class="error"><?php echo h($errors['age']); ?></div><?php endif; ?>
    </div>

    <div class="element_wrap">
        <label>お問い合わせ内容</label>
        <textarea name="contact"><?php echo h($contact); ?></textarea>
        <?php if (!empty($errors['contact'])): ?><div class="error"><?php echo h($errors['contact']); ?></div><?php endif; ?>
    </div>

    <div class="element_wrap">
        <label for="agreement">
            <input id="agreement" type="checkbox" name="agreement" value="1" <?php if ($agreement === "1") echo 'checked'; ?>>
            プライバシーポリシーに同意する
        </label>
        <?php if (!empty($errors['agreement'])): ?><div class="error"><?php echo h($errors['agreement']); ?></div><?php endif; ?>
    </div>

    <input type="submit" name="btn_confirm" value="入力内容を確認する">
</form>

<?php endif; ?>

</body>
</html>

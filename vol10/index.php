<?php
session_start();

define('FILE_DIR', __DIR__ . '/images/test/');
define('FILE_URL', 'images/test/');

$page_flag = 0;
$clean = [];
$error = [];

if (!is_dir(FILE_DIR)) {
    mkdir(FILE_DIR, 0777, true);
}

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function validation($data) {
    $error = [];

    if (empty($data['your_name'])) {
        $error[] = "「氏名」は必ず入力してください。";
    } elseif (20 < mb_strlen($data['your_name'])) {
        $error[] = "「氏名」は20文字以内で入力してください。";
    }

    if (empty($data['email'])) {
        $error[] = "「メールアドレス」は必ず入力してください。";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error[] = "「メールアドレス」は正しい形式で入力してください。";
    }

    if (empty($data['gender'])) {
        $error[] = "「性別」は必ず入力してください。";
    } elseif ($data['gender'] !== 'male' && $data['gender'] !== 'female') {
        $error[] = "「性別」は必ず入力してください。";
    }

    if (empty($data['age'])) {
        $error[] = "「年齢」は必ず入力してください。";
    } elseif ((int)$data['age'] < 1 || 6 < (int)$data['age']) {
        $error[] = "「年齢」は必ず入力してください。";
    }

    if (empty($data['contact'])) {
        $error[] = "「お問い合わせ内容」は必ず入力してください。";
    }

    if (empty($data['agreement'])) {
        $error[] = "プライバシーポリシーをご確認ください。";
    } elseif ((int)$data['agreement'] !== 1) {
        $error[] = "プライバシーポリシーをご確認ください。";
    }

    return $error;
}

function ageLabel($age) {
    $list = [
        '1' => '〜19歳',
        '2' => '20歳〜29歳',
        '3' => '30歳〜39歳',
        '4' => '40歳〜49歳',
        '5' => '50歳〜59歳',
        '6' => '60歳〜',
    ];
    return $list[$age] ?? '';
}

if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        $clean[$key] = h($value);
    }
}

if (!empty($clean['btn_confirm'])) {
    $error = validation($clean);

    if (!empty($_FILES['attachment_file']['tmp_name'])) {
        $file = $_FILES['attachment_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error[] = 'ファイルのアップロードに失敗しました。';
        } else {
            $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowed_mime, true)) {
                $error[] = '画像ファイルのみ添付できます。';
            } else {
                $ext_map = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif',
                    'image/webp' => 'webp',
                ];
                $safe_name = uniqid('img_', true) . '.' . $ext_map[$mime];
                $destination = FILE_DIR . $safe_name;

                $upload_res = move_uploaded_file($file['tmp_name'], $destination);

                if ($upload_res !== true) {
                    $error[] = 'ファイルのアップロードに失敗しました。';
                } else {
                    $clean['attachment_file'] = $safe_name;
                }
            }
        }
    }

    if (empty($error)) {
        $page_flag = 1;
        $_SESSION['page'] = true;
    }

} elseif (!empty($clean['btn_submit'])) {
    if (!empty($_SESSION['page']) && $_SESSION['page'] === true) {
        unset($_SESSION['page']);
        $page_flag = 2;

        date_default_timezone_set('Asia/Tokyo');
        mb_language("ja");
        mb_internal_encoding("UTF-8");

        $boundary = '__BOUNDARY__';

        $header = "MIME-Version: 1.0\n";
        $header .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\n";
        $header .= "From: GRAYCODE <noreply@gray-code.com>\n";
        $header .= "Reply-To: GRAYCODE <noreply@gray-code.com>\n";

        $gender_text = ($clean['gender'] === 'male') ? '男性' : '女性';
        $age_text = ageLabel($clean['age']);

        $auto_reply_subject = 'お問い合わせありがとうございます。';

        $auto_reply_text = "この度は、お問い合わせ頂き誠にありがとうございます。\n";
        $auto_reply_text .= "下記の内容でお問い合わせを受け付けました。\n\n";
        $auto_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
        $auto_reply_text .= "氏名：" . $clean['your_name'] . "\n";
        $auto_reply_text .= "メールアドレス：" . $clean['email'] . "\n";
        $auto_reply_text .= "性別：" . $gender_text . "\n";
        $auto_reply_text .= "年齢：" . $age_text . "\n";
        $auto_reply_text .= "お問い合わせ内容：" . $clean['contact'] . "\n\n";
        $auto_reply_text .= "GRAYCODE 事務局\n";

        $body = "--{$boundary}\n";
        $body .= "Content-Type: text/plain; charset=\"UTF-8\"\n\n";
        $body .= $auto_reply_text . "\n";

        if (!empty($clean['attachment_file']) && is_file(FILE_DIR . $clean['attachment_file'])) {
            $body .= "--{$boundary}\n";
            $body .= "Content-Type: application/octet-stream; name=\"" . $clean['attachment_file'] . "\"\n";
            $body .= "Content-Disposition: attachment; filename=\"" . $clean['attachment_file'] . "\"\n";
            $body .= "Content-Transfer-Encoding: base64\n\n";
            $body .= chunk_split(base64_encode(file_get_contents(FILE_DIR . $clean['attachment_file']))) . "\n";
        }

        $body .= "--{$boundary}--\n";

        mb_send_mail($clean['email'], $auto_reply_subject, $body, $header);

        $admin_reply_subject = "お問い合わせを受け付けました";

        $admin_reply_text = "下記の内容でお問い合わせがありました。\n\n";
        $admin_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
        $admin_reply_text .= "氏名：" . $clean['your_name'] . "\n";
        $admin_reply_text .= "メールアドレス：" . $clean['email'] . "\n";
        $admin_reply_text .= "性別：" . $gender_text . "\n";
        $admin_reply_text .= "年齢：" . $age_text . "\n";
        $admin_reply_text .= "お問い合わせ内容：" . $clean['contact'] . "\n\n";

        $body = "--{$boundary}\n";
        $body .= "Content-Type: text/plain; charset=\"UTF-8\"\n\n";
        $body .= $admin_reply_text . "\n";

        if (!empty($clean['attachment_file']) && is_file(FILE_DIR . $clean['attachment_file'])) {
            $body .= "--{$boundary}\n";
            $body .= "Content-Type: application/octet-stream; name=\"" . $clean['attachment_file'] . "\"\n";
            $body .= "Content-Disposition: attachment; filename=\"" . $clean['attachment_file'] . "\"\n";
            $body .= "Content-Transfer-Encoding: base64\n\n";
            $body .= chunk_split(base64_encode(file_get_contents(FILE_DIR . $clean['attachment_file']))) . "\n";
        }

        $body .= "--{$boundary}--\n";

        mb_send_mail('webmaster@gray-code.com', $admin_reply_subject, $body, $header);
    } else {
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
}

h1 {
    margin-bottom: 20px;
    padding: 20px 0;
    color: #209eff;
    font-size: 122%;
    border-top: 1px solid #999;
    border-bottom: 1px solid #999;
}

input[type=text],
input[type=email],
select {
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

.error_list {
    padding: 10px 30px;
    color: #ff2e5a;
    font-size: 86%;
    text-align: left;
    border: 1px solid #ff2e5a;
    border-radius: 5px;
}
.preview-image {
    max-width: 300px;
    height: auto;
}
</style>
</head>
<body>
<h1>お問い合わせフォーム</h1>

<?php if ($page_flag === 1): ?>

<form method="post" action="">
    <div class="element_wrap">
        <label>氏名</label>
        <p><?php echo $clean['your_name']; ?></p>
    </div>
    <div class="element_wrap">
        <label>メールアドレス</label>
        <p><?php echo $clean['email']; ?></p>
    </div>
    <div class="element_wrap">
        <label>性別</label>
        <p><?php echo ($clean['gender'] === 'male') ? '男性' : '女性'; ?></p>
    </div>
    <div class="element_wrap">
        <label>年齢</label>
        <p><?php echo h(ageLabel($clean['age'])); ?></p>
    </div>
    <div class="element_wrap">
        <label>お問い合わせ内容</label>
        <p><?php echo nl2br($clean['contact']); ?></p>
    </div>
    <?php if (!empty($clean['attachment_file'])): ?>
    <div class="element_wrap">
        <label>画像ファイルの添付</label>
        <p><img class="preview-image" src="<?php echo h(FILE_URL . $clean['attachment_file']); ?>" alt=""></p>
    </div>
    <?php endif; ?>
    <div class="element_wrap">
        <label>プライバシーポリシーに同意する</label>
        <p><?php echo ($clean['agreement'] === "1") ? '同意する' : '同意しない'; ?></p>
    </div>

    <input type="submit" name="btn_back" value="戻る">
    <input type="submit" name="btn_submit" value="送信">

    <input type="hidden" name="your_name" value="<?php echo $clean['your_name']; ?>">
    <input type="hidden" name="email" value="<?php echo $clean['email']; ?>">
    <input type="hidden" name="gender" value="<?php echo $clean['gender']; ?>">
    <input type="hidden" name="age" value="<?php echo $clean['age']; ?>">
    <input type="hidden" name="contact" value="<?php echo $clean['contact']; ?>">
    <?php if (!empty($clean['attachment_file'])): ?>
        <input type="hidden" name="attachment_file" value="<?php echo h($clean['attachment_file']); ?>">
    <?php endif; ?>
    <input type="hidden" name="agreement" value="<?php echo $clean['agreement']; ?>">
</form>

<?php elseif ($page_flag === 2): ?>

<p>送信が完了しました。</p>

<?php else: ?>

<?php if (!empty($error)): ?>
<ul class="error_list">
    <?php foreach ($error as $value): ?>
        <li><?php echo h($value); ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data">
    <div class="element_wrap">
        <label>氏名</label>
        <input type="text" name="your_name" value="<?php echo !empty($clean['your_name']) ? $clean['your_name'] : ''; ?>">
    </div>
    <div class="element_wrap">
        <label>メールアドレス</label>
        <input type="email" name="email" value="<?php echo !empty($clean['email']) ? $clean['email'] : ''; ?>">
    </div>
    <div class="element_wrap">
        <label>性別</label>
        <label for="gender_male"><input id="gender_male" type="radio" name="gender" value="male" <?php echo (!empty($clean['gender']) && $clean['gender'] === "male") ? 'checked' : ''; ?>>男性</label>
        <label for="gender_female"><input id="gender_female" type="radio" name="gender" value="female" <?php echo (!empty($clean['gender']) && $clean['gender'] === "female") ? 'checked' : ''; ?>>女性</label>
    </div>
    <div class="element_wrap">
        <label>年齢</label>
        <select name="age">
            <option value="">選択してください</option>
            <option value="1" <?php echo (!empty($clean['age']) && $clean['age'] === "1") ? 'selected' : ''; ?>>〜19歳</option>
            <option value="2" <?php echo (!empty($clean['age']) && $clean['age'] === "2") ? 'selected' : ''; ?>>20歳〜29歳</option>
            <option value="3" <?php echo (!empty($clean['age']) && $clean['age'] === "3") ? 'selected' : ''; ?>>30歳〜39歳</option>
            <option value="4" <?php echo (!empty($clean['age']) && $clean['age'] === "4") ? 'selected' : ''; ?>>40歳〜49歳</option>
            <option value="5" <?php echo (!empty($clean['age']) && $clean['age'] === "5") ? 'selected' : ''; ?>>50歳〜59歳</option>
            <option value="6" <?php echo (!empty($clean['age']) && $clean['age'] === "6") ? 'selected' : ''; ?>>60歳〜</option>
        </select>
    </div>
    <div class="element_wrap">
        <label>お問い合わせ内容</label>
        <textarea name="contact"><?php echo !empty($clean['contact']) ? $clean['contact'] : ''; ?></textarea>
    </div>
    <div class="element_wrap">
        <label>画像ファイルの添付</label>
        <input type="file" name="attachment_file" accept="image/*">
    </div>
    <div class="element_wrap">
        <label for="agreement"><input id="agreement" type="checkbox" name="agreement" value="1" <?php echo (!empty($clean['agreement']) && $clean['agreement'] === "1") ? 'checked' : ''; ?>>プライバシーポリシーに同意する</label>
    </div>
    <input type="submit" name="btn_confirm" value="入力内容を確認する">
</form>

<?php endif; ?>
</body>
</html>

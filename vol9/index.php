<?php
session_start();

define('FILE_DIR', __DIR__ . '/images/test/');
define('FILE_URL', 'images/test/');
define('MAX_FILE_SIZE', 3 * 1024 * 1024); // 3MB

if (!is_dir(FILE_DIR)) {
    mkdir(FILE_DIR, 0755, true);
}

$page_flag = 0;
$clean = array();
$error = array();

$data = array(
    'your_name' => '',
    'email' => '',
    'gender' => '',
    'age' => '',
    'contact' => '',
    'agreement' => '',
    'attachment_file' => ''
);

// 初回アクセス時にCSRFトークン生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// PRG用の完了メッセージ
if (!empty($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
} else {
    $flash_message = '';
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function clean_mail_header_value($str) {
    return str_replace(array("\r", "\n"), '', trim($str ?? ''));
}

function gender_label($gender) {
    if ($gender === 'male') return '男性';
    if ($gender === 'female') return '女性';
    return '';
}

function age_label($age) {
    if ($age === '1') return '〜19歳';
    if ($age === '2') return '20歳〜29歳';
    if ($age === '3') return '30歳〜39歳';
    if ($age === '4') return '40歳〜49歳';
    if ($age === '5') return '50歳〜59歳';
    if ($age === '6') return '60歳〜';
    return '';
}

function validation($data) {
    $error = array();

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
        $error[] = "「性別」は正しく選択してください。";
    }

    if (empty($data['age'])) {
        $error[] = "「年齢」は必ず入力してください。";
    } elseif ((int)$data['age'] < 1 || 6 < (int)$data['age']) {
        $error[] = "「年齢」は正しく選択してください。";
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

function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function upload_image($file, &$error) {
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return '';
    }

    if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
        $error[] = 'ファイルのアップロードに失敗しました。';
        return '';
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        $error[] = 'ファイルサイズは3MB以下にしてください。';
        return '';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allow_mimes = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
    );

    if (!isset($allow_mimes[$mime])) {
        $error[] = '画像ファイルは JPG / PNG / GIF / WEBP のみアップロードできます。';
        return '';
    }

    $ext = $allow_mimes[$mime];
    $save_name = bin2hex(random_bytes(16)) . '.' . $ext;
    $save_path = FILE_DIR . $save_name;

    if (!move_uploaded_file($file['tmp_name'], $save_path)) {
        $error[] = 'ファイルの保存に失敗しました。';
        return '';
    }

    return $save_name;
}

function build_mail_header() {
    $boundary = '__BOUNDARY__';

    $header = "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    $header .= "From: GRAYCODE <noreply@gray-code.com>\r\n";
    $header .= "Reply-To: GRAYCODE <noreply@gray-code.com>\r\n";

    return $header;
}

function build_multipart_mail_body($text, $attachment_name = '') {
    $boundary = '__BOUNDARY__';

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $text . "\r\n";

    if ($attachment_name !== '') {
        $path = FILE_DIR . $attachment_name;

        if (is_file($path)) {
            $filename = basename($attachment_name);
            $content = chunk_split(base64_encode(file_get_contents($path)));

            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= $content . "\r\n";
        }
    }

    $body .= "--{$boundary}--\r\n";

    return $body;
}

function delete_uploaded_file($attachment_name) {
    if (empty($attachment_name)) {
        return;
    }

    $basename = basename($attachment_name);
    $path = FILE_DIR . $basename;

    if (is_file($path)) {
        unlink($path);
    }
}

// POST取り込み
if (!empty($_POST)) {
    foreach ($data as $key => $value) {
        $data[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
        $clean[$key] = h($data[$key]);
    }
}

mb_language("Japanese");
mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

// 確認ボタン
if (!empty($_POST['btn_confirm'])) {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error[] = '不正なリクエストです。ページを再読み込みしてやり直してください。';
    } else {
        $error = validation($data);

        if (!empty($_FILES['attachment_file']['tmp_name'])) {
            $saved_name = upload_image($_FILES['attachment_file'], $error);
            if ($saved_name !== '') {
                $data['attachment_file'] = $saved_name;
                $clean['attachment_file'] = h($saved_name);
            }
        }
    }

    if (empty($error)) {
        $page_flag = 1;
    }

// 送信ボタン
} elseif (!empty($_POST['btn_submit'])) {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error[] = '不正なリクエストです。ページを再読み込みしてやり直してください。';
    } else {
        $error = validation($data);

        if (!empty($data['attachment_file'])) {
            $basename = basename($data['attachment_file']);
            if ($basename !== $data['attachment_file'] || !is_file(FILE_DIR . $basename)) {
                $error[] = '添付ファイルの指定が不正です。';
            } else {
                $data['attachment_file'] = $basename;
                $clean['attachment_file'] = h($basename);
            }
        }

        if (empty($error)) {
            $mail_name = $data['your_name'];
            $mail_email = clean_mail_header_value($data['email']);
            $mail_gender = gender_label($data['gender']);
            $mail_age = age_label($data['age']);
            $mail_contact = $data['contact'];
            $mail_file = $data['attachment_file'];

            $header = build_mail_header();

            $auto_reply_subject = 'お問い合わせありがとうございます。';
            $auto_reply_text = "この度は、お問い合わせ頂き誠にありがとうございます。\n";
            $auto_reply_text .= "下記の内容でお問い合わせを受け付けました。\n\n";
            $auto_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
            $auto_reply_text .= "氏名：" . $mail_name . "\n";
            $auto_reply_text .= "メールアドレス：" . $mail_email . "\n";
            $auto_reply_text .= "性別：" . $mail_gender . "\n";
            $auto_reply_text .= "年齢：" . $mail_age . "\n";
            $auto_reply_text .= "お問い合わせ内容：\n" . $mail_contact . "\n\n";
            $auto_reply_text .= "GRAYCODE 事務局";

            $auto_body = build_multipart_mail_body($auto_reply_text, $mail_file);
            $user_mail_sent = mb_send_mail($mail_email, $auto_reply_subject, $auto_body, $header);

            $admin_reply_subject = "お問い合わせを受け付けました";
            $admin_reply_text = "下記の内容でお問い合わせがありました。\n\n";
            $admin_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
            $admin_reply_text .= "氏名：" . $mail_name . "\n";
            $admin_reply_text .= "メールアドレス：" . $mail_email . "\n";
            $admin_reply_text .= "性別：" . $mail_gender . "\n";
            $admin_reply_text .= "年齢：" . $mail_age . "\n";
            $admin_reply_text .= "お問い合わせ内容：\n" . $mail_contact . "\n\n";

            $admin_body = build_multipart_mail_body($admin_reply_text, $mail_file);
            $admin_mail_sent = mb_send_mail('webmaster@gray-code.com', $admin_reply_subject, $admin_body, $header);

            if ($user_mail_sent && $admin_mail_sent) {
                delete_uploaded_file($mail_file);
                $_SESSION['flash_message'] = '送信が完了しました。';
            } else {
                $_SESSION['flash_message'] = '送信に失敗しました。時間をおいて再度お試しください。';
            }

            // 送信後はトークン再生成
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // PRG
            header('Location: ' . $_SERVER['PHP_SELF'] . '?done=1');
            exit;
        }
    }

// 戻るボタン
} elseif (!empty($_POST['btn_back'])) {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error[] = '不正なリクエストです。ページを再読み込みしてやり直してください。';
    } else {
        $page_flag = 0;
    }
}

// GETでdone=1が来たら完了画面
if (isset($_GET['done']) && $_GET['done'] === '1') {
    $page_flag = 2;
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
.error_list {
    padding: 10px 30px;
    color: #ff2e5a;
    font-size: 86%;
    text-align: left;
    border: 1px solid #ff2e5a;
    border-radius: 5px;
}
.preview-image {
    max-width: 240px;
    height: auto;
}
.message {
    margin-top: 20px;
    font-weight: bold;
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
        <p><?php echo h(gender_label($data['gender'])); ?></p>
    </div>
    <div class="element_wrap">
        <label>年齢</label>
        <p><?php echo h(age_label($data['age'])); ?></p>
    </div>
    <div class="element_wrap">
        <label>お問い合わせ内容</label>
        <p><?php echo nl2br($clean['contact']); ?></p>
    </div>

    <?php if (!empty($clean['attachment_file'])): ?>
    <div class="element_wrap">
        <label>画像ファイルの添付</label>
        <p><img class="preview-image" src="<?php echo h(FILE_URL . $data['attachment_file']); ?>" alt="添付画像プレビュー"></p>
    </div>
    <?php endif; ?>

    <div class="element_wrap">
        <label>プライバシーポリシー</label>
        <p>同意する</p>
    </div>

    <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
    <input type="hidden" name="your_name" value="<?php echo $clean['your_name']; ?>">
    <input type="hidden" name="email" value="<?php echo $clean['email']; ?>">
    <input type="hidden" name="gender" value="<?php echo h($data['gender']); ?>">
    <input type="hidden" name="age" value="<?php echo h($data['age']); ?>">
    <input type="hidden" name="contact" value="<?php echo $clean['contact']; ?>">
    <input type="hidden" name="agreement" value="1">
    <?php if (!empty($clean['attachment_file'])): ?>
        <input type="hidden" name="attachment_file" value="<?php echo $clean['attachment_file']; ?>">
    <?php endif; ?>

    <input type="submit" name="btn_back" value="戻る">
    <input type="submit" name="btn_submit" value="送信">
</form>

<?php elseif ($page_flag === 2): ?>

<p class="message"><?php echo h($flash_message ?: '送信が完了しました。'); ?></p>

<?php else: ?>

<?php if (!empty($error)): ?>
    <ul class="error_list">
    <?php foreach ($error as $value): ?>
        <li><?php echo h($value); ?></li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">

    <div class="element_wrap">
        <label>氏名</label>
        <input type="text" name="your_name" value="<?php echo $clean['your_name'] ?? ''; ?>">
    </div>

    <div class="element_wrap">
        <label>メールアドレス</label>
        <input type="text" name="email" value="<?php echo $clean['email'] ?? ''; ?>">
    </div>

    <div class="element_wrap">
        <label>性別</label>
        <label for="gender_male"><input id="gender_male" type="radio" name="gender" value="male" <?php if (($data['gender'] ?? '') === 'male') echo 'checked'; ?>>男性</label>
        <label for="gender_female"><input id="gender_female" type="radio" name="gender" value="female" <?php if (($data['gender'] ?? '') === 'female') echo 'checked'; ?>>女性</label>
    </div>

    <div class="element_wrap">
        <label>年齢</label>
        <select name="age">
            <option value="">選択してください</option>
            <option value="1" <?php if (($data['age'] ?? '') === '1') echo 'selected'; ?>>〜19歳</option>
            <option value="2" <?php if (($data['age'] ?? '') === '2') echo 'selected'; ?>>20歳〜29歳</option>
            <option value="3" <?php if (($data['age'] ?? '') === '3') echo 'selected'; ?>>30歳〜39歳</option>
            <option value="4" <?php if (($data['age'] ?? '') === '4') echo 'selected'; ?>>40歳〜49歳</option>
            <option value="5" <?php if (($data['age'] ?? '') === '5') echo 'selected'; ?>>50歳〜59歳</option>
            <option value="6" <?php if (($data['age'] ?? '') === '6') echo 'selected'; ?>>60歳〜</option>
        </select>
    </div>

    <div class="element_wrap">
        <label>お問い合わせ内容</label>
        <textarea name="contact"><?php echo $clean['contact'] ?? ''; ?></textarea>
    </div>

    <div class="element_wrap">
        <label>画像ファイルの添付</label>
        <input type="file" name="attachment_file" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
    </div>

    <div class="element_wrap">
        <label for="agreement">
            <input id="agreement" type="checkbox" name="agreement" value="1" <?php if (($data['agreement'] ?? '') === '1') echo 'checked'; ?>>
            プライバシーポリシーに同意する
        </label>
    </div>

    <input type="submit" name="btn_confirm" value="入力内容を確認する">
</form>

<?php endif; ?>
</body>
</html>

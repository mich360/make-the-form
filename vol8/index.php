<?php
session_start();

define('FILE_DIR', __DIR__ . '/images/test/');
define('FILE_URL', 'images/test/');
define('ADMIN_EMAIL', 'webmaster@gray-code.com');
define('FROM_EMAIL', 'noreply@gray-code.com');
define('FROM_NAME', 'GRAYCODE');

// アップロード先ディレクトリ作成
if (!is_dir(FILE_DIR)) {
    mkdir(FILE_DIR, 0777, true);
}

// 文字コード設定
mb_language("Japanese");
mb_internal_encoding("UTF-8");

// 変数初期化
$page_flag = 0;
$clean = [];
$error = [];

// 初回アクセス時にCSRFトークン生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// POSTデータをサニタイズ
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            $clean[$key] = $value;
        } else {
            $clean[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
    }
}

// 戻るボタン
if (!empty($clean['btn_back'])) {
    $page_flag = 0;

// 確認ボタン
} elseif (!empty($clean['btn_confirm'])) {

    // CSRFチェック
    if (
        empty($clean['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $clean['csrf_token'])
    ) {
        $error[] = '不正な画面遷移です。';
    }

    // 入力チェック
    if (empty($error)) {
        $error = validation($clean);
    }

    // ファイルアップロード処理
    if (empty($error) && !empty($_FILES['attachment_file']['tmp_name'])) {
        $upload = handle_upload($_FILES['attachment_file']);

        if (!empty($upload['error'])) {
            $error[] = $upload['error'];
        } else {
            $clean['attachment_file'] = $upload['saved_name'];
            $_SESSION['attachment_file'] = $upload['saved_name'];
        }
    } else {
        // 新規アップロードが無い場合、既存 hidden を引き継ぐ
        if (!empty($clean['attachment_file'])) {
            $_SESSION['attachment_file'] = $clean['attachment_file'];
        }
    }

    if (empty($error)) {
        $page_flag = 1;
    }

// 送信ボタン
} elseif (!empty($clean['btn_submit'])) {

    // CSRFチェック
    if (
        empty($clean['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $clean['csrf_token'])
    ) {
        $error[] = '不正な画面遷移です。';
    }

    if (empty($error)) {
        $error = validation($clean);
    }

    if (empty($error)) {
        $page_flag = 2;

        date_default_timezone_set('Asia/Tokyo');

        $header  = "MIME-Version: 1.0\n";
        $header .= "From: " . mb_encode_mimeheader(FROM_NAME) . " <" . FROM_EMAIL . ">\n";
        $header .= "Reply-To: " . FROM_EMAIL . "\n";
        $header .= "Content-Type: text/plain; charset=UTF-8\n";

        $gender_text = ($clean['gender'] === 'male') ? '男性' : '女性';
        $age_text = get_age_label($clean['age']);

        $attachment_text = !empty($_SESSION['attachment_file'])
            ? "添付ファイル：" . $_SESSION['attachment_file'] . "\n"
            : "";

        // 自動返信
        $auto_reply_subject = 'お問い合わせありがとうございます。';
        $auto_reply_text  = "この度は、お問い合わせ頂き誠にありがとうございます。\n";
        $auto_reply_text .= "下記の内容でお問い合わせを受け付けました。\n\n";
        $auto_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
        $auto_reply_text .= "氏名：" . $clean['your_name'] . "\n";
        $auto_reply_text .= "メールアドレス：" . $clean['email'] . "\n";
        $auto_reply_text .= "性別：" . $gender_text . "\n";
        $auto_reply_text .= "年齢：" . $age_text . "\n";
        $auto_reply_text .= $attachment_text;
        $auto_reply_text .= "お問い合わせ内容：\n" . html_entity_decode($clean['contact'], ENT_QUOTES, 'UTF-8') . "\n\n";
        $auto_reply_text .= "GRAYCODE 事務局";

        @mb_send_mail($clean['email'], $auto_reply_subject, $auto_reply_text, $header);

        // 管理者通知
        $admin_reply_subject = "お問い合わせを受け付けました";
        $admin_reply_text  = "下記の内容でお問い合わせがありました。\n\n";
        $admin_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
        $admin_reply_text .= "氏名：" . $clean['your_name'] . "\n";
        $admin_reply_text .= "メールアドレス：" . $clean['email'] . "\n";
        $admin_reply_text .= "性別：" . $gender_text . "\n";
        $admin_reply_text .= "年齢：" . $age_text . "\n";
        $admin_reply_text .= $attachment_text;
        $admin_reply_text .= "お問い合わせ内容：\n" . html_entity_decode($clean['contact'], ENT_QUOTES, 'UTF-8') . "\n";

        @mb_send_mail(ADMIN_EMAIL, $admin_reply_subject, $admin_reply_text, $header);

        // 添付ファイル自動削除
        if (!empty($_SESSION['attachment_file'])) {
            $file_path = FILE_DIR . $_SESSION['attachment_file'];
            if (is_file($file_path)) {
                unlink($file_path);
            }
        }

        // セッション整理
        unset($_SESSION['attachment_file']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function validation($data)
{
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
        $error[] = "「性別」は正しく選択してください。";
    }

    if (empty($data['age'])) {
        $error[] = "「年齢」は必ず入力してください。";
    } elseif ((int)$data['age'] < 1 || (int)$data['age'] > 6) {
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

function get_age_label($age)
{
    switch ((string)$age) {
        case "1": return "〜19歳";
        case "2": return "20歳〜29歳";
        case "3": return "30歳〜39歳";
        case "4": return "40歳〜49歳";
        case "5": return "50歳〜59歳";
        case "6": return "60歳〜";
        default:  return "";
    }
}

function handle_upload($file)
{
    $result = [
        'saved_name' => '',
        'error' => ''
    ];

    if (!isset($file['error']) || is_array($file['error'])) {
        $result['error'] = 'ファイルアップロードが不正です。';
        return $result;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'ファイルのアップロードに失敗しました。';
        return $result;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $result['error'] = 'ファイルサイズは5MB以下にしてください。';
        return $result;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed_types[$mime_type])) {
        $result['error'] = '画像ファイル（jpg / png / gif / webp）のみ添付できます。';
        return $result;
    }

    $extension = $allowed_types[$mime_type];
    $saved_name = sha1_file($file['tmp_name']) . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $destination = FILE_DIR . $saved_name;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $result['error'] = 'ファイルの保存に失敗しました。';
        return $result;
    }

    $result['saved_name'] = $saved_name;
    return $result;
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
    max-width: 800px;
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

input[type=text],
input[type=email],
select,
textarea {
    padding: 8px 10px;
    font-size: 14px;
    border: none;
    border-radius: 3px;
    background: #ddf0ff;
    width: 60%;
    max-width: 100%;
    box-sizing: border-box;
}

textarea {
    height: 120px;
    resize: vertical;
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

label.main_label {
    display: inline-block;
    margin-bottom: 10px;
    font-weight: bold;
    width: 170px;
    vertical-align: top;
}

.element_wrap p {
    display: inline-block;
    margin: 0;
    text-align: left;
    width: calc(100% - 180px);
    word-break: break-word;
}

.inline_label {
    margin-right: 10px;
    font-weight: normal;
}

.error_list {
    padding: 10px 30px;
    color: #ff2e5a;
    font-size: 86%;
    text-align: left;
    border: 1px solid #ff2e5a;
    border-radius: 5px;
    margin-bottom: 20px;
}

.preview_image {
    max-width: 300px;
    height: auto;
    border: 1px solid #ccc;
    border-radius: 6px;
}
</style>
</head>
<body>
<div class="container">
<h1>お問い合わせフォーム</h1>

<?php if ($page_flag === 1): ?>

<form method="post" action="">
    <div class="element_wrap">
        <label class="main_label">氏名</label>
        <p><?php echo $clean['your_name']; ?></p>
    </div>
    <div class="element_wrap">
        <label class="main_label">メールアドレス</label>
        <p><?php echo $clean['email']; ?></p>
    </div>
    <div class="element_wrap">
        <label class="main_label">性別</label>
        <p><?php echo ($clean['gender'] === "male") ? '男性' : '女性'; ?></p>
    </div>
    <div class="element_wrap">
        <label class="main_label">年齢</label>
        <p><?php echo get_age_label($clean['age']); ?></p>
    </div>
    <div class="element_wrap">
        <label class="main_label">お問い合わせ内容</label>
        <p><?php echo nl2br($clean['contact']); ?></p>
    </div>

    <?php if (!empty($_SESSION['attachment_file'])): ?>
    <div class="element_wrap">
        <label class="main_label">画像ファイルの添付</label>
        <p>
            <img
                class="preview_image"
                src="<?php echo htmlspecialchars(FILE_URL . $_SESSION['attachment_file'], ENT_QUOTES, 'UTF-8'); ?>"
                alt="添付画像プレビュー">
        </p>
    </div>
    <?php endif; ?>

    <div class="element_wrap">
        <label class="main_label">プライバシーポリシー</label>
        <p><?php echo ($clean['agreement'] === "1") ? '同意する' : '同意しない'; ?></p>
    </div>

    <input type="submit" name="btn_back" value="戻る">
    <input type="submit" name="btn_submit" value="送信">

    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="your_name" value="<?php echo $clean['your_name']; ?>">
    <input type="hidden" name="email" value="<?php echo $clean['email']; ?>">
    <input type="hidden" name="gender" value="<?php echo $clean['gender']; ?>">
    <input type="hidden" name="age" value="<?php echo $clean['age']; ?>">
    <input type="hidden" name="contact" value="<?php echo $clean['contact']; ?>">
    <input type="hidden" name="attachment_file" value="<?php echo !empty($_SESSION['attachment_file']) ? htmlspecialchars($_SESSION['attachment_file'], ENT_QUOTES, 'UTF-8') : ''; ?>">
    <input type="hidden" name="agreement" value="<?php echo $clean['agreement']; ?>">
</form>

<?php elseif ($page_flag === 2): ?>

<p>送信が完了しました。</p>

<?php else: ?>

<?php if (!empty($error)): ?>
    <ul class="error_list">
    <?php foreach ($error as $value): ?>
        <li><?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data">
    <div class="element_wrap">
        <label class="main_label">氏名</label>
        <input type="text" name="your_name" value="<?php echo !empty($clean['your_name']) ? $clean['your_name'] : ''; ?>">
    </div>
    <div class="element_wrap">
        <label class="main_label">メールアドレス</label>
        <input type="text" name="email" value="<?php echo !empty($clean['email']) ? $clean['email'] : ''; ?>">
    </div>
    <div class="element_wrap">
        <label class="main_label">性別</label>
        <label class="inline_label" for="gender_male">
            <input id="gender_male" type="radio" name="gender" value="male" <?php echo (!empty($clean['gender']) && $clean['gender'] === "male") ? 'checked' : ''; ?>>
            男性
        </label>
        <label class="inline_label" for="gender_female">
            <input id="gender_female" type="radio" name="gender" value="female" <?php echo (!empty($clean['gender']) && $clean['gender'] === "female") ? 'checked' : ''; ?>>
            女性
        </label>
    </div>
    <div class="element_wrap">
        <label class="main_label">年齢</label>
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
        <label class="main_label">お問い合わせ内容</label>
        <textarea name="contact"><?php echo !empty($clean['contact']) ? $clean['contact'] : ''; ?></textarea>
    </div>
    <div class="element_wrap">
        <label class="main_label">画像ファイルの添付</label>
        <input type="file" name="attachment_file" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
    </div>
    <div class="element_wrap">
        <label class="inline_label" for="agreement">
            <input id="agreement" type="checkbox" name="agreement" value="1" <?php echo (!empty($clean['agreement']) && $clean['agreement'] === "1") ? 'checked' : ''; ?>>
            プライバシーポリシーに同意する
        </label>
    </div>

    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    <input type="submit" name="btn_confirm" value="入力内容を確認する">
</form>

<?php endif; ?>
</div>
</body>
</html>

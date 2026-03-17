<?php
session_start();

// 文字コード設定
mb_language("Japanese");
mb_internal_encoding("UTF-8");

// ===== 設定 =====
define('CONTACT_FILE', 'contact.php'); // 実ファイル名に合わせて変更
define('FROM_MAIL', 'noreply@gray-code.com');
define('ADMIN_MAIL', 'webmaster@gray-code.com');
define('SITE_NAME', 'GRAYCODE');
define('RATE_LIMIT_SECONDS', 10); // 送信間隔制限

// 変数の初期化
$page_flag = 0;
$clean = array();
$error = array();

// CSRFトークン生成
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// フォーム表示開始時刻を保存（初回のみ）
if (empty($_SESSION['form_start_time'])) {
	$_SESSION['form_start_time'] = time();
}

// PRG後の完了表示
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SESSION['form_success'])) {
	$page_flag = 2;
	unset($_SESSION['form_success']);
}

// サニタイズ
if (!empty($_POST)) {
	foreach ($_POST as $key => $value) {
		if (is_array($value)) {
			$clean[$key] = $value;
		} else {
			$clean[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
		}
	}
}

// 戻る
if (!empty($clean['btn_back'])) {
	$page_flag = 0;

// 確認
} elseif (!empty($clean['btn_confirm'])) {

	// honeypotチェック
	if (!empty($clean['website'])) {
		$error[] = '不正な送信が検出されました。';
	}

	// CSRFチェック
	if (empty($error) && (
		empty($clean['csrf_token']) ||
		empty($_SESSION['csrf_token']) ||
		!hash_equals($_SESSION['csrf_token'], $clean['csrf_token'])
	)) {
		$error[] = '不正な画面遷移です。';
	}

	// 短時間送信対策（3秒未満はBotっぽいので確認段階で弾く）
	if (empty($error) && !empty($_SESSION['form_start_time'])) {
		$elapsed = time() - $_SESSION['form_start_time'];
		if ($elapsed < 3) {
			$error[] = '送信操作が早すぎます。少し時間をおいてお試しください。';
		}
	}

	if (empty($error)) {
		$error = validation($clean);
	}

	if (empty($error)) {
		$page_flag = 1;
	}

// 送信
} elseif (!empty($clean['btn_submit'])) {

	// honeypotチェック
	if (!empty($clean['website'])) {
		$error[] = '不正な送信が検出されました。';
	}

	// CSRFチェック
	if (empty($error) && (
		empty($clean['csrf_token']) ||
		empty($_SESSION['csrf_token']) ||
		!hash_equals($_SESSION['csrf_token'], $clean['csrf_token'])
	)) {
		$error[] = '不正な画面遷移です。';
	}

	if (empty($error)) {
		$error = validation($clean);
	}

	// 送信間隔制限
	if (empty($error) && !empty($_SESSION['last_submit_time'])) {
		$diff = time() - $_SESSION['last_submit_time'];
		if ($diff < RATE_LIMIT_SECONDS) {
			$wait = RATE_LIMIT_SECONDS - $diff;
			$error[] = '連続送信はできません。あと' . $wait . '秒ほど待ってから送信してください。';
			$page_flag = 1;
		}
	}

	if (empty($error)) {
		date_default_timezone_set('Asia/Tokyo');

		$header = "MIME-Version: 1.0\n";
		$header .= "From: " . SITE_NAME . " <" . FROM_MAIL . ">\n";
		$header .= "Reply-To: " . FROM_MAIL . "\n";
		$header .= "Content-Type: text/plain; charset=UTF-8\n";

		$auto_reply_subject = 'お問い合わせありがとうございます。';
		$admin_reply_subject = 'お問い合わせを受け付けました';

		$gender_text = ($clean['gender'] === 'male') ? '男性' : '女性';
		$age_text = get_age_label($clean['age']);
		$contact_text = html_entity_decode($clean['contact'], ENT_QUOTES, 'UTF-8');

		$auto_reply_text = "この度は、お問い合わせ頂き誠にありがとうございます。\n";
		$auto_reply_text .= "下記の内容でお問い合わせを受け付けました。\n\n";
		$auto_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
		$auto_reply_text .= "氏名：" . $clean['your_name'] . "\n";
		$auto_reply_text .= "メールアドレス：" . $clean['email'] . "\n";
		$auto_reply_text .= "性別：" . $gender_text . "\n";
		$auto_reply_text .= "年齢：" . $age_text . "\n";
		$auto_reply_text .= "お問い合わせ内容：\n" . $contact_text . "\n\n";
		$auto_reply_text .= SITE_NAME . " 事務局";

		$admin_reply_text = "下記の内容でお問い合わせがありました。\n\n";
		$admin_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
		$admin_reply_text .= "氏名：" . $clean['your_name'] . "\n";
		$admin_reply_text .= "メールアドレス：" . $clean['email'] . "\n";
		$admin_reply_text .= "性別：" . $gender_text . "\n";
		$admin_reply_text .= "年齢：" . $age_text . "\n";
		$admin_reply_text .= "お問い合わせ内容：\n" . $contact_text . "\n";

		$user_mail_result = mb_send_mail($clean['email'], $auto_reply_subject, $auto_reply_text, $header);
		$admin_mail_result = mb_send_mail(ADMIN_MAIL, $admin_reply_subject, $admin_reply_text, $header);

		if ($user_mail_result && $admin_mail_result) {
			// 送信成功時刻を保存
			$_SESSION['last_submit_time'] = time();

			// 各種トークン再生成
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
			$_SESSION['form_start_time'] = time();

			// PRG
			$_SESSION['form_success'] = true;
			header('Location: ' . CONTACT_FILE);
			exit;
		} else {
			$error[] = 'メール送信に失敗しました。時間をおいて再度お試しください。';
			$page_flag = 1;
		}
	}
}

function validation($data) {

	$error = array();

	if (empty($data['your_name'])) {
		$error[] = '「氏名」は必ず入力してください。';
	} elseif (20 < mb_strlen($data['your_name'])) {
		$error[] = '「氏名」は20文字以内で入力してください。';
	}

	if (empty($data['email'])) {
		$error[] = '「メールアドレス」は必ず入力してください。';
	} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
		$error[] = '「メールアドレス」は正しい形式で入力してください。';
	}

	if (empty($data['gender'])) {
		$error[] = '「性別」は必ず入力してください。';
	} elseif ($data['gender'] !== 'male' && $data['gender'] !== 'female') {
		$error[] = '「性別」は正しく選択してください。';
	}

	if (empty($data['age'])) {
		$error[] = '「年齢」は必ず入力してください。';
	} elseif ((int)$data['age'] < 1 || 6 < (int)$data['age']) {
		$error[] = '「年齢」は正しく選択してください。';
	}

	if (empty($data['contact'])) {
		$error[] = '「お問い合わせ内容」は必ず入力してください。';
	} elseif (1000 < mb_strlen($data['contact'])) {
		$error[] = '「お問い合わせ内容」は1000文字以内で入力してください。';
	}

	if (empty($data['agreement'])) {
		$error[] = 'プライバシーポリシーをご確認ください。';
	} elseif ((int)$data['agreement'] !== 1) {
		$error[] = 'プライバシーポリシーをご確認ください。';
	}

	return $error;
}

function get_age_label($age) {
	if ($age === "1") {
		return "〜19歳";
	} elseif ($age === "2") {
		return "20歳〜29歳";
	} elseif ($age === "3") {
		return "30歳〜39歳";
	} elseif ($age === "4") {
		return "40歳〜49歳";
	} elseif ($age === "5") {
		return "50歳〜59歳";
	} elseif ($age === "6") {
		return "60歳〜";
	}
	return "";
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

.wrapper {
	max-width: 820px;
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
	box-sizing: border-box;
}

input[type=text],
input[type=email],
select,
textarea {
	width: 60%;
	max-width: 100%;
}

input[name=btn_confirm],
input[name=btn_submit],
input[name=btn_back] {
	margin-top: 10px;
	padding: 7px 20px;
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
	padding: 12px 0;
	border-bottom: 1px solid #ccc;
	text-align: left;
}

label.main_label {
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
	width: calc(100% - 160px);
	word-break: break-word;
}

label[for=gender_male],
label[for=gender_female],
label[for=agreement] {
	margin-right: 10px;
	width: auto;
	font-weight: normal;
}

textarea[name=contact] {
	height: 120px;
	resize: vertical;
}

.error_list {
	padding: 10px 30px;
	color: #ff2e5a;
	font-size: 14px;
	text-align: left;
	border: 1px solid #ff2e5a;
	border-radius: 5px;
	margin-bottom: 20px;
	background: #fff8fa;
}

.complete_message {
	margin-top: 30px;
	padding: 20px;
	border: 1px solid #b7e3ff;
	border-radius: 6px;
	background: #eef8ff;
	color: #333;
}

/* honeypot 非表示 */
.hp-wrap {
	position: absolute;
	left: -9999px;
	width: 1px;
	height: 1px;
	overflow: hidden;
}
</style>
</head>
<body>
<div class="wrapper">
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
	<div class="element_wrap">
		<label class="main_label">プライバシーポリシー</label>
		<p><?php echo ($clean['agreement'] === "1") ? '同意する' : '同意しない'; ?></p>
	</div>

	<div class="hp-wrap" aria-hidden="true">
		<label for="website_confirm">Website</label>
		<input type="text" name="website" id="website_confirm" tabindex="-1" autocomplete="off" value="">
	</div>

	<input type="submit" name="btn_back" value="戻る">
	<input type="submit" name="btn_submit" value="送信">

	<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
	<input type="hidden" name="your_name" value="<?php echo $clean['your_name']; ?>">
	<input type="hidden" name="email" value="<?php echo $clean['email']; ?>">
	<input type="hidden" name="gender" value="<?php echo $clean['gender']; ?>">
	<input type="hidden" name="age" value="<?php echo $clean['age']; ?>">
	<input type="hidden" name="contact" value="<?php echo $clean['contact']; ?>">
	<input type="hidden" name="agreement" value="<?php echo $clean['agreement']; ?>">
</form>

<?php elseif ($page_flag === 2): ?>

<div class="complete_message">
	<p>送信が完了しました。</p>
</div>

<?php else: ?>

<?php if (!empty($error)): ?>
	<ul class="error_list">
	<?php foreach ($error as $value): ?>
		<li><?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></li>
	<?php endforeach; ?>
	</ul>
<?php endif; ?>

<form method="post" action="">
	<div class="element_wrap">
		<label class="main_label">氏名</label>
		<input type="text" name="your_name" value="<?php if (!empty($clean['your_name'])) { echo $clean['your_name']; } ?>">
	</div>
	<div class="element_wrap">
		<label class="main_label">メールアドレス</label>
		<input type="email" name="email" value="<?php if (!empty($clean['email'])) { echo $clean['email']; } ?>">
	</div>
	<div class="element_wrap">
		<label class="main_label">性別</label>
		<label for="gender_male"><input id="gender_male" type="radio" name="gender" value="male" <?php if (!empty($clean['gender']) && $clean['gender'] === "male") { echo 'checked'; } ?>>男性</label>
		<label for="gender_female"><input id="gender_female" type="radio" name="gender" value="female" <?php if (!empty($clean['gender']) && $clean['gender'] === "female") { echo 'checked'; } ?>>女性</label>
	</div>
	<div class="element_wrap">
		<label class="main_label">年齢</label>
		<select name="age">
			<option value="">選択してください</option>
			<option value="1" <?php if (!empty($clean['age']) && $clean['age'] === "1") { echo 'selected'; } ?>>〜19歳</option>
			<option value="2" <?php if (!empty($clean['age']) && $clean['age'] === "2") { echo 'selected'; } ?>>20歳〜29歳</option>
			<option value="3" <?php if (!empty($clean['age']) && $clean['age'] === "3") { echo 'selected'; } ?>>30歳〜39歳</option>
			<option value="4" <?php if (!empty($clean['age']) && $clean['age'] === "4") { echo 'selected'; } ?>>40歳〜49歳</option>
			<option value="5" <?php if (!empty($clean['age']) && $clean['age'] === "5") { echo 'selected'; } ?>>50歳〜59歳</option>
			<option value="6" <?php if (!empty($clean['age']) && $clean['age'] === "6") { echo 'selected'; } ?>>60歳〜</option>
		</select>
	</div>
	<div class="element_wrap">
		<label class="main_label">お問い合わせ内容</label>
		<textarea name="contact"><?php if (!empty($clean['contact'])) { echo $clean['contact']; } ?></textarea>
	</div>
	<div class="element_wrap">
		<label for="agreement"><input id="agreement" type="checkbox" name="agreement" value="1" <?php if (!empty($clean['agreement']) && $clean['agreement'] === "1") { echo 'checked'; } ?>>プライバシーポリシーに同意する</label>
	</div>

	<div class="hp-wrap" aria-hidden="true">
		<label for="website">Website</label>
		<input type="text" name="website" id="website" tabindex="-1" autocomplete="off" value="">
	</div>

	<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
	<input type="submit" name="btn_confirm" value="入力内容を確認する">
</form>

<?php endif; ?>
</div>
</body>
</html>

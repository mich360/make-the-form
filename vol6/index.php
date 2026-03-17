<?php

// 変数の初期化
$page_flag = 0;
$clean = array();
$error = array();
$data = array(
	'your_name' => '',
	'email' => '',
	'gender' => '',
	'age' => '',
	'contact' => '',
	'agreement' => ''
);

// HTMLエスケープ
function h($str) {
	return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// メールヘッダ用クリーニング
function clean_mail_header_value($str) {
	return str_replace(array("\r", "\n"), '', trim($str ?? ''));
}

// ラベル変換
function gender_label($gender) {
	if ($gender === 'male') {
		return '男性';
	} elseif ($gender === 'female') {
		return '女性';
	}
	return '';
}

function age_label($age) {
	if ($age === '1') {
		return '〜19歳';
	} elseif ($age === '2') {
		return '20歳〜29歳';
	} elseif ($age === '3') {
		return '30歳〜39歳';
	} elseif ($age === '4') {
		return '40歳〜49歳';
	} elseif ($age === '5') {
		return '50歳〜59歳';
	} elseif ($age === '6') {
		return '60歳〜';
	}
	return '';
}

// バリデーション
function validation($data) {
	$error = array();

	if (empty($data['your_name'])) {
		$error[] = "「氏名」は必ず入力してください。";
	}

	if (empty($data['email'])) {
		$error[] = "「メールアドレス」は必ず入力してください。";
	} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
		$error[] = "「メールアドレス」は正しい形式で入力してください。";
	}

	if (empty($data['gender'])) {
		$error[] = "「性別」は必ず入力してください。";
	}

	if (empty($data['age'])) {
		$error[] = "「年齢」は必ず入力してください。";
	}

	if (empty($data['contact'])) {
		$error[] = "「お問い合わせ内容」は必ず入力してください。";
	}

	if (empty($data['agreement'])) {
		$error[] = "プライバシーポリシーをご確認ください。";
	}

	return $error;
}

// POST受け取り
if (!empty($_POST)) {
	foreach ($data as $key => $value) {
		$data[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
		$clean[$key] = h($data[$key]);
	}
}

// 日本語メール設定
mb_language("Japanese");
mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

if (!empty($_POST['btn_confirm'])) {

	$error = validation($data);

	if (empty($error)) {
		$page_flag = 1;
	}

} elseif (!empty($_POST['btn_submit'])) {

	// 送信前も再検証
	$error = validation($data);

	if (empty($error)) {
		$page_flag = 2;

		$mail_name = $data['your_name'];
		$mail_email = clean_mail_header_value($data['email']);
		$mail_gender = gender_label($data['gender']);
		$mail_age = age_label($data['age']);
		$mail_contact = $data['contact'];

		// 変数とタイムゾーンを初期化
		$header = null;
		$auto_reply_subject = null;
		$auto_reply_text = null;
		$admin_reply_subject = null;
		$admin_reply_text = null;

		$header = "MIME-Version: 1.0\r\n";
		$header .= "From: GRAYCODE <noreply@gray-code.com>\r\n";
		$header .= "Reply-To: GRAYCODE <noreply@gray-code.com>\r\n";

		// 件名を設定
		$auto_reply_subject = 'お問い合わせありがとうございます。';

		// 本文を設定
		$auto_reply_text = "この度は、お問い合わせ頂き誠にありがとうございます。\n";
		$auto_reply_text .= "下記の内容でお問い合わせを受け付けました。\n\n";
		$auto_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
		$auto_reply_text .= "氏名：" . $mail_name . "\n";
		$auto_reply_text .= "メールアドレス：" . $mail_email . "\n";
		$auto_reply_text .= "性別：" . $mail_gender . "\n";
		$auto_reply_text .= "年齢：" . $mail_age . "\n";
		$auto_reply_text .= "お問い合わせ内容：\n" . $mail_contact . "\n\n";
		$auto_reply_text .= "GRAYCODE 事務局";

		// 自動返信メール送信
		mb_send_mail($mail_email, $auto_reply_subject, $auto_reply_text, $header);

		// 運営側へ送るメールの件名
		$admin_reply_subject = "お問い合わせを受け付けました";

		// 本文を設定
		$admin_reply_text = "下記の内容でお問い合わせがありました。\n\n";
		$admin_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
		$admin_reply_text .= "氏名：" . $mail_name . "\n";
		$admin_reply_text .= "メールアドレス：" . $mail_email . "\n";
		$admin_reply_text .= "性別：" . $mail_gender . "\n";
		$admin_reply_text .= "年齢：" . $mail_age . "\n";
		$admin_reply_text .= "お問い合わせ内容：\n" . $mail_contact . "\n\n";

		// 管理者へメール送信
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

input[type=text],
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
	<div class="element_wrap">
		<label>プライバシーポリシー</label>
		<p>同意する</p>
	</div>

	<input type="submit" name="btn_back" value="戻る">
	<input type="submit" name="btn_submit" value="送信">

	<input type="hidden" name="your_name" value="<?php echo $clean['your_name']; ?>">
	<input type="hidden" name="email" value="<?php echo $clean['email']; ?>">
	<input type="hidden" name="gender" value="<?php echo h($data['gender']); ?>">
	<input type="hidden" name="age" value="<?php echo h($data['age']); ?>">
	<input type="hidden" name="contact" value="<?php echo $clean['contact']; ?>">
	<input type="hidden" name="agreement" value="1">
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

<form method="post" action="">
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
		<label for="gender_male"><input id="gender_male" type="radio" name="gender" value="male" <?php if (($data['gender'] ?? '') === "male") { echo 'checked'; } ?>>男性</label>
		<label for="gender_female"><input id="gender_female" type="radio" name="gender" value="female" <?php if (($data['gender'] ?? '') === "female") { echo 'checked'; } ?>>女性</label>
	</div>

	<div class="element_wrap">
		<label>年齢</label>
		<select name="age">
			<option value="">選択してください</option>
			<option value="1" <?php if (($data['age'] ?? '') === "1") { echo 'selected'; } ?>>〜19歳</option>
			<option value="2" <?php if (($data['age'] ?? '') === "2") { echo 'selected'; } ?>>20歳〜29歳</option>
			<option value="3" <?php if (($data['age'] ?? '') === "3") { echo 'selected'; } ?>>30歳〜39歳</option>
			<option value="4" <?php if (($data['age'] ?? '') === "4") { echo 'selected'; } ?>>40歳〜49歳</option>
			<option value="5" <?php if (($data['age'] ?? '') === "5") { echo 'selected'; } ?>>50歳〜59歳</option>
			<option value="6" <?php if (($data['age'] ?? '') === "6") { echo 'selected'; } ?>>60歳〜</option>
		</select>
	</div>

	<div class="element_wrap">
		<label>お問い合わせ内容</label>
		<textarea name="contact"><?php echo $clean['contact'] ?? ''; ?></textarea>
	</div>

	<div class="element_wrap">
		<label for="agreement">
			<input id="agreement" type="checkbox" name="agreement" value="1" <?php if (($data['agreement'] ?? '') === "1") { echo 'checked'; } ?>>
			プライバシーポリシーに同意する
		</label>
	</div>

	<input type="submit" name="btn_confirm" value="入力内容を確認する">
</form>

<?php endif; ?>

</body>
</html>

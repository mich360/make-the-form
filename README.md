# 🛡️ お問い合わせフォーム 完成系まとめ

## ✅ ① CSRF対策（なりすまし防止）
**目的：外部サイトから勝手に送信されるのを防ぐ**

- トークンをセッションに保存
- フォームに hidden で埋め込み
- 送信時に一致チェック

```php
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

✅ ② PRG方式（再送信防止）

目的：リロード・戻るでメール再送信されるのを防ぐ

流れ：

POST送信

処理成功

GETへリダイレクト

header('Location: contact.php');
exit;
✅ ② PRG方式（再送信防止）

目的：リロード・戻るでメール再送信されるのを防ぐ

流れ：

POST送信

処理成功

GETへリダイレクト
if (!empty($clean['website'])) {
    $error[] = '不正な送信';
}
✅ ⑤ 入力時間チェック（Bot検出）

目的：瞬間送信Botを弾く
if ($elapsed < 3) {
    $error[] = '操作が早すぎます';
}
✅ ⑥ バリデーション

目的：不正・異常データの排除

必須チェック

文字数制限

メール形式チェック
filter_var($email, FILTER_VALIDATE_EMAIL);
✅ ⑦ サニタイズ（XSS対策）

目的：スクリプト埋め込み防止
htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

👍 結論

👉 「安全・安定・実運用OKな完成形」
必要なら👇  
- `CHANGELOG.md`  
- GitHub用README  
- HTMLドキュメント版  

にも変換できます 👍

# LYLY PDF Generator - デプロイ手順

## ローカル開発 (Docker Compose)

```bash
docker compose up --build
```

- フロントエンド: http://localhost:3000
- バックエンド API: http://localhost:8080

---

## Railway デプロイ

### 前提条件

- [Railway CLI](https://docs.railway.com/guides/cli) インストール済み
- Railway アカウント (Hobby プラン $5/月 推奨)

### 1. プロジェクト作成

```bash
railway login
railway init
```

### 2. バックエンドサービスの設定

Railway Dashboard で backend サービスを作成:

1. **New Service** > **GitHub Repo** からリポジトリを接続
2. **Settings** タブ:
   - Root Directory: `/` (デフォルト)
   - Dockerfile Path: `Dockerfile`
3. **Variables** タブで環境変数を設定:

| 変数名 | 値 | 備考 |
|--------|-----|------|
| `CORS_ORIGINS` | `https://your-frontend.up.railway.app` | フロントエンドのURL |
| `AUTH_PASSWORD` | `your_secure_password` | 認証を有効化 |
| `PORT` | (自動設定) | Railway が自動注入 |

4. **Volumes** タブで永続ボリュームを追加:
   - Mount Path: `/app/output`
   - Size: 1GB (必要に応じて調整)

5. **Settings** > **Networking** で Public Domain を生成
   - 生成されたURL (例: `lyly-backend.up.railway.app`) をメモ

### 3. フロントエンドサービスの設定

Railway Dashboard で frontend サービスを作成:

1. **New Service** > **GitHub Repo** から同じリポジトリを接続
2. **Settings** タブ:
   - Root Directory: `frontend`
   - Dockerfile Path: `Dockerfile`
3. **Variables** タブで環境変数を設定:

| 変数名 | 値 | 備考 |
|--------|-----|------|
| `PHP_BACKEND_URL` | `http://backend.railway.internal:8080` | Railway内部ネットワーク |
| `NEXT_PUBLIC_PHP_DIRECT_URL` | `https://lyly-backend.up.railway.app` | Step 2で生成した公開URL |

> **重要:** `NEXT_PUBLIC_PHP_DIRECT_URL` はビルド時に埋め込まれるため、変更後は再デプロイが必要です。

4. **Settings** > **Networking** で Public Domain を生成

### 4. CORS設定の更新

フロントエンドの公開URLが確定したら、バックエンドの `CORS_ORIGINS` を更新:

```
CORS_ORIGINS=https://your-frontend.up.railway.app
```

複数オリジンはカンマ区切り:
```
CORS_ORIGINS=https://your-frontend.up.railway.app,http://localhost:3000
```

### 5. 動作確認

1. フロントエンドURLにアクセス
2. ログイン画面が表示されることを確認 (AUTH_PASSWORD 設定時)
3. CSVをアップロードしてPDF生成テスト

---

## カスタムドメイン

Railway Dashboard > Settings > Networking > Custom Domain から設定可能。
SSL証明書は Railway が自動発行。

CORS_ORIGINS にカスタムドメインを追加すること:
```
CORS_ORIGINS=https://lyly.example.com
```

---

## トラブルシューティング

### PDF生成がタイムアウトする
- Railway のデフォルトタイムアウトは60秒
- Dockerfileで `max_execution_time = 600` を設定済み
- 大量注文の場合は SSE ストリーミング (`generate_stream`) を使用

### ボリュームのデータが消えた
- Railway Volume は永続化されるが、サービス削除時に消える
- 定期的に ZIP ダウンロードでバックアップ推奨

### CORS エラー
- `CORS_ORIGINS` にフロントエンドの正確なURL（末尾スラッシュなし）を設定
- `https://` と `http://` を間違えないこと

---
name: lyly-dev
description: LYLY PDF開発時のルールと注意事項を表示
---

# LYLY PDF開発ルール

## 厳守事項

1. **DRAFTS_XYS座標は変更禁止**
   - `config.php` 647-661行目
   - 変更時は必ずユーザーに確認を取る

2. **Rotate関数の使用ルール**
   - 必ず `StartTransform()` / `StopTransform()` で囲む
   - 回転軸: 要素の中心点 `(x + w/2, y + h/2)`

3. **1機能ずつ変更→テスト**
   - 複数同時変更禁止
   - 変更後は `php run.php` でテスト

4. **変更前に必ずバックアップ**
   - git commitまたはファイルコピー

## 主要関数（include/lyly.php）
| 関数 | 行 | 機能 |
|------|-----|------|
| `create_pdf_one()` | 168 | 個別PDF生成 |
| `create_pdf()` | 436 | 印刷用PDF生成 |
| `rotate()` | 604 | PDF全体90度回転 |
| `rotate_one()` | 631 | PDF全体270度回転 |

## テンプレート要素タイプ
| タイプ | 説明 | 主要属性 |
|--------|------|---------|
| `image` | 画像 | x, y, w, h, angle, mask |
| `text` | 単行テキスト | x, y, w, h, font, font_size, auto_size |
| `multitext` | 複数行テキスト | x, y, w, h, font, font_size |
| `rect` | 矩形 | x, y, w, h |
| `ellipse` | 楕円 | x, y, w, h, angle |
| `calendar` | カレンダー | date, size |

## 動作確認
```bash
php run.php
# temp/ → 個別PDF
# draft/ → 印刷用PDF
```

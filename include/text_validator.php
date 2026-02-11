<?php
/**
 * テキスト検証モジュール
 * CSV入力テキストの文字化け・タイポ・日付時間形式を検証する
 */

// ============================================================
// フレーズ辞書（商品でよく使われる英語フレーズ）
// ============================================================
const PHRASE_DICTIONARY = [
    'Happy Birthday',
    'Happy Anniversary',
    'Happy Wedding',
    'Happy New Year',
    'Happy Valentine',
    'Happy Graduation',
    'Merry Christmas',
    'I Love You',
    'Thank You',
    'Best Friends Forever',
    'Together Forever',
    'Forever and Always',
    'Always and Forever',
    'Welcome Baby',
    'Hello World',
    'Now Playing',
    'Our Song',
    'Special Day',
    'Congratulations',
    'Just Married',
    'Best Wishes',
    'With Love',
    'You are my sunshine',
    'To the moon and back',
];

// ============================================================
// 単語辞書（商品テキストで頻出する英単語）
// ============================================================
const WORD_DICTIONARY = [
    // お祝い
    'happy', 'birthday', 'anniversary', 'congratulations', 'celebration',
    'wedding', 'graduation', 'christmas', 'valentine', 'easter',
    // 感情・形容詞
    'love', 'forever', 'always', 'together', 'memories', 'special',
    'beautiful', 'precious', 'wonderful', 'amazing', 'perfect',
    'sweet', 'lovely', 'favorite', 'greatest', 'dearest',
    // 人間関係
    'family', 'friends', 'baby', 'mother', 'father', 'sister', 'brother',
    'daughter', 'friend', 'husband', 'wife', 'parents',
    // 動詞
    'thank', 'welcome', 'remember', 'playing', 'married', 'wishes',
    // 時間
    'day', 'year', 'years', 'month', 'today', 'moment',
    // その他
    'best', 'first', 'new', 'merry', 'song', 'world', 'hello',
    'now', 'just', 'with', 'sunshine', 'moon', 'back', 'star',
];

// ============================================================
// タイポ検知対象のフィールド名パターン
// ============================================================
const TYPO_CHECK_FIELDS = [
    'タイトル',
    'テキスト',      // テキスト1, テキスト2, テキスト3 等にマッチ
    'ハッシュタグ',  // ハッシュタグ1段目, 2段目等にマッチ
];

// 日付フィールド名パターン
const DATE_CHECK_FIELDS = [
    '日付',
    '特別な記念日',
];

// 時間フィールド名パターン
const TIME_CHECK_FIELDS = [
    '時間',
    'テキスト2行目',  // ミュージックデザインで時間が入ることがある
];

// ============================================================
// エンコーディング検知・自動変換
// ============================================================

/**
 * CSVファイルのエンコーディングを検出し、必要に応じてUTF-8に変換する
 *
 * @param string $file_path CSVファイルのパス
 * @return array ['path' => string, 'converted' => bool, 'original_encoding' => string, 'warnings' => array]
 */
function validate_and_convert_encoding(string $file_path): array {
    $result = [
        'path' => $file_path,
        'converted' => false,
        'original_encoding' => 'UTF-8',
        'warnings' => [],
    ];

    $raw = file_get_contents($file_path);
    if ($raw === false) {
        $result['warnings'][] = 'ファイルを読み込めません: ' . $file_path;
        return $result;
    }

    // UTF-8 BOM検出・除去
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
        $raw = substr($raw, 3);
        $result['warnings'][] = 'UTF-8 BOMを検出・除去しました';
        // BOM除去した内容を一時ファイルに書き出し（元ファイルは変更しない）
        $bom_removed_path = preg_replace('/\.csv$/i', '_utf8.csv', $file_path);
        if ($bom_removed_path === $file_path) {
            $bom_removed_path = $file_path . '_utf8.csv';
        }
        file_put_contents($bom_removed_path, $raw);
        $result['path'] = $bom_removed_path;
        $result['converted'] = true;
    }

    // エンコーディング検出（strict mode）
    $detected = mb_detect_encoding($raw, ['ASCII', 'UTF-8', 'SJIS-win', 'EUC-JP'], true);

    if ($detected === false) {
        $result['warnings'][] = 'エンコーディングを検出できません。UTF-8として処理を続行します';
        return $result;
    }

    $result['original_encoding'] = $detected;

    if ($detected === 'ASCII' || $detected === 'UTF-8') {
        // UTF-8の妥当性チェック
        if ($detected === 'UTF-8' && !mb_check_encoding($raw, 'UTF-8')) {
            $result['warnings'][] = '不正なUTF-8バイトシーケンスが含まれています';
        }
        return $result;
    }

    // Shift-JIS / EUC-JP → UTF-8 変換
    $converted = mb_convert_encoding($raw, 'UTF-8', $detected);
    if ($converted === false) {
        $result['warnings'][] = sprintf('%sからUTF-8への変換に失敗しました', $detected);
        return $result;
    }

    // 変換後ファイルを書き出し
    $converted_path = preg_replace('/\.csv$/i', '_utf8.csv', $file_path);
    if ($converted_path === $file_path) {
        $converted_path = $file_path . '_utf8.csv';
    }
    file_put_contents($converted_path, $converted);

    $result['path'] = $converted_path;
    $result['converted'] = true;
    $result['warnings'][] = sprintf('CSVを%sからUTF-8に自動変換しました', $detected);

    return $result;
}

// ============================================================
// 文字化け検知
// ============================================================

/**
 * テキスト値の文字化け・無効文字を検知する
 *
 * @param string $value テキスト値
 * @param string $field_name フィールド名
 * @return array 警告メッセージの配列
 */
function validate_encoding_text(string $value, string $field_name): array {
    $warnings = [];

    if (trim($value) === '') {
        return $warnings;
    }

    // 無効なUTF-8シーケンス
    if (!mb_check_encoding($value, 'UTF-8')) {
        $warnings[] = [
            'type' => 'encoding',
            'field' => $field_name,
            'message' => '無効なUTF-8文字が含まれています',
            'value' => $value,
        ];
    }

    // Unicode置換文字 U+FFFD
    if (preg_match('/\x{FFFD}/u', $value)) {
        $warnings[] = [
            'type' => 'encoding',
            'field' => $field_name,
            'message' => '文字化け（置換文字 U+FFFD）が含まれています',
            'value' => $value,
        ];
    }

    // 制御文字（\n, \r, \t 以外）
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
        $warnings[] = [
            'type' => 'encoding',
            'field' => $field_name,
            'message' => '不正な制御文字が含まれています',
            'value' => $value,
        ];
    }

    return $warnings;
}

// ============================================================
// 英語タイポ検知
// ============================================================

/**
 * フィールド名がタイポ検知対象かどうか判定する
 */
function is_typo_check_field(string $field_name): bool {
    foreach (TYPO_CHECK_FIELDS as $pattern) {
        if (mb_strpos($field_name, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * フィールド名が日付検知対象かどうか判定する
 */
function is_date_check_field(string $field_name): bool {
    foreach (DATE_CHECK_FIELDS as $pattern) {
        if (mb_strpos($field_name, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * フィールド名が時間検知対象かどうか判定する
 */
function is_time_check_field(string $field_name): bool {
    foreach (TIME_CHECK_FIELDS as $pattern) {
        if (mb_strpos($field_name, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * 英語テキストのタイポを検知する
 *
 * @param string $value テキスト値
 * @param string $field_name フィールド名
 * @return array 警告の配列
 */
function validate_english_text(string $value, string $field_name): array {
    $warnings = [];

    if (trim($value) === '') {
        return $warnings;
    }

    // タイポ検知対象フィールドでなければスキップ
    if (!is_typo_check_field($field_name)) {
        return $warnings;
    }

    // フレーズ辞書チェック（大文字小文字無視で部分一致）
    foreach (PHRASE_DICTIONARY as $phrase) {
        $phrase_lower = strtolower($phrase);
        $phrase_len = mb_strlen($phrase_lower);

        // 完全一致なら問題なし、スキップ
        if (stripos($value, $phrase) !== false) {
            continue;
        }

        // Levenshtein距離でフレーズ全体をチェック
        // 値がフレーズと同程度の長さの場合のみ
        $value_trimmed = trim($value);
        $value_trimmed_lower = strtolower($value_trimmed);
        $len_diff = abs(mb_strlen($value_trimmed_lower) - $phrase_len);

        if ($len_diff <= 3) {
            $distance = levenshtein($value_trimmed_lower, $phrase_lower);
            // フレーズ長に応じた閾値
            $threshold = ($phrase_len <= 10) ? 2 : 3;
            if ($distance > 0 && $distance <= $threshold) {
                $warnings[] = [
                    'type' => 'typo',
                    'field' => $field_name,
                    'message' => sprintf('"%s" → "%s" のタイポの可能性', $value_trimmed, $phrase),
                    'value' => $value,
                ];
                return $warnings; // フレーズマッチしたらそれ以上チェック不要
            }
        }
    }

    // 単語単位チェック：英単語のみ抽出（3文字以上）
    if (!preg_match_all('/[A-Za-z]{3,}/', $value, $matches)) {
        return $warnings;
    }

    foreach ($matches[0] as $word) {
        $word_lower = strtolower($word);
        $word_len = strlen($word_lower);

        // 辞書内に完全一致があればOK
        if (in_array($word_lower, WORD_DICTIONARY)) {
            continue;
        }

        // Levenshtein距離でチェック
        $best_match = null;
        $best_distance = PHP_INT_MAX;

        foreach (WORD_DICTIONARY as $dict_word) {
            // 長さが大きく違う単語はスキップ（高速化）
            if (abs(strlen($dict_word) - $word_len) > 2) {
                continue;
            }
            $dist = levenshtein($word_lower, $dict_word);
            if ($dist < $best_distance) {
                $best_distance = $dist;
                $best_match = $dict_word;
            }
        }

        // 閾値判定：短い単語は距離1のみ、長い単語は距離2まで
        $threshold = ($word_len >= 7) ? 2 : 1;
        if ($best_distance > 0 && $best_distance <= $threshold && $best_match !== null) {
            $warnings[] = [
                'type' => 'typo',
                'field' => $field_name,
                'message' => sprintf('"%s" → "%s" のタイポの可能性', $word, $best_match),
                'value' => $value,
            ];
        }
    }

    return $warnings;
}

// ============================================================
// 日付検証
// ============================================================

/**
 * 日付フィールドの形式を検証する
 *
 * @param string $value 日付値
 * @param string $field_name フィールド名
 * @return array 警告の配列
 */
function validate_date_field(string $value, string $field_name): array {
    $warnings = [];
    $value = trim($value);

    if ($value === '') {
        return $warnings;
    }

    // 日付フィールドでなければスキップ
    if (!is_date_check_field($field_name)) {
        return $warnings;
    }

    // 許容フォーマット: YYYY/MM/DD, YYYY.MM.DD, YYYY-MM-DD
    if (preg_match('/^(\d{4})[\/\.\-](\d{1,2})[\/\.\-](\d{1,2})$/', $value, $m)) {
        $year = (int)$m[1];
        $month = (int)$m[2];
        $day = (int)$m[3];

        if (!checkdate($month, $day, $year)) {
            $warnings[] = [
                'type' => 'date',
                'field' => $field_name,
                'message' => sprintf('無効な日付です: "%s" (月: 1-12, 日: 1-31)', $value),
                'value' => $value,
            ];
        } elseif ($year < 1900 || $year > (int)date('Y') + 2) {
            $warnings[] = [
                'type' => 'date',
                'field' => $field_name,
                'message' => sprintf('年が範囲外です: "%s" (1900-%d)', $value, (int)date('Y') + 2),
                'value' => $value,
            ];
        }
        return $warnings;
    }

    // 許容フォーマット: MM/DD, M/D
    if (preg_match('/^(\d{1,2})[\/\.\-](\d{1,2})$/', $value, $m)) {
        $month = (int)$m[1];
        $day = (int)$m[2];

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            $warnings[] = [
                'type' => 'date',
                'field' => $field_name,
                'message' => sprintf('無効な日付です: "%s" (月: 1-12, 日: 1-31)', $value),
                'value' => $value,
            ];
        }
        return $warnings;
    }

    // どのフォーマットにもマッチしない
    $warnings[] = [
        'type' => 'date',
        'field' => $field_name,
        'message' => sprintf('日付形式が不明です: "%s" (YYYY/MM/DD or MM/DD 形式を推奨)', $value),
        'value' => $value,
    ];

    return $warnings;
}

// ============================================================
// 時間検証
// ============================================================

/**
 * 時間フィールドの形式を検証する
 *
 * @param string $value 時間値
 * @param string $field_name フィールド名
 * @return array 警告の配列
 */
function validate_time_field(string $value, string $field_name): array {
    $warnings = [];
    $value = trim($value);

    if ($value === '') {
        return $warnings;
    }

    // 時間フィールドでなければスキップ
    if (!is_time_check_field($field_name)) {
        return $warnings;
    }

    // HH:MM or H:MM or HH:MM:SS
    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
        $first = (int)$m[1];
        $second = (int)$m[2];

        // フィールド名で MM:SS（音楽再生時間）か HH:MM（時刻）かを判定
        $is_music_time = (mb_strpos($field_name, 'テキスト2行目') !== false);

        if ($is_music_time) {
            // MM:SS形式: 分0-59, 秒0-59
            if ($first > 59) {
                $warnings[] = [
                    'type' => 'time',
                    'field' => $field_name,
                    'message' => sprintf('分の値が範囲外です: "%s" (分: 0-59)', $value),
                    'value' => $value,
                ];
            }
            if ($second > 59) {
                $warnings[] = [
                    'type' => 'time',
                    'field' => $field_name,
                    'message' => sprintf('秒の値が範囲外です: "%s" (秒: 0-59)', $value),
                    'value' => $value,
                ];
            }
        } else {
            // HH:MM形式: 時0-23, 分0-59
            if ($first > 23) {
                $warnings[] = [
                    'type' => 'time',
                    'field' => $field_name,
                    'message' => sprintf('時間の値が範囲外です: "%s" (時: 0-23)', $value),
                    'value' => $value,
                ];
            }
            if ($second > 59) {
                $warnings[] = [
                    'type' => 'time',
                    'field' => $field_name,
                    'message' => sprintf('分の値が範囲外です: "%s" (分: 0-59)', $value),
                    'value' => $value,
                ];
            }
        }
        return $warnings;
    }

    // どのフォーマットにもマッチしない
    $warnings[] = [
        'type' => 'time',
        'field' => $field_name,
        'message' => sprintf('時間形式が不明です: "%s" (HH:MM 形式を推奨)', $value),
        'value' => $value,
    ];

    return $warnings;
}

// ============================================================
// 注文単位の統合検証
// ============================================================

/**
 * 1注文のテキストフィールドを全て検証する
 *
 * @param array $row CSV行データ（連想配列）
 * @param array $template_keys テンプレートキーの配列
 * @return array ['order_name' => string, 'warnings' => array]
 */
function validate_order_text(array $row, array $template_keys): array {
    $order_name = $row['Name'] ?? 'unknown';
    $all_warnings = [];
    $checked = []; // 重複チェック防止用（同じフィールド・同じ値は1回だけ）

    foreach ($template_keys as $key) {
        $template = get_template($key);
        foreach ($template as $field) {
            $type = $field['type'] ?? '';
            $name = $field['name'] ?? '';

            // テキスト・マルチテキストフィールドのみ
            if ($type === 'text' || $type === 'multitext') {
                // CSVから値を取得（set_template と同じロジック）
                $value = isset($row[$name]) ? $row[$name] : ($field['value'] ?? '');

                // 重複チェック
                $check_key = $name . '::' . $value;
                if (isset($checked[$check_key])) {
                    continue;
                }
                $checked[$check_key] = true;

                // デフォルト値と同じならスキップ（顧客入力でない）
                if ($value === ($field['value'] ?? '')) {
                    continue;
                }

                // 1. 文字化けチェック
                $all_warnings = array_merge($all_warnings, validate_encoding_text($value, $name));

                // 2. 英語タイポチェック
                $all_warnings = array_merge($all_warnings, validate_english_text($value, $name));

                // 3. 日付チェック
                $all_warnings = array_merge($all_warnings, validate_date_field($value, $name));

                // 4. 時間チェック
                $all_warnings = array_merge($all_warnings, validate_time_field($value, $name));
            }

            // カレンダーフィールドの日付チェック
            if ($type === 'calendar') {
                $date_value = isset($row[$name]) ? $row[$name] : ($field['date'] ?? '');
                $check_key = $name . '::' . $date_value;
                if (isset($checked[$check_key])) {
                    continue;
                }
                $checked[$check_key] = true;
                if ($date_value !== ($field['date'] ?? '')) {
                    $all_warnings = array_merge($all_warnings, validate_date_field($date_value, $name));
                }
            }
        }
    }

    return [
        'order_name' => $order_name,
        'warnings' => $all_warnings,
    ];
}

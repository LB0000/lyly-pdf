<?php
/**
 * LYLY PDF Generator API
 *
 * アクション:
 * - generate: CSVからPDF生成
 * - download: PDFファイルダウンロード
 * - zip: 一括ZIPダウンロード
 * - list: 生成済みPDF一覧
 */

// メモリと実行時間の制限を緩和（PDF生成は大量のメモリを使用）
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600);
date_default_timezone_set('Asia/Tokyo');

// PHPエラーをJSON形式で返すようにする
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);
set_error_handler(function($severity, $message, $file, $line) {
    // 非推奨警告は無視（TCPDFライブラリの互換性のため）
    if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// CORS対応（許可オリジンはconfig.phpで設定）
require_once('./config.php');
require_once('./include/database.php');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, CORS_ALLOWED_ORIGINS, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// OPTIONSリクエスト（プリフライト）への対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// エラーをJSON形式で返す
function jsonError($message, $code = 400) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// 成功レスポンス
function jsonSuccess($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 認証ミドルウェア ---
// AUTH_PASSWORD 環境変数が設定されている場合のみ認証を要求
// 未設定時は認証なし（後方互換）
function getAuthToken($password) {
    return hash_hmac('sha256', 'lyly-auth-token', $password);
}

function checkAuth() {
    $password = getenv('AUTH_PASSWORD');
    if (!$password) return true; // 認証未設定 → 全許可

    $expectedToken = getAuthToken($password);

    // Authorizationヘッダーで認証
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return hash_equals($expectedToken, $matches[1]);
    }

    // URLクエリパラメータで認証（download/preview等のリンク用）
    $queryToken = $_GET['_token'] ?? '';
    if ($queryToken !== '' && hash_equals($expectedToken, $queryToken)) {
        return true;
    }

    return false;
}

function requireAuth() {
    if (!checkAuth()) {
        jsonError('認証が必要です', 401);
    }
}

$action = $_GET['action'] ?? '';

// 認証不要のアクション
$publicActions = ['login', 'check_auth'];

// 認証チェック（login/check_auth以外）
if (!in_array($action, $publicActions, true)) {
    requireAuth();
}

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'check_auth':
        handleCheckAuth();
        break;
    case 'generate':
        handleGenerate();
        break;
    case 'generate_stream':
        handleGenerateStream();
        break;
    case 'download':
        handleDownload();
        break;
    case 'zip':
        handleZip();
        break;
    case 'list':
        handleList();
        break;
    case 'history':
        handleHistory();
        break;
    case 'history_detail':
        handleHistoryDetail();
        break;
    case 'preview':
        handlePreview();
        break;
    default:
        jsonError('不明なアクション');
}

/**
 * ログイン処理
 */
function handleLogin() {
    $password = getenv('AUTH_PASSWORD');
    if (!$password) {
        // 認証未設定 → 常に成功
        jsonSuccess(['token' => null, 'authRequired' => false]);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $inputPassword = $input['password'] ?? '';

    if (!hash_equals($password, $inputPassword)) {
        jsonError('パスワードが正しくありません', 401);
    }

    jsonSuccess([
        'token' => getAuthToken($password),
        'authRequired' => true
    ]);
}

/**
 * 認証状態確認
 */
function handleCheckAuth() {
    $password = getenv('AUTH_PASSWORD');
    jsonSuccess([
        'authRequired' => (bool)$password,
        'authenticated' => checkAuth()
    ]);
}

/**
 * PDF生成処理
 */
function handleGenerate() {
    // CSVファイルのチェック
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        jsonError('CSVファイルがアップロードされていません');
    }

    // ファイルサイズチェック（最大10MB）
    $maxSize = 10 * 1024 * 1024;
    if ($_FILES['csv']['size'] > $maxSize) {
        jsonError('ファイルサイズが大きすぎます（最大10MB）');
    }

    // ファイル拡張子チェック
    $ext = strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        jsonError('CSVファイルを選択してください');
    }

    $processType = $_POST['process'] ?? 'all';
    if (!in_array($processType, ['all', 'temp', 'draft'])) {
        jsonError('無効な処理タイプです');
    }

    // 一時ファイルを保存
    $uploadDir = './uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // ファイル名に uniqid を追加して競合を防ぐ
    $csvFile = $uploadDir . 'upload_' . date('YmdHis') . '_' . uniqid() . '.csv';
    if (!move_uploaded_file($_FILES['csv']['tmp_name'], $csvFile)) {
        jsonError('CSVファイルの保存に失敗しました');
    }

    // downloadモード: ガイドラインオーバーレイを無効化（高速化）
    $mode = $_POST['mode'] ?? 'normal';

    // 処理実行
    try {
        require_once('./include/common.php');

        // downloadモードの場合、ガイドラインオーバーレイを無効化
        global $GUIDELINE_OVERLAY_DISABLE;
        $GUIDELINE_OVERLAY_DISABLE = ($mode === 'download');

        // 処理開始時刻を記録（今回生成されたファイルのみを取得するため）
        $startTime = time();

        // 出力フォルダを作成
        global $OUTPUT_BASE_FOLDER;
        $OUTPUT_BASE_FOLDER = createOutputFolder();

        $logs = [];
        $files = [];
        $skipped_logs = [];
        $zipFilePath = null;

        // CSV読み込み
        $csv = csv_to_array($csvFile, $skipped_logs);

        // 除外されたデータをログに追加
        foreach ($skipped_logs as $skip) {
            $logs[] = [
                'message' => sprintf('行%d: %s - スキップ (%s)', $skip['line'], $skip['name'] ?: '(空)', $skip['reason']),
                'type' => 'skipped'
            ];
        }

        if (count($csv) === 0) {
            $logs[] = ['message' => '有効な注文情報がありませんでした', 'type' => 'error'];
            jsonSuccess(['logs' => $logs, 'files' => []]);
        }

        $logs[] = ['message' => '注文件数: ' . count($csv) . '件 (除外: ' . count($skipped_logs) . '件)', 'type' => 'info'];

        // 画像を事前に一括並列ダウンロード
        batch_download_images($csv);
        $logs[] = ['message' => '画像ダウンロード完了', 'type' => 'info'];

        // 個別PDF生成
        $successCount = 0;
        $failedOrders = [];
        if ($processType == 'all' || $processType == 'temp') {
            foreach ($csv as $v) {
                $name = $v['Name'] ?? 'unknown';
                $keys = get_template_names($v);
                if ($keys) {
                    $hasError = false;
                    $errorDetails = [];
                    foreach ($keys as $key) {
                        try {
                            create_pdf_one($v, $key);
                        } catch (Exception $e) {
                            $hasError = true;
                            $errorDetails[] = $key . ': ' . $e->getMessage();
                            error_log("個別PDF作成エラー [{$name}][{$key}]: " . $e->getMessage());
                        }
                    }

                    if ($hasError) {
                        $failedOrders[] = [
                            'name' => $name,
                            'details' => implode(', ', $errorDetails)
                        ];
                        $logs[] = [
                            'message' => $name . '...一部失敗: ' . implode(', ', $errorDetails),
                            'type' => 'error'
                        ];
                    } else {
                        $successCount++;
                        $logs[] = ['message' => $name . '...作成完了', 'type' => 'success'];
                    }
                } else {
                    $failedOrders[] = [
                        'name' => $name,
                        'details' => 'テンプレートなし'
                    ];
                    $logs[] = ['message' => $name . '...テンプレートなし', 'type' => 'error'];
                }
            }
        }

        // 印刷用PDF生成
        $draftFileCount = 0;
        global $SKIPPED_PDFS;
        $SKIPPED_PDFS = [];
        if ($processType == 'all' || $processType == 'draft') {
            $draft_list = draft_list($csv);
            $draftFileCount = 0;
            foreach ($draft_list as $k => $v) {
                create_pdf($k, $v);
                $draftFileCount++;
            }
            $logs[] = ['message' => '印刷データ...作成完了', 'type' => 'success'];

            // スキップされた個別PDFを表示
            if (count($SKIPPED_PDFS) > 0) {
                $logs[] = [
                    'message' => '⚠️ 以下の個別PDFが見つからずスキップされました:',
                    'type' => 'warning'
                ];
                foreach ($SKIPPED_PDFS as $skipped) {
                    $logs[] = [
                        'message' => '  • ' . $skipped,
                        'type' => 'error'
                    ];
                }
            }
        }

        // サマリー情報を表示
        $totalOrders = count($csv);
        $failedCount = count($failedOrders);
        $summaryMessage = sprintf(
            "処理完了: 注文%d件 / 成功%d件 / 失敗%d件 / 印刷用PDF%dファイル",
            $totalOrders,
            $successCount,
            $failedCount,
            $draftFileCount
        );
        $logs[] = ['message' => $summaryMessage, 'type' => 'info'];

        // 失敗した注文の詳細を表示
        if (count($failedOrders) > 0) {
            $logs[] = ['message' => '⚠️ 以下の注文は生成に失敗しました:', 'type' => 'warning'];
            foreach ($failedOrders as $failed) {
                $logs[] = [
                    'message' => '  • ' . $failed['name'] . ': ' . $failed['details'],
                    'type' => 'error'
                ];
            }
        }

        // ZIPファイルを作成
        try {
            $zipFilePath = createZipArchive($OUTPUT_BASE_FOLDER);
            $logs[] = ['message' => 'ZIPファイル作成完了: ' . basename($zipFilePath), 'type' => 'success'];
        } catch (Exception $e) {
            // 詳細なエラーログ
            error_log('ZIP作成エラー詳細: ' . $e->getMessage());
            error_log('フォルダパス: ' . $OUTPUT_BASE_FOLDER);
            error_log('フォルダ存在確認: ' . (is_dir($OUTPUT_BASE_FOLDER) ? 'yes' : 'no'));

            $logs[] = ['message' => 'ZIP作成エラー: ' . $e->getMessage(), 'type' => 'error'];

            // ZIP作成失敗でも処理は継続（個別PDFはダウンロード可能）
            $zipFilePath = null;
        }

        // 生成されたファイル一覧を取得（今回の処理で生成されたもののみ）
        $files = getGeneratedFiles($processType, $startTime);

        // SQLiteに生成履歴を保存
        try {
            saveGeneration([
                'timestamp'     => basename(rtrim($OUTPUT_BASE_FOLDER, '/')),
                'csv_filename'  => $_FILES['csv']['name'] ?? null,
                'process_type'  => $processType,
                'mode'          => $mode,
                'order_count'   => count($csv),
                'success_count' => $successCount,
                'failed_count'  => count($failedOrders),
                'draft_count'   => $draftFileCount,
                'temp_count'    => count(array_filter($files, fn($f) => $f['type'] === 'temp')),
                'total_files'   => count($files),
                'zip_file'      => $zipFilePath ? basename($zipFilePath) : null,
                'zip_size'      => $zipFilePath && file_exists($zipFilePath) ? filesize($zipFilePath) : 0,
                'folder_path'   => $OUTPUT_BASE_FOLDER,
            ]);
        } catch (Exception $dbErr) {
            error_log('DB保存エラー: ' . $dbErr->getMessage());
        }

        // 一時CSVを削除
        unlink($csvFile);

        jsonSuccess([
            'logs' => $logs,
            'files' => $files,
            'zipFile' => $zipFilePath ? basename($zipFilePath) : null,
            'zipFilePath' => $zipFilePath,
            'outputFolder' => $OUTPUT_BASE_FOLDER
        ]);

    } catch (Exception $e) {
        // 例外時もアップロードCSVを削除（残留防止）
        if (isset($csvFile) && file_exists($csvFile)) {
            @unlink($csvFile);
        }
        jsonError('PDF生成エラー: ' . $e->getMessage());
    }
}

/**
 * SSEイベント送信
 */
function sse_emit($type, $message, $extra = []) {
    $data = array_merge(['type' => $type, 'message' => $message], $extra);
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

/**
 * PDF生成処理（SSEストリーミング版）
 */
function handleGenerateStream() {
    // CSVファイルのチェック
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: text/event-stream');
        sse_emit('error', 'CSVファイルがアップロードされていません');
        sse_emit('done', 'エラーで終了', ['files' => [], 'zipFile' => null]);
        return;
    }
    if ($_FILES['csv']['size'] > 10 * 1024 * 1024) {
        header('Content-Type: text/event-stream');
        sse_emit('error', 'ファイルサイズが大きすぎます（最大10MB）');
        sse_emit('done', 'エラーで終了', ['files' => [], 'zipFile' => null]);
        return;
    }
    $ext = strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        header('Content-Type: text/event-stream');
        sse_emit('error', 'CSVファイルを選択してください');
        sse_emit('done', 'エラーで終了', ['files' => [], 'zipFile' => null]);
        return;
    }

    $processType = $_POST['process'] ?? 'all';
    if (!in_array($processType, ['all', 'temp', 'draft'])) {
        header('Content-Type: text/event-stream');
        sse_emit('error', '無効な処理タイプです');
        sse_emit('done', 'エラーで終了', ['files' => [], 'zipFile' => null]);
        return;
    }
    $mode = $_POST['mode'] ?? 'normal';

    // SSEヘッダー設定
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    // 出力バッファリング無効化
    while (ob_get_level()) ob_end_flush();
    ob_implicit_flush(true);

    // 一時ファイルを保存
    $uploadDir = './uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $csvFile = $uploadDir . 'upload_' . date('YmdHis') . '_' . uniqid() . '.csv';
    if (!move_uploaded_file($_FILES['csv']['tmp_name'], $csvFile)) {
        sse_emit('error', 'CSVファイルの保存に失敗しました');
        return;
    }

    try {
        require_once('./include/common.php');

        global $GUIDELINE_OVERLAY_DISABLE;
        $GUIDELINE_OVERLAY_DISABLE = ($mode === 'download');

        $startTime = time();

        global $OUTPUT_BASE_FOLDER;
        $OUTPUT_BASE_FOLDER = createOutputFolder();

        $skipped_logs = [];
        $csv = csv_to_array($csvFile, $skipped_logs);

        // 除外データ
        foreach ($skipped_logs as $skip) {
            sse_emit('skipped', sprintf('行%d: %s - スキップ (%s)', $skip['line'], $skip['name'] ?: '(空)', $skip['reason']));
        }

        if (count($csv) === 0) {
            sse_emit('error', '有効な注文情報がありませんでした');
            sse_emit('done', '処理完了', ['files' => [], 'zipFile' => null, 'outputFolder' => $OUTPUT_BASE_FOLDER]);
            @unlink($csvFile);
            return;
        }

        sse_emit('info', '注文件数: ' . count($csv) . '件 (除外: ' . count($skipped_logs) . '件)');

        // 画像一括ダウンロード
        sse_emit('info', '画像ダウンロード中...');
        batch_download_images($csv);
        sse_emit('info', '画像ダウンロード完了');

        // 個別PDF生成
        $successCount = 0;
        $failedOrders = [];
        $totalOrders = count($csv);

        if ($processType == 'all' || $processType == 'temp') {
            $orderIndex = 0;
            foreach ($csv as $v) {
                $orderIndex++;
                $name = $v['Name'] ?? 'unknown';
                $keys = get_template_names($v);
                if ($keys) {
                    $hasError = false;
                    $errorDetails = [];
                    foreach ($keys as $key) {
                        try {
                            create_pdf_one($v, $key);
                        } catch (Exception $e) {
                            $hasError = true;
                            $errorDetails[] = $key . ': ' . $e->getMessage();
                        }
                    }
                    if ($hasError) {
                        $failedOrders[] = $name;
                        sse_emit('error', $name . '...一部失敗: ' . implode(', ', $errorDetails));
                    } else {
                        $successCount++;
                        sse_emit('success', $name . '...作成完了', ['progress' => round($orderIndex / $totalOrders * 100)]);
                    }
                } else {
                    $failedOrders[] = $name;
                    sse_emit('error', $name . '...テンプレートなし');
                }
            }
        }

        // 印刷用PDF生成
        $draftFileCount = 0;
        global $SKIPPED_PDFS;
        $SKIPPED_PDFS = [];
        if ($processType == 'all' || $processType == 'draft') {
            sse_emit('info', '印刷用PDF作成中...');
            $draft_list = draft_list($csv);
            foreach ($draft_list as $k => $v) {
                create_pdf($k, $v);
                $draftFileCount++;
            }
            sse_emit('success', '印刷データ...作成完了');

            if (count($SKIPPED_PDFS) > 0) {
                foreach ($SKIPPED_PDFS as $skipped) {
                    sse_emit('warning', 'スキップ: ' . $skipped);
                }
            }
        }

        // サマリー
        sse_emit('info', sprintf("処理完了: 注文%d件 / 成功%d件 / 失敗%d件 / 印刷用PDF%dファイル",
            $totalOrders, $successCount, count($failedOrders), $draftFileCount));

        // ZIP作成
        $zipFilePath = null;
        try {
            sse_emit('info', 'ZIPファイル作成中...');
            $zipFilePath = createZipArchive($OUTPUT_BASE_FOLDER);
            sse_emit('success', 'ZIPファイル作成完了');
        } catch (Exception $e) {
            sse_emit('error', 'ZIP作成エラー: ' . $e->getMessage());
        }

        // ファイル一覧取得
        $files = getGeneratedFiles($processType, $startTime);

        // SQLiteに生成履歴を保存
        try {
            saveGeneration([
                'timestamp'     => basename(rtrim($OUTPUT_BASE_FOLDER, '/')),
                'csv_filename'  => $_FILES['csv']['name'] ?? null,
                'process_type'  => $processType,
                'mode'          => $mode,
                'order_count'   => $totalOrders,
                'success_count' => $successCount,
                'failed_count'  => count($failedOrders),
                'draft_count'   => $draftFileCount,
                'temp_count'    => count(array_filter($files, fn($f) => $f['type'] === 'temp')),
                'total_files'   => count($files),
                'zip_file'      => $zipFilePath ? basename($zipFilePath) : null,
                'zip_size'      => $zipFilePath && file_exists($zipFilePath) ? filesize($zipFilePath) : 0,
                'folder_path'   => $OUTPUT_BASE_FOLDER,
            ]);
        } catch (Exception $dbErr) {
            sse_emit('warning', 'DB保存エラー: ' . $dbErr->getMessage());
        }

        // 完了イベント
        sse_emit('done', '全処理完了', [
            'files' => $files,
            'zipFile' => $zipFilePath ? basename($zipFilePath) : null,
            'zipFilePath' => $zipFilePath,
            'outputFolder' => $OUTPUT_BASE_FOLDER
        ]);

    } catch (Exception $e) {
        sse_emit('error', 'PDF生成エラー: ' . $e->getMessage());
        sse_emit('done', 'エラーで終了', ['files' => [], 'zipFile' => null]);
    }

    // クリーンアップ
    if (isset($csvFile) && file_exists($csvFile)) {
        @unlink($csvFile);
    }
}

/**
 * 生成されたPDFファイル一覧を取得
 * @param string $type 取得するファイルタイプ (all, temp, draft)
 * @param int|null $sinceTime この時刻以降に生成されたファイルのみを取得（nullの場合は全件）
 */
function getGeneratedFiles($type, $sinceTime = null) {
    $files = [];
    global $OUTPUT_BASE_FOLDER;

    // OUTPUT_BASE_FOLDERが設定されている場合はそこを優先
    $tempDir = isset($OUTPUT_BASE_FOLDER) ? $OUTPUT_BASE_FOLDER . 'temp/' : './temp/';
    $draftDir = isset($OUTPUT_BASE_FOLDER) ? $OUTPUT_BASE_FOLDER . 'draft/' : './draft/';

    // temp/フォルダ
    if ($type == 'all' || $type == 'temp') {
        if (is_dir($tempDir)) {
            foreach (glob($tempDir . '*.pdf') as $file) {
                $mtime = filemtime($file);
                // sinceTimeが指定されている場合、それ以降のファイルのみを取得
                if ($sinceTime !== null && $mtime < $sinceTime) {
                    continue;
                }
                $files[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'type' => 'temp',
                    'size' => filesize($file),
                    'mtime' => $mtime
                ];
            }
        }
    }

    // draft/フォルダ
    if ($type == 'all' || $type == 'draft') {
        if (is_dir($draftDir)) {
            foreach (glob($draftDir . '*.pdf') as $file) {
                $mtime = filemtime($file);
                // sinceTimeが指定されている場合、それ以降のファイルのみを取得
                if ($sinceTime !== null && $mtime < $sinceTime) {
                    continue;
                }
                $files[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'type' => 'draft',
                    'size' => filesize($file),
                    'mtime' => $mtime
                ];
            }
        }
    }

    // 最新順にソート
    usort($files, function($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });

    return $files;
}

/**
 * PDFファイルダウンロード
 */
function handleDownload() {
    $file = $_GET['file'] ?? '';

    if (empty($file)) {
        jsonError('ファイルが指定されていません');
    }

    // パストラバーサル対策
    $file = realpath($file);
    $baseTemp = realpath('./temp/');
    $baseDraft = realpath('./draft/');
    $baseOutput = realpath('./output/');

    if ($file === false) {
        jsonError('ファイルが見つかりません', 404);
    }

    // temp/, draft/, output/ 配下のファイルのみ許可
    $normalizedFile = rtrim($file, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $allowed = false;
    if ($baseTemp !== false) {
        $baseTemp = rtrim($baseTemp, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($normalizedFile, $baseTemp) === 0) {
            $allowed = true;
        }
    }
    if ($baseDraft !== false) {
        $baseDraft = rtrim($baseDraft, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($normalizedFile, $baseDraft) === 0) {
            $allowed = true;
        }
    }
    if ($baseOutput !== false) {
        $baseOutput = rtrim($baseOutput, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($normalizedFile, $baseOutput) === 0) {
            $allowed = true;
        }
    }
    if (!$allowed) {
        jsonError('アクセスが許可されていません', 403);
    }

    if (!file_exists($file)) {
        jsonError('ファイルが見つかりません', 404);
    }

    // ダウンロード（ファイル種別に応じたMIMEタイプ）
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeTypes = ['pdf' => 'application/pdf', 'zip' => 'application/zip'];
    $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $contentType);
    $filename = preg_replace('/["\r\n]/', '', basename($file));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

/**
 * ZIP一括ダウンロード
 */
function handleZip() {
    $type = $_GET['type'] ?? 'all';
    if (!in_array($type, ['all', 'temp', 'draft'])) {
        $type = 'all';
    }

    $files = getGeneratedFiles($type);

    if (empty($files)) {
        jsonError('ダウンロードするファイルがありません');
    }

    $zipFile = './temp/download_' . date('YmdHis') . '_' . uniqid() . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
        jsonError('ZIPファイルの作成に失敗しました');
    }

    foreach ($files as $file) {
        $zip->addFile($file['path'], $file['type'] . '/' . $file['name']);
    }

    $zip->close();

    // ダウンロード
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="lyly_pdf_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);

    // 一時ZIPを削除
    unlink($zipFile);
    exit;
}

/**
 * PDF一覧取得
 */
function handleList() {
    $type = $_GET['type'] ?? 'all';
    if (!in_array($type, ['all', 'temp', 'draft'])) {
        $type = 'all';
    }
    $files = getGeneratedFiles($type);
    jsonSuccess(['files' => $files]);
}

/**
 * 過去の生成履歴を取得（SQLite対応、ページネーション）
 */
function handleHistory() {
    // 初回起動時: 既存のファイルシステム履歴をDBにインポート
    importExistingHistory();

    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
    $offset = isset($_GET['offset']) ? max((int)$_GET['offset'], 0) : 0;

    $rows = getGenerationHistory($limit, $offset);
    $total = getGenerationCount();

    $histories = [];
    foreach ($rows as $row) {
        $datetime = strtotime($row['created_at']);
        $histories[] = [
            'timestamp'         => $row['timestamp'],
            'datetime'          => $datetime,
            'datetimeFormatted' => date('Y年m月d日 H:i', $datetime),
            'csvFilename'       => $row['csv_filename'],
            'processType'       => $row['process_type'],
            'orderCount'        => (int)$row['order_count'],
            'successCount'      => (int)$row['success_count'],
            'failedCount'       => (int)$row['failed_count'],
            'draftCount'        => (int)$row['draft_count'],
            'tempCount'         => (int)$row['temp_count'],
            'totalFiles'        => (int)$row['total_files'],
            'zipFile'           => $row['zip_file'],
            'zipSize'           => (int)$row['zip_size'],
            'folderPath'        => $row['folder_path'],
        ];
    }

    jsonSuccess([
        'histories' => $histories,
        'total'     => $total,
        'limit'     => $limit,
        'offset'    => $offset,
    ]);
}

/**
 * 特定の履歴の詳細（ファイル一覧）を取得
 */
function handleHistoryDetail() {
    $timestamp = $_GET['timestamp'] ?? '';

    if (empty($timestamp)) {
        jsonError('タイムスタンプが指定されていません');
    }

    // パストラバーサル対策
    $timestamp = basename($timestamp);
    $folderPath = './output/' . $timestamp . '/';

    if (!is_dir($folderPath)) {
        jsonError('フォルダが見つかりません', 404);
    }

    $files = [];

    // draft/フォルダのファイルを取得
    $draftFiles = glob($folderPath . 'draft/*.pdf');
    if ($draftFiles) {
        foreach ($draftFiles as $file) {
            $files[] = [
                'name' => basename($file),
                'path' => $file,
                'type' => 'draft',
                'size' => filesize($file),
                'mtime' => filemtime($file)
            ];
        }
    }

    // temp/フォルダのファイルを取得
    $tempFiles = glob($folderPath . 'temp/*.pdf');
    if ($tempFiles) {
        foreach ($tempFiles as $file) {
            $files[] = [
                'name' => basename($file),
                'path' => $file,
                'type' => 'temp',
                'size' => filesize($file),
                'mtime' => filemtime($file)
            ];
        }
    }

    // 最新順にソート
    usort($files, function($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });

    jsonSuccess([
        'timestamp' => $timestamp,
        'files' => $files,
        'folderPath' => $folderPath
    ]);
}

/**
 * PDFプレビュー（ブラウザ内表示用）
 */
function handlePreview() {
    $file = $_GET['file'] ?? '';

    if (empty($file)) {
        jsonError('ファイルが指定されていません');
    }

    // パストラバーサル対策
    $file = realpath($file);
    $baseTemp = realpath('./temp/');
    $baseDraft = realpath('./draft/');
    $baseOutput = realpath('./output/');

    if ($file === false) {
        jsonError('ファイルが見つかりません', 404);
    }

    // temp/, draft/, output/ 配下のファイルのみ許可（ディレクトリ区切り文字付きでプレフィックスバイパスを防止）
    $allowed = false;
    if ($baseTemp !== false) {
        $baseTempSep = rtrim($baseTemp, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($file, $baseTempSep) === 0) {
            $allowed = true;
        }
    }
    if ($baseDraft !== false) {
        $baseDraftSep = rtrim($baseDraft, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($file, $baseDraftSep) === 0) {
            $allowed = true;
        }
    }
    if ($baseOutput !== false) {
        $baseOutputSep = rtrim($baseOutput, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($file, $baseOutputSep) === 0) {
            $allowed = true;
        }
    }

    if (!$allowed) {
        jsonError('アクセスが許可されていません', 403);
    }

    if (!file_exists($file)) {
        jsonError('ファイルが見つかりません', 404);
    }

    // PDFファイルかどうか確認
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        jsonError('PDFファイルではありません', 400);
    }

    // _gl版（ガイドライン付き）が存在すればプレビュー用に優先
    $glFile = preg_replace('/\.pdf$/i', '_gl.pdf', $file);
    if ($glFile !== $file && file_exists($glFile)) {
        $file = $glFile;
    }

    // キャッシュ検証用ヘッダー
    $mtime = filemtime($file);
    $etag = '"' . md5($file . $mtime) . '"';

    // If-None-Match チェック（304 Not Modified）
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        http_response_code(304);
        exit;
    }

    // インライン表示（ブラウザ内でプレビュー）
    $filename = preg_replace('/["\r\n]/', '', basename($file));
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: private, max-age=3600');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    readfile($file);
    exit;
}

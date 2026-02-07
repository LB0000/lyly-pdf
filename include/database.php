<?php
/**
 * SQLite Database Helper for LYLY PDF Generator
 *
 * 生成履歴の永続化を担当。
 * OUTPUT_DIR/lyly.db にSQLiteデータベースを保持する。
 */

/**
 * PDOインスタンスを取得（シングルトン）
 */
function getDatabase() {
    static $db = null;
    if ($db !== null) return $db;

    $dbDir = OUTPUT_DIR;
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    $dbPath = $dbDir . 'lyly.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // WALモード（並列読み書き性能向上）
    $db->exec("PRAGMA journal_mode = WAL");

    // 自動マイグレーション
    migrateDatabase($db);

    return $db;
}

/**
 * テーブル作成（自動マイグレーション）
 */
function migrateDatabase($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS generations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp TEXT NOT NULL UNIQUE,
        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
        csv_filename TEXT,
        process_type TEXT DEFAULT 'all',
        mode TEXT DEFAULT 'download',
        order_count INTEGER DEFAULT 0,
        success_count INTEGER DEFAULT 0,
        failed_count INTEGER DEFAULT 0,
        draft_count INTEGER DEFAULT 0,
        temp_count INTEGER DEFAULT 0,
        total_files INTEGER DEFAULT 0,
        zip_file TEXT,
        zip_size INTEGER DEFAULT 0,
        folder_path TEXT NOT NULL
    )");
}

/**
 * 生成履歴を保存
 */
function saveGeneration($data) {
    $db = getDatabase();
    $stmt = $db->prepare("INSERT OR REPLACE INTO generations
        (timestamp, csv_filename, process_type, mode, order_count, success_count, failed_count,
         draft_count, temp_count, total_files, zip_file, zip_size, folder_path)
        VALUES
        (:timestamp, :csv_filename, :process_type, :mode, :order_count, :success_count, :failed_count,
         :draft_count, :temp_count, :total_files, :zip_file, :zip_size, :folder_path)");

    $stmt->execute([
        ':timestamp'     => $data['timestamp'],
        ':csv_filename'  => $data['csv_filename'] ?? null,
        ':process_type'  => $data['process_type'] ?? 'all',
        ':mode'          => $data['mode'] ?? 'download',
        ':order_count'   => $data['order_count'] ?? 0,
        ':success_count' => $data['success_count'] ?? 0,
        ':failed_count'  => $data['failed_count'] ?? 0,
        ':draft_count'   => $data['draft_count'] ?? 0,
        ':temp_count'    => $data['temp_count'] ?? 0,
        ':total_files'   => $data['total_files'] ?? 0,
        ':zip_file'      => $data['zip_file'] ?? null,
        ':zip_size'      => $data['zip_size'] ?? 0,
        ':folder_path'   => $data['folder_path'],
    ]);

    return $db->lastInsertId();
}

/**
 * 生成履歴を取得（ページネーション対応）
 */
function getGenerationHistory($limit = 50, $offset = 0) {
    $db = getDatabase();
    $stmt = $db->prepare("SELECT * FROM generations ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * 生成履歴の総件数を取得
 */
function getGenerationCount() {
    $db = getDatabase();
    $stmt = $db->query("SELECT COUNT(*) FROM generations");
    return (int)$stmt->fetchColumn();
}

/**
 * 既存のファイルシステム履歴をDBにインポート（初回マイグレーション用）
 */
function importExistingHistory() {
    $db = getDatabase();

    // 既にデータがある場合はスキップ
    $count = getGenerationCount();
    if ($count > 0) return;

    $outputDir = OUTPUT_DIR;
    if (!is_dir($outputDir)) return;

    $folders = glob($outputDir . '*', GLOB_ONLYDIR);
    if (!$folders) return;

    $stmt = $db->prepare("INSERT OR IGNORE INTO generations
        (timestamp, created_at, draft_count, temp_count, total_files, zip_file, zip_size, folder_path)
        VALUES (:timestamp, :created_at, :draft_count, :temp_count, :total_files, :zip_file, :zip_size, :folder_path)");

    foreach ($folders as $folder) {
        $timestamp = basename($folder);
        $zipFile = $folder . '.zip';

        $draftFiles = glob($folder . '/draft/*.pdf');
        $draftCount = is_array($draftFiles) ? count($draftFiles) : 0;

        $tempFiles = glob($folder . '/temp/*.pdf');
        $tempCount = is_array($tempFiles) ? count($tempFiles) : 0;

        $datetime = filemtime($folder);

        $stmt->execute([
            ':timestamp'   => $timestamp,
            ':created_at'  => date('Y-m-d H:i:s', $datetime),
            ':draft_count' => $draftCount,
            ':temp_count'  => $tempCount,
            ':total_files' => $draftCount + $tempCount,
            ':zip_file'    => file_exists($zipFile) ? basename($zipFile) : null,
            ':zip_size'    => file_exists($zipFile) ? filesize($zipFile) : 0,
            ':folder_path' => $folder . '/',
        ]);
    }
}

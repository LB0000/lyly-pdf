<?php

	require_once('./include/common.php');

	if(isset($argv[1])){
		$skipped_logs = [];
		$csv = csv_to_array($argv[1], $skipped_logs);

		// 除外されたデータを表示
		foreach ($skipped_logs as $skip) {
			printf("行%d: %s - スキップ (%s)\n", $skip['line'], $skip['name'] ?: '(空)', $skip['reason']);
		}
		printf("注文件数: %d件 (除外: %d件)\n", count($csv), count($skipped_logs));

		// 完了済み注文のフィルタリング
		if (END_ORDER) {
			$before_count = count($csv);
			$csv = end_del($csv);
			$skipped_end = $before_count - count($csv);
			if ($skipped_end > 0) {
				printf("処理済みスキップ: %d件\n", $skipped_end);
			}
			printf("実処理対象: %d件\n", count($csv));
		}
	} else {
		print "CSVファイルを指定してください";
		exit;
	}
	if(isset($argv[2])){
		$process = $argv[2];
	} else {
		print "処理タイプ(all, temp, draft)を指定してください";
		exit;
	}

	// ドライランモード: CSV検証のみ実行
	if ($process == "dry") {
		dry_run($csv);
		exit;
	}

	// downloadモード: ガイドラインオーバーレイを無効化
	global $GUIDELINE_OVERLAY_DISABLE;
	$GUIDELINE_OVERLAY_DISABLE = false;
	if(isset($argv[3]) && $argv[3] === 'download'){
		$GUIDELINE_OVERLAY_DISABLE = true;
		print "ダウンロードモード: ガイドラインオーバーレイ無効\n";
	}

	// 出力フォルダを作成
	global $OUTPUT_BASE_FOLDER;
	$OUTPUT_BASE_FOLDER = createOutputFolder();
	printf("出力フォルダ: %s\n", $OUTPUT_BASE_FOLDER);

	if(count($csv) !== 0){
		// 画像を事前に一括並列ダウンロード
		batch_download_images($csv);

		//個別注文ごとの処理
		if($process == "all" || $process == "temp"){
			$total_orders = count($csv);
			$current_order = 0;
			foreach($csv as $k=> $v){
				$current_order++;
				$name = $v['Name'] ?? 'unknown';
				printf("\r[2/3] 個別PDF生成中... %s (%d/%d)              ", $name, $current_order, $total_orders);
				$keys = get_template_names($v);
				if(!$keys){
					printf("\r%s...テンプレートなし（スキップ）              \n", $name);
					continue;
				}
				foreach($keys as $k2=> $v2){
					create_pdf_one($v, $v2);
				}
			}
			printf("\r[2/3] 個別PDF生成完了: %d件                        \n", $total_orders);
		}

		//印刷用にまとめる処理
		if($process == "all" || $process == "draft"){
		// draft専用モード時も個別PDFを生成（即削除される）
		if($process == "draft"){
			$total_orders = count($csv);
			$current_order = 0;
			foreach($csv as $k=> $v){
				$current_order++;
				printf("\r[2/3] 個別PDF生成中... %s (%d/%d)              ", $v['Name'] ?? 'unknown', $current_order, $total_orders);
				$keys = get_template_names($v);
				if($keys){
					foreach($keys as $k2=> $v2){
						create_pdf_one($v, $v2);
					}
				}
			}
			printf("\r[2/3] 個別PDF生成完了                              \n");
		}

			$draft_list = draft_list($csv);
			$total_drafts = count($draft_list);
			$current_draft = 0;
			foreach($draft_list as $k=>$v){
				$current_draft++;
				printf("\r[3/3] 印刷用PDF生成中... %s (%d/%d)              ", $k, $current_draft, $total_drafts);
				create_pdf($k, $v);
			}
			printf("\r[3/3] 印刷データ作成完了                            \n");
		}

		// 処理完了後、注文名をend_order.txtに追記
		if (END_ORDER) {
			end_order_append($csv);
			printf("end_order.txt に %d件 追記完了\n", count($csv));
		}
	} else {
		print "新しい注文情報がありませんでした";
	}

?>

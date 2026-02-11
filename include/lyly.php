<?php

	//===============================
	//	lyly用関数群
	//===============================
	use Yasumi\Yasumi;

	//画像URLのバリデーション（SSRF/LFI対策）
	function validate_image_url($url) {
		$parsed = parse_url($url);
		if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
			return false;
		}
		// httpsとhttpのみ許可（file://等を排除）
		if (!in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
			return false;
		}
		$host = strtolower($parsed['host']);
		// ローカルホスト・プライベートIPを排除
		if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '[::1]'], true)) {
			return false;
		}
		// プライベートIP範囲のチェック（169.254.x.x, 10.x.x.x, 172.16-31.x.x, 192.168.x.x）
		$ip = gethostbyname($host);
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
			return false;
		}
		return true;
	}

	//画像の一括並列ダウンロード（curl_multi使用）
	function batch_download_images($csv_array){
		global $OUTPUT_BASE_FOLDER;
		$downloadFolder = isset($OUTPUT_BASE_FOLDER) ? $OUTPUT_BASE_FOLDER . 'download/' : './download/';

		// 全注文からダウンロード対象の画像URLを収集
		$downloads = []; // ['url' => ..., 'path' => ...]
		foreach($csv_array as $row){
			$keys = get_template_names($row);
			if(!$keys) continue;
			foreach($keys as $key){
				$template = get_template($key);
				foreach($template as $elem){
					if($elem['type'] !== 'image') continue;
					$url = $row[$elem['name']] ?? '';
					if(trim($url) === '') continue;
					if(!validate_image_url($url)) continue;
					$file_path = $downloadFolder.($row['Name'] ?? 'unknown')."_".$elem['name'].".jpg";
					if(file_exists($file_path)) continue; // 既にダウンロード済み
					// 重複URL排除
					if(isset($downloads[$file_path])) continue;
					$downloads[$file_path] = $url;
				}
			}
		}

		if(empty($downloads)){
			printf("[1/3] ダウンロード対象なし（全てキャッシュ済み）\n");
			return;
		}

		// curl_multiで並列ダウンロード（最大10並列）
		$total_images = count($downloads);
		$completed_images = 0;
		$batchSize = 10;
		$batches = array_chunk($downloads, $batchSize, true);

		foreach($batches as $batch){
			$mh = curl_multi_init();
			$handles = [];

			foreach($batch as $file_path => $url){
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_multi_add_handle($mh, $ch);
				$handles[$file_path] = $ch;
			}

			// 全ハンドルの完了を待つ
			do {
				$status = curl_multi_exec($mh, $active);
				if($active){
					curl_multi_select($mh);
				}
			} while($active && $status == CURLM_OK);

			// 結果を取得してファイル保存
			foreach($handles as $file_path => $ch){
				$data = curl_multi_getcontent($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_multi_remove_handle($mh, $ch);
				curl_close($ch);

				$completed_images++;
				$pct = round($completed_images / $total_images * 100);
				printf("\r[1/3] 画像ダウンロード中... %d/%d (%d%%)    ", $completed_images, $total_images, $pct);

				if(!$data || $httpCode !== 200){
					e("一括DL失敗: " . $batch[$file_path] . " (HTTP {$httpCode})");
					continue;
				}
				file_put_contents($file_path, $data);
				if(filesize($file_path) === 0){
					unlink($file_path);
					continue;
				}
				convert_to_jpeg($file_path);
			}

			curl_multi_close($mh);
		}
		printf("\r[1/3] 画像ダウンロード完了: %d件              \n", $total_images);
	}

	//CSVファイルを配列化して返す
	//除外ログを&$skipped_logsに格納
	function csv_to_array($file_path, &$skipped_logs = null){
		mb_internal_encoding('UTF-8');

		if ($skipped_logs === null) {
			$skipped_logs = [];
		}

		// エンコーディング検知・自動変換
		$encoding_result = validate_and_convert_encoding($file_path);
		if ($encoding_result['converted']) {
			$file_path = $encoding_result['path'];
			// 変換後ファイルパスを記録（クリーンアップ用）
			global $CONVERTED_CSV_PATH;
			$CONVERTED_CSV_PATH = $encoding_result['path'];
		}
		foreach ($encoding_result['warnings'] as $w) {
			$skipped_logs[] = [
				'line' => 0,
				'name' => '(エンコーディング)',
				'reason' => $w,
			];
		}

		$file = fopen($file_path, 'r');
		if ($file === false) {
			throw new Exception("CSVファイルを開けません: " . $file_path);
		}
		$headers = fgetcsv($file, 0, ",", "\"", "\\");
		$data = []; // 配列を初期化

		// 各行を処理
		$no = 0;
		$line_number = 1; // ヘッダー行を1行目とする
		while (($row = fgetcsv($file, 0, ",", "\"", "\\")) !== false) {
			$line_number++;
			if(count($headers) !== count($row)){
				fclose($file);
				throw new Exception("CSV Error: 項目数が一致しません (ヘッダー: ".count($headers).", 行: ".count($row).")");
			}
			//値が入っていない行はスキップ
			if (empty(array_filter($row))) {
				continue;
			}
			$tmp = array_combine($headers, $row);

			// Nameが'#'で始まらない行はスキップ（不正データ除外）
			if (!isset($tmp['Name']) || strpos($tmp['Name'], '#') !== 0) {
				$skipped_logs[] = [
					'line' => $line_number,
					'name' => $tmp['Name'] ?? '',
					'reason' => 'Name検証失敗（#で始まらない）'
				];
				continue;
			}

			if(isset($tmp['Name'], $tmp['Order ID']) && strpos($tmp['Name'], '#') === 0 && is_numeric($tmp['Order ID'])){
				$no = 1;
				$row[1] = $row[1].'('.$no.')';
				$data[] = array_combine($headers, $row);
			} else {
				$no++;
				$row[1] = $row[1].'('.$no.')';
				$data[] = array_combine($headers, $row);
			}
		}
		fclose($file);
		return $data;
	}

	//作成するPDFの種類を取得
	function get_template_names($val){
		$productTitle = $val['Product Title'] ?? '';
		if (empty($productTitle)) {
			return null;
		}

		//縦横判定
		$tateyoko = '';
		if(mb_strpos($productTitle, 'シンプルデザイン') !== false){
			if(isset($val['写真の形']) && mb_strpos($val['写真の形'], '縦長') === 0){
				$tateyoko = "縦";
			} else if(isset($val['写真の形']) && mb_strpos($val['写真の形'], '横長') === 0){
				$tateyoko = "横";
			}
		}

		if(mb_strpos($productTitle, 'アクリルパネル') === 0){
			if(isset($val['Variant Option 1']) && mb_strpos($val['Variant Option 1'], 'M') === 0){
				return NAMES[$productTitle."M".$tateyoko] ?? null;
			} else {
				return NAMES[$productTitle."S".$tateyoko] ?? null;
			}
		} else {
			if(mb_strpos($productTitle, 'アクリル時計') === 0){
				$colorKey = $val['文字盤の色'] ?? '';
				return NAMES[$productTitle.$colorKey] ?? null;
			} else {
				return NAMES[$productTitle.$tateyoko] ?? null;
			}
		}
	}

	//デザインテンプレート取得
	function get_template($key){
		if ($key === null) {
			return [];
		}
		return TEMPLATES[$key] ?? [];
	}
	function set_template($template, $csv, $key){
		//置換処理
		foreach($template as $k=>$v){
			//image
			if($v['type'] == 'image'){
				$url = '';
				
				$url = $csv[$v['name']] ?? '';

				if(trim($url) == ''){
					$v['E'] = '写真URLが存在しない';
				} else if(!validate_image_url($url)) {
					$v['E'] = '許可されないURL: ' . $url;
					e('SSRF拒否: ' . $url);
				} else {
					global $OUTPUT_BASE_FOLDER;
					$downloadFolder = isset($OUTPUT_BASE_FOLDER) ? $OUTPUT_BASE_FOLDER . 'download/' : './download/';
					$file_path = $downloadFolder.($csv['Name'] ?? 'unknown')."_".$v['name'].".jpg";
					//ローカルにある場合スキップ
					if(file_exists($file_path)){
						$v['file'] = $file_path;
					} else if($url !='') {
						try {
							$ctx = stream_context_create(['http' => ['timeout' => 30], 'ssl' => ['verify_peer' => false]]);
							$image_data = file_get_contents($url, false, $ctx);
							if($image_data === false){
								$v['E'] = '画像ダウンロード失敗';
							} else {
								file_put_contents($file_path, $image_data);
								if (filesize($file_path) === 0) {
									$v['E'] = '0バイトの画像';
									unlink($file_path);
								} else {
									convert_to_jpeg($file_path);
									$v['file'] = $file_path;
								}
							}
							usleep(100000); // 0.1秒待機（サーバー負荷軽減）
						} catch(Exception $e){
							$v['E'] = '画像の問題: ' . $e->getMessage();
							e("画像エラー: " . $e->getMessage());
						}
					}
				}
			}
			//text
			if($v['type'] == 'text' || $v['type'] == 'multitext'){
				if(isset($csv[$v['name']])){
					$v['value'] = $csv[$v['name']];
				}
				//font(_***_editor)
				if(isset($csv['_'.$v['name'].'_editor']) && trim($csv['_'.$v['name'].'_editor']) != ''){
					$str = $csv['_'.$v['name'].'_editor'];
					$font = get_font($str);
					$size = get_fontsize($str);

					if($font){
						$v['font'] = $font;
					}
					// font sizeはtemplate通り
					//if($size){
					//	$v['font_size'] = $size;
					//}
				}
			}
			//calendar
			if($v['type'] == 'calendar'){
				if(isset($csv[$v['name']])){
					$v['date'] = $csv[$v['name']] ?? '';
				}
			}

			//エラーが存在する場合
			if(isset($v['E'])){
				e(($csv['Name'] ?? 'unknown').' '.$v['E']);
			}

			//set
			$template[$k] = $v;
		}
		return $template;
	}

	//jpgに変換(tcpdfの為)
	function convert_to_jpeg($file_path) {
		// 画像の種類を判定し、JPEGに変換
		if (is_readable($file_path)) {
			try {
				$image = @imagecreatefromstring(file_get_contents($file_path));
				if ($image !== false) {
					imagejpeg($image, $file_path, 100);
					imagedestroy($image);
				}
			} catch(Exception $msg) {
				e($file_path." 画像の問題 ".$msg);
			}
		}
	}

	//個別のPDF作成
	function create_pdf_one($val, $key){
		global $OUTPUT_BASE_FOLDER;
		$name = $val['Name'] ?? 'unknown';
		$tempFolder = isset($OUTPUT_BASE_FOLDER) ? $OUTPUT_BASE_FOLDER . 'temp/' : './temp/';
		$save_path = $tempFolder.$name.'_'.$key.'.pdf';

		//初期設定=================
		$tcpdf = new setasign\Fpdi\Tcpdf\Fpdi();
		$tcpdf->setPrintHeader(false);
		$tcpdf->setPrintFooter(false);
		$tcpdf->SetMargins(0, 0, 0);
		$tcpdf->SetAutoPageBreak(false, 0);
		$tcpdf->AddPage();
		$tcpdf->setFontSubsetting(false);

		foreach(FONTS as $v){
			//$tcpdf->AddFont($v, '', './'.$v.'.ttf', true);
		}

		//========================

		//各デザイン処理===========
		$template = get_template($key);
		$template = set_template($template, $val, $key);

		//BASE描画
		$load_path = './parts/'.$key.".pdf";
		$tcpdf->setSourceFile($load_path);
		$tpl = $tcpdf->importPage(1);
		$tcpdf->useTemplate($tpl,['adjustPageSize' => true]);

		//text, image
		if($template){
			foreach($template as $v){
				$angle = 0;
				if(isset($v['angle'])){
					$angle = $v['angle'];
				}

				if($v['type'] == 'text'){
					//type
					$font_type = '';
					if(isset($v['font_type'])){
						$font_type = $v['font_type'];
					}
					//color
					if(isset($v['font_color'])){
						$tmp = explode(',',$v['font_color']);
						if(count($tmp) == 4){
							$tcpdf->SetTextColor($tmp[0],$tmp[1],$tmp[2],$tmp[3]);
						} else if(count($tmp) == 3) {
							$tcpdf->SetTextColor($tmp[0],$tmp[1],$tmp[2]);
						}
					} else {
						$tcpdf->SetTextColor(0, -1, -1, -1, false, '');
					}
					if(isset($v['auto_size']) && $v['auto_size'] == 1){
						$tcpdf->SetXY($v['x'], $v['y']);
						for ($i = $v['font_size']; $i >= AUTO_SIZE_MIN; $i -= 0.001){
							$tcpdf->SetFont($v['font'], $font_type, $i);
							if ($tcpdf->GetStringWidth($v['value']) <= $v['w']){
								break;
							}
						}
						$tcpdf->Cell($v['w'], $v['h'], $v['value'], TEXT_BORDER, 1, $v['align'], 0, '', 0, false, 'T', 'M');
					} else {
						$tcpdf->SetXY($v['x'], $v['y']);
						$tcpdf->SetFont($v['font'], $font_type, $v['font_size']);
						$tcpdf->Cell($v['w'], $v['h'], $v['value'], TEXT_BORDER, 1, $v['align']);
					}
				} else if($v['type'] == 'multitext'){
					//type
					$font_type = '';
					if(isset($v['font_type'])){
						$font_type = $v['font_type'];
					}
					//color
					if(isset($v['font_color'])){
						$tmp = explode(',',$v['font_color']);
						if(count($tmp) == 4){
							$tcpdf->SetTextColor($tmp[0],$tmp[1],$tmp[2],$tmp[3]);
						} else if(count($tmp) == 3) {
							$tcpdf->SetTextColor($tmp[0],$tmp[1],$tmp[2]);
						}
					} else {
						$tcpdf->SetTextColor(0, -1, -1, -1, false, '');
					}
					$tcpdf->SetXY($v['x'], $v['y']);
					$tcpdf->SetFont($v['font'], $font_type, $v['font_size']);
					$tcpdf->MultiCell($v['w'], $v['h'], $v['value'], TEXT_BORDER, $v['align']);
				} else if($v['type'] == 'image'){
					//angle
					if($angle != 0){
						$tcpdf->Rotate($angle, $v['x']+($v['w']/2), $v['y']+($v['h']/2));
					}
					if(isset($v['file']) && $v['file']){
						$finfo = finfo_open(FILEINFO_MIME_TYPE);
						$mimeType = finfo_file($finfo, $v['file']);
						finfo_close($finfo);
						if ($mimeType === 'image/jpeg') {
							if(!isset($v['mask'])){
								$tcpdf->Image($v['file'], $v['x'], $v['y'], $v['w'], $v['h'], 'JPG', '', 'C', true, IMAGE_DPI);
							} else {
								if($v['mask'] == 'rect'){
									$tcpdf->StartTransform();
									if(isset($v['mangle'])){
										$tcpdf->Rotate($v['mangle'], $v['mx']+($v['mw']/2), $v['my']+($v['mh']/2));
									}
									$tcpdf->Rect($v['mx'], $v['my'], $v['mw'], $v['mh'], 'CNZ');
									if(isset($v['mangle'])){
										$tcpdf->Rotate(-($v['mangle']), $v['mx']+($v['mw']/2), $v['my']+($v['mh']/2));
									}
									$tcpdf->Image($v['file'], $v['x'], $v['y'], $v['w'], $v['h'], 'JPG', '', 'C', true, IMAGE_DPI);
									$tcpdf->StopTransform();
								} else if($v['mask'] == 'roundedrect'){
									$tcpdf->StartTransform();
									$tcpdf->RoundedRect($v['mx'], $v['my'], $v['mw'], $v['mh'], $v['mround'], '1111', 'CNZ');
									$tcpdf->Image($v['file'], $v['x'], $v['y'], $v['w'], $v['h'], 'JPG', '', 'C', true, IMAGE_DPI);
									$tcpdf->StopTransform();
								} else if($v['mask'] == 'ellipse'){
									$tcpdf->StartTransform();
									$tcpdf->Ellipse($v['mx'], $v['my'], $v['mw'], $v['mh'], $v['mangle'], 0, 360, 'CNZ');
									$tcpdf->Image($v['file'], $v['x'], $v['y'], $v['w'], $v['h'], 'JPG', '', 'C', true, IMAGE_DPI);
									$tcpdf->StopTransform();
								}
							}
						}
					}
					//angle
					if($angle != 0){
						$tcpdf->Rotate(-($angle), $v['x']+($v['w']/2), $v['y']+($v['h']/2));
					}

				} else if($v['type'] == 'rect'){
					$tcpdf->Rect($v['x'], $v['y'], $v['w'], $v['h'], 'F', array(), array(0,0,0));
				} else if($v['type'] == 'roundedrect'){
					$tcpdf->RoundedRect($v['x'], $v['y'], $v['w'], $v['h'], $v['round'], '1111', 'F', array(), array(0,0,0));
				} else if($v['type'] == 'ellipse'){
					$tcpdf->Ellipse($v['x'], $v['y'], $v['w'], $v['h'], $angle, 0, 360, 'F', array(), array(0,0,0));
				} else if($v['type'] == 'rectline'){
					$tcpdf->SetLineWidth($v['border_width']*0.3759);
					$tcpdf->SetDrawColor(0, 0, 0);
					$tcpdf->Rect($v['x'], $v['y'], $v['w'], $v['h'], 'D');
				} else if($v['type'] == 'pdf'){
					$tcpdf->setSourceFile('./parts/'.$v['file']);
					$tpl = $tcpdf->importPage(1);
					$tcpdf->useTemplate($tpl,['adjustPageSize' => true]);					
				} else if($v['type'] == 'calendar'){
					//デザイン調整変数-------------
					if($v['size'] == 's'){
						$spx = 2;
						$spy = 28.3;
						$dmx = 8.2;
						$dmy = 5.6;
						$fs = 6;
						$font = "Shippori Mincho";
					} else if($v['size'] == 'm'){
						$spx = 3;
						$spy = 40;
						$dmx = 11.5;
						$dmy = 8;
						$fs = 9;
						$font = "Shippori Mincho";
					}
					//----------------------------

					$dt = new DateTime($v['date']);

					// 年、月、日、曜日を抽出
					$year  = $dt->format('Y');
					$month = $dt->format('m');
					$day   = $dt->format('d');
					$weekday = $dt->format('w'); // 0:日曜日, 1:月曜日, ...
					$font_type = '';

					//月名
					$month_name = ['January','February','March','April','May','June','July','August','September','October','November','December',];

					if($v['size'] == 's'){
						//year
						$tcpdf->SetXY($v['x'], $v['y']);
						$tcpdf->SetFont($font, '', '12.65');
						$tcpdf->Cell(56, 4.5, $year, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
						//month num
						$tcpdf->SetXY($v['x']+1.8, $v['y']+5.5);
						$tcpdf->SetFont($font, $font_type, 28.55);
						$tcpdf->Cell(10, 10, (int)$month, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
						//month name
						$tcpdf->SetXY($v['x'], $v['y']+11);
						$tcpdf->SetFont($font, $font_type, 12.69);
						$tcpdf->Cell(56, 4.5, $month_name[(int)$month-1], 0, 1, 'C', 0, '', 0, false, 'T', 'M');
					} else if($v['size'] == 'm'){
						//year
						$tcpdf->SetXY($v['x'], $v['y']);
						$tcpdf->SetFont($font, '', '17.85');
						$tcpdf->Cell(78, 4.5, $year, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
						//month num
						$tcpdf->SetXY($v['x']+1, $v['y']+7.8);
						$tcpdf->SetFont($font, $font_type, 40.15);
						$tcpdf->Cell(15, 15, (int)$month, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
						//month name
						$tcpdf->SetXY($v['x'], $v['y']+16);
						$tcpdf->SetFont($font, $font_type, 17.85);
						$tcpdf->Cell(78, 7.5, $month_name[(int)$month-1], 0, 1, 'C', 0, '', 0, false, 'T', 'M');
					}

					$calendar = get_calendar($v['date']);
					//days
					foreach($calendar['days'] as $ck => $cv){
						$px = ($ck+(int)$calendar['week'])%7*$dmx;
						$py = floor(($ck+(int)$calendar['week'])/7)*$dmy;
						//ハート
						$tmp1 = new DateTime($v['date']);
						$tmp2 = new DateTime($cv['day']);
						if($tmp1 == $tmp2){
							if(isset($v['black']) && $v['black']){
								if($v['size'] == 's'){
									$tcpdf->ImageSVG($file='./parts/calendar_s_heart_black.svg', $x=$v['x']+$spx+$px-1.5, $y=$v['y']+$spy+$py-0.75, $w='6.06', $h='4.74', $link='', $align='', $palign='', $border=0, $fitonpage=false);
								} else if($v['size'] == 'm'){
									$tcpdf->ImageSVG($file='./parts/calendar_m_heart_black.svg', $x=$v['x']+$spx+$px-2.75, $y=$v['y']+$spy+$py-1.2, $w='8.516', $h='6.665', $link='', $align='', $palign='', $border=0, $fitonpage=false);
								}
							} else {
								if($v['size'] == 's'){
									$tcpdf->ImageSVG($file='./parts/calendar_s_heart.svg', $x=$v['x']+$spx+$px-1.5, $y=$v['y']+$spy+$py-0.75, $w='6.06', $h='4.74', $link='', $align='', $palign='', $border=0, $fitonpage=false);
								} else if($v['size'] == 'm'){
									$tcpdf->ImageSVG($file='./parts/calendar_m_heart.svg', $x=$v['x']+$spx+$px-2.75, $y=$v['y']+$spy+$py-1.2, $w='8.516', $h='6.665', $link='', $align='', $palign='', $border=0, $fitonpage=false);
								}
							}
						}
						//各日表示
						if(isset($v['black']) && $v['black']){
							$tcpdf->SetTextColor(0, -1, -1, -1, false, '');
						} else {
							if($cv['week'] == 'red'){
								$tcpdf->SetTextColor(31, 96, 89, 0);
							} else if($cv['week'] == 'blue'){
								$tcpdf->SetTextColor(86, 52, 0, 0);
							} else {
								$tcpdf->SetTextColor(0, -1, -1, -1, false, '');
							}
						}
						$tcpdf->SetXY($v['x']+$spx+$px, $v['y']+$spy+$py);
						$tcpdf->SetFont($font, $font_type, $fs);
						$tcpdf->Cell(3, 3, day_cut($cv['day']), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
					}
				}
			}
		}
		//=======================

		//保存処理================
		$tcpdf->Output($save_path, "F");
		//=======================

		//カレンダー(横形式)のは縦に回転しておく
		if($key == 'calendar_s_text' || $key == 'calendar_s_fullcolor'|| $key == 'calendar_s_futa'){
			rotate_one($save_path, 129.52, 91);
		} else if($key == 'calendar_m_text' || $key == 'calendar_m_fullcolor' || $key == 'calendar_m_futa'){
			rotate_one($save_path, 182.05, 128);
		}
		
		
		if($key == '6normal_s_text' || $key == '6normal_s_fullcolor' || $key == '6normal_s_futa'){
			rotate_one($save_path, 129.52, 91);
		}

	return $save_path;
	}


	


	//まとめたPDFの作成
	function create_pdf($k, $v){
		global $SKIPPED_PDFS;
		if (!isset($SKIPPED_PDFS)) {
			$SKIPPED_PDFS = [];
		}

		//座標ループ
		$xys = DRAFTS_XYS[$k];
		//作成するPDF個数
		$limit = count($xys);
		$page_max = ceil(count($v)/$limit);

		for($j=0; $j < $page_max; $j++){

			//初期設定=================
			$tcpdf = new setasign\Fpdi\Tcpdf\Fpdi();
			$tcpdf->setPrintHeader(false);
			$tcpdf->setPrintFooter(false);
			$tcpdf->SetMargins(0, 0, 0);
			$tcpdf->SetAutoPageBreak(false, 0);
			$tcpdf->AddPage();
			$tcpdf->setFontSubsetting(false);
			//========================

			//BASE描画(サイズ用)-------------
			$tcpdf->setSourceFile('./parts/draft_base.pdf');
			$tpl = $tcpdf->importPage(1);
			$tcpdf->useTemplate($tpl,['adjustPageSize' => true]);
			//------------------------------

			$doc_size = $tcpdf->getTemplateSize($tpl);
			$ax = $doc_size['width']/2;
			$ay = $doc_size['height']/2;
			//---------------------

			//ページごとの初期値
			$start = $j*$limit;
			$ids = '';

			for($i=0; $i < count($xys); $i++){
				$k2 = $start + $i;
				$v2 = $xys[$i];

				//IDS
				if(isset($v[$k2])){
					if($ids == ''){
						$ids .= get_id($v[$k2]);
					} else {
						$ids .= ','.get_id($v[$k2]);
					}
				}

				//各PDF配置
				if(isset($v[$k2])){
					//描画しない例外処理---------
					$flag = true;
					if($k === 's_futa' && strpos($v[$k2], 'led') !== false){
						$flag = false;
					} else if($k === 's_led_futa' && strpos($v[$k2], 'led') === false){
						$flag = false;
					}
					//-------------------------

					$x = floatval($v2[0])+$ax;
					$y = floatval($v2[1])+$ay;
					global $OUTPUT_BASE_FOLDER;
					$tempFolder = isset($OUTPUT_BASE_FOLDER) ? $OUTPUT_BASE_FOLDER . 'temp/' : './temp/';
					$path = $tempFolder.$v[$k2];

					if(file_exists($path)) {
						$tcpdf->setSourceFile($path);
						$tpl = $tcpdf->importPage(1);
						if($flag){
							$tcpdf->useTemplate($tpl, $x, $y);
						}
						$doc_size_one = $tcpdf->getTemplateSize($tpl);

						//rect test-------------
						//$tcpdf->SetAlpha(0.1);
						//$tcpdf->Rect($x, $y, $doc_size_one['width'], $doc_size_one['height'], 'F');
						//$tcpdf->SetAlpha(1);
						//----------------------
					} else {
						global $SKIPPED_PDFS;
						$SKIPPED_PDFS[] = basename($path);
						e("個別PDF(".$path.")が存在しないのでスキップします");
						continue;
					}

					//ファイル名表示---------
					if(NAME_DISPLAY){
						$tcpdf->SetXY($x+$doc_size_one['width']+6, $y);
						$tcpdf->SetFont('helvetica', '', 8);
						$tcpdf->StartTransform();
						$tcpdf->Rotate(270, $x+$doc_size_one['width']+6, $y);
						$tcpdf->Cell(0, 0, '['.str_replace('./temp/', '', $path).']', 0, 1, 'L');
						$tcpdf->StopTransform();
					}
					//----------------------
				}
			}
			//枠描画=========================
			//共通でいい
			//$tcpdf->setSourceFile('./parts/'.draft_base_name($k));
			//$tpl = $tcpdf->importPage(1);
			//$tcpdf->useTemplate($tpl,['adjustPageSize' => true]);
			//===============================

			//保存処理================
			global $OUTPUT_BASE_FOLDER;
			$draftFolder = isset($OUTPUT_BASE_FOLDER) ? $OUTPUT_BASE_FOLDER . 'draft/' : './draft/';
			//idsいらない
			//$file_path = $draftFolder.get_time().($j+1).'_'.$k.'_'.$ids.'.pdf';
			$file_path = $draftFolder.get_time().($j+1).'_'.$k.'.pdf';
			$tcpdf->Output($file_path, "F");

			//ガイドラインオーバーレイ処理================
			// クリーン版はそのまま保持、_gl版にガイドラインを重ねる
			global $GUIDELINE_OVERLAY_DISABLE;
			$overlay_enabled = GUIDELINE_OVERLAY && empty($GUIDELINE_OVERLAY_DISABLE);
			$gl_path = null;
			if($overlay_enabled && isset(GUIDELINE_PDFS[$k]) && !empty(GUIDELINE_PDFS[$k])){
				$guideline_path = GUIDELINE_PDFS[$k];
				if(file_exists($guideline_path)){
					$gl_path = preg_replace('/\.pdf$/i', '_gl.pdf', $file_path);
					copy($file_path, $gl_path);
					overlay_guideline($gl_path, $guideline_path, GUIDELINE_ALPHA);
				} else {
					e("ガイドラインPDF not found: ".$guideline_path);
				}
			}
			//===========================================

			if(ROTATE){
				rotate($file_path);
				if($gl_path && file_exists($gl_path)){
					rotate($gl_path);
				}
			}
			//=======================
		}
	}

	//CSV配列から印刷用PDFに処理しやすい配列を作成して返す
	function draft_list($list){
		$data = [];
		foreach($list as $v){
			$names = get_template_names($v);
			//print_r($names);
			if (!$names) continue;
			foreach($names as $v2){
				$quantity = $v['Line Item Quantity'] ?? 1;
				$name = $v['Name'] ?? 'unknown';
				for($i=0; $i < order_count($quantity); $i++){
					$data[$v2][] = $name;
				}
			}
		}
		$ret = [];

		foreach(DRAFTS as $k => $v){
			foreach($v as $v2){
				if(isset($data[$v2])){
					foreach($data[$v2] as $v3){
						$ret[$k][] = $v3.'_'.$v2.'.pdf';
					}
				}
			}
		}
		return $ret;
	}

	//数値化
	function order_count($val){
		return intval($val);
	}
	//#以下の数字を抽出
	function get_id($str){
		$p = '/#\d+/';
		if (preg_match($p, $str, $m)) {
			return $m[0];
		}
		return '';
	}
	//今の時間を取得(ファイル名用)
	function get_time(){
		return date('Ymd_Hi_');
	}


	//現状未使用
	function draft_base_name($val){
		if(strpos($val,'s') === 0){
			return 'draft_s.pdf';
		} else if(strpos($val,'m') === 0){
			return 'draft_m.pdf';
		} else if(strpos($val,'block') === 0){
			return 'draft_block.pdf';
		} else if(strpos($val,'tokei') === 0){
			return 'draft_tokei.pdf';
		}
	}

	//90度回転して保存(draft用)
	function rotate($path){
		//全て同じサイズ
		$w=335.5;
		$h=775.5;

		//初期設定=================
		$tcpdf = new setasign\Fpdi\Tcpdf\Fpdi('L','mm', array($h, $w));
		$tcpdf->setPrintHeader(false);
		$tcpdf->setPrintFooter(false);
		$tcpdf->SetMargins(0, 0, 0);
		$tcpdf->SetAutoPageBreak(false, 0);
		$tcpdf->AddPage();
		$tcpdf->setFontSubsetting(false);
		//========================

		$tcpdf->setSourceFile($path);
		$tpl = $tcpdf->importPage(1);

		//775,335
		$tcpdf->StartTransform();
		$tcpdf->Rotate(90, 0, 0);
		$tcpdf->useTemplate($tpl, -$w, 0);
		$tcpdf->StopTransform();
		$tcpdf->Output($path, "F");
	}

	//カレンダーなど用
	function rotate_one($path, $w, $h){
		//初期設定=================
		$tcpdf = new setasign\Fpdi\Tcpdf\Fpdi('','mm', array($h, $w));
		$tcpdf->setPrintHeader(false);
		$tcpdf->setPrintFooter(false);
		$tcpdf->SetMargins(0, 0, 0);
		$tcpdf->SetAutoPageBreak(false, 0);
		$tcpdf->AddPage();
		$tcpdf->setFontSubsetting(false);
		//========================

		$tcpdf->setSourceFile($path);
		$tpl = $tcpdf->importPage(1);

		$tcpdf->StartTransform();
		$tcpdf->Rotate(270, 0, 0);
		$tcpdf->useTemplate($tpl, 0, -$h);
		$tcpdf->StopTransform();
		$tcpdf->Output($path, "F");
	}


	//完了済みを削除
	function end_del($csv_base){
		$csv = [];
		$end_order_path = __DIR__ . '/../end_order.txt';

		// end_order.txt が存在しない場合はそのまま返す
		if (!file_exists($end_order_path)) {
			return $csv_base;
		}

		$end_text = file_get_contents($end_order_path);
		// \r\n と \n の両方に対応
		$end_list = preg_split('/\r?\n/', trim($end_text));
		$end_list = array_map('trim', $end_list);
		$end_list = array_filter($end_list);
		// 高速検索のためハッシュ化
		$end_set = array_flip($end_list);

		foreach($csv_base as $v){
			$name = $v['Name'] ?? '';
			// Name に "(1)" 等の連番がある場合、ベース名で照合
			$base_name = preg_replace('/\(\d+\)$/', '', $name);
			if (isset($end_set[$name]) || isset($end_set[$base_name])) {
				printf("%s - スキップ（処理済み）\n", $name);
			} else {
				$csv[] = $v;
			}
		}
		return $csv;
	}

	//完了済み注文をend_order.txtに追記
	function end_order_append($csv_array){
		$end_order_path = __DIR__ . '/../end_order.txt';
		$names = [];
		foreach($csv_array as $v){
			$name = $v['Name'] ?? '';
			// ベース名を抽出して重複排除（連番を除く）
			$base_name = preg_replace('/\(\d+\)$/', '', $name);
			if ($base_name !== '' && !in_array($base_name, $names)) {
				$names[] = $base_name;
			}
		}
		if (!empty($names)) {
			$append_text = implode("\n", $names) . "\n";
			file_put_contents($end_order_path, $append_text, FILE_APPEND | LOCK_EX);
		}
	}

	//ドライラン: CSV検証のみ実行（PDF生成・画像DLなし）
	function dry_run($csv_array){
		if (empty($csv_array)) {
			print "注文データがありません\n";
			return;
		}

		$ok_count = 0;
		$ng_count = 0;
		$ng_details = [];
		$checked_urls = []; // URL疎通チェックのキャッシュ

		// END_ORDER有効時は処理済み注文を表示
		if (END_ORDER) {
			$end_order_path = __DIR__ . '/../end_order.txt';
			if (file_exists($end_order_path)) {
				$end_text = file_get_contents($end_order_path);
				$end_list = array_map('trim', preg_split('/\r?\n/', trim($end_text)));
				$end_set = array_flip(array_filter($end_list));
				foreach ($csv_array as $row) {
					$name = $row['Name'] ?? '';
					$base = preg_replace('/\(\d+\)$/', '', $name);
					if (isset($end_set[$name]) || isset($end_set[$base])) {
						printf("  SKIP: %s - 処理済み（end_order.txt）\n", $name);
					}
				}
			}
		}

		foreach ($csv_array as $row) {
			$name = $row['Name'] ?? '(不明)';
			$errors = [];

			// チェック1: Product TitleがNAMESに存在するか
			$keys = get_template_names($row);
			if ($keys === null) {
				$productTitle = $row['Product Title'] ?? '(空)';
				$errors[] = "Product Title がNAMESに未登録: " . $productTitle;
			}

			// チェック2: テンプレートPDFが実在するか
			if ($keys !== null) {
				foreach ($keys as $key) {
					$pdf_path = './parts/' . $key . '.pdf';
					if (!file_exists($pdf_path)) {
						$errors[] = "テンプレートPDF不在: " . $pdf_path;
					}
				}
			}

			// チェック3: 画像URLの疎通確認（HEADリクエストのみ）
			if ($keys !== null) {
				foreach ($keys as $key) {
					$template = get_template($key);
					foreach ($template as $elem) {
						if ($elem['type'] !== 'image') continue;
						$url = $row[$elem['name']] ?? '';
						if (trim($url) === '') continue;

						// キャッシュ済みURLはスキップ
						if (isset($checked_urls[$url])) {
							if ($checked_urls[$url] !== true) {
								$errors[] = $checked_urls[$url];
							}
							continue;
						}

						if (!validate_image_url($url)) {
							$msg = "無効なURL ({$elem['name']}): " . $url;
							$errors[] = $msg;
							$checked_urls[$url] = $msg;
							continue;
						}

						$ch = curl_init($url);
						curl_setopt($ch, CURLOPT_NOBODY, true);
						curl_setopt($ch, CURLOPT_TIMEOUT, 10);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
						curl_exec($ch);
						$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						$curlError = curl_errno($ch);
						curl_close($ch);

						if ($curlError) {
							$msg = "画像URLタイムアウト ({$elem['name']}): " . $url;
							$errors[] = $msg;
							$checked_urls[$url] = $msg;
						} else if ($httpCode < 200 || $httpCode >= 400) {
							$msg = "画像URL到達不可 ({$elem['name']}): {$url} (HTTP {$httpCode})";
							$errors[] = $msg;
							$checked_urls[$url] = $msg;
						} else {
							$checked_urls[$url] = true;
						}
					}
				}
			}

			// チェック4: テキスト検証（タイポ・日付・文字化け）
			if ($keys !== null) {
				$validation = validate_order_text($row, $keys);
				foreach ($validation['warnings'] as $w) {
					$errors[] = sprintf('[%s] %s: %s', $w['type'], $w['field'], $w['message']);
				}
			}

			// 結果集計
			if (empty($errors)) {
				$ok_count++;
				printf("  OK: %s\n", $name);
			} else {
				$ng_count++;
				foreach ($errors as $err) {
					printf("  NG: %s - %s\n", $name, $err);
				}
				$ng_details[$name] = $errors;
			}
		}

		// サマリー
		printf("\n===== ドライラン結果 =====\n");
		printf("OK: %d件 / NG: %d件\n", $ok_count, $ng_count);
		if ($ng_count > 0) {
			printf("\n--- NGの詳細 ---\n");
			foreach ($ng_details as $name => $errors) {
				printf("%s:\n", $name);
				foreach ($errors as $err) {
					printf("  - %s\n", $err);
				}
			}
		}
	}

	//フォントの種類を返す
	function get_font($str){
		$p = "/font:(\d+)/";
		preg_match($p, $str, $m);
		
		if (count($m) > 1) {
			return FONTS[$m[1]];
		} else {
			return false;
		}
	}
	//フォントサイズを返す
	function get_fontsize($str){
		$p = "/size:(\d+)/";
		preg_match($p, $str, $m);
		
		if (count($m) > 1) {
			$pt = $m[1] * (72 / 150);
			return $pt;
		} else {
			return false;
		}
	}

	function e($msg){
		$logFile = __DIR__ . '/../log.txt';
		$entry = "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
		file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
	}

	//始まりの曜日と、各日の休日判定済みリストを返す
	function get_calendar($d){
		$dt = new DateTime($d);
		$year  = $dt->format('Y');
		$month = $dt->format('m');
		$day   = $dt->format('d');
		$ym = $year.'-'.$month;
		
		$list = [];
	
		//祭日Yasumi取得-----------
		$holidays = Yasumi::create('Japan', $year, 'ja_JP');

		foreach ($holidays->getHolidayDates() as $date) {
			if(strpos($date, $ym) !== false){
				$list[] = $date;
			}
		}
		//-------------------------

	
		$last = (new DateTimeImmutable)->modify('last day of'.$ym)->format('d');
		$ts = mktime(0, 0, 0, $month, 1, $year);
		$week = date('w', $ts);

		$days = [];
		//1でlastに+1しておく
		for($i = 1; $i < $last+1; $i++){
			$tmp1 = $ym.'-'.str_pad($i, 2, '0', STR_PAD_LEFT);
			$tmp2 = ($week+$i-1)%7;
			$w = '';
			if($tmp2 == 0){
				$w = 'red';
			} else if($tmp2 == 6){
				$w = 'blue';
			}
			foreach($list as $v){
				if($tmp1 == $v){
					$w = 'red';
					break;
				}
			}
			$days[] = ['day'=>$tmp1, 'week'=>$w];
		}
		return ['week'=>$week, 'days'=>$days];
	}

	function day_cut($date){
		$dt = new DateTime($date);
		return (int)$dt->format('d');
	}

	// 古い出力フォルダを削除（最新$keep_count件を保持）
	function cleanup_output($keep_count = 5){
		$outputDir = OUTPUT_DIR;
		if (!is_dir($outputDir)) {
			print "出力フォルダが存在しません\n";
			return;
		}

		// output/内のディレクトリを収集
		$dirs = [];
		foreach (scandir($outputDir) as $entry) {
			if ($entry === '.' || $entry === '..') continue;
			$path = $outputDir . $entry;
			if (is_dir($path)) {
				$dirs[] = ['name' => $entry, 'path' => $path, 'mtime' => filemtime($path)];
			}
		}

		// 更新日時の新しい順にソート
		usort($dirs, function($a, $b) { return $b['mtime'] - $a['mtime']; });

		if (count($dirs) <= $keep_count) {
			printf("出力フォルダ: %d件（保持上限 %d件以下のため削除なし）\n", count($dirs), $keep_count);
			return;
		}

		// 古いフォルダを削除対象に
		$to_delete = array_slice($dirs, $keep_count);
		$deleted_count = 0;
		$deleted_size = 0;

		foreach ($to_delete as $dir) {
			// フォルダサイズを概算（ZIP + フォルダ）
			$zip_path = $dir['path'] . '.zip';
			if (file_exists($zip_path)) {
				$deleted_size += filesize($zip_path);
				unlink($zip_path);
			}

			// フォルダを再帰削除
			$it = new RecursiveDirectoryIterator($dir['path'], RecursiveDirectoryIterator::SKIP_DOTS);
			$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($files as $file) {
				$deleted_size += $file->getSize();
				if ($file->isDir()) {
					rmdir($file->getRealPath());
				} else {
					unlink($file->getRealPath());
				}
			}
			rmdir($dir['path']);
			$deleted_count++;
		}

		$size_mb = round($deleted_size / 1024 / 1024);
		printf("クリーンアップ完了: %d件削除（約%dMB解放）、最新%d件を保持\n", $deleted_count, $size_mb, $keep_count);
	}

	// タイムスタンプ付き出力フォルダを作成
	function createOutputFolder(){
		// 競合を防ぐためユニークIDを追加
		$timestamp = date('Ymd_His') . '_' . uniqid();
		$baseFolder = OUTPUT_DIR . $timestamp . '/';

		// ベースフォルダ作成（エラーチェック付き）
		if (!file_exists(OUTPUT_DIR)) {
			if (!mkdir(OUTPUT_DIR, 0755, true) && !is_dir(OUTPUT_DIR)) {
				throw new Exception('出力ディレクトリの作成に失敗しました: ' . OUTPUT_DIR);
			}
			clearstatcache(true, OUTPUT_DIR);
		}
		if (!file_exists($baseFolder)) {
			if (!mkdir($baseFolder, 0755, true)) {
				throw new Exception('出力フォルダの作成に失敗しました: ' . $baseFolder);
			}
			clearstatcache(true, $baseFolder);
		}

		// サブフォルダ作成（エラーチェック付き）
		$subFolders = ['temp', 'draft', 'download'];
		foreach ($subFolders as $folder) {
			$path = $baseFolder . $folder . '/';
			if (!file_exists($path)) {
				if (!mkdir($path, 0755, true)) {
					throw new Exception('サブフォルダの作成に失敗しました: ' . $path);
				}
				clearstatcache(true, $path);
			}
		}

		// 最終的な全キャッシュクリア
		clearstatcache(true);

		return $baseFolder;
	}

	// フォルダをZIPファイルに圧縮
	function createZipArchive($folderPath){
		// STEP 1: キャッシュクリア（念のため）
		clearstatcache(true, $folderPath);
		clearstatcache(true, OUTPUT_DIR);

		// STEP 2: is_dir()で事前検証
		if (!is_dir($folderPath)) {
			throw new Exception('フォルダが存在しません: ' . $folderPath);
		}
		if (!is_dir(OUTPUT_DIR)) {
			throw new Exception('出力ディレクトリが存在しません: ' . OUTPUT_DIR);
		}

		// STEP 3: realpath()をリトライロジック付きで実行
		$realPath = realpath($folderPath);
		if ($realPath === false) {
			// リトライ: キャッシュクリア + 10ms待機
			clearstatcache(true, $folderPath);
			usleep(10000);
			$realPath = realpath($folderPath);

			if ($realPath === false) {
				// 詳細なエラーログ
				error_log("realpath() failed for existing directory: " . $folderPath);
				error_log("Directory exists: " . (is_dir($folderPath) ? 'yes' : 'no'));
				throw new Exception('フォルダパスの解決に失敗しました: ' . $folderPath);
			}
		}

		$baseOutputDir = realpath(OUTPUT_DIR);
		if ($baseOutputDir === false) {
			clearstatcache(true, OUTPUT_DIR);
			usleep(10000);
			$baseOutputDir = realpath(OUTPUT_DIR);

			if ($baseOutputDir === false) {
				error_log("realpath() failed for OUTPUT_DIR: " . OUTPUT_DIR);
				throw new Exception('出力ディレクトリパスの解決に失敗しました: ' . OUTPUT_DIR);
			}
		}
		$realPath = rtrim($realPath, DIRECTORY_SEPARATOR);
		$baseOutputDir = rtrim($baseOutputDir, DIRECTORY_SEPARATOR);
		$realPathWithSep = $realPath . DIRECTORY_SEPARATOR;
		$baseOutputDirWithSep = $baseOutputDir . DIRECTORY_SEPARATOR;

		if (strpos($realPathWithSep, $baseOutputDirWithSep) !== 0) {
			throw new Exception('不正なフォルダパス: ' . $folderPath);
		}

		$zipFilePath = rtrim($folderPath, "/\\") . '.zip';

		if (!class_exists('ZipArchive')) {
			throw new Exception('ZipArchive クラスが利用できません');
		}

		$zip = new ZipArchive();
		if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			throw new Exception('ZIPファイルの作成に失敗しました: ' . $zipFilePath);
		}

		// フォルダ内のファイルを再帰的に追加（ドットファイルをスキップ）
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($realPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($files as $file) {
			if (!$file->isDir()) {
				$filePath = $file->getRealPath();
				if ($filePath === false) {
					continue;
				}
				// _gl.pdf をスキップ（プレビュー専用ファイル）
				if (preg_match('/_gl\.pdf$/i', $filePath)) {
					continue;
				}
				if (strpos($filePath, $realPathWithSep) !== 0) {
					error_log("ZIP作成警告: 予期しないパス - $filePath");
					continue;
				}
				$relativePath = substr($filePath, strlen($realPathWithSep));

				// ファイル追加エラーをログに記録
				if (!$zip->addFile($filePath, $relativePath)) {
					error_log("ZIP作成警告: ファイル追加失敗 - $filePath");
				}
			}
		}

		$zip->close();
		return $zipFilePath;
	}

	/**
	 * 既存PDFの全ページにガイドラインPDFを中央基準で重ねる
	 */
	function overlay_guideline($base_path, $guideline_path, $alpha = 0.5){
		// ベースPDFのページ数とサイズ取得
		$fpdi_base = new setasign\Fpdi\Tcpdf\Fpdi();
		$page_count = $fpdi_base->setSourceFile($base_path);
		$tpl_base = $fpdi_base->importPage(1);
		$base_size = $fpdi_base->getTemplateSize($tpl_base);

		// ガイドラインPDFのサイズ取得
		$fpdi_guide = new setasign\Fpdi\Tcpdf\Fpdi();
		$fpdi_guide->setSourceFile($guideline_path);
		$tpl_guide = $fpdi_guide->importPage(1);
		$guide_size = $fpdi_guide->getTemplateSize($tpl_guide);

		// 中央基準での配置位置を計算
		$offset_x = ($base_size['width'] - $guide_size['width']) / 2;
		$offset_y = ($base_size['height'] - $guide_size['height']) / 2;

		// サイズ差をログ出力
		e("ガイドラインオーバーレイ: base={$base_size['width']}x{$base_size['height']}mm, guide={$guide_size['width']}x{$guide_size['height']}mm, offset=({$offset_x}, {$offset_y})");

		// 新しいPDFを作成
		$tcpdf = new setasign\Fpdi\Tcpdf\Fpdi('P', 'mm', array($base_size['width'], $base_size['height']));
		$tcpdf->setPrintHeader(false);
		$tcpdf->setPrintFooter(false);
		$tcpdf->SetMargins(0, 0, 0);
		$tcpdf->SetAutoPageBreak(false, 0);

		// 全ページをループ
		for($p = 1; $p <= $page_count; $p++){
			$tcpdf->AddPage();

			// ベースPDFのページを配置
			$tcpdf->setSourceFile($base_path);
			$tpl = $tcpdf->importPage($p);
			$tcpdf->useTemplate($tpl, 0, 0);

			// ガイドラインPDFを中央基準・透明度付きで配置
			$tcpdf->setSourceFile($guideline_path);
			$tpl_g = $tcpdf->importPage(1);
			$tcpdf->SetAlpha($alpha);
			$tcpdf->useTemplate($tpl_g, $offset_x, $offset_y);
			$tcpdf->SetAlpha(1);
		}

		// 上書き保存
		$tcpdf->Output($base_path, 'F');
	}

?>

<?php
	// 1(有効) or 0(無効)  ※実務で使う場合すべて有効で　※デザインの調整を行う場合は無効にした方が楽な物があります
	const ROTATE = 1;		//印刷PDFの回転処理
	const END_ORDER = 0;	//完了した注文は省く
	const NAME_DISPLAY = 1;	//印刷PDFへ個別PDFのファイル名を表示する
	
	//---241109 add--------------- 
	const AUTO_SIZE_MIN = 0.01;	//自動サイズ時のフォントの最小サイズ(これ以上小さくしたくない値を入れてください)
	const TEXT_BORDER = 0;		//1(有効) or 0(無効) テキストボックスに線が入ります。デザイン微調整の時有効にすると分かりやすい(実務で使う場合は0)
	//----------------------------

	//商品とPDFテンプレートの紐づけ用(作成するPDFの全種類)※例：textが必要ない場合でも印刷用で順番をそろえる空が必要なので書いておく
	const NAMES = [
		'テスト'=>['test_s_text', 'test_s_fullcolor', 'test_s_futa'],
		'アクリルパネル インスタデザインS'=>['insta_s_text', 'insta_s_fullcolor', 'insta_s_futa'],
		'アクリルパネル インスタデザインM'=>['insta_m_text', 'insta_m_fullcolor', 'insta_m_futa'],
		'アクリルパネル シンプルデザインS縦'=>['simple_s_tate_text', 'simple_s_tate_fullcolor', 'simple_s_tate_futa'],
		'アクリルパネル シンプルデザインM縦'=>['simple_m_tate_text', 'simple_m_tate_fullcolor', 'simple_m_tate_futa'],
		'アクリルパネル シンプルデザインS横'=>['simple_s_yoko_text', 'simple_s_yoko_fullcolor', 'simple_s_yoko_futa'],
		'アクリルパネル シンプルデザインM横'=>['simple_m_yoko_text', 'simple_m_yoko_fullcolor', 'simple_m_yoko_futa'],
		'アクリルパネル ミュージックデザインS'=>['music_s_text', 'music_s_fullcolor', 'music_s_futa'],
		'アクリルパネル ミュージックデザインM'=>['music_m_text', 'music_m_fullcolor', 'music_m_futa'],
		'LEDスタンドパネル インスタデザイン'=>['led_insta_text', 'led_insta_fullcolor', 'led_insta_futa'],
		'LEDスタンドパネル シンプルデザイン縦'=>['led_simple_tate_text', 'led_simple_tate_fullcolor', 'led_simple_tate_futa'],
		'LEDスタンドパネル シンプルデザイン横'=>['led_simple_yoko_text', 'led_simple_yoko_fullcolor', 'led_simple_yoko_futa'],
		'LEDスタンドパネル ミュージックデザイン'=>['led_music_text', 'led_music_fullcolor', 'led_music_futa'],
		'アクリル時計黒'=>['tokei_kuro_fullcolor', 'tokei_kuro_futa'],
		'アクリル時計白'=>['tokei_shiro_fullcolor', 'tokei_shiro_futa'],
		'アクリルブロック'=>['block_fullcolor', 'block_futa'],
		'アクリルパネル カメラデザインS'=>['camera_s_text', 'camera_s_fullcolor', 'camera_s_futa'],
		'アクリルパネル カメラデザインM'=>['camera_m_text', 'camera_m_fullcolor', 'camera_m_futa'],
		'アクリルパネル カレンダーデザインS'=>['calendar_s_text', 'calendar_s_fullcolor', 'calendar_s_futa'],
		'アクリルパネル カレンダーデザインM'=>['calendar_m_text', 'calendar_m_fullcolor', 'calendar_m_futa'],
		'アクリルパネル ベビーデザインS'=>['baby_s_text', 'baby_s_fullcolor', 'baby_s_futa'],
		'アクリルパネル ベビーデザインM'=>['baby_m_text', 'baby_m_fullcolor', 'baby_m_futa'],
	];

	//個別のデザインデータ(数字は基本mm単位, font_sizeとborder_widthのみpt単位) xy座標は左上から
	const TEMPLATES = [
		//テスト用=========================================
		'test_s_text'=>[
		],	//空
		'test_s_fullcolor'=>[
			//画像(通常)
			['name'=>'写真1', 'type'=>'image', 'file'=>'', 'x'=>0, 'y'=>0, 'w'=>20, 'h'=>20],
			['name'=>'写真2', 'type'=>'image', 'file'=>'', 'x'=>20, 'y'=>0, 'w'=>20, 'h'=>20],
			['name'=>'写真3', 'type'=>'image', 'file'=>'', 'x'=>40, 'y'=>0, 'w'=>20, 'h'=>20],
			//画像(回転)
			['name'=>'写真1', 'type'=>'image', 'file'=>'', 'x'=>0, 'y'=>20, 'w'=>20, 'h'=>20, 'angle'=>45],
			['name'=>'写真2', 'type'=>'image', 'file'=>'', 'x'=>20, 'y'=>20, 'w'=>20, 'h'=>20, 'angle'=>90],
			['name'=>'写真3', 'type'=>'image', 'file'=>'', 'x'=>40, 'y'=>20, 'w'=>20, 'h'=>20, 'angle'=>180],
			//画像(マスク)
			['name'=>'写真1', 'type'=>'image', 'file'=>'', 'x'=>0, 'y'=>40, 'w'=>20, 'h'=>20, 'angle'=>0, 'mask'=>'rect', 'mx'=>5, 'my'=>45, 'mw'=>10, 'mh'=>10],
			['name'=>'写真2', 'type'=>'image', 'file'=>'', 'x'=>20, 'y'=>40, 'w'=>20, 'h'=>20, 'angle'=>0, 'mask'=>'roundedrect', 'mx'=>20, 'my'=>40, 'mw'=>20, 'mh'=>20, 'mround'=>5],
			['name'=>'写真3', 'type'=>'image', 'file'=>'', 'x'=>40, 'y'=>40, 'w'=>20, 'h'=>20, 'angle'=>0, 'mask'=>'ellipse', 'mx'=>50, 'my'=>50, 'mw'=>10, 'mh'=>10, 'mangle'=>45],
			//画像(マスク回転)
			['name'=>'写真1', 'type'=>'image', 'file'=>'', 'x'=>0, 'y'=>60, 'w'=>20, 'h'=>20, 'angle'=>0,'mask'=>'rect', 'mx'=>5, 'my'=>65, 'mw'=>10, 'mh'=>10, 'mangle'=>45],				//マスクだけ回転
			['name'=>'写真2', 'type'=>'image', 'file'=>'', 'x'=>20, 'y'=>60, 'w'=>20, 'h'=>20, 'angle'=>45, 'mask'=>'roundedrect', 'mx'=>20, 'my'=>60, 'mw'=>20, 'mh'=>20, 'mround'=>5],	//画像もマスクも回転
			//図形
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>0, 'y'=>80, 'w'=>20, 'h'=>20],
			['name'=>'黒埋め', 'type'=>'roundedrect', 'x'=>20, 'y'=>80, 'w'=>20, 'h'=>20, 'round'=>10, 'angle'=>0],		//roundは0～10(小数点で表現)
			['name'=>'黒埋め', 'type'=>'ellipse', 'x'=>50, 'y'=>90, 'w'=>10, 'h'=>10, 'angle'=>0],							//x,y:中心の座標, w,hは半径
			
			//文字(Cell)
			['name'=>'説明用', 'type'=>'text', 'value'=>'通常', 'font'=>'Shippori Mincho', 'font_size'=>10, 'font_color'=>'0,0,0', 'x'=>62, 'y'=>5, 'w'=>50, 'h'=>10, 'align'=>'L', 'auto_size'=>1],
			['name'=>'説明用', 'type'=>'text', 'value'=>'回転', 'font'=>'Shippori Mincho', 'font_size'=>10, 'font_color'=>'0,0,0', 'x'=>62, 'y'=>25, 'w'=>50, 'h'=>10, 'align'=>'L', 'auto_size'=>1],
			['name'=>'説明用', 'type'=>'text', 'value'=>'マスク', 'font'=>'Shippori Mincho', 'font_size'=>10, 'font_color'=>'0,0,0', 'x'=>62, 'y'=>45, 'w'=>50, 'h'=>10, 'align'=>'L', 'auto_size'=>1],
			['name'=>'説明用', 'type'=>'text', 'value'=>'マスクの回転', 'font'=>'Shippori Mincho', 'font_size'=>10, 'font_color'=>'0,0,0', 'x'=>62, 'y'=>65, 'w'=>50, 'h'=>10, 'align'=>'L', 'auto_size'=>1],
			['name'=>'説明用', 'type'=>'text', 'value'=>'図形(黒埋め用)', 'font'=>'Shippori Mincho', 'font_size'=>10, 'font_color'=>'0,0,0', 'x'=>62, 'y'=>85, 'w'=>50, 'h'=>10, 'align'=>'L', 'auto_size'=>1],

			//複数行文字(MultiCell)
			['name'=>'複数行テキスト', 'type'=>'multitext', 'value'=>'複数行テキストがここに入る', 'font'=>'Shippori Mincho', 'font_size'=>10, 'font_color'=>'0,0,0', 'x'=>0, 'y'=>100, 'w'=>60, 'h'=>20, 'align'=>'L'],
			
		],
		'test_s_futa'=>[
		],
		//=================================================
		//カメラS
		'camera_s_text'=>[
		],	//空
		'camera_s_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>7.392, 'y'=>31.8, 'w'=>76.216, 'h'=>65.988],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'1yearphotos', 'font'=>'Shippori Mincho', 'font_size'=>9.26, 'font_color'=>'255,255,255', 'x'=>6, 'y'=>112, 'w'=>32, 'h'=>3.743, 'align'=>'L', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'11/11', 'font'=>'Shippori Mincho', 'font_size'=>9.26, 'font_color'=>'255,255,255', 'x'=>6, 'y'=>117, 'w'=>32, 'h'=>3.743, 'align'=>'L', 'auto_size'=>1],
		],
		'camera_s_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>7.392, 'y'=>31.8, 'w'=>76.216, 'h'=>65.988],
		],
		//カメラM
		'camera_m_text'=>[
		],	//空
		'camera_m_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>10.279, 'y'=>44.8, 'w'=>107.442, 'h'=>93.024],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'1yearphotos', 'font'=>'Shippori Mincho', 'font_size'=>13.05, 'font_color'=>'255,255,255', 'x'=>8.5, 'y'=>157, 'w'=>45, 'h'=>5.278, 'align'=>'L', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'11/11', 'font'=>'Shippori Mincho', 'font_size'=>13.05, 'font_color'=>'255,255,255', 'x'=>8.5, 'y'=>164, 'w'=>45, 'h'=>5.278, 'align'=>'L', 'auto_size'=>1],
		],
		'camera_m_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>10.279, 'y'=>44.8, 'w'=>107.442, 'h'=>93.024],
		],
		//カレンダーS
		'calendar_s_text'=>[],
		'calendar_s_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>8.28, 'y'=>10.5, 'w'=>53.746, 'h'=>70.078],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'これからもずっと一緒に', 'font'=>'Shippori Mincho', 'font_size'=>14.22, 'x'=>66.5, 'y'=>11, 'w'=>56, 'h'=>6, 'align'=>'C', 'auto_size'=>1],
			['name'=>'特別な記念日', 'type'=>'calendar', 'x'=>66.5, 'y'=>25, 'date'=>'2024/1/11', 'size'=>'s'],
		],
		'calendar_s_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>8.28, 'y'=>10.5, 'w'=>53.746, 'h'=>70.078],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'これからもずっと一緒に', 'font'=>'Shippori Mincho', 'font_size'=>14.22, 'x'=>66.5, 'y'=>11, 'w'=>56, 'h'=>6, 'align'=>'C', 'auto_size'=>1],
			['name'=>'特別な記念日', 'type'=>'calendar', 'black'=>1, 'x'=>66.5, 'y'=>25, 'date'=>'2024/1/11', 'size'=>'s'],
		],
		//カレンダーM
		'calendar_m_text'=>[],
		'calendar_m_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>11.6, 'y'=>14.725, 'w'=>75.579, 'h'=>98.544],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'これからもずっと一緒に', 'font'=>'Shippori Mincho', 'font_size'=>20, 'x'=>94, 'y'=>15.5, 'w'=>78, 'h'=>10, 'align'=>'C', 'auto_size'=>1],
			['name'=>'特別な記念日', 'type'=>'calendar', 'x'=>94, 'y'=>35, 'date'=>'2024/11/11', 'size'=>'m'],
		],
		'calendar_m_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>11.6, 'y'=>14.725, 'w'=>75.579, 'h'=>98.544],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'これからもずっと一緒に', 'font'=>'Shippori Mincho', 'font_size'=>20, 'x'=>94, 'y'=>15.5, 'w'=>78, 'h'=>10, 'align'=>'C', 'auto_size'=>1],
			['name'=>'特別な記念日', 'type'=>'calendar', 'black'=>1, 'x'=>94, 'y'=>35, 'date'=>'2024/11/11', 'size'=>'m'],
		],
		//ベビーS
		'baby_s_text'=>[],	//空
		'baby_s_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>6.91, 'y'=>35.828, 'w'=>77.18, 'h'=>50.998],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Welcome!!', 'font'=>'Zen Maru Gothic', 'font_size'=>20, 'font_type'=>'B', 'font_color'=>'11,23,19,0', 'x'=>18, 'y'=>6.8, 'w'=>58, 'h'=>10, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'Haruto', 'font'=>'Zen Maru Gothic', 'font_size'=>13.34, 'font_type'=>'B', 'font_color'=>'11,23,19,0', 'x'=>18, 'y'=>25.2, 'w'=>58, 'h'=>5.067, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'Zen Maru Gothic', 'font_size'=>7.5, 'font_type'=>'B', 'font_color'=>'11,23,19,0', 'x'=>6.5, 'y'=>99, 'w'=>78, 'h'=>2.849, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'Zen Maru Gothic', 'font_size'=>7.5, 'font_type'=>'B', 'font_color'=>'11,23,19,0', 'x'=>6.5, 'y'=>103, 'w'=>78, 'h'=>2.849, 'align'=>'L', 'auto_size'=>1],
		],
		'baby_s_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>6.91, 'y'=>35.828, 'w'=>77.18, 'h'=>50.998],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Welcome!!', 'font'=>'Zen Maru Gothic', 'font_size'=>20, 'font_type'=>'B', 'x'=>14.9, 'y'=>6.8, 'w'=>61.577, 'h'=>10, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'Haruto', 'font'=>'Zen Maru Gothic', 'font_size'=>13.34, 'font_type'=>'B', 'x'=>17.8, 'y'=>25.2, 'w'=>58.705, 'h'=>5.067, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'Zen Maru Gothic', 'font_size'=>7.5, 'font_type'=>'B', 'x'=>6, 'y'=>99, 'w'=>14.625, 'h'=>2.849, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'Zen Maru Gothic', 'font_size'=>7.5, 'font_type'=>'B', 'x'=>6, 'y'=>103, 'w'=>14.625, 'h'=>2.849, 'align'=>'L', 'auto_size'=>1],
		],
		//ベビーM
		'baby_m_text'=>[],	//空
		'baby_m_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>9.538, 'y'=>50.463, 'w'=>108.924, 'h'=>71.972],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Welcome!!', 'font'=>'Zen Maru Gothic', 'font_size'=>28, 'font_type'=>'B', 'font_color'=>'11,23,19,0', 'x'=>25, 'y'=>6, 'w'=>82, 'h'=>20, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'Haruto', 'font'=>'Zen Maru Gothic', 'font_size'=>18.82, 'font_type'=>'B', 'font_color'=>'11,23,19,0', 'x'=>25, 'y'=>35, 'w'=>82, 'h'=>7.15, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'Zen Maru Gothic', 'font_size'=>10.58, 'font_type'=>'B', 'font_color'=>'11,23,19,0', 'x'=>9, 'y'=>139, 'w'=>110, 'h'=>4.022, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'Zen Maru Gothic', 'font_size'=>10.58, 'font_type'=>'B', 'font_color'=>'11,23,19,0', 'x'=>9, 'y'=>145, 'w'=>110, 'h'=>4.022, 'align'=>'L', 'auto_size'=>1],
		],
		'baby_m_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>9.538, 'y'=>50.463, 'w'=>108.924, 'h'=>71.972],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Welcome!!', 'font'=>'Zen Maru Gothic', 'font_size'=>28, 'font_type'=>'B', 'x'=>20, 'y'=>8, 'w'=>89.047, 'h'=>16, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'Haruto', 'font'=>'Zen Maru Gothic', 'font_size'=>18.82, 'font_type'=>'B', 'x'=>25, 'y'=>35, 'w'=>82, 'h'=>7.15, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'Zen Maru Gothic', 'font_size'=>10.58, 'font_type'=>'B', 'x'=>9, 'y'=>139, 'w'=>110, 'h'=>4.022, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'Zen Maru Gothic', 'font_size'=>10.58, 'font_type'=>'B', 'x'=>9, 'y'=>145, 'w'=>110, 'h'=>4.022, 'align'=>'L', 'auto_size'=>1],
		],

		//インスタS
		'insta_s_text'=>[],	//空
		'insta_s_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>11.0195, 'y'=>25, 'w'=>68.961, 'h'=>68.961],
			['name'=>'黒枠', 'type'=>'rectline', 'border_width'=>1, 'x'=>11.0195, 'y'=>25, 'w'=>68.961, 'h'=>68.961],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>15.64, 'x'=>15, 'y'=>2, 'w'=>62, 'h'=>8, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'@ ai & yuki', 'font'=>'kleeone', 'font_size'=>8.26, 'x'=>16, 'y'=>16, 'w'=>50, 'h'=>2.9, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'kleeone', 'font_size'=>7.23, 'x'=>6, 'y'=>106, 'w'=>78, 'h'=>2.55, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'kleeone', 'font_size'=>7.23, 'x'=>6, 'y'=>110, 'w'=>78, 'h'=>2.55, 'align'=>'L', 'auto_size'=>1],
		],
		'insta_s_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>11.0195, 'y'=>25, 'w'=>68.961, 'h'=>68.961],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>15.64, 'x'=>15, 'y'=>2, 'w'=>62, 'h'=>8, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'@ ai & yuki', 'font'=>'kleeone', 'font_size'=>8.26, 'x'=>16, 'y'=>16, 'w'=>50, 'h'=>2.9, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'kleeone', 'font_size'=>7.23, 'x'=>6, 'y'=>106, 'w'=>78, 'h'=>2.55, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'kleeone', 'font_size'=>7.23, 'x'=>6, 'y'=>110, 'w'=>78, 'h'=>2.55, 'align'=>'L', 'auto_size'=>1],
		],
		//インスタM
		'insta_m_text'=>[],	//空
		'insta_m_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>16, 'y'=>35.25, 'w'=>97, 'h'=>97],
			['name'=>'黒枠', 'type'=>'rectline', 'border_width'=>1, 'x'=>16, 'y'=>35.25, 'w'=>97, 'h'=>97],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>22, 'x'=>22, 'y'=>2.8, 'w'=>86, 'h'=>12, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'@ ai & yuki', 'font'=>'kleeone', 'font_size'=>11.62, 'x'=>24.225, 'y'=>22.5, 'w'=>80, 'h'=>4.082, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'kleeone', 'font_size'=>10.16, 'x'=>9.594, 'y'=>149, 'w'=>80, 'h'=>3.572, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'kleeone', 'font_size'=>10.16, 'x'=>9.594, 'y'=>155, 'w'=>80, 'h'=>3.572, 'align'=>'L', 'auto_size'=>1],
		],
		'insta_m_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>16, 'y'=>35.25, 'w'=>97, 'h'=>97],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>22, 'x'=>22, 'y'=>2.8, 'w'=>86, 'h'=>12, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'@ ai & yuki', 'font'=>'kleeone', 'font_size'=>11.62, 'x'=>24.225, 'y'=>22.5, 'w'=>80, 'h'=>4.082, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'kleeone', 'font_size'=>10.16, 'x'=>9.594, 'y'=>149, 'w'=>80, 'h'=>3.572, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'kleeone', 'font_size'=>10.16, 'x'=>9.594, 'y'=>155, 'w'=>80, 'h'=>3.572, 'align'=>'L', 'auto_size'=>1],
		],
		//シンプルS縦
		'simple_s_tate_text'=>[
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Happy Birthday', 'font'=>'kleeone', 'font_size'=>21.33, 'x'=>5, 'y'=>86, 'w'=>81, 'h'=>7.5, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>102, 'w'=>81, 'h'=>5, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>109, 'w'=>81, 'h'=>5, 'align'=>'C', 'auto_size'=>1],
		],
		'simple_s_tate_fullcolor'=>[
			['name'=>'写真縦', 'type'=>'image', 'file'=>'', 'x'=>17.066, 'y'=>7, 'w'=>56.868, 'h'=>71.085],
		],
		'simple_s_tate_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>17.066, 'y'=>7, 'w'=>56.868, 'h'=>71.085],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Happy Birthday', 'font'=>'kleeone', 'font_size'=>21.33, 'x'=>5, 'y'=>86, 'w'=>81, 'h'=>7.5, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>102, 'w'=>81, 'h'=>5, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>109, 'w'=>81, 'h'=>5, 'align'=>'C', 'auto_size'=>1],
		],
		//シンプルS横
		'simple_s_yoko_text'=>[
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Happy Birthday', 'font'=>'kleeone', 'font_size'=>21.33, 'x'=>5, 'y'=>79.5, 'w'=>81, 'h'=>7.5, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>93.5, 'w'=>81, 'h'=>5, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>101.25, 'w'=>81, 'h'=>5, 'align'=>'C', 'auto_size'=>1],
		],
		'simple_s_yoko_fullcolor'=>[
			['name'=>'写真横', 'type'=>'image', 'file'=>'', 'x'=>9.957, 'y'=>11.58, 'w'=>71.086, 'h'=>56.869],
		],
		'simple_s_yoko_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>9.957, 'y'=>11.58, 'w'=>71.086, 'h'=>56.869],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Happy Birthday', 'font'=>'kleeone', 'font_size'=>21.33, 'x'=>5, 'y'=>79.5, 'w'=>81, 'h'=>7.5, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>93.5, 'w'=>81, 'h'=>5, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>101.25, 'w'=>81, 'h'=>5, 'align'=>'C', 'auto_size'=>1],
		],
		//シンプルM縦
		'simple_m_tate_text'=>[
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Happy Birthday', 'font'=>'kleeone', 'font_size'=>30, 'x'=>10, 'y'=>122.8, 'w'=>108, 'h'=>10.542, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>20, 'x'=>10, 'y'=>142.6, 'w'=>108, 'h'=>7.028, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>20, 'x'=>10, 'y'=>153.5, 'w'=>108, 'h'=>7.028, 'align'=>'C', 'auto_size'=>1],
		],
		'simple_m_tate_fullcolor'=>[
			['name'=>'写真縦', 'type'=>'image', 'file'=>'', 'x'=>24, 'y'=>16.29, 'w'=>80, 'h'=>100],
		],
		'simple_m_tate_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>24, 'y'=>16.29, 'w'=>80, 'h'=>100],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Happy Birthday', 'font'=>'kleeone', 'font_size'=>30, 'x'=>10, 'y'=>122.8, 'w'=>108, 'h'=>10.542, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>20, 'x'=>10, 'y'=>142.6, 'w'=>108, 'h'=>7.028, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>20, 'x'=>10, 'y'=>153.5, 'w'=>108, 'h'=>7.028, 'align'=>'C', 'auto_size'=>1],
		],
		//シンプルM横
		'simple_m_yoko_text'=>[
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Happy Birthday', 'font'=>'kleeone', 'font_size'=>30, 'x'=>10, 'y'=>112, 'w'=>108, 'h'=>10.542, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>20, 'x'=>10, 'y'=>131.6, 'w'=>108, 'h'=>7.028, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>20, 'x'=>10, 'y'=>142.4, 'w'=>108, 'h'=>7.028, 'align'=>'C', 'auto_size'=>1],
		],
		'simple_m_yoko_fullcolor'=>[
			['name'=>'写真横', 'type'=>'image', 'file'=>'', 'x'=>14, 'y'=>16.29, 'w'=>100, 'h'=>80],
		],
		'simple_m_yoko_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>14, 'y'=>16.29, 'w'=>100, 'h'=>80],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'Happy Birthday', 'font'=>'kleeone', 'font_size'=>30, 'x'=>10, 'y'=>112, 'w'=>108, 'h'=>10.542, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>20, 'x'=>10, 'y'=>131.6, 'w'=>108, 'h'=>7.028, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>20, 'x'=>10, 'y'=>142.4, 'w'=>108, 'h'=>7.028, 'align'=>'C', 'auto_size'=>1],
		],
		//ミュージックS
		'music_s_text'=>[],	//空
		'music_s_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>11.042, 'y'=>12, 'w'=>68.916, 'h'=>68.916],
			['name'=>'黒枠', 'type'=>'rectline', 'border_width'=>2, 'x'=>11.042, 'y'=>12, 'w'=>68.916, 'h'=>68.916],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>15.63, 'x'=>14.5, 'y'=>84.2, 'w'=>64, 'h'=>5.496, 'align'=>'C', 'auto_size'=>1],
			['name'=>'時間（始まり）1', 'type'=>'text', 'value'=>'00:00', 'font'=>'kleeone', 'font_size'=>8.2, 'x'=>4.5, 'y'=>96.2, 'w'=>82, 'h'=>5.496, 'align'=>'L'],
			['name'=>'時間（終わり）2', 'type'=>'text', 'value'=>'12:12', 'font'=>'kleeone', 'font_size'=>8.2, 'x'=>4.5, 'y'=>96.2, 'w'=>82, 'h'=>5.496, 'align'=>'R'],
			['name'=>'名前3', 'type'=>'text', 'value'=>'Masaki & Aina', 'font'=>'kleeone', 'font_size'=>12.8, 'x'=>14.5, 'y'=>100, 'w'=>64, 'h'=>5.496, 'align'=>'C'],
		],
		'music_s_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>11.042, 'y'=>12, 'w'=>68.916, 'h'=>68.916],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>15.63, 'x'=>14.5, 'y'=>84.2, 'w'=>64, 'h'=>5.496, 'align'=>'C', 'auto_size'=>1],
			['name'=>'時間（始まり）1', 'type'=>'text', 'value'=>'00:00', 'font'=>'kleeone', 'font_size'=>8.2, 'x'=>4.5, 'y'=>96.2, 'w'=>82, 'h'=>5.496, 'align'=>'L'],
			['name'=>'時間（終わり）2', 'type'=>'text', 'value'=>'12:12', 'font'=>'kleeone', 'font_size'=>8.2, 'x'=>4.5, 'y'=>96.2, 'w'=>82, 'h'=>5.496, 'align'=>'R'],
			['name'=>'名前3', 'type'=>'text', 'value'=>'Masaki & Aina', 'font'=>'kleeone', 'font_size'=>12.8, 'x'=>14.5, 'y'=>100, 'w'=>64, 'h'=>5.496, 'align'=>'C'],
		],
		//ミュージックM
		'music_m_text'=>[],	//空
		'music_m_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>15.5, 'y'=>17.3, 'w'=>97, 'h'=>97],
			['name'=>'黒枠', 'type'=>'rectline', 'border_width'=>2, 'x'=>15.5, 'y'=>17.3, 'w'=>97, 'h'=>97],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>22, 'x'=>17, 'y'=>118.5, 'w'=>94, 'h'=>5.496, 'align'=>'C', 'auto_size'=>1],
			['name'=>'時間（始まり）1', 'type'=>'text', 'value'=>'00:00', 'font'=>'kleeone', 'font_size'=>11.54, 'x'=>6, 'y'=>135.42, 'w'=>116, 'h'=>4.618, 'align'=>'L'],
			['name'=>'時間（終わり）2', 'type'=>'text', 'value'=>'12:12', 'font'=>'kleeone', 'font_size'=>11.54, 'x'=>6, 'y'=>135.42, 'w'=>116, 'h'=>4.618, 'align'=>'R'],
			['name'=>'名前3', 'type'=>'text', 'value'=>'Masaki & Aina', 'font'=>'kleeone', 'font_size'=>17, 'x'=>17, 'y'=>140.5, 'w'=>94, 'h'=>5.496, 'align'=>'C'],
		],
		'music_m_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>15.5, 'y'=>17.3, 'w'=>97, 'h'=>97],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>22, 'x'=>17, 'y'=>118.5, 'w'=>94, 'h'=>5.496, 'align'=>'C', 'auto_size'=>1],
			['name'=>'時間（始まり）1', 'type'=>'text', 'value'=>'00:00', 'font'=>'kleeone', 'font_size'=>11.54, 'x'=>6, 'y'=>135.42, 'w'=>116, 'h'=>4.618, 'align'=>'L'],
			['name'=>'時間（終わり）2', 'type'=>'text', 'value'=>'12:12', 'font'=>'kleeone', 'font_size'=>11.54, 'x'=>6, 'y'=>135.42, 'w'=>116, 'h'=>4.618, 'align'=>'R'],
			['name'=>'名前3', 'type'=>'text', 'value'=>'Masaki & Aina', 'font'=>'kleeone', 'font_size'=>17, 'x'=>17, 'y'=>140.5, 'w'=>94, 'h'=>5.496, 'align'=>'C'],
		],
		//LEDインスタ
		'led_insta_text'=>[
			['name'=>'黒枠', 'type'=>'rectline', 'border_width'=>1, 'x'=>15, 'y'=>26.5, 'w'=>61, 'h'=>61],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>14.97, 'x'=>18, 'y'=>4.3, 'w'=>55, 'h'=>8, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'@ID', 'font'=>'kleeone', 'font_size'=>7.29, 'x'=>20, 'y'=>18.5, 'w'=>50, 'h'=>2.9, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'kleeone', 'font_size'=>6.38, 'x'=>11, 'y'=>101, 'w'=>69, 'h'=>2.5, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'kleeone', 'font_size'=>6.38, 'x'=>11, 'y'=>105, 'w'=>69, 'h'=>2.5, 'align'=>'L', 'auto_size'=>1],
		],
		'led_insta_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>15, 'y'=>26.5, 'w'=>61, 'h'=>61],
		],
		'led_insta_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>15, 'y'=>26.5, 'w'=>61, 'h'=>61],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>14.97, 'x'=>18, 'y'=>4.3, 'w'=>55, 'h'=>8, 'align'=>'C', 'auto_size'=>1],
			['name'=>'ID', 'type'=>'text', 'value'=>'@ID', 'font'=>'kleeone', 'font_size'=>7.29, 'x'=>20, 'y'=>18.5, 'w'=>50, 'h'=>2.9, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ1段目', 'type'=>'text', 'value'=>'ハッシュタグ1', 'font'=>'kleeone', 'font_size'=>6.38, 'x'=>11, 'y'=>101, 'w'=>69, 'h'=>2.5, 'align'=>'L', 'auto_size'=>1],
			['name'=>'ハッシュタグ2段目', 'type'=>'text', 'value'=>'ハッシュタグ2', 'font'=>'kleeone', 'font_size'=>6.38, 'x'=>11, 'y'=>105, 'w'=>69, 'h'=>2.5, 'align'=>'L', 'auto_size'=>1],
		],
		//LEDシンプル縦
		'led_simple_tate_text'=>[
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>24, 'x'=>5, 'y'=>84.5, 'w'=>81, 'h'=>9.23, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>15, 'x'=>5, 'y'=>98, 'w'=>81, 'h'=>5.773, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>14, 'x'=>5, 'y'=>105, 'w'=>81, 'h'=>5.388, 'align'=>'C', 'auto_size'=>1],
		],
		'led_simple_tate_fullcolor'=>[
			['name'=>'写真縦', 'type'=>'image', 'file'=>'', 'x'=>17.5, 'y'=>7, 'w'=>56, 'h'=>70],
		],
		'led_simple_tate_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>17.5, 'y'=>7, 'w'=>56, 'h'=>70],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>24, 'x'=>5, 'y'=>84.5, 'w'=>81, 'h'=>9.23, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>15, 'x'=>5, 'y'=>98, 'w'=>81, 'h'=>5.773, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>14, 'x'=>5, 'y'=>105, 'w'=>81, 'h'=>5.388, 'align'=>'C', 'auto_size'=>1],
		],
		//LEDシンプル横
		'led_simple_yoko_text'=>[
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>21.33, 'x'=>5, 'y'=>79.5, 'w'=>81, 'h'=>9.23, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>93.5, 'w'=>81, 'h'=>5.773, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>101.25, 'w'=>81, 'h'=>5.388, 'align'=>'C', 'auto_size'=>1],
		],
		'led_simple_yoko_fullcolor'=>[
			['name'=>'写真横', 'type'=>'image', 'file'=>'', 'x'=>9.957, 'y'=>11.5, 'w'=>71.086, 'h'=>56.868],
		],
		'led_simple_yoko_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>9.957, 'y'=>11.5, 'w'=>71.086, 'h'=>56.868],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>21.33, 'x'=>5, 'y'=>79.5, 'w'=>81, 'h'=>9.23, 'align'=>'C', 'auto_size'=>1],
			['name'=>'名前2', 'type'=>'text', 'value'=>'Mai & Akihiro', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>93.5, 'w'=>81, 'h'=>5.773, 'align'=>'C', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>14.22, 'x'=>5, 'y'=>101.25, 'w'=>81, 'h'=>5.388, 'align'=>'C', 'auto_size'=>1],
		],
		//LEDミュージック
		'led_music_text'=>[
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>8.42, 'x'=>21, 'y'=>70.5, 'w'=>49, 'h'=>3.24, 'align'=>'L', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>7, 'x'=>21, 'y'=>75, 'w'=>49, 'h'=>2.694, 'align'=>'L'],
		],
		'led_music_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>19, 'y'=>12.5, 'w'=>53, 'h'=>53],
		],
		'led_music_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>19, 'y'=>12.5, 'w'=>53, 'h'=>53],
			['name'=>'タイトル', 'type'=>'text', 'value'=>'I love you', 'font'=>'kleeone', 'font_size'=>8.42, 'x'=>21, 'y'=>70.5, 'w'=>49, 'h'=>3.24, 'align'=>'L', 'auto_size'=>1],
			['name'=>'日付3', 'type'=>'text', 'value'=>'2023.12.15', 'font'=>'kleeone', 'font_size'=>7, 'x'=>21, 'y'=>75, 'w'=>49, 'h'=>2.694, 'align'=>'L'],
		],
		//時計黒
		'tokei_kuro_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>5, 'y'=>5, 'w'=>90, 'h'=>90],
			['name'=>'パーツ', 'type'=>'pdf', 'file'=>'tokei_kuro_parts.pdf'],
		],
		'tokei_kuro_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>5, 'y'=>5, 'w'=>90, 'h'=>90],
		],
		//時計白
		'tokei_shiro_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>5, 'y'=>5, 'w'=>90, 'h'=>90],
			['name'=>'パーツ', 'type'=>'pdf', 'file'=>'tokei_shiro_parts.pdf'],
		],
		'tokei_shiro_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>5, 'y'=>5, 'w'=>90, 'h'=>90],
		],
		//ブロック
		'block_fullcolor'=>[
			['name'=>'写真', 'type'=>'image', 'file'=>'', 'x'=>0, 'y'=>0, 'w'=>103, 'h'=>103],
		],
		'block_futa'=>[
			['name'=>'黒埋め', 'type'=>'rect', 'x'=>0, 'y'=>0, 'w'=>103, 'h'=>103],
		],
	];

	//印刷用にまとめるデザインのグループ分け
	const DRAFTS = [
		's_text'=>['test_s_text', 'insta_s_text', 'simple_s_tate_text', 'simple_s_yoko_text', 'music_s_text', 'led_insta_text', 'led_simple_tate_text', 'led_simple_yoko_text', 'led_music_text', 'camera_s_text', 'calendar_s_text', 'baby_s_text'],
		's_fullcolor'=>['test_s_fullcolor', 'insta_s_fullcolor', 'simple_s_tate_fullcolor', 'simple_s_yoko_fullcolor', 'music_s_fullcolor', 'led_insta_fullcolor', 'led_simple_tate_fullcolor', 'led_simple_yoko_fullcolor', 'led_music_fullcolor', 'camera_s_fullcolor', 'calendar_s_fullcolor', 'baby_s_fullcolor'],
		's_futa'=>['test_s_futa', 'insta_s_futa', 'simple_s_tate_futa', 'simple_s_yoko_futa', 'music_s_futa', 'led_insta_futa', 'led_simple_tate_futa', 'led_simple_yoko_futa', 'led_music_futa', 'camera_s_futa', 'calendar_s_futa', 'baby_s_futa'],					//順番を合わせるためs_futaとs_led_futaは同じにする
		's_led_futa'=>['test_s_futa', 'insta_s_futa', 'simple_s_tate_futa', 'simple_s_yoko_futa', 'music_s_futa', 'led_insta_futa', 'led_simple_tate_futa', 'led_simple_yoko_futa', 'led_music_futa', 'camera_s_futa', 'calendar_s_futa', 'baby_s_futa'],				//順番を合わせるためs_futaとs_led_futaは同じにする
		'm_text'=>['insta_m_text', 'simple_m_tate_text', 'simple_m_yoko_text', 'music_m_text', 'camera_m_text', 'calendar_m_text', 'baby_m_text'],
		'm_fullcolor'=>['insta_m_fullcolor', 'simple_m_tate_fullcolor', 'simple_m_yoko_fullcolor', 'music_m_fullcolor', 'camera_m_fullcolor', 'calendar_m_fullcolor', 'baby_m_fullcolor'],
		'm_futa'=>['insta_m_futa', 'simple_m_tate_futa', 'simple_m_yoko_futa', 'music_m_futa', 'camera_m_futa', 'calendar_m_futa', 'baby_m_futa'],
		'tokei_fullcolor' => ['tokei_kuro_fullcolor', 'tokei_shiro_fullcolor'],
		'tokei_futa' => ['tokei_kuro_futa', 'tokei_shiro_futa'],
		'block_fullcolor'=>['block_fullcolor'],
		'block_futa'=>['block_futa'],
	];

	//印刷用にまとめる際の配置座標(順番、個数もここで決まる) x,y座標は中央からの値
	const DRAFTS_XYS = [
		's_text'=>[[46.686,209.207],[45.9,64.875],[45.816,-74.5],[46.816,-211.8],[47,-349.252],[-54.517,209.777],[-54.717,66.245],[-54.977,-74],[-54,-211.1],[-54,-348.3],[-154,209.207],[-154.5,65],[-154.3,-74.5],[-153,-212],[-153,-349.252]],
		's_fullcolor'=>[[46.686,209.207],[45.9,64.875],[45.816,-74.5],[46.816,-211.8],[47,-349.252],[-54.517,209.777],[-54.717,66.245],[-54.977,-74],[-54,-211.1],[-54,-348.3],[-154,209.207],[-154.5,65],[-154.3,-74.5],[-153,-212],[-153,-349.252]],		//s_textと同じ
		's_futa'=>[[46.686,209.207],[45.9,64.875],[45.816,-74.5],[46.816,-211.8],[47,-349.252],[-54.517,209.777],[-54.717,66.245],[-54.977,-74],[-54,-211.1],[-54,-348.3],[-154,209.207],[-154.5,65],[-154.3,-74.5],[-153,-212],[-153,-349.252]],			//s_textと同じ
		's_led_futa'=>[[46.686,209.207],[45.9,64.875],[45.816,-74.5],[46.816,-211.8],[47,-349.252],[-54.517,209.777],[-54.717,66.245],[-54.977,-74],[-54,-211.1],[-54,-348.3],[-154,209.207],[-154.5,65],[-154.3,-74.5],[-153,-212],[-153,-349.252]],			//s_textと同じ
		'm_text'=>[[1,195.5],[1,4],[1,-188],[1.5,-378],[-146,195.5],[-146,4],[-146,-188],[-145.5,-378]],
		'm_fullcolor'=>[[1,195.5],[1,4],[1,-188],[1.5,-378],[-146,195.5],[-146,4],[-146,-188],[-145.5,-378]],		//m_textと同じ
		'm_futa'=>[[1,195.5],[1,4],[1,-188],[1.5,-378],[-146,195.5],[-146,4],[-146,-188],[-145.5,-378]],			//m_textと同じ
		'tokei_fullcolor'=>[[53.5, 254.5],[53.5,150.5],[53.5,47],[53.5,-57],[53.5,-161],[53.5,-265],[53.5,-369],[-58.5, 254.5],[-58.5,150.5],[-58.5,47],[-58.5,-57],[-58.5,-161],[-58.5,-265],[-58.5,-369]],
		'tokei_futa'=>[[53.5, 254.5],[53.5,150.5],[53.5,47],[53.5,-57],[53.5,-161],[53.5,-265],[53.5,-369],[-58.5, 254.5],[-58.5,150.5],[-58.5,47],[-58.5,-57],[-58.5,-161],[-58.5,-265],[-58.5,-369]],			//tokei_kuro_fullcolorと同じ
		'block_fullcolor'=>[[52.0, 253.0],[52.0,149.0],[52.0,45.5],[52.0,-58.5],[52.0,-162.5],[52.0,-266.5],[52.0,-370.5],[-60.0, 253.0],[-60.0,149.0],[-60.0,45.5],[-60.0,-58.5],[-60.0,-162.5],[-60.0,-266.5],[-60.0,-370.5]],	
		'block_futa'=>[[52.0, 253.0],[52.0,149.0],[52.0,45.5],[52.0,-58.5],[52.0,-162.5],[52.0,-266.5],[52.0,-370.5],[-60.0, 253.0],[-60.0,149.0],[-60.0,45.5],[-60.0,-58.5],[-60.0,-162.5],[-60.0,-266.5],[-60.0,-370.5]],			//block_fullcolorと同じ
	];

	//フォントの種類
	const FONTS = [
		'1'=>'Klee One',
		'2'=>'Zen Maru Gothic',
		'3'=>'Shippori Mincho',
		'4'=>'TAoonishi',
		'5'=>'Alex Brush',
		'6'=>'BaskOldFace',
	];


?>
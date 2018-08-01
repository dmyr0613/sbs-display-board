<?php

// Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';

// アクセストークンを使いCurlHTTPClientをインスタンス化
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
// CurlHTTPClientとシークレットを使いLINEBotをインスタンス化
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
// LINE Messaging APIがリクエストに付与した署名を取得
$signature = $_SERVER['HTTP_' . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

// 署名が正当かチェック。正当であればリクエストをパースし配列へ
// 不正であれば例外の内容を出力
try {
  $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
} catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log('parseEventRequest failed. InvalidSignatureException => '.var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log('parseEventRequest failed. UnknownEventTypeException => '.var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log('parseEventRequest failed. UnknownMessageTypeException => '.var_export($e, true));
} catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log('parseEventRequest failed. InvalidEventRequestException => '.var_export($e, true));
}

// 配列に格納された各イベントをループで処理
foreach ($events as $event) {
  //ユーザIDを表示
  error_log($event->getUserID());

  // MessageEventクラスのインスタンスでなければ処理をスキップ
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
    error_log('Non message event has come');
    continue;
  }
  /*
  // TextMessageクラスのインスタンスでなければ処理をスキップ
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    error_log('Non text message has come');
    continue;
  }
  */
  // オウム返し
  //$bot->replyText($event->getReplyToken(), $event->getText());

  if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
    //入力されたテキストを取得
    $SectionName = $event->getText();
  }

  if ($SectionName == '病院からのお知らせ'){
    //リッチメニューから「病院からのお知らせ」
    $messageStr = '外来診療日：月曜日～金曜日（祝日年末年始を除く） ';
    $messageStr = $messageStr . "\r\n" . '午前：08:00～11:00';
    $messageStr = $messageStr . "\r\n" . '午後：12:00～15:00（予約のみ）';
    $messageStr = $messageStr . "\r\n";
    $messageStr = $messageStr . "\r\n" . '※初診の場合は、かかりつけ医からの当院宛の紹介状をお持ちください。';
    $bot->replyText($event->getReplyToken(), $messageStr);
  } elseif($SectionName == '電話連絡') {
    //リッチメニューから「電話連絡」
    $messageStr = '電話番号：';
    $messageStr = $messageStr . "\r\n" . '054-283-1450（代表）';
    $messageStr = $messageStr . "\r\n";
    $messageStr = $messageStr . "\r\n" . '予約受付時間：';
    $messageStr = $messageStr . "\r\n" . '平日 10:00～17:00';
    $messageStr = $messageStr . "\r\n";
    $messageStr = $messageStr . "\r\n" . 'ホームページ：';
    $messageStr = $messageStr . "\r\n" . 'http://www.sbs-infosys.co.jp/';
    $bot->replyText($event->getReplyToken(), $messageStr);

  } else {

    //入力された診療科から診療科コードを取得
    $section_id = 0;
    if ($SectionName=='内科'){
      $section_id = 2;
    } elseif ($SectionName=='消化器内科') {
      $section_id = 4;
    } elseif ($SectionName=='神経内科') {
      $section_id = 8;
    } elseif ($SectionName=='腎臓内科') {
      $section_id = 9;
    } elseif ($SectionName=='小児科') {
      $section_id = 15;
    } elseif ($SectionName=='外科') {
      $section_id = 20;
    } elseif ($SectionName=='形成外科') {
      $section_id = 22;
    } elseif ($SectionName=='整形外科') {
      $section_id = 21;
    } elseif ($SectionName=='皮膚科') {
      $section_id = 27;
    } elseif ($SectionName=='泌尿器科') {
      $section_id = 28;
    } elseif ($SectionName=='産婦人科') {
      $section_id = 29;
    } elseif ($SectionName=='眼科') {
      $section_id = 31;
    } elseif ($SectionName=='耳鼻科') {
      $section_id = 32;
    } elseif ($SectionName=='歯科口腔外科') {
      $section_id = 40;
    }

    if ($section_id > 0) {
      error_log("同じ診療科が存在した");
      // PrimeKarte APIにアクセスし診察待ち状況を取得

      //時間を取得
      date_default_timezone_set('Asia/Tokyo');
      $reqtime = date("His");
      error_log($reqtime);

      if ($reqtime > '140000' or $reqtime < '083000') {
        error_log("診察時間外のため、テスト的に10:30固定で問合せ");
        $reqtime = '103000';
      }

      $jsonString = file_get_contents('http://35.190.234.51/displaybd/db/last/0000000001/' . $section_id . '/20180507/000000/' . $reqtime);
      //$jsonString = file_get_contents('https://primearch.jp/displaybd/db/last/0000000001/' . $section_id . '/20180507/000000/' . $reqtime);

      // 文字列を連想配列に変換
      $obj = json_decode($jsonString, true);
      $messageStr = $SectionName . 'の診察状況';
      foreach ($obj as $key => $val){
        error_log($key);
        $messageStr = $messageStr . "\r\n";
        $messageStr = $messageStr . "\r\n" . '診察室：' . $val["rName"];
        $messageStr = $messageStr . "\r\n" . '現在診察中：' . $val["curNo"];
        $messageStr = $messageStr . "\r\n" . 'もうすぐ呼ばれる方：' . "\r\n" . $val["waitNo01"];
        if ($val["waitNo02"]>0) {
          $messageStr = $messageStr . '、' . $val["waitNo02"];
        }
        if ($val["waitNo03"]>0) {
          $messageStr = $messageStr . '、' . $val["waitNo03"];
        }
      }
      $bot->replyText($event->getReplyToken(), $messageStr);
    }
    //診療科が見つからない場合は、リストを返す
    if($section_id==0) {
      error_log("同じ診療科が存在しなかった");
      // アクションの配列
      //$suggestArray = array('内科','外科','整形');
      //$suggestArray = array('内科','消化器内科','神経内科','腎臓内科','小児科','外科','形成外科','整形外科','皮膚科','泌尿器科','産婦人科','眼科','耳鼻科','歯科口腔外科');
      $suggestArray = array('内科','消化器内科','小児科','外科');
      $actionArray = array();
      //候補を全てアクションにして追加
      foreach($suggestArray as $secname) {
        array_push($actionArray, new LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder ($secname, $secname));
        error_log($secname);
      }

      if($SectionName=='診療科を選択') {
        //リッチメニューから「診療科を選択」
        $messageTitle = '診察状況をお知らせします。';
      } else {
        //入力された診療科が見つからない。
        $messageTitle = '指定された診療科が見つかりませんでした。';
      }

      // Buttonsテンプレートを返信
      $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
        '見つかりませんでした。',
        new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder ($messageTitle, '診療科を選択してください。', null, $actionArray));
        $bot->replyMessage($event->getReplyToken(), $builder);
    }
  }
}

// テキストを返信。引数はLINEBot、返信先、テキスト
function replyTextMessage($bot, $replyToken, $text) {
  // 返信を行いレスポンスを取得
  // TextMessageBuilderの引数はテキスト
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));
  // レスポンスが異常な場合
  if (!$response->isSucceeded()) {
    // エラー内容を出力
    error_log('Failed! '. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// 画像を返信。引数はLINEBot、返信先、画像URL、サムネイルURL
function replyImageMessage($bot, $replyToken, $originalImageUrl, $previewImageUrl) {
  // ImageMessageBuilderの引数は画像URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($originalImageUrl, $previewImageUrl));
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// 位置情報を返信。引数はLINEBot、返信先、タイトル、住所、
// 緯度、経度
function replyLocationMessage($bot, $replyToken, $title, $address, $lat, $lon) {
  // LocationMessageBuilderの引数はダイアログのタイトル、住所、緯度、経度
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($title, $address, $lat, $lon));
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// スタンプを返信。引数はLINEBot、返信先、
// スタンプのパッケージID、スタンプID
function replyStickerMessage($bot, $replyToken, $packageId, $stickerId) {
  // StickerMessageBuilderの引数はスタンプのパッケージID、スタンプID
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder($packageId, $stickerId));
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// 動画を返信。引数はLINEBot、返信先、動画URL、サムネイルURL
function replyVideoMessage($bot, $replyToken, $originalContentUrl, $previewImageUrl) {
  // VideoMessageBuilderの引数は動画URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\VideoMessageBuilder($originalContentUrl, $previewImageUrl));
  if (!$response->isSucceeded()) {
    error_log('Failed! '. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// オーディオファイルを返信。引数はLINEBot、返信先、
// ファイルのURL、ファイルの再生時間
function replyAudioMessage($bot, $replyToken, $originalContentUrl, $audioLength) {
  // AudioMessageBuilderの引数はファイルのURL、ファイルの再生時間
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\AudioMessageBuilder($originalContentUrl, $audioLength));
  if (!$response->isSucceeded()) {
    error_log('Failed! '. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// 複数のメッセージをまとめて返信。引数はLINEBot、
// 返信先、メッセージ(可変長引数)
function replyMultiMessage($bot, $replyToken, ...$msgs) {
  // MultiMessageBuilderをインスタンス化
  $builder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
  // ビルダーにメッセージを全て追加
  foreach($msgs as $value) {
    $builder->add($value);
  }
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// Buttonsテンプレートを返信。引数はLINEBot、返信先、代替テキスト、
// 画像URL、タイトル、本文、アクション(可変長引数)
function replyButtonsTemplate($bot, $replyToken, $alternativeText, $imageUrl, $title, $text, ...$actions) {
  // アクションを格納する配列
  $actionArray = array();
  // アクションを全て追加
  foreach($actions as $value) {
    array_push($actionArray, $value);
  }
  // TemplateMessageBuilderの引数は代替テキスト、ButtonTemplateBuilder
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // ButtonTemplateBuilderの引数はタイトル、本文、
    // 画像URL、アクションの配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder ($title, $text, $imageUrl, $actionArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// Confirmテンプレートを返信。引数はLINEBot、返信先、代替テキスト、
// 本文、アクション(可変長引数)
function replyConfirmTemplate($bot, $replyToken, $alternativeText, $text, ...$actions) {
  $actionArray = array();
  foreach($actions as $value) {
    array_push($actionArray, $value);
  }
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // Confirmテンプレートの引数はテキスト、アクションの配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder ($text, $actionArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// Carouselテンプレートを返信。引数はLINEBot、返信先、代替テキスト、
// ダイアログの配列
function replyCarouselTemplate($bot, $replyToken, $alternativeText, $columnArray) {
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
  $alternativeText,
  // Carouselテンプレートの引数はダイアログの配列
  new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder (
   $columnArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

?>

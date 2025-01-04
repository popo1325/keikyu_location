<?php
$positionCsvPath = 'position.csv';
$kindCsvPath = 'kind.csv';

$trainApiUrl = 'https://app-kq.net/api/train';
$trainTimeApiUrl = 'https://app-kq.net/api/trainTime';

try {
    function loadCsvToArray($csvFilePath)
    {
        if (!file_exists($csvFilePath) || !is_readable($csvFilePath)) {
            throw new Exception("$csvFilePath が見つからないか、読み取れません。");
        }
        $csvData = array_map('str_getcsv', file($csvFilePath));
        $header = array_shift($csvData);
        $result = [];
        foreach ($csvData as $row) {
            $result[$row[0]] = $row; // $row[0]がキー, 全体を値として格納
        }
        return $result;
    }

    $positions = loadCsvToArray($positionCsvPath);
    $kinds = loadCsvToArray($kindCsvPath);

    // train APIからデータを取得
    $ch = curl_init($trainApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $trainResponse = curl_exec($ch);
    if ($trainResponse === false) {
        throw new Exception('train APIリクエストエラー: ' . curl_error($ch));
    }
    $trainHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($trainHttpCode !== 200) {
        throw new Exception("train APIエラー: ステータスコード $trainHttpCode");
    }

    $trainData = json_decode($trainResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSONデコードエラー: ' . json_last_error_msg());
    }

    foreach ($trainData as &$train) {
        if (!isset($train['train_no'], $train['direction'], $train['position'], $train['train_kind'])) {
            continue; // 必要なデータが揃っていない場合はスキップ
        }

        $trainNo = $train['train_no'];
        $direction = $train['direction'] - 1;
        $positionKey = $train['position'];
        $trainKindKey = $train['train_kind'];

        // positionを駅名に置き換える
        if (isset($positions[$positionKey])) {
            $train['position'] = $positions[$positionKey][1]; // 駅名に置き換え
        } else {
            $train['position'] = ""; // デフォルト値
        }

        // train_kindをkind.csvの値に置き換える
        if (isset($kinds[$trainKindKey])) {
            $train['train_kind'] = $kinds[$trainKindKey][1]; // 種別名に置き換え
        } else {
            $train['train_kind'] = ""; // デフォルト値
        }

        // trainTime APIにリクエストを送信
        $lineNo = $positions[$positionKey][2] ?? null; // line_noを取得
        if ($lineNo) {
            $queryParams = http_build_query([
                'direction' => $direction,
                'line_no' => $lineNo,
                'train_no' => $trainNo,
            ]);

            $ch = curl_init("$trainTimeApiUrl?$queryParams");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $trainTimeResponse = curl_exec($ch);
            $trainTimeHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // trainTime APIがエラーでもスキップして続行
            if ($trainTimeResponse === false || $trainTimeHttpCode !== 200) {
                $train['to'] = ""; // エラーが発生した場合はデフォルト値
            } else {
                // trainTime APIのレスポンスをデコード
                $trainTimeData = json_decode($trainTimeResponse, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // 必要な情報を `to` に追加
                    $train['to'] = $trainTimeData['info']['to'] ?? "行き先不明";
                } else {
                    $train['to'] = ""; // デコードエラーの場合のデフォルト値
                }
            }
        } else {
            $train['to'] = "";
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($trainData);

} catch (Exception $e) {
    // エラーレスポンスを返す
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()]);
}

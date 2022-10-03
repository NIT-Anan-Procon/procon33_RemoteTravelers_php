<?php

namespace App\Http\Controllers\API;

use App\Models\Situation;
use App\Models\Report;
use App\Models\Travel;
use App\Models\Location;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TravelerController extends Controller
{
    public function addReport(Request $request): \Illuminate\Http\JsonResponse
    {
        /*
         * 旅レポートを追加するAPI
        */

        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');
            $base64image = $request->input('image');
            $comment = $request->input('comment');
            $excitement = $request->input('excitement');
            $lat = $request->input('lat');
            $lon = $request->input('lon');

            // userIdからユーザの旅行情報を識別するためのIDを取得
            $travelId = Travel::where('user_id', $userId)->where('finished', 0)->where('traveler', 1)->select('travel_id')->get();

            if(!isset($base64image)){
                throw new \Exception('no image');
            }
            // base64デコード
            $image = base64_decode($base64image);

            //ファイル数カウント
            $fileNum = count(\Storage::allFiles('/public'));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_buffer($finfo, $image);
            //MIMEタイプをキーとした拡張子の配列
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png'
            ];
            //MIMEタイプから拡張子を選択してファイル名を作成
            $path = "image$fileNum.$extensions[$mime_type]";

            // 画像をサーバ上に保存し、パスを取得
            \Storage::put("/public/$path", $image);

            // 状況把握APIを呼び出し、状況を取得
            $base_url = 'http://172.31.50.221:8081/';

            $data = array(
                'image' => new \CURLFile(__DIR__ . '/../../../../storage/app/public/' . $path),
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $base_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $situationApiResponse = curl_exec($ch);
            curl_close($ch);
            $situation = json_decode($situationApiResponse, true)['situation'];

            // ユーザが旅行していなければエラーを返す
            if ($travelId->count() == 0) {
                throw new \Exception('permission denied');
            }

            // 旅レポートと旅行者状況を保存
            $travelId = $travelId[0]->travel_id;
            Report::insert([
                'travel_id' => $travelId,
                'image' => $path,
                'comment' => $comment,
                'excitement' => $excitement,
                'lat' => $lat,
                'lon' => $lon,
                'created_at' => null,
            ]);
            Situation::insert([
                'travel_id' => $travelId,
                'situation' => $situation,
                'created_at' => null,
            ]);
            Location::insert([
                'travel_id' => $travelId,
                'user_id' => $userId,
                'lat' => $lat,
                'lon' => $lon,
                'flag' => 2,
            ]);

            DB::commit();

            // レスポンスを返す
            $result = [
                'ok' => true,
                'error' => null,
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            DB::rollBack();

            // レスポンスを返す
            $result = [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
            return $this->resConversionJson($result, $e->getCode());
        }
    }

    public function finishTravel(Request $request): \Illuminate\Http\JsonResponse
    {
        /*
         * 旅行を終了するAPI
        */

        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');

            // ユーザの旅行を取得
            $travelId = Travel::where('user_id', $userId)->where('finished', 0)->where('traveler', 1)->select('travel_id')->get();

            // ユーザが旅行をしていなければエラーを返す
            if ($travelId->count() == 0) {
                throw new \Exception('permission denied');
            }

            // 旅行を終了する
            $travelId = $travelId[0]->travel_id;
            $travels = Travel::where('travel_id', $travelId);
            $travels->update([
                'finished' => 1
            ]);

            DB::commit();

            // レスポンスを返す
            $result = [
                'ok' => true,
                'error' => null
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            DB::rollBack();

            // レスポンスを返す
            $result = [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
            return $this->resConversionJson($result, $e->getCode());
        }
    }

    public function startTravel(Request $request): \Illuminate\Http\JsonResponse
    {
        /*
         * 旅行を開始するAPI
        */

        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $host = $request->input('host');
            $viewer1 = $request->input('viewer1');
            $viewer2 = $request->input('viewer2');
            $viewer3 = $request->input('viewer3');

            // 旅行識別IDをテーブルの最大値から求める
            $travelId = Travel::max('travel_id') + 1;

            // 旅行者をTravelsテーブルに追加
            Travel::insert([
                'travel_id' => $travelId,
                'user_id' => $host,
                'traveler' => 1,
                'finished' => 0
            ]);

            // 閲覧者をTravelsテーブルに追加
            $viewers = [$viewer1, $viewer2, $viewer3];
            foreach ($viewers as $viewer) {
                // データの品質をチェックしテーブルに追加
                if (!empty($viewer) && $viewer != $host) {
                    Travel::insert([
                        'travel_id' => $travelId,
                        'user_id' => $viewer,
                        'traveler' => 0,
                        'finished' => 0,
                    ]);
                }
            }

            DB::commit();

            // レスポンスを返す
            $result = [
                'ok' => true,
                'error' => null,
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            DB::rollBack();

            // レスポンスを返す
            $result = [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
            return $this->resConversionJson($result, $e->getCode());
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Models\Report;
use App\Models\Travel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TravelerController extends Controller
{
    public function addReport(Request $request)
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $data = $request->all();
            $user_id = $data['user_id'];
            $image = $request->file('image');
            $comment = $data['comment'];
            $excitement = $data['excitement'];
            $lat = $data['lat'];
            $lon = $data['lon'];

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel_id = Travel::where('user_id', $user_id)->where('finished', 0)->where('traveler', 1)->select('travel_id')->get();

            // 画像をサーバ上に保存し、パスを取得
            if ($request->hasFile('image')) {
                $path = \Storage::put('/public', $image);
                $path = explode('/', $path);
            } else {
                throw new \Exception('no image');
            }

            // ユーザが旅行しているかチェックし、旅レポートを保存
            if ($travel_id->count() != 0) {
                $travel_id = $travel_id[0]->travel_id;
                Report::insert([
                    'travel_id' => $travel_id,
                    'image' => $path[1],
                    'comment' => $comment,
                    'excitement' => $excitement,
                    'lat' => $lat,
                    'lon' => $lon,
                    'created_at' => null
                ]);
            } else {
                throw new \Exception('permission denied');
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

    public function finishTravel(Request $request)
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $user_id = $request->input('user_id');

            // ユーザの旅行を取得
            $travel_id = Travel::where('user_id', $user_id)->where('finished', 0)->where('traveler', 1)->select('travel_id')->get();

            // ユーザが旅行をしているかチェックし、旅行を終了する
            if ($travel_id->count() != 0) {
                $travel_id = $travel_id[0]->travel_id;
                $travels = Travel::where('travel_id', $travel_id);
                if ($travels->count() != 0) {
                    $travels->update([
                        'finished' => 1
                    ]);
                }
            }
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

    public function startTravel(Request $request)
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $data = $request->all();
            $host = $data['host'];
            $viewer1 = $data['viewer1'];
            $viewer2 = $data['viewer2'];
            $viewer3 = $data['viewer3'];

            // 旅行識別IDをテーブルの最大値から求める
            $travel_id = Travel::max('travel_id') + 1;

            // 旅行者をTravelsテーブルに追加
            Travel::insert([
                'travel_id' => $travel_id,
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
                        'travel_id' => $travel_id,
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

    private function resConversionJson($result, $statusCode=200)
    {
        if(empty($statusCode) || $statusCode < 100 || $statusCode >= 600){
            $statusCode = 500;
        }
        return response()->json($result, $statusCode, ['Content-Type' => 'application/json'], JSON_UNESCAPED_SLASHES);
    }
}

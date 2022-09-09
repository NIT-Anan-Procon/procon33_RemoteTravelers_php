<?php

namespace App\Http\Controllers\API;

use App\Models\Situation;
use App\Models\Account;
use App\Models\Report;
use App\Models\Location;
use App\Models\Comment;
use App\Models\Travel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommonController extends Controller
{
    public function addComment(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');
            $comment = $request->input('comment');

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel = Travel::where('user_id', $userId)->select('travel_id')->get();

            // ユーザが旅行していなければ例外処理を実行する
            if ($travel->count() == 0) {
                throw new \Exception('permission denied');
            }

            // コメントを保存する
            $travelId = $travel[0]->travel_id;
            Comment::insert([
                'travel_id' => $travelId,
                'user_id' => $userId,
                'comment' => $comment,
            ]);

            // レスポンスを返す
            $result = [
                'ok' => true,
                'error' => null,
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            // レスポンスを返す
            $result = [
                'ok' => false,
                'error' => $e->getMessage()
            ];
            return $this->resConversionJson($result, $e->getCode());
        }
    }

    public function checkTraveling(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel = Travel::where('user_id', $userId)->where('finished', 0)->get();

            // ユーザが旅行しているかチェックし、ユーザの役割をレスポンスとして返す
            if ($travel->count() == 0) {
                $result = [
                    'ok' => true,
                    'traveling' => false,
                    'traveler' => false,
                    'error' => null
                ];
            } else if ($travel[0]->traveler == 1) {
                $result = [
                    'ok' => true,
                    'traveling' => true,
                    'traveler' => true,
                    'error' => null
                ];
            } else {
                $result = [
                    'ok' => true,
                    'traveling' => true,
                    'traveler' => false,
                    'error' => null
                ];
            }
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            DB::rollBack();

            // レスポンスを返す
            $result = [
                'ok' => false,
                'traveling' => null,
                'traveler' => null,
                'error' => $e->getMessage(),
            ];
            return $this->resConversionJson($result, $e->getCode());
        }
    }

    public function getInfo(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel = Travel::where('user_id', $userId)->where('finished', 0)->get();

            // ユーザが旅行に参加しているかチェックし、データを取得
            // 現在地、現在地までの経路、提案されている目的地、コメント、旅レポート、旅行者状況を取得
            if ($travel->count() != 0) {
                $travel_id = $travel[0]->travel_id;
                $current_location = Location::where('travel_id', $travel_id)->where('flag', 0)->latest()->select('lat', 'lon')->first();
                $route = Location::where('travel_id', $travel_id)->where('flag', 0)->orderBy('created_at', 'asc')->select('lat', 'lon')->get();
                $destination = Location::where('travel_id', $travel_id)->where('flag', 1)->latest()->select('lat', 'lon')->get();
                $comments = Comment::where('travel_id', $travel_id)->orderBy('created_at', 'asc')->select('user_id', 'comment')->get();
                $reports = Report::where('travel_id', $travel_id)->orderBy('created_at', 'asc')->select('comment', 'excitement', 'lat', 'lon')->get();
                $situation = Situation::where('travel_id', $travel_id)->latest()->select('situation')->first();
            } else {
                throw new \Exception('permission denied');
            }

            // reportsのimageのパスからファイルを取得し、base64に変換する
            foreach ($reports as $report) {
                $image = \Storage::get('public/'.$report->image);
                $report->image = base64_encode($image);
            }

            // レスポンスを返す
            $result = [
                'ok' => true,
                'destination' => $destination,
                'current_location' => $current_location,
                'route' => $route,
                'comments' => $comments,
                'reports' => $reports,
                'situation' => $situation,
                'error' => null,
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            DB::rollBack();

            // レスポンスを返す
            $result = [
                'ok' => false,
                'data' => null,
                'error' => $e->getMessage()
            ];
            return $this->resConversionJson($result, $e->getCode());
        }
    }

    public function saveLocation(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');
            $lat = $request->input('lat');
            $lon = $request->input('lon');
            $suggestionFlag = $request->input('suggestion_flag');

            // suggestion_flagの値によって与える権限を変更
            // user_idからユーザの旅行情報を識別するためのIDを取得
            //前回の行先提案を削除
            if ($suggestionFlag == 1) {
                $travelId = Travel::where('user_id', $userId)->where('finished', 0)->where('traveler', 0)->get();
                Location::where('user_id', $userId)->where('flag', $suggestionFlag)->delete();
            } else {
                $travelId = Travel::where('user_id', $userId)->where('finished', 0)->where('traveler', 1)->get();
            }

            // ユーザが旅行していなければエラーを返す
            if ($travelId->count() == 0) {
                throw new \Exception('permission denied');
            }

            // 位置情報を保存
            $travelId = $travelId[0]->travel_id;
            Location::insert([
                'travel_id' => $travelId,
                'user_id' => $userId,
                'lat' => $lat,
                'lon' => $lon,
                'flag' => $suggestionFlag,
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

    public function updateInfo(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');

            // 最終更新日時を取得
            $lastUpdate = Account::where('user_id', $userId)->select('updated_at')->get()[0]->updated_at;

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel = Travel::where('user_id', $userId)->where('finished', 0)->get();

            // ユーザが旅行に参加しているかチェックし、データを取得
            if ($travel->count() == 0) {
                throw new \Exception('permission denied');
            }

            // 旅行を識別するIDを取得
            $travelId = $travel[0]->travel_id;

            // それぞれのテーブルがユーザの最終更新日時より新しいデータがあるかチェック
            $locationUpdateCount = Location::where('travel_id', $travelId)->where('created_at', '>', $lastUpdate)->count();
            $commentUpdateCount = Comment::where('travel_id', $travelId)->where('created_at', '>', $lastUpdate)->count();
            $reportUpdateCount = Report::where('travel_id', $travelId)->where('created_at', '>', $lastUpdate)->count();
            $situationUpdateCount = Situation::where('travel_id', $travelId)->where('created_at', '>', $lastUpdate)->count();

            // 更新があればそれぞれのデータを取得
            $currentLocation = ($locationUpdateCount > 0) ? Location::where('travel_id', $travelId)->latest()->select('lat', 'lon')->get()[0] : null;
            $route = ($locationUpdateCount > 0) ? Location::where('travel_id', $travelId)->orderBy('created_at', 'asc')->select('lat', 'lon')->get() : null;
            $destination = ($locationUpdateCount > 0) ? Location::where('travel_id', $travelId)->where('flag', 1)->select('lat', 'lon')->get()[0] : null;
            $comments = ($commentUpdateCount > 0) ? Comment::where('travel_id', $travelId)->orderBy('created_at', 'asc')->select('comment_id', 'user_id', 'comment', 'created_at')->get() : null;
            $reports = ($reportUpdateCount > 0) ? Report::where('travel_id', $travelId)->orderBy('created_at', 'asc')->select('report_id', 'user_id', 'image', 'created_at')->get() : null;
            $situation = ($situationUpdateCount > 0) ? Situation::where('travel_id', $travelId)->orderBy('created_at', 'asc')->select('situation_id', 'user_id', 'situation', 'created_at')->get() : null;

            DB::commit();

            // 最終更新日時を更新
            $account = Account::where("user_id", $userId)->first();
            $account->touch();

            DB::commit();

            // レスポンスを返す
            $result = [
                'ok' => true,
                'current_location' => $currentLocation,
                'route' => $route,
                'destination' => $destination,
                'comments' => $comments,
                'reports' => $reports,
                'situation' => $situation,
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

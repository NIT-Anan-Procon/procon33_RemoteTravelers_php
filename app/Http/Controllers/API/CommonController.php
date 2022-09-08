<?php

namespace App\Http\Controllers\API;

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
    public function addComment(Request $request)
    {
        try {
            // リクエストから受け取った値を取得
            $data = $request->all();
            $user_id = $data['user_id'];
            $comment = $data['comment'];

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel_id = Travel::where('user_id', $user_id)->select('travel_id')->get();

            // ユーザが旅行しているかチェックし、旅レポートを保存
            if ($travel_id->count() != 0) {
                $travel_id = $travel_id[0]->travel_id;
                Comment::insert([
                    'travel_id' => $travel_id,
                    'user_id' => $user_id,
                    'comment' => $comment,
                ]);
            } else {
                throw new \Exception('permission denied');
            }

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

    public function checkTraveling(Request $request)
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $user_id = $request->input('user_id');

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel = Travel::where('user_id', $user_id)->where('finished', 0)->get();

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

    public function getInfo(Request $request)
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel = Travel::where('user_id', $userId)->where('finished', 0)->get();

            // ユーザが旅行に参加しているかチェックし、データを取得
            // 現在地、現在地までの経路、提案されている目的地、コメント、旅レポートを取得
            if ($travel->count() != 0) {
                $travel_id = $travel[0]->travel_id;
                $current_location = Location::where('travel_id', $travel_id)->where('flag', 0)->latest()->select('lat', 'lon')->first();
                $route = Location::where('travel_id', $travel_id)->where('flag', 0)->orderBy('created_at', 'asc')->select('lat', 'lon')->get();
                $destination = Location::where('travel_id', $travel_id)->where('flag', 1)->latest()->select('lat', 'lon')->get();
                $comments = Comment::where('travel_id', $travel_id)->orderBy('created_at', 'asc')->select('user_id', 'comment')->get();
                $reports = Report::where('travel_id', $travel_id)->orderBy('created_at', 'asc')->select('comment', 'excitement', 'lat', 'lon')->get();
            } else {
                throw new \Exception('permission denied');
            }

            // reportsのimageをパスからファイルに変換
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

    public function saveLocation(Request $request)
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $data = $request->all();
            $user_id = $data['user_id'];
            $lat = $data['lat'];
            $lon = $data['lon'];
            $suggestion_flag = $data['suggestion_flag'];

            // suggestion_flagの値によって与える権限を変更
            // user_idからユーザの旅行情報を識別するためのIDを取得
            if ($suggestion_flag == 1) {
                $travel_id = Travel::where('user_id', $user_id)->where('finished', 0)->where('traveler', 0)->get();
            } else {
                $travel_id = Travel::where('user_id', $user_id)->where('finished', 0)->where('traveler', 1)->get();
            }

            // ユーザが旅行しているかチェックし、位置情報を保存
            if ($travel_id->count() != 0) {
                $travel_id = $travel_id[0]->travel_id;
                Location::insert([
                    'travel_id' => $travel_id,
                    'lat' => $lat,
                    'lon' => $lon,
                    'flag' => $suggestion_flag,
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

    public function updateInfo(Request $request)
    {
        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');

            // 最終更新日時を取得
            $lastUpdate = Account::where('user_id', $userId)->select('last_update')->first();

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel = Travel::where('user_id', $userId)->where('finished', 0)->get();

            // ユーザが旅行に参加しているかチェックし、データを取得
            if ($travel->count() == 0) {
                throw new \Exception('permission denied');
            }

            // 旅行を識別するIDを取得
            $travel_id = $travel[0]->travel_id;

            // それぞれのテーブルがユーザの最終更新日時より新しいデータがあるかチェック
            $locationUpdateFlag = Location::where('travel_id', $travel_id)->where('created_at', '>', $lastUpdate)->count();
            $commentUpdateFlag = Comment::where('travel_id', $travel_id)->where('created_at', '>', $lastUpdate)->count();
            $reportUpdateFlag = Report::where('travel_id', $travel_id)->where('created_at', '>', $lastUpdate)->count();

            // 更新があればデータを取得
            if ($locationUpdateFlag) {
                $current_location = Location::where('travel_id', $travel_id)->where('flag', 0)->latest()->select('lat', 'lon')->first();
                $route = Location::where('travel_id', $travel_id)->where('flag', 0)->orderBy('created_at', 'asc')->select('lat', 'lon')->get();
                $destination = Location::where('travel_id', $travel_id)->where('flag', 1)->latest()->select('lat', 'lon')->get();
            } else {
                $current_location = null;
                $route = null;
                $destination = null;
            }

            if ($commentUpdateFlag) {
                $comments = Comment::where('travel_id', $travel_id)->orderBy('created_at', 'asc')->select('comment', 'excitement', 'lat', 'lon')->get();
            } else {
                $comments = null;
            }

            if ($reportUpdateFlag) {
                $reports = Report::where('travel_id', $travel_id)->orderBy('created_at', 'asc')->select('image', 'lat', 'lon')->get();
            } else {
                $reports = null;
            }

            // 最終更新日時を更新
            Account::where('user_id', $userId)->update();

            // レスポンスを返す
            $result = [
                'ok' => true,
                'current_location' => $current_location,
                'route' => $route,
                'destination' => $destination,
                'comments' => $comments,
                'reports' => $reports,
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

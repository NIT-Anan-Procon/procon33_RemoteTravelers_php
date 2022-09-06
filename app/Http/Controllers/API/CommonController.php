<?php

namespace App\Http\Controllers\API;

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
            $data = $request->all();
            $user_id = $data['user_id'];

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel = Travel::where('user_id', $user_id)->where('finished', 0)->get();

            // ユーザが旅行に参加しているかチェックし、データを取得
            // 現在地、現在地までの経路、提案されている目的地、コメント、旅レポートを取得
            if ($travel->count() != 0) {
                $travel_id = $travel[0]->travel_id;
                $current_location = Location::where('travel_id', $travel_id)->where('flag', 0)->latest()->select('lat', 'lon')->first();
                $route = Location::where('travel_id', $travel_id)->where('flag', 0)->orderBy('created_at', 'asc')->select('lat', 'lon')->get();
                $destination = Location::where('travel_id', $travel_id)->where('flag', 1)->latest()->select('lat', 'lon')->first();
                $comments = Comment::where('travel_id', $travel_id)->orderBy('created_at', 'asc')->get();
                $reports = Report::where('travel_id', $travel_id)->orderBy('created_at', 'asc')->get();
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

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel_id = Travel::where('user_id', $user_id)->where('finished', 0)->where('traveler', 1)->select('travel_id')->get();

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

    private function resConversionJson($result, $statusCode=200)
    {
        if(empty($statusCode) || $statusCode < 100 || $statusCode >= 600){
            $statusCode = 500;
        }
        return response()->json($result, $statusCode, ['Content-Type' => 'application/json'], JSON_UNESCAPED_SLASHES);
    }
}

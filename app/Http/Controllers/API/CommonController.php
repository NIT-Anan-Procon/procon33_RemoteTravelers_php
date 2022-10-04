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
    const EQUATORIAL_RADIUS = 6378.137;

    public function addComment(Request $request): \Illuminate\Http\JsonResponse
    {
        /*
         * コメントを追加するAPI
        */

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

            DB::commit();

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
        /*
         * ユーザが旅行中か確認するAPI
        */

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

    public function getAlbum(Request $request): \Illuminate\Http\JsonResponse
    {
        /*
         * 過去の旅レポートを取得するAPI
        */

        try {
            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');

            // 過去のレポートを取得
            $albums = Account::select('reports.created_at', 'reports.image', 'reports.comment', 'reports.excitement', 'reports.lat', 'reports.lon')->join('travels', 'accounts.user_id', '=', 'travels.user_id')->join('reports', 'travels.travel_id', '=', 'reports.travel_id')->where('accounts.user_id', $userId)->get();

            // 画像のパスからbase64形式で画像を取得
            foreach($albums as $album) {
                $album->image = \Storage::get('public/'.$album->image);
                $album->image = base64_encode($album->image);
            }

            // レスポンスを返す
            $result = [
                'ok' => true,
                'album' => $albums,
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

    public function getInfo(Request $request): \Illuminate\Http\JsonResponse
    {
        /*
         * 旅行画面・閲覧画面に必要な情報を取得するAPI
        */

        try {
            DB::beginTransaction();

            // リクエストから受け取った値を取得
            $userId = $request->input('user_id');

            // user_idからユーザの旅行情報を識別するためのIDを取得
            $travel = Travel::where('user_id', $userId)->where('finished', 0)->get();

            // ユーザが旅行に参加しているかチェックし、データを取得
            // 現在地、現在地までの経路、提案されている目的地、コメント、旅レポート、旅行者状況を取得
            if ($travel->count() != 0) {
                $travelId = $travel[0]->travel_id;
                $currentLocation = Location::where('travel_id', $travelId)->where('flag', 0)->latest()->select('lat', 'lon')->first();
                $routes = Location::where('travel_id', $travelId)->whereIn('flag', [0, 2])->orderBy('created_at', 'asc')->select('lat', 'lon', 'flag')->get();
                $destination = Location::where('travel_id', $travelId)->where('flag', 1)->latest()->select('lat', 'lon')->get();
                $comments = Comment::join('travels', 'comments.user_id', '=', 'travels.user_id')->where('comments.travel_id', $travelId)->where('travels.travel_id', $travelId)->orderBy('comments.created_at', 'asc')->select('comments.comment', 'travels.traveler')->get();
                $reports = Report::where('travel_id', $travelId)->orderBy('created_at', 'asc')->select('comment', 'excitement', 'lat', 'lon', 'image')->get();
                $situation = Situation::where('travel_id', $travelId)->latest()->select('situation')->first();
            } else {
                throw new \Exception('permission denied');
            }

            if(!isset($situation)){
                $situation['situation'] = null;
            }
            //flagを仕様書通りになるよう変換
            foreach($routes as $route){
                    $route->flag = ($route->flag == 2)? 1 : 0;
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
                'current_location' => $currentLocation,
                'route' => $routes,
                'comments' => $comments,
                'reports' => $reports,
                'situation' => $situation['situation'],
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
        /*
         * 旅行者の現在地・行先提案の行先を保存するAPI
        */

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

            if($suggestionFlag == 0){
                // ユーザの現在地を取得
                $currentLocation = Location::where('travel_id', $travelId)->where('flag', 0)->latest()->select('lat', 'lon')->first();
                $distance = EQUATORIAL_RADIUS * acos(sin($lon) * sin($currentLocation->lon) + cos($lon) * cos($currentLocation->lon) * cos($lat - $currentLocation->lat));
                //1km移動したとき
                if ($distance > 1){
                    Situation::insert([
                        'travel_id' => $travelId[0]->travel_id,
                        'situation' => '移動中',
                        'created_at' => null,
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

    public function updateInfo(Request $request): \Illuminate\Http\JsonResponse
    {
        /*
         * updateInfoで最後に取得してきたデータから更新された分のデータを取得するAPI
         * Androidで2回目以降に旅行者・閲覧者画面に必要なデータを取得する際に使用
        */

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
            $situationUpdateCount = Situation::where('travel_id', $travelId)->where('created_at', '>', $lastUpdate)->count();
            $reportUpdateCount = Report::where('travel_id', $travelId)->where('created_at', '>', $lastUpdate)->count();

            // 更新があればそれぞれのデータを取得
            $currentLocation = ($locationUpdateCount > 0) ? Location::where('travel_id', $travelId)->where('flag', 0)->latest()->select('lat', 'lon')->first() : null;
            $routes = ($locationUpdateCount > 0) ? Location::where('travel_id', $travelId)->whereIn('flag', [0, 2])->orderBy('created_at', 'asc')->select('lat', 'lon', 'flag')->get() : null;
            $destination = ($locationUpdateCount > 0) ? Location::where('travel_id', $travelId)->where('flag', 1)->latest()->select('lat', 'lon')->get() : null;
            $comments = ($commentUpdateCount > 0) ? Comment::join('travels', 'comments.user_id', '=', 'travels.user_id')->where('comments.travel_id', $travelId)->where('travels.travel_id', $travelId)->orderBy('comments.created_at', 'asc')->select('comments.comment', 'travels.traveler')->get() : null;
            $situation = ($situationUpdateCount > 0) ? Situation::where('travel_id', $travelId)->latest()->select('situation')->first() : null;
            //レポートは更新された分だけ取得
            $reports = ($reportUpdateCount > 0) ? Report::where('travel_id', $travelId)->where('created_at', '>', $lastUpdate)->orderBy('created_at', 'asc')->select('comment', 'excitement', 'lat', 'lon', 'image')->get() : null;

            DB::commit();

            // 最終更新日時を更新
            $account = Account::where("user_id", $userId)->first();
            $account->touch();

            DB::commit();

            if(!isset($situation)){
                $situation['situation'] = null;
            }

            //flagを仕様書通りになるよう変換
            if(isset($routes)){
                foreach($routes as $route){
                    $route->flag = ($route->flag == 2)? 1 : 0;
                }
            }
            // reportsのimageのパスからファイルを取得し、base64に変換する
            if(isset($reports)){
                foreach ($reports as $report) {
                    $image = \Storage::get('public/'.$report->image);
                    $report->image = base64_encode($image);
                }
            }
            // レスポンスを返す
            $result = [
                'ok' => true,
                'current_location' => $currentLocation,
                'route' => $routes,
                'destination' => $destination,
                'comments' => $comments,
                'reports' => $reports,
                'situation' => $situation['situation'],
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

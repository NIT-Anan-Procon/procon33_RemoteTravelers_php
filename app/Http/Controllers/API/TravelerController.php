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

            $data = $request->all();
            $user_id = $data['user_id'];
            $image = $request->file('image');
            $comment = $data['comment'];
            $excitement = $data['excitement'];
            $location = $data['location'];
            $travel_id = Travel::where('user_id', $user_id)->where('finished', 0)->where('traveler', 1)->select('travel_id')->get()[0]->travel_id;

            if ($request->hasFile('image')) {
                $path = \Storage::put('/public', $image);
                $path = explode('/', $path);
            } else {
                throw new \Exception('no image');
            }

            if (empty($travel_id)) {
                throw new \Exception('permision denied');
            }

            Report::insert([
                'travel_id' => $travel_id[0]->travel_id,
                'image' => $image,
                'comment' => $comment,
                'excitement' => $excitement,
                'location' => $location,
                'created_at' => null
            ]);

            DB::commit();
            $result = [
                'ok' => true,
                'error' => null,
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            DB::rollBack();
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

            $user_id = $request->input('user_id');
            $travel_id = Travel::where('user_id', $user_id)->where('finished', 0)->select('travel_id')->get();
            if (count($travel_id) != 0) {
                $travel_id = $travel_id[0]->travel_id;
            }
            $travels = Travel::where('travel_id', $travel_id);
            if (count($travels) != 0) {
                $travels->update([
                    'finished' => 1
                ]);
            }
            DB::commit();

            $result = [
                'ok' => true,
                'error' => null,
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            DB::rollBack();
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

            $data = $request->all();
            $host = $data['host'];
            $viewers = $data['viewers'];
            $travel_id = Travel::max('travel_id') + 1;

            // 旅行者をTravelsテーブルに追加
            Travel::insert([
                'travel_id' => $travel_id,
                'user_id' => $host,
                'traveler' => 1,
                'finished' => 0
            ]);
            // 閲覧者をTravelsテーブルに追加
            foreach ($viewers as $viewer) {
                Travel::insert([
                    'travel_id' => $travel_id,
                    'user_id' => $viewer,
                    'traveler' => 0,
                    'finished' => 0
                ]);
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

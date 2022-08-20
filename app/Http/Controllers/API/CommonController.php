<?php

namespace App\Http\Controllers\API;

use App\Models\Comment;
use App\Models\Travel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommonController extends Controller
{
    public function addComment(Request $request)
    {
        $data = $request->all();
        try {
            $user_id = $data['user_id'];
            $comment = $data['comment'];
            $travel_id = Travel::where('user_id', $user_id)->select('travel_id')->get();
            Comment::insert([
               'travel_id' => 1,
               'user_id' => $user_id,
               'comment' => $comment,
               'created_at' => null
            ]);

            $result = [
                'ok' => true,
                'error' => false
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
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

            $user_id = $request->input('user_id');
            $travel = Travel::where('user_id', $user_id)->where('finished', 0)->get();

            if (empty($travel)) {
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
            $result = [
                'ok' => false,
                'traveling' => null,
                'traveler' => null,
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

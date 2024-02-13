<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class MusicController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    //

    public function index()
    {
        $music = DB::connection('mysql')->table("music_source")->get();
        return response()->json(['status' => 'success', 'data' => $music]);
    }
    public function show($id)
    {
        $music = DB::connection('mysql')->table("music_source")->where('id')->first();
        if(!$music){
            return response()->json(['status'=> 'failed','message'=> 'Data tidak ditemukan!']);
        }
        return response()->json(['status' => 'success', 'data' => $music]);
    }

    public function search(Request $request)
    {
        $searchInput = $request->input("search");
        $musicData = DB::connection('mysql')->table("music_source")->pluck("title")->toArray();
        $threshold = 3;

        $result = [];
        foreach ($musicData as $title) {
            $matches = $this->KMPSearch(strtolower($searchInput), strtolower($title));
            if (count($matches) > 0) {
                $result[] = $title;
            }
        }

        if (empty($result)) {
            foreach ($musicData as $title) {
                if ($this->levenshteinSearch(strtolower($searchInput), strtolower($title)) <= $threshold) {
                    $result[] = $title;
                }
            }
        }

        return response()->json([
            "status" => "success",
            "data" => $result
        ]);


    }

    public function levenshteinSearch($pattern, $text)
    {
        $patternLength = strlen($pattern);
        $textLength = strlen($text);

        // Inisialisasi matriks
        $dp = [];
        for ($i = 0; $i <= $patternLength; $i++) {
            $dp[$i][0] = $i;
        }
        for ($j = 0; $j <= $textLength; $j++) {
            $dp[0][$j] = $j;
        }

        // Menghitung jarak Levenshtein
        for ($i = 1; $i <= $patternLength; $i++) {
            for ($j = 1; $j <= $textLength; $j++) {
                $cost = ($pattern[$i - 1] != $text[$j - 1]) ? 1 : 0;
                $dp[$i][$j] = min(
                    $dp[$i - 1][$j] + 1,
                    $dp[$i][$j - 1] + 1,
                    $dp[$i - 1][$j - 1] + $cost
                );
            }
        }

        // Mengembalikan jarak Levenshtein antara pattern dan text
        return $dp[$patternLength][$textLength];
    }

    private function computeLPSArray($pat, &$lps)
    {
        $len = 0;
        $i = 1;
        $lps[0] = 0;

        while ($i < strlen($pat)) {
            if ($pat[$i] == $pat[$len]) {
                $len++;
                $lps[$i] = $len;
                $i++;
            } else {
                if ($len != 0) {
                    $len = $lps[$len - 1];
                } else {
                    $lps[$i] = 0;
                    $i++;
                }
            }
        }
    }

    private function KMPSearch($pat, $txt)
    {
        $m = strlen($pat);
        $n = strlen($txt);

        $lps = [];
        $this->computeLPSArray($pat, $lps);

        $i = $j = 0;
        $matches = [];

        while ($i < $n) {
            if ($pat[$j] == $txt[$i]) {
                $i++;
                $j++;
            }

            if ($j == $m) {
                $matches[] = $i - $j;
                $j = $lps[$j - 1];
            } elseif ($i < $n && $pat[$j] != $txt[$i]) {
                if ($j != 0) {
                    $j = $lps[$j - 1];
                } else {
                    $i++;
                }
            }
        }

        return $matches;
    }

}

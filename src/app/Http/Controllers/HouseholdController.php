<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Category;
// use App\Models\Oeconomica;  // まだ作成していない場合はコメントアウト
use Carbon\Carbon;

class HouseholdController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // 認証ミドルウェアはroutes/web.phpで設定済み
    }

    /**
     * Show the household input form (メイン画面).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function input(Request $request)
    {
        $user = Auth::user();
        
        // 現在の年月を取得（デフォルト）
        $currentYear = $request->get('year', Carbon::now()->year);
        $currentMonth = $request->get('month', Carbon::now()->month);
        
        // ユーザーのカテゴリを取得
        $incomeCategories = Category::where('user_id', $user->id)
            ->where('type', 'income')
            ->orderBy('category')
            ->get();
            
        $expenseCategories = Category::where('user_id', $user->id)
            ->where('type', 'expense')
            ->orderBy('category')
            ->get();

        // 指定月の収支データを取得（Oeconomicaモデルが存在しない場合は空の配列）
        $startDate = Carbon::create($currentYear, $currentMonth, 1)->startOfMonth();
        $endDate = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth();
        
        // Oeconomicaモデルが存在しない場合のフォールバック
        $oeconomicas = collect([]); // 空のコレクション
        
        // Oeconomicaモデルが存在する場合のコード（後で有効化）
        /*
        $oeconomicas = Oeconomica::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        */

        return view('household.input', compact(
            'user',
            'currentYear',
            'currentMonth',
            'incomeCategories',
            'expenseCategories',
            'oeconomicas',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Store household data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // 一時的にシンプルな実装
        return back()->with('success', '収支データ登録機能は準備中です。');
    }

    /**
     * Show monthly data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function monthly(Request $request)
    {
        $user = Auth::user();
        
        // 現在の年月を取得
        $currentYear = $request->get('year', Carbon::now()->year);
        $currentMonth = $request->get('month', Carbon::now()->month);
        
        // 一時的な実装
        return view('household.monthly', compact(
            'user',
            'currentYear',
            'currentMonth'
        ));
    }

    /**
     * Show yearly data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function yearly(Request $request)
    {
        $user = Auth::user();
        
        // 現在の年を取得
        $currentYear = $request->get('year', Carbon::now()->year);
        
        // 一時的な実装
        return view('household.yearly', compact(
            'user',
            'currentYear'
        ));
    }

    /**
     * Show settings page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function settings(Request $request)
    {
        $user = Auth::user();
        
        // 一時的な実装
        return view('household.settings', compact('user'));
    }
}
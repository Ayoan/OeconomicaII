<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Category;
use App\Models\Oeconomica;
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
            ->orderBy('id')
            ->get();
            
        $expenseCategories = Category::where('user_id', $user->id)
            ->where('type', 'expense')
            ->orderBy('id')
            ->get();

        // 指定月の収支データを取得
        $startDate = Carbon::create($currentYear, $currentMonth, 1)->startOfMonth();
        $endDate = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth();
        
        $oeconomicas = Oeconomica::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

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
        $user = Auth::user();

        // バリデーション
        $validatedData = $request->validate([
            'balance' => 'required|in:income,expense',
            'date' => 'required|date',
            'category' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'memo' => 'nullable|string|max:255',
        ], [
            'balance.required' => '収支区分を選択してください。',
            'balance.in' => '収支区分が正しくありません。',
            'date.required' => '日付を入力してください。',
            'date.date' => '正しい日付を入力してください。',
            'category.required' => 'カテゴリを選択してください。',
            'category.max' => 'カテゴリ名が長すぎます。',
            'amount.required' => '金額を入力してください。',
            'amount.integer' => '金額は整数で入力してください。',
            'amount.min' => '金額は1円以上で入力してください。',
            'memo.max' => 'メモが長すぎます（255文字以内）。',
        ]);

        // 選択された日付が指定月内かチェック
        $inputDate = Carbon::parse($validatedData['date']);
        $currentYear = $request->get('year', Carbon::now()->year);
        $currentMonth = $request->get('month', Carbon::now()->month);
        $startDate = Carbon::create($currentYear, $currentMonth, 1)->startOfMonth();
        $endDate = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth();

        if ($inputDate->lt($startDate) || $inputDate->gt($endDate)) {
            return back()->withErrors([
                'date' => '日付は表示中の月（' . $currentYear . '年' . $currentMonth . '月）内で入力してください。'
            ])->withInput();
        }

        // 選択されたカテゴリがユーザーのカテゴリか確認
        $categoryExists = Category::where('user_id', $user->id)
            ->where('category', $validatedData['category'])
            ->where('type', $validatedData['balance'])
            ->exists();

        if (!$categoryExists) {
            return back()->withErrors([
                'category' => '選択されたカテゴリが存在しません。'
            ])->withInput();
        }

        // 収支データを保存
        try {
            Oeconomica::create([
                'user_id' => $user->id,
                'balance' => $validatedData['balance'],
                'date' => $validatedData['date'],
                'category' => $validatedData['category'],
                'amount' => $validatedData['amount'],
                'memo' => $validatedData['memo'],
            ]);

            $balanceText = $validatedData['balance'] === 'income' ? '収入' : '支出';
            $message = $balanceText . 'データを登録しました。（' . 
                      $validatedData['category'] . '：' . 
                      number_format($validatedData['amount']) . '円）';

            return redirect()
                ->route('household.input', [
                    'year' => $currentYear,
                    'month' => $currentMonth
                ])
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors([
                'general' => '収支データの登録に失敗しました。もう一度お試しください。'
            ])->withInput();
        }
    }

    // 未使用のためコメントアウト（2025/7/26）
    // /**
    //  * Ajax でカテゴリを取得
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @return \Illuminate\Http\JsonResponse
    //  */
    // public function getCategories(Request $request)
    // {
    //     $user = Auth::user();
    //     $balance = $request->get('balance');

    //     if (!in_array($balance, ['income', 'expense'])) {
    //         return response()->json(['error' => 'Invalid balance type'], 400);
    //     }

    //     $categories = Category::where('user_id', $user->id)
    //         ->where('type', $balance)
    //         ->orderBy('id')
    //         ->pluck('category', 'category');

    //     return response()->json($categories);
    // }

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

    /**
     * Get single oeconomica data for editing.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $user = Auth::user();
        
        $oeconomica = Oeconomica::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$oeconomica) {
            return response()->json(['error' => 'データが見つかりません'], 404);
        }

        return response()->json([
            'id' => $oeconomica->id,
            'balance' => $oeconomica->balance,
            'date' => $oeconomica->date->format('Y-m-d'),
            'category' => $oeconomica->category,
            'amount' => $oeconomica->amount,
            'memo' => $oeconomica->memo,
        ]);
    }

    /**
     * Update oeconomica data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        $oeconomica = Oeconomica::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$oeconomica) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'データが見つかりません'], 404);
            }
            return back()->withErrors(['general' => 'データが見つかりません']);
        }

        // バリデーション
        $validatedData = $request->validate([
            'balance' => 'required|in:income,expense',
            'date' => 'required|date',
            'category' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'memo' => 'nullable|string|max:255',
        ], [
            'balance.required' => '収支区分を選択してください。',
            'balance.in' => '収支区分が正しくありません。',
            'date.required' => '日付を入力してください。',
            'date.date' => '正しい日付を入力してください。',
            'category.required' => 'カテゴリを選択してください。',
            'category.max' => 'カテゴリ名が長すぎます。',
            'amount.required' => '金額を入力してください。',
            'amount.integer' => '金額は整数で入力してください。',
            'amount.min' => '金額は1円以上で入力してください。',
            'memo.max' => 'メモが長すぎます（255文字以内）。',
        ]);

        // 選択されたカテゴリがユーザーのカテゴリか確認
        $categoryExists = Category::where('user_id', $user->id)
            ->where('category', $validatedData['category'])
            ->where('type', $validatedData['balance'])
            ->exists();

        if (!$categoryExists) {
            if ($request->expectsJson()) {
                return response()->json(['error' => '選択されたカテゴリが存在しません'], 400);
            }
            return back()->withErrors(['category' => '選択されたカテゴリが存在しません。']);
        }

        try {
            $oeconomica->update([
                'balance' => $validatedData['balance'],
                'date' => $validatedData['date'],
                'category' => $validatedData['category'],
                'amount' => $validatedData['amount'],
                'memo' => $validatedData['memo'],
            ]);

            $balanceText = $validatedData['balance'] === 'income' ? '収入' : '支出';
            $message = $balanceText . 'データを更新しました。（' . 
                      $validatedData['category'] . '：' . 
                      number_format($validatedData['amount']) . '円）';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'id' => $oeconomica->id,
                        'balance' => $oeconomica->balance,
                        'date' => $oeconomica->date->format('Y-m-d'),
                        'category' => $oeconomica->category,
                        'amount' => $oeconomica->amount,
                        'memo' => $oeconomica->memo,
                        'balance_text' => $balanceText,
                        'formatted_amount' => number_format($oeconomica->amount),
                        'short_date' => $oeconomica->date->format('m/d'),
                    ]
                ]);
            }

            return redirect()
                ->route('household.input', [
                    'year' => $request->get('year', Carbon::now()->year),
                    'month' => $request->get('month', Carbon::now()->month)
                ])
                ->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Update error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'データの更新に失敗しました: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['general' => 'データの更新に失敗しました。']);
        }
    }

    /**
     * Delete oeconomica data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        
        $oeconomica = Oeconomica::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$oeconomica) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'データが見つかりません'], 404);
            }
            return back()->withErrors(['general' => 'データが見つかりません']);
        }

        try {
            $balanceText = $oeconomica->balance === 'income' ? '収入' : '支出';
            $category = $oeconomica->category;
            $amount = $oeconomica->amount;
            
            $oeconomica->delete();

            $message = $balanceText . 'データを削除しました。（' . 
                      $category . '：' . 
                      number_format($amount) . '円）';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()
                ->route('household.input', [
                    'year' => $request->get('year', Carbon::now()->year),
                    'month' => $request->get('month', Carbon::now()->month)
                ])
                ->with('success', $message);

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'データの削除に失敗しました'], 500);
            }
            return back()->withErrors(['general' => 'データの削除に失敗しました。']);
        }
    }
}
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

    /**
     * Ajax でカテゴリを取得
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories(Request $request)
    {
        $user = Auth::user();
        $balance = $request->get('balance');

        if (!in_array($balance, ['income', 'expense'])) {
            return response()->json(['error' => 'Invalid balance type'], 400);
        }

        $categories = Category::where('user_id', $user->id)
            ->where('type', $balance)
            ->orderBy('category')
            ->pluck('category', 'category');

        return response()->json($categories);
    }

    /**
     * 月次レポート画面の表示
     */
    public function monthly(Request $request)
    {
        $user = Auth::user();
        
        // 表示月の取得（デフォルトは現在月）
        $targetMonth = $request->input('month', date('Y-m'));
        $year = substr($targetMonth, 0, 4);
        $month = substr($targetMonth, 5, 2);
        
        // 指定月のデータ取得
        $monthlyData = Oeconomica::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();
        
        // 収入データの集計
        $incomeByCategory = $monthlyData
            ->where('balance', 'income')
            ->groupBy('category')
            ->map(function ($items) {
                return $items->sum('amount');
            });
        
        // 支出データの集計
        $expenseByCategory = $monthlyData
            ->where('balance', 'expense')
            ->groupBy('category')
            ->map(function ($items) {
                return $items->sum('amount');
            });
        
        // 合計値の計算
        $totalIncome = $incomeByCategory->sum();
        $totalExpense = $expenseByCategory->sum();
        $balance = $totalIncome - $totalExpense;
        
        // 日別推移データの準備
        $dailyData = $monthlyData->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('d');
        })->map(function ($dayItems) {
            return [
                'income' => $dayItems->where('balance', 'income')->sum('amount'),
                'expense' => $dayItems->where('balance', 'expense')->sum('amount'),
            ];
        });
        
        // 前月比較用データ
        $prevMonth = Carbon::parse($targetMonth)->subMonth();
        $prevMonthData = Oeconomica::where('user_id', $user->id)
            ->whereYear('date', $prevMonth->year)
            ->whereMonth('date', $prevMonth->month)
            ->get();
        
        $prevTotalIncome = $prevMonthData->where('balance', 'income')->sum('amount');
        $prevTotalExpense = $prevMonthData->where('balance', 'expense')->sum('amount');
        
        // 前月比の計算
        $incomeChange = $prevTotalIncome > 0 
            ? round((($totalIncome - $prevTotalIncome) / $prevTotalIncome) * 100, 1)
            : 0;
        $expenseChange = $prevTotalExpense > 0 
            ? round((($totalExpense - $prevTotalExpense) / $prevTotalExpense) * 100, 1)
            : 0;
        
        // カテゴリ別色設定の取得
        $categories = Category::where('user_id', $user->id)->get();
        $categoryColors = $categories->pluck('color', 'category')->toArray();
        
        // デフォルトカラーパレット
        $defaultColors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
            '#36A2EB', '#FFCE56', '#E7E9ED', '#71B37C', '#519D9E',
            '#58595B', '#8B8C8E', '#C0C0C0', '#D4A76A', '#5DA5A2'
        ];
        
        // カテゴリに色を割り当て
        $incomeColors = [];
        $expenseColors = [];
        $colorIndex = 0;
        
        foreach ($incomeByCategory as $category => $amount) {
            $incomeColors[] = $categoryColors[$category] ?? $defaultColors[$colorIndex % count($defaultColors)];
            $colorIndex++;
        }
        
        foreach ($expenseByCategory as $category => $amount) {
            $expenseColors[] = $categoryColors[$category] ?? $defaultColors[$colorIndex % count($defaultColors)];
            $colorIndex++;
        }
        
        return view('household.monthly', compact(
            'targetMonth',
            'incomeByCategory',
            'expenseByCategory',
            'totalIncome',
            'totalExpense',
            'balance',
            'dailyData',
            'incomeChange',
            'expenseChange',
            'prevTotalIncome',
            'prevTotalExpense',
            'incomeColors',
            'expenseColors'
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
            \Log::error('Update error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'oeconomica_id' => $id,
                'data' => $validatedData,
                'trace' => $e->getTraceAsString()
            ]);
            
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

    /**
     * Export oeconomica data to CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        
        // 年月の指定（デフォルトは現在の年月）
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        
        // 指定月のデータを取得
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        $oeconomicas = Oeconomica::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // CSVファイル名
        $filename = "家計簿データ_{$year}年{$month}月_" . date('YmdHis') . '.csv';
        
        // CSVヘッダー
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        // レスポンスのコールバック
        $callback = function() use ($oeconomicas) {
            $file = fopen('php://output', 'w');
            
            // BOM付きUTF-8でエクスポート（Excel対応）
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSVヘッダー行
            fputcsv($file, [
                '日付',
                '収支区分',
                'カテゴリ',
                '金額',
                'メモ'
            ]);
            
            // データ行
            foreach ($oeconomicas as $item) {
                fputcsv($file, [
                    $item->date->format('Y/m/d'),
                    $item->balance === 'income' ? '収入' : '支出',
                    $item->category,
                    $item->amount,
                    $item->memo ?: ''
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show CSV import form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function importForm(Request $request)
    {
        $user = Auth::user();
        
        // 現在の年月を取得
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

        return view('household.import', compact(
            'user',
            'currentYear',
            'currentMonth',
            'incomeCategories',
            'expenseCategories'
        ));
    }

    /**
     * Import oeconomica data from CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importCsv(Request $request)
    {
        $user = Auth::user();
        
        // ファイルのバリデーション
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'csv_file.required' => 'CSVファイルを選択してください。',
            'csv_file.file' => '正しいファイルを選択してください。',
            'csv_file.mimes' => 'CSV形式のファイルを選択してください。',
            'csv_file.max' => 'ファイルサイズは2MB以下にしてください。',
        ]);

        $file = $request->file('csv_file');
        
        try {
            // CSVファイルを読み込み
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            
            // ヘッダー行を除去
            $header = array_shift($csvData);
            
            // データ検証とインポート
            $importCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // ユーザーのカテゴリを取得（検証用）
            $incomeCategories = Category::where('user_id', $user->id)
                ->where('type', 'income')
                ->pluck('category')
                ->toArray();
            
            $expenseCategories = Category::where('user_id', $user->id)
                ->where('type', 'expense')
                ->pluck('category')
                ->toArray();
            
            foreach ($csvData as $rowIndex => $row) {
                $lineNumber = $rowIndex + 2; // ヘッダー行を考慮
                
                // 空行はスキップ
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // カラム数チェック
                if (count($row) < 4) {
                    $errors[] = "{$lineNumber}行目: データが不足しています。";
                    $errorCount++;
                    continue;
                }
                
                $date = trim($row[0]);
                $balanceText = trim($row[1]);
                $category = trim($row[2]);
                $amount = trim($row[3]);
                $memo = isset($row[4]) ? trim($row[4]) : '';
                
                // 日付の検証
                try {
                    $parsedDate = Carbon::createFromFormat('Y/m/d', $date);
                } catch (\Exception $e) {
                    try {
                        $parsedDate = Carbon::createFromFormat('Y-m-d', $date);
                    } catch (\Exception $e2) {
                        $errors[] = "{$lineNumber}行目: 日付形式が正しくありません（{$date}）。";
                        $errorCount++;
                        continue;
                    }
                }
                
                // 収支区分の検証
                $balance = null;
                if ($balanceText === '収入') {
                    $balance = 'income';
                } elseif ($balanceText === '支出') {
                    $balance = 'expense';
                } else {
                    $errors[] = "{$lineNumber}行目: 収支区分は「収入」または「支出」で入力してください（{$balanceText}）。";
                    $errorCount++;
                    continue;
                }
                
                // カテゴリの検証（収支区分に応じて適切なカテゴリリストを使用）
                $validCategories = ($balance === 'income') ? $incomeCategories : $expenseCategories;
                $balanceTextJP = ($balance === 'income') ? '収入' : '支出';
                
                if (!in_array($category, $validCategories)) {
                    $errors[] = "{$lineNumber}行目: カテゴリ「{$category}」は{$balanceTextJP}カテゴリに存在しません。";
                    $errorCount++;
                    continue;
                }
                
                // 金額の検証
                if (!is_numeric($amount) || (int)$amount < 1) {
                    $errors[] = "{$lineNumber}行目: 金額は1以上の数値で入力してください（{$amount}）。";
                    $errorCount++;
                    continue;
                }
                
                // データを保存
                try {
                    Oeconomica::create([
                        'user_id' => $user->id,
                        'balance' => $balance,
                        'date' => $parsedDate->format('Y-m-d'),
                        'category' => $category,
                        'amount' => (int)$amount,
                        'memo' => $memo,
                    ]);
                    $importCount++;
                } catch (\Exception $e) {
                    $errors[] = "{$lineNumber}行目: データの保存に失敗しました。";
                    $errorCount++;
                }
            }
            
            // 結果をセッションに保存
            $message = "{$importCount}件のデータをインポートしました。";
            if ($errorCount > 0) {
                $message .= " {$errorCount}件のエラーがありました。";
                return back()
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }
            
            return redirect()
                ->route('household.input')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            return back()->withErrors([
                'csv_file' => 'CSVファイルの処理中にエラーが発生しました: ' . $e->getMessage()
            ]);
        }
    }
}
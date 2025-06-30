<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // ミドルウェアの設定は routes/web.php で行う
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        // バリデーション
        $validator = $this->validator($request->all());
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        // ユーザー作成
        $user = $this->create($request->all());

        // 自動ログインは行わず、ログイン画面にリダイレクト
        return redirect()->route('login')
            ->with('status', 'アカウントが正常に作成されました。ログインしてください。');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'terms_agreement' => ['required', 'accepted'],
        ], [
            'username.required' => 'ユーザー名を入力してください。',
            'username.max' => 'ユーザー名は50文字以内で入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'password.required' => 'パスワードを入力してください。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => 'パスワードが一致しません。',
            'terms_agreement.required' => '利用規約に同意してください。',
            'terms_agreement.accepted' => '利用規約に同意してください。',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // ユーザー作成
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
            ]);

            // デフォルトカテゴリを作成
            $this->createDefaultCategories($user->id);

            return $user;
        });
    }

    /**
     * Check email availability (AJAX endpoint)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['available' => false], 400);
        }

        $exists = User::where('email', $email)->exists();
        
        return response()->json(['available' => !$exists]);
    }

    /**
     * Create default categories for new user
     *
     * @param  int  $userId
     * @return void
     */
    private function createDefaultCategories($userId)
    {
        $defaultCategories = [
            // 収入カテゴリ
            ['user_id' => $userId, 'category' => '給与', 'type' => 'income', 'color' => '#4CAF50'],
            ['user_id' => $userId, 'category' => '投資収入', 'type' => 'income', 'color' => '#2196F3'],
            ['user_id' => $userId, 'category' => 'その他', 'type' => 'income', 'color' => '#FF9800'],
            
            // 支出カテゴリ
            ['user_id' => $userId, 'category' => '食費', 'type' => 'expense', 'color' => '#FF5722'],
            ['user_id' => $userId, 'category' => '酒代', 'type' => 'expense', 'color' => '#E91E63'],
            ['user_id' => $userId, 'category' => '日用品', 'type' => 'expense', 'color' => '#9C27B0'],
            ['user_id' => $userId, 'category' => '交通費', 'type' => 'expense', 'color' => '#673AB7'],
            ['user_id' => $userId, 'category' => '交際費', 'type' => 'expense', 'color' => '#3F51B5'],
            ['user_id' => $userId, 'category' => '美容費', 'type' => 'expense', 'color' => '#009688'],
            ['user_id' => $userId, 'category' => '衣服費', 'type' => 'expense', 'color' => '#795548'],
            ['user_id' => $userId, 'category' => '医療費', 'type' => 'expense', 'color' => '#607D8B'],
            ['user_id' => $userId, 'category' => '書籍', 'type' => 'expense', 'color' => '#FFC107'],
            ['user_id' => $userId, 'category' => 'サブスク', 'type' => 'expense', 'color' => '#FF9800'],
            ['user_id' => $userId, 'category' => '家賃', 'type' => 'expense', 'color' => '#F44336'],
            ['user_id' => $userId, 'category' => '水道光熱費', 'type' => 'expense', 'color' => '#CDDC39'],
            ['user_id' => $userId, 'category' => '通信費', 'type' => 'expense', 'color' => '#00BCD4'],
            ['user_id' => $userId, 'category' => '家具・家電', 'type' => 'expense', 'color' => '#4CAF50'],
            ['user_id' => $userId, 'category' => '旅行', 'type' => 'expense', 'color' => '#8BC34A'],
            ['user_id' => $userId, 'category' => 'その他', 'type' => 'expense', 'color' => '#9E9E9E'],
        ];

        foreach ($defaultCategories as $category) {
            Category::create($category);
        }
    }
}
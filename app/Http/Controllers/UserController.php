<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserRecommendation;
use App\Models\CashEntry;
use App\Models\PaymentEntry;
use Illuminate\Support\Facades\DB;

class UserController extends BaseController
{
    /**
     * Register a new user and return the user if successful.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $username = $request->input('username'); // 帳號
        $password = $request->input('password'); // 密碼
        $alias = $request->input('alias'); // 暱稱
        $telephone = $request->input('telephone'); // 電話
        $inviteCode = $request->input('invite_code', ''); // 邀請碼
        $captcha = $request->input('captcha'); // 驗證碼
        $key = $request->input('key'); // 驗證碼對應key
        $isSystem = (bool)$request->input('system'); // 是否為系統會員 (未帶即是false)
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // 驗證碼
        if (!captcha_api_check($captcha, $key, 'math')) {
            return response()->json(['result' => 'error', 'message' => 'Captcha Wrong']);
        }

        try {
            DB::beginTransaction();

            $user = User::create(
                [
                    'username' => $username,
                    'alias' => $alias,
                    'password' => $hash,
                    'system' => $isSystem
                ]
            );

            $user->code = str_pad($user->id . rand(1, 999999), 10, "0", STR_PAD_LEFT);
            $user->save();

            $inviteUser = null;

            if ($inviteCode !== '') {
                $inviteUser = User::where('code', $inviteCode)->get(); // 被推薦的使用者

                if ($inviteUser) {
                    UserRecommendation::create([
                        'user_id' => $inviteUser[0]->id,
                        'referrer_id' => $user->id,
                    ]);
                }
            }

            $userDetail = UserDetail::create(
                [
                    'user_id' => $user->id,
                    'telephone' => $telephone,
                ]
            );

            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();

            $errMsg = $e->getMessage();

            // 判斷如果是帳號重複
            if (!is_null($e->getPrevious())) {
                if ($e->getPrevious()->getCode() == 23000 && $e->getPrevious()->errorInfo[1] == 1062) {
                    $pdoMsg = $e->getMessage();

                    if (strpos($pdoMsg, 'uni_username')) {
                        $pdoMsg = 'Username already exist';
                    }

                    $errMsg = $pdoMsg;
                }
            }

            return response()->json(['result' => 'error', 'message' => $errMsg]);
        }

        $data = [
            'result' => 'ok',
            'ret' => [
                'hash' => $hash,
                'username' => $username,
                'password' => $password,
                'alias' => $alias,
                'user' => $user,
                'user_detail' => $userDetail,
                'invite_user' => $inviteUser,
            ]
        ];

        return response()->json($data);
    }

    /**
     * 建立帳號驗證碼
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCreateUserCaptcha()
    {
        return response()->json(
            [
                'result' => 'ok',
                'ret' => app('captcha')->create('math', true),
            ]
        );
    }

    /**
     * 帳號列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $startAt  = $request->query('start_at');
        $endAt    = $request->query('end_at');
        $firstResult  = $request->query('first_result');
        $maxResults   = $request->query('max_results');
        $username = $request->query('username');
        $alias = $request->query('alias');
        $enable = $request->query('enable');

        $qb = DB::table('user');

        if ($startAt) {
            $qb->where('created_at', '>=', $startAt);
        }

        if ($endAt) {
            $qb->where('created_at', '<=', $endAt);
        }

        if ($username) {
            $qb->where('username', 'like', $username);
        }

        if ($alias) {
            $qb->where('alias', 'like', $alias);
        }

        if ($enable) {
            $qb->where('enable', '=', (bool)$enable);
        }

        $total = $qb->count();

        if ($firstResult) {
            $qb->offset($firstResult);
        }

        if ($maxResults) {
            $qb->limit($maxResults);
        }

        $users = $qb->get();

        return response()->json(
            [
                'result' => 'ok',
                'ret' => $users,
                'pagination' => [
                    'first_result' => $firstResult,
                    'max_results' => $maxResults,
                    'total' => $total,
                ],
            ]
        );
    }

    /**
     * 取得使用者
     *
     * @param $userId 使用者ID
     * @param Request $request
     */
    public function get($userId, Request $request)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['result' => 'error', 'message' => 'User not found']);
        }

        return response()->json(
            [
                'result' => 'ok',
                'ret' => $user
            ]
        );
    }

    /**
     * 修改使用者
     *
     * @param $userId 使用者ID
     * @param Request $request
     */
    public function edit($userId, Request $request) {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['result' => 'error', 'message' => 'User not found']);
        }

        $username = $request->input('username'); // 帳號
        $alias = $request->input('alias'); // 暱稱
        $password = $request->input('password'); // 密碼
        $confirmPassword = $request->input('confirm_password'); //確認密碼
        $enable = $request->input('enable'); // 停啟用
        $block = $request->input('block'); // 是否凍結

        // 帳號修改
        if ($username) {
            // 檢查是否有重複非本人的帳號
            $duplicateUser = DB::table('user')->where('username', $username)->where('id', '!=', $userId)->first();

            if ($duplicateUser) {
                return response()->json(['result' => 'error', 'message' => 'Username already used']);
            }

            $user->username = $username;
        }

        // 名稱修改
        if ($alias) {
            $user->alias = $alias;
        }

        // 密碼修改
        if ($password) {
            if ($confirmPassword !== $password) {
                return response()->json(['result' => 'error', 'message' => 'Password and confirm password are different']);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $user->password = $hash;
        }

        // 是否停啟用
        if (isset($enable)) {
            $user->enable = (bool)$enable;
        }

        // 是否凍結
        if (isset($block)) {
            $isBlock = (bool)$block;

            if ($isBlock) {
                $user->block = true;
            } else {
                // 如果解凍要歸零錯誤次數
                $user->block = false;
                $user->err_num = 0;
            }
        }

        $user->save();

        return response()->json(
            [
                'result' => 'ok',
                'ret' => $user,
            ]
        );
    }

    /**
     * 使用者人工交易
     *
     * @param $userId 使用者ID
     * @param Request $request
     */
    public function op($userId, Request $request)
    {
        $amount = $request->input('amount'); // 額度
        $opcode = CashEntry::DEPOSIT_MANUAL;

        DB::beginTransaction();

        try {
            // 交易鎖
            $user = User::lockForUpdate()->find($userId);

            if (!$user) {
                throw new \Exception('User not found');
            }

            if ($user->balance - $user->pre_sub + $amount < 0) {
                throw new \Exception('Not enough balance');
            }

            if ($amount > 0) {
                $user->increment('balance', $amount);
            }

            if ($amount < 0) {
                $user->decrement('balance', abs($amount));
                $opcode = CashEntry::WITHDRAWAL_MANUAL;
            }

            $user->save();

            $cashEntry = CashEntry::create(
                [
                    'user_id' => $userId,
                    'opcode' => $opcode,
                    'amount' => $amount,
                    'balance' => $user->balance,
                    'memo' => '人工存提'
                ]
            );

            DB::commit();

            return response()->json(
                [
                    'result' => 'ok',
                    'ret' => $user,
                ]
            );


        } catch (\Exception $e) {
            DB::rollBack();

            $errMsg = $e->getMessage();

            return response()->json(['result' => 'error', 'message' => $errMsg]);
        }
    }

    /**
     * 使用者入款交易
     *
     * @param $userId 使用者ID
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deposit($userId, Request $request)
    {
        $amount = $request->input('amount'); // 額度
        $depositType = $request->input('deposit_type'); // 入款方案

        if ($amount <= 0) {
            return response()->json(['result' => 'error', 'message' => 'Amount can not be zero or negative']);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['result' => 'error', 'message' => 'User not found']);
        }

        if (!$user->kyc_verified) { // 使用者未實名認證無法進行入款
            return response()->json(['result' => 'error', 'message' => 'User need to kyc verify']);
        }

        $userDetail = UserDetail::find($userId);

        if (!$userDetail) {
            return response()->json(['result' => 'error', 'message' => 'UserDetail not found']);
        }

        // 檢查該使用者入款單是否還有未完成的
        $entry = DB::table('payment_entry')->where('user_id', $userId)->where('payment_type', PaymentEntry::DEPOSIT)->where('status', PaymentEntry::UNTREATED)->first();

        if ($entry) {
            return response()->json(['result' => 'error', 'message' => 'User had desposit entry not confirm']);
        }

        $entry = PaymentEntry::create(
            [
                'user_id' => $userId,
                'username' => $user->username,
                'payment_type' => PaymentEntry::DEPOSIT,
                'amount' => $amount,
                'deposit_type' => $depositType,
                'name_real' => $userDetail->name_real,
            ]
        );

        return response()->json(
            [
                'result' => 'ok',
                'ret' => $entry,
            ]
        );

    }
}

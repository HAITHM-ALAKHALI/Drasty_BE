<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * تسجيل دخول المستخدم وإصدار التوكن
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // محاولة التحقق وإصدار التوكن عبر حارس api المعرف بـ jwt
        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'status'  => false,
                'message' => 'بيانات الدخول غير صحيحة، يرجى التأكد من البريد الإلكتروني وكلمة المرور.',
            ], 401);
        }

        $user = auth('api')->user();

        // التحقق من حالة الحساب إن وُجدت
        if (isset($user->status) && $user->status !== 'active') {
            auth('api')->logout();
            return response()->json([
                'status'  => false,
                'message' => 'هذا الحساب معطل أو بانتظار التفعيل.',
            ], 403);
        }

        return response()->json([
            'status'       => true,
            'message'      => 'تم تسجيل الدخول بنجاح.',
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60, // بالثواني
            'user'         => [
                'user_id'   => $user->user_id,
                'full_name' => $user->full_name,
                'email'     => $user->email,
                'role_id'   => $user->role_id,
            ],
        ], 200);
    }

    /**
     * جلب بيانات صاحب التوكن الحالي
     */
    public function me()
    {
        return response()->json([
            'status' => true,
            'user'   => auth('api')->user(),
        ], 200);
    }

    /**
     * تسجيل الخروج وإبطال صلاحية التوكن
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'status'  => true,
            'message' => 'تم تسجيل الخروج بنجاح.',
        ], 200);
    }

    /**
     * تجديد التوكن المنتهي أو شبه المنتهي
     */
    public function refresh()
    {
        return response()->json([
            'status'       => true,
            'access_token' => auth('api')->refresh(),
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ], 200);
    }
}

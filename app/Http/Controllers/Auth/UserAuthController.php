<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\password_reset_tokens;
use App\Notifications\ResetPasswordRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class UserAuthController extends Controller
{

//    /**
//     * @OA\Post(
//     *     path="/api/register",
//     *     tags={"Authentication"},
//     *     summary="Đăng ký người dùng mới",
//     *     description="
//     *      - Endpoint này cho phép đăng ký người dùng mới vào hệ thống.
//     *      - Trả về thông tin của người dùng đã đăng ký.
//     *      - Role được sử dụng là nhân viên và sinh viên",
//     *     @OA\RequestBody(
//     *         required=true,
//     *         @OA\JsonContent(
//     *             @OA\Property(property="name", type="string", example="John Doe"),
//     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
//     *             @OA\Property(property="password", type="string", example="password123"),
//     *             @OA\Property(property="phone", type="string", example="123456789"),
//     *             @OA\Property(property="role", type="int", example=1),
//     *         )
//     *     ),
//     *     @OA\Response(
//     *         response=200,
//     *         description="Thành công",
//     *         @OA\JsonContent(
//     *             @OA\Property(property="metadata", type="object", example={"id": 1, "name": "John Doe", "email": "john.doe@example.com", "phone": "123456789", "role": "user"}),
//     *             @OA\Property(property="message", type="string", example="Đăng ký người dùng thành công"),
//     *             @OA\Property(property="status", type="string", example="success"),
//     *             @OA\Property(property="statusCode", type="int", example=200),
//     *         )
//     *     ),
//     *     @OA\Response(
//     *         response=500,
//     *         description="Lỗi máy chủ nội bộ",
//     *         @OA\JsonContent(
//     *             @OA\Property(property="status", type="string", example="error"),
//     *             @OA\Property(property="message", type="object", example={"name": {"tên không thể để trống"}, "email": {"email không thể để trống"}}),
//     *             @OA\Property(property="statusCode", type="int", example=500),
//     *         )
//     *     )
//     * )
//     */

    public function register(Request $request)
    {
        $validator   = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|unique:users,email',
            'password' => 'required',
            'phone' => 'required',
            'role' => ['required',
                Rule::in([0, 1])]
        ], [
            'name.required' => 'tên không thể để trống',
            'name.max' => 'Tối đa 255 ký tự được phép',
            'email.required' => 'email không thể để trống',
            'email.unique' => 'email đã tồn tại',
            'password.required' => 'mật khẩu không thể để trống',
            'phone.required' => 'số điện thoại không thể để trống',
            'role.required' => 'vai trò không thể để trống',
            'role.in' => 'Role phải là Nhân viên hoặc sinh viên'
        ]);
        if($validator->fails()){
            return response([
                "status" => "error",
                "message" => $validator->errors(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $data = $validator->validated();
        $data['password'] = bcrypt($request->password);
        $user = User::create($data);
        return response()->json([
            'metadata' => $user,
            'message' => 'Tạo tài khoản thành công',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Đăng nhập người dùng",
     *     description="
     *      - Endpoint này cho phép người dùng đăng nhập vào hệ thống.
     *      - Trả về thông tin của người dùng đã đăng nhập, bao gồm cả token đăng nhập.
     *      - Role được sử dụng là cả ba role nhân viên ,quản lí ,sinh viên",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="metadata", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="phone", type="string", example="123456789"),
     *                 @OA\Property(property="role", type="integer", example=1),
     *                 @OA\Property(property="token", type="string", example="api-token")
     *             ),
     *             @OA\Property(property="message", type="string", example="Đăng nhập người dùng thành công"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi máy chủ nội bộ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Thông tin đăng nhập không chính xác. Vui lòng thử lại"),
     *             @OA\Property(property="statusCode", type="integer", example=500),
     *         )
     *     )
     * )
     */



    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if (!auth()->attempt($data)) {
            return response([
                "status" => "error",
                "message" => 'Tài khoản mật khẩu không chính xác.
            Vui lòng thử lại',
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $token = Auth::attempt($data);

        return response()->json([
            'metadata' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'user' => auth()->user()
            ],
            'message' => 'Đăng nhập thành công',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);

    }

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Send password reset email",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", description="User's email"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset email sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Success message"),
     *             @OA\Property(property="status", type="string", description="Status"),
     *             @OA\Property(property="statusCode", type="integer", description="HTTP status code"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Error message"),
     *             @OA\Property(property="status", type="string", description="Status"),
     *             @OA\Property(property="statusCode", type="integer", description="HTTP status code"),
     *         )
     *     ),
     * )
     */
    public function sendMail(Request $request){

        $user = User::where('email', $request->email)->first();
        if(!$user){
            return response()->json([
                'message' => 'Chúng tôi không thể tìm thấy người dùng với địa chỉ email này.',
                'status' => 'error',
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $passwordReset = password_reset_tokens::updateOrCreate([
            'email' => $user->email,
        ], [
            'token' => Str::random(10),
        ]);
        if ($passwordReset) {
            $user->notify(new ResetPasswordRequest($passwordReset->token));
        }

        return response()->json([
            'message' => 'Chúng tôi đã gửi email liên kết đặt lại mật khẩu của bạn!',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/check-password",
     *     summary="Check mật khẩu cũ có khớp với mật khẩu hiện tại",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", description="User's email"),
     *             @OA\Property(property="password", type="string", format="password", description="User's password"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset email sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="metadata", type="boolean", description="true"),
     *             @OA\Property(property="message", type="string", description="Mật Khẩu chính xác"),
     *             @OA\Property(property="status", type="string", description="Status"),
     *             @OA\Property(property="statusCode", type="integer", description="HTTP status code"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Error message"),
     *             @OA\Property(property="status", type="string", description="Status"),
     *             @OA\Property(property="statusCode", type="integer", description="HTTP status code"),
     *         )
     *     ),
     * )
     */
    public function checkPass(Request $request){
        $validate = Validator::make($request->all(),[
            'password' => 'required',
            'email' => 'required|email|exists:users,email'
        ],[
            'password.required' => 'Mật khẩu không thể để trống',
            'email.required' => 'Email không thể để trống',
            'email.email' => 'Email không đúng định dạng',
            'email.exists' => 'Email không tồn tại'
        ]);
        if($validate->fails()){
            return response([
                "status" => "error",
                "message" => $validate->errors()->all(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = User::where('email', $request->email)->first();

        if(!Hash::check($request->password, $user->password)){
            return response()->json([
                'metadata' => false,
                'message' => 'Mật khẩu không chính xác',
                'status' => 'error',
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'metadata' => true,
            'message' => 'Mật khẩu chính xác',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);

    }

    /**
     * @OA\Put(
     *     path="/api/reset-password/{token}",
     *     summary="Reset password",
     *     tags={"Authentication"},
     *     description="
     *      - token là mã được gửi về email address của người dùng.
     *     - password là mật khẩu mới của người dùng.
     *     ",
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Password reset token",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="password", type="string", description="New password"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="metadata", type="mixed", description="Metadata"),
     *             @OA\Property(property="message", type="string", description="Success message"),
     *             @OA\Property(property="status", type="string", description="Status"),
     *             @OA\Property(property="statusCode", type="integer", description="HTTP status code"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid reset token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Error message"),
     *         )
     *     ),
     * )
     */
    public function reset(Request $request, $token){
        $passwordReset = password_reset_tokens::where('token', $token)->firstOrFail();
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();

            return response()->json([
                'message' => 'Mã đặt lại mật khẩu này không hợp lệ.',
            ], 422);
        }
        $user = User::where('email', $passwordReset->email)->firstOrFail();
        $updatePasswordUser = $user->update($request->only('password'));
        $passwordReset->delete();
        return response()->json([
            'metadata' => $updatePasswordUser,
            'message' => 'Đặt lại mật khẩu thành công',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use function PHPUnit\Framework\throwException;

class GoogleController extends Controller
{

    public function __construct(){
        $this->middleware('auth:api',[
            'except' => [
                "loginCallback",
                "getGoogleSignInUrl",
                "callback"
            ]
        ]);
    }

//    /**
//     * @OA\Post(
//     *     path="/api/get-google-sign-in-url",
//     *     summary="Get Google Sign-In URL",
//     *     tags={"Authentication"},
//     *     operationId="getGoogleSignInUrl",
//     *     @OA\Response(
//     *         response=200,
//     *         description="Successful operation",
//     *         @OA\JsonContent(
//     *             @OA\Property(property="metadata", type="object", @OA\Property(property="url", type="string")),
//     *             @OA\Property(property="message", type="string"),
//     *             @OA\Property(property="status", type="string"),
//     *             @OA\Property(property="statusCode", type="integer")
//     *         )
//     *     ),
//     *     @OA\Response(
//     *         response=500,
//     *         description="Internal Server Error",
//     *         @OA\JsonContent(
//     *             @OA\Property(property="status", type="string"),
//     *             @OA\Property(property="message", type="string"),
//     *             @OA\Property(property="statusCode", type="integer")
//     *         )
//     *     )
//     * )
//     */
    public function getGoogleSignInUrl()
    {
        try {
            $url = Socialite::driver('google')->stateless()
                ->redirect()->getTargetUrl();
//            return response()->json([
//                'url' => $url,
//            ])->setStatusCode(Response::HTTP_OK);
            return response()->json([
                'metadata' => ['url' => $url],
                'message' => 'Lấy thành công url login',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return $exception;
        }
    }

// /**
// * @OA\Get(
// *      path="/api/auth/google",
// *      operationId="googleAuth",
// *      tags={"Authentication"},
// *      summary="Authenticate with Google",
// *      description="Authenticate with Google and get an access token.",
// *      @OA\RequestBody(
// *          required=true,
// *          @OA\MediaType(
// *              mediaType="application/json",
// *              @OA\Schema(
// *                  @OA\Property(
// *                      property="Authorization",
// *                      type="string",
// *                      example="Bearer YOUR_GOOGLE_TOKEN"
// *                  ),
// *              ),
// *          ),
// *      ),
// *      @OA\Response(
// *          response=200,
// *          description="Successful operation",
// *          @OA\JsonContent(
// *              @OA\Property(property="metadata", type="object"),
// *              @OA\Property(property="message", type="string"),
// *              @OA\Property(property="status", type="string"),
// *              @OA\Property(property="statusCode", type="integer"),
// *          ),
// *      ),
// *      @OA\Response(
// *          response=400,
// *          description="Bad request",
// *          @OA\JsonContent(
// *              @OA\Property(property="status", type="string"),
// *              @OA\Property(property="message", type="string"),
// *              @OA\Property(property="statusCode", type="integer"),
// *          ),
// *      ),
// *      @OA\Response(
// *          response=401,
// *          description="Unauthorized",
// *          @OA\JsonContent(
// *              @OA\Property(property="status", type="string"),
// *              @OA\Property(property="message", type="string"),
// *              @OA\Property(property="statusCode", type="integer"),
// *          ),
// *      ),
// * )
// */
    public function loginCallback(Request $request)
    {
        try {
//        $testToekn = 'ya29.a0AfB_byBR-TgPRUsUTpYWO7e8ni_1UkI_8SrcdUw2_tkxCGOvRgBtop6tKHRVxN-ahtFKFLCxn77eX5-mb-nnVAh1ZdYU97CYIxbeQG-CUCLfjaV58lPYrZtS4ygtMghqW76YG2WAXs7sZs_2hbtg-A0L1ZOPdanCMzfVaCgYKAUkSARESFQHGX2MiIyPhfqchpJX8aY2AIaChAA0171';
//            $state = $request->input('state');
//            dd($request->header('Authorization'));
//            $googleUser = Socialite::driver('google')->stateless()->user();
//            echo $googleUser->token;
//            die();
//            parse_str($state, $result);
            $tokenapi = Str::replace('Bearer ','',$request->header('Authorization'));
//            dd($tokenapi);
            $googleUser = Socialite::driver('google')->userFromToken($tokenapi);
//            dd($googleUser->id);
//            echo $googleUser->token;
//            die();
//            $finduser = User::where('google_id', $googleUser->id)->first();
//            dd($googleUser->token);
//            $request->header('Authorization');
//            $googleUser = Http::get('https://www.googleapis.com/oauth2/v3/userinfo?access_token='.$tokenapi);
//            dd($googleUser->failed());
//            if($googleUser->failed()){
//                return response([
//                    "status" => "error",
//                    "message" => "Xác thực thất bại",
//                    'statusCode' => Response::HTTP_BAD_REQUEST
//                ], Response::HTTP_BAD_REQUEST);
//            }
            $finduser = User::where('google_id', $googleUser->id)->first();
//
            if($finduser){
               $token = Auth::login($finduser);
//                $finduser->token = auth()->user()->createToken("API Token")->accessToken;
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
            }else{
                $newUser = User::create([
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'google_id'=> $googleUser->id,
                    'password'=> bcrypt('123'),
                    'avatar' => $googleUser->avatar
                ]);
//                $newUser->token = $newUser->createToken('API Token')->accessToken;
//                $newUser->token = $newUser->createToken('API Token')->accessToken;
                $token = Auth::login($newUser);
                return response()->json([
                    'metadata' => [
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        'user' => auth()->user()
                    ],
                    'message' => 'Đăng ký thành công',
                    'status' => 'success',
                    'statusCode' => Response::HTTP_OK
                ], Response::HTTP_OK);

            }
//            $user = User::firstOrCreate(
//                [
//                    'email' => $googleUser->email,
//                    'name' => $googleUser->name,
//                    'google_id'=> $googleUser->id,
//                    'password'=> bcrypt('123'),
//                    'avatar' => $googleUser->avatar
//                ]
//            );
//            return response()->json([
//                'status' => __('google sign in successful'),
//                'data' => $user,
//            ], Response::HTTP_CREATED);

        } catch (\Exception $exception) {
            return response([
                "status" => "error",
                "message" =>$exception->getMessage(),
                'statusCode' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function callback()
    {
        try {
//        $testToekn = 'ya29.a0AfB_byBR-TgPRUsUTpYWO7e8ni_1UkI_8SrcdUw2_tkxCGOvRgBtop6tKHRVxN-ahtFKFLCxn77eX5-mb-nnVAh1ZdYU97CYIxbeQG-CUCLfjaV58lPYrZtS4ygtMghqW76YG2WAXs7sZs_2hbtg-A0L1ZOPdanCMzfVaCgYKAUkSARESFQHGX2MiIyPhfqchpJX8aY2AIaChAA0171';
//            $state = $request->input('state');
//            dd($request->header('Authorization'));
            $googleUser = Socialite::driver('google')->stateless()->user();
//            dd($googleUser->id);
//            echo $googleUser->token;
//            die();
//            $finduser = User::where('google_id', $googleUser->id)->first();
//            dd($googleUser->token);
//            $request->header('Authorization');
//            $googleUser = Http::get('https://www.googleapis.com/oauth2/v3/userinfo?access_token='.$tokenapi);
//            dd($googleUser->failed());
//            if($googleUser->failed()){
//                return response([
//                    "status" => "error",
//                    "message" => "Xác thực thất bại",
//                    'statusCode' => Response::HTTP_BAD_REQUEST
//                ], Response::HTTP_BAD_REQUEST);
//            }
            $finduser = User::where('google_id', $googleUser->id)->first();
//
            if($finduser){
                $token = Auth::login($finduser);
//                $finduser->token = auth()->user()->createToken("API Token")->accessToken;
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
            }else{
                $newUser = User::create([
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'google_id'=> $googleUser->id,
                    'password'=> bcrypt('123'),
                    'avatar' => $googleUser->avatar
                ]);
//                $newUser->token = $newUser->createToken('API Token')->accessToken;
//                $newUser->token = $newUser->createToken('API Token')->accessToken;
                $token = Auth::login($newUser);
                return response()->json([
                    'metadata' => [
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        'user' => auth()->user()
                    ],
                    'message' => 'Đăng ký thành công',
                    'status' => 'success',
                    'statusCode' => Response::HTTP_OK
                ], Response::HTTP_OK);

            }
//            $user = User::firstOrCreate(
//                [
//                    'email' => $googleUser->email,
//                    'name' => $googleUser->name,
//                    'google_id'=> $googleUser->id,
//                    'password'=> bcrypt('123'),
//                    'avatar' => $googleUser->avatar
//                ]
//            );
//            return response()->json([
//                'status' => __('google sign in successful'),
//                'data' => $user,
//            ], Response::HTTP_CREATED);

        } catch (\Exception $exception) {
            return response([
                "status" => "error",
                "message" =>$exception->getMessage(),
                'statusCode' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}

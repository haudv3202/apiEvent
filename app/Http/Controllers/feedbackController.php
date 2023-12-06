<?php

namespace App\Http\Controllers;

use App\Models\feedback;
use Illuminate\Http\Request;
use App\Http\Resources\feedbackResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class feedbackController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/feedback/{id_event}",
     *     summary="Lấy ra tất cả phản hồi từ người dùng gửi đến theo sự kiện",
     *     tags={"feedback"},
     *     description="
     *      - Endpoint này cho phép lấy ra thông tin phản hồi theo sự kiện.
     *      - Trả về thông tin của các user được cài đặt.
     *      - Role được sử dụng quản quản lí, nhân viên, sinh viên
     *      - id_event là id sự kiện ",
     *     @OA\Response(
     *         response=200,
     *         description="Dữ liệu trả về thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Lấy tất cả phản hồi thành công"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="content", type="string", example="hậu đặng vippro max"),
     *                     @OA\Property(property="user_id", type="integer", example="1"),
     *                     @OA\Property(property="event_id", type="integer", example="2"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="1"),
     *                         @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                         @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                         @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                         @OA\Property(property="role", type="integer", example="1"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal server error"),
     *             @OA\Property(property="statusCode", type="int", example=500)
     *         )
     *     )
     * )
     */

    public function index($id_event)
    {
        try {
            $feedback = feedback::where('event_id',$id_event)->with('user')->get();
            return response()->json([
                'metadata' => $feedback,
                'message' => 'Lấy tất cả phản hồi thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        }catch(\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => $e instanceof HttpException
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR // Internal Server Error by default
            ], $e instanceof HttpException
                ? $e->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/feedback",
     *     tags={"feedback"},
     *     summary="Tạo 1 phản hồi mới",
     *     description="
     *      - Endpoint này cho phép thêm phản hồi vào sự kiện.
     *      - Trả về thông tin các phản hồi của sự kiện đó.
     *      - Role được sử dụng quản quản lí,nhân viên,sinh viên
     *      - id_event là id sự kiện
     *      - user_id là id của người dùng
     *      - content là nội dung phản hồi của người dùng đó",
     *     operationId="storeFeedback",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="2"),
     *             @OA\Property(property="content", type="string", example="Feedback content"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Thêm phản hồi thành công"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example="3"),
     *                 @OA\Property(property="content", type="string", example="Feedback content"),
     *                 @OA\Property(property="user_id", type="integer", example="2"),
     *                 @OA\Property(property="event_id", type="integer", example="1"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-05T12:36:46.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-05T12:36:46.000000Z"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="2"),
     *                     @OA\Property(property="name", type="string", example="Tony Herman"),
     *                     @OA\Property(property="email", type="string", example="mohr.delphine@example.com"),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="phone", type="string", example="+1-458-920-6456"),
     *                     @OA\Property(property="role", type="integer", example="1"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Validation error or internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="object", example={"user_id": {"User does not exist"}}),
     *             @OA\Property(property="statusCode", type="int", example=500)
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required',
                'event_id' => 'required|exists:events,id',
                'user_id' => 'required|exists:users,id',
            ], [
                'content.required' => 'Nội dung không được để trống',
                'event_id.required' => 'ID của event không được để trống',
                'user_id.required' => 'ID người dùng cũng không được để trống',
                'user_id.exists' => 'Người dùng không tồn tại',
                'event_id.exists' => 'Sự kiện không tồn tại',
            ]);

            if($validator->fails()){
                return response([
                    "status" => "error",
                    "message" => $validator->errors(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $feedback = feedback::create($request->all());
            $feedbackWithUser = feedback::with('user')->find($feedback->id);
            return response()->json([
                'metadata' => $feedbackWithUser,
                'message' => 'Thêm phản hồi thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e){
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => $e instanceof HttpException
                    ? $e->getStatusCode()
                    : 500 // Internal Server Error by default
            ], $e instanceof HttpException
                ? $e->getStatusCode()
                : 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/feedback/show/{id}",
     *     summary="Lấy ra phản hồi của user đó",
     *     description="
     *      - Endpoint này cho phép thêm phản hồi vào sự kiện.
     *      - Trả về thông tin các phản hồi của sự kiện đó.
     *      - Role được sử dụng quản quản lí, nhân viên, sinh viên
     *      - id là của feedback đó",
     *     tags={"feedback"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của bản ghi feedback",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Get One Record Successfully"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example="2"),
     *                 @OA\Property(property="content", type="string", example="thảo ngớ"),
     *                 @OA\Property(property="user_id", type="integer", example="1"),
     *                 @OA\Property(property="event_id", type="integer", example="2"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                     @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                     @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                     @OA\Property(property="role", type="integer", example="1"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Record not exists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Comment không tồn tại"),
     *             @OA\Property(property="statusCode", type="int", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal server error"),
     *             @OA\Property(property="statusCode", type="int", example=500)
     *         )
     *     )
     * )
     */

    public function show($id)
    {
        try {
            $feedback = feedback::find($id);
            if(!$feedback){
                return response([
                    "status" => "error",
                    "message" => "Comment không tồn tại",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $feedback->load('user');
            return response()->json([
                'metadata' => $feedback,
                'message' => 'Get One Record Successfully',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response([
                "status" => "error",
                "message" => "Record not exists",
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/feedback/{id}",
     *     tags={"feedback"},
     *     summary="Cập nhật lại phản hồi",
     *     description="
     *      - Endpoint này cho phép cập nhật lại phản hồi vào sự kiện.
     *      - Trả về thông tin các phản hồi của sự kiện đó.
     *      - Role được sử dụng quản quản lí,nhân viên,sinh viên
     *      - id là của feedback đó",
     *     operationId="updateFeedback",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the feedback record to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="2"),
     *             @OA\Property(property="content", type="string", example="Updated content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Cập nhật thành công phản hồi"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example="3"),
     *                 @OA\Property(property="content", type="string", example="Feedback content update"),
     *                 @OA\Property(property="user_id", type="integer", example="2"),
     *                 @OA\Property(property="event_id", type="integer", example="1"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-05T12:36:46.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-05T12:39:16.000000Z"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="2"),
     *                     @OA\Property(property="name", type="string", example="Tony Herman"),
     *                     @OA\Property(property="email", type="string", example="mohr.delphine@example.com"),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="phone", type="string", example="+1-458-920-6456"),
     *                     @OA\Property(property="role", type="integer", example="1"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi hệ thống"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */


    public function update(Request $request, $id)
    {
        try {
            $feedback = feedback::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'content' => 'required',
                'event_id' => 'required|exists:events,id',
                'user_id' => 'required|exists:users,id',
            ], [
                'content.required' => 'Nội dung không được để trống',
                'event_id.required' => 'ID của event không được để trống',
                'user_id.required' => 'ID người dùng cũng không được để trống',
                'user_id.exists' => 'Người dùng không tồn tại',
                'event_id.exists' => 'Sự kiện không tồn tại',
            ]);

            if ($validator->fails()) {
                return response([
                    "status" => "error",
                    "message" => $validator->errors(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $feedback->update($request->all());
            $feedbackWithUser = feedback::with('user')->find($id);
            return response()->json([
                'metadata' => $feedbackWithUser,
                'message' => 'Cập nhật thành công phản hồi',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response([
                "status" => "error",
                "message" => $e->getMessage(),
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/feedback/{id}",
     *     tags={"feedback"},
     *     summary="Xóa bình luận",
     *     description="
     *      - Xóa 1 phản hồi bằng ID
     *      - Trả về thông báo xóa thành công.
     *      - Role được sử dụng quản quản lí,nhân viên,sinh viên
     *      - id là của feedback đó",
     *     operationId="deleteFeedback",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the feedback record",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Xóa phản hồi thành công"),
     *             @OA\Property(property="statusCode", type="int", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại"),
     *             @OA\Property(property="statusCode", type="int", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal server error"),
     *             @OA\Property(property="statusCode", type="int", example=500)
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $feedback = Feedback::findOrFail($id);
            if (!$feedback) {
                return response()->json([
                    'message' => 'Phản hồi không tồn tại',
                    'status' => 'error',
                    'statusCode' => Response::HTTP_NOT_FOUND
                ], Response::HTTP_NOT_FOUND);
            }
            $feedback->delete();

            return response()->json([
                'message' => 'Xóa Phản hồi thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response([
                "status" => "error",
                "message" => $e->getMessage(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

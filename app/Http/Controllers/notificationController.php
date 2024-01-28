<?php

namespace App\Http\Controllers;

use App\Models\notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailApi;
use Illuminate\Support\Facades\Validator;
use App\Models\event;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class notificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notification",
     *     summary="Get all set notification",
     *     tags={"notification"},
     *     description="
     *      - endpoint này trả về các thông báo đang cài đặt.
     *      - Trả về thông tin về người dùng đã đặt thông báo.
     *      - Vai trò: Quản lí và nhân viên
     *      - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     *     ",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved all records"),
     *         @OA\Property(
     *                 property="metadata",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Nhắc nhở email"),
     *                     @OA\Property(property="content", type="string", example="<h1 style='color:red;'>Test message</h1>"),
     *                     @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-25 01:42:27"),
     *                     @OA\Property(property="sent_at", type="string", format="date-time", example="2023-11-25 01:50:33"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
     *                 )
     *             ),
     *                 @OA\Property(property="totalDocs", type="integer", example=16),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=2),
     *                 @OA\Property(property="page", type="integer", example=2),
     *                 @OA\Property(property="pagingCounter", type="integer", example=2),
     *                 @OA\Property(property="hasPrevPage", type="boolean", example=true),
     *                 @OA\Property(property="hasNextPage", type="boolean", example=false)
     *             )
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="Record not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Record not found"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     * @OA\Response(
     *         response=500,
     *         description="System error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="System error"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        try {
            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người Get không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
            $query = notification::with(['event', 'create_by', 'userJoin.user']);
            $notification = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            if ($page > $notification->lastPage()) {
                $page = 1;
//                with('user_receiver')->
                $notification = notification::with(['event', 'create_by', 'userJoin.user'])->paginate($limit, ['*'], 'page', $page);
            }
            return response()->json(handleData($status, $notification), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }

//    /**
//     * @OA\Get(
//     *     path="/api/notification/search",
//     *     summary="Tìm kiếm thông báo",
//     *     tags={"notification"},
//     *     description="
//     * - Tìm kiếm thông báo dựa trên các thông số khác nhau.
//     * - Vai trò: Quản trị viên và Nhân viên.
//     *      - Tùy chọn có sẵn:
//     * - `page=<page_number>`: Chuyển sang trang được chỉ định.
//     * - `limit=<record_count>`: Số lượng bản ghi trên một trang.
//     * - `pagination=true|false`: Bật hoặc tắt phân trang (mặc định là false).
//     * - `type=<all|sent|deleted|scheduled>`: Loại thông báo.
//     * - `sender=<sender_id>`: ID của người gửi.
//     * - `title=<notification_title>`: Tiêu đề của thông báo.
//     * - `from_date=<start_date>`: Ngày bắt đầu tìm kiếm.
//     * - `to_date=<end_date>`: Ngày kết thúc tìm kiếm (mặc định là ngày hiện tại).
//     *     ",
//     *     @OA\Parameter(
//     *         name="page",
//     *         in="query",
//     *         schema={"type"="integer"},
//     *         description="Page number"
//     *     ),
//     *     @OA\Response(
//     *         response=200,
//     *         description="Successful operation",
//     *         @OA\JsonContent(
//     *             type="object",
//     *             @OA\Property(property="status", type="string", example="success"),
//     *             @OA\Property(property="message", type="string", example="Successfully retrieved search results"),
//     *             @OA\Property(
//     *                 property="metadata",
//     *                 type="object",
//     *                 @OA\Property(property="id", type="integer", example=1),
//     *                 @OA\Property(property="title", type="string", example="Nhắc nhở email"),
//     *                 @OA\Property(property="content", type="string", example="<h1 style='color:red;'>Test message</h1>"),
//     *                 @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-25 01:42:27"),
//     *                 @OA\Property(property="sent_at", type="string", format="date-time", example="2023-11-25 01:50:33"),
//     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
//     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
//     *             ),
//     *             @OA\Property(property="totalDocs", type="integer", example=16),
//     *             @OA\Property(property="limit", type="integer", example=10),
//     *             @OA\Property(property="totalPages", type="integer", example=2),
//     *             @OA\Property(property="page", type="integer", example=2),
//     *             @OA\Property(property="pagingCounter", type="integer", example=2),
//     *             @OA\Property(property="hasPrevPage", type="boolean", example=true),
//     *             @OA\Property(property="hasNextPage", type="boolean", example=false)
//     *         )
//     *     ),
//     * @OA\Response(
//     *         response=404,
//     *         description="Record not found",
//     *         @OA\JsonContent(
//     *             type="object",
//     *             @OA\Property(property="status", type="string", example="error"),
//     *             @OA\Property(property="message", type="string", example="Record not found"),
//     *             @OA\Property(property="statusCode", type="integer", example=404)
//     *         )
//     *     ),
//     * @OA\Response(
//     *         response=500,
//     *         description="System error",
//     *         @OA\JsonContent(
//     *             type="object",
//     *             @OA\Property(property="status", type="string", example="error"),
//     *             @OA\Property(property="message", type="string", example="System error"),
//     *             @OA\Property(property="statusCode", type="integer", example=500)
//     *         )
//     *     )
//     * )
//     */

//    public function search(Request $request)
//    {
//        try {
//            if (Auth::user()->role == 0) {
//                return response([
//                    "status" => "error",
//                    "message" => "Role người Get không hợp lệ.Vui lòng thử lại!!",
//                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
//                ], Response::HTTP_INTERNAL_SERVER_ERROR);
//            }
//            $page = $request->query('page', 1);
//            $limit = $request->query('limit', 10);
//            $status = $request->query('pagination', false);
//            // Bắt đầu xây dựng query
//            $query = notification::with(['event', 'create_by', 'userJoin.user']);
//
//// Tìm kiếm theo type
//            $type = $request->query('type', 'all');
//
//// Tìm kiếm theo sender (người tạo thông báo)
//            $sender = $request->query('sender');
//            if ($sender) {
//
//                $query->where('user_id', $sender);
//            }
//
//// Tìm kiếm theo title (tiêu đề thông báo)
//            $title = $request->query('title');
//            if ($title) {
//                $query->where('title', 'like', '%' . $title . '%');
//            }
//
//// Tìm kiếm theo from_date và to_date
//            $fromDate = $request->query('from_date');
//            $toDate = $request->query('to_date', now()->toDateString());
//
//            if ($fromDate) {
//
//                $query->where('sent_at', '>=', $fromDate);
//
//                if ($toDate) {
//                    $query->where('sent_at', '<=', $toDate);
//                }
//            }
//
////            dd($query->toSql());
//
//            $notificationSearch = getNotifications($query, $status, $type, $limit, $page);;
//            if ($page > $notificationSearch->lastPage()) {
//                $page = 1;
////                with('user_receiver')->
//                $notificationSearch = notification::with(['event', 'create_by', 'userJoin.user'])->paginate($limit, ['*'], 'page', $page);
//            }
//            return response()->json(handleData($status, $notificationSearch), Response::HTTP_OK);
//        } catch (\Exception $e) {
//            return response()->json([
//                'message' => $e->getMessage(),
//                'status' => 'error',
//                'statusCode' => Response::HTTP_NOT_FOUND
//            ], Response::HTTP_NOT_FOUND);
//        }
//    }

    /**
     * @OA\Get(
     *     path="/api/notification/getNotificationDel/{id}",
     *     summary="Hiển thị thông báo đã xóa",
     *     tags={"notification"},
     *     description="
     * - Hiển thị thông tin về thông báo đã xóa mềm.
     * - Vai trò: Quản trị viên và Nhân viên.
     *     ",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         schema={"type"="integer"},
     *         description="ID of the deleted notification",
     *         required=true
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Retrieve deleted notification successful"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Nhắc nhở email"),
     *                 @OA\Property(property="content", type="string", example="<h1 style='color:red;'>Test message</h1>"),
     *                 @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-25 01:42:27"),
     *                 @OA\Property(property="sent_at", type="string", format="date-time", example="2023-11-25 01:50:33"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
     *                 ),
     *                @OA\Property(property="totalDocs", type="integer", example=16),
     *             @OA\Property(property="limit", type="integer", example=10),
     *             @OA\Property(property="totalPages", type="integer", example=2),
     *             @OA\Property(property="page", type="integer", example=2),
     *             @OA\Property(property="pagingCounter", type="integer", example=2),
     *             @OA\Property(property="hasPrevPage", type="boolean", example=true),
     *             @OA\Property(property="hasNextPage", type="boolean", example=false)
     *             )
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="Record not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Record not found"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     * @OA\Response(
     *         response=500,
     *         description="System error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="System error"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function showNotificationDel($id)
    {
        try {
            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người Get không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $notification = notification::onlyTrashed()->where('id', $id)->get();
            return response()->json([
                'metadata' => $notification,
                'message' => 'Lấy thông báo đã xóa thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/notification/restore/{id}",
     *     summary="Khôi phục thông báo đã xóa",
     *     tags={"notification"},
     *     description="
     * - Khôi phục thông báo đã xóa trước đó.
     * - Vai trò: Quản trị viên và Nhân viên.
     *     ",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         schema={"type"="integer"},
     *         description="ID of the deleted notification to be restored",
     *         required=true
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Successfully restored deleted notification"),
     *             @OA\Property(property="statusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Notification not found"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="System error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="System error"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function restoreNotificationDel($id)
    {
        try {
            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người Get không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $notification = notification::onlyTrashed()->where('id', $id)->restore();
            return response()->json([
                'message' => 'Khôi phục thông báo đã xóa thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notification/settings/{id}",
     *     summary="Lấy ra các thông báo đang được cài đặt theo id người dùng",
     *     tags={"notification"},
     *     description="
     *      - endpoint này trả về các thông báo đang theo id người dùng.
     *      - Trả về thông tin các thông báo người dùng đang cài đặt.
     *      - Vai trò: Quản lí và nhân viên
     *      - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - event=<id sự kiện muốn tìm>
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     *     - type=<all|sent|scheduled|deleted> sẽ là param để lấy ra các list thông báo đang được setting <mặc định là all>
     *     - all là lấy ra toàn bộ thông báo đã được setting kể cả đã xóa mềm
     *     - sent là lấy ra các thông báo đã được gửi
     *     - scheduled là lấy ra các thông báo đang được cài đặt nhưng chưa được gửi
     *     - deleted là lấy ra các thông báo đã xóa mềm
     *     ",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved all records"),
     *         @OA\Property(
     *                 property="metadata",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Nhắc nhở email"),
     *                     @OA\Property(property="content", type="string", example="<h1 style='color:red;'>Test message</h1>"),
     *                     @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-25 01:42:27"),
     *                     @OA\Property(property="sent_at", type="string", format="date-time", example="2023-11-25 01:50:33"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
     *                 )
     *             ),
     *                 @OA\Property(property="totalDocs", type="integer", example=16),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=2),
     *                 @OA\Property(property="page", type="integer", example=2),
     *                 @OA\Property(property="pagingCounter", type="integer", example=2),
     *                 @OA\Property(property="hasPrevPage", type="boolean", example=true),
     *                 @OA\Property(property="hasNextPage", type="boolean", example=false)
     *             )
     *         )
     *     ),
     * @OA\Response(
     *     response=403,
     *     description="Record not found",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="status", type="string", example="error"),
     *         @OA\Property(property="message", type="string", example="Record not found"),
     *         @OA\Property(property="statusCode", type="integer", example=404)
     *     )
     * ),
     * @OA\Response(
     *         response=501,
     *         description="System error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="System error"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function getSettingsNotification($id,Request $request)
    {
        try {
            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người Get không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
            $type = $request->query('type', 'all');
            $query = notification::with(['event', 'create_by', 'userJoin.user']);
            $sender = $request->query('sender');
            if (!empty($sender)) {
                $id = $sender;
            }
            $query->where('user_id', $id);
//            dd($query->toSql());
            // Tìm kiếm theo title (tiêu đề thông báo)
            $title = $request->query('title');
            if (!empty($title)) {
                $query->where('title', 'like', '%' . $title . '%');
            }

            $eventId = $request->query('event');
            if (!empty($eventId)) {
                $query->where('event_id',$eventId );
            }

            // Tìm kiếm theo from_date và to_date
            $fromDate = $request->query('from_date');
            $toDate = $request->query('to_date', now()->toDateTimeString());

            if ($fromDate) {
                $query->where('sent_at', '>=', $fromDate);

                if ($toDate) {
                    $query->where('sent_at', '<=', $toDate);
                }
            }
            $notification = getNotifications($query, $status, $type, $limit, $page);
            if ($page > $notification->lastPage()) {
                $page = 1;
//                with('user_receiver')->
                $notification = getNotifications($query, $status, $type, $limit, $page);
            }
            return response()->json(handleData($status, $notification), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notification/eventSettings/{id}",
     *     summary="Lấy ra các thông báo đang được cài đặt theo id sự kiện",
     *     tags={"notification"},
     *     description="
     *      - endpoint này trả về các thông báo đang theo id sự kiện.
     *      - Trả về thông tin các thông báo người dùng đang cài đặt.
     *      - Vai trò: Quản lí và nhân viên
     *      - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - event=<id sự kiện muốn tìm>
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     *     - type=<all|sent|scheduled|deleted> sẽ là param để lấy ra các list thông báo đang được setting <mặc định là all>
     *     - all là lấy ra toàn bộ thông báo đã được setting kể cả đã xóa mềm
     *     - sent là lấy ra các thông báo đã được gửi
     *     - scheduled là lấy ra các thông báo đang được cài đặt nhưng chưa được gửi
     *     - deleted là lấy ra các thông báo đã xóa mềm
     *     ",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved all records"),
     *         @OA\Property(
     *                 property="metadata",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Nhắc nhở email"),
     *                     @OA\Property(property="content", type="string", example="<h1 style='color:red;'>Test message</h1>"),
     *                     @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-25 01:42:27"),
     *                     @OA\Property(property="sent_at", type="string", format="date-time", example="2023-11-25 01:50:33"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
     *                 )
     *             ),
     *                 @OA\Property(property="totalDocs", type="integer", example=16),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=2),
     *                 @OA\Property(property="page", type="integer", example=2),
     *                 @OA\Property(property="pagingCounter", type="integer", example=2),
     *                 @OA\Property(property="hasPrevPage", type="boolean", example=true),
     *                 @OA\Property(property="hasNextPage", type="boolean", example=false)
     *             )
     *         )
     *     )
     * )
     */
    public function getSettingsEventNotification($id,Request $request)
    {
        try {
            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người Get không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
            $type = $request->query('type', 'all');
            $query = notification::with(['event', 'create_by', 'userJoin.user']);
            $sender = $request->query('sender');
            if (!empty($sender)) {
                $id = $sender;
            }
            $query->where('event_id', $id);
//            dd($query->toSql());
            // Tìm kiếm theo title (tiêu đề thông báo)
            $title = $request->query('title');
            if (!empty($title)) {
                $query->where('title', 'like', '%' . $title . '%');
            }

            $eventId = $request->query('event');
            if (!empty($eventId)) {
                $query->where('event_id',$eventId );
            }

            // Tìm kiếm theo from_date và to_date
            $fromDate = $request->query('from_date');
            $toDate = $request->query('to_date', now()->toDateTimeString());

            if ($fromDate) {
                $query->where('sent_at', '>=', $fromDate);

                if ($toDate) {
                    $query->where('sent_at', '<=', $toDate);
                }
            }
            $notification = getNotifications($query, $status, $type, $limit, $page);
            if ($page > $notification->lastPage()) {
                $page = 1;
//                with('user_receiver')->
                $notification = getNotifications($query, $status, $type, $limit, $page);
            }
            return response()->json(handleData($status, $notification), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function test()
    {
//        $currentDateTime = Carbon::now();
//        $fiveHoursAgo = $currentDateTime->subHours(5)->toDateTimeString();
//        $events = event::where('start_time', '>', $fiveHoursAgo)
//            ->with(['attendances.user', 'user','user.receivedNotifications'])
//            ->whereDate('start_time', '=', $currentDateTime->toDateString())
//            ->where('status', 1)
//            ->get();
        $currentDateTime = Carbon::now()->toDateTimeString();
        $emails = notification::where('time_send', '<=', $currentDateTime)
            ->with(['event' => function ($query) {
                $query->with('attendances.user');
            }])
            ->whereNull('sent_at')
            ->get();

//               if ($emails->count() > 0) {
//                   $notificationsToUpdate = [];
//                   foreach ($emails as $email) {
//                       $data = [
//                           'title' => $email->title,
//                           'message' => $email->content,
//                       ];
//                       if($email->event->attendances->count() > 0){
//                           foreach ($email->event->attendances as $userSend) {
//                               dd($userSend->user->email);
//                           }
//                           $notificationsToUpdate[] = $email->id;
//                       }
//                   }
//                   notification::whereIn('id', $notificationsToUpdate)->update(['sent_at' => now()]);
//               }
        return response()->json([
            'metadata' => $emails,
            'message' => 'Tạo thông báo thành công',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
//        $currentDateTime = \Illuminate\Support\Carbon::now();
//        $dateCr = $currentDateTime->toDateTimeString();
//        $fiveHoursAhead = $currentDateTime->addHours(12)->toDateTimeString();
//        $events = event::where('start_time', '>=', $dateCr)
//            ->with(['attendances.user', 'user', 'notifications' => function ($query) use ($fiveHoursAhead) {
//                $query->where('time_send', '<', $fiveHoursAhead);
//            }])
//            ->where('start_time', '<', $fiveHoursAhead)
//            ->where('status', 2)
//            ->where('notification_sent', false)
//            ->get();
//
//        $notificationsToUpdateEvent = [];
//        foreach ($events as $item) {
//            if (!empty($item->attendances)) {
//                foreach ($item->attendances as $userSend) {
//                    $data = [
//                        'title' => "EMAIL NHẮC NHỞ SỰ KIỆN " . $item->name,
//                        'message' => $item->notifications->last()->content,
//                    ];
//                    dd($data);
//                    $notificationsToUpdateEvent[] = $item->id;
//                }
//            }
//        }
    }

    /**
     * @OA\Post(
     *     path="/api/notification",
     *     tags={"notification"},
     *     summary="Tạo mới 1 thông báo để chuẩn bị gửi",
     *     description="
     *      - Endpoint này cho phép người tạo mới thông báo cho sinh viên.
     *      - Trả về thông tin các thông báo đã tồn tại
     *      - Role được sử dụng là cả hai role nhân viên ,quản lí
     *      - title là tiêu đề của email muốn gửi
     *      - content là nội dung muốn gửi
     *      - time_send là thời gian gửi
     *      - event_id là id của sự kiện cần gửi thông báo
     *     - status<2|1|0> là trạng thái gửi thông báo của sự kiện đó <2 là chuẩn bị diễn ra, 1 là đang diễn ra, 0 là đã diễn ra>
     *     - Muốn set 1 thông báo sự kiện A được thông báo trước khi sự kiện A diễn ra 1 ngày thì status = 2 và (time_send < start_time)",
     *     operationId="storeNotification",
     * @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="content", type="string", example="Notification content"),
     *              @OA\Property(property="title", type="string", example="title content"),
     *             @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-28T17:02:29"),
     *             @OA\Property(property="event_id", type="integer", example=1),
     *         )
     *     ),
     * @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *            @OA\Property(
     *                 property="metadata",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Nhắc nhở email"),
     *                     @OA\Property(property="content", type="string", example="<h1 style='color:red;'>Test message</h1>"),
     *                     @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-25 01:42:27"),
     *                     @OA\Property(property="sent_at", type="string", format="date-time", example="2023-11-25 01:50:33"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
     *             )),
     *             @OA\Property(property="message", type="string", example="Cài đặt gửi email thành công"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200)
     *         )
     *     ),
     * @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="object", example={
     *                 "title": {"Tiêu đề không được để trống"},
     *                 "content": {"Nội đung không được để trống"},
     *                 "create_by": {"ID người tạo không được để trống"},
     *                 "time_send": {"Thời gian không được để trống"},
     *                 "status": {"Người nhận không được để trống"}
     *             }),
     *             @OA\Property(property="statusCode", type="integer", example=400)
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Người dùng không tồn tại"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     * @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi máy chủ nội bộ"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'content' => 'required',
//                'status' => ['required', Rule::in([0, 1, 2])],
//                'receiver_id' => 'required|exists:users,id',
                'event_id' => 'required|exists:events,id',
                'time_send' => 'unique:notifications,time_send'
            ], [
                'title.required' => 'Tiêu để không được để trống',
                'content.required' => 'Nội dung không được để trống',
                'status.required' => 'Trạng thái thông báo không để trống',
                'status.in' => 'Trạng thái chỉ được nhập 2 là chuẩn bị diễn ra, 1 là đang diễn ra, 0 là đã diễn ra',
                'time_send.unique' => 'Thời gian gửi đã tồn tại',
                'time_send.after' => 'Thời gian gửi phải sau thời gian hiện tại',
//                'receiver_id.required' => 'ID của người dùng không được để trống',
//                'receiver_id.exists' => 'ID của người dùng không tồn tại',
                'event_id.required' => 'Sự kiện không để trống',
                'event_id.exists' => 'Sự kiện không tồn tại',
                'create_by.required' => 'ID của người tạo không được để trống',
                'create_by.exists' => 'ID của người tạo không tồn tại'
            ]);

            if ($validator->fails()) {
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $user = Auth::user();

            if ($user->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người tạo không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            notification::create([
                'title' => $request->title,
                'content' => $request->input('content'),
                'time_send' => $request->time_send,
//                'status' => $request->status,
//                'receiver_id' => $request->receiver_id,
                'event_id' => $request->event_id,
                'user_id' => $user->id
            ]);
//            with('user_receiver')->
            $notification = notification::with(['event', 'create_by', 'userJoin.user'])->get();
            return response()->json([
                'metadata' => $notification,
                'message' => 'Tạo thông báo thành công',
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
     * @OA\Post(
     *     path="/api/notification/send",
     *     tags={"notification"},
     *     summary="Send an email",
     *     description="
     *      - Gửi email với dữ liệu được cung cấp
     *      - Trả lại message thông báo gửi thành công
     *      - Role được sử dụng là Nhân viên và Quản lí",
     *     operationId="sendEmail",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Email Title"),
     *             @OA\Property(property="message", type="string", example="Email Content"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="metadata", type="object", example={"title": "Email Title", "message": "Email Content"}),
     *             @OA\Property(property="message", type="string", example="Gửi Email example@gmail.com thành công"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=1)
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
    public function create(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'message' => 'required',
                'email' => 'required|email'
            ], [
                'title.required' => 'Id sự kiện không để trống.',
                'message.required' => 'Email không để trống.',
                'create_by.required' => 'Id người tạo không để trống.',
                'create_by.exists' => 'Id người tạo không tồn tại.',
                'email.email' => 'Email không hợp lệ',
                'email.required' => 'Không để trống email',
            ]);


            if ($validator->fails()) {
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $data = [
                'title' => $request->title,
                'message' => $request->message,
            ];
            Mail::to($request->email)->send(new EmailApi($data));

            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người tạo không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            return response()->json([
                'metadata' => $data,
                'message' => 'Gửi ' . $request->email . ' thành công',
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

    /**
     * @OA\Get(
     *     path="/api/notification/show/{id}",
     *     tags={"notification"},
     *     summary="Lấy ra 1 ghi trong notification",
     *     description="
     *      -Lấy dữ liệu thông báo theo ID
     *      - Data trả về là thông tin của thông báo và thông tin người được gửi
     *      - Role thực hiện là Quản lí và nhân viên",
     *     operationId="getNotificationById",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của thông báo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="metadata", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Nhắc nhở email"),
     *                 @OA\Property(property="content", type="string", example="<h1 style='color:red;'>Test message</h1>"),
     *                 @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-25 01:42:27"),
     *                 @OA\Property(property="sent_at", type="string", format="date-time", example="2023-11-25 01:50:33"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
     *             ),
     *             @OA\Property(property="message", type="string", example="Get One Record Successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200)
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

    public function show($id)
    {
        try {
//            with('user_receiver')->
            $notification = notification::with(['event', 'create_by', 'userJoin.user'])->find($id);
            return response()->json([
                'metadata' => $notification,
                'message' => 'Lấy 1 bản ghi thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response([
                "status" => "error",
                "message" => "Record not exists",
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }


    /**
     * @OA\Patch(
     *     path="/api/notification/{id}",
     *     tags={"notification"},
     *     summary="Cập nhật thông tin thông báo",
     *     description="
     *     - Cập nhật thông báo theo ID
     *     - Data trả về là dữ liệu của các thông báo và dữ liệu của user đã được cài đặt
     *     - id ở đây là id của thông báo
     *     - Role được cập nhật là Quản lí và nhân viên
     *     - event_id là id của sự kiện cần gửi thông báo
     *     - status<2|1|0> là trạng thái gửi thông báo của sự kiện đó <2 là chuẩn bị diễn ra, 1 là đang diễn ra, 0 là đã diễn ra>
     *     - Muốn set 1 thông báo sự kiện A được thông báo trước khi sự kiện A diễn ra 1 ngày thì status = 2 và time_send < start_time",
     *     operationId="updateNotificationById",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID thông báo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="content", type="string", example="Notification content"),
     *              @OA\Property(property="title", type="string", example="title content"),
     *             @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-28T17:02:29"),
     *             @OA\Property(property="event_id", type="integer", example=1),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="metadata", type="object", example={
     *                "id": 1,
    "title": "Title Cập nhật",
    "content": "<h1 style='color:red;'>Test message</h1>",
    "time_send": "2023-11-25 01:42:27",
    "sent_at": "2023-11-25 01:50:33",
    "receiver_id": 1,
    "created_at": null,
    "updated_at": "2023-11-24T18:50:33.000000Z"
     *             }),
     *             @OA\Property(property="message", type="string", example="Update One Record Successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Record not exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Record not exists"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal server error"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $notification = notification::with('event')->findOrFail($id);

//            'receiver_id' => 'exists:users,id',
            $validator = Validator::make($request->all(), [
                'event_id' => 'exists:events,id',
//                'status' => [Rule::in([1, 2, 3])],
                'time_send' => 'unique:notifications,time_send'
            ], [
                'title.required' => 'Tiêu để không được để trống',
                'content.required' => 'Nội dung không được để trống',
                'status.required' => 'Trạng thái không được để trống',
                'status.in' => 'Trạng thái phải là 3 là sắp diễn ra, 2 là đang diễn ra, 1 là đã diễn ra',
                'receiver_id.required' => 'ID của người dùng không được để trống',
                'receiver_id.exists' => 'ID của người dùng không tồn tại',
                'time_send.unique' => 'Thời gian gửi đã tồn tại',
                'time_send.after' => 'Thời gian gửi phải sau thời gian hiện tại',
                'event_id.exists' => 'Sự kiện không tồn tại',
                'create_by.required' => 'ID của người tạo không được để trống',
                'create_by.exists' => 'ID của người tạo không tồn tại'
            ]);

            if ($validator->fails()) {
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }


            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người thực hiện không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if ($notification->sent_at != null) {
                return response([
                    "status" => "error",
                    "message" => "Thông báo đã được gửi không thể cập nhật.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
//            ,'receiver_id'
            $data = $request->only(['title', 'content', 'time_send', 'event_id']);
            $data['user_id'] = Auth::user()->id;
            $data['updated_at'] = Carbon::now();
            $notification->update($data);
//            with('user_receiver')->
            $notification = notification::with(['event', 'create_by', 'userJoin.user'])->get();
            return response()->json([
                'metadata' => $notification,
                'message' => 'Cập nhật thông báo thành công',
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
     *     path="/api/notification/{id}",
     *     tags={"notification"},
     *     summary="Xóa thông báo",
     *     description="Xóa 1 thông báo đang tham gia sự kiện
     *     - softDel = true|false là xóa mềm (trường deleted_at sẽ được cập nhật) và ngược lại <true là xóa mềm, false là xóa vĩnh viễn><mặc định là true>",
     *     operationId="deleteNotificationById",
     * @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của thông báo",
     *         @OA\Schema(type="integer")
     *     ),
     * @OA\Parameter(
     *         name="id_user",
     *         in="path",
     *         required=true,
     *         description="ID của người thực hiện xóa",
     *         @OA\Schema(type="integer")
     *     ),
     * @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Xóa thành công thông báo"),
     *             @OA\Property(property="metadata", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Nhắc nhở email"),
     *                     @OA\Property(property="content", type="string", example="<h1 style='color:red;'>Test message</h1>"),
     *                     @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-25 01:42:27"),
     *                     @OA\Property(property="sent_at", type="string", format="date-time", example="2023-11-25 01:50:33"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
     *                 )
     *             ),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     * @OA\Response(
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

    public function destroy($id, Request $request)
    {
        try {
            $notification = notification::withTrashed()->find($id);
            $softDel = $request->query('softDel', true);
            if (!$notification) {
                return response()->json([
                    'message' => 'Bản ghi không tồn tại',
                    'status' => 'error',
                    'statusCode' => Response::HTTP_NOT_FOUND
                ], Response::HTTP_NOT_FOUND);
            }
            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người xóa không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
//            $notification->delete();
//            $notification->forceDelete();
            if ($softDel === "true" || $softDel === true) {
                $notification->delete();
            } else if ($softDel === "false") {
                $notification->forceDelete();
            } else {
                return response([
                    "status" => "error",
                    "message" => "Param truyền vào không hợp lệ",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

//            with('user_receiver')->
            $notification = notification::with(['event', 'create_by', 'userJoin.user'])->get();
            return response()->json([
                'metadata' => $notification,
                'message' => 'Xóa bản ghi thành công',
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

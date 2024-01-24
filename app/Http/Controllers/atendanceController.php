<?php

namespace App\Http\Controllers;

use App\Models\atendance;
use App\Models\event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\User;

class atendanceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/attendances/join/{id_event}",
     *     summary="Lấy tất cả các bản ghi theo id sự kiện",
     *     tags={"Attendances"},
     *      description="
     *      - id_event là id sự kiện
     *      - Endpoint trả về thôn tìn người dùng tham gia sự kiện.
     *      - Trả về thông tin của người dùng đã tham gia sự kiện.
     *      - Role được sử dụng là role nhân viên ,quản lí
     *     - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     *     ",
     *      @OA\Response(
     *         response=200,
     *         description="Successful response with feedback data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved all feedback"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="docs", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="1"),
     *                         @OA\Property(property="user_id", type="integer", example="2"),
     *                         @OA\Property(property="event_id", type="integer", example="2"),
     *                         @OA\Property(
     *                             property="user",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example="1"),
     *                             @OA\Property(property="name", type="string", example="Emil Macejkovic"),
     *                             @OA\Property(property="email", type="string", example="dwalker@example.com"),
     *                             @OA\Property(property="phone", type="string", example="(838) 979-6792"),
     *                             @OA\Property(property="role", type="integer", example="2"),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-24T04:26:59.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-24T04:26:59.000000Z"),
     *                             @OA\Property(property="avatar", type="string", example="https://www.elle.vn/wp-content/uploads/2017/07/25/hinh-anh-dep-1.jpg")
     *                         )
     *                     )
     *                 ),
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
     *     @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi hệ thống"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function index($id_event, Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
            $user = Auth::user();
            if ($user == null || $user->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người dùng không hợp lệ",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $query = atendance::where('event_id', $id_event)->with('user');
            $atendance = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            if (!$status && $page > $atendance->lastPage()) {
                $page = 1;
                $atendance = atendance::where('event_id', $id_event)->with('user')->paginate($limit, ['*'], 'page', $page);
            }
            $data = ($status) ? [
                'metadata' => $atendance,
                'message' => 'Get All Records Successfully',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ] : [
                'metadata' => [
                    'docs' => $atendance->items(),
                    'totalDocs' => $atendance->total(),
                    'limit' => $atendance->perPage(),
                    'totalPages' => $atendance->lastPage(),
                    'page' => $atendance->currentPage(),
                    'pagingCounter' => $atendance->currentPage(), // Bạn có thể sử dụng currentPage hoặc số khác nếu cần
                    'hasPrevPage' => $atendance->previousPageUrl() != null,
                    'hasNextPage' => $atendance->nextPageUrl() != null
                    //                    'prevPage' => $atendance->previousPageUrl(),
                    //                    'nextPage' =>$atendance->nextPageUrl(),
                ],
                'message' => 'Lấy thành công tất cả các bản ghi',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ];
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
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
     *     path="/api/attendances",
     *     tags={"Attendances"},
     *     summary="Thêm người dùng vào sự kiện có sẵn",
     *      description="
     *      - Endpoint trả về danh sách dữ liệu dữ liệu người dùng của sự kiện đó
     *      - Role được sử dụng là role nhân viên ,quản lí ,sinh viên
     *     - event_id là id sự kiện tham gia",
     *     operationId="storeAttendance",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event_id", type="integer", example="1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Create Record Successfully"),
     *             @OA\Property(property="statusCode", type="int", example=200),
     *     @OA\Property(
     *                 property="metadata",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="event_id", type="integer", example=2),
     *     *                    @OA\Property(property="user", type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *     @OA\Property(property="phone", type="string", example="123456789"),
     *     @OA\Property(property="role", type="integer", example=1),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-23 11:20:22")
     * ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-23 11:20:22")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Sai validate",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="object", example={"user_id": {"ID của người dùng không được để trống"}}),
     *             @OA\Property(property="statusCode", type="int", example=500),

     *         )
     *     ),
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'event_id' => 'required|exists:events,id',
            ], [
                'event_id.required' => 'Id sự kiện không được để trống',
                //                'user_id.required' => 'Id người dùng không được để trống.',
                //                'user_id.exists' => 'Người dùng không tồn tại.',
                'event_id.exists' => 'Sự kiện không tồn tại.'
            ]);

            if ($validator->fails()) {
                //                return response(['status' => 'error', 'message' => $validator->errors()], 500);
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $atendance = atendance::where('user_id', Auth::user()->id)->where('event_id', $request->event_id)->first();
            if ($atendance) {
                return response([
                    "status" => "error",
                    "message" => "Người dùng đã tồn tại trong sự kiện này",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $event = event::find($request->event_id);
            if ($event->status == 0) {
                return response([
                    "status" => "error",
                    "message" => "Sự kiện này đã kết thúc không thể tham gia",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            atendance::create([
                'user_id' => Auth::user()->id,
                'event_id' => $request->event_id
            ]);
            $usersInEvent = atendance::where('event_id', $request->event_id)
                ->with('user')
                ->get();
            return response()->json([
                'metadata' => $usersInEvent,
                'message' => 'Tạo mới bản ghi thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
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
     * @OA\Post(
     *     path="/api/attendances/add",
     *     tags={"Attendances"},
     *     summary="Thêm sinh viên vào sự kiện bằng email khi sinh viên đã đăng nhập ",
     *     description="
     *          - Endpoint này cho phép quản lí và nhân viên thêm sinh viên vào sự kiện bằng email
     *          - Trả về data của sinh viên sau khi được thêm mới bằng email
     *          - event_id là id sự kiện tham gia
     *          - email là email của người muốn thêm (Lưu ý : phải có trong hệ thống)
     *          ",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event_id", type="integer", example=1, description="Id của sự kiện."),
     *             @OA\Property(property="email", type="string", example="john.doe@example.com", description="Email của sinh viên.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="metadata", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="event_id", type="integer", example=1),
     *                    @OA\Property(property="user", type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *     @OA\Property(property="phone", type="string", example="123456789"),
     *     @OA\Property(property="role", type="integer", example=1),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-23 11:20:22")
     * ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-05 12:34:56"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-05 12:34:56")
     *             ),
     *             @OA\Property(property="message", type="string", example="Tạo mới bản ghi thành công"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="object", example={"event_id": {"Id sự kiện không để trống."}, "email": {"Email không để trống."}}),
     *             @OA\Property(property="statusCode", type="integer", example=400)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Id sự kiện không tồn tại."),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi hệ thống"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */

    public function addEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'event_id' => 'required|exists:events,id',
                'email' => 'required|exists:users,email'
            ], [
                'event_id.required' => 'Id sự kiện không để trống.',
                'event_id.exists' => 'Id sự kiện không tồn tại.',
                'email.required' => 'Email không để trống.',
                'email.exists' => 'Email không tồn tại.',
                'create_by.required' => 'Id người tạo không để trống.',
                'create_by.exists' => 'Id người tạo không tồn tại.'
            ]);

            if ($validator->fails()) {
                //                return response(['status' => 'error', 'message' => $validator->errors()], 500);
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }



            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người tạo không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = User::select('id')->where('email', $request->email)->first()->id;

            $atendance = atendance::where('user_id', $id)->where('event_id', $request->event_id)->first();
            if ($atendance) {
                return response([
                    "status" => "error",
                    "message" => "Người dùng đã tồn tại trong sự kiện này",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $event = event::find($request->event_id);
            if ($event->status == 0) {
                return response([
                    "status" => "error",
                    "message" => "Sự kiện này đã kết thúc không thể tham gia",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            atendance::create([
                "user_id" => (int)$id,
                "event_id" => $request->event_id
            ]);
            $usersInEvent = atendance::where('event_id', $request->event_id)
                ->with('user') // Đảm bảo Eloquent trả về thông tin người dùng liên quan
                ->get();
            return response()->json([
                'metadata' => $usersInEvent,
                'message' => 'Tạo mới bản ghi thành công ',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
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
     *     path="/api/attendances/{id}",
     *     summary="Lấy ra thông tin người tham gia sự kiện",
     *     tags={"Attendances"},
     *     description="
     *          - Endpoint này lấy ra 1 bản ghi của sự kiện
     *          - id là id của atendances
     *          ",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của attendance",
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
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="event_id", type="integer", example=2),
     *     *                    @OA\Property(property="user", type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *     @OA\Property(property="phone", type="string", example="123456789"),
     *     @OA\Property(property="role", type="integer", example=1),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-23 11:20:22")
     * ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-23 11:20:22")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             type="object",
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

            $usersInEvent = atendance::with('user')->findOrFail($id);
            //            dd($usersInEvent);
            return response()->json([
                'metadata' => $usersInEvent,
                'message' => 'Lấy 1 bản ghi thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response([
                "status" => "error",
                "message" => "Bản ghi không tồn tại",
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }
 
       /**
     * @OA\Patch(
     *     path="/api/attendances_rate",
     *     summary="Rate a event",
     *     tags={"Attendances"},
     *     description="
     *          - Endpoint này cho phép người tham gia sự kiện đánh giá về sự kiện
     *          - Trả về data của tất cả người dùng đánh giá
     *          - event_id là id sự kiện tham gia
     *          - rate là số lượng sao của người đánh giá
     *          ",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event_id", type="integer", example="1"),
     *             @OA\Property(property="rate", type="integer", example="2")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Update One Record Successfully"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="event_id", type="integer", example=2),
     *                 @OA\Property(property="rate", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-23 11:20:22")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại "),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi hệ thống"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function rate(Request $request)
    {
        //validate request
        $validator = Validator::make($request->all(), [
            'rate' => 'required|min:0|max:5',
            'event_id' => 'required|exists:events,id'
        ], [
            'rate.required' => 'Phai nhập số lượng star của người rate',
            'rate.min' => 'Giá trị bé nhất của rate là 0 sao',
            'rate.max' => 'Giá trị lớn nhất của rate là 5 sao',
            'event_id.required' => 'Không được bỏ trống id event',
            'event_id.exists' => 'event không tồn tại'
        ]);
        if ($validator->fails()) {
            //                return response(['status' => 'error', 'message' => $validator->errors()], 500);
            return response([
                "status" => "error",
                "message" => $validator->errors()->all(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        //check user is attending this event or not
        $atendance = atendance::where('user_id', auth()->user()->id)->where('event_id', $request->event_id)->first();
        if (!$atendance) {
            return response([
                "status" => "error",
                "message" => "Người dùng  chưa tham gia sự kiện này",
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $atendance->update(['rate' => $request->rate]);
        return response()->json([
            'metadata' => $atendance,
            'message' => 'Người dùng đánh giá sự kiện thành công',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Patch(
     *     path="/api/attendances/{atendance}",
     *     summary="Update an attendance record",
     *     tags={"Attendances"},
     *     description="
     *          - Endpoint này cho phép quản lí và nhân viên cập nhật thông tin người tham gia sự kiện
     *          - Trả về data của tất cả người dùng thuộc sự kiện đó
     *          - event_id là id sự kiện tham gia
     *          - email là email của người muốn thêm (Lưu ý : phải có trong hệ thống)
     *          - update_by là id của người cập nhật (Lưu ý : phải có trong hệ thống)
     *          ",
     *     @OA\Parameter(
     *         name="atendance",
     *         in="path",
     *         required=true,
     *         description="Mô hình dữ liệu attendance",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="2")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Update One Record Successfully"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="event_id", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-23 11:20:22")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại "),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             type="object",
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

            $validator = Validator::make($request->all(), [
                'event_id' => 'exists:events,id',
                'user_id' => 'exists:users,id'

            ], [
                'user_id.required' => 'Không để trống id người dùng',
                'user_id.exists' => 'Người Dùng không tồn tại',
                'event_id.required' => 'Không để trống id Sự kiện',
                'event_id.exists' => 'Sự kiện không tồn tại'
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

            $event = event::find($id);
            if ($event->status == 0) {
                return response([
                    "status" => "error",
                    "message" => "Sự kiện này đã kết thúc không thể cập nhật",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $atendance = atendance::findOrFail($id);

            $data = $request->only(['event_id', 'user_id']);
            $data['updated_at'] = Carbon::now();
            $atendance->update($data);

            $usersInEvent = atendance::where('event_id', $request->event_id)
                ->with('user') // Đảm bảo Eloquent trả về thông tin người dùng liên quan
                ->get();
            return response()->json([
                'metadata' => $usersInEvent,
                'message' => 'Cập nhật thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response([
                "status" => "error",
                "message" => "Bản ghi không tồn tại",
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/attendances/{id}",
     *     summary="Xóa 1 người dùng đã tham gia sự kiện",
     *     tags={"Attendances"},
     *     description="
     *          - Endpoint này sẽ xóa 1 sinh viên đang tham gia sự kiện đi
     *          - Role được sử dụng là role Quản lí
     *          - Xóa thành công sẽ trả lại data là các sinh viên còn lại sự kiện đó
     *          - id là id của bản ghi tham gia
     *          ",
     *     @OA\Parameter(
     *         name="atendance",
     *         in="path",
     *         required=true,
     *         description="Mô hình dữ liệu attendance",
     *         @OA\Schema(type="integer")
     *     ),
     *      @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Xóa bản ghi thành công"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="1"),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="event_id", type="integer", example=2),
     *                    @OA\Property(property="user", type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *     @OA\Property(property="phone", type="string", example="123456789"),
     *     @OA\Property(property="role", type="integer", example=1),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-23 11:20:22")
     * ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-23 11:20:22")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi hệ thống"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $atendance = atendance::find($id);
            if (!$atendance) {
                return response()->json([
                    'message' => 'Bản ghi không tồn tại',
                    'status' => 'error',
                    'statusCode' => Response::HTTP_NOT_FOUND
                ], Response::HTTP_NOT_FOUND);
            }
            $idEvent = $atendance->event_id;


            $user = Auth::user();
            if ($user->role == 0 || $user->role == 1) {
                return response([
                    "status" => "error",
                    "message" => "Role người xóa không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $atendance->delete();

            $usersInEvent = atendance::where('event_id', $idEvent)
                ->with('user')
                ->get();

            return response()->json([
                'metadata' => $usersInEvent,
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

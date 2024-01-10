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
     *                     @OA\Property(property="status", type="integer", example=2),
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
     *     @OA\Response(
     *         response=404,
     *         description="Record not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Record not found"),
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

    public function index(Request $request)
    {
        try {
            if(Auth::user()->role == 0){
                return response([
                    "status" => "error",
                    "message" => "Role người Get không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
//            ::with('user_receiver')
            $query = notification::with('event');
            $notification = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            if ($page > $notification->lastPage()) {
                $page = 1;
//                with('user_receiver')->
                $notification = notification::with('event')->paginate($limit, ['*'], 'page', $page);
            }
            return response()->json(handleData($status,$notification), Response::HTTP_OK);
        }catch(\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function test(){
//        $currentDateTime = Carbon::now();
//        $fiveHoursAgo = $currentDateTime->subHours(5)->toDateTimeString();
//        $events = event::where('start_time', '>', $fiveHoursAgo)
//            ->with(['attendances.user', 'user','user.receivedNotifications'])
//            ->whereDate('start_time', '=', $currentDateTime->toDateString())
//            ->where('status', 1)
//            ->get();

        $currentDateTime = \Illuminate\Support\Carbon::now();
        $dateCr = $currentDateTime->toDateTimeString();
        $fiveHoursAhead = $currentDateTime->addHours(5)->toDateTimeString();
        $events = event::where('start_time', '>=', $dateCr)
            ->with(['attendances.user', 'user','notifications' => function($query){
                $query->where('status',2);
            }])
            ->where('start_time', '<', $fiveHoursAhead)
            ->where('status', 2)
            ->where('notification_sent', false)
            ->get();
//        dd($events[0]->notifications->last()->content);
        foreach ($events as $item) {
            if (!empty($item->attendances)) {
                foreach ($item->attendances as $userSend) {
                    $data = [
                        'title' => "EMAIL NHẮC NHỞ SỰ KIỆN " . $item->name,
                        'message' => $item->notifications->last()->content,
                    ];

                    dd($userSend->user->email);
                }
            }
        }

//        $currentDateTime = Carbon::now()->toDateTimeString();
//        $emails = notification::where('time_send', '<=', $currentDateTime)
//            ->with(['event' => function($query){
//                 $query->with('attendances.user');
//            }])
//            ->whereNull('sent_at')
//            ->get();
//        dd($emails);
//        tt người tham gia sự kiện
//        $emails[0]->event->attendances
//        return response()->json([
//            'metadata' => $emails[0]->event->attendances,
//            'message' => 'test',
//            'status' => 'success',
//            'statusCode' => Response::HTTP_OK
//        ], Response::HTTP_OK);
//        foreach ($emails as $item) {
////            dd($item);
////            dd($item->user->receivedNotifications->last()->content);
//            foreach($item->event->attendances as $userSend){
//                      $data = [
//                          'title' => $item->title,
//                          'message' =>$item->content,
//                      ];
////                $userSend->user->email
//                dd($userSend->user->email);
//            }
//
//        }

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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="content", type="string", example="Notification content"),
     *              @OA\Property(property="title", type="string", example="title content"),
     *             @OA\Property(property="time_send", type="string", format="date-time", example="2023-11-28T17:02:29"),
     *             @OA\Property(property="event_id", type="integer", example=1),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
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
     *                     @OA\Property(property="status", type="integer", example=2),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
     *             )),
     *             @OA\Property(property="message", type="string", example="Cài đặt gửi email thành công"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
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
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Người dùng không tồn tại"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
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
                'status' => ['required',  Rule::in([0, 1, 2])],
//                'receiver_id' => 'required|exists:users,id',
                'event_id' => 'required|exists:events,id',
                'time_send' => ['unique:notifications,time_send', 'after:' . now(),function ($attribute, $value, $fail) use ($request) {
                    $status = $request->input('status');
                    $eventId = $request->input('event_id');
                    $event = Event::find($eventId);
                    if ($status == 2 && Carbon::parse($value)->greaterThanOrEqualTo($event->start_time)) {
                        $fail('Trạng thái gửi là chuẩn bị diễn ra thì thời gian phải trước khi thời gian sự kiện diễn ra');
                    } elseif ($status == 1 && (Carbon::parse($value)->lessThan($event->start_time) || Carbon::parse($value)->greaterThan($event->end_time))) {
                        $fail('Trạng thái gửi là đang diễn ra thì thời gian gửi phải trong khoảng thời gian diễn ra sự kiện');
                    } elseif ($status == 0 && Carbon::parse($value)->lessThan($event->end_time)) {
                        $fail('Trạng thái gửi là đã kết thúc thì thời gian gửi phải lớn hơn thời gian sự kiện kết thúc');
                    }
                }]
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

            if($validator->fails()){
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $user =  Auth::user();

            if($user->role == 0){
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
                'status' => $request->status,
//                'receiver_id' => $request->receiver_id,
                'event_id' => $request->event_id,
                'create_by' => Auth::user()->id
            ]);
//            with('user_receiver')->
            $notification = notification::with('event')->get();
            return response()->json([
                'metadata' => $notification,
                'message' => 'Tạo thông báo thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e){
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

            if(Auth::user()->role == 0){
                return response([
                    "status" => "error",
                    "message" => "Role người tạo không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            return response()->json([
                'metadata' => $data,
                'message' => 'Gửi '.$request->email.' thành công',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        }catch (\Exception $e){
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
     *                 @OA\Property(property="status", type="integer", example=1),
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
            $notification = notification::with('event')->find($id);
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
     *             @OA\Property(property="status", type="integer", example=1)
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
                'status' => [Rule::in([1, 2, 3])],
                'time_send' => ['unique:notifications,time_send', 'after:' . now(),function ($attribute, $value, $fail) use ($request,$notification) {
                    $status = $request->input('status',$notification->status);
                    $eventId = $request->input('event_id',$notification->event->id);
                    $eventUpdate = Event::find($eventId);
                    if ($status == 2 && Carbon::parse($value)->greaterThanOrEqualTo($eventUpdate->start_time)) {
                        $fail('Trạng thái gửi là chuẩn bị diễn ra thì thời gian phải trước khi thời gian sự kiện diễn ra');
                    } elseif ($status == 1 && (Carbon::parse($value)->lessThan($eventUpdate->start_time) || Carbon::parse($value)->greaterThan($eventUpdate->end_time))) {
                        $fail('Trạng thái gửi là đang diễn ra thì thời gian gửi phải trong khoảng thời gian diễn ra sự kiện');
                    } elseif ($status == 0 && Carbon::parse($value)->lessThan($eventUpdate->end_time)) {
                        $fail('Trạng thái gửi là đã kết thúc thì thời gian gửi phải lớn hơn thời gian sự kiện kết thúc');
                    }
                }]
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


            if(Auth::user()->role == 0){
                return response([
                    "status" => "error",
                    "message" => "Role người thực hiện không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if($notification->sent_at != null){
                return response([
                    "status" => "error",
                    "message" => "Thông báo đã được gửi không thể cập nhật.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
//            ,'receiver_id'
            $data = $request->only(['title', 'content', 'time_send','event_id','status']);
            $data['create_by'] = Auth::user()->id;
            $data['updated_at'] = Carbon::now();
            $notification->update($data);
//            with('user_receiver')->
            $notification = notification::with('event')->get();
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
     *     description="Xóa 1 thông báo đang tham gia sự kiện",
     *     operationId="deleteNotificationById",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của thông báo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id_user",
     *         in="path",
     *         required=true,
     *         description="ID của người thực hiện xóa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
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
     *                     @OA\Property(property="status", type="integer", example=2),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 11:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 11:30:00")
     *                 )
     *             ),
     *             @OA\Property(property="statusCode", type="integer", example=200),
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

    public function destroy($id)
    {
        try {
            $notification = notification::find($id);
            if (!$notification) {
                return response()->json([
                    'message' => 'Bản ghi không tồn tại',
                    'status' => 'error',
                    'statusCode' => Response::HTTP_NOT_FOUND
                ], Response::HTTP_NOT_FOUND);
            }
            if(Auth::user()->role == 0){
                return response([
                    "status" => "error",
                    "message" => "Role người xóa không hợp lệ.Vui lòng thử lại!!",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $notification->delete();
//            with('user_receiver')->
            $notification = notification::with('event')->get();
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

<?php

namespace App\Http\Controllers;

use App\Models\event;
use App\Models\events_keywords;
use App\Models\keywords;
use App\Models\User;
use App\Models\atendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Http\Resources\EventResources;
use App\Models\feedback;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class eventController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/event",
     *     summary="Lấy tất cả các sự kiện",
     *     tags={"Event"},
     *      description="
     *      - Endpoint trả về thông tin của tất cả các sự kiện
     *      - Role được sử dụng là role của tất cả
     *      - Trả về thông tin của tất cả các sự kiện đã diễn ra
     *     - StatusJoin là xác đinh người dùng đang login vào đã tham gia những sự kiện nào
     *     - Nếu là 1 thì là trạng thái người dùng đó đã tham gia
     *     - 0 là trạng thái người dùng đó không tham gia
     *     - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     *     - search=<nội dung muốn tìm kiếm >
     *     - sort=<latest|oldest> mặc định sẽ là latest sẽ là sắp xếp ngày đăng mới nhất(oldest là cũ nhất)
     *     - star là số lượng sao mà sự kiện có nhiều nhất
     * ",
     * @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Lấy dữ liệu thành công"),
     *              @OA\Property(property="statusCode", type="integer", example=200),
     *              @OA\Property(property="metadata",type="object",
     *              @OA\Property(property="docs", type="array",
     *                  @OA\Items(
     *                      type="object",
     *                       @OA\Property(property="name", type="string", example="Event Name"),
     *                       @OA\Property(property="location", type="string", example="Ha Noi"),
     *                       @OA\Property(property="contact", type="string", example="0986567467"),
     *                       @OA\Property(property="user_id", type="integer", example=2),
     *                       @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                       @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *                       @OA\Property(property="attendances_count", type="interger", example=3),
     *                       @OA\Property(property="status_join", type="interger", example=1),
     *                       @OA\Property(
     *                          property="user",
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example="1"),
     *                          @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                          @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                          @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                          @OA\Property(property="role", type="integer", example="1"),
     *                          @OA\Property(property="google_id", type="string", example="137518716745268"),
     *                          @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/ACg8ocL2nrwZ_mNIBGYaLd8tnzAJLMR0g_UXSVhY_BN67ZWA=s96-c"),
     *                          @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                          @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                      )
     *                    )
     *                  ),
     * @OA\Property(property="totalDocs", type="integer", example=16),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=2),
     *                 @OA\Property(property="page", type="integer", example=2),
     *                 @OA\Property(property="pagingCounter", type="integer", example=2),
     *                 @OA\Property(property="hasPrevPage", type="boolean", example=true),
     *                 @OA\Property(property="hasNextPage", type="boolean", example=false)
     *              )
     *
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bản ghi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy bản ghi"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     * @OA\Response(
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
    public function index(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
            $search = $request->query('search', '');
            $sort = $request->query('sort', 'latest');
            $star = $request->query('star',null);
            //return $page;

            $query = event::where('name', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhere('contact', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('start_time', 'like', "%{$search}%")
                ->orWhere('end_time', 'like', "%{$search}%")
                ->withCount('attendances')->with('user')->with('keywords')->with(['notifications',
                    'attendances' => function ($query) {
                        $query->with('user')->select('atendances.*')
                            ->selectSub(function ($subQuery) {
                                $subQuery->selectRaw('IF(COUNT(feedback.id) > 0, 1, 0) as status_feedback')
                                    ->from('feedback')
                                    ->whereColumn('feedback.event_id', 'atendances.event_id')
                                    ->whereColumn('feedback.user_id', '=', 'atendances.user_id');
                            }, 'status_feedback');
                    }
                ]);


            $query->leftJoin('atendances', function ($join) {
                $join->on('events.id', '=', 'atendances.event_id')
                    ->where('atendances.user_id', '=', Auth::user()->id);
            })
                ->select('events.*')
                ->selectSub(function ($query) {
                    $query->selectRaw('IF(COUNT(atendances.id) > 0, 1, 0) as status_join')
                        ->from('atendances')
                        ->whereColumn('atendances.event_id', 'events.id')
                        ->where('atendances.user_id', Auth::user()->id);
                }, 'status_join');
            $query->orderBy('id', ($sort) == 'oldest' ? 'asc' : 'desc');
//            $event = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            if ($search) {
                $event = ($status) ? $query->take($limit)->get() : $query->paginate($limit, ['*'], 'page', $page);
            } else {
                $event = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            }
            if (!$status && $page > $event->lastPage()) {
                $page = 1;
                $event = event::where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
//                ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('start_time', 'like', "%{$search}%")
                    ->orWhere('end_time', 'like', "%{$search}%")
                    ->withCount('attendances')->with('user')->with('keywords')->with(['notifications',
                        'attendances' => function ($query) {
                            $query->with('user')->select('atendances.*')
                                ->selectSub(function ($subQuery) {
                                    $subQuery->selectRaw('IF(COUNT(feedback.id) > 0, 1, 0) as status_feedback')
                                        ->from('feedback')
                                        ->whereColumn('feedback.event_id', 'atendances.event_id')
                                        ->whereColumn('feedback.user_id', '=', 'atendances.user_id');
                                }, 'status_feedback');
                        }
                    ])
                    ->leftJoin('attendances', function ($join) {
                        $join->on('events.id', '=', 'attendances.event_id')
                            ->where('attendances.user_id', '=', Auth::user()->id);
                    })
                    ->select('events.*')
                    ->selectSub(function ($query) {
                        $query->selectRaw('IF(COUNT(attendances.id) > 0, 1, 0) as status_join')
                            ->from('attendances')
                            ->whereColumn('attendances.event_id', 'events.id')
                            ->where('attendances.user_id', Auth::user()->id);
                    }, 'status_join')
                    ->paginate($limit, ['*'], 'page', $page);
            }

//            $event->map(function ($event) {
//                $imageUrl = asset("Upload/{$event->banner}");
//                $event->banner = $imageUrl; // Thay đổi giá trị trường `url` của mỗi đối tượng
//                return $event;
//            });
            return response()->json(handleData($status, $event), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => $e instanceof HttpException
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR
            ], $e instanceof HttpException
                ? $e->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/eventJoin",
     *     summary="Lấy tất cả các sự kiện đang tham gia",
     *     tags={"Event"},
     *      description="
     *      - Endpoint trả về thông tin của tất cả các sự kiện đang tham gia
     *      - Role được sử dụng là role của tất cả
     *      - Trả về thông tin của tất cả các sự kiện đã diễn ra
     *     - StatusJoin là xác đinh người dùng đang login vào đã tham gia những sự kiện nào
     *     - Nếu là 1 thì là trạng thái người dùng đó đã tham gia
     *     - 0 là trạng thái người dùng đó không tham gia
     *     - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     *     - search=<nội dung muốn tìm kiếm >
     *     - sort=<latest|oldest> mặc định sẽ là latest sẽ là sắp xếp ngày đăng mới nhất(oldest là cũ nhất)
     * ",
     * @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Lấy dữ liệu thành công"),
     *              @OA\Property(property="statusCode", type="integer", example=200),
     *              @OA\Property(property="metadata",type="object",
     *              @OA\Property(property="docs", type="array",
     *                  @OA\Items(
     *                      type="object",
     *                       @OA\Property(property="name", type="string", example="Event Name"),
     *                       @OA\Property(property="location", type="string", example="Ha Noi"),
     *                       @OA\Property(property="contact", type="string", example="0986567467"),
     *                       @OA\Property(property="user_id", type="integer", example=2),
     *                       @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                       @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *                       @OA\Property(property="attendances_count", type="interger", example=3),
     *                       @OA\Property(property="area", type="string", example="Hà Nội"),
     *                       @OA\Property(property="status_join", type="interger", example=1),
     *                       @OA\Property(
     *                          property="user",
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example="1"),
     *                          @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                          @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                          @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                          @OA\Property(property="role", type="integer", example="1"),
     *                          @OA\Property(property="google_id", type="string", example="137518716745268"),
     *                          @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/ACg8ocL2nrwZ_mNIBGYaLd8tnzAJLMR0g_UXSVhY_BN67ZWA=s96-c"),
     *                          @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                          @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                      )
     *                    )
     *                  ),
     * @OA\Property(property="totalDocs", type="integer", example=16),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=2),
     *                 @OA\Property(property="page", type="integer", example=2),
     *                 @OA\Property(property="pagingCounter", type="integer", example=2),
     *                 @OA\Property(property="hasPrevPage", type="boolean", example=true),
     *                 @OA\Property(property="hasNextPage", type="boolean", example=false)
     *              )
     *
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bản ghi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy bản ghi"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     * @OA\Response(
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
    public function eventJoin(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
            $search = $request->query('search', '');
            $sort = $request->query('sort', 'latest');
            //return $page;

            $query = event::where('name', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhere('contact', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
//                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('start_time', 'like', "%{$search}%")
                ->orWhere('end_time', 'like', "%{$search}%")
                ->withCount('attendances')->with('user')->with('keywords')->with([
                    'notifications',
                    'attendances' => function ($query) {
                        $query->with('user')->select('atendances.*')
                            ->selectSub(function ($subQuery) {
                                $subQuery->selectRaw('IF(COUNT(feedback.id) > 0, 1, 0) as status_feedback')
                                    ->from('feedback')
                                    ->whereColumn('feedback.event_id', 'atendances.event_id')
                                    ->whereColumn('feedback.user_id', '=', 'atendances.user_id');
                            }, 'status_feedback');
                    }
                ]);


            $query->leftJoin('atendances', function ($join) {
                $join->on('events.id', '=', 'atendances.event_id')
                    ->where('atendances.user_id', '=', Auth::user()->id);
            })
                ->select('events.*')
                ->selectSub(function ($query) {
                    $query->selectRaw('IF(COUNT(atendances.id) > 0, 1, 0) as status_join')
                        ->from('atendances')
                        ->whereColumn('atendances.event_id', 'events.id')
                        ->where('atendances.user_id', Auth::user()->id);
                }, 'status_join')
                ->selectSub(function ($query) {
                    $query->selectRaw('IF(COUNT(feedback.id) > 0, 1, 0) as status_feedBack_join')
                        ->from('feedback')
                        ->whereColumn('feedback.event_id', 'events.id')
                        ->where('feedback.user_id', Auth::user()->id);
                }, 'status_feedBack_join');
            $query->orderBy('id', ($sort) == 'oldest' ? 'asc' : 'desc');
            // having là câu lệnh lọc data < ở đây là lọc status_join sau khi đưa ra kết quả
            $query->having('status_join', 1);
            $event = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            if (!$status && $page > $event->lastPage()) {
                $page = 1;
                $event = event::where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
//                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('start_time', 'like', "%{$search}%")
                    ->orWhere('end_time', 'like', "%{$search}%")
                    ->withCount('attendances')->with('user')->with('keywords')->with([
                        'notifications',
                        'attendances' => function ($query) {
                            $query->with('user')->select('atendances.*')
                                ->selectSub(function ($subQuery) {
                                    $subQuery->selectRaw('IF(COUNT(feedback.id) > 0, 1, 0) as status_feedback')
                                        ->from('feedback')
                                        ->whereColumn('feedback.event_id', 'atendances.event_id')
                                        ->whereColumn('feedback.user_id', '=', 'atendances.user_id');
                                }, 'status_feedback');
                        }
                    ])
                    ->leftJoin('attendances', function ($join) {
                        $join->on('events.id', '=', 'attendances.event_id')
                            ->where('attendances.user_id', '=', Auth::user()->id);
                    })
                    ->select('events.*')
                    ->selectSub(function ($query) {
                        $query->selectRaw('IF(COUNT(attendances.id) > 0, 1, 0) as status_join')
                            ->from('attendances')
                            ->whereColumn('attendances.event_id', 'events.id')
                            ->where('attendances.user_id', Auth::user()->id);
                    }, 'status_join')
                    ->having('status_join', 1)
                    ->paginate($limit, ['*'], 'page', $page);
            }

//            $event->map(function ($event) {
//                $imageUrl = asset("Upload/{$event->banner}");
//                $event->banner = $imageUrl; // Thay đổi giá trị trường `url` của mỗi đối tượng
//                return $event;
//            });
            return response()->json(handleData($status, $event), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => $e instanceof HttpException
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR
            ], $e instanceof HttpException
                ? $e->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/event/notification",
     *     tags={"Event"},
     *     summary="lấy ra sự kiện sắp diễn ra  trước 24h ",
     *     description="
     * - Endpoint trả về các bản ghi sự kiện diễn ra trước 24h
     * -Role được sử dụng quản lí, nhân viên
     * - user trả về là thông tin của người tạo sự kiện
     * ",
     *     operationId="notificationEvent24h",
     *     @OA\Response(
     *         response=200,
     *         description="Dữ liệu trả về thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Lấy các sự kiện sắp diễn ra thành công"),
     *             @OA\Property(property="statusCode", type="int", example=200),
     *             @OA\Property(property="metadata", type="array",
     *                  @OA\Items(type="object",
     *                           @OA\Property(property="name", type="string", example="Event Name"),
     *                           @OA\Property(property="location", type="string", example="Ha Noi"),
     *                           @OA\Property(property="contact", type="string", example="0986567467"),
     *                           @OA\Property(property="user_id", type="integer", example=2),
     * @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                           @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                           @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *                           @OA\Property(property="user", type="array", @OA\Items(
     *                              type="object",
     *                      @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Mr. Stewart Boehm"),
     *                     @OA\Property(property="email", type="integer", example="upton.tessie@example.com"),
     *                     @OA\Property(property="phone", type="integer", example="2023-12-20T04:57:16.000000Z"),
     *                     @OA\Property(property="role", type="integer", example="205.840.6294"),
     *                         @OA\Property(property="google_id", type="string", example="137518716745268"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/ACg8ocL2nrwZ_mNIBGYaLd8tnzAJLMR0g_UXSVhY_BN67ZWA=s96-c"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                          )),
     *                         @OA\Property(property="attendances", type="array", @OA\Items(
     *                              type="object",
     *                     @OA\Property(property="id", type="interger", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="event_id", type="integer", example=2),
     *                          )),
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Sai validate",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Sai validate"),
     *             @OA\Property(property="statusCode", type="int", example=422),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi hệ thống"),
     *             @OA\Property(property="statusCode", type="int", example=500),
     *         )
     *     )
     * )
     */
    public function indexNotification(Request $request)
    {
        try {

//            $validator = Validator::make($request->all(), [
//                'id_user_get' => 'required'
//            ], [
//                'id_user_get.required' => 'id người lấy thông báo không được để trống',
//            ]);
//            if ($validator->fails()) {
//                return response([
//                    "status" => "error",
//                    "message" => $validator->errors()->all(),
//                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
//                ], Response::HTTP_INTERNAL_SERVER_ERROR);
//            }
            $currentDateTime = \Illuminate\Support\Carbon::now();
            $dateCr = $currentDateTime->toDateTimeString();
            $fiveHoursAhead = $currentDateTime->addHours(24)->toDateTimeString();
//            $user = User::find($request->id_user_get);
            if (Auth::user()->role == 0) {
                return response([
                    "status" => "error",
                    "message" => "Role người dùng không hợp lệ",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $events = event::where('start_time', '>=', $dateCr)
                ->with(['attendances.user', 'user'])
                ->where('start_time', '<', $fiveHoursAhead)
                ->where('status', 1)
                ->get();

            return response()->json([
                'metadata' => $events,
                'message' => 'Get All Records Successfully',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => $e instanceof HttpException
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR
            ], $e instanceof HttpException
                ? $e->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/searchEvent",
     *     summary="Tìm kiếm sự kiện theo tên",
     *     tags={"Event"},
     *     operationId="Tìm kiếm sự kiện",
     *     description="Request cần nhập vào là tên sự kiện. Tên sự kiện chỉ cần nhập gần giống, không nhất thiết phải giống hẳn. Endpoint trả ra là những sự kiện có tên dạng vậy
     *     - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     *     ",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Event Name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Event information retrieved successfully"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="metadata", type="object",
     *                 @OA\Property(property="docs", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="Event Name"),
     *                         @OA\Property(property="location", type="string", example="Ha Noi"),
     *                         @OA\Property(property="contact", type="string", example="0986567467"),
     *                         @OA\Property(property="user_id", type="integer", example=2),
     *                         @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                         @OA\Property(property="start_time", type="string", format="date-time", example="2023-11-23T11:20:22"),
     *                         @OA\Property(property="end_time", type="string", format="date-time", example="2023-11-23T11:20:22"),
     *                         @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *                         @OA\Property(property="attendances_count", type="integer", example=3),
     *                         @OA\Property(
     *                             property="user",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                             @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                             @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                             @OA\Property(property="role", type="integer", example=1),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
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
     *         description="Event not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Event not found"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal server error"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function searchEvent(Request $request)
    {
        try {
            if ($request->name == "" || $request->name == null) {
                $request->name == "";
            }
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
            $query = event::where('name', 'like', "%{$request->name}%")->withCount('attendances')->with('user');
            $event = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            if (!$status && $page > $event->lastPage()) {
                $page = 1;
                $event = event::where('name', 'like', "%{$request->name}%")->withCount('attendances')->with('user')->paginate($limit, ['*'], 'page', $page);
            }
//            $event->map(function ($event) {
//                $imageUrl = url(Storage::url("Upload/{$event->banner}"));
//                $event->banner = $imageUrl; // Thay đổi giá trị trường `url` của mỗi đối tượng
//                return $event;
//            });
            return response()->json(handleData($status, $event), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => $e instanceof HttpException
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR
            ], $e instanceof HttpException
                ? $e->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/recreateEvent",
     *     tags={"Event"},
     *     summary="Thêm mới bản ghi với dữ liệu được cung cấp",
     *     description="
     * - Endpoint trả về bản ghi mới được thêm vào
     * -Role đước sử dụng là nhân viên, quản lí
     * -id là id của sự kiện cần được tạo lại
     * -name là tên sự kiện
     * -location là nơi tổ chức sự kiện
     * -contact là liên lạc bằng số điện thoại
     * -banner là đường dẫn ảnh của sự kiện
     * -start_time là thời gian bắt đầu sự kiện
     * -end_time là thời gian kết thúc sự kiện
     * ",
     *     operationId="recreateEvent",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="interger", example=1),
     *             @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *             @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thêm mới dữ liệu thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Tạo mới bản ghi thành công"),
     *             @OA\Property(property="statusCode", type="int", example=200),
     *             @OA\Property(property="metadata", type="array",
     *                  @OA\Items(type="object",
     *                           @OA\Property(property="name", type="string", example="Event Name"),
     *                           @OA\Property(property="location", type="string", example="Ha Noi"),
     *                           @OA\Property(property="contact", type="string", example="0986567467"),
     *                           @OA\Property(property="user_id", type="integer", example=2),
     *                           @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                           @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                           @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *                          @OA\Property(property="area", type="string", example="Hà Nội"),
     *                           @OA\Property(property="attendances_count", type="interger", example=3),
     * @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                     @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                     @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                     @OA\Property(property="role", type="integer", example="1"),
     *                           @OA\Property(property="google_id", type="string", example="137518716745268"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/ACg8ocL2nrwZ_mNIBGYaLd8tnzAJLMR0g_UXSVhY_BN67ZWA=s96-c"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                 )
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Sai validate",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Sai validate"),
     *             @OA\Property(property="statusCode", type="int", example=422),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi hệ thống"),
     *             @OA\Property(property="statusCode", type="int", example=500),
     *         )
     *     )
     * )
     */
    public function recreateEvent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'start_time' => 'required',
                'end_time' => 'required|after:start_time'
            ], [
                'id.required' => 'Không được để trống id của sự kiện cần tạo lại',
                'start_time.required' => 'Không được để trống thời gian bắt đầu',
                'end_time.required' => 'Không được để trống thời gian kết thúc',
                'end_time.after' => 'Ngày kết thúc của dự án phải lớn hơn ngày bắt đầu',
                'user_id.required' => 'Không được để trống id của người tạo sự kiện'
            ]);
            if ($validator->fails()) {
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $logUserRole = auth()->user()->role;
            if ($logUserRole == 1 || $logUserRole == 2) {
                //Tạo thêm ảnh mới
                $event = Event::findOrFail($request->id);
//                $imageName = time() . '.' . pathinfo($event->banner, PATHINFO_EXTENSION);
//                $imageUrl = asset("Upload/{$imageName}");
//                $sourcePath =  Storage::path("Upload/{$event->banner}"); // Đường dẫn tệp tin hiện tại
//                $destinationPath = Storage::path("Upload/{$imageName}"); // Đường dẫn tới tệp tin mới
//                $sourcePath =  "Upload/{$event->banner}"; // Đường dẫn tệp tin hiện tại
//                $destinationPath = "Upload/{$imageName}"; // Đường dẫn tới tệp tin mới
//                dd($sourcePath,$destinationPath);
//                $success = File::copy($sourcePath, $destinationPath);
//                Storage::url("Upload/{$imageName}");
//                Storage::disk('public')->copy($sourcePath, $destinationPath);
                $newEventData = $event->toArray();

                $newEventData['status'] = 2;
                $newEventData['user_id'] = Auth::user()->id;
                $newEventData['start_time'] = $request->start_time;
                $newEventData['end_time'] = $request->end_time;
//                $newEventData['banner'] = $imageName;
                Event::create($newEventData);

                $eventRecreate = Event::orderBy('id', 'desc')->with('user')->first();
                return response()->json([
                    'metadata' => $eventRecreate,
                    'message' => 'Create Record Successfully',
                    'status' => 'success',
                    'statusCode' => Response::HTTP_OK
                ], Response::HTTP_OK);
            }
            return response([
                "status" => "error",
                "message" => "Không phải nhân viên hoặc quản lí thì không có quyền tạo lại sự kiện",
                'statusCode' => Response::HTTP_FORBIDDEN
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
                'statusCode' => $e instanceof HttpException
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR
            ], $e instanceof HttpException
                ? $e->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/event",
     *     tags={"Event"},
     *     summary="Thêm mới bản ghi với dữ liệu được cung cấp",
     *     description="
     * - Endpoint trả về bản ghi mới được thêm vào
     * -Role đước sử dụng là nhân viên, quản lí
     * -name là tên sự kiện
     * -location là nơi tổ chức sự kiện
     * -contact là liên lạc bằng số điện thoại
     * -banner là ảnh của sự kiện
     * -start_time là thời gian bắt đầu sự kiện
     * -end_time là thời gian kết thúc sự kiện
     * - keywords là mảng chứa các id của keyword có thể để trống
     * ",
     *     operationId="storeEvent",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Event Name"),
     *             @OA\Property(property="location", type="string", example="Hai Phong"),
     *             @OA\Property(property="contact", type="string", example="0983467584"),
     *             @OA\Property(property="banner", type="string",format = "binary", example="anh1.jpg"),
     *             @OA\Property(property="description", type="string", example="Sự kiện rất hoành tráng"),
     *             @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *             @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *      @OA\Property(property="keywords",type="array",@OA\Items(type="integer"),example="[1, 2]")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thêm mới dữ liệu thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Tạo mới bản ghi thành công"),
     *             @OA\Property(property="statusCode", type="int", example=200),
     *             @OA\Property(property="metadata", type="array",
     *                  @OA\Items(type="object",
     *                           @OA\Property(property="name", type="string", example="Event Name"),
     *                           @OA\Property(property="location", type="string", example="Ha Noi"),
     *                           @OA\Property(property="contact", type="string", example="0986567467"),
     *                           @OA\Property(property="user_id", type="integer", example=2),
     *                           @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                           @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                           @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                                   @OA\Property(property="description", type="string", example="Sự kiện rất hoành tráng"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *                           @OA\Property(property="attendances_count", type="interger", example=3),
     * @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                     @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                     @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                     @OA\Property(property="role", type="integer", example="1"),
     *                     @OA\Property(property="google_id", type="string", example="137518716745268"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/ACg8ocL2nrwZ_mNIBGYaLd8tnzAJLMR0g_UXSVhY_BN67ZWA=s96-c"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                 )
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Sai validate",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Sai validate"),
     *             @OA\Property(property="statusCode", type="int", example=422),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi hệ thống"),
     *             @OA\Property(property="statusCode", type="int", example=500),
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        //Check valiadate
//        dd($request->keywords);

//        [
//            function ($attribute, $value, $fail) {
//                $keywordIds = json_decode($value);
////                    if (empty($keywordIds) || !is_array($keywordIds) || count($keywordIds) === 0) {
////                        $fail('Mảng keywords không được trống và phải có ít nhất một phần tử.');
////                        return;
////                    }
//                $existingKeywords = keywords::whereIn('id', $keywordIds)->get();
//
//                $missingKeywords = collect($keywordIds)->diff($existingKeywords->pluck('id')->toArray());
//
//                if ($missingKeywords->isNotEmpty()) {
//                    $missingKeywordsList = $missingKeywords->implode(', ');
//                    $fail("Từ khóa có ID {$missingKeywordsList} không tồn tại! Vui lòng tạo mới và thử lại.");
//                }
//            },
//        ]
//        'description' => 'required',

//        old
//        'user_id' => ['required',Rule::exists('users', 'id')->where(function ($query) {
//        $query->whereIn('role', [1, 2]);
//    })]

        $validate = Validator::make($request->all(), [
            'name' => ['required'],
            'location' => ['required'],
            'description' => 'required',
            'contact' => [
                'required',
                'regex:/^(\+?\d{1,3}[- ]?)?\d{10}$/'
            ],
            'banner' => 'required',
            'start_time' => ['required'],
            'end_time' => ['required', 'after:start_time'],
            'content' => 'required',
            'keywords' => ['array',
                'min:1', // ít nhất một phần tử trong mảng
                Rule::exists('keywords', 'id')],
            'area' => 'required'
        ], [
            'name.required' => 'Không để trống name của của sự kiện nhập',
            'location.required' => 'Không được để trống địa điểm của sự kiện',
            'contact.required' => 'Không được để trống phần liên lạc',
            'contact.regex' => 'Định dạng số điện thoại được nhập không đúng',
            'banner.image' => 'Vui lòng upload banner định dạng file',
            'banner.mimes' => 'Vui lòng upload banner định dạng jpeg,png,jpg,gif,svg',
//            'user_id.required' => 'User Id không được để trống',
            'banner.required' => 'Ảnh sự kiện bắt buộc phải có',
            'start_time.required' => 'Ngày khởi đầu của event không được để trống',
            'end_time.required' => 'Ngày kết thúc của event không được để trống',
            'end_time.after' => 'Ngày kết thúc của dự án phải lớn hơn ngày bắt đầu',
            'description.required' => 'Không được để trống trường mô tả',
//            'user_id.exists' => 'Role của userid không hợp lệ hoặc người dùng không tồn tại',
            'content.required' => 'Không được để trống trường nội dung',
            'keywords.array' => 'keywords Phải là 1 array',
            'keywords.min' => 'Keywords Phải có ít nhất 1 phần tủ',
            'keywords.exists' => 'Trong số các keywords đẩy lên có 1 hoặc nhiều keywords không tồn tại,vui lòng thêm và thử lại',
            'area.required' => 'Nơi tổ chức phải tồn tại'
        ]);
        if ($validate->fails()) {
            //            dd($validate->errors());
            return response([
                "status" => "error",
                "message" => $validate->errors()->all(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $logUserRole = auth()->user()->role;
//        return $logUserRole;
        if ($logUserRole == 1 || $logUserRole == 2) {
            //Only staff and admin can make event
            try {
//                $imageName = time() . '.' . $request->banner->extension();
//                $request->banner->move(public_path('Upload'), $imageName);
//                $request->banner->storeAs('Upload', $imageName, 'public');
                $resourceData = $request->all();
//                $resourceData['banner'] = $imageName;
                $resourceData['user_id'] = Auth::user()->id;
                $event = event::create($resourceData);
                $returnData = event::withCount('attendances')->with('user')->findOrFail($event->id);
//                asset("Upload/{$returnData->banner}")
//                $returnData->banner = $imageName;
//                dd($request->keywords);
                if (!empty($request->keywords)) {
//                    dd($request->keywords);
//                    $keywordsReply = Str::of($request->keywords)->trim('[]')->explode(',');
                    $dataKeywords = collect($request->keywords)->map(function ($keywordId) use ($event) {
                        return [
                            'keywords_id' => (int)$keywordId,
                            'event_id' => $event->id,
                        ];
                    })->toArray();
                    events_keywords::insert($dataKeywords);
                    $returnData->event_keywords = $returnData->eventKeywords;
                }


                return response()->json([
                    'metadata' => $returnData,
                    'message' => 'Create Record Successfully',
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
        return response([
            "status" => "error",
            "message" => "Chỉ nhân viên và quán lí mới có thể tạo mới sự kiện",
            "statusCode" => Response::HTTP_CONFLICT
        ], Response::HTTP_CONFLICT);
    }

    /**
     * @OA\Get(
     *      path="/api/event/{id}",
     *      operationId="getEventsById",
     *      tags={"Event"},
     *      summary="Lấy dữ liệu sự kiện từ một id cho trước",
     *      description="
     * -Endpoint này lấy ra 1 bản ghi của sự kiện
     * - status_feedback <1|0> 1 là đã phản hồi sự kiện 0 là chưa phản hồi
     * -id là id của event mà mình cần tìm kiếm",
     *      @OA\Parameter(
     *          name="id",
     *          description="ID sự kiện ",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Get One Record Successfully"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Event Name"),
     *                           @OA\Property(property="location", type="string", example="Ha Noi"),
     *                           @OA\Property(property="contact", type="string", example="0986567467"),
     *                           @OA\Property(property="user_id", type="integer", example=2),
     *                           @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                           @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                           @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *                           @OA\Property(property="attendances_count", type="interger", example=3),
     * @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                     @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                     @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                     @OA\Property(property="role", type="integer", example="1"),
     *                     @OA\Property(property="google_id", type="string", example="137518716745268"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/ACg8ocL2nrwZ_mNIBGYaLd8tnzAJLMR0g_UXSVhY_BN67ZWA=s96-c"),
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


            $event = event::withCount('attendances')
                ->leftJoin('atendances', function ($join) {
                    $join->on('events.id', '=', 'atendances.event_id')
                        ->where('atendances.user_id', '=', Auth::user()->id);
                })
                ->select('events.*')
                ->selectSub(function ($query) {
                    $query->selectRaw('IF(COUNT(atendances.id) > 0, 1, 0) as status_join')
                        ->from('atendances')
                        ->whereColumn('atendances.event_id', 'events.id')
                        ->where('atendances.user_id', Auth::user()->id);
                }, 'status_join')
                ->selectSub(function ($query) {
                    $query->selectRaw('IF(COUNT(feedback.id) > 0, 1, 0) as status_feedBack_join')
                        ->from('feedback')
                        ->whereColumn('feedback.event_id', 'events.id')
                        ->where('feedback.user_id', Auth::user()->id);
                }, 'status_feedBack_join')
//                ->selectSub(function ($query) {
//                    $query->selectRaw('IF(COUNT(feedback.id) > 0, 1, 0) as status_feedback')
//                        ->from('feedback')
//                        ->whereColumn('feedback.event_id', 'events.id')
//                        ->where('feedback.user_id', Auth::user()->id);
//                }, 'status_feedback')
                ->with([
                    'feedback' => function ($query) {
                        $query->with('user');
                    },
                    'keywords',
                    'user',
                    'notifications' => function ($query) {
                        $query->with('create_by');
                    },
                    'attendances' => function ($query) {
                        $query->with('user')->select('atendances.*')
                            ->selectSub(function ($subQuery) {
                                $subQuery->selectRaw('IF(COUNT(feedback.id) > 0, 1, 0) as status_feedback')
                                    ->from('feedback')
                                    ->whereColumn('feedback.event_id', 'atendances.event_id')
                                    ->whereColumn('feedback.user_id', '=', 'atendances.user_id');
                            }, 'status_feedback');
                    }])
                ->findOrFail($id);
            return response()->json([
                'metadata' => $event,
                'message' => 'Get One Record Successfully',
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
     * @OA\Post(
     *     path="/api/eventStatistics",
     *     tags={"Event"},
     *     summary="Thống kê sự kiện ",
     *     description="
     * - Endpoint trả về các bản ghi sự kiện
     * -Role đước sử dụng quản lí, nhân viên
     * -name là tên sự kiện
     * -location là nơi tổ chức sự kiện
     * -contact là liên lạc bằng số điện thoại
     * -user_id là id của user tổ chức sự kiện này
     * -start_time là thời gian bắt đầu sự kiện
     * -end_time là thời gian kết thúc sự kiện
     * -attendances_count là số sinh viên tham gia
     *  - attendances là thông tin của các sinh viên tham gia
     *  - feedback là thông tin của các feedback của sinh viên
     * - start_time có thể rỗng
     * - end_time có thể rỗng
     * - Nhưng nếu nhập start_time thì bắt buộc phải nhập end_time
     * - Nếu không nhập cả start_time và end_time thì sẽ là thống kê của tuần hiện tại
     *     - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     * ",
     *     operationId="eventStatistics",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *             @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dữ liệ trả về thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Tạo mới bản ghi thành công"),
     *             @OA\Property(property="statusCode", type="int", example=200),
     *             @OA\Property(property="metadata", type="object",
     * @OA\Property(property="docs", type="array",
     *                  @OA\Items(type="object",
     *                           @OA\Property(property="name", type="string", example="Event Name"),
     *                           @OA\Property(property="location", type="string", example="Ha Noi"),
     *                           @OA\Property(property="contact", type="string", example="0986567467"),
     *                           @OA\Property(property="user_id", type="integer", example=2),
     * @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                           @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                           @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *                           @OA\Property(property="attendances_count", type="integer", example=0),
     *                           @OA\Property(property="feedback", type="array", @OA\Items(
     *                              type="object",
     *                      @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="content", type="string", example="Phucla DepZai"),
     *                     @OA\Property(property="user_id", type="integer", example="1"),
     *                     @OA\Property(property="event_id", type="integer", example="2"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                          )),
     *                         @OA\Property(property="attendances", type="array", @OA\Items(
     *                              type="object",
     *                     @OA\Property(property="id", type="interger", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="event_id", type="integer", example=2),
     *                          )),
     * @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                     @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                     @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                     @OA\Property(property="role", type="integer", example="1"),
     *                          @OA\Property(property="google_id", type="string", example="137518716745268"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/ACg8ocL2nrwZ_mNIBGYaLd8tnzAJLMR0g_UXSVhY_BN67ZWA=s96-c"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                 ))
     *                  ),
     *  @OA\Property(property="totalDocs", type="integer", example=16),
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
     *         response=422,
     *         description="Sai validate",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Sai validate"),
     *             @OA\Property(property="statusCode", type="int", example=422),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi hệ thống",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi hệ thống"),
     *             @OA\Property(property="statusCode", type="int", example=500),
     *         )
     *     )
     * )
     */
    public function eventStatistics(Request $request)
    {
        $logUser = auth()->user()->role;
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $status = $request->query('pagination', false);
        if ($logUser == 0) {
            return response([
                "status" => "error",
                "message" => 'Sinh viên không thể xem thống kê',
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        //Nếu không có request thì là mặc định tuần hiện tại
        if ($request->start_time == "" && $request->end_time == "") {
            $today = Carbon::now();

            // Lấy thông tin về tuần và năm
            $weekNumber = $today->weekOfYear;
            $year = $today->year;
            $dayOfWeekNumber = $today->dayOfWeek == 0 ? 7 : $today->dayOfWeek;

            //Lấy ngày đầu tiên, cuối cùng của tuần đó
            $firstDayOfWeekNumber = $today->copy()->addDays(-$dayOfWeekNumber + 1);
            $lastDayOfWeekNumber = $today->copy()->addDays(7 - $dayOfWeekNumber);
            $query = event::where('end_time', '>=', $firstDayOfWeekNumber)
                ->where('start_time', '<=', $lastDayOfWeekNumber)
                ->with('feedback')
                ->withCount('attendances')
                ->with([
                    'attendances' => function ($query) {
                        $query->with('user');
                    }
                ])
                ->with('keywords')
                ->with('user');
            $eventInWeek = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            if ($page > $eventInWeek->lastPage()) {
                $page = 1;
                $eventInWeek = event::where('end_time', '>=', $firstDayOfWeekNumber)
                    ->where('start_time', '<=', $lastDayOfWeekNumber)
                    ->with('feedback')
                    ->withCount('attendances')
                    ->with([
                        'attendances' => function ($query) {
                            $query->with('user');
                        }
                    ])
                    ->with('keywords')
                    ->with('user')
                    ->paginate($limit, ['*'], 'page', $page);
            }
//            $eventInWeek->map(function ($event) {
//                $imageUrl = asset("Upload/{$event->banner}");
//                $event->banner = $imageUrl; // Thay đổi giá trị trường `url` của mỗi đối tượng
//                return $event;
//            });
            return response()->json(handleData($status, $eventInWeek), Response::HTTP_OK);
        }
        $validator = Validator::make($request->all(), [
            'start_time' => 'required',
            'end_time' => 'required', 'after:start_time'
        ], [
            'start_time.required' => 'Ngày bắt đầu phải có',
            'end_time.required' => 'Ngày kết thúc phải có',
            'start_time.after' => 'Ngày kết thúc phải lớn hơn ngày bắt đầu'
        ]);
        if ($validator->fails()) {
            return response([
                "status" => "error",
                "message" => $validator->errors()->all(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $eventInStatistic = event::where('end_time', '>=', $request->start_time)
            ->where('start_time', '<=', $request->end_time)
            ->with('feedback')
            ->withCount('attendances')
            ->with([
                'attendances' => function ($query) {
                    $query->with('user');
                }
            ])
            ->with('keywords')
            ->with('user')
            ->paginate($limit, ['*'], 'page', $page);
//        $eventInStatistic->map(function ($event) {
//            $imageUrl = asset("Upload/{$event->banner}");
//            $event->banner = $imageUrl; // Thay đổi giá trị trường `url` của mỗi đối tượng
//            return $event;
//        });
        return response()->json([
            'metadata' => [
                'docs' => $eventInStatistic->items(),
                'totalDocs' => $eventInStatistic->total(),
                'limit' => $eventInStatistic->perPage(),
                'totalPages' => $eventInStatistic->lastPage(),
                'page' => $eventInStatistic->currentPage(),
                'pagingCounter' => $eventInStatistic->currentPage(), // Bạn có thể sử dụng currentPage hoặc số khác nếu cần
                'hasPrevPage' => $eventInStatistic->previousPageUrl() != null,
                'hasNextPage' => $eventInStatistic->nextPageUrl() != null
            ],
            'message' => 'Get One Record Successfully',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Patch(
     *     path="/api/event/{id}",
     *     operationId="updateEvent",
     *     tags={"Event"},
     *     summary="Sửa dữ liệu của một sự kiện ",
     *     description="
     * -Endpoint trả về dữ liệu bản ghi mới được sửa đổi
     * -Role quy định là role nhân viên, quản lí
     * -name là tên sự kiện
     * -location là nơi tổ chức sự kiện
     * -contact là liên lạc bằng số điện thoại
     * -start_time là thời gian bắt đầu sự kiện
     * -end_time là thời gian kết thúc sự kiện
     * -Banner là ảnh chuyển qua mã base 64
     * -keywords là mảng chứa id keyword cần cập nhật ( Lưu ý : phải tồn tại keywords đó)
     * ",
     *     operationId="eventPut",
     * @OA\Parameter(
     *         name="id",
     *         description="ID của một sự kiện",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     * @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Event Name"),
     *             @OA\Property(property="location", type="string", example="Hai Phong"),
     *             @OA\Property(property="contact", type="string", example="0983118678"),
     *             @OA\Property(property="status", type="integer", example=0),
     * @OA\Property(property="banner", type="string", example="anh1.jpg"),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *       *      @OA\Property(property="keywords",type="array",@OA\Items(type="integer"),example="[1, 2]")
     *         )
     *     ),
     * @OA\Response(
     *         response=200,
     *         description="Sửa dữ liệu thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Update One Record Successfully"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="metadata", type="object",
     *                 @OA\Property(property="location", type="string", example="Hai Phong"),
     *                 @OA\Property(property="contact", type="string", example="0983118678"),
     *                 @OA\Property(property="status", type="integer", example=0),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     * @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     * @OA\Property(property="attendances_count", type="integer", example=3),
     * @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                     @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                     @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                     @OA\Property(property="role", type="integer", example="1"),
     *                      @OA\Property(property="google_id", type="string", example="137518716745268"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/ACg8ocL2nrwZ_mNIBGYaLd8tnzAJLMR0g_UXSVhY_BN67ZWA=s96-c"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                 )
     *             )
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại "),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     * @OA\Response(
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
        //Check validate
//        dd($request->all());

        $validate = Validator::make($request->all(), [
            'contact' => [
                'regex:/^(\+?\d{1,3}[- ]?)?\d{10}$/'
            ],
            'status' => [
                Rule::in([0, 1, 2])
            ],
            'end_time' => ['after:start_time'],
            'keywords' => ['array',
                'min:1', // ít nhất một phần tử trong mảng
                Rule::exists('keywords', 'id')]
        ], [
            'name.required' => 'Không để trống name của của sự kiện nhập',
            'location.required' => 'Không được để trống địa điểm của sự kiện',
            'contact.required' => 'Không được để trống phần liên lạc',
            'contact.regex' => 'Định dạng số điện thoại được nhập không đúng',
            'status.required' => 'Trạng thái của sự kiện không được để trống',
            'status.in' => 'Vui lòng nhập đúng trạng thái',
//            'user_id.required' => 'User Id không được để trống',
            'start_time.required' => 'Ngày khởi đầu của event không được để trống',
            'end_time.required' => 'Ngày kết thúc của event không được để trống',
            'banner.required' => 'Không được để trống ảnh',
            'end_time.after' => 'Ngày kết thúc của dự án phải lớn hơn ngày bắt đầu',
            'description.required' => 'Không được để trống trường mô tả',
            'content.required' => 'Không được để trống trường nội dung',
            'keywords.array' => 'keywords Phải là 1 array',
            'keywords.min' => 'Keywords Phải có ít nhất 1 phần tủ',
            'keywords.exists' => 'Trong số các keywords đẩy lên có 1 hoặc nhiều keywords không tồn tại,vui lòng thêm và thử lại'
        ]);
        if ($validate->fails()) {
            return response([
                "status" => "error",
                "message" => $validate->errors()->all(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $logUserRole = auth()->user()->role;
        if ($logUserRole == 1 || $logUserRole == 2) {
            //Check role
            $event = event::with('user')->findOrFail($id);
            try {
                //Xóa ảnh
//                $imagePath = public_path('Upload/' . $event->banner);
//                File::delete($imagePath);
//                Storage::disk('public')->delete('Upload/' .$event->getRawOriginal('banner'));
                //Thêm ảnh mới
//                $image_64 = $request->banner; //your base64 encoded data
//
//                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf
//
//                $replace = substr($image_64, 0, strpos($image_64, ',')+1);

// find substring fro replace here eg: data:image/png;base64,
//
//                $image = str_replace($replace, '', $image_64);
//
//                $image = str_replace(' ', '+', $image);
//
//                $imageName = Str::random(10).'.'.$extension;
//                $img = base64_decode($image);
//                $imageName = time() . '.' . $request->banner->extension();
//                $request->banner->move(public_path('Upload'), $img);
//                Storage::disk('public')->put('Upload/' . $imageName, base64_decode($image));
//                $imageUrl = Storage::url($imagePath);
                $resourceData = $request->only(['name', 'location', 'contact', 'status','description', 'banner', 'start_time', 'end_time', 'content', 'user_id', 'keywords']);
//                $resourceData['banner'] = $imageName;
//                $resourceData['user_id'] = Auth::user()->id;
//                $resourceData
                $event->update($resourceData);
//                url("Upload/{$event->banner}")
//                $event->banner = url(Storage::url("Upload/{$imageName}"));
//                dd($event->banner);
                if (!empty($request->keywords)) {
                    events_keywords::where("event_id", $event->id)->delete();
                    $dataKeywords = collect($request->keywords)->map(function ($keywordId) use ($event) {
                        return [
                            'keywords_id' => (int)$keywordId,
                            'event_id' => $event->id,
                        ];
                    })->toArray();
                    events_keywords::insert($dataKeywords);
                    $event->event_keywords = $event->eventKeywords;
                }
                return response()->json([
                    'metadata' => $event,
                    'message' => 'Update One Record Successfully',
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
        return response([
            "status" => "error",
            "message" => "Chỉ nhân viên và quản lí mới có quyền sửa bản ghi",
            "statusCode" => Response::HTTP_CONFLICT
        ], Response::HTTP_CONFLICT);
    }

    /**
     * @OA\Delete(
     *     path="/api/event/{id}",
     *     summary="Xóa một bản ghi",
     *     tags={"Event"},
     * description="
     *          - Endpoint này sẽ xóa 1 sự kiện
     *          - Role được sử dụng là role Quản lí
     *          - Xóa thành công sẽ trả lại data là của các sự kiện còn lại
     *          - id là id của event cần xóa
     *          ",
     *     @OA\Parameter(
     *         name="events",
     *         in="path",
     *         required=true,
     *         description="events record model",
     *         @OA\Schema(type="integer")
     *     ),
     *      @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Xóa một bản ghi thành công"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     * @OA\Property(property="metadata",type="array",
     *                  @OA\Items(
     *                      type="object",
     *                       @OA\Property(property="name", type="string", example="Event Name"),
     *                       @OA\Property(property="location", type="string", example="Ha Noi"),
     *                       @OA\Property(property="contact", type="string", example="0986567467"),
     *                       @OA\Property(property="user_id", type="integer", example=2),
     *                       @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                       @OA\Property(property="start_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="end_time", type="string",format="date-time", example="2023-11-23 11:20:22"),
     *                       @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     * @OA\Property(property="attendances_count", type="interger", example=3),
     * @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Kurtis Legros IV"),
     *                     @OA\Property(property="email", type="string", example="haudvph20519@fpt.edu.vn"),
     *                     @OA\Property(property="phone", type="string", example="+1 (564) 267-3494"),
     *                     @OA\Property(property="role", type="integer", example="1"),
     *                      @OA\Property(property="google_id", type="string", example="137518716745268"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/a/ACg8ocL2nrwZ_mNIBGYaLd8tnzAJLMR0g_UXSVhY_BN67ZWA=s96-c"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-02T08:55:45.000000Z")
     *                 )
     *                  )
     *              )
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
            $event = event::findOrFail($id);
            if (!$event) {
                return response()->json([
                    'message' => 'Không tồn tại bản ghi',
                    'status' => 'error',
                    'statusCode' => Response::HTTP_NOT_FOUND
                ], Response::HTTP_NOT_FOUND);
            }
            $logUserRole = auth()->user()->role;
            if ($logUserRole != 2) {
                return response()->json([
                    'message' => 'Không phải quản lí thì sẽ không có quyền xóa',
                    'status' => 'error',
                    'statusCode' => Response::HTTP_FORBIDDEN
                ], Response::HTTP_FORBIDDEN);
            }
            //Xóa ảnh
//            dd($event->getRawOriginal('banner'));

//            $imagePath = public_path('Upload/' . $event->banner);
//            File::delete($imagePath);
//            Storage::disk('public')->delete('Upload/' .$event->getRawOriginal('banner'));
            events_keywords::where("event_id", $event->id)->delete();
            $event->delete();
            $restOfEvents = event::with('user')->get();
//            $restOfEvents->map(function ($event) {
//                $imageUrl = asset("Upload/{$event->banner}");
//                $event->banner = $imageUrl; // Thay đổi giá trị trường `url` của mỗi đối tượng
//                return $event;
//            });
            return response()->json([
                'metadata' => $restOfEvents,
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

    /**
     * @OA\Get(
     *      path="/api/statistics",
     *      operationId="Statistic",
     *      tags={"Event"},
     *      summary="Lấy ra những điều cần thiết trong trang dashboard",
     *      description="
     *  -Endpoint này lấy ra những điều cần thiết trong trang dashboard
     *  -eventInLastMonth là số sự kiện diễn ra trong tháng trước
     *  -eventInCurrentMonth là số sự kiện diễn ra trong tháng này
     *  -percentInEvent là phần trăm tăng bao nhiêu số sự kiện từ thang trước sang tháng này
     *  -Tương tự với joinEvent là người tham gia sự kiện được thống kê ở mỗi tháng
     *  -Tương tự với feedBack là số feedback được thống kê ở mỗi tháng
     *  -userInRoleStaff là số nhân viên từ trước tới nay",
     *      @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Thống kê thành công"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="eventInLastMonth", type="integer", example=1),
     *                           @OA\Property(property="eventInCurrentMonth", type="integer", example=1),
     *                           @OA\Property(property="percentInEvent", type="integer", example=0.8),
     *                           @OA\Property(property="joinEventInCurrentMonth", type="integer", example=2),
     *                           @OA\Property(property="joinEventInLastMonth", type="integer", example=1),
     *                           @OA\Property(property="percentInJoinEvent", type="integer", example=0.9),
     *                           @OA\Property(property="userInRoleStaff", type="integer", example=10),
     *                           @OA\Property(property="feedBackInCurrentMonth", type="integer", example=10),
     *                           @OA\Property(property="feedBackInLastMonth", type="integer", example=15),
     *                           @OA\Property(property="percentInFeedBack", type="interger", example=0.7),
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
    public function Statistics()
    {
        // return auth()->user();
//        dd(Auth::user()->role);
        if (Auth::user()->role != 2) {
            return response()->json([
                'message' => 'Không phải quản lí thì không có quyền vào xem thống kê',
                'status' => 'error',
                'statusCode' => Response::HTTP_FORBIDDEN
            ], Response::HTTP_FORBIDDEN);
        }
        $currentTime = Carbon::now();
        $dayIncurrentMonth = $currentTime->daysInMonth;
        $firstDayOfMonth = Carbon::now()->startOfMonth();
        $lastDayOfMonth = Carbon::now()->endOfMonth();

        $firstDayOfLastMonth = Carbon::now()->subDays($dayIncurrentMonth)->startOfMonth();
        $lastDayOfLastMonth = Carbon::now()->subDays($dayIncurrentMonth)->endOfMonth();
        //Tổng số sự kiện tháng hiện tại
        $eventInCurrentMonth = event::where('end_time', '>=', $firstDayOfMonth)
            ->where('start_time', '<=', $lastDayOfMonth)
            ->where('status', '<', 2)
            ->count();
        //Tổng số sự kiện tháng trước
        $eventInLastMonth = event::where('end_time', '>=', $firstDayOfLastMonth)
            ->where('start_time', '<=', $lastDayOfLastMonth)
            ->where('status', '<', 2)
            ->count();
        //Tổng số sinh viên tham gia tháng này
        $joinEventInCurrentMonth = atendance::where('created_at', '>=', $firstDayOfMonth)
            ->where('created_at', '<=', $lastDayOfMonth)
            ->count();
        //Tổng số sinh viên tham gia tháng trước
        $joinEventInLastMonth = atendance::where('created_at', '>=', $firstDayOfLastMonth)
            ->where('created_at', '<=', $lastDayOfLastMonth)
            ->count();

        //Tổng số nhân viên từ trước tới nay
        $userInRoleStaff = User::where('role', 1)->count();

        //Tổng số feedback tham gia tháng này
        $feedBackInCurrentMonth = feedback::where('created_at', '>=', $firstDayOfMonth)
            ->where('created_at', '<=', $lastDayOfMonth)
            ->count();
        //Tổng số feedback tham gia tháng trước
        $feedBackInLastMonth = feedback::where('created_at', '>=', $firstDayOfLastMonth)
            ->where('created_at', '<=', $lastDayOfLastMonth)
            ->count();

        //Validate nếu mẫu bằng 0
        $percentInEvent = ($eventInLastMonth == 0)
            ? 1
            : ($eventInCurrentMonth - $eventInLastMonth) / $eventInLastMonth;
        $percentInJoinEvent = ($joinEventInLastMonth == 0)
            ? 1
            : ($joinEventInCurrentMonth - $joinEventInLastMonth) / $joinEventInLastMonth;
        $percentInFeedBack = ($feedBackInLastMonth == 0)
            ? 1
            : ($feedBackInCurrentMonth - $feedBackInLastMonth) / $feedBackInLastMonth;

        $returnData = [
            'eventInLastMonth' => $eventInLastMonth,
            'eventInCurrentMonth' => $eventInCurrentMonth,
            'percentInEvent' => $percentInEvent,
            'joinEventInCurrentMonth' => $joinEventInCurrentMonth,
            'joinEventInLastMonth' => $joinEventInLastMonth,
            'percentInJoinEvent' => $percentInJoinEvent,
            'userInRoleStaff' => $userInRoleStaff,
            'feedBackInCurrentMonth' => $feedBackInCurrentMonth,
            'feedBackInLastMonth' => $feedBackInLastMonth,
            'percentInFeedBack' => $percentInFeedBack
        ];

        return response()->json([
            'metadata' => $returnData,
            'message' => 'Lấy thông tin thống kê thành công',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/eventStatisticsStudent",
     *     summary="Lấy thông tin thống kê sinh viên join sự kiện theo tháng ,năm",
     *     tags={"Event"},
     *     description="Trả ra 12 tháng và số lượng sinh viên tham gia sự kiện trong tháng đó
     *      - Param không bắt buộc là year (năm) , mặc định là năm hiện tại ",
     * @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Lấy dữ liệu thống kê thành công"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="metadata", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Jan"),
     *                     @OA\Property(property="total", type="integer", example=0),
     *                 )
     *             )
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bản ghi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy bản ghi"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     * @OA\Response(
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
    public function StatisticsStudentJoin(Request $request)
    {
        if (Auth::user()->role != 2) {
            return response()->json([
                'message' => 'Không phải quản lí thì không có quyền vào xem thống kê',
                'status' => 'error',
                'statusCode' => Response::HTTP_FORBIDDEN
            ], Response::HTTP_FORBIDDEN);
        }
        $year = $request->input('year') ?? Carbon::now()->year;
        $months = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec',
        ];

        $statistics = atendance::select(
            DB::raw("DATE_FORMAT(created_at, '%b') as month_name"),
            DB::raw("DATE_FORMAT(created_at, '%m') as month_number"), // Lấy số tháng
            DB::raw('COUNT(*) as total_students')
        )
            ->whereYear('created_at', $year)
            ->groupBy('month_number', 'month_name')
            ->get();

        $statisticsArray = [];

        foreach ($months as $monthNumber => $monthName) {
            $data = $statistics->firstWhere('month_number', $monthNumber);
            $statisticsArray[] = [
                'name' => $monthName,
                'total' => $data ? $data->total_students : 0,
            ];
        }
        return response()->json([
            'metadata' => $statisticsArray,
            'message' => 'Lấy thông tin thống kê thành công',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/getNearstEvent",
     *     summary="Lấy tất cả các sự kiện diễn ra gần nhất",
     *     tags={"Event"},
     *     description="Endpoint trả về thông tin của 5 sự kiện đang và sắp diễn ra gần nhất",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Lấy dữ liệu thành công"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="metadata", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Event Name"),
     *                     @OA\Property(property="location", type="string", example="Ha Noi"),
     *                     @OA\Property(property="contact", type="string", example="0986567467"),
     *                     @OA\Property(property="user_id", type="integer", example=2),
     *                     @OA\Property(property="banner", type="string", example="http://127.0.0.1:8000/Upload/1702785355.jpg"),
     *                     @OA\Property(property="start_time", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                     @OA\Property(property="end_time", type="string", format="date-time", example="2023-11-23 11:20:22"),
     *                     @OA\Property(property="content", type="string", example="Chào mừng tổng thống"),
     *                     @OA\Property(property="attendances_count", type="integer", example=3),
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bản ghi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy bản ghi"),
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
    public function getNearstEvent()
    {
        $currentTime = Carbon::now();
        $fiveEventNearst = event::where('status', '>', 0)
            ->where('start_time', '>=', $currentTime)
            ->orWhere('start_time', '<', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();
        return response()->json([
            'metadata' => $fiveEventNearst,
            'message' => 'Lấy ra 5 sự kiện đã kết thúc gần nhất',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }


}

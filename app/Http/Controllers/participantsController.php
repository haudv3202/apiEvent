<?php

namespace App\Http\Controllers;

use App\Http\Resources\ParticipantsResources;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;

class participantsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/participants",
     *     summary="Lấy tất cả bản ghi",
     *     tags={"Participants"},
     *  description="
     *      - Endpoint trả về thôn tin của tất cả người dùng.
     *      - Role được sử dụng là role quản lí
     *      - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     *     - role=0,1,2 khi truyền thêm param này sẽ là lọc data trả ra các user có role nào < Mặc định là role 0>
     *     - name=<tên người dùng> khi truyền thêm param này sẽ là lọc data trả ra các user có tên như thế < Mặc định là null>
     *     - email=<email người dùng> khi truyền thêm param này sẽ là lọc data trả ra các user có email như thế < Mặc định là null>
     *     ",
     *     @OA\Response(
     *         response=200,
     *         description="Dữ liệu trả về thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Get All Record Successfully"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="docs", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Phuc La"),
     *                     @OA\Property(property="email", type="string", example="phuclaf@gmail.com"),
     *                     @OA\Property(property="password", type="string", example="123456"),
     *                     @OA\Property(property="phone", type="string", example="0983118272"),
     *                     @OA\Property(property="role", type="integer", example=1),
     *                 ))
     *             ),
     *              @OA\Property(property="totalDocs", type="integer", example=16),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=2),
     *                 @OA\Property(property="page", type="integer", example=2),
     *                 @OA\Property(property="pagingCounter", type="integer", example=2),
     *                 @OA\Property(property="hasPrevPage", type="boolean", example=true),
     *                 @OA\Property(property="hasNextPage", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bản ghi không tồn tại",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại "),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi server"),
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
            $role = $request->query('role', 0);
            if (auth()->user()->role != 2) {
                return response([
                    "status" => "error",
                    "message" => "Role người dùng không hợp lệ",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $query = User::query();

            if ($request->role != null) {
                if (strpos($role, ',') !== false) {
                    $roles = explode(',', $role);
                    $query->whereIn('role', $roles);
                } else {
                    // Nếu không có dấu phẩy, thực hiện truy vấn với điều kiện cho role đơn
                    $query->where('role', $role);
                }
            }

            $name = $request->query('name','');
            if($name !== '' && $name !== null){
                $query->where('name', 'like', '%' . $name . '%');
            }
            $email = $request->query('email','');
            if($email !== '' && $email !== null){
                $query->where('email', 'like', '%' . $email . '%');
            }
//            $users = ($status) ?  User::all() : User::paginate($limit, ['*'], 'page', $page);
            $users = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);

            if (!$status && $page > $users->lastPage()) {
                $page = 1;
                $users = User::paginate($limit, ['*'], 'page', $page);
            }
            return response()->json(handleData($status, $users), Response::HTTP_OK);
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
     *     path="/api/searchUser",
     *     summary="Tìm kiếm người dùng theo email hoặc số điện thoại",
     *     tags={"Participants"},
     *     description="
     * -Tìm kiếm theo post
     * -Request là email và phone
     * -email là email của người cần tìm, không cần nhập quá giống
     * -phone là số diện thoại của người cần tìm, không cần nhập quá giống
     * -Ta sẽ tìm kiếm theo SDT hoặc Email
     * -Role là tất cả các role
     *     - Sẽ có 1 số option param sau
     *     - page=<số trang> chuyển sang trang cần
     *     - limit=<số record> số record muốn lấy trong 1 trang
     *     - pagination=true|false sẽ là trạng thái phân trang hoặc không phân trang <mặc định là false phân trang>
     * ",
     *     operationId="getUserByEmailAndPhone",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="phuclaf@gmail.com"),
     *             @OA\Property(property="phone", type="string", example="0983118272")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Dữ liệu người dùng được trả về thành công"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *  @OA\Property(property="docs", type="array",
     * @OA\Items( type="object",
     *                 @OA\Property(property="name", type="string", example="Phuc La"),
     *                 @OA\Property(property="email", type="string", example="phuclaf@gmail.com"),
     *                 @OA\Property(property="password", type="string", example="123456"),
     *                 @OA\Property(property="phone", type="string", example="0983118272"),
     *                 @OA\Property(property="role", type="integer", example=1)
     *             ))),
     * @OA\Property(property="totalDocs", type="integer", example=16),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=2),
     *                 @OA\Property(property="page", type="integer", example=2),
     *                 @OA\Property(property="pagingCounter", type="integer", example=2),
     *                 @OA\Property(property="hasPrevPage", type="boolean", example=true),
     *                 @OA\Property(property="hasNextPage", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng mong muốn",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy người dùng"),
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
    public function getUserByEmailAndPhone(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'phone' => 'required'

            ], [
                'email.required' => 'Email không được để trống',
                'phone.required' => 'Số điện thoại không được để trống'
            ]);
            if ($validator->fails()) {
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $data = $request->all();
            $email = $data['email'];
            $phone = $data['phone'];
            $query = User::
            where(function ($query) use ($email, $phone) {
                $query->where('email', 'like', "%{$email}%")
                    ->orWhere('phone', 'like', "%{$phone}%");
            });
            $users = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            if ($page > $users->lastPage()) {
                $page = 1;
                $users = User::
                where(function ($query) use ($email, $phone) {
                    $query->where('email', 'like', "%{$email}%")
                        ->orWhere('phone', 'like', "%{$phone}%");
                })
                    ->paginate($limit, ['*'], 'page', $page);
            }
            return response()->json(handleData($status, $users), Response::HTTP_OK);
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
     *     path="/api/participants",
     *     tags={"Participants"},
     *     summary="Thêm mới người dùng với dữ liệu được cung cấp",
     *     description="
     * -Endpoint trả về người dùng vừa được thêm
     * -Role người thêm phải lớn hơn hoặc bằng người được thêm
     * -Role là sinh viên thì không có quyền thêm
     * - Password mặc định sẽ là tên email đằng trước dấu @ ví dụ Email là example@gmail.com  thì password là example
     * ",
     *     operationId="storeParticipants",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
    *            @OA\Property(property="name", type="string", example="Phuc La"),
     *                     @OA\Property(property="email", type="string", example="phuclaf@gmail.com"),
     *                     @OA\Property(property="phone", type="string", example="0983118272"),
     *                     @OA\Property(property="role", type="integer", example=1),
     *         )
     *     ),
     *    @OA\Response(
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
     *                     @OA\Property(property="name", type="string", example="Phuc La"),
     *                     @OA\Property(property="email", type="string", example="phuclaf@gmail.com"),
     *                     @OA\Property(property="password", type="string", example="123456"),
     *                     @OA\Property(property="phone", type="string", example="0983118272"),
     *                     @OA\Property(property="role", type="integer", example=1),
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Validation error or internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="object", example={"user_id": {"User ID is required"}}),
     *             @OA\Property(property="statusCode", type="int", example=500),
     *         )
     *     ),
     * )
     */
    public function store(Request $request)
    {
        try {
            $logUser = auth()->user()->role;
            $userAdd = $request->role;
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required'
                ],
                'email' => [
                    'required','unique:users,email'
                ],
                'phone' => [
                    'required',
                    'regex:/^(\+?\d{1,3}[- ]?)?\d{10}$/','unique:users,phone'
                ],
                'role' => [
                    'required',
                    Rule::in([0, 1, 2])
                ],
                'student_code' => 'unique:users,student_code'
            ], [
                'name.required' => 'Không để trống name của người dùng',
                'email.required' => 'Không để trống email của người dùng',
                'phone.required' => 'Số điện thoại không được để trống',
                'phone.regex' => 'Số điện thoại không đúng định dạng',
                'role.required' => 'Role không được để trống',
                'role.in' => 'Role phải là 0 hoặc 1 hoặc 2',
                'student_code.unique' => 'Mã sinh viên đã tồn tại',
                'email.unique' => 'Email đã tồn tại',
                'phone.unique' => 'Số điện thoại đã tồn tại',
                'name.unique' => 'Tên người dùng đã tồn tại'
            ]);

            if ($validator->fails()) {
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if ($logUser < $userAdd || $logUser == 0) {
                //Nếu role thấp hơn hoặc role là sinh viên thì loại
                return response([
                    "status" => "error",
                    "message" => "Sai role ,Role không được là sinh viên, và người add phải có role lớn hơn người được add",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $data = $validator->validated();
            $data['password'] = bcrypt(explode('@', $request->email)[0]);
            $user = User::create($data);
            return response()->json([
                'metadata' => $user,
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
     *     path="/api/importUser",
     *     summary="Nhập danh sách người dùng từ tệp Excel
     *      - Nhập file excel có dạng type là file name =  listUser",
     *     tags={"Participants"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                              @OA\Property(property="listUser", type="string", example="nhập file lên nhé"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nhập danh sách người dùng thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Thông điệp thành công"),
     *             @OA\Property(property="status", type="string", description="Trạng thái"),
     *             @OA\Property(property="statusCode", type="integer", description="Mã trạng thái HTTP"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Yêu cầu phải là quản trị viên hoặc nhân viên",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Thông điệp lỗi"),
     *             @OA\Property(property="status", type="string", description="Trạng thái"),
     *             @OA\Property(property="statusCode", type="integer", description="Mã trạng thái HTTP"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Thông điệp lỗi"),
     *             @OA\Property(property="status", type="string", description="Trạng thái"),
     *             @OA\Property(property="statusCode", type="integer", description="Mã trạng thái HTTP"),
     *         )
     *     )
     * )
     */

    public function importUser(Request $request)
    {
        try {
            if (auth()->user()->role == 0) {
                return response()->json([
                    'message' => 'Yêu cầu phải là quản trị viên hoặc nhân viên',
                    'status' => 'error',
                    'statusCode' => Response::HTTP_CONFLICT
                ], Response::HTTP_CONFLICT);
            }
            $List = Excel::toArray([], $request->file('listUser'));
            $dataImport = [];
            for ($i = 1; $i < count($List[0]); $i++) {
                if (!empty($List[0][$i][1]) && !empty($List[0][$i][2]) && !empty($List[0][$i][3] && !empty($List[0][$i][4]))) {
                    $email = $List[0][$i][2];

                    // Kiểm tra xem email đã tồn tại trong cơ sở dữ liệu hay chưa
                    $existingUser = User::where('email', $email)->first();
                    if (!$existingUser) {
                        // Nếu email chưa tồn tại, thêm vào mảng dataImport
                        $dataHandle = explode('@', $email)[0];
                        $dataImport[] = [
                            'name' => $List[0][$i][1],
                            'email' => $email,
                            'role' => 0,
                            'phone' => $List[0][$i][3],
                            'student_code' => $List[0][$i][4],
                            'password' => bcrypt($dataHandle),
                            'created_at' => now()
                        ];
                    } else {
                        continue;
                    }
                }
            }
            if (!empty($dataImport)) {
                DB::table('users')->insert($dataImport);
                     return response()->json([
                         'message' => "Nhập thành công " . count($dataImport) . " người dùng",
                         'status' => 'success',
                         'statusCode' => Response::HTTP_OK
                     ], Response::HTTP_OK);
            }else {
                return response()->json([
                    'message' => "Không có người dùng nào được nhập hoặc đã có trong hệ thống",
                    'status' => 'error',
                    'statusCode' => Response::HTTP_CONFLICT
                ], Response::HTTP_CONFLICT);
            }

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
     *      path="/api/participants/{id}",
     *      operationId="getParticipantsById",
     *      tags={"Participants"},
     *      summary="Lấy dữ liệu người dùng theo id cho trước",
     *      description="
     * -Endpoint trả về một người dùng theo id cho trước
     * id là id của người dùng",
     *      @OA\Parameter(
     *          name="id",
     *          description="Participant ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Lấy bản ghi thành công"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                         @OA\Property(property="name", type="string", example="Phuc La"),
     *                     @OA\Property(property="email", type="string", example="phuclaf@gmail.com"),
     *                     @OA\Property(property="password", type="string", example="123456"),
     *                     @OA\Property(property="phone", type="string", example="0983118272"),
     *                     @OA\Property(property="role", type="integer", example=1),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bản ghi nào như thế",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy bản ghi nào như thế"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi server"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $users = User::findOrFail($id);
            return response()->json([
                'metadata' => $users,
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
     * @OA\Patch(
     *      path="/api/participants/{id}",
     *      operationId="updateParticipants",
     *      tags={"Participants"},
     *      summary="Sửa dữ liệu bản ghi theo id cho trước",
     *      description="
     * -Sửa người dùng cho theo id cho trước
     * -Endpoint trả về người mới được sửa đổi
     * -Người sửa role không được lớn hơn người được sửa
     * - Role sinh viên không được sửa bất kì cái gì",
     *      @OA\Parameter(
     *          name="id",
     *          description="Mẫu người dùng",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *             @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Phuc La"),
     *             @OA\Property(property="email", type="string", example="phucla@gmail.com"),
     *             @OA\Property(property="phone", type="string", example="0982221151"),
     *             @OA\Property(property="role", type="integer", example=1),
     *         )
     *      ),
     *      @OA\Response(
     *         response=200,
     *         description="Dữ liệu trả về thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Sửa dữ liệu thành công"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                      @OA\Property(property="id", type="string", example="1"),
     *                      @OA\Property(property="name", type="string", example="Phuc La"),
     *                      @OA\Property(property="email", type="string", example="phucla@gmail.com"),
     *                      @OA\Property(property="phone", type="string", example="0982221151"),
     *                      @OA\Property(property="role", type="integer", example=1),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Record not exists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Record not exists"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Server error"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if (auth()->check()) {
            $logUserRole = auth()->user()->role;
        } else {
            return response([
                'status' => 'error',
                'message' => 'Not logged in yet',
                'statusCode' => Response::HTTP_UNAUTHORIZED
            ], Response::HTTP_UNAUTHORIZED);
        }

        $roleUpdate = $request->input('role');
        $canUpdate = false;

        //Validate cho request
        $validator = Validator::make($request->all(), [
            'email' => [
                'regex:~^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$~'
            ],
            'phone' => [
                'regex:/^(\+?\d{1,3}[- ]?)?\d{10}$/'
            ],
            'role' => [
                Rule::in([0, 1, 2])
            ]
        ], [
            'name.required' => 'Không để trống name của người dùng',
            'email.required' => 'Không để trống email của người dùng',
            'email.regex' => 'Email được nhập vào không đúng định dạng',
            'password.required' => 'Password không dược để trống',
            'phone.required' => 'Số điện thoại không được để trống',
            'phone.regex' => 'Số điện thoại không đúng định dạng',
            'role.required' => 'Role không được để trống'
        ]);

        //Nếu nó sai từ validate request thì nó dừng luôn
        if ($validator->fails()) {
            return response([
                "status" => "error",
                "message" => $validator->errors()->all(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        //Check role của từng người
        if ($logUserRole == 2) {
            $canUpdate = true;
        } else if ($logUserRole == 1) {
            if ($roleUpdate == 2) {
                return response([
                    "status" => "error",
                    "message" => "Nhân viên không thể sửa đổi thông tin quản lí",
                    "statusCode" => Response::HTTP_CONFLICT
                ], Response::HTTP_CONFLICT);
            } else {
                //Đây là 2 trường hợp còn lại là 0,1 : nhân viên, sinh viên
                $canUpdate = true;
            }
        } else {
            //Trường hợp còn lại là sinh viên thì không cho chỉnh sửa bất cứ cải gì
            return response([
                "status" => "error",
                "message" => "Sinh viên không thể sửa đổi cái gì",
                "statusCode" => Response::HTTP_CONFLICT
            ], Response::HTTP_CONFLICT);
        }
        if ($canUpdate == true) {
            $data = $request->only(['name', 'email', 'phone', 'role', 'student_code']);;
            $user->update($data);
        }
        return response()->json([
            'metadata' => $user,
            'message' => 'Update One Record Successfully',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Patch(
     *     path="/api/updateUser",
     *     summary="Cập nhật thông tin người dùng",
     *     description="Cập nhật chi tiết người dùng, bao gồm tên, email, điện thoại và hình đại diện. Mật khẩu có thể được cập nhật nếu được cung cấp.",
     *     operationId="updateUser",
     *     tags={"Participants"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to update",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="User details to be updated",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="phone", type="string", format="phone"),
     *                 @OA\Property(property="avatar", type="string"),
     *                 @OA\Property(property="password", type="string", format="password"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="metadata", type="object", description="Updated user details"),
     *             @OA\Property(property="message", type="string", description="Update successful message"),
     *             @OA\Property(property="status", type="string", description="Status of the response (success)"),
     *             @OA\Property(property="statusCode", type="integer", description="HTTP status code (200)"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Not logged in",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", description="Status of the response (error)"),
     *             @OA\Property(property="message", type="string", description="Unauthorized error message"),
     *             @OA\Property(property="statusCode", type="integer", description="HTTP status code (401)"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Validation error or internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", description="Status of the response (error)"),
     *             @OA\Property(property="message", type="array", @OA\Items(type="string"), description="Validation error messages"),
     *             @OA\Property(property="statusCode", type="integer", description="HTTP status code (500)"),
     *         )
     *     ),
     * )
     */

    public function updateUser(Request $request)
    {
        if (auth()->check()) {
            $logUserRole = auth()->user()->role;
        } else {
            return response([
                'status' => 'error',
                'message' => 'Not logged in yet',
                'statusCode' => Response::HTTP_UNAUTHORIZED
            ], Response::HTTP_UNAUTHORIZED);
        }
        $user = User::findOrFail(auth()->user()->id);


        //Validate cho request
        $validator = Validator::make($request->all(), [
            'email' => [
                'regex:~^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$~'
            ],
            'phone' => [
                'regex:/^(\+?\d{1,3}[- ]?)?\d{10}$/'
            ]
        ], [
            'name.required' => 'Không để trống name của người dùng',
            'email.required' => 'Không để trống email của người dùng',
            'email.regex' => 'Email được nhập vào không đúng định dạng',
            'password.required' => 'Password không dược để trống',
            'phone.required' => 'Số điện thoại không được để trống',
            'phone.regex' => 'Số điện thoại không đúng định dạng',
            'role.required' => 'Role không được để trống'
        ]);

        //Nếu nó sai từ validate request thì nó dừng luôn
        if ($validator->fails()) {
            return response([
                "status" => "error",
                "message" => $validator->errors()->all(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        $data = $request->only(['name', 'email', 'phone', 'avatar']);
        if ($request->has('password')) {
            // If the password field is present, update the password
            $data['password'] = Hash::make($request->input('password'));
        }
        $user->update($data);
        return response()->json([
            'metadata' => $user,
            'message' => 'Update One Record Successfully',
            'status' => 'success',
            'statusCode' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/api/participants/{List ID}",
     *     summary="Xóa 1 bản ghi người dùng",
     * description="
     * - List ID là id người dùng cần xóa có thể xóa nhiều người dùng cùng lúc bằng cách phân cách bằng dấu phẩy
     * - Ví dụ muốn xóa 1 người thì truyền là 1, muốn xóa nhiều người thì truyền là 1,2,3,4
     * - Role xóa chỉ có thể là quản lí
     * ",
     *     tags={"Participants"},
     *     @OA\Parameter(
     *         name="participants",
     *         in="path",
     *         required=true,
     *         description="Mô hình dữ liệu người dùng",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Xóa bản ghi thành công"),
     *             @OA\Property(property="statusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Record not exists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Bản ghi không tồn tại"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Lỗi server"),
     *             @OA\Property(property="statusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function destroy($listUser)
    {
        try {
//            dd($listUser);
//            $user = User::findOrFail($listUser);
//            if (!$user) {
//                return response()->json([
//                    'message' => 'Record not exists',
//                    'status' => 'error',
//                    'statusCode' => Response::HTTP_NOT_FOUND
//                ], Response::HTTP_NOT_FOUND);
//            }
            if (auth()->user()->role != 2) {
                return response()->json([
                    'message' => 'Không thể xóa bản ghi do role không phải quản lí',
                    'status' => 'error',
                    'statusCode' => Response::HTTP_CONFLICT
                ], Response::HTTP_CONFLICT);
            }
            $userIdsArray = explode(',', $listUser);

            $userIds = array_map('intval', $userIdsArray);
            $deletedUsers = User::whereIn('id', $userIds)->delete();
            if ($deletedUsers > 0) {
                return response()->json([
                    'message' => 'Xóa '.$deletedUsers.' người dùng thành công',
                    'status' => 'success',
                    'statusCode' => Response::HTTP_OK
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Không có người dùng nào được xóa',
                    'status' => 'error',
                    'statusCode' => Response::HTTP_NOT_FOUND
                ], Response::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            return response([
                "status" => "error",
                "message" => $e->getMessage(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

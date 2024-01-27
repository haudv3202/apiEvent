<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\area;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AreasController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/areas",
     *     summary="Lấy ra tất cả các cơ sở",
     *     tags={"areas"},
     *     description="
     *      - Endpoint này cho phép lấy ra thông tin các cơ sở.
     *      - Role được sử dụng quản quản lí, nhân viên, sinh viên ",
     *     @OA\Response(
     *         response=200,
     *         description="Lấy ra tất cả các cơ sở",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved all area"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="docs", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="1"),
     *                         @OA\Property(property="name", type="string", example="Hà nội"),
     *                         @OA\Property(property="address", type="string", example="mô tả 1"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
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
    public function index(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('pagination', false);
            $query = area::query();
            $areas = ($status) ? $query->get() : $query->paginate($limit, ['*'], 'page', $page);
            if (!$status && $page > $areas->lastPage()) {
                $page = 1;
                $areas = area::paginate($limit, ['*'], 'page', $page);
            }
            return response()->json(handleData($status,$areas), Response::HTTP_OK);
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/api/areas",
     *     tags={"areas"},
     *     summary="Tạo 1 cơ sở mới",
     *     description="
     *      - Endpoint này cho phép thêm cở sở mới.
     *      - Trả về thông tin cơ sở đó.
     *      - Role được sử dụng quản quản lí
     *      - description là mô tả cơ sở đó <có thể để trống>",
     *     operationId="areas",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Hà Nội"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Thêm cơ sở thành công"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example="3"),
     *                 @OA\Property(property="name", type="string", example="Hà Nội"),
     *                 @OA\Property(property="address", type="string", example="desribe"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-05T12:36:46.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-05T12:36:46.000000Z")
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
                'name' => 'required',
            ], [
                'name.required' => 'Tên cơ sở không để trống',
            ]);

            if($validator->fails()){
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if (Auth::user()->role != 2) {
                return response([
                    "status" => "error",
                    "message" => "Role người dùng không hợp lệ",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $area = area::create($request->all());
            return response()->json([
                'metadata' => $area,
                'message' => 'Create Successfully',
                'status' => 'success',
                'statusCode' => Response::HTTP_CREATED
            ], Response::HTTP_CREATED);
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
     * @OA\Get(
     *     path="/api/areas/{id}",
     *     summary="Lấy ra 1 cơ sở",
     *     tags={"areas"},
     *     description="
     *      - Endpoint này cho phép lấy ra thông tin 1 cơ sở.
     *      - Role được sử dụng quản quản lí",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của cơ sở",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example="1"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy ra 1 cơ sở",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Get One Record Successfully"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example="1"),
     *                 @OA\Property(property="name", type="string", example="Hà nội"),
     *                 @OA\Property(property="address", type="string", example="mô tả 1"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error hoặc không tìm thấy cơ sở",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message",
     *            type="string", example="Cơ sở không tồn tại"),
     *        @OA\Property(property="statusCode", type="integer", example=500)
     *       )
     *    )
     * )
     */
    public function show(string $id)
    {
        try {
            $area = area::find($id);
            if(!$area){
                return response([
                    "status" => "error",
                    "message" => "Cơ sở không tồn tại",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            return response()->json([
                'metadata' => $area,
                'message' => 'Get One Record Successfully',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

    }

    /**
     * @OA\Patch(
     *     path="/api/areas/{id}",
     *     tags={"areas"},
     *     summary="Cập nhật 1 cơ sở",
     *     description="
     *      - Endpoint này cho phép cập nhật thông tin 1 cơ sở.
     *      - Role được sử dụng quản quản lí
     *      - description là mô tả cơ sở đó <có thể để trống>",
     *     operationId="updateAreas",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của cơ sở",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example="1"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Hà Nội"),
     *             @OA\Property(property="address", type="string", example="mô tả 1"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Cập nhật cơ sở thành công"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example="3"),
     *                 @OA\Property(property="name", type="string", example="Hà Nội"),
     *                 @OA\Property(property="address", type="string", example="desribe"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-05T12:36:46.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-05 12:36:46"),
     *        )
     *    )
     * ),
     *     @OA\Response(
     *     response=500,
     *     description="Validation error or internal server error",
     *     @OA\JsonContent(
     *     @OA\Property(property="status", type="string", example="error"),
     *     @OA\Property(property="message", type="object", example={"user_id": {"User does not exist"}}),
     *     @OA\Property(property="statusCode", type="int", example=500)
     *    )
     *  ),
     *     security={
     *     {"bearerAuth": {}}
     *     }
     *     )
     */
    public function update(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'unique:areas,name',
            ], [
                'name.unique' => 'Tên cơ sở đã tồn tại'
            ]);

            if($validator->fails()){
                return response([
                    "status" => "error",
                    "message" => $validator->errors()->all(),
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $area = area::find($id);
            if(!$area){
                return response([
                    "status" => "error",
                    "message" => "Cơ sở không tồn tại",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if (Auth::user()->role != 2) {
                return response([
                    "status" => "error",
                    "message" => "Role người dùng không hợp lệ",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $area->update($request->only(['name','address']));
            return response()->json([
                'metadata' => $area,
                'message' => 'Update Successfully',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
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
     * @OA\Delete(
     *     path="/api/areas/{id}",
     *     tags={"areas"},
     *     summary="Xóa 1 cơ sở",
     *     description="
     *      - Endpoint này cho phép xóa 1 cơ sở.
     *      - Role được sử dụng quản quản lí",
     *     operationId="Deleteareas",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của cơ sở",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example="1"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa 1 cơ sở thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Xóa cơ sở thành công"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example="1"),
     *                 @OA\Property(property="name", type="string", example="Hà nội"),
     *                 @OA\Property(property="address", type="string", example="mô tả 1"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-28 17:02:29"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error hoặc không tìm thấy cơ sở",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(
     *                 property="message",
     *            type="string",
     *       example="Cơ sở không tồn tại"
     *     ),
     *     @OA\Property(property="statusCode", type="integer", example=500)
     *   )
     * )
     * )
     */

    public function destroy(string $id)
    {
        try {
            $area = area::find($id);
            if(!$area){
                return response([
                    "status" => "error",
                    "message" => "Cơ sở không tồn tại",
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $area->delete();
            return response()->json([
                'metadata' => $area,
                'message' => 'Delete Successfully',
                'status' => 'success',
                'statusCode' => Response::HTTP_OK
            ], Response::HTTP_OK);
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
}

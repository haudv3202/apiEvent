<?php

use Illuminate\Http\Response;
function handleData($status = false,$data){
    return ($status) ? [
        'metadata' => $data,
        'message' => 'Get All Records Successfully',
        'status' => 'success',
        'statusCode' => Response::HTTP_OK
    ] : [
        'metadata' => [
            'docs' => $data->items(),
            'totalDocs' => $data->total(),
            'limit' => $data->perPage(),
            'totalPages' => $data->lastPage(),
            'page' => $data->currentPage(),
            'pagingCounter' => $data->currentPage(), // Bạn có thể sử dụng currentPage hoặc số khác nếu cần
            'hasPrevPage' => $data->previousPageUrl() != null,
            'hasNextPage' => $data->nextPageUrl() != null
//                    'prevPage' => $atendance->previousPageUrl(),
//                    'nextPage' =>$atendance->nextPageUrl(),
        ],
        'message' => 'Lấy thành công tất cả các bản ghi',
        'status' => 'success',
        'statusCode' => Response::HTTP_OK
    ];
}


function getNotifications($query, $status, $type, $limit, $page) {
    if ($status === 'true') {
        switch ($type) {
            case 'all':
                return $query->withTrashed()->get();
            case 'sent':
                return $query->whereNotNull('sent_at')->get();
            case 'scheduled':
                return $query->whereNull('sent_at')->get();
            default:
                return $query->onlyTrashed()->get();
        }
    } else {
        switch ($type) {
            case 'all':
                return $query->withTrashed()->paginate($limit, ['*'], 'page', $page);
            case 'sent':
                return $query->whereNotNull('sent_at')->paginate($limit, ['*'], 'page', $page);
            case 'scheduled':
                return $query->whereNull('sent_at')->paginate($limit, ['*'], 'page', $page);
            default:
                return $query->onlyTrashed()->paginate($limit, ['*'], 'page', $page);
        }
    }
}


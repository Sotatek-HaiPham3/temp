<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Consts;
use Illuminate\Validation\ValidationException;
use App\Http\Services\FileUploadService;
use App\Http\Requests\FileUploadFormRequest;
use Illuminate\Support\Facades\Auth;
use Exception;
use DB;

class FileUploadAPIController extends AppBaseController
{

    protected $fileUploadService;

    public function __construct()
    {
        $this->fileUploadService = new FileUploadService;
    }

    /**
     * @SWG\Post(
     *   path="/upload-file",
     *   summary="Upload File",
     *   tags={"Files"},
     *   consumes={"multipart/form-data"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="file",
     *       in="formData",
     *       required=true,
     *       type="file"
     *   ),
     *  @SWG\Parameter(
     *       name="start",
     *       in="formData",
     *       required=false,
     *       type="integer"
     *   ),
     *  @SWG\Parameter(
     *       name="duration",
     *       in="formData",
     *       required=false,
     *       type="integer"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function uploadFile(FileUploadFormRequest $request)
    {
        $fileUpload = $request->file('file');
        $userId = Auth::id();

        $destinationFolder = $userId ? "users/{$userId}" : 'guest'; 

        $data = $this->fileUploadService->uploadFile($fileUpload, $request->all(), $destinationFolder);

        return $this->sendResponse([
            'id' => $data->id,
            'file_path' => $data->file_path,
            'is_video' => !empty($data->is_video)
        ]);
    }

    /**
     * @SWG\Post(
     *   path="/s3/pre-signed",
     *   summary="Generate Pre-Signed Form",
     *   tags={"Files"},
     *   consumes={"multipart/form-data"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="filename", in="formData", required=true, type="string"),
     *   @SWG\Parameter(name="mime_type", in="formData", required=true, type="string"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function generatePreSignedS3Form(Request $request)
    {
        $request->validate([
            'filename' => 'required',
            'mime_type' => 'required'
        ]);
        $data = $this->fileUploadService->generatePreSignedS3Form($request->all());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Post(
     *   path="/video/verify-upload",
     *   summary="Verify Upload S3",
     *   tags={"Files"},
     *   consumes={"multipart/form-data"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="game_id", in="formData", required=true, type="integer"),
     *   @SWG\Parameter(name="title", in="formData", required=true, type="string"),
     *   @SWG\Parameter(name="mimetype", in="formData", required=true, type="string"),
     *   @SWG\Parameter(name="prefix", in="formData", required=true, type="string"),
     *   @SWG\Parameter(name="filename", in="formData", required=true, type="string"),
     *   @SWG\Parameter(name="description", in="formData", required=false, type="string"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function verifyUploadS3(Request $request)
    {
        $request->validate([
            'game_id'   => 'required|exists:games,id',
            'title'     => 'required',
            'tags'      => 'array',
            'mimetype'  => 'required',
            'prefix'    => 'required',
            'filename'  => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->fileUploadService->verifyUploadS3($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
     * @SWG\Put(
     *   path="/remove-upload-file",
     *   summary="Remove upload File",
     *   tags={"Files"},
     *   consumes={"multipart/form-data"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="id",
     *       in="formData",
     *       required=true,
     *       type="integer"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function removeFileUpload(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:file_uploads,id'
        ]);

        $data = $this->fileUploadService->removeFileUpload($request->id);

        return $this->sendResponse('ok');
    }
}

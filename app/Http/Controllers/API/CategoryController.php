<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    const PATH_UPLOAD = 'categories';

    /**
     * @OA\Get(
     *     path="/api/admin/categories",
     *     summary="Lấy danh sách danh mục",
     *     tags={"Category"},
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách danh mục",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Category")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::query()
            ->with(['children'])
            ->where('parent_id', null)
            ->get();
        $categoryParent = Category::query()->where('parent_id', null)->get();
        return response()->json(
            [
                'success' => true,
                'message' => 'Lấy thành công danh mục',
                'data' => ['categories' => $categories, 'categoryParent' => $categoryParent],
            ],
            200,
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'name' => ['required', 'max:255'],
                'image' => ['required', 'mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'parent_id' => ['nullable', 'exists:categories,id'],
            ]);

            $imageUrl = null;

            if ($request->hasFile('image')) {
                // Lấy ảnh từ yêu cầu
                $image = $request->file('image');

                // Upload ảnh lên Imgur
                $response = Http::withHeaders([
                    'Authorization' => 'Client-ID b806999527d9d43',
                ])->attach(
                    'image', file_get_contents($image->getRealPath()), $image->getClientOriginalName()
                )->post('https://api.imgur.com/3/image');

                // Kiểm tra xem yêu cầu có thành công không
                if ($response->successful()) {
                    $imageUrl = $response->json()['data']['link'];
                } else {
                    throw new \Exception('Không thể upload ảnh lên Imgur');
                }
            }

            $data['image'] = $imageUrl;

            // Tạo danh mục mới
            $category = Category::query()->create($data);

            DB::commit();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Thêm danh mục thành công.',
                    'data' => [
                        'category' => $category,
                        'image_url' => $data['image'], // Trả về URL của ảnh từ Imgur
                    ],
                ],
                201,
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Thêm danh mục thất bại',
                    'error' => $exception->getMessage()
                ],
                500,
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Lấy thành công dữ liệu của bản ghi ' . $id,
                    'data' => $category,
                ],
                200,
            );
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'lấy dữ liệu không thành công',
                    'error' => $exception->getMessage()
                ],
                500,
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'name' => ['required', 'max:255'],
                'image' => ['mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'parent_id' => ['nullable', 'exists:categories,id'],
            ]);

            $model = Category::query()->findOrFail($id);

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store(self::PATH_UPLOAD, 'public');

                $image_old = $model->image;
            } else {
                $image_old = null;
            }

            $model->update($data);

            if ($image_old && Storage::exists($image_old)) {
                Storage::delete($image_old);
            }

            DB::commit();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Cập nhật danh mục thành công.',
                    'data' => $model,
                ],
                201,
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Cập nhật danh mục thất bại',
                    'error' => $exception->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $model = Category::findOrFail($id);

            $model->delete();

            if ($model->image && Storage::exists($model->image)) {
                Storage::delete($model->image);
            }

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Xoá danh mục thành công.',
                ],
                200,
            );

        } catch (\Exception $e) {

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Xoá danh mục không thành công.',
                    'error' => $e->getMessage()
                ],
                500,
            );
        }
    }
}

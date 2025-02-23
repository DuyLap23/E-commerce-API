<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    const PATH_UPLOAD = 'categories';

    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Lấy danh sách danh mục",
     *     description="Trả về danh sách tất cả các danh mục chính và danh mục con.",
     *     tags={"Category"},
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true,
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lấy thành công danh mục",
     *             ),
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=3,
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Category 2",
     *                     ),
     *                     @OA\Property(
     *                         property="slug",
     *                         type="string",
     *                         example="",
     *                     ),
     *                     @OA\Property(
     *                         property="image",
     *                         type="string",
     *                         example="https://picsum.photos/200/300?random=2",
     *                     ),
     *                     @OA\Property(
     *                         property="parent_id",
     *                         type="integer",
     *                         nullable=true,
     *                         example=null,
     *                     ),
     *                     @OA\Property(
     *                         property="created_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2024-09-05T16:26:27.000000Z",
     *                     ),
     *                     @OA\Property(
     *                         property="updated_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2024-09-05T16:26:27.000000Z",
     *                     ),
     *                     @OA\Property(
     *                         property="deleted_at",
     *                         type="string",
     *                         nullable=true,
     *                         example=null,
     *                     ),
     *                     @OA\Property(
     *                         property="children",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(
     *                                 property="id",
     *                                 type="integer",
     *                                 example=9,
     *                             ),
     *                             @OA\Property(
     *                                 property="name",
     *                                 type="string",
     *                                 example="Category 3",
     *                             ),
     *                             @OA\Property(
     *                                 property="slug",
     *                                 type="string",
     *                                 example="",
     *                             ),
     *                             @OA\Property(
     *                                 property="image",
     *                                 type="string",
     *                                 example="https://picsum.photos/200/300?random=3",
     *                             ),
     *                             @OA\Property(
     *                                 property="parent_id",
     *                                 type="integer",
     *                                 example=3,
     *                             ),
     *                             @OA\Property(
     *                                 property="created_at",
     *                                 type="string",
     *                                 format="date-time",
     *                                 example="2024-09-05T16:26:27.000000Z",
     *                             ),
     *                             @OA\Property(
     *                                 property="updated_at",
     *                                 type="string",
     *                                 format="date-time",
     *                                 example="2024-09-05T16:26:27.000000Z",
     *                             ),
     *                             @OA\Property(
     *                                 property="deleted_at",
     *                                 type="string",
     *                                 nullable=true,
     *                                 example=null,
     *                             ),
     *                         ),
     *                     ),
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false,
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lỗi khi lấy danh mục.",
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Server Error Message",
     *             ),
     *         )
     *     )
     * )
     */


    public function index(Request $request)
    {
        // Lấy giá trị tìm kiếm
        $search = $request->input('search');

        // Nếu có tìm kiếm
        if ($search) {
            // Chỉ tìm theo tên, không lấy quan hệ
            $categories = Category::where('name', 'LIKE', "%{$search}%")
                ->orWhere('slug', 'LIKE', "%{$search}%")
                ->latest('id')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách danh mục thành công',
                'data' => [
                    'categories' => $categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'image' => $category->image,
                            'created_at' => $category->created_at
                        ];
                    }),
                ]
            ], 200);
        }

        // Nếu không có tìm kiếm, lấy toàn bộ danh mục cha và con
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->orWhere('parent_id', 0)
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy thành công danh mục',
            'data' => [
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'image' => $category->image,
                        'created_at' => $category->created_at,
                        'children' => $category->children->map(function ($child) {
                            return [
                                'id' => $child->id,
                                'name' => $child->name,
                                'slug' => $child->slug,
                                'image' => $child->image,
                                'created_at' => $child->created_at
                            ];
                        })
                    ];
                }),
            ]
        ], 200);
    }
    public function getCategoryChild()
    {
        $categories = Category::with('children')
            ->where('parent_id', 0) // Lấy các danh mục cha
            ->whereHas('children') // Kiểm tra danh mục cha có danh mục con
            ->latest('id')
            ->get();
        if (!$categories) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục',
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Lấy thành công danh mục',
            'categories' => $categories,
        ], 200);
    }

    public function fillerCategory()
    {
        $categories = Category::query()
            ->where('parent_id', '!=', 0)
            ->latest('id')
            ->get();
        if (!$categories) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục con',
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Lấy thành công danh mục con',
            'categories' => $categories,
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/api/admin/categories/trashed",
     *     summary="Lấy danh mục đã xóa",
     *     tags={"Category"},
     *     security={{"Bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thành công danh mục đã xóa.",
     *         @OA\Schema(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thành công danh mục đã xoá"),
     *             @OA\Property(property="trashedCategories", type="array", @OA\Items(ref="#/components/schemas/Category")),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Bạn cần đăng nhập để xem thông tin.",
     *         @OA\Schema(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn cần đăng nhập để xem thông tin.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không thể lấy được danh mục đã xóa.",
     *         @OA\Schema(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không thể lấy được danh mục đã xoá.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Đã xảy ra lỗi không mong muốn.",
     *         @OA\Schema(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi không mong muốn.")
     *         )
     *     ),
     * )
     */
    public function trashed()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để xem thông tin.'
                ], 401); // 401 Unauthorized
            }

            // Lấy tất cả danh mục đã xóa
            $trashedCategories = Category::onlyTrashed()->get();
            if (!$trashedCategories) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy danh mục',
                ]);
            }
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Lấy thành công danh mục đã xoá',
                    'trashedCategories' => $trashedCategories,
                ],
                200,
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy được danh mục đã xoá.'
            ], 404);
        } catch (\Exception $e) {
            // Ghi log lỗi
            Log::error('Đã xảy ra lỗi: ' . $e->getMessage());

            // Nếu có lỗi không mong muốn khác, trả về lỗi 500
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn.'
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/admin/categories",
     *     summary="Thêm danh mục mới",
     *     description="Thêm danh mục vào hệ thống.",
     *     tags={"Category"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Tên danh mục",
     *                     example="Danh mục 1"
     *                 ),
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     description="Hình ảnh danh mục (đường dẫn ảnh)",
     *                     example="",
     *                 ),
     *                 @OA\Property(
     *                     property="parent_id",
     *                     type="integer",
     *                     description="ID danh mục cha (nếu có)",
     *                     example=1
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Thêm danh mục thành công."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=2
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Danh mục 1"
     *                     ),
     *                     @OA\Property(
     *                         property="image",
     *                         type="string",
     *                         example="",
     *                         description="Để ở đây trống để test"
     *                     ),
     *                     @OA\Property(
     *                         property="parent_id",
     *                         type="integer",
     *                         example=1
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Truy cập bị từ chối (không phải admin)",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Bạn không phải admin."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Thêm danh mục thất bại",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Thêm danh mục thất bại"
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Chi tiết lỗi..."
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải admin.'
            ], 403);
        }

        try {
            $data = $request->validate([
                'name' => ['required', 'max:255'],
                'image' => ['nullable', 'mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'parent_id' => ['nullable', 'exists:categories,id'],
            ]);

            if (Category::where('name', $request->name)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tên danh mục đã tồn tại, vui lòng chọn tên khác.'
                ], 400);
            }

            $data['slug'] = Str::slug($request->name);

            if ($request->hasFile('image')) {
                $data['image'] = asset('storage/' . $request->file('image')->store(self::PATH_UPLOAD, 'public'));
            }

            $data['parent_id'] = $data['parent_id'] ?? 0;

            if ($data['parent_id'] && Category::find($data['parent_id'])->parent_id != 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục cha không thể là danh mục con của một danh mục khác!'
                ], 400);
            }

            $category = Category::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Thêm danh mục thành công.',
                'data' => ['category' => $category],
            ], 201);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Thêm danh mục thất bại',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     summary="Lấy chi tiết danh mục hoặc lấy sản phẩm theo danh mục",
     *     tags={"Category"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của danh mục",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thành công dữ liệu của danh mục",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thành công dữ liệu của bản ghi {id}"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Category Name"),
     *                 @OA\Property(property="image", type="string", example="http://127.0.0.1:8000/storage/categories/WH9uJPvTt5VjkpDr0dE5uEkeXaFtCKaOwuAups3C.jpg"),
     *                 @OA\Property(property="parent_id", type="string", example=""),
     *                 @OA\Property(property="slug", type="string", example="category-name"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Danh mục không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lấy dữ liệu không thành công"),
     *             @OA\Property(property="error", type="string", example="No query results for model [Category]"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lấy dữ liệu không thành công"),
     *             @OA\Property(property="error", type="string", example="Server Error Message"),
     *         )
     *     )
     * )
     */
    public function showClient(string $id)
    {
        try {
            // Kiểm tra danh mục
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại!',
                ], 404);
            }

            if ($category->parent_id == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đây là danh mục cha, vui lòng chọn danh mục con!',
                ], 400);
            }

            // Lấy sản phẩm thuộc danh mục
            $products = Product::where('category_id', $id)
                ->where('is_active', 1)
                ->select(['id', 'name', 'slug', 'img_thumbnail', 'price_sale', 'price_regular', 'brand_id']) // Chỉ chọn các cột cần thiết
                ->get();


            return response()->json([
                'success' => true,
                'message' => 'Lấy thành công dữ liệu của danh mục: ' . $category->name,
                'products' => $products,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu không thành công!',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            // Kiểm tra danh mục
            $category = Category::query()
                ->with(['children']) // Lấy danh mục con
                ->where('id', $id)
                ->first(); // Chỉ lấy một danh mục

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại!',
                ], 404);
            }


            return response()->json([
                'success' => true,
                'message' => 'Lấy thành công dữ liệu của danh mục: ',
                'categories' => $category,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu không thành công!',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/api/admin/categories/{id}",
     *     summary="Cập nhật danh mục",
     *     description="Cập nhật thông tin của một danh mục.",
     *     tags={"Category"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của danh mục cần cập nhật",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Tên danh mục"),
     *             @OA\Property(property="image", type="string", format="binary", example="image.jpg"),
     *             @OA\Property(property="parent_id", type="integer", example=1),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật danh mục thành công."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tên danh mục"),
     *                 @OA\Property(property="slug", type="string", example="ten-danh-muc"),
     *                 @OA\Property(property="image", type="string", example="http://127.0.0.1:8000/storage/categories/image.jpg"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Danh mục không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Danh mục không tìm thấy"),
     *             @OA\Property(property="error", type="string", example="No query results for model [Category]"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Thông tin không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Danh mục cha không thể là danh mục con của một danh mục khác!"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cập nhật danh mục thất bại"),
     *             @OA\Property(property="error", type="string", example="Server Error Message"),
     *         )
     *     )
     * )
     */

    public function update(Request $request, string $id)
    {
        try {
            $data = $request->validate([
                'name' => ['required', 'max:255'],
                'slug' => ['nullable', 'max:255', 'unique:categories,slug,' . $id],
                'image' => ['nullable', 'mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'parent_id' => ['nullable', 'exists:categories,id'],
            ]);

            $model = Category::query()->findOrFail($id);
            $existingCategory = Category::where('name', $request->name)
            ->where('id', '!=', $id)
            ->where('parent_id', $request->parent_id ?? 0)  // Thêm điều kiện kiểm tra parent_id
            ->first();

        if ($existingCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Tên danh mục đã tồn tại trong cùng cấp, vui lòng chọn tên khác.'
            ], 400);
        }

            // Kiểm tra nếu parent_id không tồn tại hoặc null, gán giá trị mặc định là 0
            if (!isset($data['parent_id'])) {
                $data['parent_id'] = 0;
            }

            // Kiểm tra xem parent_id có hợp lệ không
            if ($data['parent_id'] && $data['parent_id'] != 0) {
                $parentID = Category::query()->find($data['parent_id']);
                if ($parentID && $parentID->parent_id != 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Danh mục cha không thể là danh mục con của một danh mục khác!',
                    ], 400);
                }
            }

            // Kiểm tra và cập nhật slug
            if ($request->has('slug')) {
                $data['slug'] = Str::slug($request->input('slug'));
            } else {
                $data['slug'] = Str::slug($request->name);
            }

            // Xử lý ảnh nếu có
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store(self::PATH_UPLOAD, 'public');
                $data['image'] = asset('storage/' . $path);
                $image_old = $model->image;
            } else {
                $image_old = null;
            }

            // Cập nhật thông tin danh mục
            $model->update($data);

            // Xóa ảnh cũ nếu có
            if ($image_old && Storage::exists($image_old)) {
                Storage::delete($image_old);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật danh mục thành công.',
                'data' => $model,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật danh mục thất bại',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */

/**
 * @OA\Delete(
 *     path="/api/admin/categories/{id}",
 *     summary="Xóa danh mục và chuyển sản phẩm sang danh mục lưu trữ",
 *     tags={"Category"},
 *     security={{"Bearer": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID của danh mục cần xóa",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Xóa danh mục thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Xóa danh mục thành công. Các sản phẩm đã được chuyển sang danh mục 'Category 1 (Lưu trữ)'"),
 *             @OA\Property(property="archived_category_id", type="integer", example=15)
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Chưa đăng nhập",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Bạn chưa đăng nhập.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Không có quyền",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Bạn không phải admin.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Không tìm thấy danh mục",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Danh mục không tồn tại.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Không thể xóa danh mục",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi xóa danh mục.")
 *         )
 *     )
 * )
 */


 public function destroy(string $id)
{
    $currentUser = auth('api')->user();
    if (!$currentUser) {
        return response()->json([
            'success' => false,
            'message' => 'Bạn chưa đăng nhập.',
        ], 401);
    }
    if (!$currentUser || !$currentUser->isAdmin()) {
        return response()->json([
            'success' => false,
            'message' => 'Bạn không phải admin.'
        ], 403);
    }

    try {
        // Bắt đầu transaction
        DB::beginTransaction();

        $model = Category::findOrFail($id);

        // Lấy hoặc tạo danh mục lưu trữ chung
        $archiveParentCategory = Category::firstOrCreate(
            ['slug' => 'danh-muc-luu-tru'],
            [
                'name' => 'Danh mục lưu trữ',
                'parent_id' => 0,
                'image' => null
            ]
        );

        // Lấy hoặc tạo danh mục con lưu trữ
        $archiveChildCategory = Category::firstOrCreate(
            ['slug' => 'san-pham-da-xoa'],
            [
                'name' => 'Sản phẩm đã xóa',
                'parent_id' => $archiveParentCategory->id,
                'image' => null
            ]
        );

        // Cập nhật category_id của tất cả sản phẩm trong danh mục bị xóa
        Product::where('category_id', $id)
            ->update(['category_id' => $archiveChildCategory->id]);

        // Cập nhật parent_id cho các danh mục con (nếu có)

        Category::where('parent_id', $id)
            ->update(['parent_id' => $archiveChildCategory->id]);

        // Xóa ảnh của danh mục cũ nếu có
        if ($model->image && Storage::exists($model->image)) {
            Storage::delete($model->image);
        }

        // Soft delete danh mục cũ
        $model->delete();

        // Commit transaction
        DB::commit();

        // Ghi log thành công
        Log::info("Danh mục '{$model->name}' với ID {$id} đã được xóa và sản phẩm đã được chuyển sang danh mục lưu trữ.");

        return response()->json(
            [
                'success' => true,
                'message' => "Xóa danh mục thành công. Các sản phẩm đã được chuyển sang danh mục lưu trữ",
                'archive_parent_id' => $archiveParentCategory->id,
                'archive_child_id' => $archiveChildCategory->id
            ],
            200
        );
    } catch (QueryException $e) {
        // Rollback transaction nếu có lỗi
        DB::rollBack();

        if ($e->errorInfo[1] == 1451) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Không thể xóa danh mục này vì nó có liên quan đến các bản ghi khác.',
                ],
                400
            );
        }

        Log::error("Lỗi khi xóa danh mục ID {$id}: " . $e->getMessage());

        return response()->json(
            [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa danh mục.',
            ],
            400
        );
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(
            [
                'success' => false,
                'message' => 'Danh mục không tồn tại.',
            ],
            404
        );
    }
}
}

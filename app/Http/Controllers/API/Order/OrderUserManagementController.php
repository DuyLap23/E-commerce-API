<?php

namespace App\Http\Controllers\API\Order;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderUserManagementController extends Controller
{
    /**
     * @OA\Get(
     *     tags={"Orders User Management"},
     *     path="/api/user/orders",
     *     security={{"Bearer": {}}},
     *     summary="Hiển thị danh sách đơn hàng của người dùng theo trạng thái",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Trạng thái đơn hàng (all, pending, shipping, delivered, cancelled, returned_refunded)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách đơn hàng của người dùng theo trạng thái."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Chưa đăng nhập."
     *     )
     * )
     */
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        $status = $request->query('status', 'all');
        $query = Order::where('user_id', Auth::id());

        // Lọc theo trạng thái đơn hàng
        switch ($status) {
            case 'pending':
                $query->where('order_status', 'pending'); // chờ thanh toán
                break;
            case 'shipping':
                $query->where('order_status', 'shipping'); // đang vận chuyển
                break;
            case 'delivered':
                $query->where('order_status', 'delivered'); // đã hoàn thành
                break;
            case 'cancelled':
                $query->where('order_status', 'cancelled'); // đã hủy
                break;
            case 'returned_refunded':
                $query->where('order_status', 'returned_refunded'); // trả hàng/hoàn tiền
                break;
            case 'all':
            default:
                break;
        }

        // Lấy đơn hàng cùng sản phẩm (bao gồm cả sản phẩm đã bị xóa mềm)
        $orders = $query->with([
            'orderItems.product' => function ($query) {
                $query->withTrashed();
            }
        ])->orderBy('created_at', 'desc')->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Không có đơn hàng nào'], 404);
        }

        // Gắn thêm thuộc tính `image_url` và `status_deleted` cho từng order
        $orders->map(function ($order) {
            // Lấy ảnh đầu tiên của sản phẩm từ danh sách orderItems
            $order->image_url = $order->orderItems->first()->product->img_thumbnail ?? null;

            // Duyệt qua các orderItems để gán status_deleted
            $order->orderItems->map(function ($item) {
                $item->status_deleted = $item->product && $item->product->trashed() ? 1 : 0;
                return $item;
            });

            return $order;
        });

        // Trả về JSON
        return response()->json([
            'message' => 'Số lượng đơn hàng: ' . $orders->count(),
            'orders' => $orders
        ]);
    }


    /**
     * @OA\Patch(
     *     path="/api/user/orders/{id}/cancel",
     *     summary="Hủy đơn hàng",
     *     tags={"Orders User Management"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng cần hủy",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="note", type="string", description="Lý do hủy đơn hàng")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đơn hàng đã được hủy."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Không thể hủy đơn hàng trong trạng thái này."
     *     )
     * )
     */
    public function cancelOrder($id, Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
        }
        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'Bạn không có quyền hủy đơn hàng này.'], 403);
        }
        if ($order->order_status !== 'pending' && $order->order_status !== 'processing') {
            return response()->json(['message' => 'Không thể hủy đơn hàng trong trạng thái này.'], 400);
        }
        $order->order_status = 'cancelled';
        $order->note = $request->input('note');
        $order->save();

        return response()->json(['message' => 'Đơn hàng đã được hủy.']);
    }

    /**
     * @OA\Patch(
     *     path="/api/user/orders/address",
     *     summary="Cập nhật địa chỉ giao hàng",
     *     tags={"Orders User Management"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="address_name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="phone_number", type="string", example="0123456789"),
     *             @OA\Property(property="city", type="string", example="TP.HCM"),
     *             @OA\Property(property="district", type="string", example="Quận 1"),
     *             @OA\Property(property="ward", type="string", example="Phường Bến Nghé"),
     *             @OA\Property(property="detail_address", type="string", example="123 Đường ABC"),
     *             @OA\Property(property="is_default", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Địa chỉ giao hàng đã được cập nhật."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy địa chỉ."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ."
     *     )
     * )
     */
    public function updateAddress(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }
        $userId = Auth::id();
        $address = Address::where('user_id', $userId)->first();

        if (!$address) {
            return response()->json(['message' => 'Không tìm thấy địa chỉ.'], 404);
        }
        $request->validate([
            'address_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'ward' => 'required|string|max:255',
            'detail_address' => 'required|string|max:255',
            'is_default' => 'boolean',
        ]);
        $address->address_name = $request->address_name;
        $address->phone_number = $request->phone_number;
        $address->city = $request->city;
        $address->district = $request->district;
        $address->ward = $request->ward;
        $address->detail_address = $request->detail_address;
        $address->is_default = $request->is_default ?? false;
        $address->save();

        return response()->json(['message' => 'Địa chỉ giao hàng đã được cập nhật.', 'address' => $address]);
    }

    /**
     * @OA\Patch(
     *     path="/api/user/orders/{id}/payment-method",
     *     summary="Thay đổi hình thức thanh toán",
     *     tags={"Orders User Management"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng cần thay đổi hình thức thanh toán",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_method", type="string", example="credit_card")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hình thức thanh toán đã được cập nhật."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ."
     *     )
     * )
     */
    public function updatePaymentMethod(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }
        $order = Order::find($id);

        if (!$order || $order->user_id !== Auth::id()) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
        }

        $request->validate([
            'payment_method' => 'required|in:credit_card,paypal,cash',
        ]);

        $order->payment_method = $request->payment_method;
        $order->save();

        return response()->json(['message' => 'Hình thức thanh toán đã được cập nhật.', 'order' => $order]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/orders/{id}",
     *     summary="Lấy thông tin chi tiết đơn hàng",
     *     tags={"Orders User Management"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng cần lấy chi tiết",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin chi tiết đơn hàng.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="order_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="total_amount", type="number"),
     *             @OA\Property(property="address", type="object", nullable=true,
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="address_name", type="string"),
     *                 @OA\Property(property="phone_number", type="string"),
     *                 @OA\Property(property="city", type="string"),
     *                 @OA\Property(property="district", type="string"),
     *                 @OA\Property(property="ward", type="string"),
     *                 @OA\Property(property="detail_address", type="string")
     *             ),
     *             @OA\Property(property="payment_method", type="string"),
     *             @OA\Property(property="payment_status", type="string"),
     *             @OA\Property(property="order_status", type="string"),
     *             @OA\Property(property="note", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="order_items", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="order_id", type="integer"),
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="quantity", type="integer"),
     *                     @OA\Property(property="price", type="number"),
     *                     @OA\Property(property="size", type="string"),
     *                     @OA\Property(property="color", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Chưa đăng nhập."
     *     )
     * )
     */
    public function show($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        $order = Order::with([
            'user',
            'address',
            'orderItems.product' => function ($query) {
                $query->withTrashed(); // Bao gồm cả sản phẩm đã xóa mềm
            }
        ])->find($id);

        if (!$order || $order->user_id !== Auth::id()) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
        }

        return response()->json([
            'order_id' => $order->id,
            'name' => $order->user ? $order->user->name : 'N/A',
            'email' => $order->user ? $order->user->email : 'N/A',
            'total_amount' => $order->total_amount,
            'address' => $order->address ? [
                'id' => $order->address->id,
                'address_name' => $order->address->address_name,
                'phone_number' => $order->address->phone_number,
                'city' => $order->address->city,
                'district' => $order->address->district,
                'ward' => $order->address->ward,
                'detail_address' => $order->address->detail_address,
            ] : 'N/A',
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'voucher_code' => $order->voucher_code !== null ? $order->voucher_code : ' ',
            'voucher_discount' => $order->voucher_discount !== null
                ? (int) $order->voucher_discount : 0,
            'note' => $order->note,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'order_items' => $order->orderItems->map(function ($item) {
                return [
                    'order_id' => $item->order_id,
                    'product_id' => $item->product_id,
                    'slug' => $item->product->slug,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'size' => $item->size,
                    'color' => $item->color,
                    'product_name' => $item->product ? $item->product->name : '',
                    'img_thumbnail' => $item->product ? $item->product->img_thumbnail : '',
                    'price_regular' => $item->product ? $item->product->price_regular : '',
                    'price_sale' => $item->product ? $item->product->price_sale : '',
                    'status_deleted' => $item->product && $item->product->trashed() ? 1 : 0, // Sử dụng `trashed()`
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            }),
        ]);
    }


    /**
     * @OA\Patch(
     *     tags={"Orders User Management"},
     *     path="/api/user/orders/mark-as-received/{id}",
     *     security={{"Bearer": {}}},
     *     summary="Đánh dấu đơn hàng là đã nhận",
     *     description="Cập nhật trạng thái đơn hàng sang 'received' nếu người dùng đã nhận hàng.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đơn hàng đã được đánh dấu là đã nhận.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn hàng đã được đánh dấu là đã nhận thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi khi không thể đánh dấu đơn hàng.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn hàng không thể được đánh dấu là đã nhận.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn hàng không tồn tại.")
     *         )
     *     )
     * )
     */
    public function markAsReceived($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        if ($order->order_status === 'delivered') {
            // Chỉ chuyển trạng thái sang "Hoàn thành" nếu đơn hàng chưa được đánh dấu
            if ($order->order_status !== 'completed') {
                $order->order_status = 'completed';
                $order->received_at = Carbon::now();
                $order->save();

                return response()->json(['message' => 'Đơn hàng đã được hoàn thành.'], 200);
            } else {
                return response()->json(['message' => 'Đơn hàng đã được đánh dấu là đã nhận.'], 400);
            }
        }

        return response()->json(['message' => 'Đơn hàng không thể được đánh dấu là đã nhận.'], 400);
    }
}

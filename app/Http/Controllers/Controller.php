<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Top deal shop dự án tốt nghiệp.",
 *         version="1.0.0",
 *         description="https://apitopdeal.shop/",
 *         @OA\Contact(
 *             name="Tên người liên hệ",
 *             email="email@example.com"
 *         )
 *     ),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="Bearer",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="JWT",
 *             description="Nhập token JWT trong định dạng 'Bearer {token}'"
 *         ),
 *         @OA\Schema(
 *             schema="User",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
 *             @OA\Property(property="email", type="string", example="email@example.com"),
 *             @OA\Property(property="avatar", type="string", example="path/to/avatar.png"),
 *             @OA\Property(property="link_fb", type="string", example="https://github.com/DuyLap23/datnBackEnd"),
 *             @OA\Property(property="link_tiktok", type="string", example="https://github.com/DuyLap23/datnBackEnd"),
 *             @OA\Property(property="role", type="string", example="customer")
 *         ),
 *         @OA\Schema(
 *             schema="Address",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="user_id", type="integer", example=1),
 *             @OA\Property(property="address_name", type="string", example="Số 1, Đường ABC"),
 *             @OA\Property(property="phone_number", type="string", example="0123456789"),
 *             @OA\Property(property="city", type="string", example="Hà Nội"),
 *             @OA\Property(property="district", type="string", example="Quận Hoàn Kiếm"),
 *             @OA\Property(property="ward", type="string", example="Phường Tràng Tiền"),
 *             @OA\Property(property="detail_address", type="string", example="Gần Hồ Gươm"),
 *             @OA\Property(property="is_default", type="boolean", example=true)
 *         ),
 *         @OA\Schema(
 *             schema="ResetPasswordRequest",
 *             type="object",
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
 *         )
 *     )
 * )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}

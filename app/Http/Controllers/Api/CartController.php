<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CartStoreRequest;
use App\Http\Requests\Api\CartUpdateRequest;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();
        if (! $authenticatedUser instanceof User) {
            return response()->json([
                'message' => 'Autentikasi tidak valid.',
            ], 401);
        }

        return response()->json([
            'message' => 'Data cart berhasil diambil.',
            'data' => $this->cartService->getCartSummary($authenticatedUser),
        ]);
    }

    public function store(CartStoreRequest $request): JsonResponse
    {
        $authenticatedUser = $request->user();
        if (! $authenticatedUser instanceof User) {
            return response()->json([
                'message' => 'Autentikasi tidak valid.',
            ], 401);
        }

        $validated = $request->validated();

        try {
            $cartSummary = $this->cartService->addItem(
                $authenticatedUser,
                (int) $validated['product_id'],
                (int) $validated['quantity'],
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validasi cart gagal.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Produk berhasil ditambahkan ke cart.',
            'data' => $cartSummary,
        ], 201);
    }

    public function update(CartUpdateRequest $request, int $productId): JsonResponse
    {
        $authenticatedUser = $request->user();
        if (! $authenticatedUser instanceof User) {
            return response()->json([
                'message' => 'Autentikasi tidak valid.',
            ], 401);
        }

        if ($productId < 1) {
            return response()->json([
                'message' => 'Validasi cart gagal.',
                'errors' => [
                    'product_id' => ['Parameter productId tidak valid.'],
                ],
            ], 422);
        }

        $validated = $request->validated();

        try {
            $cartSummary = $this->cartService->updateItem(
                $authenticatedUser,
                $productId,
                (int) $validated['quantity'],
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validasi cart gagal.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Jumlah item cart berhasil diperbarui.',
            'data' => $cartSummary,
        ]);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $authenticatedUser = $request->user();
        if (! $authenticatedUser instanceof User) {
            return response()->json([
                'message' => 'Autentikasi tidak valid.',
            ], 401);
        }

        if ($productId < 1) {
            return response()->json([
                'message' => 'Validasi cart gagal.',
                'errors' => [
                    'product_id' => ['Parameter productId tidak valid.'],
                ],
            ], 422);
        }

        $cartSummary = $this->cartService->removeItem($authenticatedUser, $productId);

        return response()->json([
            'message' => 'Produk berhasil dihapus dari cart.',
            'data' => $cartSummary,
        ]);
    }
}

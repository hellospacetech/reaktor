<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UserBankAccountRequest;
use App\Models\Bank;
use App\Models\UserBankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserBankAccountController extends Controller
{
    /**
     * Kullanıcının tüm banka hesaplarını listeler.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $accounts = $request->user()->bankAccounts()
            ->with('bank')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return JsonResource::collection($accounts);
    }

    /**
     * Yeni bir banka hesabı oluşturur.
     *
     * @param UserBankAccountRequest $request
     * @return JsonResponse
     */
    public function store(UserBankAccountRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        
        return DB::transaction(function () use ($data, $user) {
            // Eğer bu hesap varsayılan olarak işaretlendiyse, diğer varsayılan hesapları kaldır
            if (isset($data['is_default']) && $data['is_default']) {
                $user->bankAccounts()->update(['is_default' => false]);
            }
            
            $account = $user->bankAccounts()->create($data);
            
            return response()->json([
                'message' => 'Banka hesabı başarıyla oluşturuldu.',
                'data' => $account->load('bank'),
            ], 201);
        });
    }

    /**
     * Belirtilen banka hesabının detaylarını gösterir.
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $account = $request->user()->bankAccounts()->with('bank')->findOrFail($id);
        
        return response()->json([
            'data' => $account,
        ]);
    }

    /**
     * Belirtilen banka hesabını günceller.
     *
     * @param UserBankAccountRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UserBankAccountRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $account = $user->bankAccounts()->findOrFail($id);
        
        return DB::transaction(function () use ($data, $user, $account) {
            // Eğer bu hesap varsayılan olarak işaretlendiyse, diğer varsayılan hesapları kaldır
            if (isset($data['is_default']) && $data['is_default']) {
                $user->bankAccounts()->where('id', '!=', $account->id)->update(['is_default' => false]);
            }
            
            $account->update($data);
            
            return response()->json([
                'message' => 'Banka hesabı başarıyla güncellendi.',
                'data' => $account->fresh('bank'),
            ]);
        });
    }

    /**
     * Belirtilen banka hesabını siler.
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $account = $request->user()->bankAccounts()->findOrFail($id);
        
        // Varsayılan hesap siliniyorsa, başka bir hesabı varsayılan yap
        if ($account->is_default) {
            $newDefault = $request->user()->bankAccounts()
                ->where('id', '!=', $id)
                ->where('is_active', true)
                ->first();
                
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }
        
        $account->delete();
        
        return response()->json([
            'message' => 'Banka hesabı başarıyla silindi.',
        ]);
    }
    
    /**
     * Tüm bankaların listesini döndürür (select için).
     *
     * @return JsonResponse
     */
    public function banks(): JsonResponse
    {
        $banks = Bank::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'logo_path']);
            
        return response()->json([
            'data' => $banks,
        ]);
    }
}

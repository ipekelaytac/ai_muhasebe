<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Domain\Accounting\Enums\PartyType;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\PartyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    protected PartyService $partyService;
    
    public function __construct(PartyService $partyService)
    {
        $this->partyService = $partyService;
    }
    
    /**
     * List parties
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'type' => 'nullable|string|in:' . implode(',', PartyType::ALL),
            'is_active' => 'nullable|boolean',
            'search' => 'nullable|string|max:100',
            'has_balance' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:name,code,created_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        
        $parties = $this->partyService->listParties($validated);
        
        return response()->json([
            'success' => true,
            'data' => $parties,
        ]);
    }
    
    /**
     * Create party
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'type' => 'required|string|in:' . implode(',', PartyType::ALL),
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'tax_office' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'payment_terms_days' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $party = $this->partyService->createParty($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Cari hesap oluşturuldu.',
                'data' => $party,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get party details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $data = $this->partyService->getPartyWithBalance($id);
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cari hesap bulunamadı.',
            ], 404);
        }
    }
    
    /**
     * Update party
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $party = Party::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'tax_office' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'payment_terms_days' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        
        try {
            $party = $this->partyService->updateParty($party, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Cari hesap güncellendi.',
                'data' => $party,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Deactivate party
     */
    public function destroy(int $id): JsonResponse
    {
        $party = Party::findOrFail($id);
        
        try {
            $this->partyService->deactivateParty($party);
            
            return response()->json([
                'success' => true,
                'message' => 'Cari hesap pasif yapıldı.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

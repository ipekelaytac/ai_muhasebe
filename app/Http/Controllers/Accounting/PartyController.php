<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartyRequest;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PartyController extends Controller
{
    /**
     * Display a listing of parties
     */
    public function index(Request $request): JsonResponse
    {
        $query = Party::query()
            ->forCompany($request->get('company_id'))
            ->forBranch($request->get('branch_id'));

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $parties = $query->with(['company', 'branch'])->paginate($request->get('per_page', 15));

        return response()->json($parties);
    }

    /**
     * Store a newly created party
     */
    public function store(StorePartyRequest $request): JsonResponse
    {
        $party = Party::create($request->validated());

        return response()->json($party->load(['company', 'branch']), 201);
    }

    /**
     * Display the specified party
     */
    public function show(Party $party): JsonResponse
    {
        $party->load(['company', 'branch', 'documents', 'payments']);

        return response()->json($party);
    }

    /**
     * Update the specified party
     */
    public function update(StorePartyRequest $request, Party $party): JsonResponse
    {
        $party->update($request->validated());

        return response()->json($party->fresh()->load(['company', 'branch']));
    }

    /**
     * Remove the specified party
     */
    public function destroy(Party $party): JsonResponse
    {
        // Check if party has documents or payments
        if ($party->documents()->count() > 0 || $party->payments()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete party with existing documents or payments'
            ], 422);
        }

        $party->delete();

        return response()->json(['message' => 'Party deleted successfully']);
    }
}

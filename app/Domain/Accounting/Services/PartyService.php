<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\AuditLog;
use App\Domain\Accounting\Models\Party;
use Illuminate\Support\Facades\DB;

class PartyService
{
    /**
     * Create a new party
     */
    public function createParty(array $data): Party
    {
        return DB::transaction(function () use ($data) {
            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = Party::generateCode($data['company_id'], $data['type']);
            }
            
            $party = Party::create($data);
            
            AuditLog::log($party, 'create', null, $party->toArray());
            
            return $party;
        });
    }
    
    /**
     * Update a party
     */
    public function updateParty(Party $party, array $data): Party
    {
        return DB::transaction(function () use ($party, $data) {
            $oldValues = $party->toArray();
            
            $party->update($data);
            
            AuditLog::log($party, 'update', $oldValues, $party->fresh()->toArray());
            
            return $party->fresh();
        });
    }
    
    /**
     * Deactivate a party
     */
    public function deactivateParty(Party $party): Party
    {
        return DB::transaction(function () use ($party) {
            // Check if party has open documents
            $openDocuments = $party->documents()->open()->count();
            if ($openDocuments > 0) {
                throw new \Exception("Bu cari hesabın {$openDocuments} açık belgesi var. Önce belgeleri kapatın.");
            }
            
            $oldValues = $party->toArray();
            
            $party->update(['is_active' => false]);
            
            AuditLog::log($party, 'status_change', $oldValues, $party->toArray());
            
            return $party;
        });
    }
    
    /**
     * Get party with balance details
     */
    public function getPartyWithBalance(int $id): array
    {
        $party = Party::with(['linkable'])->findOrFail($id);
        
        return [
            'party' => $party,
            'receivable_balance' => $party->receivable_balance,
            'payable_balance' => $party->payable_balance,
            'net_balance' => $party->balance,
            'open_documents_count' => $party->documents()->open()->count(),
        ];
    }
    
    /**
     * List parties with filters
     */
    public function listParties(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Party::scoped($filters['company_id'], $filters['branch_id'] ?? null);
        
        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        } else {
            // Default to active only
            $query->active();
        }
        
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        
        if (!empty($filters['has_balance'])) {
            // This is expensive - consider caching balances
            $query->whereHas('documents', function ($q) {
                $q->open();
            });
        }
        
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);
        
        $perPage = $filters['per_page'] ?? 20;
        
        return $query->paginate($perPage);
    }
    
    /**
     * Create party from existing Customer model
     */
    public function createFromCustomer(\App\Models\Customer $customer, int $companyId): Party
    {
        $type = $customer->type === 'supplier' ? 'supplier' : 'customer';
        
        return $this->createParty([
            'company_id' => $companyId,
            'type' => $type,
            'linkable_type' => get_class($customer),
            'linkable_id' => $customer->id,
            'name' => $customer->name,
            'tax_number' => $customer->tax_number ?? null,
            'tax_office' => $customer->tax_office ?? null,
            'phone' => $customer->phone ?? null,
            'email' => $customer->email ?? null,
            'address' => $customer->address ?? null,
            'is_active' => true,
        ]);
    }
    
    /**
     * Create party from existing Employee model
     */
    public function createFromEmployee(\App\Models\Employee $employee, int $companyId): Party
    {
        return $this->createParty([
            'company_id' => $companyId,
            'branch_id' => $employee->branch_id ?? null,
            'type' => 'employee',
            'linkable_type' => get_class($employee),
            'linkable_id' => $employee->id,
            'name' => $employee->name,
            'phone' => $employee->phone ?? null,
            'is_active' => $employee->status === 'active',
        ]);
    }
    
    /**
     * Sync party with linked model
     */
    public function syncWithLinkedModel(Party $party): Party
    {
        if (!$party->linkable) {
            return $party;
        }
        
        $linked = $party->linkable;
        $updates = [];
        
        // Sync common fields
        if (isset($linked->name) && $linked->name !== $party->name) {
            $updates['name'] = $linked->name;
        }
        if (isset($linked->phone) && $linked->phone !== $party->phone) {
            $updates['phone'] = $linked->phone;
        }
        if (isset($linked->email) && $linked->email !== $party->email) {
            $updates['email'] = $linked->email;
        }
        
        if (!empty($updates)) {
            return $this->updateParty($party, $updates);
        }
        
        return $party;
    }
}

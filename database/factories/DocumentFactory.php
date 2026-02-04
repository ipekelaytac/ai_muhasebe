<?php

namespace Database\Factories;

use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-1 year', 'now');
        return [
            'company_id' => Company::factory(),
            'branch_id' => null,
            'document_number' => 'DOC-' . \Illuminate\Support\Str::random(8),
            'type' => DocumentType::EXPENSE_DUE,
            'direction' => 'payable',
            'party_id' => Party::factory(),
            'document_date' => $date,
            'due_date' => $date,
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'status' => DocumentStatus::PENDING,
            'period_year' => (int) $date->format('Y'),
            'period_month' => (int) $date->format('n'),
        ];
    }
}

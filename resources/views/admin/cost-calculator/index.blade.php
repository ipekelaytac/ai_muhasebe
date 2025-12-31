@extends('layouts.admin')

@section('title', 'Maliyet Hesaplayıcı')
@section('page-title', 'Maliyet Hesaplayıcı')
@section('page-subtitle', 'Ürün maliyetlerini hesaplayın')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-calculator me-2"></i>Maliyet Hesaplama Formu
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cost-calculator.calculate') }}" id="costCalculatorForm">
                    @csrf
                    <div id="itemsContainer">
                        <div class="item-row mb-3 p-3 border rounded" data-index="0">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Özellik Adı</label>
                                    <input type="text" name="items[0][name]" class="form-control" placeholder="Örn: Kumaş" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Birim</label>
                                    <select name="items[0][unit]" class="form-select unit-select" required>
                                        <option value="adet">Adet</option>
                                        <option value="metre">Metre</option>
                                        <option value="cm">CM</option>
                                        <option value="kg">KG</option>
                                        <option value="gram">Gram</option>
                                        <option value="litre">Litre</option>
                                        <option value="ml">ML</option>
                                        <option value="m2">M²</option>
                                        <option value="cm2">CM²</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Birim Fiyatı</label>
                                    <div class="input-group">
                                        <input type="number" name="items[0][unit_price]" class="form-control unit-price" step="0.01" min="0" placeholder="0.00" required>
                                        <span class="input-group-text">₺</span>
                                    </div>
                                    <small class="text-muted unit-label">1 adet için</small>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Kullanılan Miktar</label>
                                    <input type="number" name="items[0][quantity]" class="form-control quantity" step="0.01" min="0" placeholder="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Maliyet</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control item-cost" readonly value="0.00">
                                        <span class="input-group-text">₺</span>
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-sm w-100 remove-item" style="display: none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button type="button" class="btn btn-success" id="addItemBtn">
                            <i class="bi bi-plus-circle me-2"></i>Özellik Ekle
                        </button>
                        <div class="text-end">
                            <h5 class="mb-0">
                                <span class="text-muted">Toplam Maliyet: </span>
                                <span class="text-primary fw-bold" id="totalCost">0.00 ₺</span>
                            </h5>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-calculator me-2"></i>Hesapla
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" id="resetBtn">
                            <i class="bi bi-arrow-clockwise me-2"></i>Temizle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($items) && count($items) > 0)
        <div class="card border-0 shadow-sm" id="resultCard">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle me-2"></i>Hesaplama Sonucu
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Özellik Adı</th>
                                <th class="text-center">Birim</th>
                                <th class="text-end">Birim Fiyatı</th>
                                <th class="text-end">Kullanılan Miktar</th>
                                <th class="text-end">Maliyet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td class="fw-bold">{{ $item['name'] }}</td>
                                <td class="text-center">{{ ucfirst($item['unit']) }}</td>
                                <td class="text-end">{{ number_format($item['unit_price'], 2) }} ₺</td>
                                <td class="text-end">{{ number_format($item['quantity'], 2) }} {{ $item['unit'] }}</td>
                                <td class="text-end fw-bold text-primary">{{ number_format($item['cost'], 2) }} ₺</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">TOPLAM MALİYET</th>
                                <th class="text-end fs-5 fw-bold text-success">{{ number_format($total_cost, 2) }} ₺</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Hesaplama Detayları</h6>
                    @foreach($items as $item)
                    <p class="mb-2">
                        <strong>{{ $item['name'] }}:</strong> 
                        {{ number_format($item['unit_price'], 2) }} ₺/{{ $item['unit'] }} × 
                        {{ number_format($item['quantity'], 2) }} {{ $item['unit'] }} = 
                        <strong>{{ number_format($item['cost'], 2) }} ₺</strong>
                    </p>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = 1;
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');
    const resetBtn = document.getElementById('resetBtn');
    
    // Unit labels mapping
    const unitLabels = {
        'adet': '1 adet için',
        'metre': '1 metre için',
        'cm': '1 cm için',
        'kg': '1 kg için',
        'gram': '1 gram için',
        'litre': '1 litre için',
        'ml': '1 ml için',
        'm2': '1 m² için',
        'cm2': '1 cm² için'
    };
    
    // Add new item row
    addItemBtn.addEventListener('click', function() {
        const newRow = document.querySelector('.item-row').cloneNode(true);
        newRow.setAttribute('data-index', itemIndex);
        
        // Update input names
        newRow.querySelectorAll('input, select').forEach(function(input) {
            if (input.name) {
                input.name = input.name.replace(/\[0\]/, '[' + itemIndex + ']');
            }
            if (input.classList.contains('unit-price') || input.classList.contains('quantity')) {
                input.value = '';
            }
            if (input.classList.contains('item-cost')) {
                input.value = '0.00';
            }
        });
        
        // Show remove button
        newRow.querySelector('.remove-item').style.display = 'block';
        
        // Add event listeners
        attachEventListeners(newRow);
        
        itemsContainer.appendChild(newRow);
        itemIndex++;
        updateRemoveButtons();
    });
    
    // Remove item row
    itemsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            const row = e.target.closest('.item-row');
            row.remove();
            updateRemoveButtons();
            calculateTotal();
        }
    });
    
    // Update remove buttons visibility
    function updateRemoveButtons() {
        const rows = itemsContainer.querySelectorAll('.item-row');
        rows.forEach(function(row, index) {
            const removeBtn = row.querySelector('.remove-item');
            if (rows.length > 1) {
                removeBtn.style.display = 'block';
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }
    
    // Attach event listeners to a row
    function attachEventListeners(row) {
        const unitSelect = row.querySelector('.unit-select');
        const unitPrice = row.querySelector('.unit-price');
        const quantity = row.querySelector('.quantity');
        const unitLabel = row.querySelector('.unit-label');
        
        // Update unit label
        unitSelect.addEventListener('change', function() {
            unitLabel.textContent = unitLabels[this.value] || '1 birim için';
            calculateItemCost(row);
        });
        
        // Calculate on input change
        unitPrice.addEventListener('input', function() {
            calculateItemCost(row);
        });
        
        quantity.addEventListener('input', function() {
            calculateItemCost(row);
        });
    }
    
    // Calculate cost for a single item
    function calculateItemCost(row) {
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const costInput = row.querySelector('.item-cost');
        
        // Birim fiyatı zaten seçilen birim için girildiği için dönüşüm yapmıyoruz
        // Sadece çarpma yapıyoruz: birim fiyatı × miktar = maliyet
        const cost = unitPrice * quantity;
        costInput.value = cost.toFixed(2);
        
        calculateTotal();
    }
    
    // Calculate total cost
    function calculateTotal() {
        const costInputs = document.querySelectorAll('.item-cost');
        let total = 0;
        
        costInputs.forEach(function(input) {
            total += parseFloat(input.value) || 0;
        });
        
        document.getElementById('totalCost').textContent = total.toFixed(2) + ' ₺';
    }
    
    // Reset form
    resetBtn.addEventListener('click', function() {
        if (confirm('Formu temizlemek istediğinize emin misiniz?')) {
            itemsContainer.innerHTML = `
                <div class="item-row mb-3 p-3 border rounded" data-index="0">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Özellik Adı</label>
                            <input type="text" name="items[0][name]" class="form-control" placeholder="Örn: Kumaş" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Birim</label>
                            <select name="items[0][unit]" class="form-select unit-select" required>
                                <option value="adet">Adet</option>
                                <option value="metre">Metre</option>
                                <option value="cm">CM</option>
                                <option value="kg">KG</option>
                                <option value="gram">Gram</option>
                                <option value="litre">Litre</option>
                                <option value="ml">ML</option>
                                <option value="m2">M²</option>
                                <option value="cm2">CM²</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Birim Fiyatı</label>
                            <div class="input-group">
                                <input type="number" name="items[0][unit_price]" class="form-control unit-price" step="0.01" min="0" placeholder="0.00" required>
                                <span class="input-group-text">₺</span>
                            </div>
                            <small class="text-muted unit-label">1 adet için</small>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Kullanılan Miktar</label>
                            <input type="number" name="items[0][quantity]" class="form-control quantity" step="0.01" min="0" placeholder="0" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Maliyet</label>
                            <div class="input-group">
                                <input type="text" class="form-control item-cost" readonly value="0.00">
                                <span class="input-group-text">₺</span>
                            </div>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm w-100 remove-item" style="display: none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            itemIndex = 1;
            attachEventListeners(itemsContainer.querySelector('.item-row'));
            calculateTotal();
            
            // Hide result card if exists
            const resultCard = document.getElementById('resultCard');
            if (resultCard) {
                resultCard.style.display = 'none';
            }
        }
    });
    
    // Attach event listeners to initial row
    itemsContainer.querySelectorAll('.item-row').forEach(function(row) {
        attachEventListeners(row);
    });
    
    // Initial calculation
    calculateTotal();
});
</script>
@endsection


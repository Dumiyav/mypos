<?php
// Start output buffering as a backup
if (!ob_get_level()) {
    ob_start();
}

// Get menu items and tables
$menuItems = readJsonFile('menu.json');
$tables = readJsonFile('tables.json');

// Filter available tables
$availableTables = [];
foreach ($tables as $table) {
    if ($table['status'] === 'available') {
        $availableTables[] = $table;
    }
}

// Group menu items by category
$menuByCategory = [];
foreach ($menuItems as $item) {
    if ($item['available']) {
        if (!isset($menuByCategory[$item['category']])) {
            $menuByCategory[$item['category']] = [];
        }
        $menuByCategory[$item['category']][] = $item;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $tableId = $_POST['table_id'] ?? '';
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    $discount = floatval($_POST['discount'] ?? 0);
    
    // Validate input
    $errors = [];
    
    if (empty($tableId)) {
        $errors[] = 'Please select a table.';
    }
    
    if (empty($items)) {
        $errors[] = 'Please add at least one item to the order.';
    }
    
    // If no errors, create the order
    if (empty($errors)) {
        // Prepare order items
        $orderItems = [];
        for ($i = 0; $i < count($items); $i++) {
            if (isset($items[$i]) && isset($quantities[$i]) && $quantities[$i] > 0) {
                $orderItems[] = [
                    'menu_item_id' => $items[$i],
                    'quantity' => intval($quantities[$i]),
                    'notes' => '',
                    'status' => 'pending'
                ];
            }
        }
        
        // Calculate order total
        $totals = calculateOrderTotal($orderItems, $menuItems, $discount);
        
        // Create new order
        $newOrder = [
            'id' => generateId(),
            'table_id' => $tableId,
            'items' => $orderItems,
            'status' => 'active',
            'discount' => $discount,
            'tax' => $totals['tax'],
            'total' => $totals['total'],
            'payment_method' => '',
            'payment_status' => 'pending',
            'created_at' => date(DATE_FORMAT),
            'updated_at' => date(DATE_FORMAT)
        ];
        
        // Get existing orders
        $orders = readJsonFile('orders.json');
        
        // Add new order
        $orders[] = $newOrder;
        
        // Update table status
        foreach ($tables as &$table) {
            if ($table['id'] === $tableId) {
                $table['status'] = 'occupied';
                break;
            }
        }
        
        // Save changes
        $ordersSaved = writeJsonFile('orders.json', $orders);
        $tablesSaved = writeJsonFile('tables.json', $tables);
        
        if ($ordersSaved && $tablesSaved) {
            setFlashMessage('success', 'Order created successfully.');
            
            // Clean any output buffers before redirect
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Redirect to orders page
            header('Location: index.php?page=orders');
            exit;
        } else {
            $errors[] = 'Failed to save order.';
        }
    }
}
?>

<div class="container mx-auto">
    <div class="flex items-center mb-6">
        <a href="index.php?page=orders" class="mr-4">
            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <h1 class="text-2xl font-bold">Create New Order</h1>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (empty($availableTables)): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
        <p>No tables are currently available. Please wait for a table to become available or free up a table.</p>
    </div>
    <?php endif; ?>
    
    <?php if (empty($menuItems)): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
        <p>No menu items are available. Please add menu items before creating an order.</p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($availableTables) && !empty($menuItems)): ?>
    <!-- Order Creation Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <script>
            // Define the order management functionality
            document.addEventListener('DOMContentLoaded', function() {
                window.orderApp = {
                    items: [],
                    quantities: [],
                    discount: 0,
                    tableId: '',
                    menuItems: <?php echo json_encode($menuItems); ?>,
                    
                    addItem: function(itemId) {
                        const index = this.items.indexOf(itemId);
                        if (index === -1) {
                            this.items.push(itemId);
                            this.quantities.push(1);
                        } else {
                            this.quantities[index]++;
                        }
                        this.updateOrderItems();
                    },
                    
                    removeItem: function(index) {
                        this.items.splice(index, 1);
                        this.quantities.splice(index, 1);
                        this.updateOrderItems();
                    },
                    
                    updateQuantity: function(index, value) {
                        this.quantities[index] = parseInt(value);
                        if (this.quantities[index] <= 0) {
                            this.removeItem(index);
                        }
                        this.updateOrderItems();
                    },
                    
                    getItemName: function(itemId) {
                        for (const item of this.menuItems) {
                            if (item.id === itemId) {
                                return item.name;
                            }
                        }
                        return 'Unknown Item';
                    },
                    
                    getItemPrice: function(itemId) {
                        for (const item of this.menuItems) {
                            if (item.id === itemId) {
                                return parseFloat(item.price);
                            }
                        }
                        return 0;
                    },
                    
                    calculateSubtotal: function() {
                        let subtotal = 0;
                        for (let i = 0; i < this.items.length; i++) {
                            subtotal += this.getItemPrice(this.items[i]) * this.quantities[i];
                        }
                        return subtotal;
                    },
                    
                    calculateDiscount: function() {
                        return parseFloat(this.discount) || 0;
                    },
                    
                    calculateTax: function() {
                        const subtotal = this.calculateSubtotal();
                        const discount = this.calculateDiscount();
                        const discountedSubtotal = Math.max(0, subtotal - discount);
                        return discountedSubtotal * (<?php echo TAX_RATE; ?> / 100);
                    },
                    
                    calculateTotal: function() {
                        const subtotal = this.calculateSubtotal();
                        const discount = this.calculateDiscount();
                        const discountedSubtotal = Math.max(0, subtotal - discount);
                        const tax = this.calculateTax();
                        return discountedSubtotal + tax;
                    },
                    
                    formatCurrency: function(amount) {
                        return '<?php echo CURRENCY; ?>' + amount.toFixed(2);
                    },
                    
                    updateOrderItems: function() {
                        const orderItemsContainer = document.getElementById('orderItems');
                        const noItemsMessage = document.getElementById('noItemsMessage');
                        const orderItemsList = document.getElementById('orderItemsList');
                        const subtotalElement = document.getElementById('subtotal');
                        const taxElement = document.getElementById('tax');
                        const totalElement = document.getElementById('total');
                        const submitButton = document.getElementById('submitButton');
                        
                        // Update summary
                        subtotalElement.textContent = this.formatCurrency(this.calculateSubtotal());
                        taxElement.textContent = this.formatCurrency(this.calculateTax());
                        totalElement.textContent = this.formatCurrency(this.calculateTotal());
                        
                        // Update order items
                        if (this.items.length === 0) {
                            noItemsMessage.style.display = 'block';
                            orderItemsList.style.display = 'none';
                        } else {
                            noItemsMessage.style.display = 'none';
                            orderItemsList.style.display = 'block';
                            
                            // Clear existing items
                            orderItemsList.innerHTML = '';
                            
                            // Add each item
                            for (let i = 0; i < this.items.length; i++) {
                                const itemId = this.items[i];
                                const quantity = this.quantities[i];
                                const itemName = this.getItemName(itemId);
                                const itemPrice = this.getItemPrice(itemId);
                                const itemTotal = itemPrice * quantity;
                                
                                const itemElement = document.createElement('div');
                                itemElement.className = 'flex items-center justify-between mb-4';
                                itemElement.innerHTML = `
                                    <div>
                                        <div class="font-medium">${itemName}</div>
                                        <div class="text-sm text-gray-500">${this.formatCurrency(itemPrice)} × ${quantity} = ${this.formatCurrency(itemTotal)}</div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input type="hidden" name="items[${i}]" value="${itemId}">
                                        <input type="number" name="quantities[${i}]" value="${quantity}" min="1" class="w-16 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" onchange="window.orderApp.updateQuantity(${i}, this.value)">
                                        <button type="button" class="text-red-500 hover:text-red-700" onclick="window.orderApp.removeItem(${i})">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                `;
                                
                                orderItemsList.appendChild(itemElement);
                            }
                        }
                        
                        // Enable/disable submit button
                        submitButton.disabled = this.items.length === 0 || !this.tableId;
                    },
                    
                    selectTable: function(tableId) {
                        this.tableId = tableId;
                        
                        // Update visual selection
                        document.querySelectorAll('.table-option').forEach(el => {
                            if (el.getAttribute('data-table-id') === tableId) {
                                el.classList.add('bg-blue-50', 'border-blue-500');
                            } else {
                                el.classList.remove('bg-blue-50', 'border-blue-500');
                            }
                        });
                        
                        // Update hidden input
                        document.getElementById('selectedTableId').value = tableId;
                        
                        // Update submit button state
                        this.updateOrderItems();
                    },
                    
                    init: function() {
                        // Initialize discount input
                        const discountInput = document.getElementById('discount');
                        discountInput.addEventListener('input', (e) => {
                            this.discount = e.target.value;
                            this.updateOrderItems();
                        });
                        
                        // Initial update
                        this.updateOrderItems();
                    }
                };
                
                // Initialize the app
                window.orderApp.init();
            });
        </script>
        
        <form method="POST" action="">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Table Selection -->
                <div class="lg:col-span-1">
                    <h2 class="text-lg font-semibold mb-4">Select Table</h2>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 gap-4">
                        <?php foreach ($availableTables as $table): ?>
                        <div class="table-option border rounded-lg cursor-pointer hover:bg-gray-50" 
                             data-table-id="<?php echo $table['id']; ?>"
                             onclick="window.orderApp.selectTable('<?php echo $table['id']; ?>')">
                            <div class="flex items-center justify-center p-4">
                                <div class="text-center">
                                    <div class="font-medium"><?php echo $table['name']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $table['capacity']; ?> seats</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="selectedTableId" name="table_id" value="">
                    
                    <div class="mt-6">
                        <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span id="subtotal"><?php echo CURRENCY; ?>0.00</span>
                            </div>
                            
                            <div>
                                <label for="discount" class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm"><?php echo CURRENCY; ?></span>
                                    </div>
                                    <input type="number" id="discount" name="discount" value="0" min="0" step="0.01" class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            
                            <div class="flex justify-between">
                                <span>Tax (<?php echo TAX_RATE; ?>%):</span>
                                <span id="tax"><?php echo CURRENCY; ?>0.00</span>
                            </div>
                            
                            <div class="border-t pt-2 flex justify-between font-bold">
                                <span>Total:</span>
                                <span id="total"><?php echo CURRENCY; ?>0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Middle Column: Menu Items -->
                <div class="lg:col-span-1">
                    <h2 class="text-lg font-semibold mb-4">Menu Items</h2>
                    
                    <div class="space-y-6">
                        <?php foreach ($menuByCategory as $category => $items): ?>
                        <div>
                            <h3 class="text-md font-medium mb-2 capitalize"><?php echo $category; ?></h3>
                            
                            <div class="space-y-2">
                                <?php foreach ($items as $item): ?>
                                <div class="border rounded-lg p-3 hover:bg-gray-50 cursor-pointer" 
                                     onclick="window.orderApp.addItem('<?php echo $item['id']; ?>')">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium"><?php echo $item['name']; ?></h4>
                                            <p class="text-sm text-gray-500"><?php echo $item['description']; ?></p>
                                        </div>
                                        <span class="font-medium"><?php echo formatCurrency($item['price']); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Right Column: Order Items -->
                <div class="lg:col-span-1">
                    <h2 class="text-lg font-semibold mb-4">Order Items</h2>
                    
                    <div id="orderItems" class="border rounded-lg p-4 bg-gray-50 min-h-[300px]">
                        <div id="noItemsMessage" class="text-center py-8 text-gray-500">
                            <p>No items added to the order.</p>
                            <p class="text-sm">Click on menu items to add them to the order.</p>
                        </div>
                        
                        <div id="orderItemsList" class="space-y-4" style="display: none;">
                            <!-- Order items will be dynamically added here -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <a href="index.php?page=orders" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                    Cancel
                </a>
                <button id="submitButton" type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded" disabled>
                    Create Order
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
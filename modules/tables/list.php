<?php
// Get tables
$tables = readJsonFile('tables.json');

// Handle table status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $tableId = $_POST['table_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    // Validate input
    if (!empty($tableId) && !empty($status)) {
        // Update table status
        foreach ($tables as &$table) {
            if ($table['id'] === $tableId) {
                $table['status'] = $status;
                break;
            }
        }
        
        // Save changes
        if (writeJsonFile('tables.json', $tables)) {
            setFlashMessage('success', 'Table status updated successfully.');
        } else {
            setFlashMessage('error', 'Failed to update table status.');
        }
        
        // Redirect to refresh the page
        header('Location: index.php?page=tables');
        exit;
    }
}

// Get active orders for tables
$orders = readJsonFile('orders.json');
$activeOrders = [];

foreach ($orders as $order) {
    if ($order['status'] === 'active') {
        $activeOrders[$order['table_id']] = $order;
    }
}
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Tables</h1>
    </div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($tables as $table): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold"><?php echo $table['name']; ?></h3>
                    
                    <?php
                    $statusClass = '';
                    switch ($table['status']) {
                        case 'available':
                            $statusClass = 'bg-green-100 text-green-800';
                            break;
                        case 'occupied':
                            $statusClass = 'bg-red-100 text-red-800';
                            break;
                        case 'reserved':
                            $statusClass = 'bg-yellow-100 text-yellow-800';
                            break;
                    }
                    ?>
                    <span class="px-2 py-1 rounded-full text-xs <?php echo $statusClass; ?>">
                        <?php echo ucfirst($table['status']); ?>
                    </span>
                </div>
                
                <p class="text-gray-500 mb-4">Capacity: <?php echo $table['capacity']; ?> seats</p>
                
                <?php if (isset($activeOrders[$table['id']])): ?>
                <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-sm font-medium">Active Order: #<?php echo substr($activeOrders[$table['id']]['id'], -4); ?></p>
                    <p class="text-sm text-gray-500">
                        <?php echo count($activeOrders[$table['id']]['items']); ?> items | 
                        <?php echo formatCurrency($activeOrders[$table['id']]['total']); ?>
                    </p>
                    <a href="index.php?page=order_update&id=<?php echo $activeOrders[$table['id']]['id']; ?>" class="text-blue-500 hover:text-blue-700 text-sm">
                        View Order
                    </a>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                    
                    <div class="mb-4">
                        <label for="status_<?php echo $table['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Change Status</label>
                        <select id="status_<?php echo $table['id']; ?>" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" <?php echo isset($activeOrders[$table['id']]) ? 'disabled' : ''; ?>>
                            <option value="available" <?php echo $table['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="occupied" <?php echo $table['status'] === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                            <option value="reserved" <?php echo $table['status'] === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-between">
                        <?php if ($table['status'] === 'available'): ?>
                        <a href="index.php?page=order_create" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm">
                            New Order
                        </a>
                        <?php endif; ?>
                        
                        <button type="submit" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded text-sm ml-auto" <?php echo isset($activeOrders[$table['id']]) ? 'disabled' : ''; ?>>
                            Update
                        </button>
                    </div>
                    
                    <?php if (isset($activeOrders[$table['id']])): ?>
                    <p class="text-sm text-gray-500 mt-2">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Cannot change status while table has an active order.
                    </p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
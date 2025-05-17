<?php
// Get menu items
$menuItems = readJsonFile('menu.json');

// Sort menu items by category
usort($menuItems, function($a, $b) {
    return strcmp($a['category'], $b['category']);
});

// Get categories
$categories = [];
foreach ($menuItems as $item) {
    if (!in_array($item['category'], $categories)) {
        $categories[] = $item['category'];
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Find and remove the menu item
    $newMenuItems = [];
    foreach ($menuItems as $item) {
        if ($item['id'] !== $id) {
            $newMenuItems[] = $item;
        }
    }
    
    // Save updated menu items
    if (writeJsonFile('menu.json', $newMenuItems)) {
        setFlashMessage('success', 'Menu item deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete menu item.');
    }
    
    // Redirect to menu list
    header('Location: index.php?page=menu');
    exit;
}
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Menu Items</h1>
        <a href="index.php?page=menu_add" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            Add New Item
        </a>
    </div>
    
    <!-- Category Tabs -->
    <div class="mb-6" x-data="{ activeTab: '<?php echo !empty($categories) ? $categories[0] : ''; ?>' }">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <?php foreach ($categories as $index => $category): ?>
                <button
                    @click="activeTab = '<?php echo $category; ?>'"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === '<?php echo $category; ?>', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== '<?php echo $category; ?>' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                >
                    <?php echo ucfirst($category); ?>
                </button>
                <?php endforeach; ?>
            </nav>
        </div>
        
        <!-- Menu Items by Category -->
        <?php foreach ($categories as $category): ?>
        <div x-show="activeTab === '<?php echo $category; ?>'" class="mt-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($menuItems as $item): ?>
                <?php if ($item['category'] === $category): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold"><?php echo $item['name']; ?></h3>
                                <p class="text-gray-500 mt-1"><?php echo $item['description']; ?></p>
                            </div>
                            <span class="text-lg font-bold"><?php echo formatCurrency($item['price']); ?></span>
                        </div>
                        
                        <div class="mt-4 flex justify-between items-center">
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">
                                <?php echo ucfirst($item['category']); ?>
                            </span>
                            
                            <span class="px-2 py-1 rounded-full text-xs <?php echo $item['available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                            </span>
                        </div>
                        
                        <div class="mt-4 flex justify-end space-x-2">
                            <a href="index.php?page=menu_edit&id=<?php echo $item['id']; ?>" class="text-blue-500 hover:text-blue-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            <a href="index.php?page=menu&action=delete&id=<?php echo $item['id']; ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this item?')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
                
                <?php
                // Check if there are no items in this category
                $hasItems = false;
                foreach ($menuItems as $item) {
                    if ($item['category'] === $category) {
                        $hasItems = true;
                        break;
                    }
                }
                
                if (!$hasItems):
                ?>
                <div class="col-span-full text-center py-8 text-gray-500">
                    No items found in this category.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($categories)): ?>
        <div class="text-center py-8 text-gray-500">
            No menu items found. Add your first menu item to get started.
        </div>
        <?php endif; ?>
    </div>
</div>
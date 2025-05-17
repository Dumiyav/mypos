<?php
// Check if ID is provided
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Menu item ID is required.');
    header('Location: index.php?page=menu');
    exit;
}

$id = $_GET['id'];

// Get menu items
$menuItems = readJsonFile('menu.json');

// Find the menu item
$menuItem = null;
foreach ($menuItems as $item) {
    if ($item['id'] === $id) {
        $menuItem = $item;
        break;
    }
}

// If menu item not found
if (!$menuItem) {
    setFlashMessage('error', 'Menu item not found.');
    header('Location: index.php?page=menu');
    exit;
}

// Define categories
$categories = ['appetizer', 'main', 'dessert', 'beverage'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $category = $_POST['category'] ?? '';
    $available = isset($_POST['available']) ? true : false;
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required.';
    }
    
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero.';
    }
    
    if (empty($category)) {
        $errors[] = 'Category is required.';
    }
    
    // If no errors, update the menu item
    if (empty($errors)) {
        // Update menu item
        foreach ($menuItems as &$item) {
            if ($item['id'] === $id) {
                $item['name'] = $name;
                $item['description'] = $description;
                $item['price'] = $price;
                $item['category'] = $category;
                $item['available'] = $available;
                break;
            }
        }
        
        // Save updated menu items
        if (writeJsonFile('menu.json', $menuItems)) {
            setFlashMessage('success', 'Menu item updated successfully.');
            header('Location: index.php?page=menu');
            exit;
        } else {
            $errors[] = 'Failed to update menu item.';
        }
    }
}
?>

<div class="container mx-auto">
    <div class="flex items-center mb-6">
        <a href="index.php?page=menu" class="mr-4">
            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <h1 class="text-2xl font-bold">Edit Menu Item</h1>
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
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($menuItem['name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" required><?php echo htmlspecialchars($menuItem['description']); ?></textarea>
                    </div>
                    
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm"><?php echo CURRENCY; ?></span>
                            </div>
                            <input type="number" id="price" name="price" value="<?php echo $menuItem['price']; ?>" min="0" step="0.01" class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category; ?>" <?php echo ($menuItem['category'] === $category) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($category); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="available" name="available" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" <?php echo $menuItem['available'] ? 'checked' : ''; ?>>
                        <label for="available" class="ml-2 block text-sm text-gray-700">Available</label>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <a href="index.php?page=menu" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    Update Item
                </button>
            </div>
        </form>
    </div>
</div>
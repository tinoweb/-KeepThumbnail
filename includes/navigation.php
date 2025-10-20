<?php
// Navegação do sistema

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="bg-white shadow-sm border-b mb-8" style="background: var(--secondary-bg) !important; border-color: var(--border-color) !important;"
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <!-- Logo/Brand -->
            <div class="flex items-center">
                <a href="index.php" class="flex items-center text-xl font-bold text-gray-800">
                    <i class="fas fa-images text-blue-600 mr-2"></i>
                    KeepThumbnail
                </a>
            </div>
            
            <!-- Menu Principal -->
            <div class="flex items-center space-x-4">
                <a href="index.php" 
                   class="<?php echo $currentPage === 'index' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900'; ?> px-3 py-2 rounded-md text-sm font-medium transition duration-200">
                    <i class="fas fa-upload mr-1"></i> Upload
                </a>
                
                <a href="gallery.php" 
                   class="<?php echo $currentPage === 'gallery' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900'; ?> px-3 py-2 rounded-md text-sm font-medium transition duration-200">
                    <i class="fas fa-th-large mr-1"></i> Galeria
                </a>
                
                <!-- Dropdown de Ferramentas -->
                <div class="relative group">
                    <button class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium transition duration-200 flex items-center">
                        <i class="fas fa-tools mr-1"></i> Ferramentas
                        <i class="fas fa-chevron-down ml-1 text-xs"></i>
                    </button>
                    
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="py-1">
                            <a href="test.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-vial mr-2"></i> Testar Sistema
                            </a>
                            <a href="test_gemini.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-robot mr-2"></i> Testar IA Gemini
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <a href="install.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i> Instalação
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

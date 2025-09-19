<?php include 'conf/head.php'; ?>

        <div class="container">
            <!-- Hero Section -->
            <div class="hero-section">
                <h1 class="hero-title">View Tables</h1>
                <p class="hero-subtitle">Browse and manage your generated database tables</p>
            </div>
            
            <!-- Back Navigation -->
            <div style="margin-bottom: 30px;">
                <a href="index.php" class="action-btn" style="background: linear-gradient(135deg, #64748b, #475569);">
                    <i class="material-icons">arrow_back</i>
                    Back to Dashboard
                </a>
            </div>
            
            <!-- Tables Content -->
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">Generated Tables</h2>
                    <p class="content-subtitle">List of all generated database tables and their files</p>
                </div>
                <div class="content-body">
                    <?php
                    // Check if Panel directory exists and list generated folders
                    $panelPath = 'Panel/';
                    if (is_dir($panelPath)) {
                        $directories = array_filter(glob($panelPath . '*'), 'is_dir');
                        
                        if (count($directories) > 0) {
                            echo '<div class="action-cards">';
                            foreach ($directories as $dir) {
                                $folderName = basename($dir);
                                $indexFile = $dir . '/index.php';
                                $hasIndex = file_exists($indexFile);
                                
                                echo '<div class="action-card">';
                                echo '<div class="action-icon" style="background: linear-gradient(135deg, #10b981, #059669);">';
                                echo '<i class="material-icons">folder</i>';
                                echo '</div>';
                                echo '<h3 class="action-title">' . ucfirst(str_replace('_', ' ', $folderName)) . '</h3>';
                                echo '<p class="action-description">Generated files for ' . $folderName . ' table</p>';
                                
                                if ($hasIndex) {
                                    echo '<a href="' . $dir . '/index.php" class="action-btn" style="background: linear-gradient(135deg, #10b981, #059669);">';
                                    echo '<i class="material-icons">launch</i>';
                                    echo 'Open Panel';
                                    echo '</a>';
                                } else {
                                    echo '<div style="color: #f59e0b; font-size: 0.9rem; font-weight: 500;">';
                                    echo '<i class="material-icons" style="font-size: 18px; vertical-align: middle;">warning</i>';
                                    echo ' No index file found';
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div style="text-align: center; padding: 60px 20px; color: #718096;">';
                            echo '<i class="material-icons" style="font-size: 64px; margin-bottom: 20px; color: #cbd5e1;">folder_open</i>';
                            echo '<h3 style="margin: 0 0 12px 0; color: #64748b;">No Tables Generated Yet</h3>';
                            echo '<p style="margin: 0 0 30px 0;">Create your first table to see it listed here.</p>';
                            echo '<a href="index.php" class="action-btn">';
                            echo '<i class="material-icons">add</i>';
                            echo 'Create First Table';
                            echo '</a>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div style="text-align: center; padding: 60px 20px; color: #718096;">';
                        echo '<i class="material-icons" style="font-size: 64px; margin-bottom: 20px; color: #cbd5e1;">error_outline</i>';
                        echo '<h3 style="margin: 0 0 12px 0; color: #64748b;">Panel Directory Not Found</h3>';
                        echo '<p style="margin: 0;">The Panel directory does not exist. Generate a table first.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>

<?php include 'conf/foot.php'; ?>
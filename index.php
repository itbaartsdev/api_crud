        <div class="container">
            <!-- Simple Two-Card Layout -->
            <div class="simple-cards">
                <!-- Generator Tambah/Ubah Card with Form Inputs -->
                <div class="simple-card form-card">
                    <div class="card-header">
                        <h2 class="card-title">generator<br>tambah/ubah</h2>
                    </div>
                    <div class="form-inputs">
                        <div class="empty-state">
                            <i class="material-icons" style="font-size: 48px; color: #ccc; margin-bottom: 16px;">add_circle_outline</i>
                            <p style="color: #666; margin: 0 0 20px 0; font-size: 16px;">Click "Tambah" button to create a new table</p>
                            <p style="color: #999; margin: 0; font-size: 14px;">Or click "Edit" on existing tables to modify them</p>
                        </div>
                    </div>
                </div>
                
                <!-- Tabel Generator Card with Database Tables -->
                <div class="simple-card table-card">
                    <div class="card-header">
                        <h2 class="card-title">tabel generator</h2>
                        <button class="tambah-btn" onclick="showAddForm()" style="background: #4CAF50; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-top: 10px; display: flex; align-items: center; gap: 6px; font-size: 14px;">
                            <i class="material-icons" style="font-size: 16px;">add</i>
                            Tambah
                        </button>
                    </div>
                    <div class="table-content">
                        <?php
                        // Helper function to convert table name to display name
                        function tableNameToDisplayName($tableName) {
                            // Convert underscore to space and capitalize each word
                            return ucwords(str_replace('_', ' ', $tableName));
                        }
                        
                        // Display database tables with edit/delete functionality
                        // Pastikan menggunakan koneksi local
                        if (file_exists('conf/koneksi.php')) {
                            include 'conf/koneksi.php';
                        } else if (file_exists('../conf/koneksi.php')) {
                            include '../conf/koneksi.php';
                        } else {
                            include '../../conf/koneksi.php';
                        }
                        $query = "SHOW TABLES";
                        $result = mysqli_query($koneksi, $query);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            echo '<div class="table-list">';
                            while ($row = mysqli_fetch_array($result)) {
                                $tableName = $row[0];
                                // Skip system tables
                                if (in_array($tableName, ['user', 'setting'])) continue;
                                
                                // Convert table name to display name
                                $displayName = tableNameToDisplayName($tableName);
                                
                                echo '<div class="table-item">';
                                echo '<div class="table-info">';
                                echo '<i class="material-icons">storage</i>';
                                echo '<span class="table-name">' . $displayName . '</span>';
                                echo '</div>';
                                echo '<div class="table-actions">';
                                echo '<button class="action-btn edit-btn" onclick="editTable(\'' . $tableName . '\')" title="Edit Table">';
                                echo '<i class="material-icons">edit</i>';
                                echo '</button>';
                                echo '<button class="action-btn delete-btn" onclick="deleteTable(\'' . $tableName . '\')" title="Delete Table">';
                                echo '<i class="material-icons">delete</i>';
                                echo '</button>';
                                echo '<a href="Panel/index.php?page=' . $displayName . '" class="action-btn view-btn" title="View Panel" target="_blank">';
                                echo '<i class="material-icons">launch</i>';
                                echo '</a>';
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="no-tables">';
                            echo '<i class="material-icons">info</i>';
                            echo '<p>No tables found</p>';
                            echo '<div class="create-hint">';
                            echo '<small>Click the "Tambah" button above to create your first table</small>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
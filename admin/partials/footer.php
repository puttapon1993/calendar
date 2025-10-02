<?php
// File: footer.php
// Location: /admin/partials/
?>
            </div> <!-- end .content-area -->
        </main> <!-- end .main-content -->
    </div> <!-- end .admin-wrapper -->

    <footer style="text-align: center; padding: 1.5rem; font-size: 0.9em; color: #7f8c8d; background-color: #ecf0f1;">
        <p style="margin: 0;">© พัฒนาเว็บไซต์โดย ครูพุทธพล ภาคสุวรรณ์</p>
    </footer>

    <!-- Generic Alert Modal -->
    <div id="alert-modal" class="modal-overlay hidden" style="z-index: 1070;">
        <div class="modal-content">
            <button type="button" class="modal-close-btn">&times;</button>
            <h3 id="alert-modal-title">แจ้งเตือน</h3>
            <p id="alert-modal-body"></p>
            <div class="form-actions" style="text-align: right;">
                <button type="button" id="alert-modal-ok-btn" class="button">ตกลง</button>
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="modal-overlay hidden" style="z-index: 1070;">
         <div class="modal-content">
            <h3 id="confirmation-modal-title">ยืนยันการบันทึก</h3>
            <div id="confirmation-modal-body"></div>
            <div class="form-actions" style="text-align: right;">
                <button type="button" id="cancel-save-btn" class="button button-secondary">เปลี่ยนวัน</button>
                <button type="button" id="confirm-save-btn" class="button">ยืนยัน</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebar-toggle');
        const body = document.body;
        const SIDEBAR_STATE_KEY = 'calendar_sidebar_collapsed';

        if (localStorage.getItem(SIDEBAR_STATE_KEY) === 'true') {
            body.classList.add('sidebar-collapsed');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem(SIDEBAR_STATE_KEY, body.classList.contains('sidebar-collapsed'));
            });
        }
        
        function setupModalCloseListeners(modal) {
            if (!modal) return;
            const closeButtons = modal.querySelectorAll('.modal-close-btn, #alert-modal-ok-btn');
            closeButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    modal.classList.add('hidden');
                });
            });
        }

        const alertModal = document.getElementById('alert-modal');
        setupModalCloseListeners(alertModal);

        function showAlert(message, title = 'แจ้งเตือน') {
            if (alertModal) {
                document.getElementById('alert-modal-title').textContent = title;
                document.getElementById('alert-modal-body').textContent = message;
                alertModal.classList.remove('hidden');
            } else {
                alert(message); // Fallback
            }
        }

        window.showAlert = showAlert;
    });
    </script>
</body>
</html>


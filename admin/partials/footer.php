            </div> <!-- end .content-area -->
        </main> <!-- end .main-content -->
    </div> <!-- end .admin-wrapper -->

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebar-toggle');
        const body = document.body;
        const SIDEBAR_STATE_KEY = 'calendar_sidebar_collapsed';

        // Apply saved state on page load
        if (localStorage.getItem(SIDEBAR_STATE_KEY) === 'true') {
            body.classList.add('sidebar-collapsed');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                body.classList.toggle('sidebar-collapsed');
                // Save the state to localStorage
                localStorage.setItem(SIDEBAR_STATE_KEY, body.classList.contains('sidebar-collapsed'));
            });
        }
    });
    </script>
</body>
</html>


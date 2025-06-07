    </div> <!-- End of main container -->
    
    <footer class="bg-gray-800 text-white py-6 mt-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; <?php echo date('Y'); ?> Smart Intern Management. All rights reserved.</p>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Built and Design by BSCS - C2022</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Mobile menu JavaScript -->
    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');
        
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
        
        // Alert message auto-close
        const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
        alerts.forEach((alert) => {
            const closeButton = alert.querySelector('svg');
            if (closeButton) {
                closeButton.addEventListener('click', () => {
                    alert.style.display = 'none';
                });
            }
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        });
    </script>
</body>
</html>

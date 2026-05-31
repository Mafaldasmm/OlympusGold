    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2026 Olympus Gold Gym. Todos os direitos reservados.</p>
            <p>Localização: Porto, Portugal | Contacto: info@oggym.pt</p>
        </div>
    </footer>

<?php require INCLUDES_PATH . '/confirm-dialog.php'; ?>

    <script src="<?= e(asset('js/toast.js')) ?>"></script>
    <script src="<?= e(asset('js/csrf-sync.js')) ?>"></script>
    <script src="<?= e(asset('js/confirm-dialog.js')) ?>"></script>
    <script src="<?= e(asset('js/mobile-collapsible.js')) ?>"></script>
<?php foreach ($extraScripts ?? [] as $script): ?>
    <script src="<?= e(asset('js/' . $script)) ?>"></script>
<?php endforeach; ?>
    <script>
        const hamburger = document.getElementById('hamburger');
        const navLinks = document.getElementById('nav-links');

        if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            const isOpen = navLinks.classList.toggle('open');
            hamburger.classList.toggle('active');
            hamburger.setAttribute('aria-expanded', isOpen);
        });

        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', false);
            });
        });
        }
    </script>

</body>
</html>

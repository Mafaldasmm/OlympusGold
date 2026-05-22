    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2026 Olympus Gold Gym. Todos os direitos reservados.</p>
            <p>Localização: Porto, Portugal | Contacto: info@oggym.pt</p>
        </div>
    </footer>

    <script>
        const hamburger = document.getElementById('hamburger');
        const navLinks = document.getElementById('nav-links');

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
    </script>

</body>
</html>

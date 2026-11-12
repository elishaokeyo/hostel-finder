</main>
<?php $wa = preg_match('/^\d{8,15}$/', ADMIN_WHATSAPP) ? ADMIN_WHATSAPP : ''; ?>
<footer class="simple-footer">
    <p>&copy; <?= date('Y') ?> Hostel Finder &middot; Designed and developed by <strong>ELISHA OKEYO</strong></p>
    <p>
        <a href="../index.php">Public site</a>
        <?php if ($wa): ?>&middot; <a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener">Contact us on WhatsApp</a><?php endif; ?>
    &middot; Map data &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors
    </p>
</footer>
</body>

</html>
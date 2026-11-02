<?php

declare(strict_types=1);

/**
 * includes/footer.php
 * Penutup halaman. Pasangannya adalah includes/header.php.
 */
?>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= e(date('Y')) ?> <?= e(config('app_name')) ?>. Deployed on Azure, Seluruh hak cipta dilindungi.</p>
    </div>
</footer>
</body>
</html>

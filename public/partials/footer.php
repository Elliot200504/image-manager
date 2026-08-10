<?php declare(strict_types=1); ?>
<footer class="site-footer">
    <span><?= e(APP_NAME) ?></span>
</footer>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.js"></script>
<script>
    // Exposed for the JS layer so every state-changing fetch can carry a token.
    window.CSRF_TOKEN = <?= json_encode(Csrf::token(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="assets/js/app.js"></script>
<?php if (!empty($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="assets/js/<?= e($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>

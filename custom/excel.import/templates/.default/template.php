
<style>
    .success { color: green; margin: 10px 0; }
    .error { color: red; margin: 10px 0; }
</style>
<div class="import-form">


    <?php if ($arResult['SUCCESS']): ?>
        <div class="success">Импорт завершён успешно.</div>
    <?php endif; ?>

    <?php foreach ($arResult['ERRORS'] as $err): ?>
        <div class="error"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
    
    <h2>Импорт товаров из Excel</h2>
    <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="post" enctype="multipart/form-data">
        <label>
            <br>Выберите файл Excel (.xls или .xlsx):<br><br>
            <input type="file" name="excel_file" accept=".xls,.xlsx" required>
        </label>
        <br><br>
        <button type="submit">Загрузить и импортировать</button>
    </form>
</div>

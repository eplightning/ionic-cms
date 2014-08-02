<div class="wrexbox">
    <h1>Dodawanie administratora</h1>
    <?php if($message): ?>
    <div class="notice"><?php echo $message ?></div>
    <?php endif; ?>
    <form action="index.php" method="post">
        <div class="group">
            <div class="title">Dane</div>
            <div class="content">
            <div>
                <label for="username">Login:</label>
                <input type="text" name="username" id="username" maxlength="20" />
            </div>
            <div>
                <label for="email">Adres e-mail:</label>
                <input type="text" name="email" id="email" maxlength="70" />
            </div>
            <div>
                <label for="password">Hasło:</label>
                <input type="password" name="password" id="password" />
            </div>
            <div>
                <label for="password2">Potwierdź hasło:</label>
                <input type="password" name="password2" id="password2" />
            </div>
            <div>
                <input type="submit" name="submit" id="submit" value="Wyślij" />
            </div>
            </div>
        </div>
    </form>
</div>

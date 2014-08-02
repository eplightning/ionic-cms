<div class="wrexbox">
    <h1>Konfiguracja systemu</h1>
    <?php if($message): ?>
    <div class="notice"><?php echo $message ?></div>
    <?php endif; ?>
    <form action="index.php" method="post">
    <div class="group">
        <div class="title">
            Konfiguracja
        </div>
        <div class="content">
            <div>
                <label for="host">Host serwera MySQL:<br /><small>Zwykle localhost</small></label>
                <input type="text" name="host" id="host" value="localhost" />
            </div>
            <div>
                <label for="user">Użytkownik MySQL:</label>
                <input type="text" name="user" id="user" value="" />
            </div>
            <div>
                <label for="password">Hasło MySQL:</label>
                <input type="text" name="password" id="password" value="" />
            </div>
            <div>
                <label for="database">Baza danych MySQL:</label>
                <input type="text" name="database" id="database" value="" />
            </div>
            <div>
                <label for="prefix">Prefix bazy danych:</label>
                <input type="text" name="prefix" id="prefix" value="ionic_" />
            </div>
            <div>
                <label for="persistent">Stałe połączenie z bazą:<br /><small>Cache'uje połączenia z bazą danych, nie włączaj jeśli nie jesteś pewien ,że nie sprawi to na twoim serwerze problemu.</small></label>
                <input type="checkbox" name="persistent" id="persistent" value="1" />
            </div>
            <div>
                <input type="submit" name="submit" id="submit" value="Wyślij" />
            </div>
        </div>
    </div>
    </form>
</div>

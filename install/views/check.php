<div class="wrexbox">
    <h1>Sprawdzanie przedinstalacyjne</h1>
    <div class="group">
        <div class="title">
            Serwer
        </div>
        <div class="content">
            <div>
                <label>Wersja PHP:</label>
                <?php echo $server['php'] ?>
                <div class="msg">
                    Wymagana przynajmniej wersja 5.3
                </div>
            </div>
            <div>
                <label>Wsparcie UTF-8 PCRE:</label>
                <?php echo $server['pcre'] ?>
            </div>
            <div>
                <label>mbstring:</label>
                <?php echo $server['mbstring'] ?>
            </div>
            <div>
                <label>Rozszerzenie PHP - fileinfo:</label>
                <?php echo $server['fileinfo'] ?>
            </div>
            <div>
                <label>MySQL:</label>
                <?php echo $server['mysql'] ?>
                <div class="msg">PDO</div>
            </div>
            <div>
                <label>register_globals:</label>
                <?php echo $server['globals'] ?>
                <div class="msg">
                    Zaleca się wyłączenie
                </div>
            </div>
        </div>
    </div>
    <div class="group">
        <div class="title">
            CHMOD
        </div>
        <div class="content">
            <?php foreach($files as $f): ?>
            <div>
                <label><?php echo $f['file'] ?></label>
                <?php if($f['writeable']): ?><span style="color: green">Tak</span>
                <?php else: ?>
                <span style="color: red">Nie</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <p style="text-align: right">
        <?php if($success): ?>
            <a href="index.php?nextstep=1" class="button">Dalej</a>
        <?php endif; ?>
    </p>
</div>

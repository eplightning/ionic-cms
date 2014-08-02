<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <link rel="stylesheet" type="text/css" href="style.css" />
        <title>Instalator IonicCMS</title>
    </head>
    <body>
        <div id="top">
            <div id="logo">
                IonicCMS<br />
                <small>Instalator</small>
            </div>
        </div>
        <div id="page-container">
            <div id="left-side">
                <?php echo $content ?>
            </div>
            <div id="right-side">
                <div class="rightbox">
                    <h1>Postęp</h1>
                    <ul class="icon-list2">
                        <?php $i = 1; foreach($steps as $name): ?>
                        <?php if($i == $currentStep): ?>
                        <li><strong><?php echo $name ?></strong></li>
                        <?php elseif($i > $currentStep): ?>
                        <li style="color: #999"><?php echo $name ?></li>
                        <?php else: ?>
                        <li><?php echo $name ?></li>
                        <?php endif; ?>
                        <?php $i++; endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </body>
</html>
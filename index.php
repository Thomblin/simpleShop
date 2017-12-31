<?php
include('autoload.php');

$config = new Config();
$db = new Db($config);
$items = new Items($db);

Translation::init($config);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo t('site.title') ?></title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <script src="http://code.jquery.com/jquery-1.4.2.min.js"></script>
    <script type="text/javascript">

        function preview() {
            $.post('ajax.php', $('#ajax_form').serialize(), function (response) {
                if (response.error) {
                    alert(response.error);
                }
                else {
                    $('#mail_text').html('<pre>' + response.mail + '</pre>');
                    $('#main').hide();
                    $('#mail').show();

                    if(response.order) {
                        $('#order_hint').hide();
                        $('#order').show();
                    } else {
                        $('#order').hide();
                        $('#order_hint').show();
                    }
                }
            }, 'json');
        }

        function back() {
            $('#mail').hide();
            $('#main').show();
        }

        function send() {
            $.post('ajax.php?mail=1', $('#ajax_form').serialize(), function (response) {
                if (response.error) {
                    alert(response.error);
                }
                else {
                    $('#mail').hide();
                    $('#mail').hide();
                    $('#confirm').show();
                }
            }, 'json');
        }

        $("body").delegate(".price", "change", function () {
            $.post('ajax.php?price_only=1', $('#ajax_form').serialize(), function (response) {
                $('#porto').html(response.porto);
                $('#total').html(response.price);
            }, 'json');
        });

    </script>
    <style type="text/css">
        body {
            background-color: #fff;
            background-image: url();
            background-repeat: repeat-y;
            font-family: georgia, times new roman, times, serif;
        }

        div.main {
            width: 600px;
            margin-left: 200px;
            margin-top: 75px;
            margin-bottom: 75px;
        }

        span.head {
            font-weight: bold;
        }

        div.item {
            border: 2px #880000 solid;
            margin: 5px 5px;
            padding: 5px 5px;
            background: url() #ddd;
        }

        span.description {
            font-style: italic;
        }

        div.picture {
            float: right;
        }

        div.footer, div.footer a, div.footer a:hover, div.footer a:visited {
            position: relative;
            bottom: 0px;
            border-top: 1px solid #ccc;
            font-size: 0.9em;
            font-style: italic;
            color: #ccc;
        }

        div.intro {
            position: relative;
            bottom: 10px;
            font-size: 1.2em;
            color: #ccc;
        }
    </style>
</head>
<body>
<form id="ajax_form">
    <div align="center"><a href="../index.php"><img src="" height="162" alt="<?php echo t('site.title') ?>"/></a>
    </div>
    <br>

    <div class="main" id="main">
        <div class="intro"><img src="" align="left"><strong><?php echo t('site.welcome') ?></strong>
        </div>
        <div class="item">
            <span class="head"><?php echo t('site.contact') ?></span><br/>

            <label><?php echo t('name') ?>:<br/><input type="text" name="name"/></label>
            <br/>
            <label><?php echo t('email') ?>*:<br/><input type="text" name="email"/></label>
            <br/>
            <label><?php echo t('street') ?>*:<br/><input type="text" name="street"/></label>
            <br/>
            <label><?php echo t('zip_city') ?>*:<br/><input type="text" name="zipcode_location"/></label>
        </div>
        <?php foreach ($items->getItems() as $item): ?>
        <div class="item">
            <div>
                <br/>
                <span class="head"><?php echo $item['name'] ?></span>
            </div>

            <div style="float:left">
                <?php foreach ($item['bundles'] as $bundle): ?>
                <?php $selected = ' selected="selected"' ?>
                <label><?php echo $bundle['name'] ?>:<br/>
                    <select name="<?php echo $item['item_id'] . '[' . $bundle['bundle_id'] . ']' ?>" class="price">
                        <?php for ($i = $bundle['min_count']; $i <= min($bundle['max_count'], $bundle['inventory']); ++$i): ?>
                        <option value="<?php echo $i ?>"<?php echo $selected ?>><?php echo $i ?> <?php echo t('times') ?>
                            (<?php echo number_format($i * $bundle['price'], 2, ',', '.') . ' ' . Config::CURRENCY ?> )
                        </option>
                        <?php $selected = '' ?>
                        <?php endfor; ?>
                    </select>
                </label>
                <br/>
                <?php endforeach; ?>
                <br/>
                <?php echo t('porto') ?> (<?php echo t('min') ?> <?php echo number_format($item['min_porto'], 2, ',', '.') . ' ' . Config::CURRENCY ?> )
            </div>

            <?php if (!empty($item['picture'])): ?>
            <div class="picture"><img src="<?php echo $item['picture'] ?>" alt="<?php echo $item['name'] ?>"
                                      title="<?php echo $item['name'] ?>"/></div>
            <?php endif; ?>
            <div style="clear:both"></div>

            <div>
                <br/>
                <span class="description"><?php echo $item['description'] ?></span>
            </div>

        </div>
        <?php endforeach; ?>
        <div class="item">
            <span class="head"><?php echo t('form.comments') ?></span><br/>

            <textarea type="text" name="comment"/></textarea>
            <br/>
            <input type="checkbox" name="collectionByTheCustomer" class="price"/> <?php echo t('form.will_collect_no_porto') ?>
        </div>
        <div class="item">
            <span class="head"><?php echo t('total') ?></span><br/>

            <?php echo t('porto') ?>: <span id="porto">0 <?php echo Config::CURRENCY; ?></span>
            <?php echo t('sum') ?>: <span id="total">0 <?php echo Config::CURRENCY; ?></span>
            <br/>
            <br/>
            <a href="javascript:;" onclick="preview();"><?php echo t('preview') ?></a>
            <br/>
        </div>
    </div>
    <div class="main" id="mail" style="display:none">
        <div class="item" id="mail_text"></div>

        <div class="item">
            <div>
                <a href="javascript:;" onclick="back();"><?php echo t('back') ?></a>

                <a href="javascript:;" onclick="send();" id="order" style="margin-left: 50px;"><?php echo t('order') ?></a>
            </div>
            <div id="order_hint" style="padding-top: 5px;">
                <?php echo t('change_selection') ?>
            </div>
        </div>
    </div>
    <div class="main" id="confirm" style="display:none">
        <div class="item">
            <?php echo t('form.success_message') ?>

        </div>
    </div>
    <div class="footer">
        <a href="" target="_top"><?php echo t('site.hints_imprint') ?></a> &middot; <?php echo t('form.developed_by') ?> <a href="http://sebastian-detert.de">Seeb</a> &middot; <?php echo t('form.designed_by') ?> Wozilla
    </div>
</form>
</body>
</html>

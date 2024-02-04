<!doctype html>
<html lang="<?= $this->getValue('page.language', $page ?? null) ?>" data-bs-theme="<?= $this->getValue('theme.colormode', $theme ?? null) ? $this->getValue('theme.colormode', $theme ?? null) : 'auto' ?>">

<head>
    <?php if($this->getValue('theme.colormodee', $theme ?? null)): ?>
    <script src="<?= $this->getValue('asset', $asset ?? null) ?>/coloor-mode.js"></script>
    <?php endif; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Qwwwest">
    <meta name="generator" content="Namaskar">
    <title><?= $this->getValue('page.title', $page ?? null) ?> :: <?= $this->getValue('site.name', $site ?? null) ?></title>


    <?php if($this->getValue('theme.bootswatch', $theme ?? null)): ?>
    <link href="<?= $this->getValue('asset', $asset ?? null) ?>/vendor/bootswatch/<?= $this->getValue('theme.bootswatch', $theme ?? null) ?>/bootstrap.min.css" rel="stylesheet">
    <?php else: ?>
    <link href="<?= $this->getValue('asset', $asset ?? null) ?>/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <?php endif; ?>
    <link href="<?= $this->getValue('asset', $asset ?? null) ?>/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $this->getValue('asset', $asset ?? null) ?>/namaskar.css" rel="stylesheet">
    <link href="<?= $this->getValue('asset', $asset ?? null) ?>/styles.css" rel="stylesheet">



</head>

<body class="navbar-<?= $this->getValue('theme.navbar.position', $theme ?? null) ?? 'normal'  ?> 
<?= $this->getValue('page.show.regions', $page ?? null) ? 'show-regions': '' ?>">
    

    [region header.top ]
    
<!-- <?= $this->theme."/"."navbar.html" ?> -->
<?php foreach(N('folder.templates') as $key => $folder) : ?>
    <?php if(is_file("$folder".'/'."$this->theme/navbar.html.php")): ?>
        <?php require "$folder".'/'."$this->theme/navbar.html.php"; ?>
    <?php endif; ?>
<?php endforeach; ?>
<!-- end <?= $this->theme."/"."navbar.html" ?> -->


    [region header.bottom ]

    <div class="container-fluid pb-3 flex-grow-1 d-flex flex-column flex-sm-row overflow-auto">

        <?php if($this->getValue('page.regions.sidebar.first', $page ?? null) 
|| $this->getValue('site.regions.sidebar.first', $site ?? null)): ?>

        
<!-- <?= $this->theme."/"."aside.html" ?> -->
<?php foreach(N('folder.templates') as $key => $folder) : ?>
    <?php if(is_file("$folder".'/'."$this->theme/aside.html.php")): ?>
        <?php require "$folder".'/'."$this->theme/aside.html.php"; ?>
    <?php endif; ?>
<?php endforeach; ?>
<!-- end <?= $this->theme."/"."aside.html" ?> -->

        <?php endif; ?>
        <div class="container col overflow-auto h-100 ">


            [region breadcrumb]
            
<!-- <?= $this->theme."/"."breadcrumb.html" ?> -->
<?php foreach(N('folder.templates') as $key => $folder) : ?>
    <?php if(is_file("$folder".'/'."$this->theme/breadcrumb.html.php")): ?>
        <?php require "$folder".'/'."$this->theme/breadcrumb.html.php"; ?>
    <?php endif; ?>
<?php endforeach; ?>
<!-- end <?= $this->theme."/"."breadcrumb.html" ?> -->

            [render "page.region.breadcrumb"]

            <main class="container col overflow-auto h-100 border rounded-3 p-3">

                [region help]

                [region main.top]
                <?php if($this->getValue('page.admin', $page ?? null)): ?>
                
<!-- <?= $this->theme."/"."admin.html" ?> -->
<?php foreach(N('folder.templates') as $key => $folder) : ?>
    <?php if(is_file("$folder".'/'."$this->theme/admin.html.php")): ?>
        <?php require "$folder".'/'."$this->theme/admin.html.php"; ?>
    <?php endif; ?>
<?php endforeach; ?>
<!-- end <?= $this->theme."/"."admin.html" ?> -->

                <?php endif; ?>



                <?php if($this->getValue('site.autoTitle', $site ?? null)): ?>
                <h1><?= $this->getValue('page.title', $page ?? null) ?></h1>
                <?php endif; ?>

                [content]
                [region main.bottom]
            </main>

        </div>
    </div>

    <?php if($this->getValue('page.regions.footer', $page ?? null)): ?>
    <footer class="pt-3 mt-4 text-body-secondary border-top">
        [region footer]
    </footer>
    <?php endif; ?>

    <script src="<?= $this->getValue('asset', $asset ?? null) ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <?php if($this->getValue('site.load.highlight.js', $site ?? null)): ?>
    <link rel="stylesheet" href="<?= $this->getValue('asset', $asset ?? null) ?>/vendor/highlight/<?= $this->getValue('site.load.highlight.js.theme', $site ?? null)  ?>.min.css">
    <script src="<?= $this->getValue('asset', $asset ?? null) ?>/vendor/highlight/highlight.min.js"></script>
    <script>//hljs.highlightAll();
        document.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('pre code').forEach((el) => {
                className = el.className;
                console.log("className = ".className);
                hljs.highlightElement(el);
            });
        });
    </script>

    <?php endif; ?>
    <?php if($this->getValue('site.microlight', $site ?? null)): ?>
    <script src="<?= $this->getValue('asset', $asset ?? null) ?>/vendor/js/microlight.js"></script>
    <script>microlight.reset();</script>
    <?php endif; ?>

    <?php if($this->getValue('site.jquery', $site ?? null)): ?>
    <script src="<?= $this->getValue('asset', $asset ?? null) ?>/vendor/js/jquery.js"></script>
    <?php endif; ?>

    <script src="<?= $this->getValue('asset', $asset ?? null) ?>/namaskar.js"></script>

</body>

</html>
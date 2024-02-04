<?php if($this->getValue('theme.navbar.model', $theme ?? null) === 100): ?>
<nav class="navbar navbar-expand<?= $this->getValue('theme.navbar.expand', $theme ?? null) != 'always' ? '-' . $this->getValue('theme.navbar.expand', $theme ?? null) : ''  ?> <?= $this->getValue('theme.navbar.position', $theme ?? null)  ?> navbar-dark bg-dark" aria-label="Sixth navbar example">
  <div class="<?= $this->getValue('theme.navbar.container', $theme ?? null)  ?>">
    <a class="navbar-brand" href="#"><?= $this->getValue('site.name', $site ?? null)  ?></a>
    <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbar"
      aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <?php if($this->getValue('site.menu.main', $site ?? null)): ?>
    <div class=" navbar-collapse collapse navbar-dark bg-dark" id="navbar">
      <ul class="navbar-nav me-auto mb-2 mb-md-0 ">
         
                <?php if( ($this->getValue('site.menu.main', $site ?? null))) : ?>

                <?php foreach($this->getValue('site.menu.main', $site ?? null) as $key => $item) : ?>
                <?php 
                $loop = [];
                $loop['index'] =  $key + 1;
                $loop['index0'] =  $key;
                $loop['key'] =  $key;
                $loop['first'] =  $key === 0 ;
                $loop['last'] =  $key === count($this->getValue('site.menu.main', $site ?? null)) -1 ;
                $item['_'] =  $loop ;

                ?>    
               
        <li class="nav-item">
          <?php if($this->getValue('item.active', $item ?? null)): ?>
          <span class="nav-link active" aria-current="page"><?= $this->getValue('item.label', $item ?? null)  ?>
          </span>
          <?php else: ?>
          <a href="<?= $this->getValue('homepath', $homepath ?? null)  ?>/<?= $this->getValue('item.url', $item ?? null)  ?>" class="nav-link"><?= $this->getValue('item.label', $item ?? null)  ?>
          </a>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
                <?php endif; ?>
      </ul>

    </div>
    <?php endif; ?>

  </div>
</nav>
<?php endif; ?>
<?php if($this->getValue('theme.navbar.model', $theme ?? null) === 10): ?>
<header>


  <nav class="navbar <?= $this->getValue('theme.navbar.position', $theme ?? null)  ?> navbar-expand<?= $this->getValue('navbar.expand', $navbar ?? null) != 'always'? '-'.navbar.expand : ''  ?> navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="#"><?= $this->getValue('site.name', $site ?? null) ?></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample05"
        aria-controls="navbarsExample05" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>


      <div class="collapse navbar-collapse" id="navbarsExample05">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Link</a>
          </li>
          <li class="nav-item">
            <a class="nav-link disabled" aria-disabled="true">Disabled</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">Action</a></li>
              <li><a class="dropdown-item" href="#">Another action</a></li>
              <li><a class="dropdown-item" href="#">Something else here</a></li>
            </ul>
          </li>
        </ul>

      </div>
    </div>
  </nav>
</header>
<?php endif; ?>

<?php if($this->getValue('theme.navbar.model', $theme ?? null) === 1): ?>
<header class="mb-4">
  <nav class="navbar  bg-<?= $this->getValue('theme.navbar.bgcolor', $theme ?? null)  ?> navbar-<?= $this->getValue('theme.navbar.type', $theme ?? null)  ?> <?= $this->getValue('theme.navbar.position', $theme ?? null)  ?>
   navbar-expand-<?= $this->getValue('theme.navbar.expand', $theme ?? null) ?? 'none' ?> 
    bg-<?= $this->getValue('theme.navbar.bg', $theme ?? null)  ?>">


    <div class="<?= $this->getValue('theme.navbar.container', $theme ?? null)  ?>  bg-<?= $this->getValue('theme.navbar.bg', $theme ?? null)  ?> mainlinks">
      
<!-- <?= $this->theme."/"."hamburger.html" ?> -->
<?php foreach(N('folder.templates') as $key => $folder) : ?>
    <?php if(is_file("$folder".'/'."$this->theme/hamburger.html.php")): ?>
        <?php require "$folder".'/'."$this->theme/hamburger.html.php"; ?>
    <?php endif; ?>
<?php endforeach; ?>
<!-- end <?= $this->theme."/"."hamburger.html" ?> -->

      <a class="navbar-brand" href="<?= $this->getValue('homepath', $homepath ?? null)  ?>/<?= $this->getValue('site.homeURL', $site ?? null)  ?>">
        <?php if($this->getValue('site.logo.svg', $site ?? null)): ?>
        <svg alt="" class="logo" width="<?= $this->getValue('site.logo.width', $site ?? null) ?>" height="<?= $this->getValue('site.logo.height', $site ?? null) ?>">
          <use xlink:href="<?= $this->getValue('media', $media ?? null) ?>/<?= $this->getValue('site.logo.svg', $site ?? null) ?>"></use>
        </svg>
        <?php endif; ?>
        <?php if($this->getValue('site.logo.img', $site ?? null)): ?>
        <img alt="" class="logo" width="<?= $this->getValue('site.logo.width', $site ?? null) ?>" height="<?= $this->getValue('site.logo.height', $site ?? null) ?>"
          src="<?= $this->getValue('media', $media ?? null) ?>/<?= $this->getValue('site.logo.img', $site ?? null) ?>" />

        <?php endif; ?>
        <strong><?= $this->getValue('site.name', $site ?? null)  ?></strong></a>
      
<!-- <?= $this->theme."/"."language-menu.html" ?> -->
<?php foreach(N('folder.templates') as $key => $folder) : ?>
    <?php if(is_file("$folder".'/'."$this->theme/language-menu.html.php")): ?>
        <?php require "$folder".'/'."$this->theme/language-menu.html.php"; ?>
    <?php endif; ?>
<?php endforeach; ?>
<!-- end <?= $this->theme."/"."language-menu.html" ?> -->


      <?php if($this->getValue('site.menu.main', $site ?? null)): ?>
      <div class=" collapse navbar-collapse h-100 bg-<?= $this->getValue('theme.navbar.bg', $theme ?? null)  ?>" id="navbarCollapse">
        <ul class="navbar-nav me-auto mb-2 mb-md-0 ">
           
                <?php if( ($this->getValue('site.menu.main', $site ?? null))) : ?>

                <?php foreach($this->getValue('site.menu.main', $site ?? null) as $key => $item) : ?>
                <?php 
                $loop = [];
                $loop['index'] =  $key + 1;
                $loop['index0'] =  $key;
                $loop['key'] =  $key;
                $loop['first'] =  $key === 0 ;
                $loop['last'] =  $key === count($this->getValue('site.menu.main', $site ?? null)) -1 ;
                $item['_'] =  $loop ;

                ?>    
               
          <li class="nav-item">

            <?php if($this->getValue('item.active', $item ?? null)): ?>
            <span class="nav-link active" aria-current="page"><?= $this->getValue('item.label', $item ?? null)  ?>
            </span>
            <?php else: ?>
            <a href="<?= $this->getValue('homepath', $homepath ?? null)  ?>/<?= $this->getValue('item.url', $item ?? null)  ?>" class="nav-link"><?= $this->getValue('item.label', $item ?? null)  ?>
            </a>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
                <?php endif; ?>
        </ul>
        <div id="submenus" class="d-block d-md-none"></div>
      </div>
      <?php endif; ?>

    </div>
  </nav>

</header>
<?php endif; ?>


<?php if($this->getValue('theme.navbar.model', $theme ?? null) === 3): ?>
<header data-bs-theme="dark">
  <div class="collapse text-bg-dark" id="navbarHeader">
    <div class="container">
      <div class="row">
        <div class="col-sm-8 col-md-7 py-4">
          <h4>About</h4>
          <p class="text-body-secondary">Add some information about the album below, the author, or any other
            background context. Make it a few sentences long so folks can pick up some informative tidbits.
            Then, link them off to some social networking sites or contact information.</p>
        </div>
        <div class="col-sm-4 offset-md-1 py-4">
          <h4>Contact</h4>
          <ul class="list-unstyled">
            <li><a href="#" class="text-white">Follow on Twitter</a></li>
            <li><a href="#" class="text-white">Like on Facebook</a></li>
            <li><a href="#" class="text-white">Email me</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
      <a href="#" class="navbar-brand d-flex align-items-center">
        <svg class="bi" width="32" height="32" alt="">
          <use xlink:href="<?= $this->getValue('asset', $asset ?? null) ?>/shapes.svg#gear"></use>
        </svg>

        <strong>Album</strong>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarHeader"
        aria-controls="navbarHeader" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>
  </div>
</header>
<?php endif; ?>

<?php if($this->getValue('theme.navbar.model', $theme ?? null) === 4): ?>
<div class="container">
  <header class="border-bottom lh-1 py-3 ">
    <div class="row flex-nowrap justify-content-between align-items-center">
      <div class="col-4 pt-1">
        <a class="link-secondary" href="#">Subscribe</a>
      </div>
      <div class="col-4 text-center">
        <a class="blog-header-logo text-body-emphasis text-decoration-none" href="#">Large</a>
      </div>
      <div class="col-4 d-flex justify-content-end align-items-center">
        <a class="link-secondary" href="#" aria-label="Search">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor"
            stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="mx-3" role="img" viewBox="0 0 24 24">
            <title>Search</title>
            <circle cx="10.5" cy="10.5" r="7.5" />
            <path d="M21 21l-5.2-5.2" />
          </svg>
        </a>
        <a class="btn btn-sm btn-outline-secondary" href="#">Sign up</a>
      </div>
    </div>
  </header>

  <div class="nav-scroller py-1 mb-3 border-bottom">
    <nav class="nav nav-underline justify-content-between">
      <a class="nav-item nav-link link-body-emphasis active" href="#">World</a>
      <a class="nav-item nav-link link-body-emphasis" href="#">U.S.</a>
      <a class="nav-item nav-link link-body-emphasis" href="#">Technology</a>
      <a class="nav-item nav-link link-body-emphasis" href="#">Culture</a>
      <a class="nav-item nav-link link-body-emphasis" href="#">Opinion</a>
      <a class="nav-item nav-link link-body-emphasis" href="#">Science</a>

    </nav>
  </div>
</div>

<?php endif; ?>
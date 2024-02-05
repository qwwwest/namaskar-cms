<?php if($this->getValue('page.breadcrumb', $page ?? null)): ?>

<div class="container">
  <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider: '<?= $this->getValue('theme.breadcrumb.divider', $theme ?? null)  ?>';">
    <ol class="breadcrumb p-0 m-1 ">
       
                <?php if( ($this->getValue('page.breadcrumb', $page ?? null))) : ?>

                <?php foreach($this->getValue('page.breadcrumb', $page ?? null) as $key => $item) : ?>
                <?php 
                $loop = [];
                $loop['index'] =  $key + 1;
                $loop['index0'] =  $key;
                $loop['key'] =  $key;
                $loop['first'] =  $key === 0 ;
                $loop['last'] =  $key === count($this->getValue('page.breadcrumb', $page ?? null)) -1 ;
                $item['_'] =  $loop ;

                ?>    
               

      <?php if($this->getValue('loop.first', $loop ?? null)): ?>
      <li class="breadcrumb-item">
        <a class="link-body-emphasis fw-semibold text-decoration-none" href="<?= $this->getValue('homepath', $homepath ?? null)  ?>/<?= $this->getValue('item.url', $item ?? null) ?>">
          <svg class="bi" width="24" height="24" alt="">
            <use xlink:href="<?= $this->getValue('asset', $asset ?? null) ?>/shapes.svg#home"></use>
          </svg>
          <span class="visually-hidden">Home</span>
        </a>
      </li>
      <?php endif; ?>
      <?php if(! $this->getValue('loop.first', $loop ?? null) 
&& ! $this->getValue('loop.last', $loop ?? null)): ?>
      <li class="breadcrumb-item">
        <a class="link-body-emphasis fw-semibold text-decoration-none" href="<?= $this->getValue('homepath', $homepath ?? null)  ?>/<?= $this->getValue('item.url', $item ?? null) ?>"><?= $this->getValue('item.title', $item ?? null)           ?> </a>
      </li>
      <?php endif; ?>
      <?php if($this->getValue('loop.last', $loop ?? null)): ?>
      <li class="breadcrumb-item active" aria-current="page">
        <?= $this->getValue('item.title', $item ?? null)  ?>
      </li>
      <?php endif; ?>
      <?php endforeach; ?>
                <?php endif; ?>
    </ol>
  </nav>
</div>
<?php endif; ?>
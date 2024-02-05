<?php if($this->getValue('site.language.menu', $site ?? null)): ?>
<ul class="navbar-nav navbar-expand ml-auto mb-2 mb-md-0 language-menu">
     
                <?php if( ($this->getValue('site.language.menu', $site ?? null))) : ?>

                <?php foreach($this->getValue('site.language.menu', $site ?? null) as $key => $item) : ?>
                <?php 
                $loop = [];
                $loop['index'] =  $key + 1;
                $loop['index0'] =  $key;
                $loop['key'] =  $key;
                $loop['first'] =  $key === 0 ;
                $loop['last'] =  $key === count($this->getValue('site.language.menu', $site ?? null)) -1 ;
                $item['_'] =  $loop ;

                ?>    
               
    <li class="nav-item">
        <a href="<?= $this->getValue('homepath', $homepath ?? null)  ?>/<?= $this->getValue('item.url', $item ?? null)  ?>" class="nav-link<?= $this->getValue('item.active', $item ?? null) ? ' active' : '' ?>"> <?= $this->getValue('item.label', $item ?? null)  ?></a>
    </li>
    <?php if(! $this->getValue('loop.last', $loop ?? null)): ?><li class=" nav-link nav-item nav-sep">|</li><?php endif; ?>
    <?php endforeach; ?>
                <?php endif; ?>
</ul>
<?php endif; ?>
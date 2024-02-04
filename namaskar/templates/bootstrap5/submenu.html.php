<ul class="submenu<?= $this->getValue('depth', $depth ?? null) === 1 ? " top" : ""  ?>">
     
                <?php if( ($this->getValue('elts', $elts ?? null))) : ?>

                <?php foreach($this->getValue('elts', $elts ?? null) as $key => $elt) : ?>
                <?php 
                $loop = [];
                $loop['index'] =  $key + 1;
                $loop['index0'] =  $key;
                $loop['key'] =  $key;
                $loop['first'] =  $key === 0 ;
                $loop['last'] =  $key === count($this->getValue('elts', $elts ?? null)) -1 ;
                $item['_'] =  $loop ;

                ?>    
               

    <?php if($this->getValue('elt.children', $elt ?? null) 
&& $this->isElementAccessible( $this->getValue('elt', $elt ?? null) )): ?>
    <li class=" submenu level<?= $this->getValue('depth', $depth ?? null)  ?>">
        <?php if($this->getValue('type', $type ?? null) === 'dynamic'): ?>
        <button class="btn btn-toggle align-items-center rounded <?= $this->getValue('elt.active', $elt ?? null) 
|| $this->getValue('level', $level ?? null) > 1 ? '': ' collapsed' ?>"
            data-bs-toggle="collapse" data-bs-target="#id<?= $this->getValue('elt.id', $elt ?? null) ?>"
            aria-expanded="<?= $this->getValue('elt.active', $elt ?? null) 
|| $this->getValue('level', $level ?? null) > 1 ? 'true': 'false' ?>">
            <svg class="bi" width="24" height="24" alt="">
                <use xlink:href="<?= $this->getValue('asset', $asset ?? null) ?>/shapes.svg#chevronright"></use>
            </svg>

        </button>
        <?php endif; ?>
        <a href="<?= $this->getValue('homepath', $homepath ?? null)  ?>/<?= $this->getValue('elt.url', $elt ?? null)  ?>"
            class=" rounded level<?= $this->getValue('depth', $depth ?? null)  ?><?= $this->getValue('elt.current', $elt ?? null) ? ' current': ' collapsed' ?>">
            <?= $this->getValue('elt.title', $elt ?? null)  ?>
        </a>
        <div class="collapse<?= $this->getValue('elt.active', $elt ?? null) 
|| $this->getValue('level', $level ?? null) > 1 ? ' show active': '' ?>" id="id<?= $this->getValue('elt.id', $elt ?? null) ?>">
            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                <?php $this->include('submenu.html', [
'elts' => $this->getValue('elt.children', $elt ?? null),
'type' => $this->getValue('type', $type ?? null),
'level' => $this->getValue('level', $level ?? null) - 1,
'depth' => $this->getValue('depth', $depth ?? null) + 1,
'parentActive' => $this->getValue('parentActive', $parentActive ?? null),
'homepath' => $this->getValue('homepath', $homepath ?? null),
]) ?>
            </ul>
        </div>
    </li>
    <?php endif; ?>
    <?php if(! $this->getValue('elt.children', $elt ?? null) 
&& $this->isElementAccessible( $this->getValue('elt', $elt ?? null) )): ?>
    <li class="nav-item"><a href="<?= $this->getValue('homepath', $homepath ?? null)  ?>/<?= $this->getValue('elt.url', $elt ?? null)  ?>"
            class="rounded level<?= $this->getValue('depth', $depth ?? null)  ?><?= $this->getValue('elt.active', $elt ?? null) ? ' show active': '' ?><?= $this->getValue('elt.current', $elt ?? null) ? '  current': '' ?>"><?= $this->getValue('elt.title', $elt ?? null) ?> </a>
    </li>
    <?php endif; ?>

    <?php endforeach; ?>
                <?php endif; ?>
</ul>
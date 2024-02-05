<!-- menu-links-->
<ul>
     
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
               
    <?php if($this->getValue('elt.active', $elt ?? null) 
|| $this->getValue('level', $level ?? null) 
|| $this->getValue('parentActive', $parentActive ?? null)): ?>
    <li class="nav-item">
        <a href="<?= $this->getValue('elt.absUrl', $elt ?? null)  ?>" class="nav-link <?= $this->getValue('elt.active', $elt ?? null)  ?>"><?= $this->getValue('elt.title', $elt ?? null) ?></a>

        <?php if($this->getValue('elt.children', $elt ?? null)): ?>

        <?php $this->include('menu-links.html', [
'elts' => $this->getValue('elt.children', $elt ?? null),
'level' => $this->getValue('level', $level ?? null) - 1,
'parentActive' => $this->getValue('parentActive', $parentActive ?? null),
]) ?>

        <?php endif; ?>

    </li>
    <?php endif; ?>
    <?php endforeach; ?>
                <?php endif; ?>
</ul>
<!-- /menu-links-->
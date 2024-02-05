

<?php if($this->getValue('type', $type ?? null) === "highlight"): ?>

<div class="alert alert-premium<?= $this->getValue('dismissible', $dismissible ?? null) ? ' alert-dismissible fade show' : ''  ?> " role="alert">
  <?php if($this->getValue('dismissible', $dismissible ?? null)): ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
  </button>
  <?php endif; ?>
  <h4 class="alert-heading"><svg class="bi flex-shrink-0 me-2" role="img">
      <?php if($this->getValue('type', $type ?? null)): ?>
      <use xlink:href="<?= $this->getValue('absroot', $absroot ?? null) ?>/asset/shapes.svg#info"></use>
      <?php endif; ?>
    </svg><?= $this->getValue('title', $title ?? null) ?></h4>


</div>


{% else if title %}
<div class="alert alert-<?= $this->getValue('type', $type ?? null) ?><?= $this->getValue('dismissible', $dismissible ?? null) ? ' alert-dismissible fade show' : ''  ?> " role="alert">
  <?php if($this->getValue('dismissible', $dismissible ?? null)): ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
  </button>
  <?php endif; ?>
  <h4 class="alert-heading"><svg class="bi flex-shrink-0 me-2" role="img">
      <?php if($this->getValue('type', $type ?? null)): ?>
      <use xlink:href="<?= $this->getValue('absroot', $absroot ?? null) ?>/asset/shapes.svg#<?= $this->getValue('type', $type ?? null) ?>"></use>
      <?php endif; ?>
    </svg><?= $this->getValue('title', $title ?? null) ?></h4>
  <p><?= $this->getValue('content', $content ?? null)  ?></p>

</div>

<?php else: ?>

<div class="alert alert-<?= $this->getValue('type', $type ?? null)  ?><?= $this->getValue('dismissible', $dismissible ?? null) ? ' alert-dismissible fade show' : ''  ?> d-flex align-items-center"
  role="alert">

  <svg class="bi flex-shrink-0 me-2" role="img">
    <use xlink:href="<?= $this->getValue('absroot', $absroot ?? null) ?>/asset/shapes.svg#<?= $this->getValue('type', $type ?? null) ?>">
  </svg>
  <div><?= $this->getValue('content', $content ?? null)  ?>
    <?php if($this->getValue('dismissible', $dismissible ?? null)): ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>